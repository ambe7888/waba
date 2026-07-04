<!-- CUSTOM PLAN VENDOR MODAL -->
<x-lw.modal id="lwCustomPlanModal" :header="__tr('Customize Plan & Pricing')" :hasForm="true">
    <x-lw.form id="lwCustomPlanForm" :action="route('vendor.write.custom_plan.data')" data-callback="appFuncs.modelSuccessCallback" data-callback-params="{{ json_encode(['modalId' => '#lwCustomPlanModal', 'datatableId' => '#lwManageVendorsTable']) }}">
        <div id="lwCustomPlanBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwCustomPlanBody-template">
            <input type="hidden" name="vendorIdOrUid" value="<%- __tData._uid %>" />
            
            <fieldset class="lw-fieldset mb-3">
                <legend class="lw-fieldset-legend">{{  __tr('Custom Plan Limits') }}</legend>
                <div class="alert alert-info">
                    {{ __tr('Leave fields empty to use the default limits from the assigned plan.') }}
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="ai_credits">{{ __tr('AI Credits') }}</label>
                        <input type="number" class="form-control" name="ai_credits" id="ai_credits" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.ai_credits === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.ai_credits !== '' ? 'Default: ' + __tData.plan_defaults.ai_credits : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.ai_credits : '' %>">
                    </div>

                    <div class="col-md-6 form-group mb-3">
                        <label for="contacts">{{ __tr('Contacts Limit') }}</label>
                        <input type="number" class="form-control" name="contacts" id="contacts" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.contacts === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.contacts !== '' ? 'Default: ' + __tData.plan_defaults.contacts : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.contacts : '' %>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="campaigns">{{ __tr('Campaigns Limit') }}</label>
                        <input type="number" class="form-control" name="campaigns" id="campaigns" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.campaigns === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.campaigns !== '' ? 'Default: ' + __tData.plan_defaults.campaigns : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.campaigns : '' %>">
                    </div>

                    <div class="col-md-6 form-group mb-3">
                        <label for="drip_campaigns">{{ __tr('Drip Campaigns Limit') }}</label>
                        <input type="number" class="form-control" name="drip_campaigns" id="drip_campaigns" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.drip_campaigns === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.drip_campaigns !== '' ? 'Default: ' + __tData.plan_defaults.drip_campaigns : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.drip_campaigns : '' %>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="bot_replies">{{ __tr('Bot Replies Limit') }}</label>
                        <input type="number" class="form-control" name="bot_replies" id="bot_replies" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.bot_replies === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.bot_replies !== '' ? 'Default: ' + __tData.plan_defaults.bot_replies : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.bot_replies : '' %>">
                    </div>

                    <div class="col-md-6 form-group mb-3">
                        <label for="bot_flows">{{ __tr('Bot Flows Limit') }}</label>
                        <input type="number" class="form-control" name="bot_flows" id="bot_flows" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.bot_flows === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.bot_flows !== '' ? 'Default: ' + __tData.plan_defaults.bot_flows : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.bot_flows : '' %>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="contact_custom_fields">{{ __tr('Contact Custom Fields Limit') }}</label>
                        <input type="number" class="form-control" name="contact_custom_fields" id="contact_custom_fields" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.contact_custom_fields === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.contact_custom_fields !== '' ? 'Default: ' + __tData.plan_defaults.contact_custom_fields : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.contact_custom_fields : '' %>">
                    </div>

                    <div class="col-md-6 form-group mb-3">
                        <label for="system_users">{{ __tr('Team Members Limit') }}</label>
                        <input type="number" class="form-control" name="system_users" id="system_users" placeholder="<%- __tData.plan_defaults && __tData.plan_defaults.system_users === -1 ? 'Unlimited' : (__tData.plan_defaults && __tData.plan_defaults.system_users !== '' ? 'Default: ' + __tData.plan_defaults.system_users : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.system_users : '' %>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="ai_chat_bot">{{ __tr('AI Chat Bot') }}</label>
                        <select name="ai_chat_bot" id="ai_chat_bot" class="form-control">
                            <option value="" <%= __tData.custom_limits && (__tData.custom_limits.ai_chat_bot === '' || __tData.custom_limits.ai_chat_bot === undefined) ? 'selected' : '' %>>Default (<%- __tData.plan_defaults && __tData.plan_defaults.ai_chat_bot === 1 ? 'Enabled' : 'Disabled' %>)</option>
                            <option value="1" <%= __tData.custom_limits && String(__tData.custom_limits.ai_chat_bot) === '1' ? 'selected' : '' %>>Enable</option>
                            <option value="0" <%= __tData.custom_limits && String(__tData.custom_limits.ai_chat_bot) === '0' ? 'selected' : '' %>>Disable</option>
                        </select>
                    </div>

                    <div class="col-md-6 form-group mb-3">
                        <label for="api_access">{{ __tr('API and Webhook Access') }}</label>
                        <select name="api_access" id="api_access" class="form-control">
                            <option value="" <%= __tData.custom_limits && (__tData.custom_limits.api_access === '' || __tData.custom_limits.api_access === undefined) ? 'selected' : '' %>>Default (<%- __tData.plan_defaults && __tData.plan_defaults.api_access === 1 ? 'Enabled' : 'Disabled' %>)</option>
                            <option value="1" <%= __tData.custom_limits && String(__tData.custom_limits.api_access) === '1' ? 'selected' : '' %>>Enable</option>
                            <option value="0" <%= __tData.custom_limits && String(__tData.custom_limits.api_access) === '0' ? 'selected' : '' %>>Disable</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="lw-fieldset mb-3">
                <legend class="lw-fieldset-legend">{{  __tr('Custom Manual Pricing') }}</legend>
                <div class="alert alert-info">
                    {{ __tr('Set a custom price for this client. Applies only to Manual/Offline Subscriptions.') }}
                </div>
                
                <div class="form-group mb-3">
                    <label for="custom_plan_charge">{{ __tr('Custom Charge Amount') }}</label>
                    <input type="number" step="0.01" class="form-control" name="custom_plan_charge" id="custom_plan_charge" placeholder="<%- __tData.plan_defaults.charge || '' %>" value="<%- __tData.custom_plan_charge ? __tData.custom_plan_charge : '' %>">
                </div>

                <div class="form-group mb-3">
                    <label for="custom_plan_frequency">{{ __tr('Charge Frequency') }}</label>
                    <select name="custom_plan_frequency" id="custom_plan_frequency" class="form-control">
                        <option value="">{{ __tr('Default') }}</option>
                        <option value="monthly" <%= __tData.custom_plan_frequency === 'monthly' ? 'selected' : '' %>>{{ __tr('Monthly') }}</option>
                        <option value="yearly" <%= __tData.custom_plan_frequency === 'yearly' ? 'selected' : '' %>>{{ __tr('Yearly') }}</option>
                    </select>
                </div>
            </fieldset>

        </script>
        <!-- FORM FOOTER -->
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">{{ __tr('Save Customizations') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </x-lw.form>
</x-lw.modal>
<!--/ CUSTOM PLAN VENDOR MODAL -->
