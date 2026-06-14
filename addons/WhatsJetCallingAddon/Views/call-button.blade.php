<template x-if="contact">
    <div class="d-inline-block lw-calling-actions-wrapper" x-data="{
        get callPermission() {
            if (!contact || !contact.__data) return 'none';
            const status = contact.__data.call_permission_status || 'none';
            if (status === 'granted') {
                const grantedAt = contact.__data.call_permission_granted_at;
                if (grantedAt) {
                    const diffTime = Math.abs(new Date() - new Date(grantedAt));
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays <= 7) {
                        return 'granted';
                    }
                }
                return 'expired';
            }
            return status;
        }
    }">
        <!-- Green phone button for granted call permission -->
        <a href="#" x-show="callPermission === 'granted'" class="lw-whatsapp-bar-icon-btn mr-2 btn-call-active" @click.prevent="window.WhatsJetCalling.startCall(contact._uid)" title="{{ __tr('Lancer un appel voix') }}">
            <i class="fas fa-phone text-white"></i>
        </a>
        
        <!-- Gray phone button for none/expired/declined/requested permission -->
        <a href="#" x-show="callPermission !== 'granted'" class="lw-whatsapp-bar-icon-btn mr-2 btn-call-inactive" @click.prevent="window.WhatsJetCalling.requestCallPermission(contact._uid)" :title="callPermission === 'requested' ? '{{ addslashes(__tr('Demande en cours... Cliquez pour renvoyer')) }}' : '{{ addslashes(__tr('Demander l\'autorisation d\'appel (Modèle Meta)')) }}'">
            <i class="fas fa-phone" :style="callPermission === 'requested' ? 'color: #ffc107 !important;' : 'color: rgba(255, 255, 255, 0.4) !important;'"></i>
        </a>
    </div>
</template>
