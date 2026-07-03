<div class="row">
    @php
    $vendorId = getVendorId();
    $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
    $planCredits = $vendor->plan_ai_credits ?? 0;
    $extraCredits = $vendor->extra_ai_credits ?? 0;
    $totalCredits = $planCredits + $extraCredits;
    // check the feature limit
    $vendorPlanDetails = vendorPlanDetails('ai_chat_bot', 1, $vendorId);
    $selectedOtherBotsForTimingRestrictions = getVendorSettings('enable_selected_other_bot_timing_restrictions') ?: [];
    @endphp
    <div class="col-md-8" x-cloak>
        <h1>
            <?= __tr('Bots Settings') ?>
        </h1>
        <!-- Page Heading -->
        <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false,enable_bot_timing_restrictions:'{{ getVendorSettings('enable_bot_timing_restrictions') }}'}" x-cloak>
            <legend @click="panelOpened = !panelOpened" >{!! __tr('Bot Timing Settings') !!} <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small> </legend>
            <form x-show="panelOpened" id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="bot_timing_settings_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                <input type="hidden" name="pageType" value="bot_timing_settings">
                <!-- set hidden input field with form type -->
                <input type="hidden" name="form_type" value="bot_timing_settings_form" />
                <div class="col">
                    <x-lw.checkbox id="enableBotTimingRestrictions" :offValue="0" @click="(enable_bot_timing_restrictions = !enable_bot_timing_restrictions)" name="enable_bot_timing_restrictions" :checked="getVendorSettings('enable_bot_timing_restrictions')" data-lw-plugin="lwSwitchery" :label="__tr('Enable Bot Timing Restrictions')" />
                </div>
             <div x-show="enable_bot_timing_restrictions">
                <div class="col-12 row">
                    <div class="col-md-4 col-lg-3">
                        <x-lw.input-field type="time" id="lwBotTimingStart" data-form-group-class=""
                        :label="__tr('Daily Start Time')" value="{{ getVendorSettings('bot_start_timing') }}" name="bot_start_timing" />
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <x-lw.input-field type="time" id="lwBotTimingEnd" data-form-group-class=""
                        :label="__tr('Daily End Time')" value="{{ getVendorSettings('bot_end_timing') }}" name="bot_end_timing" />
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <x-lw.input-field  type="selectize" data-form-group-class="" name="bot_timing_timezone" :label="__tr('Select your Timezone')" data-selected="{{ (getVendorSettings('bot_timing_timezone') != 'UTC') ? getVendorSettings('bot_timing_timezone') : getVendorSettings('timezone') }}">
                            <x-slot name="selectOptions">
                                @foreach (getTimezonesArray() as $timezone)
                                    <option value="{{ $timezone['value'] }}">{{ $timezone['text'] }}</option>
                                @endforeach
                            </x-slot>
                        </x-lw.input-field>
                    </div>
               </div>
               <div class="col-12 row mt-4">
                <div class="col-12">
                    <div class="mb-3 alert alert-warning">{{  __tr('Time restrictions will only applicable to following enabled bots types only, following disabled bots types would work normally as avaialble') }}</div>
                </div>
                <div class="col-12 mt-3">
                    <h2>
                        {{  __tr('Enable bot timing restrictions for') }}
                        <hr>
                    </h2>
                </div>
                @if (isAiBotAvailable())
                <div class="col-md-6 col-lg-3 mb-3">
                    <strong>
                        <x-lw.checkbox data-size="small" id="lwAiBot" :checked="getVendorSettings('enable_ai_bot_timing_restrictions')" name="enable_ai_bot_timing_restrictions" data-lw-plugin="lwSwitchery" :label="__tr('AI Bots')" />
                    </strong>
                </div>
                @endif
                    @foreach (configItem('bot_reply_trigger_types') as $replyBotTypeKey => $replyBotType)
                        <div class="col-md-6 col-lg-3 mb-3">
                            <x-lw.checkbox data-size="small" id="lw{{ $replyBotTypeKey }}Bot" :value="$replyBotTypeKey" :checked="array_key_exists($replyBotTypeKey, $selectedOtherBotsForTimingRestrictions)" name="enable_selected_other_bot_timing_restrictions[{{ $replyBotTypeKey }}]" data-lw-plugin="lwSwitchery" :label="$replyBotType['title']" />
                        </div>
                    @endforeach
               </div>
             </div>
            <hr>
            <div class="form-group m-3 pl-2">
                <!-- Update Button -->
                <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </button>
                <!-- /Update Button -->
            </div>
            </form>
        </fieldset>
        {{-- AI BOT General Settings --}}
        <fieldset class="lw-fieldset mb-3"  x-data="{panelOpened:false}" x-cloak>
            <legend @click="panelOpened = !panelOpened" >{!! __tr('AI Bot General Settings') !!}  <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small></legend>
            <form x-show="panelOpened" id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="ai_bot_setup_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                <input type="hidden" name="pageType" value="ai_bot_settings">
                <!-- set hidden input field with form type -->
                <input type="hidden" name="form_type" value="ai_bot_settings_form" />
            <div class="col">
                <x-lw.input-field placeholder="{{ __tr('Message on AI Bot Failed') }}" type="text" id="lwFlowiseFailedMessage" data-form-group-class="col-md-12 col-lg-8"
                :label="__tr('Message on AI Bot Failed')" value="{{ getVendorSettings('flowise_failed_message') }}" name="flowise_failed_message" :helpText="__tr('If for some reason AI Bot failed to respond this error message will be sent to contact WhatsApp, Leave blank if you do not want to send such a message.')" />
            </div>
            <div class="mt-4 col pl-4">
                <x-lw.checkbox id="enableFlowiseAiBotByDefaultForAllUsers" name="default_enable_flowise_ai_bot_for_users" :checked="getVendorSettings('default_enable_flowise_ai_bot_for_users')" data-lw-plugin="lwSwitchery" :label="__tr('Enable AI Chat Bot by default for All New Contacts')" />
                <div class="help-text text-muted ml-2">{{  __tr('It will enable for AI Chat bot for contacts created using incoming messages, import etc.') }}</div>
            </div>
            <hr>
            <div class="form-group m-3 pl-2">
                <!-- Update Button -->
                <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </button>
                <!-- /Update Button -->
            </div>
            </form>
        </fieldset>
        <h1 class="mt-5">
            <?= __tr('AI Bots Integrations') ?>
        </h1>
        
        <div class="row my-4">
            <div class="col-md-6 mb-4">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body">
                        <div class="font-weight-bold text-uppercase mb-1">
                            {{ __tr('AI Credits Balance') }}
                        </div>
                        <div class="h2 mb-0 font-weight-bold text-white">
                            {{ $totalCredits }}
                        </div>
                        <div class="mt-2 text-white-50 text-sm">
                            {{ __tr('Subscription Credits:') }} {{ $planCredits }}<br>
                            {{ __tr('Purchased Credits:') }} {{ $extraCredits }}
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('vendor.settings.read', ['pageType' => 'ai-credits-topup']) }}" class="btn btn-light btn-sm text-primary font-weight-bold">
                                <i class="fas fa-coins"></i> {{ __tr('Recharge Credits') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning my-4">
            {{  __tr('AI Chat bot only get triggered if manual chat bot did not respond and contact has enabled for AI Bot reply.') }}
        </div>
        <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak >
                <legend @click="panelOpened = !panelOpened">
                    <img width="150" src="{{ asset('imgs/openai-lockup.svg') }}" alt="{{ __tr('OpenAI') }}"> {!! __tr('Chat Bot Setup') !!} <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small></legend>
                <div x-cloak x-show="panelOpened">
                    <div>
                        <p>{{  __tr('Using OpenAI you can build your chat bot for your custom information so it can answer the questions of the customer based on the information you have provided.') }}</p>
                    </div>
                    <div x-data="{open_ai_bot_data_source_type:'{{ getVendorSettings('open_ai_bot_data_source_type') }}'}">
                        @if ($vendorPlanDetails['is_limit_available'])
                        <!-- whatsapp cloud api setup form -->
                        <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form" name="ai_bot_setup_page" method="post" action="<?= route('vendor.settings.write.update') ?>">
                            <input type="hidden" name="pageType" value="open_ai_bot_setup">
                            <!-- set hidden input field with form type -->
                            <input type="hidden" name="form_type" value="ai_bot_setup_page" />
                            <x-lw.checkbox id="enableOpenAiBot" name="enable_open_ai_bot" :checked="getVendorSettings('enable_open_ai_bot')" data-lw-plugin="lwSwitchery" :label="__tr('Enable OpenAI Chat Bot')" />
                            <div class="alert alert-warning my-4">
                                {{  __tr('Note - Enabling it will send chat history to OpenAI, it may increase the OpenAI API cost.') }}
                            </div>
                            <x-lw.checkbox id="useExistingChatHistory" name="use_existing_chat_history" :checked="getVendorSettings('use_existing_chat_history')" data-lw-plugin="lwSwitchery" :label="__tr('Use existing chat history as context')" />                            
                            <x-lw.input-field placeholder="{{ __tr('Your AI Bot Name') }}"
                            type="text" id="lwOpenAIBotName" data-form-group-class="col-md-12 col-lg-8"
                            :label="__tr('Your AI Bot Name')" name="open_ai_bot_name" value="{{ getVendorSettings('open_ai_bot_name') }}" />
                            <input type="hidden" name="open_ai_bot_data_source_type" value="text" />
                            <input type="hidden" name="open_ai_max_token" value="1000" />
                            <fieldset>
                                <legend>{{  __tr('Bot Training Data') }}</legend>
                                
                                <div class="col my-4 mb-sm-0">
                                    <label for="lwTrainingTextData">{{  __tr('Information about your business') }}</label>
                                    <textarea rows="10" id="lwTrainingTextData" class="lw-form-field form-control" placeholder="{{ __tr('Type all the information the bot should know to answer customer questions...') }}" name="open_ai_input_training_data">{!! getVendorSettings('open_ai_input_training_data') !!}</textarea>
                                    <div class="help-text my-4">{{  __tr('The AI will use this text to answer questions.') }}</div>
                                </div>
                            </fieldset>
                            <hr>
                        <div class="form-group m-3">
                            <!-- Update Button -->
                            <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">
                                <?= __tr('Save') ?>
                            </button>
                            <!-- /Update Button -->
                        </div>
                        </form>
                        <!-- / whatsapp cloud api setup form -->
                        @else
                            <div class="alert alert-danger">
                                {{  __tr('This Feature is not available in your plan, please upgrade your subscription plan.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </fieldset>
    {{-- flowise --}}
        <div class="col-md-8" x-cloak>
        <!-- Page Heading -->
        <fieldset x-data="{panelOpened:false}" x-cloak>
            <legend @click="panelOpened = !panelOpened"><img class="p-2 mr-3" style="background-color: black" width="150" src="{{ asset('imgs/flowise-ai-logo.png') }}" alt="{{ __tr('FlowiseAI') }}"><?= __tr('ChatBot Setup') ?> <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small></legend>
            <div x-show="panelOpened">
                <p>{{  __tr('FlowiseAI is a platform designed to simplify the creation and management of chatbots by leveraging OpenAI\'s powerful AI models, including GPT (Generative Pre-trained Transformer). It provides users with tools to design, build, and deploy AI-powered chatbots tailored to a wide range of applications, from customer service and support to personalized interactions and engagement. FlowiseAI aims to make the development of intelligent chatbots accessible to businesses and developers of all sizes, emphasizing ease of use, scalability, and integration capabilities. By utilizing FlowiseAI, organizations can enhance their customer experience, automate responses to frequently asked questions, and offer real-time assistance without the need for extensive coding knowledge.') }}</p>
            <p>{{  __tr('You can learn more about flowiseAI from links given below') }}</p>
            <p>
                <a class="btn btn-light" href="https://flowiseai.com/" target="_blank">{{  __tr('Official Website') }}</a>
                <a class="btn btn-danger" href="https://www.youtube.com/watch?v=tD6fwQyUIJE&list=PL4HikwTaYE0HDOuXMm5sU6DH6_ZrHBLSJ" target="_blank"><i class="fab fa-youtube"></i> {{  __tr('Video Tutorials') }}</a>
            </p>
            <p>
                {{  __tr('Whatever the bot you create using FlowiseAI you need to grab the url from CURL option, and needs to place it under following url input field') }}
                <div>
                    <img class="img-fluid" src="{{ asset('imgs/flowise-ai-curl-url.png') }}" alt="{{ __tr('Get FlowiseAI URL') }}">
                </div>
            </p>
            <div>
                @if ($vendorPlanDetails['is_limit_available'])
                <!-- whatsapp cloud api setup form -->
                <form id="lwWhatsAppFacebookAppForm" class="lw-ajax-form lw-form"
                    name="ai_bot_setup_page" method="post"
                    action="<?= route('vendor.settings.write.update') ?>" x-data="{lwFlowiseUrlExists:{{ getVendorSettings('flowise_url') ? 1 : 0 }}}">
                    <input type="hidden" name="pageType" value="flowise_ai_bot_setup">
                    <!-- set hidden input field with form type -->
                    <input type="hidden" name="form_type" value="ai_bot_setup_page" />

                    <x-lw.checkbox id="enableFlowiseAiBot" name="enable_flowise_ai_bot" :checked="getVendorSettings('enable_flowise_ai_bot')" data-lw-plugin="lwSwitchery" :label="__tr('Enable FlowiseAI Chat Bot')" />
                    <div class="form-group" x-cloak x-show="lwFlowiseUrlExists">
                        <div class="btn-group">
                            <button type="button" disabled="true" class="btn btn-success lw-btn">
                                {{ __tr('FlowiseAI Settings are exist') }}
                            </button>
                            <button type="button" @click="lwFlowiseUrlExists = !lwFlowiseUrlExists"
                                class="btn btn-light lw-btn">{{ __tr('Update') }}</button>
                        </div>
                    </div>
                    
                <div x-show="!lwFlowiseUrlExists">
                    {{-- flowise ai chat url --}}
                    <x-lw.input-field placeholder="{{ __tr('Your Flowise Bot URL') }}"
                        type="text" id="lwFlowiseAiUrl" data-form-group-class="col-md-12 col-lg-8"
                        :label="__tr('Your Flowise Bot URL')" name="flowise_url" :helpText="__tr('You need to get this url from the your FlowiseAi Chat CURL tab.')" />
                    {{-- flowise ai chat access token if required --}}
                    <x-lw.input-field placeholder="{{ __tr('Authorization Bearer Token (optional)') }}"
                    type="text" id="lwFlowiseAiUrl" data-form-group-class="col-md-12 col-lg-8"
                    :label="__tr('Authorization Bearer Token (optional)')" name="flowise_access_token" :helpText="__tr('If you have added authorization using bearer token, you need to add it here.')" />
                   
                </div>
                <hr>
                <div class="form-group m-3">
                    <!-- Update Button -->
                    <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">
                        <?= __tr('Save') ?>
                    </button>
                    <!-- /Update Button -->
                </div>
                </form>
                <!-- / whatsapp cloud api setup form -->
                @else
                    <div class="alert alert-danger">
                        {{  __tr('This Feature is not available in your plan, please upgrade your subscription plan.') }}
                    </div>
                @endif
            </div>
            </div>
            </fieldset>
    </div>
</div>