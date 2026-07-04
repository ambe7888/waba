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
                
                <div class="form-group mb-3">
                    <label for="ai_credits">{{ __tr('AI Credits') }}</label>
                    <input type="number" class="form-control" name="ai_credits" id="ai_credits" placeholder="<%- __tData.plan_defaults.ai_credits === -1 ? 'Unlimited' : (__tData.plan_defaults.ai_credits !== '' ? 'Default: ' + __tData.plan_defaults.ai_credits : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.ai_credits : '' %>">
                </div>

                <div class="form-group mb-3">
                    <label for="contact_limit">{{ __tr('Contact Limit') }}</label>
                    <input type="number" class="form-control" name="contact_limit" id="contact_limit" placeholder="<%- __tData.plan_defaults.contact_limit === -1 ? 'Unlimited' : (__tData.plan_defaults.contact_limit !== '' ? 'Default: ' + __tData.plan_defaults.contact_limit : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.contact_limit : '' %>">
                </div>

                <div class="form-group mb-3">
                    <label for="campaign_limit">{{ __tr('Campaign Limit') }}</label>
                    <input type="number" class="form-control" name="campaign_limit" id="campaign_limit" placeholder="<%- __tData.plan_defaults.campaign_limit === -1 ? 'Unlimited' : (__tData.plan_defaults.campaign_limit !== '' ? 'Default: ' + __tData.plan_defaults.campaign_limit : '') %>" value="<%- __tData.custom_limits ? __tData.custom_limits.campaign_limit : '' %>">
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
