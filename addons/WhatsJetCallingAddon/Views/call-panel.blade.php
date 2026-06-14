<div id="lw-whatsapp-call-overlay" class="lw-call-overlay" style="display: none;">
    <div class="lw-call-card">
        <!-- Glassmorphism Card content -->
        <div class="lw-call-card-content">
            <!-- Call Status Header -->
            <div class="lw-call-status-header">
                <span id="lw-call-status-text">{{ __tr('Initialisation...') }}</span>
            </div>
            
            <!-- User Avatar & Details -->
            <div class="lw-call-user-details mt-4">
                <div class="lw-call-avatar-container">
                    <div class="lw-call-pulse"></div>
                    <div class="lw-call-pulse-2"></div>
                    <div class="lw-call-avatar">
                        <span id="lw-call-avatar-initials">--</span>
                    </div>
                </div>
                <h3 id="lw-call-user-name" class="mt-4 text-white font-weight-bold">--</h3>
                <p id="lw-call-user-phone" class="text-white-50">--</p>
            </div>
            
            <!-- Timer -->
            <div class="lw-call-timer mt-3" id="lw-call-timer" style="display: none;">00:00</div>
            
            <!-- Actions/Controls -->
            <div class="lw-call-controls mt-4">
                <!-- Mic Mute Button -->
                <button id="lw-call-btn-mute" class="btn btn-circle btn-outline-light mr-3" onclick="window.WhatsJetCalling.toggleMute()" title="{{ __tr('Couper le micro') }}">
                    <i id="lw-call-btn-mute-icon" class="fas fa-microphone"></i>
                </button>
                
                <!-- End Call Button (Hangup) -->
                <button id="lw-call-btn-hangup" class="btn btn-circle btn-danger" onclick="window.WhatsJetCalling.endCall()" title="{{ __tr('Raccrocher') }}">
                    <i class="fas fa-phone fa-rotate-135" style="transform: rotate(135deg);"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Remote audio element for WebRTC playback -->
    <audio id="lw-call-remote-audio" autoplay></audio>
</div>

@push('vendorChannelBroadcastStack')
// Route callEvent from the main Echo listener (app.blade.php) to WhatsJetCalling
if (data.callEvent && window.WhatsJetCalling) {
    window.WhatsJetCalling.handleCallEvent(data.callEvent);
}
@endpush
