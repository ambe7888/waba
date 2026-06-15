@php
    $isForIndividualContact = !__isEmpty($contact);
    $isNonTemplateCampaign = $campaignType == 'non-template';
    $thisPageTitle = $isForIndividualContact ? __tr('Send WhatsApp Template Message') : ($isNonTemplateCampaign ? __tr('New Non-Template Campaign') : __tr('Create New Campaign'));
    $pageType = (!$isForIndividualContact and !$isNonTemplateCampaign) ? 'create-new-campaign' : 'send-template-message';
@endphp
@extends('layouts.app', ['title' => $thisPageTitle])
@section('content')
@include('users.partials.header', [
'title' => $thisPageTitle,
'description' => '',
// 'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6" x-data="{
    fromPhoneNumberId: '{{ getVendorSettings('current_phone_number_id') }}',
    phoneNumber: '{{ $contact->wa_id ?? '' }}',
    selectedTemplate: '',
    selectedNonTemplatePresetMessage: '',
    campaignTitle: '',
    contactGroup: '',
    labelTags: [],
    contactGroupIds: [],
    labelTagIds: [],
    restrictByTemplatedContactLanguage: '',
    campaignTimeZone: '{{ getVendorSettings('timezone') }}',
    scheduleAt: '',
    expireAt: '',
    contactCount: 0,
    restrictByLanguageChange: function() {

        const el = this.$refs.lwRestrictLanguageSwitch;

        if (el.checked) {
            this.restrictByTemplatedContactLanguage = 'on';
        } else {
            this.restrictByTemplatedContactLanguage = '';
        }
    },
    getTargetedContactCount: function() {
        if ('{{ $isNonTemplateCampaign }}') {
            return false;
        }

        var self = this;
        __DataRequest.post('{{ route('vendor.campaign.read.targeted_contact_count') }}', {
                'group_contact_ids': this.contactGroupIds,
                'label_ids': this.labelTagIds,
                'restrict_by_language': this.restrictByTemplatedContactLanguage,
                'template_id': this.selectedTemplate
            }, function(responseData) {
                self.contactCount = responseData.data.totalContacts;
            }
        );
    },
    generateCampaignTitle: function() {
        let name = '';
        if (this.selectedTemplate) {
            let selectEl = document.getElementById('lwField_templateSelection');
            if (selectEl && selectEl.selectedIndex >= 0) {
                name = selectEl.options[selectEl.selectedIndex].text.trim().split('(')[0].trim();
            }
        } else if (this.selectedNonTemplatePresetMessage) {
            let selectEl = document.getElementById('lwField_noTemplatePresetMessageSelection');
            if (selectEl && selectEl.selectedIndex >= 0) {
                name = selectEl.options[selectEl.selectedIndex].text.trim();
            }
        }
        if (!name || name === 'Sélectionnez' || name === 'Select') {
            name = 'Campagne';
        }
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }).replace(':', 'h');
        this.campaignTitle = name + ' - ' + dateStr + ' ' + timeStr;
    },
    autoGenerateTitleEffect: function() {
        if (this.selectedTemplate || this.selectedNonTemplatePresetMessage) {
            if (!this.campaignTitle || this.campaignTitle === '' || this.campaignTitle.startsWith('Campagne -') || this.campaignTitle === 'Campagne') {
                this.generateCampaignTitle();
            }
        }
    }
}" id="lwCreateNewCampaignContainer">
          <div class="row">
              <!-- button -->
    <div class="col-xl-12 mb-3 text-right">
        @if ($contact)
        <a class="lw-btn btn btn-secondary" href="{{ route('vendor.contact.read.list_view') }}">{{ __tr('Back to Contacts') }}</a>
        @endif
        @if ($isNonTemplateCampaign)
         <a class="lw-btn btn btn-secondary" href="{{ route('vendor.campaign.read.non_template_list_view') }}">{{ __tr('Manage Preset Messages') }}</a>
        @else
        <a class="lw-btn btn btn-light lw-ajax-link-action" data-confirm="{{ __tr('On template sync page will be refreshed') }}" data-callback="__Utils.viewReload" data-method="post" href="{{ route('vendor.whatsapp_service.templates.write.sync') }}" > {{ __tr('Sync WhatsApp Templates') }}</a>
        @endif
        <a class="lw-btn btn btn-dark" href="{{ route('vendor.campaign.read.list_view') }}">{{ __tr('Manage Campaigns') }}</a>
    </div>
    <!--/ button -->
    <div class="col-12">
        @if(session('loggedBySuperAdmin') and !getAppSettings('enable_queue_jobs_for_campaigns') and (!getAppSettings('cron_setup_done_at')))
        <div class="alert alert-danger text-left">
            <strong>{{  __tr('App Alert!!') }}</strong> {{  __tr('Cron Job or Queue Worker setup is required to execute campaigns. Please see Setup and Integration page in SuperAdmin area.') }}
        </div>
        @endif
        <div class="card lw-scrollable-main-card">
            @if ($contact)
            <div class="card-header">
                <div>{{  __tr('Name') }} : {{ $contact->full_name }}</div>
                <div>{{  __tr('Phone') }} : {{ $contact->wa_id }}</div>
                <div>{{  __tr('Country') }} : {{ $contact->country?->name }}</div>
            </div>
            @else
                @if(!getVendorSettings('test_recipient_contact') and !$isNonTemplateCampaign)
                <div class="card-body">
                    <div class="alert alert-danger">
                        {{  __tr('Test Contact missing, You need to set the Test Contact first, do it under the WhatsApp Settings') }}
                    </div>
                </div>
                @endif
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="{{ $isNonTemplateCampaign ? 'col-lg-7 col-md-8 col-sm-12' : 'col-sm-12 col-md-8 col-lg-6' }}">
                    @if (!$contact)
                    <h2 class="text-warning">{{  __tr('Step 1') }}</h2>
                    @endif
                    @if ($campaignType != 'non-template')
                    <x-lw.form lwSubmitOnChange data-event-callback="lwPrepareUploadPlugIn"
                        :action="route('vendor.request.template.view', ['page_type' => $pageType])" data-pre-callback="clearTemplateContainer" data-callback="onTemplateChangeProcess">
                        <div x-cloak x-show="!selectedTemplate">
                            <x-lw.input-field x-model="selectedTemplate"
                                placeholder="{!! __tr('Select & Configure Template') !!}" type="selectize"
                                data-lw-plugin="lwSelectize" data-selected=" " type="select"
                                id="lwField_templateSelection" name="template_selection" data-form-group-class=""
                                class="custom-select" data-selected=" " :label="__tr('Select Template')">
                                <x-slot name="selectOptions">
                                    <option value="">{{ __tr('Select & Configure Template') }}</option>
                                    @foreach ($whatsAppTemplates as $whatsAppTemplate)
                                    <option value="{{ $whatsAppTemplate->_uid }}">{{ $whatsAppTemplate->template_name }}
                                        ({{ $whatsAppTemplate->language }}) - ({{ $whatsAppTemplate->category }})</option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>
                        </div>
                        </x-lw.form>
                        @endif
                        @if ($campaignType == 'non-template')
                        <div>
                            @if (__isEmpty($nonTemplatePresetMessages))
                                <div class="card border-0 shadow-sm p-4 mb-4" style="background: #fff8f5; border-left: 5px solid #ff9800 !important; border-radius: 8px !important;">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3">
                                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.8rem;"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-warning font-weight-bold mb-2" style="font-size: 1.05rem;">
                                                {{ __tr('Aucun message prédéfini disponible') }}
                                            </h4>
                                            <p class="text-dark-50 mb-3 text-sm" style="line-height: 1.6;">
                                                {{ __tr("Pour envoyer une campagne sans modèle, vous devez d'abord enregistrer un message prédéfini. Ce message servira de modèle de texte gratuit pour votre diffusion.") }}
                                            </p>
                                            <a href="{{ route('vendor.campaign.read.non_template_list_view') }}" class="btn btn-warning text-white font-weight-bold btn-sm shadow-sm" style="border-radius: 6px !important;">
                                                <i class="fas fa-plus mr-1"></i> {{ __tr('Créer un message prédéfini') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <x-lw.input-field x-model="selectedNonTemplatePresetMessage"
                                    placeholder="{!! __tr('Select') !!}" type="selectize"
                                    data-lw-plugin="lwSelectize" data-selected=" " type="select"
                                    id="lwField_noTemplatePresetMessageSelection" name="non_template_preset_message" data-form-group-class=""
                                    class="custom-select" data-selected=" " :label="__tr('Sélectionnez un message prédéfini pour une campagne sans modèle')">
                                    <x-slot name="selectOptions">
                                        <option value="">{{ __tr('Select') }}</option>
                                        @foreach ($nonTemplatePresetMessages as $nonTemplatePresetMessage)
                                            <option value="{{ $nonTemplatePresetMessage->_uid }}">{{ $nonTemplatePresetMessage->name }}</option>
                                        @endforeach
                                    </x-slot>
                                </x-lw.input-field>
                            @endif
                        </div>
                        @endif

                        <div x-cloak class="mt-4">
                            @if ($contact)
                            <x-lw.form x-show="selectedTemplate" :action="route('vendor.template_message.contact.process', [
                                'contactUid' => $contact->_uid
                            ])">
                                <input type="hidden" name="contact_uid" value="{{ $contact->_uid }}">
                                <div id="lwTemplateStructureContainer">
                                    {!! $template !!}
                                </div>
                                {{-- /Carousel Template View --}}
                                @include('whatsapp.from-phone-number')
                                <button type="submit" class="btn btn-primary mt-4">{{ __tr('Send') }}</button>
                            </x-lw.form>
                            @else
                            {{-- Campaign Creation --}}
                            <x-lw.form x-show="selectedTemplate || selectedNonTemplatePresetMessage" :action="route('vendor.campaign.schedule.process')" data-confirm="#lwScheduleMessageConfirmation">
                                <div x-show="selectedTemplate" id="lwTemplateStructureContainer">
                                    {!! $template !!}
                                </div>
                                <input type="hidden" name="selected_preset_message_uid" :value="selectedNonTemplatePresetMessage">
                                <h2 class="mt-5 text-warning">{{  __tr('Step 2') }}</h2>
                               <fieldset class="w-100">
                                <legend>{{  __tr('Contacts and Schedule') }}</legend>
                                <x-lw.input-field type="text" id="lwCampaignTitle" data-form-group-class="" :label="__tr('Campaign Title')" name="title" x-model="campaignTitle" required="required">
                                    <x-slot name="append">
                                        <button class="btn btn-outline-success font-weight-bold" type="button" @click="generateCampaignTitle()" title="{{ __tr('Générer un nom de campagne automatique') }}">
                                            <i class="fas fa-magic mr-1"></i> {{ __tr('Générer') }}
                                        </button>
                                    </x-slot>
                                </x-lw.input-field>
                                 <fieldset class="shadow-none">
                                    <legend>{{  __tr('Target Contacts') }}</legend>
                                    {{-- select group --}}
                                 <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectGroupsField"
                                 data-form-group-class="" data-selected=" " :label="__tr('Groups/Contact')" name="contact_group[]" multiple>
                                 <x-slot name="selectOptions">
                                     <option value="">{{ __tr('Select Contacts Group') }}</option>
                                     <option value="all_contacts">{{ __tr('All Contacts') }}</option>
                                     @foreach($vendorContactGroups as $vendorContactGroup)
                                     <option value="{{ $vendorContactGroup['_id'] }}">{{ $vendorContactGroup['title'] }}</option>
                                     @endforeach
                                 </x-slot>
                             </x-lw.input-field>
                             <div x-effect="autoGenerateTitleEffect(); getTargetedContactCount()"></div>
                                     {{-- /select group --}}
                                     @if (isset($allLabels) && count($allLabels) > 0)
                                     <x-lw.input-field :label="__tr('Labels/Tags')" type="selectize" data-lw-plugin="lwSelectize" id="lwAssignLabelsField" data-form-group-class="" name="contact_labels[]" multiple >
                                        <x-slot name="selectOptions">
                                        <option value="">{{ __tr('Select Labels') }}</option>
                                            @foreach($allLabels as $label)
                                                <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                            @endforeach
                                        </x-slot>
                                    </x-lw.input-field>
                                     @endif
                                      <!-- Restrict by Template Language field -->
                                    <div class="form-group pt-3">
                                        <label for="lwOnlyForTemplateLanguageMatchingContact">
                                            <input type="checkbox" id="lwOnlyForTemplateLanguageMatchingContact" data-lw-plugin="lwSwitchery" data-color="#ff0000" 
                                            @click="restrictByLanguageChange()" x-ref="lwRestrictLanguageSwitch" name="restrict_by_templated_contact_language">
                                           {!! __tr('Restrict by Language Code - Send only to the contacts whose language code matches with template language code.') !!}
                                        </label>
                                    </div>
                                    @if(!$isNonTemplateCampaign)
                                    <strong class="m-2">
                                        {{ __tr('Total Targeted Contacts: ') }} <span x-text="contactCount"></span>
                                    </strong>
                                    @endif
                                 </fieldset>
                                    <fieldset x-data="{scheduleNow:true}">
                                        <legend>{{  __tr('Schedule') }}</legend>
                                        <div class="form-group pt-3">
                                            <label for="lwNowCampaign">
                                                <input x-model="scheduleNow" type="checkbox" id="lwNowCampaign"  data-secondary-color="orange" data-lw-plugin="lwSwitchery" checked  value="" name="schedule_now">
                                              <span x-show="scheduleNow">{{ __tr('Now') }}</span>
                                              <span x-show="!scheduleNow">{{ __tr('Later') }}</span>
                                            </label>
                                        </div>
                                        <div x-show="!scheduleNow">
                                            <x-lw.input-field  type="selectize" data-form-group-class="" name="timezone" :label="__tr('Select your Timezone')" data-selected="{{ getVendorSettings('timezone') }}" id="lwCampaignTimezone">
                                                <x-slot name="selectOptions">
                                                    @foreach (getTimezonesArray() as $timezone)
                                                        <option value="{{ $timezone['value'] }}">{{ $timezone['text'] }}</option>
                                                    @endforeach
                                                </x-slot>
                                            </x-lw.input-field>
                                        </div>
                                        <template x-if="!scheduleNow">
                                            <div x-show="!scheduleNow">
                                                <x-lw.input-field  type="datetime-local" id="lwScheduleAt" data-form-group-class="" min="{{ formatDateTime(now(), 'Y-m-d\TH:i:s') }}" :label="__tr('Schedule At')" name="schedule_at" @change="scheduleAt = $event.target.value" required />
                                            </div>
                                        </template>
                                    </fieldset>
                                    <fieldset x-data="{expiryOn:false}">
                                        <legend>{{  __tr('Expiry') }}</legend>
                                        <div class="alert alert-danger">
                                            {{  __tr("Messages will be set as expired if delayed in sending and won't be sent for the further processing.") }}
                                        </div>
                                        <div class="form-group pt-3">
                                            <label for="lwExpiryCampaign">
                                                <input x-model="expiryOn" type="checkbox" id="lwExpiryCampaign"  data-secondary-color="#D3D3D3" data-lw-plugin="lwSwitchery" value="" name="expire_on">
                                              <span x-show="expiryOn">{{ __tr('Set Expiry for Messages') }}</span>
                                              <span x-show="!expiryOn">{{ __tr('No Expiry for Processing') }}</span>
                                            </label>
                                        </div>
                                        <template x-if="expiryOn">
                                            <div x-show="expiryOn" x-data="{ minDate: '{{ formatDateTime(now(), 'Y-m-d\TH:i:s') }}' }">
                                                <x-lw.input-field  type="datetime-local" id="lwExpireAt" data-form-group-class="" min="{{ formatDateTime(now(), 'Y-m-d\TH:i:s') }}" :label="__tr('Expire At')" name="expire_at" @change="expireAt = $event.target.value" required />
                                            </div>
                                        </template>
                                    </fieldset>
                               </fieldset>
                               @include('whatsapp.from-phone-number')
                               <div class="my-4">
                                <button type="submit" class="btn btn-primary">{{ __tr('Schedule Campaign') }}</button>
                               </div>
                            </x-lw.form>
                            <template type="text/template" id="lwScheduleMessageConfirmation">
                                <h3>{{  __tr('Are you sure?') }}</h3>
                                @if ($isNonTemplateCampaign)
                                <p>{{  __tr('You want to schedule a WhatsApp NON Template Message. It will get scheduled for the selected group contacts and messages will be delivered to contacts whose 24 hours service window is open at that time actual message is sent.') }}</p>
                                @else
                                <p>{{  __tr('You want to schedule a WhatsApp Template Message. Test message will be sent to your selected test contact immediately and on success it will get scheduled for the selected group contacts') }}</p>
                                @endif
                            </template>
                            @endif
                        </div>
                    </div>

                    @if ($isNonTemplateCampaign)
                     <!-- Help Sidebar on the right -->
                     <div class="col-lg-5 col-md-4 col-sm-12 mb-4">
                         <div class="alert alert-warning text-left shadow-sm mb-3" style="border-radius: 8px !important;">
                             <strong>{{  __tr('Please note:') }}</strong> {{  __tr('Non template Messages only delivered to contacts whose 24 hours service window is open at the time actual message is sent.') }}
                         </div>
                        {{-- Visual Guidance Checklist --}}
                        <div class="card border-0 shadow-none p-4" style="background: #f8f9fa; border-radius: 12px !important; border: 1px solid #e9ecef !important;">
                            <h4 class="font-weight-bold text-dark mb-3" style="font-size: 0.95rem;">
                                <i class="fas fa-route text-success mr-2"></i>{{ __tr('Comment fonctionne une campagne gratuite sans modèle ?') }}
                            </h4>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="badge badge-success rounded-circle p-2 text-white mr-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">1</div>
                                        <div>
                                            <h5 class="font-weight-bold mb-1 text-sm text-dark">{{ __tr('Créez vos messages prédéfinis') }}</h5>
                                            <p class="text-muted text-xs mb-0">{{ __tr('Enregistrez les textes promotionnels ou de relance que vous souhaitez diffuser gratuitement.') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="badge badge-success rounded-circle p-2 text-white mr-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">2</div>
                                        <div>
                                            <h5 class="font-weight-bold mb-1 text-sm text-dark">{{ __tr('Sélectionnez le message') }}</h5>
                                            <p class="text-muted text-xs mb-0">{{ __tr('Choisissez le message dans la liste ci-dessus pour passer à l\'étape de ciblage.') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="badge badge-success rounded-circle p-2 text-white mr-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">3</div>
                                        <div>
                                            <h5 class="font-weight-bold mb-1 text-sm text-dark">{{ __tr('Ciblez vos contacts actifs (24h)') }}</h5>
                                            <p class="text-muted text-xs mb-0">{{ __tr('Sélectionnez vos groupes. Seuls les contacts ayant interagi avec vous ces dernières 24 heures recevront ce message gratuitement (sans frais Meta).') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
          </div>
</div>
@endsection()
@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>
<script>
    (function($){
            'use strict';

            window.clearTemplateContainer = function(inputData) {
                $('#lwTemplateStructureContainer').text('');
                return inputData;
            };
            window.onTemplateChangeProcess = function(responseData) {
                if (responseData.reaction == 1) {
                    _.defer(function() {
                        window.lwPluginsInit();
                        const lwCreateNewCampaignContainer = document.getElementById('lwCreateNewCampaignContainer');
                        var campaignData = Alpine.$data(lwCreateNewCampaignContainer);

                        // Track change event on contact group selectize
                        if (!_.isUndefined($('#lwSelectGroupsField')[0])) {
                            const groupSelectize = $('#lwSelectGroupsField')[0].selectize;
                            groupSelectize.on('change', function(value) {

                                if (value.includes('all_contacts')) {
                                    // Force single selection
                                    this.clear(true);
                                    this.addItem('all_contacts', true);
                                    this.settings.maxItems = 1;
                                    campaignData.contactGroup = 'all_contacts';
                                    campaignData.contactGroupIds.push('all_contacts');
                                } else {
                                    // Normal multi select
                                    this.settings.maxItems = null;
                                    const selectedTexts = this.items
                                        .map(v => this.options[v]?.text)
                                        .filter(Boolean)
                                        .join(',');
                                    campaignData.contactGroup = selectedTexts;
                                    campaignData.contactGroupIds = value;
                                }
                            });
                        }
                        
                        // Track change event on labels
                        if (!_.isUndefined($('#lwAssignLabelsField')[0])) {
                            const lwAssignLabelsField = $('#lwAssignLabelsField')[0].selectize;
                            lwAssignLabelsField.on('change', function(values) {
                                var labels = values
                                        .map(v => lwAssignLabelsField.options[v]?.text)
                                        .filter(Boolean);
                                campaignData.labelTags = labels;
                                campaignData.labelTagIds = values;
                            });
                        }
                        
                        // Track change event on timezone selectize
                        if (!_.isUndefined($('#lwCampaignTimezone')[0])) {
                            const lwCampaignTimezone = $('#lwCampaignTimezone')[0].selectize;
                            lwCampaignTimezone.on('change', function(value) {                            
                                campaignData.campaignTimeZone = lwCampaignTimezone.options[value]?.text;                            
                            });
                        }
                        
                        _.forEach(responseData.data.bodyParameters, function(value) {
                            var dynamicTemplateField = $('#lwField_'+value)[0].selectize;
                            dynamicTemplateField.on('change', function(item) {
                                document.getElementById('lw_'+value+'_text').textContent = item;
                            });
                            
                        });

                        _.forEach(responseData.data.buttonParameters, function(value) {
                            var dynamicTemplateField = $('#lwField_'+value)[0].selectize;
                            dynamicTemplateField.on('change', function(item) {
                                document.getElementById('lw_'+value+'_text').textContent = item;
                            });
                            
                        });

                        _.forEach(responseData.data.carouselTemplateData, function(carouselData) {
                            if (!_.isUndefined(carouselData.cards)) {
                                _.forEach(carouselData.cards, function(item, index) {
                                    if(!_.isUndefined(item.components[1].example)) {
                                        _.forEach(item.components[1].example.body_text[0], function(value, key) {
                                            var dynamicTemplateField = $('#lwField_lwField_'+index+'_'+key)[0].selectize;
                                            dynamicTemplateField.on('change', function(item) {
                                                document.getElementById('lw_'+index+'_'+key+'_text').textContent = item;
                                            });
                                        });
                                    }
                                });
                            }
                        });

                        if (_.has(responseData.data.buttonItems, 'COPY_CODE')) {                            
                            var dynamicTemplateField = $('#lwField_copy_code')[0].selectize;
                            dynamicTemplateField.on('change', function(item) {
                                document.getElementById('lw_copy_code_text').textContent = item;
                            });
                        }

                        if (responseData.data.headerFormat == 'DOCUMENT') {
                            var dynamicTemplateField = $('#lwField_header_document_name')[0].selectize;
                            dynamicTemplateField.on('change', function(item) {
                                document.getElementById('lw_header_document_name_text').textContent = item;
                            });
                        } else if (responseData.data.headerFormat == 'LOCATION') {
                            var locationLatitude = $('#lwField_location_latitude')[0].selectize,
                                locationLongitude = $('#lwField_location_longitude')[0].selectize,
                                locationName = $('#lwField_location_name')[0].selectize,
                                locationAddress = $('#lwField_location_address')[0].selectize;

                            locationLatitude.on('change', function(item) {
                                document.getElementById('lw_location_latitude_text').textContent = item;
                            });

                            locationLongitude.on('change', function(item) {
                                document.getElementById('lw_location_longitude_text').textContent = item;
                            });

                            locationName.on('change', function(item) {
                                document.getElementById('lw_location_name_text').textContent = item;
                            });

                            locationAddress.on('change', function(item) {
                                document.getElementById('lw_location_address_text').textContent = item;
                            });                                
                        }
                    });
                }
            };
            @if(request()->use_template)
            // Initial Change if required
            __DataRequest.post('{{ route('vendor.request.template.view') }}', {
                'template_selection' : '{{ request()->use_template }}',
            }, function() {
                __DataRequest.updateModels({selectedTemplate:'{{ request()->use_template }}'});
                    _.defer(function(){
                        if ($('#lwTemplateStructureContainer').find('.lw-file-uploader').length) {
                        window.initUploader();
                    }
                });
            }, {
                eventStreamUpdate: true
            });
            @endif
        })(jQuery);
</script>
@endpush