@php
    $isNotNonTemplateCampaign = (!isset($nonTemplateCampaign) or !$nonTemplateCampaign);
@endphp
<style>
    /* Design Système WhatsClick - Formulaire Bot Reply (Style Vert Émeraude Unifié) */
    .lw-form-modal-body fieldset {
        background-color: #fafdfb !important;
        border: 1px solid #a7f3d0 !important;
        border-left: 4px solid #10b981 !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.25rem !important;
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.04) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .lw-form-modal-body fieldset:hover {
        border-color: #6ee7b7 !important;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.08) !important;
    }
    .lw-form-modal-body fieldset legend {
        width: auto !important;
        padding: 0.25rem 0.75rem !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        color: #065f46 !important;
        background-color: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        border-radius: 0.5rem !important;
        margin-bottom: 0.75rem !important;
        display: inline-flex !important;
        align-items: center !important;
    }
    .lw-form-modal-body .help-text {
        background-color: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        border-radius: 0.5rem !important;
        color: #065f46 !important;
        font-size: 0.85rem !important;
    }
    .lw-form-modal-body .lw-insert-var-btn {
        transition: all 0.15s ease-in-out;
        text-transform: none !important;
    }
    .lw-form-modal-body .lw-insert-var-btn:hover {
        background-color: #10b981 !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
    }
    .lw-form-modal-body .form-control {
        border-radius: 0.5rem !important;
        border: 1px solid #cbd5e1 !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    .lw-form-modal-body .form-control:focus {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
    }
</style>
<script>
if (typeof window.insertVariableAtCursor !== 'function') {
    window.insertVariableAtCursor = function(targetId, textToInsert) {
        var elem = document.getElementById(targetId);
        if (!elem) return;
        var start = elem.selectionStart || 0;
        var end = elem.selectionEnd || 0;
        var val = elem.value || '';
        elem.value = val.substring(0, start) + textToInsert + val.substring(end);
        elem.selectionStart = elem.selectionEnd = start + textToInsert.length;
        elem.focus();
        elem.dispatchEvent(new Event('input', { bubbles: true }));
    };
}
</script>
<!-- Add New Advance Bot Reply Modal -->
<x-lw.modal id="lwAddNewAdvanceBotReply" modal-dialog-class="modal-lg lw-modal-xl" :header="$isNotNonTemplateCampaign ? __tr('Add New Bot Reply') : __tr('Add New Preset Message')" :hasForm="true">
    <!--  Add New Bot Reply Form -->
    <x-lw.form x-data="{triggerType:'',headerType:'',interactiveButtonType:'button'}" id="lwAddNewAdvanceBotReplyForm"
        :action="route('vendor.bot_reply.write.create')"
        :data-callback-params="['modalId' => '#lwAddNewAdvanceBotReply', 'datatableId' => '#lwBotReplyList']"
        data-callback="appFuncs.modelSuccessCallback" x-on:reset-modal.window="headerType = ''">

        <div id="lwAddBotReplyBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwAddBotReplyBody-template">

        <!-- form body -->
        <div>
            <!-- form fields form fields -->
            <input type="hidden" name="message_type" :value="isAdvanceBot">
            <template x-if="botFlowUid">
                <input type="hidden" name="bot_flow_uid" :value="botFlowUid">
            </template>
            <template x-if="ntCampaignPresetMessage">
                <input type="hidden" name="nt_campaign_preset_message" value="yes">
            </template>
            <!-- Name -->
            <x-lw.input-field type="text" id="lwAdvanceBotNameField" data-form-group-class="" :label="__tr('Name')"
                name="name" required="true" />
            <!-- /Name -->
            <fieldset class="lw-dynamic-template-container">
                <legend><i class="fas fa-reply text-success mr-1"></i> {{  __tr('Reply Message') }}</legend>

                <div x-show="isAdvanceBot == 'template'" x-data="{ selectedTemplate: '', onTemplateChange() {

                                if (!_.isEmpty(this.selectedTemplate)) {
                                    __DataRequest.post('{{ route('vendor.request.template.view') }}', {
                                        'template_selection': this.selectedTemplate,
                                        'page_type': 'bot_reply_form',
                                        'form_type': 'add_template_bot'
                                    }, function() {
                                        _.defer(function() {
                                            $('.lw-change-template-btn').remove();
                                            window.lwPluginsInit();
                                        });
                                    });
                                } else {
                                    $('#lwTemplateStructureContainer').text('');
                                }
                            } 
                        }">
                        @if($isNotNonTemplateCampaign)
                        <div x-cloak>
                            <x-lw.input-field 
                                x-model="selectedTemplate"
                                placeholder="{!! __tr('Select & Configure Template') !!}" 
                                type="selectize"
                                data-lw-plugin="lwSelectize" 
                                data-selected=" " 
                                type="select"
                                id="lwField_templateSelection" 
                                name="template_selection" 
                                data-form-group-class=""
                                required
                                class="custom-select" 
                                :label="__tr('Select Template')">
                                <x-slot name="selectOptions">
                                    <option value="">{{ __tr('Select & Configure Template') }}</option>
                                    @foreach ($templateData['whatsAppTemplates'] as $whatsAppTemplate)
                                        <option value="{{ $whatsAppTemplate->_uid }}">
                                            {{ $whatsAppTemplate->template_name }} ({{ $whatsAppTemplate->language }}) ({{ $whatsAppTemplate->category }})
                                        </option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>
                            <div x-effect="onTemplateChange()"></div>
                        </div>
                        @endif
                    <div id="lwTemplateStructureContainer" class="lw-template-structure-model"></div>
                </div>

                <div x-show="isAdvanceBot != 'template'">
                    <!-- Reply_Text -->
                    <div class="form-group" x-show="isAdvanceBot == 'simple' || isAdvanceBot == 'interactive'">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="lwAdvanceBotReplyTextField" class="mb-0">{{ __tr('Reply Text') }}</label>
                            <small class="text-muted font-weight-bold"><span id="replyTextCounter">0</span> / 1024 {{ __tr('caractères') }}</small>
                        </div>
                        <textarea cols="10" rows="3" id="lwAdvanceBotReplyTextField" class="lw-form-field form-control"
                            placeholder="{{ __tr('Add your main message body text here') }}" name="reply_text" required="true"
                            maxlength="1024" oninput="$('#replyTextCounter').text(this.value.length)"></textarea>
                        <x-whatsapp-format-buttons inputId="lwAdvanceBotReplyTextField" />
                        <div class="help-text my-3 border p-3">
    <div class="mb-2"><i class="fas fa-info-circle text-success mr-1"></i> {{  __tr("You are free to use following dynamic variables for reply text, which will get replaced with contact's concerned field value.") }} <small class="text-success font-weight-bold">({{ __tr("Click a variable to insert it") }})</small></div>
    <div class="pt-1">
        @foreach($dynamicFields as $dynField)
            <span class="badge badge-success cursor-pointer font-weight-bold mr-1 mb-2 p-2 shadow-sm lw-insert-var-btn" style="cursor: pointer; font-size: 0.85rem;" onclick="insertVariableAtCursor('lwAdvanceBotReplyTextField', '{{ $dynField }}')" title="{{ __tr('Click to insert') }}">{{ $dynField }}</span>
        @endforeach
    </div>
