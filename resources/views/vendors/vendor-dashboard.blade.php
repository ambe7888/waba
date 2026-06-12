@extends('layouts.app', ['title' => __tr('Dashboard')])
@php
$vendorIdOrUid = $vendorIdOrUid ?? getVendorUid();
if(!isset($vendorViewBySuperAdmin)) {
$vendorViewBySuperAdmin = null;
}
@endphp
<?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
@section('content')
@if(hasCentralAccess())
@include('users.partials.header', [
'title' => __tr('__vendorTitle__ Dashboard', [
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
<div class="container-fluid">
    @if(hasCentralAccess())
    @php
    $currentActivePlanDetails = getVendorCurrentActiveSubscription($vendorInfo['id']);
    $planDetails = vendorPlanDetails(null, null, $vendorInfo['id']);
    @endphp
    <div class="col-xl-12 p-0">
        <!-- breadcrumbs -->
        <nav aria-label="breadcrumb" class="lw-breadcrumb-container">
            <ol class="breadcrumb bg-transparent text-light p-0 m-0">
                <li class=" breadcrumb-item mb-3">
                    <a class="text-decoration-none" href="{{ route('central.vendors') }}">{{ __tr('Manage Vendors')
                        }}</a>

                </li>
                <li class="text-light breadcrumb-item" aria-current="page">{{ __tr('Dashboard') }}</li>
            </ol>
        </nav>
        <!-- /breadcrumbs -->
    </div>
    @endif
    @include('layouts.headers.cards')
    @if(hasVendorAccess() or $vendorViewBySuperAdmin )
<div class="container-fluid">
    @php
        $whatsappSetupDone = isWhatsAppBusinessAccountReady($vendorIdOrUid) && !getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid);
        $templatesDone = ($totalTemplates ?? 0) > 0;
        $groupsDone = ($totalGroups ?? 0) > 0;
        $contactsDone = ($totalContacts ?? 0) > 0;
        $campaignsDone = ($totalCampaigns ?? 0) > 0;

        $completedSteps = 0;
        if ($whatsappSetupDone) $completedSteps++;
        if ($templatesDone) $completedSteps++;
        if ($groupsDone) $completedSteps++;
        if ($contactsDone) $completedSteps++;
        if ($campaignsDone) $completedSteps++;

        $totalSteps = 5;
        $percentage = round(($completedSteps / $totalSteps) * 100);
    @endphp

    @if (getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid))
    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle mr-3" style="font-size: 1.5rem;"></i>
            <div>
                <strong>{{ __tr('Votre jeton WhatsApp semble avoir expiré.') }}</strong> {{ __tr('Veuillez générer un nouveau jeton permanent et l\'enregistrer.') }}
                <br>
                <a class="btn btn-sm btn-white text-danger font-weight-bold mt-2"
                    href="{{ route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) }}">{{ __tr('Configurer l\'API Cloud') }}</a>
            </div>
        </div>
    </div>
    @endif

    @if (getAppSettings('pusher_by_vendor') and !getVendorSettings('pusher_app_id', null, null, $vendorIdOrUid))
    <div class="alert alert-warning shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle mr-3" style="font-size: 1.5rem;"></i>
            <div>
                <strong>{{ __tr('Configuration de Pusher requise.') }}</strong> {!! __tr('Les clés Pusher sont nécessaires pour les communications en temps réel (Chat, etc.). Obtenez-les sur __pusherLink__, créez un canal et renseignez-les.', [
                '__pusherLink__' => '<a target="_blank" href="https://pusher.com" class="text-underline font-weight-bold">pusher.com</a>'
                ]) !!}
                <br>
                <a class="btn btn-sm btn-white text-warning font-weight-bold mt-2"
                    href="{{ route('vendor.settings.read', ['pageType' => 'general']) }}#pusherKeysConfiguration">{{ __tr('Configuration de Pusher') }}</a>
            </div>
        </div>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card onboarding-card border-0" style="background: white; border-radius: 16px !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important; overflow: hidden;">
                <!-- Header Card with Gradient -->
                <div class="card-header border-0 py-4 d-flex align-items-center justify-content-between flex-wrap" style="background: linear-gradient(135deg, #128c7e, #25d366) !important; border-radius: 16px 16px 0 0 !important;">
                    <div>
                        <h3 class="text-white mb-1 font-weight-bold" style="font-size: 1.25rem;">
                            <i class="fas fa-flag-checkered mr-2"></i> {{ __tr('Guide de Démarrage Rapide') }}
                        </h3>
                        <p class="text-white-50 mb-0" style="font-size: 0.85rem;">
                            {{ __tr('Suivez ces étapes pour configurer pleinement votre compte et commencer vos campagnes.') }}
                        </p>
                    </div>
                    <div class="d-flex align-items-center mt-2 mt-md-0">
                        <span class="h2 text-white font-weight-bold mb-0 mr-2" style="font-size: 1.8rem;">{{ $percentage }}%</span>
                        <span class="text-white-50" style="font-size: 0.85rem;">{{ __tr('terminé') }}</span>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <!-- Progress Bar -->
                    <div class="onboarding-progress-wrapper mb-4">
                        <div class="progress rounded-pill shadow-inner" style="height: 10px; background-color: #f0f2f5; overflow: hidden;">
                            <div class="progress-bar rounded-pill" role="progressbar" 
                                 style="width: {{ $percentage }}%; background: linear-gradient(90deg, #128c7e, #25d366); transition: width 0.6s ease;" 
                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <!-- Steps List -->
                    <div class="row">
                        <!-- Step 1 -->
                        <div class="col-12 mb-3">
                            <div class="onboarding-step-item d-flex align-items-start p-3 rounded" style="background: #f8f9fa; border-left: 4px solid {{ $whatsappSetupDone ? '#2dce89' : '#fb6340' }}; transition: all 0.3s ease; border-radius: 8px !important;">
                                <div class="mr-3 mt-1">
                                    @if($whatsappSetupDone)
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(45, 206, 137, 0.15); color: #2dce89; font-size: 1.1rem;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(251, 99, 64, 0.15); color: #fb6340; font-size: 1.1rem;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1 font-weight-bold {{ $whatsappSetupDone ? 'text-muted text-decoration-line-through' : 'text-dark' }}" style="font-size: 1rem;">
                                        1. {{ __tr('Configuration de l\'API WhatsApp Cloud') }}
                                    </h4>
                                    <p class="mb-2 text-sm text-muted">
                                        @if(getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid))
                                            <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> {{ __tr('Votre jeton WhatsApp semble avoir expiré. Veuillez en générer un nouveau.') }}</span>
                                        @elseif($whatsappSetupDone)
                                            {{ __tr('Votre compte WhatsApp Cloud API est configuré et prêt pour l\'envoi de messages.') }}
                                        @else
                                            {{ __tr('Connectez votre compte développeur Facebook et configurez les clés d\'accès de votre compte WhatsApp Business.') }}
                                        @endif
                                    </p>
                                    @if(!$whatsappSetupDone || getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid))
                                        <a class="btn btn-sm btn-primary text-white" href="{{ route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) }}">
                                            <i class="fab fa-whatsapp mr-1"></i> {{ __tr('Configurer l\'API WhatsApp') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="col-12 mb-3">
                            <div class="onboarding-step-item d-flex align-items-start p-3 rounded" style="background: #f8f9fa; border-left: 4px solid {{ $templatesDone ? '#2dce89' : '#8898aa' }}; transition: all 0.3s ease; border-radius: 8px !important;">
                                <div class="mr-3 mt-1">
                                    @if($templatesDone)
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(45, 206, 137, 0.15); color: #2dce89; font-size: 1.1rem;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-muted font-weight-bold" style="width: 36px; height: 36px; background-color: #e9ecef; font-size: 0.95rem;">
                                            2
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1 font-weight-bold {{ $templatesDone ? 'text-muted text-decoration-line-through' : 'text-dark' }}" style="font-size: 1rem;">
                                        2. {{ __tr('Synchronisation des modèles de messages') }}
                                    </h4>
                                    <p class="mb-2 text-sm text-muted">
                                        @if($templatesDone)
                                            {{ __tr('Vos modèles de messages approuvés par Meta ont été correctement synchronisés.') }}
                                        @else
                                            {{ __tr('Importez et synchronisez vos modèles de messages approuvés par Meta pour pouvoir les utiliser.') }}
                                        @endif
                                    </p>
                                    @if(!$templatesDone)
                                        <a class="btn btn-sm btn-primary text-white {{ !$whatsappSetupDone ? 'disabled' : '' }}" href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">
                                            <i class="fas fa-sync mr-1"></i> {{ __tr('Synchroniser les modèles') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="col-12 mb-3">
                            <div class="onboarding-step-item d-flex align-items-start p-3 rounded" style="background: #f8f9fa; border-left: 4px solid {{ $groupsDone ? '#2dce89' : '#8898aa' }}; transition: all 0.3s ease; border-radius: 8px !important;">
                                <div class="mr-3 mt-1">
                                    @if($groupsDone)
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(45, 206, 137, 0.15); color: #2dce89; font-size: 1.1rem;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-muted font-weight-bold" style="width: 36px; height: 36px; background-color: #e9ecef; font-size: 0.95rem;">
                                            3
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1 font-weight-bold {{ $groupsDone ? 'text-muted text-decoration-line-through' : 'text-dark' }}" style="font-size: 1rem;">
                                        3. {{ __tr('Création de groupes de contacts') }}
                                    </h4>
                                    <p class="mb-2 text-sm text-muted">
                                        @if($groupsDone)
                                            {{ __tr('Vous avez créé des groupes de contacts pour vos campagnes.') }}
                                        @else
                                            {{ __tr('Créez des groupes de contacts (ex: clients, prospects, VIP) pour segmenter vos diffusions.') }}
                                        @endif
                                    </p>
                                    @if(!$groupsDone)
                                        <a class="btn btn-sm btn-primary text-white" href="{{ route('vendor.contact.group.read.list_view') }}">
                                            <i class="fas fa-users mr-1"></i> {{ __tr('Gérer les groupes') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="col-12 mb-3">
                            <div class="onboarding-step-item d-flex align-items-start p-3 rounded" style="background: #f8f9fa; border-left: 4px solid {{ $contactsDone ? '#2dce89' : '#8898aa' }}; transition: all 0.3s ease; border-radius: 8px !important;">
                                <div class="mr-3 mt-1">
                                    @if($contactsDone)
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(45, 206, 137, 0.15); color: #2dce89; font-size: 1.1rem;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-muted font-weight-bold" style="width: 36px; height: 36px; background-color: #e9ecef; font-size: 0.95rem;">
                                            4
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1 font-weight-bold {{ $contactsDone ? 'text-muted text-decoration-line-through' : 'text-dark' }}" style="font-size: 1rem;">
                                        4. {{ __tr('Création ou importation de vos contacts') }}
                                    </h4>
                                    <p class="mb-2 text-sm text-muted">
                                        @if($contactsDone)
                                            {{ __tr('Vos contacts ont été ajoutés et sont prêts.') }}
                                        @else
                                            {{ __tr('Ajoutez vos destinataires manuellement ou importez-les en masse via un fichier Excel ou CSV.') }}
                                        @endif
                                    </p>
                                    @if(!$contactsDone)
                                        <a class="btn btn-sm btn-primary text-white" href="{{ route('vendor.contact.read.list_view') }}">
                                            <i class="fas fa-user-plus mr-1"></i> {{ __tr('Gérer les contacts') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="col-12">
                            <div class="onboarding-step-item d-flex align-items-start p-3 rounded" style="background: #f8f9fa; border-left: 4px solid {{ $campaignsDone ? '#2dce89' : '#8898aa' }}; transition: all 0.3s ease; border-radius: 8px !important;">
                                <div class="mr-3 mt-1">
                                    @if($campaignsDone)
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(45, 206, 137, 0.15); color: #2dce89; font-size: 1.1rem;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-muted font-weight-bold" style="width: 36px; height: 36px; background-color: #e9ecef; font-size: 0.95rem;">
                                            5
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1 font-weight-bold {{ $campaignsDone ? 'text-muted text-decoration-line-through' : 'text-dark' }}" style="font-size: 1rem;">
                                        5. {{ __tr('Lancement de votre première campagne') }}
                                    </h4>
                                    <p class="mb-2 text-sm text-muted">
                                        @if($campaignsDone)
                                            {{ __tr('Félicitations, vous avez déjà configuré et envoyé des campagnes de messages.') }}
                                        @else
                                            {{ __tr('Planifiez ou envoyez votre première campagne de diffusion à vos groupes de contacts.') }}
                                        @endif
                                    </p>
                                    @if(!$campaignsDone)
                                        <a class="btn btn-sm btn-primary text-white {{ (!$whatsappSetupDone || !$templatesDone || !$contactsDone) ? 'disabled' : '' }}" href="{{ route('vendor.campaign.read.list_view') }}">
                                            <i class="fas fa-paper-plane mr-1"></i> {{ __tr('Gérer les campagnes') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(hasCentralAccess())
    <div class="col-xl-12 pl-1">
        <div class="">
            <div class="card-body">
                <fieldset class="mb-5">
                    <legend>{{ __tr('Vendor Details') }}</legend>
                     <div class="col-xl-12 ">
                        <a data-method="post" class="btn btn-light btn-sm lw-ajax-link-action float-right" href="{{ route('central.vendors.user.write.login_as',['vendorUid'=>$vendorIdOrUid])}}"   data-confirm="#lwLoginAs-template" title="{{ __tr('Login as Vendor Admin') }}"><i class="fa fa-sign-in-alt"></i> {{  __tr('Login') }}</a>
                    </div>
                    <div class="my-2 ">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Vendor Title:') }}</h4>
                        <p class="card-text">{{$vendorInfo['title']}} </p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Account Status:') }}</h4>
                        @if($vendorInfo['status']==0)
                        <p class="card-text">{{__tr('Inactive') }}</p>
                        @else
                        <p class="card-text">{{configItem('status_codes',$vendorInfo['status'])}}</p>
                        @endif

                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Created On:') }}</h4>
                        <p class="card-text">{{formatDate($vendorUserData['created_at'])}}</p>
                    </div>
                    <hr>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Admin User Name:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['first_name'] . ' ' . $vendorUserData['last_name'])}}</p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Username:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['username'])}}</p>
                    </div>
                    @if($vendorUserData['mobile_number'])
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Phone Number:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['mobile_number'])}}</p>
                    </div>
                    @endif
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Email:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['email'])}}</p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Admin User Status:') }}</h4>
                        @if($vendorUserData['status']==0)
                        <p class="card-text">{{__tr('Inactive') }}</p>
                        @else
                        <p class="card-text">{{configItem('status_codes', $vendorUserData['status'])}}</p>
                        @endif
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    @php
                        $planStructure = $planDetails->plan_id ? getPaidPlans($planDetails->plan_id) : getFreePlan();
                        $planCharges = $planStructure['charges'][$planDetails->frequency] ?? null;
                    @endphp
                    <legend>{{ __tr('Current Subscribed Plan') }}</legend>
                    <div class="row">
                        <div class="col-md-7">
                            @if ($planDetails->hasActivePlan())
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Plan Title:') }}</h4>
                                <p class="card-text">{{$planDetails->planTitle()}} </p>
                            </div>
                            @if($planCharges)
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Current Plan Charges:') }}</h4>
                                <p class="card-text"> {{ $planCharges['title'] ?? '' }} {{ formatAmount($planCharges['charge'],
                                    true) }}</p>
                            </div>
                            @endif
                            @if($currentActivePlanDetails)
                            @if($planDetails['subscription_type']=='manual')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{configItem('subscription_status',$currentActivePlanDetails['status'])}}</p>
                            </div>
                            @elseif($planDetails['subscription_type']=='auto')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{configItem('subscription_status',$currentActivePlanDetails['stripe_status'])}}</p>
                            </div>
                            @else
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{__tr('Active') }}</p>
                            </div>
                            @endif
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Subscription Type:') }}</h4>
                                @if (data_get($currentActivePlanDetails, ('is_auto_recurring')))
                                     <p class="card-text">{{configItem('subscription_methods', 'auto')}}
                                @else
                                    <p class="card-text">{{configItem('subscription_methods',$planDetails['subscription_type'])}}
                                @endif
                                </p>
                            </div>
                            @if($currentActivePlanDetails)
                            {{--  check payment method is manual for payment method --}}
                            @if($planDetails['subscription_type']=='manual')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Payment Method:') }}</h4>
                                <p class="card-text">{{ $currentActivePlanDetails['__data']['manual_txn_details']['selected_payment_method'] ?? 'NA' }}</p>
                            </div>
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Created On:') }}</h4>
                                <p class="card-text">{{formatDate($currentActivePlanDetails['created_at'])}}</p>
                            </div>
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Expire On:') }}</h4>
                                <p class="card-text">{{ $planDetails['ends_at'] ? formatDate($planDetails['ends_at']):  'NA'}}</p>
                            </div>
                            @else
                            <div class="alert alert-warning">{{ __tr('Vendor does not have any active plan.') }}</div>
                            @endif
                        </div>
                        <div class="col-md-4 mt--5">
                            <fieldset class="mb-4">
                                <legend>{{ __tr('Plan Details') }}</legend>
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
                                @else
                                    <hr><div class="alert alert-warning">{{  __tr('Vendor does not have any active plan.') }}</div>
                                @endif
                                @if($planDetails->isAuto())
                                    <hr>
                                    <h4 class="text-warning">
                                        {{  __tr('Please note vendor is on the Auto Renewal Subscription Plan, First you need to cancel it to manage manual subscription.') }}
                                    </h4>
                                    <a data-show-processing="true" class="lw-ajax-link-action btn btn-danger" data-method="post" href="{{ route('central.subscription.write.cancel', [
                                        'vendorUid' => $vendorIdOrUid
                                    ]) }}">
                                        {{ __tr('Cancel Auto Subscription and Discard Grace Period if any') }}
                                    </a>
                                @else
                                    @if (!$isExtendedLicence)
                                        <hr><div class="alert alert-danger">
                                            {{  __tr('Extended licence required to enable manage subscription') }}
                                        </div>
                                    @endif
                                @endif
                                {{-- show warning message to admin --}}
                                @stack('autoSubscriptionWarningMessagesStack')
                                {{-- /show warning message to admin --}}
                            </fieldset>
                        </div>
                    </div>
                    @if ($isExtendedLicence and !data_get($currentActivePlanDetails, 'is_auto_recurring'))
                        <hr><button type="button" class="lw-btn btn btn-primary" data-toggle="modal" data-target="#lwAddNewManualSubscription"> {{ __tr('Create New Subscription') }}</button>
                    @endif
                </fieldset>
            </div>

            <div class="col-xl-12">
                @if (data_get($currentActivePlanDetails, ('is_auto_recurring')))
                    <h1>{{  __tr('Razorpay Subscription Log') }}</h1>
                @else
                    <h1>{{  __tr('Manual/Prepaid Subscription Log') }}</h1>
                @endif
                <x-lw.datatable data-page-length="100" id="lwManualSubscriptionList" data-page-length="10"
                    :url="route('central.subscription.manual_subscription.read.list', [
                        'vendorUid' => $vendorIdOrUid,
                        'isAutoRecurring' => data_get($currentActivePlanDetails, ('is_auto_recurring'))
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
                                <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSubscriptionStatus" data-form-group-class="" data-selected="<%- __tData.status %>" :label="__tr('Status')" name="status" required="true">
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
                    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditManualSubscriptionBody" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.read.update.data', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditManualSubscription"><i class="fa fa-edit"></i> {{  __tr('Update') }}</a>
                    <a data-method="post" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.write.delete', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteManualSubscription-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwManualSubscriptionList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
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
    </div>
