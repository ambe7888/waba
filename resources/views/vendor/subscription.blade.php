@extends('layouts.app', ['title' => __tr('My Subscription')])
@section('content')
    @include('users.partials.header', [
    'title' => __tr('My Subscription'),
    'description' => '',
    'class' => 'col-lg-7'
    ])
    <?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
   @if(isset($message))
    <div class="container">
        <div class="alert alert-danger">
            {{ $message }}
        </div>
    </div>
   @else
   @php
   $subscriptionPlans = getAppSettings('subscription_plans');
   $isManualSubscription = $currentSubscription?->plan_id ? true : false;
   $isCashierSubscription = $currentSubscription?->stripe_status ? true : false;
   $hasPlansForPurchase = false;
   $isRazorpaySubscription = ($currentSubscription?->is_auto_recurring == 1 and $currentSubscription?->gateway == 'razorpay') ? true : false;
   $isSubscriptionManuallyCancelled = data_get($currentSubscription, '__data.auto_recurring_data.is_manually_cancelled_subscription', false);
   $currentSubscriptionPlan = getVendorCurrentAutoCreatedSubscription(null, 'created');
   $subscriptionPlanStatus = data_get($currentSubscriptionPlan, 'status');
    
   foreach ($planStructure as $planKey => $plan) {
       $plan = $planDetails[$planKey];
       if (!$plan['enabled']) {
           continue;
       }
       $hasPlansForPurchase = true;
       break;
   }

   // Determine default initial selected plan
   $initialSelectedPlan = null;
   foreach ($planStructure as $planKey => $plan) {
       if (($planDetails[$planKey]['enabled'] ?? false)) {
           $firstCharge = array_key_first($planDetails[$planKey]['charges'] ?? ['monthly' => []]);
           $initialSelectedPlan = $plan['id'] . '___' . ($firstCharge ?? 'monthly');
           break;
       }
   }
   @endphp
   <div class="container-fluid pb-5">
       <div class="row">
           <div class="col-xl-12">
            @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-0" style="border-radius: 12px;">
                @foreach ($errors->all() as $error)
                    <div><i class="fa fa-exclamation-circle mr-2"></i>{{ $error }}</div>
                @endforeach
            </div>
            @endif
            @if(getAppSettings('enable_stripe') and !$isValidStripeKeys)
               <div class="alert alert-danger shadow-sm border-0" style="border-radius: 12px;">
                    {{  __tr('Stripe is not correctly configured, Invalid Keys. Please contact administrator') }}
               </div>
               @endif

               <!-- Status Banner & Current Plan -->
               <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                   <div class="card-body p-4">
                       @if (request('success') == true and request('message'))
                           <div class="alert alert-success border-0 shadow-sm" style="border-radius: 10px;">
                               <i class="fa fa-check-circle mr-2"></i>{{ request('message') }}
                           </div>
                       @elseif(request('message'))
                           <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 10px;">
                               <i class="fa fa-exclamation-triangle mr-2"></i>{{ request('message') }}
                           </div>
                       @endif
                       @if ($currentPlan and ($isManualSubscription or $subscriber->subscribed($currentPlan['id'])))
                           @if ($subscriber->subscription($currentPlan['id'])?->onTrial())
                               <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 10px;">
                                   {{  __tr('You are on trial until __trialEndsAt__', [
                                       '__trialEndsAt__' => formatDateTime($subscriber->trialEndsAt($currentPlan['id']))
                                   ]) }}
                               </div>
                           @endif
                           @if ($subscriber->subscription($currentPlan['id'])?->onGracePeriod())
                               <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 10px;">
                                   {{  __tr('Subscription has been cancelled and you are on the grace period till __endsAt__', [
                                       '__endsAt__' => formatDateTime($subscriber->subscription($currentPlan['id'])->ends_at)
                                   ]) }}
                               </div>
                           @endif
                           <div class="d-flex flex-wrap align-items-center justify-content-between">
                               <div>
                                   <span class="badge badge-subtle-success text-uppercase tracking-wider px-3 py-1 font-weight-bold" style="background: #ecfdf5; color: #047857; border-radius: 20px; font-size: 0.75rem;">
                                       <i class="fa fa-check-circle mr-1"></i> {{ __tr('Plan Actuel') }}
                                   </span>
                                   <h2 class="font-weight-bold text-dark mt-2 mb-1" style="font-size: 1.75rem;">{{ $planDetails[$currentPlan['id']]['title'] }}</h2>
                                   @if ($currentSubscription->ends_at ?? null)
                                       <p class="text-danger font-weight-bold mb-0" style="font-size: 0.95rem;">
                                           <i class="fa fa-calendar-alt mr-1"></i>
                                           @if($isRazorpaySubscription and !$isSubscriptionManuallyCancelled)
                                               @if ($subscriptionPlanStatus != 'created')
                                                   {{  __tr('Next Charge: __expiryDate__', ['__expiryDate__' => formatDate($currentSubscription->ends_at)]) }}
                                               @endif
                                           @else
                                               {{  __tr('Date d\'expiration : __expiryDate__', ['__expiryDate__' => formatDate($currentSubscription->ends_at)]) }}
                                           @endif
                                       </p>
                                   @endif
                               </div>
                               <div class="mt-3 mt-md-0">
                                   @if ($isCashierSubscription and !$subscriber->subscription($currentPlan['id'])?->canceled())
                                       <a data-show-processing="true" class="lw-ajax-link-action btn btn-outline-danger btn-sm px-3" style="border-radius: 8px;" href="{{ route('subscription.write.cancel') }}">
                                           {{ __tr('Cancel Subscription') }}
                                       </a>
                                   @endif
                               </div>
                           </div>
                       @else
                           @if ($freePlanDetails['enabled'])
                               <div>
                                   <span class="badge badge-secondary text-uppercase tracking-wider px-3 py-1 font-weight-bold" style="border-radius: 20px; font-size: 0.75rem;">
                                       {{ __tr('Plan Gratuit') }}
                                   </span>
                                   <h2 class="font-weight-bold text-dark mt-2 mb-1">{{ $freePlanDetails['title'] }}</h2>
                               </div>
                           @else
                               <div class="alert alert-warning border-0 shadow-sm mb-0" style="border-radius: 10px;">
                                   <i class="fa fa-info-circle mr-2"></i>{{ __tr('Aucun plan actif actuellement') }}
                               </div>
                           @endif
                       @endif

                       @if(getAppSettings('enable_stripe') and $isValidStripeKeys and $isCashierSubscription)
                           <div class="mt-3">
                               <a class="btn btn-light btn-sm font-weight-bold" href="{{ route('subscription.read.billing_portal') }}" style="border-radius: 8px;">
                                   <i class="fa fa-credit-card mr-1"></i> {{ __tr('Go to Billing Portal') }}
                               </a>
                               @if ($currentPlan and $isCashierSubscription and $subscriber->subscription($currentPlan['id']) and $subscriber->subscription($currentPlan['id'])->canceled() and $subscriber->subscription($currentPlan['id'])->onGracePeriod())
                                   <a data-show-processing="true" class="lw-ajax-link-action btn btn-success btn-sm font-weight-bold ml-2" style="border-radius: 8px;" href="{{ route('subscription.write.resume') }}">
                                       {{ __tr('Resume Subscription') }}
                                   </a>
                               @endif
                           </div>
                       @endif
                   </div>
               </div>

               <!-- Plan Selection & Checkout Card -->
               @if ($hasPlansForPurchase)
                   <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                       <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                           <h4 class="font-weight-bold text-dark mb-1">
                               {{ $currentPlan ? __tr('Modifier / Renouveler le plan') : __tr('Choisir un plan d\'abonnement') }}
                           </h4>
                           <p class="text-muted small mb-0">{{ __tr('Cliquez sur le pack de votre choix pour le sélectionner') }}</p>
                       </div>
                       <div class="card-body p-4" x-data="{selectedPlanFrequencyNew: '{{ $initialSelectedPlan }}'}">
                            <div class="row">
                               @foreach ($planStructure as $planKey => $plan)
                                   @php
                                       $planId = $plan['id'];
                                       $features = $plan['features'];
                                       $planData = $planDetails[$planKey];
                                       if (!$planData['enabled']) {
                                           continue;
                                       }
                                       $charges = $planData['charges'] ?? [];
                                       $firstChargeKey = array_key_first($charges);
                                       $defaultPlanVal = $planId . '___' . ($firstChargeKey ?? 'monthly');
                                   @endphp
                                   <div class="col-xl-4 col-md-6 col-12 mb-4">
                                       <div class="card h-100 position-relative transition-all"
                                            @click="selectedPlanFrequencyNew = '{{ $defaultPlanVal }}'"
                                            :style="selectedPlanFrequencyNew && selectedPlanFrequencyNew.startsWith('{{ $planId }}___') 
                                                ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4; cursor: pointer; border-radius: 16px; transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15);' 
                                                : 'border: 1px solid #e2e8f0; background-color: #ffffff; cursor: pointer; border-radius: 16px; transition: all 0.2s ease;'">
                                           
                                           <!-- Selection Badge -->
                                           <div x-show="selectedPlanFrequencyNew && selectedPlanFrequencyNew.startsWith('{{ $planId }}___')" 
                                                class="position-absolute" style="top: -12px; right: 20px; z-index: 10;">
                                               <span class="badge badge-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 20px; font-size: 0.78rem; background-color: #10b981;">
                                                   <i class="fa fa-check-circle mr-1"></i> {{ __tr('Sélectionné') }}
                                               </span>
                                           </div>

                                           <div class="card-body p-4 d-flex flex-column justify-content-between">
                                               <div>
                                                   <div class="d-flex align-items-center justify-content-between mb-3">
                                                       <span class="badge px-3 py-1 font-weight-bold text-uppercase" style="background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 0.8rem;">
                                                           {{ $planData['title'] }}
                                                       </span>
                                                   </div>

                                                   <!-- Features List -->
                                                   <div class="my-3">
                                                       @foreach ($features as $featureKey => $featureValue)
                                                           @php
                                                               $structureFeatureValue = $featureValue;
                                                               $featureValue = $planData['features'][$featureKey];
                                                           @endphp
                                                           <div class="d-flex align-items-start my-2 small text-dark">
                                                               @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                                                                   @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                                       <i class="fa fa-check-circle text-success mr-2 mt-1"></i>
                                                                   @else
                                                                       <i class="fa fa-times-circle text-danger mr-2 mt-1"></i>
                                                                   @endif
                                                                   <span>{{ ($structureFeatureValue['description']) }}</span>
                                                               @else
                                                                   <i class="fa fa-check-circle text-success mr-2 mt-1"></i>
                                                                   <span>
                                                                       @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                                                                           <strong>{{ __tr('Illimité') }}</strong>
                                                                       @elseif(isset($featureValue['limit']))
                                                                           <strong>{{ $featureValue['limit'] }}</strong>
                                                                           @if(isset($featureValue['limit_duration']))
                                                                               {{ ($featureValue['limit_duration']) }}
                                                                           @endif
                                                                       @endif
                                                                       {{ ($structureFeatureValue['description']) }}
                                                                   </span>
                                                               @endif
                                                           </div>
                                                       @endforeach
                                                   </div>
                                               </div>

                                               <!-- Price Radio Selector -->
                                               <div class="pt-3 border-top mt-3">
                                                   @foreach ($charges as $itemKey => $itemValue)
                                                       @php
                                                           if(!$itemValue['enabled']) {
                                                               continue;
                                                           }
                                                           $selectorVal = $planId . '___' . $itemKey;
                                                       @endphp
                                                       <div class="form-check custom-radio my-1">
                                                           <input x-model="selectedPlanFrequencyNew" 
                                                                  class="form-check-input" 
                                                                  type="radio" 
                                                                  name="plan"
                                                                  id="{{ $planId }}{{ $itemKey }}"
                                                                  value="{{ $selectorVal }}">
                                                           <label class="form-check-label font-weight-bold text-dark" style="cursor: pointer;" for="{{ $planId }}{{ $itemKey }}">
                                                               <span class="h5 font-weight-bold text-emerald mb-0" style="color: #059669;">{{ formatAmount($itemValue['charge'], true) }}</span>
                                                               <span class="text-muted small">/ {{ $planStructure[$planId]['charges'][$itemKey]['title'] ?? $itemKey }}</span>
                                                               @if ($planSelectorId == $selectorVal)
                                                                   <small class="text-emerald font-weight-bold ml-1"><em>({{ __tr('Renouveler le plan actuel') }})</em></small>
                                                               @endif
                                                           </label>
                                                       </div>
                                                   @endforeach
                                               </div>
                                           </div>
                                       </div>
                                   </div>
                               @endforeach
                            </div>

                            <!-- Payment Action Buttons -->
                            <div class="mt-4 pt-3 border-top">
                                @stack('autoSubscriptionChangePlanStack')

                                <!-- Stripe Payment -->
                                @if ($isCashierSubscription && getAppSettings('enable_stripe') and $isValidStripeKeys)
                                    <form class="lw-ajax-form mb-2" data-show-processing="true" action="{{ route('subscription.write.change') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="plan" x-model="selectedPlanFrequencyNew">
                                        <button value="stripe" type="submit" class="btn btn-primary btn-block font-weight-bold py-3" style="border-radius: 12px;">
                                            {{ __tr('Change/Renew Plan Via Stripe') }}
                                        </button>
                                    </form>
                                @endif

                                <!-- Wave Payment -->
                                @if(getAppSettings('enable_wave'))
                                    <form action="{{ route('wave.checkout') }}" method="post" id="wave-pay-form" class="mb-2">
                                        @csrf
                                        <input type="hidden" name="plan" x-model="selectedPlanFrequencyNew">
                                        <button value="wave" type="submit" class="btn btn-info btn-block font-weight-bold py-3" style="border-radius: 12px;">
                                            <i class="fa fa-money-bill-wave mr-2"></i> {{ __tr('Change/Renew Plan Via Wave') }}
                                        </button>
                                    </form>
                                @endif

                                <!-- MoneyFusion (Mobile Money & Cards) Main Payment Button -->
                                @if(getAppSettings('enable_moneyfusion'))
                                    <form action="{{ route('moneyfusion.checkout') }}" method="post" id="moneyfusion-pay-form" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="plan" x-model="selectedPlanFrequencyNew">
                                        <button value="moneyfusion" type="submit" class="btn btn-block text-white font-weight-bold py-3 shadow-sm transition-all" 
                                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-size: 1.15rem; border-radius: 12px; cursor: pointer;">
                                            <i class="fa fa-mobile-alt mr-2"></i> {{ __tr('Payer par Orange Money, MTN, Moov, Wave, Carte') }}
                                        </button>
                                    </form>
                                @endif

                                @if ((getAppSettings('enable_upi_payment') or getAppSettings('enable_bank_transfer') or getAppSettings('enable_paypal') or getAppSettings('enable_razorpay') or getAppSettings('enable_paystack') or getAppSettings('enable_yoomoney') or getAppSettings('enable_phonepe')) and !$isRazorpaySubscription or ($isRazorpaySubscription and $isSubscriptionManuallyCancelled))
                                    <fieldset class="mt-4 pt-3 border-top">
                                        <legend class="h6 font-weight-bold text-dark">{{ __tr('Manual/Prepaid Subscription') }}</legend>
                                        @if ($existingManualSubscriptionPendingRequest)
                                            <div class="alert alert-danger my-3 border-0 shadow-sm" style="border-radius: 10px;">
                                                {{ __tr('Your already have pending request for manual subscription change, please wait once it get confirmed') }}
                                            </div>
                                        @else
                                            <form action="{{ route('vendor.subscription_manual_pay') }}" method="post" id="manual-pay-form">
                                                @csrf
                                                <input type="hidden" name="selected_plan" x-model="selectedPlanFrequencyNew">
                                                @if (getAppSettings('enable_paypal'))
                                                    <label for="lPaypalPaymentOption" class="mr-4"><input type="radio" id="lPaypalPaymentOption" name="payment_method" value="paypal"><img height="60" src="{{ asset('imgs/pay_pal.png') }}"></label>
                                                @endif
                                                @if (getAppSettings('enable_razorpay'))
                                                    <label for="lwRazorpayPaymentOption" class="mr-4"><input type="radio" id="lwRazorpayPaymentOption" name="payment_method" value="razorpay"><img height="40" src="{{ asset('imgs/razorpay.png') }}"></label>
                                                @endif
                                                @if (getAppSettings('enable_upi_payment'))
                                                    <label for="lwUpiPaymentOption" class="mr-4"><input type="radio" id="lwUpiPaymentOption" name="payment_method" value="upi"> {{ __tr('Pay with any UPI') }} <img height="60" src="{{ asset('imgs/upi-icon.png') }}"></label>
                                                @endif
                                                @if (getAppSettings('enable_paystack'))
                                                    <label for="lwPaystackPaymentOption" class="mr-4"><input type="radio" id="lwPaystackPaymentOption" name="payment_method" value="paystack"><img src="{{ asset('imgs/paystack-icon.png') }}" height="60"></label>
                                                @endif
                                                @if (getAppSettings('enable_yoomoney'))
                                                    <label for="lwYooMoneyPaymentOption" class="mr-4"><input type="radio" id="lwYooMoneyPaymentOption" name="payment_method" value="yoomoney"><img height="60" src="{{ asset('imgs/yoomoney.png') }}"></label>
                                                @endif
                                                @if (getAppSettings('enable_phonepe'))
                                                    <label for="lwPhonePePaymentOption" class="mr-4"><input type="radio" id="lwPhonePePaymentOption" name="payment_method" value="phonepe"><img height="60" src="{{ asset('imgs/phonepe.png') }}"></label>
                                                @endif
                                                <div class="my-3">
                                                    <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2" style="border-radius: 8px;">{{ __tr('Continue') }}</button>
                                                </div>
                                            </form>
                                        @endif
                                    </fieldset>
                                @endif
                            </div>
                       </div>
                   </div>
               @else
                   <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px;">
                       <i class="fa fa-info-circle mr-2"></i>{{ __tr('Aucun plan disponible à l\'achat') }}
                   </div>
               @endif

               <!-- Invoices Table if Stripe -->
               @if (getAppSettings('enable_stripe') and getAppSettings('stripe_enable_invoice_list') and $isValidStripeKeys)
                   <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                       <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                           <h5 class="font-weight-bold text-dark">{{ __tr('Stripe Invoices') }}</h5>
                       </div>
                       <div class="card-body p-4">
                           <div class="table-responsive">
                               <table class="table table-hover align-items-center" id="invoicesTable">
                                   <thead class="thead-light">
                                       <tr>
                                           <th>{{ __tr('Number') }}</th>
                                           <th>{{ __tr('Date') }}</th>
                                           <th>{{ __tr('Total') }}</th>
                                           <th>{{ __tr('Invoice Download') }}</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                   @foreach ($invoices as $invoice)
                                       <tr>
                                           <td class="font-weight-bold">{{ $invoice->number }}</td>
                                           <td>{{ $invoice->date()->toDayDateTimeString() }}</td>
                                           <td class="text-success font-weight-bold">{{ $invoice->total() }}</td>
                                           <td>
                                               <a class="btn btn-sm btn-light font-weight-bold" style="border-radius: 8px;" href="{{ route('subscription.read.download_invoice', [$invoice->id]) }}">
                                                   <i class="fa fa-download mr-1"></i>{{ __tr('Download') }}
                                               </a>
                                           </td>
                                       </tr>
                                   @endforeach
                                   </tbody>
                               </table>
                           </div>
                       </div>
                   </div>
               @endif
               @stack('autoSubscriptionInvoiceListStack')
           </div>
       </div>
   </div>
   @endif