</div>
                    </div>
                    <!-- /Reply_Text -->
                    <div x-show="isAdvanceBot == 'interactive' || isAdvanceBot == 'media'">
                        {{-- select type --}}
                        <div x-show="isAdvanceBot == 'interactive'" class="form-group">
                            <label for="lwAdvanceBotHeaderTypeField">{{ __tr('Header Type (optional)') }}</label>
                            <select x-init="$watch('headerType', function(val) {
                                if((interactiveButtonType == 'list') && !_.includes(['','text'], val)) {
                                    interactiveButtonType = 'button';
                                }
                            })" x-model="headerType" id="lwAdvanceBotHeaderTypeField" class="form-control custom-select" name="header_type">
                                <option value="">{{ __tr('Aucun') }}</option>
                                <option value="text">{{ __tr('Text') }}</option>
                                <option value="image">{{ __tr('Image') }}</option>
                                <option value="video">{{ __tr('Video') }}</option>
                                <option value="document">{{ __tr('Document') }}</option>
                            </select>
                        </div>

                        <div x-show="isAdvanceBot == 'media'" class="form-group">
                            <label for="lwMediaHeaderType">{{ __tr('Header Type') }}</label>
                            <select x-model="headerType" id="lwMediaHeaderType" class="form-control custom-select" name="media_header_type">
                                <option value="">{{ __tr('Aucun (None)') }}</option>
                                <option value="image">{{ __tr('Image') }}</option>
                                <option value="video">{{ __tr('Video') }}</option>
                                <option value="document">{{ __tr('Document') }}</option>
                                <option value="audio">{{ __tr('Audio') }}</option>
                            </select>
                        </div>

                        <div class="my-3">
                        {{-- document --}}
                        <div x-show="headerType == 'document'" class="form-group col-sm-12">
                        <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>" id="lwDocumentField" data-file-input-element="#lwMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                        </div>
                        {{-- image --}}
                        <div x-show="headerType == 'image'" class="form-group col-sm-12">
                        <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" id="lwImageField" data-file-input-element="#lwMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                    </div>
                    {{-- video --}}
                    <div x-show="headerType == 'video'" class="form-group col-sm-12">
                        <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" id="lwVideoField" data-file-input-element="#lwMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                        </div>
                    {{-- audio --}}
                        <div x-show="headerType == 'audio'" class="form-group col-sm-12">
                        <input id="lwAudioMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Audio') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_audio') ?>" id="lwAudioField" data-file-input-element="#lwMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_audio') ?>' />
                        </div>
                    </div>
                            <input id="lwMediaFileName" type="hidden" value="" name="uploaded_media_file_name" />
                            <div x-show="(isAdvanceBot == 'media') && headerType && (headerType != 'audio')">
                            <label for="lwMediaCaptionText">{{  __tr('Caption/Text') }}</label>
                            <textarea name="caption" id="lwCaptionField" class="form-control" rows="2"></textarea>
                            <x-whatsapp-format-buttons inputId="lwCaptionField" />
                            <div class="help-text my-3 border p-3">
    <div class="mb-2"><i class="fas fa-info-circle text-success mr-1"></i> {{  __tr("You are free to use following dynamic variables for caption, which will get replaced with contact's concerned field value.") }} <small class="text-success font-weight-bold">({{ __tr("Click a variable to insert it") }})</small></div>
    <div class="pt-1">
        @foreach($dynamicFields as $dynField)
            <span class="badge badge-success cursor-pointer font-weight-bold mr-1 mb-2 p-2 shadow-sm lw-insert-var-btn" style="cursor: pointer; font-size: 0.85rem;" onclick="insertVariableAtCursor('lwCaptionField', '{{ $dynField }}')" title="{{ __tr('Click to insert') }}">{{ $dynField }}</span>
        @endforeach
    </div>