@endif
</div>
@if(isThisDemoVendorAccountAccess())
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="alert alert-dark">
                <h2 class="text-white">{{ __tr('Demo Account') }}</h2>
                <p>{{ __tr('Contacts created here with your numbers will be deleted frequently. You need to add your number to allow for test') }}</p>
                <p>{{ __tr('If you want to test system with your own account. Facebook also provides Test Number which
                    is very easy to setup and test. You can follow the steps given in Quick Start on dashboard to get
                    started.') }}</p>
                     <a title="{{  __tr('You can update your numbers for test on this demo account') }}" class="lw-btn btn btn-xl btn-danger" href="#"  data-toggle="modal" data-target="#lwRegisterDemoNumber"><i class="fab fa-whatsapp"></i> {{  __tr('Add your WhatsApp Numbers for Demo Test') }}</a>
            </div>
        </div>
    </div>
</div>
@include('vendors.demo-instructions')
@endif
@if(hasVendorAccess() or $vendorViewBySuperAdmin)
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
@endif
<script type="text/template" id="lwLoginAs-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want login to this vendor admin account?') }}</p>
</script>

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
@push('head')
<?= __yesset(['dist/css/dashboard.css'],true) ?>
@endpush
@push('js')
<?= __yesset(['dist/js/dashboard.js'],true)?>
@endpush
@endsection()