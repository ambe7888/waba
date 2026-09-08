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
            // Inbound-call-specific state, before it becomes the active call.
            this.incomingCallId = null;
            this.incomingOfferSdp = null;
            this.incomingCallerWaId = null;
            // Set when the agent hangs up while /calling/initiate is still
            // in flight -- we don't have Meta's call_id yet so we can't
            // terminate it, but the connect request has already reached
            // Meta and the customer's phone will still ring. Once the
            // call_id comes back we terminate it immediately.
            this.pendingCancel = false;
            const iceServers = [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ];
            // TURN relay (self-hosted coturn) -- required for audio to
            // connect when either side is behind a NAT/firewall plain STUN
            // can't traverse. window.WA_TURN_CONFIG is set inline in
            // chat.blade.php with a short-lived credential pair.
            if (window.WA_TURN_CONFIG && window.WA_TURN_CONFIG.urls) {
                iceServers.push({
                    urls: window.WA_TURN_CONFIG.urls,
                    username: window.WA_TURN_CONFIG.username,
                    credential: window.WA_TURN_CONFIG.credential
                });
            }
            this.rtcConfig = { iceServers: iceServers };

            // The overlay is included deep inside the chat header (a flex
            // row), and some ancestor there breaks its `position: fixed`
            // (it renders as a normal flex child instead of a full-screen
            // popup, stretching the header). Move it to be a direct child
            // of <body> so it always escapes that context.
            const overlay = document.getElementById('lw-whatsapp-call-overlay');
            if (overlay && overlay.parentElement !== document.body) {
                document.body.appendChild(overlay);
            }

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

            const { call_id, event: callEventType, sdp, sdp_type, status, from } = callEvent;

            // Handle status updates (RINGING, ACCEPTED) for a call we're already tracking.
            if (callEventType === 'status' && status) {
                if (!this.callId && !this.incomingCallId) { return; }
                console.log('Call status update:', status);
                const statusEl = document.getElementById('lw-call-status-text');
                if (statusEl) {
                    if (status === 'RINGING') { statusEl.innerText = "Sonnerie..."; }
                    else if (status === 'ACCEPTED') { statusEl.innerText = "Accepté, connexion..."; }
                }
                return;
            }

            if (callEventType === 'connect') {
                // If we already have an active outbound call in progress, this
                // "connect" is Meta's SDP answer to OUR offer.
                if (this.callId && this.peerConnection && sdp) {
                    // Meta can redeliver the same webhook; the backend now
                    // dedupes those, but guard here too -- once the peer
                    // connection has already negotiated (stable), setting the
                    // same remote answer again would throw and must NOT be
                    // treated as a real failure (it isn't one).
                    if (this.peerConnection.signalingState === 'stable') {
                        console.log('Duplicate connect event for an already-negotiated call, ignoring.');
                        return;
                    }
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

                // Otherwise: a genuinely new inbound call.
                if (this.callId || this.incomingCallId) {
                    // This browser tab is busy on another call. Other agents
                    // of the same vendor also received this broadcast and may
                    // be free -- do NOT reject on Meta's side from here, that
                    // would hang up the call for everyone. Just don't show it
                    // on this busy tab.
                    return;
                }
                this.showIncomingCall(call_id, sdp, from);
                return;
            }

            // Handle call termination from remote side
            if (callEventType === 'terminate') {
                if (call_id !== this.callId && call_id !== this.incomingCallId) { return; }
                console.log('Call terminated by remote side.');
                this.endCallLocally();
                showSuccessMessage("L'appel a été terminé.");
                return;
            }
        }

        /**
         * Display the incoming-call overlay with Accept/Reject controls and
         * play the ringtone, without touching the mic/peer connection yet --
         * those are only created once the agent actually answers.
         */
        showIncomingCall(callId, offerSdp, callerWaId) {
            this.incomingCallId = callId;
            this.incomingOfferSdp = offerSdp;
            this.incomingCallerWaId = callerWaId;

            const contact = this.findContactByWaId(callerWaId);
            if (contact) { this.currentCallContactUid = contact._uid; }

            const displayName = (contact && (contact.full_name || contact.name)) || (callerWaId ? ('+' + callerWaId) : 'Numéro inconnu');
            const initials = (contact && contact.name_initials) ? contact.name_initials : (displayName.charAt(0).toUpperCase() || '?');

            document.getElementById('lw-call-avatar-initials').innerText = initials;
            document.getElementById('lw-call-user-name').innerText = displayName;
            document.getElementById('lw-call-user-phone').innerText = callerWaId ? ('+' + callerWaId) : '';
            this.setDirectionBadge('incoming');
            document.getElementById('lw-call-status-text').innerText = "Appel entrant...";
            document.getElementById('lw-call-status-text').style.color = '';
            document.getElementById('lw-call-timer').style.display = 'none';
            document.getElementById('lw-call-controls-active').style.display = 'none';
            document.getElementById('lw-call-controls-incoming').style.display = 'flex';
            document.getElementById('lw-whatsapp-call-overlay').style.display = 'flex';

            const ringtone = document.getElementById('lw-call-ringtone');
            if (ringtone) { ringtone.currentTime = 0; ringtone.play().catch(function() {}); }
        }

        /**
         * Best-effort lookup of a contact's saved name from the chat
         * sidebar's already-loaded contact list, by WhatsApp id. Used to
         * show the real contact name on incoming calls instead of just the
         * raw phone number.
         */
        findContactByWaId(waId) {
            if (!waId) { return null; }
            try {
                const alpineData = this.getActiveContactAlpineData();
                if (alpineData && Array.isArray(alpineData.contacts)) {
                    return alpineData.contacts.find(function(c) { return c.wa_id === waId; }) || null;
                }
            } catch (e) {}
            return null;
        }

        /**
         * Update the small "Appel entrant / Appel sortant" badge at the top
         * of the call popup.
         */
        setDirectionBadge(direction) {
            const badgeIcon = document.querySelector('#lw-call-direction-badge i');
            const badgeText = document.getElementById('lw-call-direction-text');
            if (!badgeText) { return; }
            if (direction === 'incoming') {
                badgeText.innerText = 'Appel entrant';
                if (badgeIcon) { badgeIcon.className = 'fas fa-phone-alt'; badgeIcon.style.transform = ''; }
            } else {
                badgeText.innerText = 'Appel sortant';
                if (badgeIcon) { badgeIcon.className = 'fas fa-phone-alt'; badgeIcon.style.transform = 'scaleX(-1)'; }
            }
        }

        stopRingtone() {
            const ringtone = document.getElementById('lw-call-ringtone');
            if (ringtone) { try { ringtone.pause(); ringtone.currentTime = 0; } catch (e) {} }
        }

        /**
         * Answer an incoming call: build the SDP answer locally, then
         * pre_accept + accept it with Meta (backend enforces that order).
         */
        async answerIncomingCall() {
            if (!this.incomingCallId || !this.incomingOfferSdp) { return; }
            this.stopRingtone();

            const callId = this.incomingCallId;
            const offerSdp = this.incomingOfferSdp;
            document.getElementById('lw-call-controls-incoming').style.display = 'none';
            document.getElementById('lw-call-status-text').innerText = "Connexion...";

            try {
                if (!window.isSecureContext || !navigator.mediaDevices) {
                    throw new Error("L'accès au microphone nécessite une connexion sécurisée (HTTPS).");
                }

                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                this.peerConnection = new RTCPeerConnection(this.rtcConfig);
                this.localStream.getTracks().forEach(track => this.peerConnection.addTrack(track, this.localStream));
                this.peerConnection.ontrack = (event) => {
                    const remoteAudio = document.getElementById('lw-call-remote-audio');
                    if (remoteAudio) { remoteAudio.srcObject = event.streams[0]; }
                };
                this.peerConnection.onconnectionstatechange = () => this.handleConnectionStateChange();

                await this.peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: offerSdp }));
                const answer = await this.peerConnection.createAnswer();
                await this.peerConnection.setLocalDescription(answer);
                await this.waitForIceGathering();

                const finalSdp = this.peerConnection.localDescription.sdp;
                const csrfToken = this.getCsrfToken();
                const response = await fetch(`/vendor-console/calling/accept/${this.currentCallContactUid || 'unknown'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ call_id: callId, sdp: finalSdp })
                });
                const resData = await response.json();
                if (resData.reaction !== 1) {
                    throw new Error(resData.message || "Meta a rejeté la prise d'appel.");
                }

                this.callId = callId;
                this.incomingCallId = null;
                this.incomingOfferSdp = null;
                document.getElementById('lw-call-controls-active').style.display = 'flex';
                document.getElementById('lw-call-status-text').innerText = "Connecté";
                document.getElementById('lw-call-status-text').style.color = "#1B6F20";
                this.startTimer();
            } catch (err) {
                console.error('Failed to answer incoming call', err);
                showErrorMessage("Impossible de répondre à l'appel : " + err.message);
                this.silentlyRejectCall(callId);
                this.endCallLocally();
            }
        }

        /**
         * Reject an incoming call before answering it.
         */
        async rejectIncomingCall() {
            if (this.incomingCallId) {
                this.silentlyRejectCall(this.incomingCallId);
            }
            this.endCallLocally();
        }

        silentlyRejectCall(callId) {
            if (!callId) { return; }
            const csrfToken = this.getCsrfToken();
            fetch(`/vendor-console/calling/reject/${this.currentCallContactUid || 'unknown'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ call_id: callId })
            }).catch(function(e) { console.error('Error rejecting call', e); });
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
         * Wait for ICE gathering to complete (max 5 seconds timeout).
         * This is critical for Meta's ice-lite mode: we must send a complete
         * SDP Offer with all candidates, not an empty one.
         */
        waitForIceGathering() {
            return new Promise((resolve) => {
                // Already complete (happens in trickle ICE scenarios)
                if (this.peerConnection.iceGatheringState === 'complete') {
                    resolve();
                    return;
                }

                const timeout = setTimeout(() => {
                    console.warn('ICE gathering timed out after 5s, sending SDP anyway.');
                    this.peerConnection.removeEventListener('icegatheringstatechange', onGatheringStateChange);
                    resolve();
                }, 5000);

                const onGatheringStateChange = () => {
                    if (this.peerConnection.iceGatheringState === 'complete') {
                        clearTimeout(timeout);
                        this.peerConnection.removeEventListener('icegatheringstatechange', onGatheringStateChange);
                        resolve();
                    }
                };

                this.peerConnection.addEventListener('icegatheringstatechange', onGatheringStateChange);
            });
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
            this.pendingCancel = false;

            // Update UI overlay details
            document.getElementById('lw-call-avatar-initials').innerText = contact.name_initials || '--';
            document.getElementById('lw-call-user-name').innerText = contact.full_name || contact.wa_id;
            document.getElementById('lw-call-user-phone').innerText = '+' + contact.wa_id;
            this.setDirectionBadge('outgoing');
            document.getElementById('lw-call-status-text').innerText = "Initialisation...";
            document.getElementById('lw-call-timer').style.display = 'none';
            document.getElementById('lw-call-controls-incoming').style.display = 'none';
            document.getElementById('lw-call-controls-active').style.display = 'flex';
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

                // 4. Wait for ICE gathering to complete BEFORE sending to Meta.
                // Meta uses ice-lite (a=setup:passive): it only has ONE fixed candidate.
                // The browser must include ALL its own candidates in the SDP Offer
                // so Meta knows how to route UDP audio back to the browser.
                // Sending the SDP before gathering is complete = NO audio.
                document.getElementById('lw-call-status-text').innerText = "Préparation réseau...";
                await this.waitForIceGathering();

                const finalSdp = this.peerConnection.localDescription.sdp;
                console.log('ICE gathering complete. SDP Offer with candidates:\n', finalSdp);

                // 5. Send complete SDP Offer (with ICE candidates) to Meta
                document.getElementById('lw-call-status-text').innerText = "Appel en cours...";

                const csrfToken = this.getCsrfToken();
                const response = await fetch(`/vendor-console/calling/initiate/${contactUid}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        sdp: finalSdp
                    })
                });

                const resData = await response.json();
                if (resData.reaction === 1) {
                    const newCallId = resData.data.call_id;

                    // The agent hung up while this request was still in
                    // flight. Meta has already started ringing the customer
                    // -- terminate it right away instead of leaving it live.
                    if (this.pendingCancel) {
                        this.pendingCancel = false;
                        console.log('Call was cancelled before call_id arrived, terminating now:', newCallId);
                        fetch(`/vendor-console/calling/terminate/${contactUid}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.getCsrfToken() },
                            body: JSON.stringify({ call_id: newCallId })
                        }).catch(function(e) { console.error('Error terminating pre-cancelled call', e); });
                        return;
                    }

                    this.callId = newCallId;
                    console.log("Call initiated. Call ID:", this.callId);
                    console.log("Waiting for SDP answer from Meta via webhook/Echo...");

                    // The SDP answer will arrive asynchronously via the calls webhook,
                    // broadcasted through Echo. handleCallEvent() will process it.
                    document.getElementById('lw-call-status-text').innerText = "Sonnerie...";
                } else {
                    this.pendingCancel = false;
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
            } else if (this.currentCallContactUid && !this.callId) {
                // We're still waiting on /calling/initiate to come back with
                // Meta's call_id -- it can't be terminated yet. Flag it so
                // startCall() terminates it the instant the call_id arrives,
                // instead of leaving the customer's phone ringing.
                this.pendingCancel = true;
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
            this.stopRingtone();

            // Clear call identification
            this.currentCallContactUid = null;
            this.callId = null;
            this.incomingCallId = null;
            this.incomingOfferSdp = null;
            this.incomingCallerWaId = null;

            // Hide overlay
            const overlay = document.getElementById('lw-whatsapp-call-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
            const incomingControls = document.getElementById('lw-call-controls-incoming');
            if (incomingControls) { incomingControls.style.display = 'none'; }
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
