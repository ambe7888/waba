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
                                            {{ __tr("Seuls les templates approuvés de votre compte expéditeur SaaS sont listés.") }}
                                        </small>
                                    </div>

                                    <!-- Aperçu du Template -->
                                    <div id="template-preview-container" class="mt-3 p-3 border rounded bg-white shadow-sm" style="display: none;">
                                        <div class="text-xs font-weight-bold text-uppercase text-secondary mb-2">{{ __tr('Aperçu du Template') }}</div>
                                        <div class="template-preview-text text-sm bg-light p-3 rounded mb-2 border" style="white-space: pre-wrap; line-height: 1.6; color: #374151;"></div>
                                    </div>

                                    <!-- Conteneur Variables Dynamiques -->
                                    <div id="dynamic-variables-container" class="mt-3"></div>
                                    
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
                                                        @if(!empty($vendor->admin_phone))
                                                            <small class="text-muted font-weight-bold ml-2">({{ $vendor->admin_phone }})</small>
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

        // Dynamic Variables Logic
        const templates = {!! json_encode($templates->toArray()) !!};
        let lastFocusedInput = null;

        // Track last focused variable input
        $(document).on('focus', '#dynamic-variables-container input', function() {
            lastFocusedInput = this;
        });

        // Handle tag click to insert or copy
        $(document).on('click', '.tag-helper', function(e) {
            e.preventDefault();
            const tag = $(this).data('tag');
            
            if (lastFocusedInput) {
                const startPos = lastFocusedInput.selectionStart;
                const endPos = lastFocusedInput.selectionEnd;
                const val = $(lastFocusedInput).val();
                $(lastFocusedInput).val(val.substring(0, startPos) + tag + val.substring(endPos));
                lastFocusedInput.selectionStart = lastFocusedInput.selectionEnd = startPos + tag.length;
                lastFocusedInput.focus();
            } else {
                navigator.clipboard.writeText(tag);
            }
            
            const $badge = $(this);
            const originalText = $badge.text();
            $badge.text('{{ __tr("Inséré / Copié") }}').removeClass('badge-light border').addClass('badge-success text-white');
            setTimeout(() => {
                $badge.text(originalText).removeClass('badge-success text-white').addClass('badge-light border');
            }, 1000);
        });

        $('#templateName').on('change', function() {
            let tplName = $(this).val();
            let tpl = templates.find(t => t.template_name === tplName);
            let html = '';
            let hasVars = false;
            
            if (!tplName || !tpl) {
                $('#template-preview-container').hide();
                $('#dynamic-variables-container').empty();
                return;
            }

            let bodyText = '';
            let headerText = '';
            let buttonsHtml = '';
            
            if (tpl && tpl.__data && tpl.__data.template && tpl.__data.template.components) {
                tpl.__data.template.components.forEach(comp => {
                    if (comp.type === 'BODY') {
                        bodyText = comp.text || '';
                    } else if (comp.type === 'HEADER' && comp.format === 'TEXT') {
                        headerText = comp.text || '';
                    } else if (comp.type === 'BUTTONS' && comp.buttons) {
                        comp.buttons.forEach((btn) => {
                            buttonsHtml += `<div class="mt-2"><button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="fa fa-external-link-alt text-xs mr-1"></i> ${btn.text}</button></div>`;
                        });
                    }

                    // Header Media Variables
                    if (comp.type === 'HEADER' && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(comp.format)) {
                        html += `
                            <div class="form-group border-left border-primary pl-3 mt-3">
                                <label class="font-weight-bold"><i class="fa fa-file-alt"></i> Lien URL (${comp.format}) - Entête</label>
                                <input type="url" name="variables[header_media][${comp.format.toLowerCase()}]" class="form-control" placeholder="https://..." required>
                                <small class="form-text text-muted">{{ __tr('Saisissez le lien public direct vers le média.') }}</small>
                            </div>
                        `;
                        hasVars = true;
                    }
                    
                    // Text Variables (Body, Header, Buttons)
                    let text = comp.text || '';
                    let matches = text.match(/\{\{(\d+)\}\}/g);
                    if (matches) {
                        matches = [...new Set(matches)];
                        matches.forEach(m => {
                            let num = m.replace(/[{}]/g, '');
                            html += `
                                <div class="form-group border-left border-primary pl-3 mt-3">
                                    <label class="font-weight-bold">Variable ${m} (${comp.type})</label>
                                    <input type="text" name="variables[${comp.type.toLowerCase()}][${num}]" class="form-control var-input-field" placeholder="Valeur pour ${m}" required>
                                </div>
                            `;
                        });
                        hasVars = true;
                    }
                });
            }
            
            // Build and show template preview
            let previewHtml = '';
            if (headerText) {
                let highlightedHeader = headerText.replace(/\{\{(\d+)\}\}/g, '<span class="badge badge-warning font-weight-bold text-dark">HEADER Variable {' + '{$1}' + '}</span>');
                previewHtml += `<div class="border-bottom pb-2 mb-2 font-weight-bold text-dark">${highlightedHeader}</div>`;
            }
            if (bodyText) {
                let highlightedBody = bodyText.replace(/\{\{(\d+)\}\}/g, '<span class="badge badge-warning font-weight-bold text-dark">Variable {' + '{$1}' + '}</span>');
                previewHtml += `<div class="text-dark">${highlightedBody}</div>`;
            }
            if (buttonsHtml) {
                previewHtml += `<div class="border-top pt-2 mt-2">${buttonsHtml}</div>`;
            }
            
            if (previewHtml) {
                $('#template-preview-container .template-preview-text').html(previewHtml);
                $('#template-preview-container').show();
            } else {
                $('#template-preview-container').hide();
            }
            
            if (hasVars) {
                // Prepend help tags
                const helperHtml = `
                    <div class="alert alert-secondary p-3 mb-3 border bg-light mt-3">
                        <div class="font-weight-bold text-sm mb-2 text-dark"><i class="fa fa-info-circle text-info"></i> {{ __tr('Placeholders Dynamiques:') }}</div>
                        <p class="text-xs mb-2">{{ __tr('Cliquez sur une variable ci-dessous pour l\'insérer dans le champ sélectionné ou la copier :') }}</p>
                        <div class="d-flex flex-wrap">
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{first_name}" style="cursor: pointer;">{first_name}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{last_name}" style="cursor: pointer;">{last_name}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{full_name}" style="cursor: pointer;">{full_name}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{email}" style="cursor: pointer;">{email}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{mobile_number}" style="cursor: pointer;">{mobile_number}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{app_name}" style="cursor: pointer;">{app_name}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{account_name}" style="cursor: pointer;">{account_name}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{expiry_date}" style="cursor: pointer;">{expiry_date}</span>
                            <span class="badge badge-light border text-dark p-2 mr-2 mb-2 cursor-pointer tag-helper" data-tag="{subscription_amount}" style="cursor: pointer;">{subscription_amount}</span>
                        </div>
                    </div>
                `;
                html = helperHtml + html;
            }
            
            $('#dynamic-variables-container').html(html);
            lastFocusedInput = null; // reset
        });
    })();
</script>
@endpush
