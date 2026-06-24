@extends('layouts.app', ['title' => __tr('SaaS Messagerie')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('SaaS Messagerie'),
    'description' => __tr('Envoyez des messages de diffusion manuels à vos clients (vendeurs) via les templates approuvés de votre compte Super Admin.'),
    'class' => 'col-lg-12'
])

<div class="container-fluid mt--7 pb-5">
    <div class="row">
        <div class="col-xl-12 mb-5 mb-xl-0">
            <div class="card shadow">
                <div class="card-body">

                    @if(empty($saasAdminVendorId))
                        <div class="alert alert-warning">
                            {{ __tr("Vous devez d'abord configurer le compte expéditeur dans Configurations > SaaS Automation.") }}
                        </div>
                    @else

                        <form class="lw-ajax-form lw-form" data-show-processing="true" action="{{ route('central.vendors.broadcast.send') }}" method="POST">

                            <div class="row">
                                <div class="col-lg-8">
                                    
                                    <!-- Sélecteur de Template -->
                                    <div class="form-group">
                                        <label for="templateName">{{ __tr('Template WhatsApp à envoyer') }}</label>
                                        <select name="template_name" id="templateName" class="form-control custom-select" required>
                                            <option value="">{{ __tr('--- Sélectionner un Template ---') }}</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template->template_name }}">
                                                    {{ $template->template_name }} ({{ $template->language }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">
                                            {{ __tr("Seuls les templates approuvés de votre compte expéditeur SaaS sont listés. Choisissez de préférence des templates sans variables dynamiques.") }}
                                        </small>
                                    </div>
                                    
                                    <!-- Sélection des Vendeurs (Destinataires) -->
                                    <div class="form-group mt-4">
                                        <label>{{ __tr('Sélectionnez les Vendeurs Destinataires') }}</label>
                                        <div class="custom-control custom-checkbox mb-3">
                                            <input type="checkbox" class="custom-control-input" id="selectAllVendors">
                                            <label class="custom-control-label font-weight-bold" for="selectAllVendors">{{ __tr('Sélectionner Tous les Vendeurs Actifs') }}</label>
                                        </div>
                                        
                                        <div class="vendor-list-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: .375rem; padding: 15px;">
                                            @foreach($vendors as $vendor)
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" name="vendors[]" value="{{ $vendor->_id }}" class="custom-control-input vendor-checkbox" id="vendor_{{ $vendor->_id }}">
                                                    <label class="custom-control-label" for="vendor_{{ $vendor->_id }}">
                                                        {{ $vendor->title }} 
                                                        @if($vendor->slug)
                                                            <small class="text-muted">({{ $vendor->slug }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if($vendors->isEmpty())
                                                <div class="text-muted">{{ __tr('Aucun vendeur actif trouvé.') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary lw-btn-block-mobile">
                                            <i class="fa fa-paper-plane"></i> {{ __tr('Envoyer la Diffusion') }}
                                        </button>
                                    </div>

                                </div>
                                <div class="col-lg-4">
                                    <div class="alert alert-info">
                                        <h4 class="alert-heading"><i class="fa fa-info-circle"></i> {{ __tr('Comment ça marche ?') }}</h4>
                                        <p class="mb-0 text-sm">
                                            {{ __tr("Cette fonction vous permet d'envoyer un message ponctuel à une sélection de vos clients. Le message sera envoyé au numéro de téléphone de l'administrateur principal de chaque compte Vendeur sélectionné.") }}
                                        </p>
                                        <hr>
                                        <p class="mb-0 text-sm">
                                            {{ __tr("Attention: Assurez-vous d'utiliser un template approprié (Marketing ou Utility). Les messages sont envoyés immédiatement après validation.") }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </form>

                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('appScripts')
<script>
    (function() {
        'use strict';
        // Select All Checkbox logic
        $('#selectAllVendors').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.vendor-checkbox').prop('checked', isChecked);
        });

        // If any individual checkbox is unchecked, uncheck the Select All
        $('.vendor-checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                $('#selectAllVendors').prop('checked', false);
            }
        });
    })();
</script>
@endpush
