@php
/**
* Component : Subscription
* Controller : ManualSubscriptionController
* File : manual_subscription.list.blade.php
----------------------------------------------------------------------------- */
@endphp
@extends('layouts.app', ['title' => __tr('Manual/Prepaid Subscriptions')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Manual/Prepaid Subscriptions'),
'description' => '',
'class' => 'col-lg-7'
])
<?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
<div class="container-fluid">
    <div class="row">
                <!-- Edit Manual Subscription Modal -->
                <x-lw.modal id="lwEditManualSubscription" :header="__tr('Update Subscription')" :hasForm="true">
                    <!--  Edit Manual Subscription Form -->
                    <x-lw.form id="lwEditManualSubscriptionForm"
                        :action="route('central.subscription.manual_subscription.write.update')"
                        :data-callback-params="['modalId' => '#lwEditManualSubscription', 'datatableId' => '#lwManualSubscriptionList']"
                        data-callback="appFuncs.modelSuccessCallback">
                        <!-- form body -->
                        <div id="lwEditManualSubscriptionBody" class="lw-form-modal-body"></div>
                        <script type="text/template" id="lwEditManualSubscriptionBody-template">
                            @if ($isExtendedLicence)
                            <fieldset>
                                <legend>{{  __tr('Provided Payment Details') }}</legend>
                                <dl>
                                    <dt>{{  __tr('Payment Method') }}</dt>
                                    <dd><%- __tData.__data?.manual_txn_details?.selected_payment_method %></dd>
                                    <dt>{{  __tr('Transaction Reference') }}</dt>
                                    <dd><%- __tData.__data?.manual_txn_details?.txn_reference %></dd>
                                    <dt>{{  __tr('Transaction Date') }}</dt>
                                    <dd><%- __tData.transactionDate %> </dd>
                                </dl>
                            </fieldset>
                            <input type="hidden" name="manualSubscriptionIdOrUid" value="<%- __tData._uid %>" />
                                <!-- form fields -->
                        <!-- Ends_At -->
                   <x-lw.input-field type="number" min="0" id="lwChargesField" data-form-group-class="" :label="__tr('Charges')" value="<%- __tData.charges %>" name="charges"  required="true" />
                   <x-lw.input-field type="date" id="lwEndsAtEditField" data-form-group-class="" :label="__tr('Expiry At')" value="<%- __tData.ends_at %>" name="ends_at"  required="true" />
                    <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSubscriptionStatus"
                data-form-group-class="" data-selected="<%- __tData.status %>" :label="__tr('Status')" name="status"
                required="true">
                <x-slot name="selectOptions">
                    <option value="">{{  __tr('Select Status') }}</option>
                    @foreach (configItem('subscription_status') as $subscriptionStatusKey => $subscriptionStatus)
                    <option value="{{ $subscriptionStatusKey }}">{{ $subscriptionStatus }}</option>
                    @endforeach
                </x-slot>
            </x-lw.input-field>
                    <div class="form-group">
                        <label for="lwEditRemarks">{{  __tr('Remarks if any') }}</label>
                        <textarea class="form-control" name="remarks" id="lwEditRemarks" rows="2"><%- __tData.remarks %></textarea>
                    </div>
                        <!-- /Ends_At -->
                        <fieldset class="lw-fieldset mb-3 mt-4">
                            <legend class="lw-fieldset-legend">{{  __tr('Custom Plan Limits for this Vendor') }}</legend>
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
                            <legend class="lw-fieldset-legend">{{  __tr('Custom Manual Pricing for this Vendor') }}</legend>
                            <div class="alert alert-info">
                                {{ __tr('Set a custom price for this client for future renewals.') }}
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
                        @else
                        <div class="alert alert-danger">
                            {{  __tr('Extended licence required to enable manage subscription') }}
                        </div>
                        @endif
                             </script>
                        <!-- form footer -->
                        <div class="modal-footer">
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                        </div>
                    </x-lw.form>
                    <!--/  Edit Manual Subscription Form -->
                </x-lw.modal>
                <!--/ Edit Manual Subscription Modal -->
        <div class="col-xl-12">
            <x-lw.datatable data-page-length="100" id="lwManualSubscriptionList"
                :url="route('central.subscription.manual_subscription.read.list')">
                <th data-template="#manualSubscriptionVendorColumnTemplate" data-name="null">{{ __tr('Vendor') }}</th>
                <th data-orderable="true" data-name="plan_id">{{ __tr('Plan') }}</th>
                <th data-order-by="true" data-order-type="desc" data-orderable="true" data-name="created_at">{{ __tr('Created At') }}</th>
                <th data-orderable="true" data-name="ends_at">{{ __tr('Expiry At') }}</th>
                <th data-orderable="true" data-name="charges">{{ __tr('Plan Charges') }}</th>
                <th data-orderable="true" data-name="charges_frequency">{{ __tr('Frequency') }}</th>
                <th data-template="#manualSubscriptionStatusColumnTemplate" data-name="null">{{ __tr('Status') }}</th>
                <th data-template="#manualSubscriptionActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>

            <script type="text/template" id="manualSubscriptionStatusColumnTemplate">
                <% if(__tData.status == 'Pending') { %>
                    <span class="badge badge-warning">{{  __tr('Pending') }}</span>
                <% } else if(__tData.status == 'Active') { %>
                    <span class="badge badge-success">{{  __tr('Active') }}</span>
                <% }  else { %>
                    <%- __tData.status %>
                <% } %>
                <% if(__tData.options.is_expired) { %>
                    <span class="badge badge-danger">{{  __tr('Expired') }}</span>
                <% } %>
            </script>
                    <!-- action template -->
        <script type="text/template" id="manualSubscriptionActionColumnTemplate">
            <a class="btn btn-primary btn-sm" href ="<%= __Utils.apiURL("{{ route('central.vendor.details',['vendorIdOrUid'=>'vendorIdOrUid'])}}", {'vendorIdOrUid':__tData.vendor_uid}) %>"> {{  __tr('Subscription') }} </a>
            <% if(!__tData.is_auto_recurring) { %>
                {{-- update expiry --}}
                <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditManualSubscriptionBody" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.read.update.data', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditManualSubscription"><i class="fa fa-edit"></i> {{  __tr('Update') }}</a>
                <!--  Delete Action -->
                <a data-method="post" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.write.delete', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteManualSubscription-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwManualSubscriptionList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
            <% } %>
        </script>
        <!-- /action template -->
        <!-- Manual Subscription delete template -->
        <script type="text/template" id="lwDeleteManualSubscription-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to delete this Subscription?') }}</p>
    </script>
        <!-- /Manual Subscription delete template -->
            <script type="text/template" id="manualSubscriptionVendorColumnTemplate">
                <a  href ="<%= __Utils.apiURL("{{ route('vendor.dashboard',['vendorIdOrUid'=>'vendorIdOrUid'])}}", {'vendorIdOrUid':__tData.vendor_uid}) %>"> <%-__tData.vendor_title %> </a>
                <% if(__tData.status == 'pending') { %>
                    <span class="badge badge-danger">{{  __tr('Action Required') }}</span>
                <% } %>
            </script>
        </div>
    </div>
</div>
@endsection()