<div id="lwThreeCxPanel" class="lw-three-cx-panel" style="display:none;">
    <div class="lw-three-cx-panel-header">
        <div>
            <strong id="lwThreeCxPanelTitle">{{ __tr('Appel 3CX') }}</strong>
            <div id="lwThreeCxPanelSubtitle" class="text-muted small"></div>
        </div>
        <button type="button" class="btn btn-sm btn-light" onclick="window.ThreeCxCalling.close()" title="{{ __tr('Fermer') }}">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <iframe id="lwThreeCxPanelFrame" src="about:blank" allow="microphone; autoplay"></iframe>
</div>

<style>
    .lw-three-cx-panel {
        position: fixed;
        top: 0;
        right: 0;
        width: 420px;
        max-width: 100vw;
        height: 100vh;
        background: #fff;
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
        z-index: 9998;
        display: flex;
        flex-direction: column;
    }

    .lw-three-cx-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        flex-shrink: 0;
    }

    .lw-three-cx-panel iframe {
        flex: 1;
        border: 0;
        width: 100%;
    }
</style>

<script>
    window.ThreeCxCalling = {
        baseUrl: @json(getVendorSettings('three_cx_webclient_url')),

        open: function(contact) {
            if (!this.baseUrl) {
                if (window.showErrorMessage) {
                    window.showErrorMessage(@json(__tr('Veuillez d\'abord configurer l\'URL de votre Web Client 3CX dans les paramètres (Appels 3CX).')));
                }
                return;
            }
            var phone = (contact && (contact.wa_id || contact.phone_number)) || '';
            var base = this.baseUrl.replace(/\/+$/, '');
            var url = base + '/webclient/#/call?phone=' + encodeURIComponent(phone);

            document.getElementById('lwThreeCxPanelSubtitle').innerText = (contact && (contact.full_name || contact.wa_id)) || '';
            document.getElementById('lwThreeCxPanelFrame').src = url;
            document.getElementById('lwThreeCxPanel').style.display = 'flex';
        },

        close: function() {
            document.getElementById('lwThreeCxPanel').style.display = 'none';
            document.getElementById('lwThreeCxPanelFrame').src = 'about:blank';
        }
    };
</script>