</div>
                        </div>
                        <div x-show="headerType == 'text'" class="form-group">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="lwAdvanceHeaderText" class="mb-0">{{ __tr('Header Text') }}</label>
                                <small class="text-muted font-weight-bold"><span id="headerTextCounter">0</span> / 60 {{ __tr('caractères') }}</small>
                            </div>
                            <input type="text" id="lwAdvanceHeaderText" class="form-control" name="header_text" maxlength="60" oninput="$('#headerTextCounter').text(this.value.length)">
                        </div>
                        <div class="mt-4" x-show="isAdvanceBot == 'interactive'">
                        <strong> <input type="radio" name="interactive_type" x-model="interactiveButtonType" value="button" id="lwNewAdvanceBotReplyBtnType"> <label  class="mr-2" for="lwNewAdvanceBotReplyBtnType">{{  __tr('Reply Buttons') }}</label>
                            <input type="radio" name="interactive_type" x-model="interactiveButtonType" value="cta_url" id="lwNewAdvanceBotCtaUrlBtnType"> <label class="mr-2" for="lwNewAdvanceBotCtaUrlBtnType">{{  __tr('CTA URL Button') }}</label>
                            <input type="radio" x-bind:disabled="!_.includes(['','text'],headerType)" name="interactive_type" x-model="interactiveButtonType" value="list" id="lwNewAdvanceBotListMessageType"> <label x-bind:class="!_.includes(['','text'],headerType) ? 'text-muted' : ''" class="mr-2" for="lwNewAdvanceBotListMessageType">{{  __tr('List Message') }} <abbr x-show="!_.includes(['','text'],headerType)" title="{{  __tr('Header is optional, Only text type for header is supported for the list message') }}">?</abbr></label></strong>
                            <hr class="mt-1 mb-2">
                        <template x-if="interactiveButtonType == 'button'">
                            <div>
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="lwAdvanceButton1" class="mb-0">{{ __tr('Button 1 Label') }}</label>
                                        <small class="text-muted font-weight-bold"><span id="btn1Counter">0</span> / 20 {{ __tr('caractères') }}</small>
                                    </div>
                                    <input type="text" id="lwAdvanceButton1" class="form-control" name="buttons[1]" required maxlength="20" oninput="$('#btn1Counter').text(this.value.length)">
                                </div>
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="lwAdvanceButton2" class="mb-0">{{ __tr('Button 2 Label (optional)') }}</label>
                                        <small class="text-muted font-weight-bold"><span id="btn2Counter">0</span> / 20 {{ __tr('caractères') }}</small>
                                    </div>
                                    <input type="text" id="lwAdvanceButton2" class="form-control" name="buttons[2]" maxlength="20" oninput="$('#btn2Counter').text(this.value.length)">
                                </div>
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="lwAdvanceButton3" class="mb-0">{{ __tr('Button 3 Label (optional)') }}</label>
                                        <small class="text-muted font-weight-bold"><span id="btn3Counter">0</span> / 20 {{ __tr('caractères') }}</small>
                                    </div>
                                    <input type="text" id="lwAdvanceButton3" class="form-control" name="buttons[3]" maxlength="20" oninput="$('#btn3Counter').text(this.value.length)">
                                </div>
                            </div>
                        </template>
                        <template x-if="interactiveButtonType == 'cta_url'">
                            <div>
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="lwCtaUrlButtonDisplayText" class="mb-0">{{ __tr('CTA Button Display Text') }}</label>
                                        <small class="text-muted font-weight-bold"><span id="ctaBtnCounter">0</span> / 20 {{ __tr('caractères') }}</small>
                                    </div>
                                    <input type="text" id="lwCtaUrlButtonDisplayText" class="form-control" name="button_display_text" required maxlength="20" oninput="$('#ctaBtnCounter').text(this.value.length)">
                                </div>
                                <x-lw.input-field type="text" id="lwCtaButtonUrl" data-form-group-class="" :label="__tr('CTA Button URL')" name="button_url" required="true" />
                            </div>
                        </template>
                        <template x-if="interactiveButtonType == 'list'">
                            <div x-data="{botListMessageSections:{}, addListSection : function() {
                                let uniqueSectionId = __Utils.generateUniqueId('section_');
                                this.botListMessageSections[uniqueSectionId] = {
                                    index: _.size(this.botListMessageSections) + 1,
                                    id:uniqueSectionId,
                                    title:'',
                                    rows:{}
                                };
                            },addListSectionRow : function(sectionId) {
                                let uniqueRowId = __Utils.generateUniqueId('row_');
                                this.botListMessageSections[sectionId]['rows'][uniqueRowId] = {
                                    index: _.size(this.botListMessageSections[sectionId]['rows']) + 1,
                                    id: uniqueRowId,
                                    row_id:'',title:'',
                                    description:''
                                };
                                },deleteSection(sectionId){
                                delete this.botListMessageSections[sectionId];
                                },deleteRow(sectionId, rowId){
                                delete this.botListMessageSections[sectionId]['rows'][rowId];
                            }}" >
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="lwListButtonText" class="mb-0">{{ __tr('Button Label') }}</label>
                                        <small class="text-muted font-weight-bold"><span id="listBtnCounter">0</span> / 20 {{ __tr('caractères') }}</small>
                                    </div>
                                    <input type="text" id="lwListButtonText" class="form-control" name="list_button_text" required maxlength="20" oninput="$('#listBtnCounter').text(this.value.length)">
                                </div>
                                <template x-for="(botListMessageSection, index) in botListMessageSections">
                                    <fieldset>
                                        <legend  class="py-1 px-2 mb-1"><small>{{  __tr('Section') }}</small></legend>
                                        <button @click.prevent="deleteSection(botListMessageSection.id)" class="btn btn-link float-right p-1 mt--4" type="button"><i class="fa fa-times text-danger"></i></button>
                                        <x-lw.input-field type="text" id="lwListMessageSectionTitle" data-form-group-class="" :label="__tr('Section Title')" x-bind:name="'sections[' + botListMessageSection.id+'][title]'" required="true" />
                                        <input type="hidden" x-bind:name="'sections[' + botListMessageSection.id+'][id]'" x-bind:value="botListMessageSection.id" required="true">
                                        <template x-for="botListMessageRow in botListMessageSection.rows">
                                        <fieldset>
                                            <legend  class="py-1 px-2 mb-1"><small>{{  __tr('Row') }}</small></legend>
                                            {{-- delete row btn --}}
                                            <button @click.prevent="deleteRow(botListMessageSection.id, botListMessageRow.id)" class="btn btn-link float-right p-1 mt--4" type="button"><i class="fa fa-times text-danger"></i></button>
                                            <input type="hidden" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][id]'" x-bind:value="botListMessageRow.id" required="true">
                                            {{-- row id --}}
                                            <x-lw.input-field type="text" x-bind:id="'lwListMessageRowId'+botListMessageSection.id+botListMessageRow.id" data-form-group-class="" :label="__tr('Row ID')" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][row_id]'" required="true" />
                                            {{-- row title --}}
                                            <x-lw.input-field type="text" x-bind:id="'lwListMessageRowTitle'+botListMessageSection.id+botListMessageRow.id" data-form-group-class="" :label="__tr('Row Title')" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][title]'" required="true" />
                                            {{-- row description --}}
                                            <div class="form-group">
                                                <label for="">{{ __tr('Row Description (optional)') }}</label>
                                                <textarea class="form-control" rows="2" x-bind:id="'lwListMessageRowDescription'+botListMessageSection.id+botListMessageRow.id" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][description]'"></textarea>
                                            </div>
                                        </fieldset>
                                    </template>
                                    <button type="button" class="btn btn-sm btn-light my-2" @click="addListSectionRow(botListMessageSection.id)">{{  __tr('Add Row') }}</button>
                                    </fieldset>
                                </template>
                                <button type="button" class="btn btn-sm btn-dark my-2" @click="addListSection()">{{  __tr('Add Section') }}</button>
                            </div>
                            </template>
                        </div>
                        {{-- footer text --}}
                        <div x-show="isAdvanceBot == 'interactive'" class="form-group">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="lwAdvanceFooterText" class="mb-0">{{ __tr('Footer Text (optional)') }}</label>
                                <small class="text-muted font-weight-bold"><span id="footerTextCounter">0</span> / 60 {{ __tr('caractères') }}</small>
                            </div>
                            <input type="text" id="lwAdvanceFooterText" class="form-control" name="footer_text" maxlength="60" oninput="$('#footerTextCounter').text(this.value.length)">
                        </div>
                    </div>
                    {{-- /reply --}}
                </div>
            </fieldset>
            <template x-if="!botFlowUid && !ntCampaignPresetMessage">
                <div>
                    <!-- Trigger_Type -->
                    <x-lw.input-field x-model="triggerType" type="selectize" id="lwAdvanceBotTriggerTypeField"
                    data-form-group-class="" data-selected=" " :label="__tr('Trigger Type')" name="trigger_type"
                    required="true">
                    <x-slot name="selectOptions">
                        <option value="">{{ __tr('How do you want to trigger this message?') }}</option>
                        @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                        <option value="{{ $replyBotTypeKey }}">{{ $replyBotType['title'] }} </option>
                        @endforeach
                    </x-slot>
                    </x-lw.input-field>
                    <!-- /Trigger_Type -->
                    @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                    <div x-show="triggerType == '{{ $replyBotTypeKey }}'" class="alert alert-dark">{{
                        $replyBotType['description'] }}</div>
                    @endforeach
                    <!-- Reply_Trigger -->
                    <div x-show="triggerType != 'welcome'">
                        <x-lw.input-field placeholder="{{ __tr('What\'s that magic words that will trigger this reply?') }}" type="text" id="lwAdvanceBotReplyTriggerField" data-form-group-class=""
                            :label="__tr('Reply Trigger Subject')" name="reply_trigger" required="true" />
                            <div><small class="text-muted">{{  __tr('You can have comma separated multiple triggers.') }}</small></div>
                    </div>
                    <!-- /Reply_Trigger -->
                </div>
            </template>
            
            <!-- Bot Actions -->
            @if($isNotNonTemplateCampaign)
                <fieldset x-data="{ collapsed: true, assignUserType: 'no_action' }" class="border p-3 mb-3">
                    <legend class="w-auto px-2" style="cursor: pointer;" @click="collapsed = !collapsed">
                        <i class="fa mr-1" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-down'"></i> <i class="fas fa-bolt text-success mr-1"></i> {{  __tr('Actions') }}
                    </legend>
                    <div x-show="!collapsed" x-transition>
                        <!-- Assign Team Member -->
                        <fieldset>
                            <legend><i class="fas fa-user-plus text-success mr-1"></i> {{  __tr('Assign Team Member') }}</legend>
                            <x-lw.input-field id="lwCurrentlyAssignedUserId" type="selectize" data-form-group-class="mt--4" name="bot_actions[assigned_users_id]" class="custom-select" data-selected="" x-model="assignUserType">
                                <x-slot name="selectOptions">
                                    <optgroup label="{{ __tr('No Action Perform') }}">
                                        <option value="no_action">{{  __tr('No Action') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Remove team member from contact') }}">
                                        <option value="no_one">{{  __tr('Unassigned') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Assign random team member to contact') }}">
                                        <option value="random">{{  __tr('Random') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Team Members') }}">
                                        @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                        <option value="{{ $vendorMessagingUser->_id }}">{{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} @if($vendorMessagingUser->_uid == getUserUID()) ({{  __tr('You') }}) @endif</option>
                                        @endforeach
                                    </optgroup>
                                </x-slot>
                            </x-lw.input-field>
                            <div x-show="assignUserType == 'no_action'">
                                <small class="text-muted">{{  __tr('Note: No action will perform when this bot is trigger.') }}</small>
                            </div>

                            <div x-show="assignUserType == 'no_one'">
                                <small class="text-muted">{{  __tr('Note: The team member will be unassigned when this bot is triggered.') }}</small>
                            </div>

                            <div x-show="assignUserType == 'random'">
                                <small class="text-muted">{{  __tr("Note: Random team member will be assigned, It won't affect contact to whom team member already assigned.") }}</small>
                            </div>
                            <div x-show="assignUserType !== '' && !isNaN(Number(assignUserType))">
                                <small class="text-muted">{{  __tr("Existing team member will overwrite.") }}</small>
                            </div>
                        </fieldset>
                        <!-- /Assign Team Member -->

                        <!-- Assign Label -->
                        <fieldset>
                            <legend><i class="fas fa-tag text-success mr-1"></i> {{  __tr('Assign Labels/Tags') }}</legend>
                            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwAssignLabelsField" data-form-group-class="mt--4" name="bot_actions[contact_labels][]" multiple >
                                <x-slot name="selectOptions">
                                <option value="">{{ __tr('Select Labels') }}</option>
                                    @foreach($allLabels as $label)
                                        <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("The selected labels will be assigned to the contact when triggered.") }}</small>
                        </fieldset>
                        <!-- /Assign Label -->

                        <!-- Unassign Label -->
                        <fieldset>
                            <legend><i class="fas fa-tag-slash text-success mr-1"></i> {{  __tr('Unassign Labels/Tags') }}</legend>
                            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwUnassignLabelsField" data-form-group-class="mt--4" name="bot_actions[unassign_contact_labels][]" multiple >
                                <x-slot name="selectOptions">
                                <option value="">{{ __tr('Select Labels') }}</option>
                                    @foreach($allLabels as $label)
                                        <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("The selected labels will be removed from the contact when triggered.") }}</small>
                        </fieldset>
                        <!-- /Unassign Label -->

                        <!-- Promotional -->
                        <fieldset>
                            <legend><i class="fas fa-bullhorn text-success mr-1"></i> {{  __tr('Promotional') }}</legend>
                            <select id="lwPromotional" class="form-control" placeholder="<?= __tr('Promotional') ?>" name="bot_actions[promotional]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.promotional') as $promotionalKey => $promotionalItem)
                                    <option value="<?= $promotionalKey ?>"><?= $promotionalItem['title'] ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("Defines whether this bot reply is promotional.") }}</small>
                        </fieldset>
                        <!-- /Promotional -->

                        <!-- Drip Campaign Subscription -->
                        @if(class_exists('Addons\WhatsJetDripCampaignAddon\Models\DripCampaign'))
                        @php 
                            $dripCampaigns = \Addons\WhatsJetDripCampaignAddon\Models\DripCampaign::where('vendors__id', getVendorId())->where('status', 1)->get(); 
                        @endphp
                        <fieldset class="lw-drip-fieldset form-group border p-3 rounded mb-3">
                            <legend class="w-auto px-2 font-weight-bold" style="font-size: 1.05rem;"><i class="fas fa-water text-success mr-1"></i> {{  __tr('Inscription à la campagne de relance') }}</legend>
                            @if($dripCampaigns->count())
                            <select id="lwAddDripCampaign" class="form-control" name="addon_drip_campaigns__id">
                                <option value=""><?= __tr('No Action (Do not subscribe)') ?></option>
                                @foreach ($dripCampaigns as $dripCampaign)
                                    <option value="<?= $dripCampaign->_id ?>"><?= $dripCampaign->title ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("When this bot reply is triggered, the contact will be automatically subscribed to the selected drip campaign.") }}</small>
                            @else
                            <div class="alert alert-warning mb-0 py-2">
                                <i class="fas fa-exclamation-circle mr-1"></i> {{ __tr("Aucune campagne Drip active trouvée. Vous devez d'abord créer une campagne Drip pour pouvoir y inscrire vos contacts.") }}
                                <a href="{{ route('addon.WhatsJetDripCampaignAddon.index') }}" target="_blank" class="btn btn-sm btn-outline-primary ml-2"><i class="fas fa-plus mr-1"></i> {{ __tr("Créer une campagne Drip") }}</a>
                            </div>
                            @endif
                        </fieldset>
                        @endif
                        <!-- /Drip Campaign Subscription -->

                        <!-- AI Bot -->
                        <fieldset>
                            <legend><i class="fas fa-robot text-success mr-1"></i> {{  __tr('AI Bot') }}</legend>
                            <select id="lwAiBot" class="form-control" placeholder="<?= __tr('AI Bot') ?>" name="bot_actions[ai_bot]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.ai_bot') as $aiBotKey => $aiBot)
                                    <option value="<?= $aiBotKey ?>"><?= $aiBot['title'] ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("Allows enabling or disabling AI Bot handling for this contact.") }}</small>
                        </fieldset>
                        <!-- /AI Bot -->

                        <!-- Reply Bot -->
                        <fieldset>
                            <legend><i class="fas fa-comment-dots text-success mr-1"></i> {{  __tr('Reply Bot') }}</legend>
                            <select id="lwReplyBot" class="form-control" placeholder="<?= __tr('Reply Bot') ?>" name="bot_actions[reply_bot]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.reply_bot') as $replyBotKey => $replyBot)
                                    <option value="<?= $replyBotKey ?>"><?= $replyBot['title'] ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("Allows triggering or pausing other automated bot replies.") }}</small>
                        </fieldset>
                        <!-- /Reply Bot -->
                    </div>
                </fieldset>
            @endif
            <!-- /Bot Actions -->
            
            <div class="form-group pt-3 ml-4">
                <x-lw.checkbox id="lwAddBotStatus" :offValue="0" value="1" :checked="true" name="status" data-lw-plugin="lwSwitchery" :label="__tr('Status')" />
            </div>
            
             <div x-show="!ntCampaignPresetMessage">
                <div class="my-4 ml-4">
                    <x-lw.checkbox id="lwValidateBotReply" :offValue="0" name="validate_bot_reply" value="1" data-lw-plugin="lwSwitchery" data-size="small" :label="$isNotNonTemplateCampaign ? __tr('Validate Bot Reply by Sending Test Message') : __tr('Validate Preset by Sending Test Message')" />
                </div>
            </div>
            </script>
            <template x-if="botFlowUid">
                <input type="hidden" name="trigger_type" value="is">
            </template>
            <template x-if="ntCampaignPresetMessage">
                <input type="hidden" name="trigger_type" value="NT_CAMPAIGN_MESSAGE">
            </template>
        </div>
        
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </x-lw.form>
    <!--/  Add New Bot Reply Form -->
