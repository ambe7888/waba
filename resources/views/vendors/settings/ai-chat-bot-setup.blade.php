@php
$vendorId = getVendorId();
$vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
$planCredits = $vendor->plan_ai_credits ?? 0;
$extraCredits = $vendor->extra_ai_credits ?? 0;
$totalCredits = $planCredits + $extraCredits;

$planCreditsDisplay = $planCredits >= 99999999 ? __tr('Illimité') : number_format($planCredits);
$totalCreditsDisplay = $planCredits >= 99999999 ? __tr('Illimité') : number_format($totalCredits);

// Check feature limit
$vendorPlanDetails = vendorPlanDetails('ai_chat_bot', 1, $vendorId);
$selectedOtherBotsForTimingRestrictions = getVendorSettings('enable_selected_other_bot_timing_restrictions') ?: [];
@endphp

<div class="container-fluid pb-5">
    <!-- Header Title -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('Paramètres & Intégrations des Bots IA') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Configurez vos assistants virtuels, ajustez la mémoire et gérez les règles d\'activation') }}</p>
        </div>
    </div>

    <!-- AI Credits Balance Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); border-radius: 16px;">
                <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <span class="badge px-3 py-1 font-weight-bold text-uppercase mb-2" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px; font-size: 0.75rem;">
                            <i class="fa fa-bolt mr-1"></i> {{ __tr('Solde de Crédits IA') }}
                        </span>
                        <h2 class="display-4 font-weight-bold text-white mb-1" style="font-size: 2.2rem;">
                            {{ $totalCreditsDisplay }} <span class="h5 text-white-50 font-weight-normal">{{ __tr('Crédits disponibles') }}</span>
                        </h2>
                        <div class="text-white-50 small">
                            <span class="mr-3"><i class="fa fa-calendar-alt mr-1"></i> {{ __tr('Crédits d\'abonnement:') }} <strong class="text-white">{{ $planCreditsDisplay }}</strong></span>
                            <span><i class="fa fa-shopping-bag mr-1"></i> {{ __tr('Crédits achetés:') }} <strong class="text-white">{{ number_format($extraCredits) }}</strong></span>
                        </div>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="{{ route('vendor.settings.read', ['pageType' => 'ai-credits-topup']) }}" class="btn btn-white text-emerald font-weight-bold px-4 py-2 shadow-sm" style="background: #ffffff; color: #047857; border-radius: 10px;">
                            <i class="fa fa-coins mr-2"></i> {{ __tr('Recharger mes Crédits') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notice Message -->
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center" style="border-radius: 12px; background: #ecfdf5; color: #065f46;">
        <i class="fa fa-info-circle fa-2x mr-3 text-emerald"></i>
        <div class="small">
            <strong>{{ __tr('Règle de déclenchement du Bot IA :') }}</strong>
            {{ __tr('Le chatbot IA est sollicité uniquement si le chatbot manuel n\'a pas trouvé de correspondance et que le contact a l\'option IA activée.') }}
        </div>
    </div>

    <!-- Main Container -->
    <div class="row">
        <div class="col-lg-12">
            
            <!-- 1. Bot Timing Restrictions -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;" x-data="{panelOpened:false, enable_bot_timing_restrictions:'{{ getVendorSettings('enable_bot_timing_restrictions') }}'}" x-cloak>
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between" @click="panelOpened = !panelOpened" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-light text-emerald p-2 mr-3 rounded-circle">
                            <i class="fa fa-clock"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">{{ __tr('Restrictions Horaires du Bot') }}</h5>
                            <small class="text-muted">{{ __tr('Définissez les heures d\'ouverture et de fermeture du bot') }}</small>
                        </div>
                    </div>
                    <i class="fa" :class="panelOpened ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>

                <div x-show="panelOpened" class="card-body p-4 border-top">
                    <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="bot_timing_settings_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                        <input type="hidden" name="pageType" value="bot_timing_settings">
                        <input type="hidden" name="form_type" value="bot_timing_settings_form" />
                        
                        <div class="form-group">
                            <x-lw.checkbox id="enableBotTimingRestrictions" :offValue="0" @click="(enable_bot_timing_restrictions = !enable_bot_timing_restrictions)" name="enable_bot_timing_restrictions" :checked="getVendorSettings('enable_bot_timing_restrictions')" data-lw-plugin="lwSwitchery" :label="__tr('Activer les restrictions d\'horaires')" />
                        </div>

                        <div x-show="enable_bot_timing_restrictions" class="mt-3">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <x-lw.input-field type="time" id="lwBotTimingStart" :label="__tr('Heure de début quotidienne')" value="{{ getVendorSettings('bot_start_timing') }}" name="bot_start_timing" />
                                </div>
                                <div class="col-md-4 mb-3">
                                    <x-lw.input-field type="time" id="lwBotTimingEnd" :label="__tr('Heure de fin quotidienne')" value="{{ getVendorSettings('bot_end_timing') }}" name="bot_end_timing" />
                                </div>
                                <div class="col-md-4 mb-3">
                                    <x-lw.input-field type="selectize" name="bot_timing_timezone" :label="__tr('Fuseau horaire')" data-selected="{{ (getVendorSettings('bot_timing_timezone') != 'UTC') ? getVendorSettings('bot_timing_timezone') : getVendorSettings('timezone') }}">
                                        <x-slot name="selectOptions">
                                            @foreach (getTimezonesArray() as $timezone)
                                                <option value="{{ $timezone['value'] }}">{{ $timezone['text'] }}</option>
                                            @endforeach
                                        </x-slot>
                                    </x-lw.input-field>
                                </div>
                            </div>

                            <div class="alert alert-warning border-0 small mt-3" style="border-radius: 10px;">
                                <i class="fa fa-exclamation-triangle mr-2"></i> {{ __tr('Les restrictions d\'horaires s\'appliqueront uniquement aux types de bots cochés ci-dessous.') }}
                            </div>

                            <h6 class="font-weight-bold text-dark mt-4 mb-3">{{ __tr('Activer la restriction horaire pour :') }}</h6>
                            <div class="row">
                                @if (isAiBotAvailable())
                                <div class="col-md-3 mb-3">
                                    <x-lw.checkbox data-size="small" id="lwAiBot" :checked="getVendorSettings('enable_ai_bot_timing_restrictions')" name="enable_ai_bot_timing_restrictions" data-lw-plugin="lwSwitchery" :label="__tr('Bots IA')" />
                                </div>
                                @endif
                                @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                                    <div class="col-md-3 mb-3">
                                        <x-lw.checkbox data-size="small" id="lw{{ $replyBotTypeKey }}Bot" :value="$replyBotTypeKey" :checked="array_key_exists($replyBotTypeKey, $selectedOtherBotsForTimingRestrictions)" name="enable_selected_other_bot_timing_restrictions[{{ $replyBotTypeKey }}]" data-lw-plugin="lwSwitchery" :label="$replyBotType['title']" />
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                <i class="fa fa-save mr-1"></i> {{ __tr('Enregistrer les horaires') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. AI Bot General Settings -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;" x-data="{panelOpened:false}" x-cloak>
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between" @click="panelOpened = !panelOpened" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-light text-emerald p-2 mr-3 rounded-circle">
                            <i class="fa fa-cogs"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">{{ __tr('Paramètres Généraux du Bot IA') }}</h5>
                            <small class="text-muted">{{ __tr('Message d\'erreur par défaut et activation pour les nouveaux contacts') }}</small>
                        </div>
                    </div>
                    <i class="fa" :class="panelOpened ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>

                <div x-show="panelOpened" class="card-body p-4 border-top">
                    <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="ai_bot_settings_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                        <input type="hidden" name="pageType" value="ai_bot_settings">
                        <input type="hidden" name="form_type" value="ai_bot_settings_form" />
                        
                        <div class="form-group mb-4">
                            <x-lw.input-field placeholder="{{ __tr('Message en cas d\'échec de l\'IA') }}" type="text" id="lwFlowiseFailedMessage"
                            :label="__tr('Message en cas d\'échec de l\'IA')" value="{{ getVendorSettings('flowise_failed_message') }}" name="flowise_failed_message" :helpText="__tr('Si pour une raison quelconque le bot IA ne peut pas répondre, ce message sera envoyé au contact. Laissez vide pour ne rien envoyer.')" />
                        </div>

                        <div class="form-group">
                            <x-lw.checkbox id="enableFlowiseAiBotByDefaultForAllUsers" name="default_enable_flowise_ai_bot_for_users" :checked="getVendorSettings('default_enable_flowise_ai_bot_for_users')" data-lw-plugin="lwSwitchery" :label="__tr('Activer le Chatbot IA par défaut pour tous les nouveaux contacts')" />
                            <small class="text-muted d-block mt-1">{{ __tr('Active le chatbot IA automatiquement pour les contacts créés via les messages entrants, imports, etc.') }}</small>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                <i class="fa fa-save mr-1"></i> {{ __tr('Enregistrer') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Primary Chatbot (Gemini / OpenAI) -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;" x-data="{panelOpened:true}" x-cloak>
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between" @click="panelOpened = !panelOpened" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-light text-emerald p-2 mr-3 rounded-circle">
                            <i class="fa fa-robot"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">{{ __tr('Configuration du Chatbot IA (Google Gemini & OpenAI)') }}</h5>
                            <small class="text-muted">{{ __tr('Entraînez votre assistant avec les données de votre entreprise') }}</small>
                        </div>
                    </div>
                    <i class="fa" :class="panelOpened ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>

                <div x-show="panelOpened" class="card-body p-4 border-top">
                    <p class="text-muted small mb-4">
                        {{ __tr('Grâce au système d\'IA, vous pouvez entraîner votre chatbot à répondre aux questions de vos clients en fonction des informations fournies ci-dessous (catalogue, tarifs, services, FAQ).') }}
                    </p>

                    @if ($vendorPlanDetails['is_limit_available'])
                        <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="ai_bot_setup_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                            <input type="hidden" name="pageType" value="open_ai_bot_setup">
                            <input type="hidden" name="form_type" value="ai_bot_setup_page" />
                            
                            <div class="form-group mb-3">
                                <x-lw.checkbox id="enableOpenAiBot" name="enable_open_ai_bot" :checked="getVendorSettings('enable_open_ai_bot')" data-lw-plugin="lwSwitchery" :label="__tr('Activer le Chatbot IA (Gemini / OpenAI)')" />
                            </div>

                            <div class="form-group mb-4">
                                <x-lw.checkbox id="useExistingChatHistory" name="use_existing_chat_history" :checked="getVendorSettings('use_existing_chat_history')" data-lw-plugin="lwSwitchery" :label="__tr('Conserver et utiliser l\'historique de discussion comme contexte')" />
                                <small class="text-muted d-block mt-1">{{ __tr('L\'IA se souviendra des derniers messages échangés avec le client pour des réponses plus intelligentes.') }}</small>
                            </div>

                            <div class="form-group mb-4">
                                <x-lw.input-field placeholder="{{ __tr('Ex: Assistant WhatsApp') }}"
                                type="text" id="lwOpenAIBotName" data-form-group-class="col-md-8"
                                :label="__tr('Nom de votre Assistant IA')" name="open_ai_bot_name" value="{{ getVendorSettings('open_ai_bot_name') }}" />
                            </div>

                            <input type="hidden" name="open_ai_bot_data_source_type" value="text" />
                            <input type="hidden" name="open_ai_max_token" value="1000" />

                            <div class="form-group mb-4">
                                <label for="lwTrainingTextData" class="font-weight-bold text-dark">{{ __tr('Données d\'entraînement & Informations de l\'entreprise') }}</label>
                                <textarea rows="10" id="lwTrainingTextData" class="form-control p-3" style="border-radius: 12px; font-size: 0.95rem;" placeholder="{{ __tr('Saisissez ici toutes les informations que le bot doit connaître pour répondre à vos clients :\n\n- Nom de l\'entreprise et présentation\n- Tarifs et formules d\'abonnement\n- Horaires et adresse\n- Conditions de retour, livraison, FAQ...') }}" name="open_ai_input_training_data">{!! getVendorSettings('open_ai_input_training_data') !!}</textarea>
                                <small class="text-muted d-block mt-2">{{ __tr('L\'intelligence artificielle utilisera ce texte structuré pour formuler ses réponses WhatsApp.') }}</small>
                            </div>

                            <div class="pt-3 border-top">
                                <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                    <i class="fa fa-save mr-1"></i> {{ __tr('Enregistrer l\'entraînement IA') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fa fa-lock mr-2"></i>{{ __tr('Cette fonctionnalité n\'est pas disponible dans votre plan actuel. Veuillez mettre à niveau votre abonnement.') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- 4. FlowiseAI Setup -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;" x-data="{panelOpened:false}" x-cloak>
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between" @click="panelOpened = !panelOpened" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <img class="mr-3 rounded" width="32" height="32" src="{{ asset('imgs/flowise-ai-logo.png') }}" alt="{{ __tr('FlowiseAI') }}">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">{{ __tr('Configuration FlowiseAI') }}</h5>
                            <small class="text-muted">{{ __tr('Connecter un workflow Flowise externe') }}</small>
                        </div>
                    </div>
                    <i class="fa" :class="panelOpened ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>

                <div x-show="panelOpened" class="card-body p-4 border-top">
                    <p class="text-muted small mb-3">
                        {{ __tr('FlowiseAI est une plateforme visuelle permettant de concevoir des chatbots complexes. Si vous utilisez FlowiseAI, récupérez l\'URL de votre endpoint et collez-la ci-dessous.') }}
                    </p>

                    @if ($vendorPlanDetails['is_limit_available'])
                        <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form"
                            name="ai_bot_setup_page" method="post"
                            action="<?= route('vendor.settings.write.update') ?>" x-data="{lwFlowiseUrlExists:{{ getVendorSettings('flowise_url') ? 1 : 0 }}}">
                            <input type="hidden" name="pageType" value="flowise_ai_bot_setup">
                            <input type="hidden" name="form_type" value="ai_bot_setup_page" />

                            <div class="form-group mb-3">
                                <x-lw.checkbox id="enableFlowiseAiBot" name="enable_flowise_ai_bot" :checked="getVendorSettings('enable_flowise_ai_bot')" data-lw-plugin="lwSwitchery" :label="__tr('Activer le Chatbot FlowiseAI')" />
                            </div>

                            <div class="form-group" x-cloak x-show="lwFlowiseUrlExists">
                                <div class="btn-group">
                                    <button type="button" disabled="true" class="btn btn-success font-weight-bold">
                                        <i class="fa fa-check mr-1"></i> {{ __tr('Paramètres FlowiseAI configurés') }}
                                    </button>
                                    <button type="button" @click="lwFlowiseUrlExists = !lwFlowiseUrlExists" class="btn btn-light font-weight-bold">{{ __tr('Modifier') }}</button>
                                </div>
                            </div>
                            
                            <div x-show="!lwFlowiseUrlExists">
                                <div class="form-group mb-3">
                                    <x-lw.input-field placeholder="{{ __tr('URL de votre bot Flowise') }}"
                                        type="text" id="lwFlowiseAiUrl" data-form-group-class="col-md-8"
                                        :label="__tr('URL de votre bot Flowise')" name="flowise_url" :helpText="__tr('Vous trouverez cette URL dans l\'onglet cURL de votre chat FlowiseAI.')" />
                                </div>
                                <div class="form-group mb-3">
                                    <x-lw.input-field placeholder="{{ __tr('Jeton Bearer d\'autorisation (facultatif)') }}"
                                        type="text" id="lwFlowiseAiUrl" data-form-group-class="col-md-8"
                                        :label="__tr('Jeton Bearer d\'autorisation (facultatif)')" name="flowise_access_token" :helpText="__tr('Si vous avez sécurisé l\'API avec un jeton Bearer, renseignez-le ici.')" />
                                </div>
                            </div>

                            <div class="pt-3 border-top">
                                <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                    <i class="fa fa-save mr-1"></i> {{ __tr('Enregistrer FlowiseAI') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fa fa-lock mr-2"></i>{{ __tr('Cette fonctionnalité n\'est pas disponible dans votre plan actuel.') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- 5. Meta Business Agent (Meta AI) -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;" x-data="{panelOpened:false}" x-cloak>
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between" @click="panelOpened = !panelOpened" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-light text-primary p-2 mr-3 rounded-circle">
                            <i class="fab fa-meta"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">{{ __tr('Meta Business Agent (Meta AI)') }}</h5>
                            <small class="text-muted">{{ __tr('Assistant IA natif hébergé directement sur WhatsApp par Meta') }}</small>
                        </div>
                    </div>
                    <i class="fa" :class="panelOpened ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>

                <div x-show="panelOpened" class="card-body p-4 border-top">
                    <p class="text-muted small mb-3">
                        {{ __tr('Meta Business Agent est un chatbot IA natif hébergé directement chez Meta sur WhatsApp Cloud API. Lorsqu\'il est activé, l\'assistant Llama de Meta répond directement à vos clients sur WhatsApp, sans consommer les crédits IA locaux.') }}
                    </p>

                    @if ($vendorPlanDetails['is_limit_available'])
                        <form id="lwMetaAiBotForm" class="lw-ajax-form lw-form" name="meta_ai_bot_setup_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                            <input type="hidden" name="pageType" value="meta_ai_bot_setup">
                            <input type="hidden" name="form_type" value="meta_ai_bot_setup_page" />
                            
                            <div class="form-group mb-3">
                                <x-lw.checkbox id="enableMetaAiBot" name="enable_meta_ai_bot" :checked="getVendorSettings('enable_meta_ai_bot')" data-lw-plugin="lwSwitchery" :label="__tr('Activer Meta Business Agent (Meta AI)')" />
                            </div>
                            
                            <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius: 10px;">
                                <strong><i class="fa fa-exclamation-triangle mr-1"></i> {{ __tr('Attention :') }}</strong>
                                {{ __tr('Activer Meta AI désactive les chatbots IA locaux (Gemini/OpenAI) sur cette plateforme. L\'IA native de Meta répondra directement sur WhatsApp. Assurez-vous d\'avoir configuré votre agent sur WhatsApp Manager / Meta Business Suite.') }}
                            </div>
                            
                            <div class="pt-3 border-top">
                                <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2" style="border-radius: 8px;">
                                    <i class="fa fa-save mr-1"></i> {{ __tr('Enregistrer Meta AI') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fa fa-lock mr-2"></i>{{ __tr('Cette fonctionnalité n\'est pas disponible dans votre plan actuel.') }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>