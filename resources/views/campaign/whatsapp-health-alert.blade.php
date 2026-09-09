@php
    $lwWabaId = getVendorSettings('whatsapp_business_account_id');
    $lwHealthData = getVendorSettings('whatsapp_health_status_data') ?: [];
    $lwHealthEntry = $lwWabaId ? ($lwHealthData[$lwWabaId] ?? null) : null;
    $lwHealthEntities = $lwHealthEntry['health_data']['health_status']['entities'] ?? [];
    $lwCanSendMessage = $lwHealthEntry['health_data']['health_status']['can_send_message'] ?? null;
    $lwBusinessEntity = collect($lwHealthEntities)->first(function ($entity) {
        return ($entity['entity_type'] ?? null) === 'BUSINESS';
    });
    $lwIsRestricted = $lwCanSendMessage && $lwCanSendMessage !== 'AVAILABLE';
@endphp
@if($lwIsRestricted && $lwWabaId)
    <div class="alert alert-dismissible fade show mb-4 p-3 border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap"
        style="background: #ffffff !important; border-left: 4px solid #ef4444 !important; border-radius: 12px !important;">
        <div class="d-flex align-items-center mb-2 mb-md-0">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center mr-3 shadow-sm"
                style="width: 38px; height: 38px; background-color: #ef4444 !important; flex-shrink: 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1rem; color: #ffffff !important;"></i>
            </div>
            <div>
                <strong>{{ __tr('Votre limite de facturation Meta est atteinte') }}</strong>
                <div class="text-muted small">
                    {{ __tr('Vous risquez de ne plus pouvoir envoyer de messages tant que ce n\'est pas réglé. Vérifiez votre compte de facturation Meta.') }}
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <a href="https://business.facebook.com/billing_hub/accounts/details/?{{ $lwBusinessEntity ? 'business_id=' . $lwBusinessEntity['id'] . '&' : '' }}asset_id={{ $lwWabaId }}&account_type=whatsapp-business-account"
                target="_blank" rel="noopener" class="btn btn-sm btn-danger lw-white-space-normal">
                <i class="fas fa-credit-card"></i> {{ __tr('Vérifier la facturation Meta') }}
            </a>
            <button type="button" class="close position-relative p-0 text-muted ml-2" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
@endif
