<div id="lw-whatsapp-call-overlay" class="lw-call-overlay" style="display: none;">
    <div class="lw-call-card">
        <!-- Glassmorphism Card content -->
        <div class="lw-call-card-content">
            <!-- Call direction badge -->
            <div class="lw-call-direction-badge" id="lw-call-direction-badge">
                <i class="fas fa-phone-alt"></i>
                <span id="lw-call-direction-text">{{ __tr('Appel') }}</span>
            </div>

            <!-- User Avatar & Details -->
            <div class="lw-call-user-details mt-3">
                <div class="lw-call-avatar-container">
                    <div class="lw-call-pulse"></div>
                    <div class="lw-call-pulse-2"></div>
                    <div class="lw-call-avatar">
                        <span id="lw-call-avatar-initials">--</span>
                    </div>
                </div>
                <h3 id="lw-call-user-name" class="mt-3">--</h3>
                <p id="lw-call-user-phone">--</p>
            </div>

            <!-- Call Status -->
            <div class="lw-call-status-header mt-2">
                <span id="lw-call-status-text">{{ __tr('Initialisation...') }}</span>
            </div>

            <!-- Timer -->
            <div class="lw-call-timer mt-1" id="lw-call-timer" style="display: none;">00:00</div>
            
            <!-- Actions/Controls: active call (mute + hangup) -->
            <div class="lw-call-controls mt-4" id="lw-call-controls-active">
                <!-- Mic Mute Button -->
                <button id="lw-call-btn-mute" class="btn btn-circle btn-outline-light mr-3" onclick="window.WhatsJetCalling.toggleMute()" title="{{ __tr('Couper le micro') }}">
                    <i id="lw-call-btn-mute-icon" class="fas fa-microphone"></i>
                </button>

                <!-- End Call Button (Hangup) -->
                <button id="lw-call-btn-hangup" class="btn btn-circle btn-danger" onclick="window.WhatsJetCalling.endCall()" title="{{ __tr('Raccrocher') }}">
                    <i class="fas fa-phone fa-rotate-135" style="transform: rotate(135deg);"></i>
                </button>
            </div>

            <!-- Actions/Controls: incoming call (accept + reject) -->
            <div class="lw-call-controls mt-4" id="lw-call-controls-incoming" style="display: none;">
                <button id="lw-call-btn-reject" class="btn btn-circle btn-danger mr-3" onclick="window.WhatsJetCalling.rejectIncomingCall()" title="{{ __tr('Refuser') }}">
                    <i class="fas fa-phone fa-rotate-135" style="transform: rotate(135deg);"></i>
                </button>
                <button id="lw-call-btn-answer" class="btn btn-circle" style="background:#16a34a;color:#fff;" onclick="window.WhatsJetCalling.answerIncomingCall()" title="{{ __tr('Répondre') }}">
                    <i class="fas fa-phone"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Remote audio element for WebRTC playback -->
    <audio id="lw-call-remote-audio" autoplay></audio>
    <!-- Ringtone for incoming calls -->
    <audio id="lw-call-ringtone" loop>
        <source src="{{ asset('static-assets/audio/whatsapp_incoming_call.mp3') }}" type="audio/mpeg">
    </audio>
</div>

@push('vendorChannelBroadcastStack')
// Route callEvent from the main Echo listener (app.blade.php) to WhatsJetCalling
if (data.callEvent && window.WhatsJetCalling) {
    window.WhatsJetCalling.handleCallEvent(data.callEvent);
}
@endpush
