/**
 * WhatsJet WhatsApp Cloud Calling Addon
 * WebRTC Calling and Signaling logic
 */
(function() {
    'use strict';

    class WhatsJetCalling {
        constructor() {
            this.peerConnection = null;
            this.localStream = null;
            this.currentCallContactUid = null;
            this.callId = null;
            this.timerInterval = null;
            this.callStartTime = null;
            this.isMuted = false;
            this.rtcConfig = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ]
            };

            // NOTE: Echo listener is NOT created here to avoid duplicate channel subscription.
            // app.blade.php already subscribes to vendor-channel.{vendorUid}.
            // call events are routed here via window.WhatsJetCalling.handleCallEvent()
            // from the @push('vendorChannelBroadcastStack') in the chat view.
        }

        /**
         * Handle incoming call signaling events from webhook via Echo broadcast
         */
        async handleCallEvent(callEvent) {
            console.log('Call event received via Echo:', JSON.stringify(callEvent));

            const { call_id, event: callEventType, sdp, sdp_type, status } = callEvent;

            // If we have no active call, ignore
            if (!this.callId) {
                console.log('No active call, ignoring call event.');
                return;
            }

            // Handle status updates (RINGING, ACCEPTED)
            if (callEventType === 'status' && status) {
                console.log('Call status update:', status);
                if (status === 'RINGING') {
                    document.getElementById('lw-call-status-text').innerText = "Sonnerie...";
                } else if (status === 'ACCEPTED') {
                    document.getElementById('lw-call-status-text').innerText = "Accepté, connexion...";
                }
                return;
            }

            // Handle "connect" event with SDP answer from Meta
            if (callEventType === 'connect' && sdp && this.peerConnection) {
                try {
                    const sdpTypeToUse = sdp_type === 'offer' ? 'offer' : 'answer';
                    console.log(`Setting remote SDP (${sdpTypeToUse}) from webhook...`);
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription({
                        type: sdpTypeToUse,
                        sdp: sdp
                    }));
                    console.log('Remote SDP set successfully. Call connected!');
                    document.getElementById('lw-call-status-text').innerText = "Connecté";
                    document.getElementById('lw-call-status-text').style.color = "#1B6F20";
                    this.startTimer();
                } catch (err) {
                    console.error('Failed to set remote SDP:', err);
                    this.endCall();
                    showErrorMessage("Erreur lors de la négociation WebRTC : " + err.message);
                }
                return;
            }

            // Handle call termination from remote side
            if (callEventType === 'terminate') {
                console.log('Call terminated by remote side.');
                this.endCallLocally();
                showSuccessMessage("L'appel a été terminé.");
                return;
            }
        }

        /**
         * Helper to get active contact data from Alpine.js chat window
         */
        getActiveContactAlpineData() {
            const chatWindow = document.getElementById('lwWhatsAppChatWindow');
            if (chatWindow) {
                try {
                    return Alpine.$data(chatWindow) || chatWindow.__x?.$data;
                } catch(e) {
                    console.error("Failed to retrieve Alpine data", e);
                }
            }
            return null;
        }

        /**
         * Request Call Permission (Sends Meta Consent Template Message)
         */
        async requestCallPermission(contactUid) {
            if (!confirm("Voulez-vous envoyer le modèle Meta de demande d'autorisation d'appel à ce client ?")) {
                return;
            }

            try {
                const csrfToken = this.getCsrfToken();
                const response = await fetch(`/vendor-console/calling/request-permission/${contactUid}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                if (data.reaction === 1) {
                    showSuccessMessage(data.message || "Demande d'autorisation d'appel envoyée.");
                } else {
                    showErrorMessage(data.message || "Erreur lors de l'envoi de la demande.");
                }
            } catch (error) {
                console.error("Error requesting permission", error);
                showErrorMessage("Une erreur réseau est survenue.");
            }
        }

        /**
         * Start Outbound Call via WebRTC
         */
        async startCall(contactUid) {
            const alpineData = this.getActiveContactAlpineData();
            if (!alpineData || !alpineData.contact) {
                showErrorMessage("Aucun contact sélectionné.");
                return;
            }

            const contact = alpineData.contact;
            this.currentCallContactUid = contactUid;
            this.isMuted = false;

            // Update UI overlay details
            document.getElementById('lw-call-avatar-initials').innerText = contact.name_initials || '--';
            document.getElementById('lw-call-user-name').innerText = contact.full_name || contact.wa_id;
            document.getElementById('lw-call-user-phone').innerText = '+' + contact.wa_id;
            document.getElementById('lw-call-status-text').innerText = "Initialisation...";
            document.getElementById('lw-call-timer').style.display = 'none';
            document.getElementById('lw-whatsapp-call-overlay').style.display = 'flex';
            
            // Reset mute button
            const muteBtn = document.getElementById('lw-call-btn-mute');
            muteBtn.classList.remove('muted');
            document.getElementById('lw-call-btn-mute-icon').className = 'fas fa-microphone';

            try {
                // Check if browser secure context or media devices API is available
                if (!window.isSecureContext || !navigator.mediaDevices) {
                    throw new Error("L'accès au microphone nécessite une connexion sécurisée (HTTPS). Veuillez vérifier que votre site utilise HTTPS.");
                }

                // 1. Get audio device permissions and stream
                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });

                // 2. Initialize PeerConnection
                this.peerConnection = new RTCPeerConnection(this.rtcConfig);

                // Add local tracks to peer connection
                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                });

                // Listen for remote audio stream
                this.peerConnection.ontrack = (event) => {
                    const remoteAudio = document.getElementById('lw-call-remote-audio');
                    if (remoteAudio) {
                        remoteAudio.srcObject = event.streams[0];
                    }
                };

                // Listen for connection state changes
                this.peerConnection.onconnectionstatechange = () => {
                    this.handleConnectionStateChange();
                };

                // 3. Create SDP Offer
                const offer = await this.peerConnection.createOffer();
                await this.peerConnection.setLocalDescription(offer);

                // 4. Send Offer to Backend (Meta Graph API)
                document.getElementById('lw-call-status-text').innerText = "Appel en cours...";
                
                const csrfToken = this.getCsrfToken();
                const response = await fetch(`/vendor-console/calling/initiate/${contactUid}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        sdp: this.peerConnection.localDescription.sdp
                    })
                });

                const resData = await response.json();
                if (resData.reaction === 1) {
                    this.callId = resData.data.call_id;
                    console.log("Call initiated. Call ID:", this.callId);
                    console.log("Waiting for SDP answer from Meta via webhook/Echo...");

                    // The SDP answer will arrive asynchronously via the calls webhook,
                    // broadcasted through Echo. handleCallEvent() will process it.
                    document.getElementById('lw-call-status-text').innerText = "Sonnerie...";
                } else {
                    this.endCallLocally();
                    showErrorMessage(resData.message || "Meta a rejeté la requête d'appel.");
                }

            } catch (err) {
                console.error("Failed to start WebRTC Call", err);
                this.endCall();
                let errMsg = "Impossible de démarrer l'appel.";
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errMsg += " L'accès au microphone a été refusé par le navigateur.";
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    errMsg += " Aucun microphone n'a été détecté.";
                } else {
                    errMsg += " Erreur WebRTC / Réseau : " + err.message;
                }
                showErrorMessage(errMsg);
            }
        }

        /**
         * Handle connection state change for WebRTC Peer Connection
         */
        handleConnectionStateChange() {
            if (!this.peerConnection) return;
            
            const state = this.peerConnection.connectionState;
            console.log("WebRTC Connection State changed to:", state);
            
            const statusText = document.getElementById('lw-call-status-text');
            if (!statusText) return;

            if (state === 'connected') {
                statusText.innerText = "Connecté";
                statusText.style.color = "#1B6F20";
                this.startTimer();
            } else if (state === 'disconnected' || state === 'failed') {
                statusText.innerText = "Déconnecté";
                statusText.style.color = "#ef4444";
                setTimeout(() => this.endCallLocally(), 1500);
            }
        }

        /**
         * Toggle mute/unmute local micro track
         */
        toggleMute() {
            if (!this.localStream) return;

            this.isMuted = !this.isMuted;
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = !this.isMuted;
            });

            const muteBtn = document.getElementById('lw-call-btn-mute');
            const muteIcon = document.getElementById('lw-call-btn-mute-icon');

            if (this.isMuted) {
                muteBtn.classList.add('muted');
                muteIcon.className = 'fas fa-microphone-slash';
            } else {
                muteBtn.classList.remove('muted');
                muteIcon.className = 'fas fa-microphone';
            }
        }

        /**
         * Hangup/Terminate Call
         */
        async endCall() {
            if (this.currentCallContactUid && this.callId) {
                try {
                    const csrfToken = this.getCsrfToken();
                    await fetch(`/vendor-console/calling/terminate/${this.currentCallContactUid}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            call_id: this.callId
                        })
                    });
                } catch(e) {
                    console.error("Error sending terminate request to backend", e);
                }
            }

            this.endCallLocally();
        }

        /**
         * Cleanup call local states, tracks, connection, timer and UI overlay
         */
        endCallLocally() {
            // Stop micro stream
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
                this.localStream = null;
            }

            // Close WebRTC Connection
            if (this.peerConnection) {
                this.peerConnection.close();
                this.peerConnection = null;
            }

            // Stop timer
            this.stopTimer();

            // Clear call identification
            this.currentCallContactUid = null;
            this.callId = null;

            // Hide overlay
            const overlay = document.getElementById('lw-whatsapp-call-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        }

        /**
         * Start Call timer
         */
        startTimer() {
            this.stopTimer();
            const timerEl = document.getElementById('lw-call-timer');
            if (!timerEl) return;

            timerEl.innerText = "00:00";
            timerEl.style.display = 'block';

            this.callStartTime = Date.now();
            this.timerInterval = setInterval(() => {
                const diff = Date.now() - this.callStartTime;
                const totalSec = Math.floor(diff / 1000);
                const min = String(Math.floor(totalSec / 60)).padStart(2, '0');
                const sec = String(totalSec % 60).padStart(2, '0');
                timerEl.innerText = `${min}:${sec}`;
            }, 1000);
        }

        /**
         * Stop Call timer
         */
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        }

        /**
         * Extract CSRF Token
         */
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }
    }

    // Helper functions to show messages (using LivelyWorks global functions if available)
    function showSuccessMessage(msg) {
        if (window.showSuccessMessage) {
            window.showSuccessMessage(msg);
        } else {
            alert(msg);
        }
    }

    function showErrorMessage(msg) {
        if (window.showErrorMessage) {
            window.showErrorMessage(msg);
        } else {
            alert(msg);
        }
    }

    // Register WhatsJetCalling globally
    window.WhatsJetCalling = new WhatsJetCalling();

})();
