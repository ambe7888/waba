@extends('layouts.app', ['title' => __tr('Vendor Details')])
@php
$vendorIdOrUid = $vendorIdOrUid ?? getVendorUid();
if(!isset($vendorViewBySuperAdmin)) {
    $vendorViewBySuperAdmin = null;
}
@endphp
@section('content')
@if(hasCentralAccess())
@include('users.partials.header', [
'title' => __tr('__vendorTitle__ Details', [
    '__vendorTitle__' => $vendorInfo['title'] ?? getVendorSettings('title')
]),
'description' => '',
// 'class' => 'col-lg-7'
])
@else
@include('users.partials.header', [
'title' => __tr('Hi __userFullName__,', [
    '__userFullName__' => getUserAuthInfo('profile.first_name')
]),
'description' => '',
// 'class' => 'col-lg-7'
])
@endif
<?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
<div class="container-fluid">
    @if(hasCentralAccess())
    <div class="col-xl-12 p-0">
        <!-- breadcrumbs -->
        <nav aria-label="breadcrumb" class="lw-breadcrumb-container">
            <ol class="breadcrumb bg-transparent text-light p-0 m-0">
                <li class=" breadcrumb-item mb-3">
                    <a class="text-decoration-none" href="{{ route('central.vendors') }}">{{ __tr('Manage Vendors') }}</a>

                </li>
                <li class="text-light breadcrumb-item" aria-current="page">{{ __tr('Details') }}</li>
            </ol>
        </nav>
        <!-- /breadcrumbs -->
    </div>
    @endif
</div>

