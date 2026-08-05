@extends('layouts.app', ['title' => __tr('Constructeur Drip : :title', ['title' => $campaign->title])])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Constructeur Drip : ') . $campaign->title,
    'description' => __tr('Gérez les étapes, les délais et le contenu de votre séquence automatique.'),
])

<div class="container-fluid pt-4 pb-5">
    <div class="row">
        <!-- Explication & Règle Meta -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; background: #ecfdf5; border-left: 5px solid #10b981 !important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 38px; height: 38px; background: #d1fae5; color: #047857; font-size: 1.1rem;">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <h4 class="mb-0 font-weight-bold" style="color: #065f46;">{{ __tr('Fonctionnement de la séquence Drip') }}</h4>
                    </div>
                    <p class="mb-2" style="color: #047857; font-size: 0.93rem;">
                        {{ __tr("Une campagne Drip envoie automatiquement une série de messages programmés aux contacts dès qu'ils déclenchent un Bot / Réponse automatique associée.") }}
                    </p>
                    <div class="p-3 bg-white rounded-lg border border-emerald" style="border-color: #a7f3d0 !important; border-radius: 10px;">
                        <span class="badge" style="background: #10b981; color: white; border-radius: 12px; padding: 4px 10px; font-size: 0.75rem; font-weight: 700;">
                            {{ __tr("Règle Meta 24h") }}
                        </span>
                        <span class="small font-weight-bold ml-2" style="color: #0f172a;">
                            {{ __tr("Les messages libres/personnalisés sont autorisés si le délai est inférieur à 24h. Pour les délais supérieurs à 24h, utilisez un Modèle Meta approuvé.") }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre d'outils -->
        <div class="col-12 mb-4 d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
            <div class="d-flex align-items-center" style="gap: 10px;">
                <a href="{{ route('addon.WhatsJetDripCampaignAddon.index') }}" class="btn btn-outline-secondary font-weight-bold" style="border-radius: 10px; padding: 8px 18px;">
                    <i class="fa fa-arrow-left mr-2"></i>{{ __tr('Retour aux Campagnes') }}
                </a>
                <form action="{{ route('addon.WhatsJetDripCampaignAddon.toggle_status', $campaign->_uid) }}" method="POST" class="d-inline">
                    @csrf
                    @if($campaign->status == 1)
                        <button type="submit" class="btn btn-outline-warning font-weight-bold" style="border-radius: 10px; padding: 8px 18px;" title="{{ __tr('Suspendre l\'envoi') }}">
                            <i class="fa fa-pause mr-2"></i>{{ __tr('Désactiver la campagne') }}
                        </button>
                    @else
                        <button type="submit" class="btn btn-success font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 10px; padding: 8px 18px;" title="{{ __tr('Activer l\'envoi') }}">
                            <i class="fa fa-play mr-2"></i>{{ __tr('Activer la campagne') }}
                        </button>
                    @endif
                </form>
            </div>
            <button type="button" class="btn text-white font-weight-bold shadow-sm" data-toggle="modal" data-target="#addStepModal" 
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; padding: 9px 22px;">
                <i class="fa fa-plus mr-2"></i>{{ __tr('Ajouter une étape') }}
            </button>
        </div>

        <!-- Feedback Messages -->
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px; background: #d1fae5; color: #065f46;">
                    <i class="fa fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px; background: #fee2e2; color: #991b1b;">
                    <i class="fa fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <!-- Timeline des étapes -->
            <div class="timeline mt-2">
                @forelse($steps as $index => $step)
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden; border-left: 5px solid #10b981 !important;">
                    <div class="card-body p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                            <div style="flex: 1; min-width: 250px;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge mr-3" style="background: #10b981; color: white; border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700;">
                                        {{ $index + 1 }}
                                    </span>
                                    <h4 class="mb-0 font-weight-bold" style="color: #065f46; font-size: 1.1rem;">
                                        <i class="far fa-clock mr-1" style="color: #10b981;"></i> 
                                        @if($step->delay_value == 0)
                                            {{ __tr('Envoi Immédiat') }}
                                        @else
                                            @php
                                                $delayTypes = [
                                                    'minutes' => __tr('minute(s)'),
                                                    'hours' => __tr('heure(s)'),
                                                    'days' => __tr('jour(s)'),
                                                ];
                                                $delayTypeTranslated = strtolower($delayTypes[$step->delay_type] ?? $step->delay_type);
                                            @endphp
                                            {{ __tr('Après') }} {{ $step->delay_value }} {{ $delayTypeTranslated }}
                                        @endif
                                    </h4>
                                </div>

                                <div class="pl-4">
                                    @if($step->botReply)
                                        <div class="mb-1">
                                            <span class="badge" style="background: #e0f2fe; color: #0284c7; padding: 5px 12px; border-radius: 20px; font-weight: 600;">
                                                <i class="fa fa-robot mr-1"></i> {{ __tr('Modèle Non-Meta :') }} {{ $step->botReply->name ?: ($step->botReply->reply_trigger ?: 'Message #' . $step->botReply->_id) }}
                                            </span>
                                        </div>
                                        @if($step->botReply->reply_text)
                                        <div class="mt-2 p-3 rounded-lg text-dark border" style="background: #f8fafc; border-color: #e2e8f0 !important; font-size: 0.92rem; color: #1e293b;">
                                            <em>{!! nl2br(e($step->botReply->reply_text)) !!}</em>
                                        </div>
                                        @endif
                                    @elseif($step->template)
                                        <div class="mb-1">
                                            <span class="badge" style="background: #d1fae5; color: #047857; padding: 5px 12px; border-radius: 20px; font-weight: 600;">
                                                <i class="fab fa-whatsapp mr-1"></i> {{ __tr('Modèle Meta :') }} {{ $step->template->template_name }}
                                            </span>
                                        </div>
                                        @php
                                            $templateData = $step->template->__data ?? [];
                                            $bodyText = '';
                                            if(!empty($templateData) && isset($templateData['components'])) {
                                                foreach($templateData['components'] as $component) {
                                                    if($component['type'] == 'BODY' && isset($component['text'])) {
                                                        $bodyText = $component['text'];
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if($bodyText)
                                        <div class="mt-2 p-3 rounded-lg text-dark border" style="background: #f8fafc; border-color: #e2e8f0 !important; font-size: 0.92rem; color: #1e293b;">
                                            <em>{!! nl2br(e($bodyText)) !!}</em>
                                        </div>
                                        @endif
                                    @elseif($step->custom_message)
                                        <div class="mb-1">
                                            <span class="badge" style="background: #fef3c7; color: #b45309; padding: 5px 12px; border-radius: 20px; font-weight: 600;">
                                                <i class="fa fa-comment mr-1"></i> {{ __tr('Message Personnalisé Libre') }}
                                            </span>
                                        </div>
                                        <div class="mt-2 p-3 rounded-lg text-dark border" style="background: #f8fafc; border-color: #e2e8f0 !important; font-size: 0.92rem; color: #1e293b;">
                                            <em>{!! nl2br(e($step->custom_message)) !!}</em>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center" style="gap: 8px;">
                                <button type="button" class="btn btn-sm btn-primary font-weight-bold" data-toggle="modal" data-target="#editStepModal{{ $step->_uid }}" style="background: #2563eb; border: none; border-radius: 8px; padding: 6px 14px;">
                                    <i class="fa fa-edit mr-1"></i> {{ __tr('Modifier') }}
                                </button>
                                <form action="{{ route('addon.WhatsJetDripCampaignAddon.delete_step', $step->_uid) }}" method="POST" onsubmit="return confirm('{{ __tr('Supprimer cette étape ?') }}');" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 8px; padding: 6px 12px;">
                                        <i class="fa fa-trash mr-1"></i> {{ __tr('Supprimer') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modale d'Édition d'Étape -->
                <div class="modal fade" id="editStepModal{{ $step->_uid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <form action="{{ route('addon.WhatsJetDripCampaignAddon.update_step', $step->_uid) }}" method="POST">
                            @csrf
                            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%); padding: 20px 26px;">
                                    <div>
                                        <h4 class="modal-title font-weight-bold text-white mb-0">
                                            <i class="fas fa-edit mr-2"></i>{{ __tr('Modifier l\'étape de la séquence') }}
                                        </h4>
                                    </div>
                                    <button type="button" class="close text-white opacity-80" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" style="font-size: 1.6rem;">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4" style="background: #ffffff; max-height: calc(82vh - 140px); overflow-y: auto;">

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ __tr('Délai d\'attente') }} <small class="text-danger">*</small></label>
                                                <input type="number" min="0" name="delay_value" class="form-control" required value="{{ $step->delay_value }}" style="border-radius: 8px; height: 44px;">
                                                <small class="text-muted">{{ __tr('0 = Envoi immédiat lors du déclenchement.') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ __tr('Unité de temps') }} <small class="text-danger">*</small></label>
                                                <select name="delay_type" class="form-control" required style="border-radius: 8px; height: 44px;">
                                                    <option value="minutes" {{ $step->delay_type == 'minutes' ? 'selected' : '' }}>{{ __tr('Minutes') }}</option>
                                                    <option value="hours" {{ $step->delay_type == 'hours' ? 'selected' : '' }}>{{ __tr('Heures') }}</option>
                                                    <option value="days" {{ $step->delay_type == 'days' ? 'selected' : '' }}>{{ __tr('Jours') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fas fa-robot text-primary mr-1"></i> {{ __tr('Modèle Non-Meta (Message Pré-enregistré / Bot)') }}</label>
                                        <select name="bot_replies__id" class="form-control" style="border-radius: 8px; height: 44px;">
                                            <option value="">{{ __tr('-- Aucun (Optionnel) --') }}</option>
                                            @if(isset($presetMessages))
                                                @foreach($presetMessages as $presetMsg)
                                                    <option value="{{ $presetMsg->_id }}" {{ $step->bot_replies__id == $presetMsg->_id ? 'selected' : '' }}>
                                                        {{ $presetMsg->name ?: ($presetMsg->reply_trigger ?: 'Message #' . $presetMsg->_id) }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="text-muted">{{ __tr('Recommandé : Envoyé directement sans validation Meta.') }}</small>
                                    </div>

                                    <div class="form-group text-center my-3">
                                        <span class="badge px-3 py-1 font-weight-bold text-muted" style="background: #f1f5f9; border-radius: 12px; font-size: 0.78rem;">{{ __tr('OU') }}</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fa fa-comment text-info mr-1"></i> {{ __tr('Message Personnalisé (Texte Libre)') }}</label>
                                        <textarea id="lwDripCustomMessage{{ $step->_uid }}" name="custom_message" class="form-control" rows="3" style="border-radius: 8px;">{{ $step->custom_message }}</textarea>
                                        <x-whatsapp-format-buttons inputId="lwDripCustomMessage{{ $step->_uid }}" />
                                    </div>

                                    <div class="form-group text-center my-3">
                                        <span class="badge px-3 py-1 font-weight-bold text-muted" style="background: #f1f5f9; border-radius: 12px; font-size: 0.78rem;">{{ __tr('OU') }}</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fab fa-whatsapp text-success mr-1"></i> {{ __tr('Modèle Meta WhatsApp') }}</label>
                                        <select name="whatsapp_templates__id" class="form-control" style="border-radius: 8px; height: 44px;">
                                            <option value="">{{ __tr('-- Aucun (Optionnel) --') }}</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template->_id }}" {{ $step->whatsapp_templates__id == $template->_id ? 'selected' : '' }}>{{ $template->template_name }} ({{ $template->language }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-3" style="background: #f8fafc;">
                                    <button type="button" class="btn btn-outline-secondary font-weight-bold" data-dismiss="modal" style="border-radius: 8px; padding: 8px 18px;">{{ __tr('Annuler') }}</button>
                                    <button type="submit" class="btn text-white font-weight-bold" style="background: #10b981; border: none; border-radius: 8px; padding: 8px 20px;" onclick="
                                        var msgField = document.getElementById('lwDripCustomMessage{{ $step->_uid }}');
                                        if (msgField && msgField.value) {
                                            var hidden = document.createElement('input');
                                            hidden.type = 'hidden';
                                            hidden.name = 'custom_message_b64';
                                            hidden.value = btoa(unescape(encodeURIComponent(msgField.value)));
                                            this.form.appendChild(hidden);
                                            msgField.disabled = true;
                                        }
                                    ">{{ __tr('Enregistrer les modifications') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <div class="card border-0 shadow-sm py-5 text-center" style="border-radius: 16px; background: #ffffff;">
                    <div style="font-size: 2.8rem; color: #a7f3d0; margin-bottom: 0.8rem;">
                        <i class="fas fa-list-ol"></i>
                    </div>
                    <h4 class="font-weight-bold text-dark">{{ __tr('Aucune étape configurée dans cette séquence') }}</h4>
                    <p class="text-muted small mb-3">{{ __tr('Cliquez sur le bouton "Ajouter une étape" ci-dessus pour planifier vos premiers messages.') }}</p>
                    <div>
                        <button type="button" class="btn text-white font-weight-bold shadow-sm" data-toggle="modal" data-target="#addStepModal" 
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; padding: 9px 24px;">
                            <i class="fa fa-plus mr-2"></i>{{ __tr('Ajouter une étape maintenant') }}
                        </button>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modale de Création d'Étape -->
<div class="modal fade" id="addStepModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form action="{{ route('addon.WhatsJetDripCampaignAddon.store_step', $campaign->_uid) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%); padding: 20px 26px;">
                    <div>
                        <h4 class="modal-title font-weight-bold text-white mb-0">
                            <i class="fas fa-plus-circle mr-2"></i>{{ __tr('Ajouter une nouvelle étape à la séquence') }}
                        </h4>
                    </div>
                    <button type="button" class="close text-white opacity-80" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 1.6rem;">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4" style="background: #ffffff; max-height: calc(82vh - 140px); overflow-y: auto;">

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ __tr('Délai d\'attente') }} <small class="text-danger">*</small></label>
                                <input type="number" min="0" name="delay_value" class="form-control" required value="1" style="border-radius: 8px; height: 44px;">
                                <small class="text-muted">{{ __tr('0 = Envoi immédiat dès l\'abonnement.') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ __tr('Unité de temps') }} <small class="text-danger">*</small></label>
                                <select name="delay_type" class="form-control" required style="border-radius: 8px; height: 44px;">
                                    <option value="minutes">{{ __tr('Minutes') }}</option>
                                    <option value="hours">{{ __tr('Heures') }}</option>
                                    <option value="days" selected>{{ __tr('Jours') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                         <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fas fa-robot text-primary mr-1"></i> {{ __tr('Modèle Non-Meta (Message Pré-enregistré / Bot)') }}</label>
                         <select name="bot_replies__id" class="form-control" style="border-radius: 8px; height: 44px;">
                             <option value="">{{ __tr('-- Aucun (Optionnel) --') }}</option>
                             @if(isset($presetMessages))
                                 @foreach($presetMessages as $presetMsg)
                                     <option value="{{ $presetMsg->_id }}">
                                         {{ $presetMsg->name ?: ($presetMsg->reply_trigger ?: 'Message #' . $presetMsg->_id) }}
                                     </option>
                                 @endforeach
                             @endif
                         </select>
                         <small class="text-muted">{{ __tr('Recommandé : Message pré-enregistré pour la fenêtre 24h.') }}</small>
                     </div>

                     <div class="form-group text-center my-3">
                         <span class="badge px-3 py-1 font-weight-bold text-muted" style="background: #f1f5f9; border-radius: 12px; font-size: 0.78rem;">{{ __tr('OU') }}</span>
                     </div>

                     <div class="form-group">
                         <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fa fa-comment text-info mr-1"></i> {{ __tr('Message Personnalisé (Texte Libre)') }}</label>
                         <textarea id="lwDripCustomMessage" name="custom_message" class="form-control" rows="3" placeholder="{{ __tr('Écrivez un message texte libre...') }}" style="border-radius: 8px;"></textarea>
                         <x-whatsapp-format-buttons inputId="lwDripCustomMessage" />
                     </div>

                     <div class="form-group text-center my-3">
                         <span class="badge px-3 py-1 font-weight-bold text-muted" style="background: #f1f5f9; border-radius: 12px; font-size: 0.78rem;">{{ __tr('OU') }}</span>
                     </div>

                     <div class="form-group">
                         <label class="font-weight-bold text-dark" style="font-size: 0.9rem;"><i class="fab fa-whatsapp text-success mr-1"></i> {{ __tr('Modèle Meta WhatsApp (Optionnel)') }}</label>
                         <select name="whatsapp_templates__id" class="form-control" style="border-radius: 8px; height: 44px;">
                             <option value="">{{ __tr('-- Aucun (Optionnel) --') }}</option>
                             @foreach($templates as $template)
                                 <option value="{{ $template->_id }}">{{ $template->template_name }} ({{ $template->language }})</option>
                             @endforeach
                         </select>
                     </div>
                </div>
                <div class="modal-footer border-0 p-3" style="background: #f8fafc;">
                    <button type="button" class="btn btn-outline-secondary font-weight-bold" data-dismiss="modal" style="border-radius: 8px; padding: 8px 18px;">{{ __tr('Annuler') }}</button>
                    <button type="submit" class="btn text-white font-weight-bold" style="background: #10b981; border: none; border-radius: 8px; padding: 8px 22px;" onclick="
                        var msgField = document.getElementById('lwDripCustomMessage');
                        if (msgField && msgField.value) {
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'custom_message_b64';
                            hidden.value = btoa(unescape(encodeURIComponent(msgField.value)));
                            this.form.appendChild(hidden);
                            msgField.disabled = true;
                        }
                    ">{{ __tr('Ajouter l\'étape') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