@endsection

@if(!isset($message))
@push('appScripts')
<script>
    $(document).ready(function() {
    'use strict';
        if ($('#invoicesTable').length) {
            $('#invoicesTable').DataTable({
                ajax: false,
                serverSide: false,
                "order": [[ 1, "desc" ]],
                processing: false,
                formatNumber: function (numberValue) {
                    return __Utils.formatAsLocaleNumber(numberValue);
                },
            });
        }
    });
</script>
@if(getAppSettings('enable_stripe') and $isValidStripeKeys and !$existingManualSubscriptionPendingRequest)
    @if(!$currentPlan and $hasPlansForPurchase)
    <script src="https://js.stripe.com/v3/"></script>
    @endif
    <script>
        (function(){
        'use strict';
        @if(!$currentPlan and $hasPlansForPurchase)
        var stripe = Stripe('{{ config("cashier.key") }}');
        var elements = stripe.elements({locale: '{{ app()->getLocale() }}'});
        var style = {
            base: {
                color: '#898d05',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        var card = elements.create('card', { style: style });
        card.mount('#card-element');
        card.on('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        var form = document.getElementById('payment-form'),
            clientSecret = form.dataset.secret;

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            if ($(form).serializeArray().length < 2) {
                showWarnMessage('{{ __tr('Please select plan first') }}');
                return;
            }
            @if (!getVendorSettings('city') or !getVendorSettings('country_code') or !getVendorSettings('address') or !getVendorSettings('postal_code') or !getVendorSettings('state') or !getVendorSettings('contact_email') or !getVendorSettings('contact_phone'))
                showAlert("{{ __tr('Please check that you have fulfilled business information in General settings like address, city etc') }}", 'error');
                __Utils.throwError("{{ __tr('Missing business information.') }}");
            @endif

            const { setupIntent, error } = await stripe.confirmCardSetup(
                clientSecret, {
                    payment_method: {
                        card,
                         billing_details: {
                             "address": {
                                    "city": "{{ getVendorSettings('city') }}",
                                    "country": "{{ getVendorSettings('country_code') }}",
                                    "line1": "{{ getVendorSettings('address') }}",
                                    "postal_code": "{{ getVendorSettings('postal_code') }}",
                                    "state": "{{ getVendorSettings('state') }}"
                                },
                                "email": "{{ getVendorSettings('contact_email') }}",
                                name: "{{ getUserAuthInfo('profile.full_name') }}",
                                "phone": "{{ getVendorSettings('contact_phone') }}"
                         }
                    }
                }
            );

            if (error) {
                var errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                showAlert(error.message, "error");
            } else {
                stripeTokenHandler(setupIntent);
            }
        });

        function stripeTokenHandler(setupIntent) {
            var form = document.getElementById('payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'paymentMethod');
            hiddenInput.setAttribute('value', setupIntent.payment_method);
            form.appendChild(hiddenInput);
            form.submit();
        }
        @endif
    })();
    </script>
     @endif
@endpush
@endif