@if(hasVendorAccess() or $vendorViewBySuperAdmin )
@php
$planDetails = vendorPlanDetails(null, null, $vendorInfo['id']);
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-5">
            <fieldset>
                <legend>{{  __tr('Basic Information') }}</legend>
                <dl>
                    <dt>{{  __tr('Vendor Name') }}</dt>
                    <dd>{{ $vendorInfo['title'] }}</dd>
                </dl>
            </fieldset>
            <fieldset class="mb-4">
                {{-- @dd($planDetails) --}}
                <legend>{{ __tr('Current Subscribed Plan') }}</legend>
                @if ($planDetails->hasActivePlan())
                <h2 class="text-primary">{{ $planDetails->planTitle() }}</h2>
                @php
                $planStructure = $planDetails->plan_id ? getPaidPlans($planDetails->plan_id) : getFreePlan();
                $planCharges = $planStructure['charges'][$planDetails->frequency] ?? null;
                @endphp
                <?php if (!__isEmpty(data_get($planStructure, 'features'))) { ?>
                @foreach ($planStructure['features'] as $featureKey => $featureValue)
                    @php
                         $structureFeatureValue = $featureValue;
                        $featureValue = $featureValue;
                    @endphp
                    <div class="my-2">
                     @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                         @if (isset($featureValue['limit']) and $featureValue['limit'])
                         <i class="fa fa-check mr-2 text-success"></i>
                         @else
                         <i class="fa fa-times mr-2 text-danger"></i>
                         @endif
                         {{ ($structureFeatureValue['description']) }}
                         @else
                        <i class="fa fa-check text-success mr-2"></i>
                        @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                            {{ __tr('Unlimited') }}
                        @elseif(isset($featureValue['limit']))
                            {{ $featureValue['limit'] }}
                        @endif
                        {{ ($structureFeatureValue['description']) }}
                        @if(isset($featureValue['limit_duration_title']))
                             {{ ($featureValue['limit_duration_title']) }}
                         @endif
                         @endif
                    </div>
                @endforeach
                <?php } ?>
                <hr>
                @if($planCharges)
                <h2 class="text-blue">
                    {{ $planCharges['title'] ?? '' }} {{ formatAmount($planCharges['charge'], true) }}
                </h2>
                <hr>
                @endif
                @else
                <div class="alert alert-warning">{{  __tr('Vendor does not have any active plan.') }}</div>
                @endif
                @if($planDetails->isAuto())
              <h4 class="text-warning">
                {{  __tr('Please note vendor is on the Auto Renewal Subscription Plan, First you need to cancel it to manage manual subscription.') }}
              </h4>
              <a data-show-processing="true" class="lw-ajax-link-action btn btn-danger" data-method="post" href="{{ route('central.subscription.write.cancel', [
                'vendorUid' => $vendorIdOrUid
              ]) }}">
                {{ __tr('Cancel Auto Subscription and Discard Grace Period if any') }}
            </a>
             @else
             @if ($isExtendedLicence)
                <button type="button" class="lw-btn btn btn-primary" data-toggle="modal" data-target="#lwAddNewManualSubscription"> {{ __tr('Create New Subscription') }}</button>
                @else
                <div class="alert alert-danger">
                    {{  __tr('Extended licence required to enable manage subscription') }}
                </div>
                @endif
                @endif
            </fieldset>
                    <!-- New Subscription Modal -->
        <x-lw.modal id="lwAddNewManualSubscription" :header="__tr('Create New Subscription')" :hasForm="true">
            <!--  New Subscription Form -->
            <x-lw.form x-data="{calculated_ends_at:null}" id="lwAddNewManualSubscriptionForm"
                :action="route('central.subscription.manual_subscription.write.create')"
                :data-callback-params="['modalId' => '#lwAddNewManualSubscription', 'datatableId' => '#lwManualSubscriptionList']"
                data-callback="appFuncs.modelSuccessCallback">
                <!-- form body -->
                <div class="lw-form-modal-body">
                    @if ($isExtendedLicence)
                    <div class="alert alert-danger">
                        {{  __tr('It will cancelled all the existing active subscriptions and create new subscription') }}
                    </div>
                    <!-- form fields form fields -->
                    <input type="hidden" name="vendor_uid" value="{{ $vendorInfo['uid'] }}">
                    <!-- Plan_Id -->
                    <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwPlanIdField"
                        data-form-group-class="" data-selected=" " :label="__tr('Plan')" name="plan"
                        required="true">
                        <x-slot name="selectOptions">
                            <option value="">{{  __tr('Select Plan') }}</option>
                            @foreach (getPaidPlans() as $paidPlanKey => $paidPlan)
                            <optgroup label="{{ $paidPlan['title'] }} @if(!$paidPlan['enabled']) ({{ __tr('Disabled') }}) @endif">
                                @foreach ($paidPlan['charges'] as $planChargeKey => $planCharge)
                                 <option value="{{ $paidPlanKey }}___{{ $planChargeKey }}">{{ $paidPlan['title'] }} - {{ formatAmount($planCharge['charge'], true) }} {{ $planCharge['title'] }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                    <!-- /Plan_Id -->
                    <!-- Ends_At -->
                    <x-lw.input-field x-model="calculated_ends_at" type="date" id="lwEndsAtField" data-form-group-class="" :label="__tr('Expiry on')"
                        name="ends_at" required="true" />
                    <!-- /Ends_At -->
                    <div class="form-group">
                        <label for="lwRemarks">{{  __tr('Remarks if any') }}</label>
                        <textarea class="form-control" name="remarks" id="lwRemarks" rows="2"></textarea>
                    </div>
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
                @else
                <div class="alert alert-danger">
                    {{  __tr('Extended licence required to enable manage subscription') }}
                </div>
                @endif
            </x-lw.form>
            <!--/  New Subscription Form -->
        </x-lw.modal>
        <!--/ New Subscription Modal -->
        </div>
        <div class="col-xl-12">
            <h1>{{  __tr('Manual/Prepaid Subscription Log') }}</h1>
            <x-lw.datatable data-page-length="100" id="lwManualSubscriptionList" data-page-length="10"
                :url="route('central.subscription.manual_subscription.read.list', [
                    'vendorUid' => $vendorIdOrUid
                ])">
                <th data-orderable="true" data-name="plan_id">{{ __tr('Plan') }}</th>
                <th data-order-by="true" data-order-type="desc" data-orderable="true" data-name="created_at">{{ __tr('Created At') }}</th>
                <th data-orderable="true" data-name="ends_at">{{ __tr('Expiry On') }}</th>
                <th data-orderable="true" data-name="charges">{{ __tr('Plan Charges') }}</th>
                <th data-orderable="true" data-name="charges_frequency">{{ __tr('Frequency') }}</th>
                <th data-template="#manualSubscriptionStatusColumnTemplate" data-name="null">{{ __tr('Status') }}</th>
                <th data-template="#manualSubscriptionActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
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
                                <dd><%- __tData.transactionDate %></dd>
                            </dl>
                        </fieldset>
                        <input type="hidden" name="manualSubscriptionIdOrUid" value="<%- __tData._uid %>" />
                            <!-- form fields -->
                            <x-lw.input-field type="number" min="0" id="lwChargesEditField" data-form-group-class="" :label="__tr('Charges')" value="<%- __tData.charges %>" name="charges"  required="true" />
                    <!-- Ends_At -->
               <x-lw.input-field type="date" id="lwEndsAtEditField" data-form-group-class="" :label="__tr('Expiry On')" value="<%- __tData.ends_at %>" name="ends_at"  required="true"                 />
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
            <script type="text/template" id="manualSubscriptionActionColumnTemplate">
                <% if(!__tData.is_auto_recurring) { %>
                    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditManualSubscriptionBody" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.read.update.data', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditManualSubscription"><i class="fa fa-edit"></i> {{  __tr('Update') }}</a>
                    
                    <a data-method="post" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.write.delete', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteManualSubscription-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwManualSubscriptionList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
                <% } %>
            </script>
            <!-- Manual Subscription delete template -->
            <script type="text/template" id="lwDeleteManualSubscription-template">
                <h2>{{ __tr('Are You Sure!') }}</h2>
                <p>{{ __tr('You want to delete this Subscription?') }}</p>
            </script>
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
        </div>
    </div>

    {{-- Mes Factures & Abonnements (Bottom of Subscription Page) --}}
    @if(hasVendorAccess() && isset($userManualSubscriptions))
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header border-0 pt-4 pb-3" style="background: #ecfdf5; border-bottom: 2px solid #a7f3d0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 font-weight-bold" style="color: #065f46; font-size: 1.15rem;">
                                <i class="fas fa-file-invoice mr-2" style="color: #10b981;"></i>{{ __tr('Historique des Factures & Abonnements') }}
                            </h3>
                            <p class="text-muted small mb-0" style="color: #047857;">{{ __tr('Retrouvez l\'ensemble de vos règlements et téléchargez vos reçus officiels.') }}</p>
                        </div>
                        <span class="badge" style="background: #10b981; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                            {{ count($userManualSubscriptions) }} {{ __tr('Facture(s)') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0 bg-white">
                    @if($userManualSubscriptions->isEmpty())
                        <div class="text-center py-5">
                            <div style="font-size: 2.5rem; color: #a7f3d0; margin-bottom: 0.8rem;">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h5 class="font-weight-normal text-muted">{{ __tr('Aucune facture trouvée') }}</h5>
                            <p class="text-muted small mb-0">{{ __tr('Vos abonnements souscrits apparaîtront automatiquement ici.') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                                <thead>
                                    <tr style="background: #f0fdf4; border-bottom: 2px solid #d1fae5;">
                                        <th class="px-4 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Plan souscrit') }}</th>
                                        <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Montant') }}</th>
                                        <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Date début') }}</th>
                                        <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Date fin') }}</th>
                                        <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Statut') }}</th>
                                        <th class="px-4 py-3 text-uppercase text-right" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($userManualSubscriptions as $sub)
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td class="px-4 py-3 align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 flex-shrink-0"
                                                    style="width: 36px; height: 36px; background: {{ $sub['is_active'] ? '#ecfdf5' : '#f1f5f9' }}; color: {{ $sub['is_active'] ? '#10b981' : '#64748b' }};">
                                                    <i class="fas fa-id-card" style="font-size: 0.85rem;"></i>
                                                </div>
                                                <div>
                                                    <div style="color: #0f172a; font-weight: 600;">{{ $sub['plan_title'] }}</div>
                                                    <div class="text-muted" style="font-size: 0.78rem;">{{ $sub['freq_title'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-middle">
                                            <span style="color: #047857; font-weight: 700; font-size: 0.95rem;">{{ $sub['charges'] }}</span>
                                        </td>
                                        <td class="px-3 py-3 align-middle text-muted">{{ $sub['created_at'] }}</td>
                                        <td class="px-3 py-3 align-middle text-muted">
                                            @if($sub['is_expired'])
                                                <span class="text-danger font-weight-bold">{{ $sub['ends_at'] }}</span>
                                            @else
                                                {{ $sub['ends_at'] }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 align-middle">
                                            @if($sub['is_active'])
                                                <span class="badge" style="background: #d1fae5; color: #047857; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-check-circle mr-1"></i> {{ __tr('Actif') }}
                                                </span>
                                            @elseif($sub['is_expired'])
                                                <span class="badge" style="background: #fee2e2; color: #dc2626; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-times-circle mr-1"></i> {{ __tr('Expiré') }}
                                                </span>
                                            @else
                                                <span class="badge" style="background: #fef3c7; color: #d97706; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-clock mr-1"></i> {{ $sub['status'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-middle text-right">
                                            <a href="{{ route('vendor.subscription.invoice.print', ['subscriptionUid' => $sub['_uid']]) }}"
                                               target="_blank"
                                               class="btn btn-sm"
                                               style="background: #10b981; color: #ffffff; border-radius: 8px; font-size: 0.82rem; padding: 6px 14px; font-weight: 600; border: none; box-shadow: 0 2px 6px rgba(16,185,129,0.25);"
                                               title="{{ __tr('Télécharger / Imprimer la facture') }}">
                                                <i class="fas fa-file-invoice mr-1"></i> {{ __tr('Facture') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@push('appScripts')
<script>
    (function(window) {
    'use strict';
    $('#lwPlanIdField').on('lwSelectizeOnChange', function(event, value) {
        __DataRequest.post("{{ route('central.subscription.manual_subscription.read.selected_plan_details') }}", {
            'selected_plan' : value
        });
    });
    })(window);
</script>
@endpush
@endsection()