</x-lw.modal>
<!--/ Add New Advance Bot Reply Modal -->
<!-- Edit Bot Reply Modal -->
<x-lw.modal id="lwEditBotReply" modal-dialog-class="modal-lg lw-modal-xl" :header="$isNotNonTemplateCampaign ? __tr('Edit Bot Reply') : __tr('Edit Preset Message')" :hasForm="true">
    <!--  Edit Bot Reply Form -->
    <x-lw.form id="lwEditBotReplyForm" :action="route('vendor.bot_reply.write.update')"
        :data-callback-params="['modalId' => '#lwEditBotReply', 'datatableId' => '#lwBotReplyList']"
        data-callback="appFuncs.modelSuccessCallback" x-data="{headerType:''}" x-data="{ botFlowUid:'{{ $botFlowUid ?? null }}' }">
        <!-- form body -->
        <div id="lwEditBotReplyBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwEditBotReplyBody-template">
            <div>
            <input type="hidden" name="botReplyIdOrUid" value="<%- __tData._uid %>" />
            <template x-if="botFlowUid">
                <input type="hidden" name="bot_flow_uid" :value="botFlowUid">
            </template>
                <!-- form fields -->
                <!-- Name -->
            <x-lw.input-field type="text" id="lwNameEditField" data-form-group-class="" :label="__tr('Name')" value="<%- __tData.name %>" name="name"  required="true"/>
        <!-- /Name -->
        <fieldset class="lw-dynamic-template-container">
            <legend><i class="fas fa-reply text-success mr-1"></i> {{  __tr('Reply Message') }}</legend>
        <% if(__tData.__data?.template_message) { %>
            <input type="hidden" name="message_type" value="template">
            <div x-data="{ selectedTemplate: '<%= __tData.__data?.template_message?.template_data?.inputs?.template_uid %>',
                sendMessageViaMM: '<%= __tData.__data?.template_message?.template_data?.inputs?.send_message_via_marketing_message_api %>',
                onTemplateChange() {
                        $('.lw-change-template-btn').hide();
                        if (!_.isEmpty(this.selectedTemplate)) {
                            var self = this;
                            __DataRequest.post('{{ route('vendor.request.template.view') }}', {
                                'template_selection': this.selectedTemplate,
                                'page_type': 'bot_reply_form',
                                'form_type': 'edit_template_bot'
                            }, function(responseData) {
                                $('.lw-template-header-variables-container').remove();
                                _.defer(function() {
                                    $('.lw-change-template-btn').hide();
                                    window.lwPluginsInit();
                                    if (self.sendMessageViaMM == 'on') {
                                        $('#lwSendMessageViaMarketingMessageAPI').click();
                                    }
                                });      
                            });
                        } else {
                            $('#lwTemplateStructureContainer').text('');
                        }
                    } 
                }">
                @if($isNotNonTemplateCampaign)
                <div x-cloak>
                    <x-lw.input-field 
                        x-model="selectedTemplate"
                        placeholder="{!! __tr('Select & Configure Template') !!}" 
                        type="selectize"
                        data-lw-plugin="lwSelectize" 
                        data-selected="<%= __tData.__data?.template_message?.template_data?.inputs?.template_uid %>" 
                        type="select"
                        id="lwField_templateSelection" 
                        name="template_selection" 
                        data-form-group-class=""
                        required
                        class="custom-select" 
                        :label="__tr('Select Template')"
                        disabled>
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('Select & Configure Template') }}</option>
                            @foreach ($templateData['whatsAppTemplates'] as $whatsAppTemplate)
                                <option value="{{ $whatsAppTemplate->_uid }}">
                                    {{ $whatsAppTemplate->template_name }} ({{ $whatsAppTemplate->language }}) ({{ $whatsAppTemplate->category }})
                                </option>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                    <div x-effect="onTemplateChange()"></div>
                </div>
                @endif
                <div x-cloak id="lwTemplateStructureEditContainer" class="lw-template-structure-model container-fluid"></div>
            </div>
        <% } %>

        <% if (!__tData.__data?.template_message) { %>
        <!-- Reply_Text -->
        <% if(!__tData.__data?.media_message)  { %>
        <div class="form-group">
        <label for="lwReplyTextEditField">{{ __tr('Reply Text') }}</label>
        <textarea cols="10" rows="3" id="lwReplyTextEditField" value="<%- __tData.reply_text %>" class="lw-form-field form-control" placeholder="{{ __tr('Reply Text') }}" name="reply_text"  required="true"><%- __tData.reply_text %></textarea>
        <x-whatsapp-format-buttons inputId="lwReplyTextEditField" />
        <div class="help-text my-3 border p-3">
    <div class="mb-2"><i class="fas fa-info-circle text-success mr-1"></i> {{  __tr("You are free to use following dynamic variables for reply text, which will get replaced with contact's concerned field value.") }} <small class="text-success font-weight-bold">({{ __tr("Click a variable to insert it") }})</small></div>
    <div class="pt-1">
        @foreach($dynamicFields as $dynField)
            <span class="badge badge-success cursor-pointer font-weight-bold mr-1 mb-2 p-2 shadow-sm lw-insert-var-btn" style="cursor: pointer; font-size: 0.85rem;" onclick="insertVariableAtCursor('lwAdvanceBotReplyTextField', '{{ $dynField }}')" title="{{ __tr('Click to insert') }}">{{ $dynField }}</span>
        @endforeach
    </div>
</div>
            </div>
            <% } %>
            <% if(__tData.__data?.media_message)  { %>
            <span x-init="headerType = '<%- __tData.__data?.media_message.header_type %>'"></span>
            <input type="hidden" name="message_type" value="media">
            <input type="hidden" name="media_header_type" value="<%- __tData.__data?.media_message.header_type %>">
            <fieldset>
                <div class="text-center">
                    <h2 class="text-center"> <%- __tData.__data?.media_message.header_type %></h2>
                    <div class="lw-whatsapp-header-placeholder py-3">
                        <% if(__tData.__data?.media_message.header_type == 'video')  { %>
                            <video class="lw-whatsapp-header-video" controls src="<%- __tData.__data?.media_message.media_link %>"></video>
                        <% } else if(__tData.__data?.media_message.header_type == 'image') { %>
                            <img class="lw-whatsapp-header-image" src="<%- __tData.__data?.media_message.media_link %>" alt="">
                        <% } else if(__tData.__data?.media_message.header_type == 'audio') { %>
                            <audio class="lw-whatsapp-header-audio my-auto mx-4" controls>
                                <source src="<%- __tData.__data?.media_message.media_link %>">
                            {{  __tr('Your browser does not support the audio element.') }}
                            </audio>
                        <% } else if(__tData.__data?.media_message.header_type && (__tData.__data?.media_message.header_type != 'text')) { %>
                            <a target="blank" class="btn btn-dark" href="<%- __tData.__data?.media_message.media_link %>">{{  __tr('Media Link') }}</a>
                        <% } %>
                    </div>
                </div>

                <x-lw.input-field x-model="headerType" type="selectize" id="lwMediaEditHeaderType"
                data-form-group-class="" data-selected="<%- __tData.__data?.media_message.header_type %>" :label="__tr('Header Type')" name="media_header_type" >
                    <x-slot name="selectOptions">
                        <option value="">{{  __tr('None') }}</option>
                        <option value="image">{{  __tr('Image') }}</option>
                        <option value="video">{{  __tr('Video') }}</option>
                        <option value="document">{{  __tr('Document') }}</option>
                        <option value="audio">{{  __tr('Audio') }}</option>
                    </x-slot>
                </x-lw.input-field>
                
                <div class="my-3">
                    {{-- document --}}
                    <div x-show="headerType == 'document'" class="form-group col-sm-12">
                    <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>" id="lwDocumentField" data-file-input-element="#lwUpdateMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                    </div>
                    {{-- image --}}
                    <div x-show="headerType == 'image'" class="form-group col-sm-12">
                    <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" id="lwImageField" data-file-input-element="#lwUpdateMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                    </div>
                    {{-- video --}}
                    <div x-show="headerType == 'video'" class="form-group col-sm-12">
                    <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" id="lwVideoField" data-file-input-element="#lwUpdateMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                    </div>
                    {{-- audio --}}
                    <div x-show="headerType == 'audio'" class="form-group col-sm-12">
                    <input id="lwAudioMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Audio') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_audio') ?>" id="lwAudioField" data-file-input-element="#lwUpdateMediaFileName" data-allowed-media='<?= getMediaRestriction('whatsapp_audio') ?>' />
                    </div>
                </div>
                <input id="lwUpdateMediaFileName" type="hidden" value="" name="uploaded_media_file_name" />
                
                <% if(__tData.__data?.media_message.header_type != 'audio') { %>
                <div class="form-group">
                    <label for="lwMediaCaptionText">{{  __tr('Caption/Text') }}</label>
                    <textarea name="caption" id="lwCaptionFieldEdit" class="form-control" rows="2"><%- __tData.__data?.media_message.caption %></textarea>
                    <x-whatsapp-format-buttons inputId="lwCaptionFieldEdit" />
                    <div class="help-text my-3 border p-3">
    <div class="mb-2"><i class="fas fa-info-circle text-success mr-1"></i> {{  __tr("You are free to use following dynamic variables for caption, which will get replaced with contact's concerned field value.") }} <small class="text-success font-weight-bold">({{ __tr("Click a variable to insert it") }})</small></div>
    <div class="pt-1">
        @foreach($dynamicFields as $dynField)
            <span class="badge badge-success cursor-pointer font-weight-bold mr-1 mb-2 p-2 shadow-sm lw-insert-var-btn" style="cursor: pointer; font-size: 0.85rem;" onclick="insertVariableAtCursor('lwCaptionField', '{{ $dynField }}')" title="{{ __tr('Click to insert') }}">{{ $dynField }}</span>
        @endforeach
    </div>
</div>
                </div>
                <% } %>
            </fieldset>
            <% } else if(__tData.__data?.interaction_message)  { %>
                <input type="hidden" name="message_type" value="interactive">
                {{-- <input type="hidden" name="interactive_type" value="<%- (__tData.__data?.interaction_message.interactive_type && __tData.__data?.interaction_message.interactive_type == 'cta_url') ? 'cta_url' : 'button' %>"> --}}
                <input type="hidden" name="interactive_type" value="<%- (__tData.__data?.interaction_message.interactive_type) ? __tData.__data?.interaction_message.interactive_type : 'button' %>">
                <input type="hidden" name="header_type" value="<%- __tData.__data?.interaction_message.header_type %>">
                <fieldset>
                    <div class="text-center">
                        <h2 class="text-center"> <%- __tData.__data?.interaction_message.header_type %></h2>
                        <%if(__tData.__data?.interaction_message.header_type && (__tData.__data?.interaction_message.header_type != 'text')) { %>
                        <div class="lw-whatsapp-header-placeholder py-3">
                            <% if(__tData.__data?.interaction_message.header_type == 'video')  { %>
                                <video class="lw-whatsapp-header-video" controls src="<%- __tData.__data?.interaction_message.media_link %>"></video>
                            <% } else if(__tData.__data?.interaction_message.header_type == 'image') { %>
                                <img class="lw-whatsapp-header-image" src="<%- __tData.__data?.interaction_message.media_link %>" alt="">
                            <% } else { %>
                                <a target="blank" class="btn btn-dark" href="<%- __tData.__data?.interaction_message.media_link %>">{{  __tr('Media Link') }}</a>
                            <% } %>
                        </div>
                        <% } %>
                    </div>
                    <div class="my-3">
                    {{-- document --}}
                    <% if(__tData.__data?.interaction_message.header_type == 'text')  { %>
                    <x-lw.input-field type="text" id="lwAdvanceEditHeaderText" data-form-group-class=""
                        :label="__tr('Header Text')" value="<%- __tData.__data?.interaction_message.header_text %>" name="header_text" required="true" />
                        <% } %>

                        <% if(__tData.__data?.interaction_message.interactive_type && __tData.__data?.interaction_message.interactive_type == 'list') { window.tempSectionData = _.get(__tData, '__data.interaction_message.list_data.sections', {}); %>
                            <div x-data="{botListMessageSections:window.tempSectionData, addListSection : function() {
                                let uniqueSectionId = __Utils.generateUniqueId('section_');
                                this.botListMessageSections[uniqueSectionId] = {
                                    index: _.size(this.botListMessageSections) + 1,
                                    id:uniqueSectionId,
                                    title:'',
                                    rows:{}
                                };
                            },addListSectionRow : function(sectionId) {
                                let uniqueRowId = __Utils.generateUniqueId('row_');
                                this.botListMessageSections[sectionId]['rows'][uniqueRowId] = {
                                    index: _.size(this.botListMessageSections[sectionId]['rows']) + 1,
                                    id: uniqueRowId,
                                    row_id:'',title:'',
                                    description:''
                                };
                            },deleteSection(sectionId){
                                delete this.botListMessageSections[sectionId];
                            },deleteRow(sectionId, rowId){
                                delete this.botListMessageSections[sectionId]['rows'][rowId];
                            }}" >
                                <h2>{{  __tr('List Message') }}</h2>
                                <x-lw.input-field type="text" value="<%- __tData.__data?.interaction_message.list_data.button_text %>" id="lwListButtonText" data-form-group-class="" :label="__tr('Button Label')" name="list_button_text" required="true" />
                                <template x-for="botListMessageSection in botListMessageSections">
                                    <fieldset>
                                        <legend  class="py-1 px-2 mb-1"><small>{{  __tr('Section') }}</small></legend>
                                        <input type="hidden" x-bind:name="'sections[' + botListMessageSection.id+'][id]'" x-bind:value="botListMessageSection.id" required="true">
                                        <button @click.prevent="deleteSection(botListMessageSection.id)" class="btn btn-link float-right p-1 mt--4" type="button"><i class="fa fa-times text-danger"></i></button>
                                        <x-lw.input-field type="text" x-bind:value="botListMessageSection.title" id="lwListMessageSectionTitle" data-form-group-class="" :label="__tr('Section Title')" x-bind:name="'sections[' + botListMessageSection.id+'][title]'" required="true" />
                                        <template x-for="botListMessageRow in botListMessageSection.rows">
                                        <fieldset>
                                            <legend  class="py-1 px-2 mb-1"><small>{{  __tr('Row') }}</small></legend>
                                            <input type="hidden" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][id]'" x-bind:value="botListMessageRow.id" required="true">
                                            {{-- delete row btn --}}
                                            <button @click.prevent="deleteRow(botListMessageSection.id, botListMessageRow.id)" class="btn btn-link float-right p-1 mt--4" type="button"><i class="fa fa-times text-danger"></i></button>
                                            {{-- row id --}}
                                            <x-lw.input-field type="text" x-bind:id="'lwListMessageRowId'+botListMessageSection.id+botListMessageRow.id" data-form-group-class="" :label="__tr('Row ID')" x-bind:value="botListMessageRow.row_id" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][row_id]'" required="true" />
                                            {{-- row title --}}
                                            <x-lw.input-field type="text" x-bind:id="'lwListMessageRowTitle'+botListMessageSection.id+botListMessageRow.id" data-form-group-class="" :label="__tr('Row Title')" x-bind:value="botListMessageRow.title" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][title]'" required="true" />
                                            {{-- row description --}}
                                            <div class="form-group">
                                                <label for="">{{ __tr('Row Description (optional)') }}</label>
                                                <textarea class="form-control" rows="2" x-bind:id="'lwListMessageRowDescription'+botListMessageSection.id+botListMessageRow.id" x-bind:value="botListMessageRow.description" x-bind:name="'sections[' + botListMessageSection.id+'][rows]['+botListMessageRow.id+'][description]'"></textarea>
                                            </div>
                                        </fieldset>
                                    </template>
                                    <button type="button" class="btn btn-sm btn-light my-2" @click="addListSectionRow(botListMessageSection.id)">{{  __tr('Add Row') }}</button>
                                    </fieldset>
                                </template>
                                <button type="button" class="btn btn-sm btn-dark my-2" @click="addListSection()">{{  __tr('Add Section') }}</button>
                            </div>
                        <% } else if(__tData.__data?.interaction_message.interactive_type && __tData.__data?.interaction_message.interactive_type == 'cta_url') { %>
                            <fieldset>
                                <legend>{{  __tr('Call to Action (CTA) URL Button') }}</legend>
                                <x-lw.input-field type="text" id="lwCtaEditUrlButtonDisplayText" data-form-group-class="" :label="__tr('CTA Button Display Text')" name="button_display_text" value="<%- __tData.__data?.interaction_message.cta_url?.display_text %>" required="true"/>
                                <x-lw.input-field type="text" id="lwCtaEditButtonUrl" data-form-group-class="" :label="__tr('CTA Button URL')" name="button_url" value="<%- __tData.__data?.interaction_message.cta_url?.url %>" required="true" />
                            </fieldset>
                        <% } else { %>
                        <fieldset>
                            <legend>{{  __tr('Reply Buttons') }}</legend>
                            <x-lw.input-field type="text" id="lwAdvanceEditButton1" data-form-group-class="" :label="__tr('Button 1 Label')" name="buttons[1]" value="<%- __tData.__data?.interaction_message.buttons[1] %>" required="true" />
                            <x-lw.input-field type="text" id="lwAdvanceEditButton2" data-form-group-class="" :label="__tr('Button 2 Label (optional)')" name="buttons[2]" value="<%- __tData.__data?.interaction_message.buttons[2] %>" />
                            <x-lw.input-field type="text" id="lwAdvanceEditButton3" data-form-group-class="" :label="__tr('Button 3 Label (optional)')" name="buttons[3]" value="<%- __tData.__data?.interaction_message.buttons[3] %>" />
                        </fieldset>
                        <% } %>
            {{-- footer text --}}
            <x-lw.input-field type="text" id="lwAdvanceEditFooterText" data-form-group-class=""
            :label="__tr('Footer Text (optional)')" name="footer_text" value="<%- __tData.__data?.interaction_message.footer_text %>"  />
            </fieldset>
            {{-- /reply --}}
            </fieldset>
            <% } else { %>
                <input type="hidden" name="message_type" value="simple">
            <% } %>
        <% } %>
            </fieldset>
            @if($isNotNonTemplateCampaign)
                <template x-if="!botFlowUid">
                    <div x-data="{botTriggerType:'<%- __tData.trigger_type %>'}">
                        <!-- Trigger_Type -->
                        <x-lw.input-field class="" type="selectize" x-model="botTriggerType" id="lwTriggerTypeEditField" data-form-group-class="" data-selected="<%- __tData.trigger_type %>" :label="__tr('Trigger Type')" name="trigger_type"  required="true">
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('How do you want to trigger this message?') }}</option>
                            @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                                <option value="{{ $replyBotTypeKey }}">{{ $replyBotType['title'] }} </option>
                                @endforeach
                            </x-slot>
                        </x-lw.input-field>
                        <!-- /Trigger_Type -->
                        @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                        <template x-if="botTriggerType == '{{ $replyBotTypeKey }}'">
                            <div x-show="botTriggerType == '{{ $replyBotTypeKey }}'" class="alert alert-dark">{{ $replyBotType['description'] }}</div>
                        </template>
                        @endforeach
                        <!-- Reply_Trigger -->
                        <template x-if="botTriggerType != 'welcome'">
                            <div>
                                <x-lw.input-field type="text" id="lwReplyTriggerEditField" data-form-group-class="" :label="__tr('Reply Trigger Subject')" value="<%- __tData.reply_trigger %>" name="reply_trigger"  required="true"/>
                                    <div><small class="text-muted">{{  __tr('You can have comma separated multiple triggers.') }}</small></div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Bot Actions -->
                <fieldset x-data="{ collapsed: true }">
                    <legend class="w-auto px-2" style="cursor: pointer;" @click="collapsed = !collapsed">
                        <i class="fa mr-1" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-down'"></i> <i class="fas fa-bolt text-success mr-1"></i> {{  __tr('Actions') }}
                    </legend>
                    <div x-show="!collapsed" x-transition>
                        <!-- Assign Team Member -->
                        <fieldset x-data="{ 
                            assignUserType: '<%= !_.isEmpty(__tData.__data?.bot_actions?.assigned_users_id) ? __tData.__data?.bot_actions?.assigned_users_id : 'no_action'  %>'
                        }">
                            <legend><i class="fas fa-user-plus text-success mr-1"></i> {{  __tr('Assign Team Member') }}</legend>
                            <x-lw.input-field id="lwCurrentlyAssignedUserIdEdit" type="selectize" data-form-group-class="mt--4" name="bot_actions[assigned_users_id]" class="custom-select" data-selected="<%= __tData.__data?.bot_actions?.assigned_users_id %>" x-model="assignUserType">
                                <x-slot name="selectOptions">
                                    <optgroup label="{{ __tr('No Action Perform') }}">
                                        <option value="no_action">{{  __tr('No Action') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Remove team member from contact') }}">
                                        <option value="no_one">{{  __tr('Unassigned') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Assign random team member to contact') }}">
                                        <option value="random">{{  __tr('Random') }}</option>
                                    </optgroup>
                                    <optgroup label="{{ __tr('Team Members') }}">
                                        @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                        <option value="{{ $vendorMessagingUser->_id }}">{{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} @if($vendorMessagingUser->_uid == getUserUID()) ({{  __tr('You') }}) @endif</option>
                                        @endforeach
                                    </optgroup>
                                </x-slot>
                            </x-lw.input-field>
                            <div x-show="assignUserType == 'no_action'">
                                <small class="text-muted">{{  __tr('Note: No action will perform when this bot is trigger.') }}</small>
                            </div>

                            <div x-show="assignUserType == 'no_one'">
                                <small class="text-muted">{{  __tr('Note: The team member will be unassigned when this bot is triggered.') }}</small>
                            </div>

                            <div x-show="assignUserType == 'random'">
                                <small class="text-muted">{{  __tr("Note: Random team member will be assigned, It won't affect contact to whom team member already assigned") }}</small>
                            </div>
                            <div x-show="assignUserType !== '' && !isNaN(Number(assignUserType))">
                                <small class="text-muted">{{  __tr("Existing team member will overwrite.") }}</small>
                            </div>
                        </fieldset>
                        <!-- /Assign Team Member -->

                        <!-- Assign Label -->
                        <fieldset>
                            <legend><i class="fas fa-tag text-success mr-1"></i> {{  __tr('Assign Labels/Tags') }}</legend>
                            <x-lw.input-field :label="__tr('Labels/Tags')" type="selectize" data-lw-plugin="lwSelectize" id="lwAssignLabelsEditField" data-form-group-class="" name="bot_actions[contact_labels][]" multiple data-selected="[<%- __tData.__data?.bot_actions?.contact_labels %>]">
                                <x-slot name="selectOptions">
                                <option value="">{{ __tr('Select Labels') }}</option>
                                    @foreach($allLabels as $label)
                                        <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>                        
                        </fieldset>
                        <!-- /Assign Label -->

                        <!-- Unassign Label -->
                        <fieldset>
                            <legend><i class="fas fa-tag-slash text-success mr-1"></i> {{  __tr('Unassign Labels/Tags') }}</legend>
                            <x-lw.input-field :label="__tr('Labels/Tags')" type="selectize" data-lw-plugin="lwSelectize" id="lwUnassignLabelsEditField" data-form-group-class="" name="bot_actions[unassign_contact_labels][]" multiple data-selected="[<%- __tData.__data?.bot_actions?.unassign_contact_labels %>]">
                                <x-slot name="selectOptions">
                                <option value="">{{ __tr('Select Labels') }}</option>
                                    @foreach($allLabels as $label)
                                        <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                    @endforeach
                                </x-slot>
                            </x-lw.input-field>                        
                        </fieldset>
                        <!-- /Unassign Label -->

                        <!-- Promotional -->
                        <fieldset>
                            <legend><i class="fas fa-bullhorn text-success mr-1"></i> {{  __tr('Promotional') }}</legend>
                            <select id="lwPromotional" class="form-control" placeholder="<?= __tr('Promotional') ?>" name="bot_actions[promotional]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.promotional') as $promotionalKey => $promotionalItem)
                                    <option <%= __tData.__data?.bot_actions?.promotional == "{{ $promotionalKey }}" ? 'selected' : '' %> value="<?= $promotionalKey ?>"><?= $promotionalItem['title'] ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("Defines whether this bot reply is promotional.") }}</small>
                        </fieldset>
                        <!-- /Promotional -->

                        <!-- ai_bot -->
                        <!-- Drip Campaign Subscription -->
                        @if(class_exists('Addons\WhatsJetDripCampaignAddon\Models\DripCampaign'))
                        @php 
                            $dripCampaigns = \Addons\WhatsJetDripCampaignAddon\Models\DripCampaign::where('vendors__id', getVendorId())->where('status', 1)->get(); 
                        @endphp
                        <fieldset class="lw-drip-fieldset form-group border p-3 rounded mb-3">
                            <legend class="w-auto px-2 font-weight-bold" style="font-size: 1.05rem;"><i class="fas fa-water text-success mr-1"></i> {{  __tr('Inscription à la campagne de relance') }}</legend>
                            @if($dripCampaigns->count())
                            <select id="lwDripCampaign" class="form-control" name="addon_drip_campaigns__id">
                                <option value=""><?= __tr('No Action (Do not subscribe)') ?></option>
                                @foreach ($dripCampaigns as $dripCampaign)
                                    <option <%= __tData.addon_drip_campaigns__id == "{{ $dripCampaign->_id }}" ? 'selected' : '' %> value="<?= $dripCampaign->_id ?>"><?= $dripCampaign->title ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("When this bot reply is triggered, the contact will be automatically subscribed to the selected drip campaign.") }}</small>
                            @else
                            <div class="alert alert-warning mb-0 py-2">
                                <i class="fas fa-exclamation-circle mr-1"></i> {{ __tr("Aucune campagne Drip active trouvée. Vous devez d'abord créer une campagne Drip pour pouvoir y inscrire vos contacts.") }}
                                <a href="{{ route('addon.WhatsJetDripCampaignAddon.index') }}" target="_blank" class="btn btn-sm btn-outline-primary ml-2"><i class="fas fa-plus mr-1"></i> {{ __tr("Créer une campagne Drip") }}</a>
                            </div>
                            @endif
                        </fieldset>
                        @endif
                        
                        <fieldset>
                            <legend><i class="fas fa-robot text-success mr-1"></i> {{  __tr('AI Bot') }}</legend>
                            <select id="lwAiBot" class="form-control" placeholder="<?= __tr('AI Bot') ?>" name="bot_actions[ai_bot]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.ai_bot') as $aiBotKey => $aiBot)
                                    <option <%= __tData.__data?.bot_actions?.ai_bot == "{{ $aiBotKey }}" ? 'selected' : '' %> value="<?= $aiBotKey ?>"><?= $aiBot['title'] ?></option>
                                @endforeach
                            </select>
                        </fieldset>
                        <!-- /ai_bot -->

                        <!-- Reply Bot -->
                        <fieldset>
                            <legend><i class="fas fa-comment-dots text-success mr-1"></i> {{  __tr('Reply Bot') }}</legend>
                            <select id="lwReplyBot" class="form-control" placeholder="<?= __tr('Reply Bot') ?>" name="bot_actions[reply_bot]">
                                <option value="no_action"><?= __tr('No Action') ?></option>
                                @foreach (configItem('bot_actions.reply_bot') as $replyBotKey => $replyBot)
                                    <option <%= __tData.__data?.bot_actions?.reply_bot == "{{ $replyBotKey }}" ? 'selected' : '' %> value="<?= $replyBotKey ?>"><?= $replyBot['title'] ?></option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __tr("Allows triggering or pausing other automated bot replies.") }}</small>
                        </fieldset>
                        <!-- /Reply Bot -->
                    </div>
                </fieldset>
                
                <!-- /Bot Actions -->

                @if (!isset($botFlowUid) or !$botFlowUid)
                <div class="form-group pt-3">
                    <input type="checkbox" id="lwEditBotStatus" <%- (!__tData.status || (__tData.status == 2)) ? '' : 'checked' %> data-lw-plugin="lwSwitchery" value="1" name="status">
                    <label for="lwEditBotStatus">{{  __tr('Status') }}</label>
                </div>
                @endif
                @endif
            </div>
            @if($isNotNonTemplateCampaign)
                <!-- /Reply_Trigger -->
                <div class="my-4">
                    <x-lw.checkbox id="lwEditValidateBotReply" :offValue="0" name="validate_bot_reply" value="1" data-lw-plugin="lwSwitchery" data-size="small" :label="$isNotNonTemplateCampaign ? __tr('Validate Bot Reply by Sending Test Message') : __tr('Validate Preset by Sending Test Message')" />
                </div>
                @endif
                    </script>
                @if(!$isNotNonTemplateCampaign)
                <input type="hidden" name="trigger_type" value="NT_CAMPAIGN_MESSAGE">
                @endif
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
    </x-lw.form>
    <!--/  Edit Bot Reply Form -->
</x-lw.modal>
<!--/ Edit Bot Reply Modal -->

@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>
@endpush