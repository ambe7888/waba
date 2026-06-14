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

            this.initEchoListener();
        }

        /**
         * Initialize Echo listener to handle call state transitions dynamically
         */
        initEchoListener() {
            if (window.Echo && window.appConfig && window.appConfig.vendorUid) {
                window.Echo.private(`vendor-channel.${window.appConfig.vendorUid}`)
                    .listen('.VendorChannelBroadcast', (data) => {
                        // If call permission is updated for current contact, reload or trigger UI updates
                        if (data.eventModelUpdate && data.eventModelUpdate.contact) {
                            const updatedContact = data.eventModelUpdate.contact;
                            const activeChatData = this.getActiveContactAlpineData();
                            if (activeChatData && activeChatData.contact && activeChatData.contact._uid === updatedContact._uid) {
                                // Update Alpine.js contact details reactive state
                                activeChatData.contact = updatedContact;
                            }
                        }
                    });
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
                    const answerSdp = resData.data.sdp;

                    // Set remote answer from Meta
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription({
                        type: 'answer',
                        sdp: answerSdp
                    }));
                } else {
                    this.endCallLocally();
                    showErrorMessage(resData.message || "Meta a rejeté la requête d'appel.");
                }

            } catch (err) {
                console.error("Failed to start WebRTC Call", err);
                this.endCallLocally();
                showErrorMessage("Impossible de démarrer l'appel (vérifiez les autorisations de votre micro).");
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
