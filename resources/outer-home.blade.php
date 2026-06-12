<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION ?? ''}}">
@php
$appName = getAppSettings('name');
@endphp
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ (isset($title) and $title) ? ' - ' . $title : __tr('Bienvenue') }} - {{ $appName }}</title>
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $appName }}" />
    <meta name="description" content="{{ getAppSettings('description') }}" />
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $appName }}" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="{{ $appName }}" />
    <meta property="og:description" content="{{ getAppSettings('description') }}" />
    <meta property="og:image" content="{{ getAppSettings('logo_image_url') }}" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ url('/') }}" />
    <meta property="twitter:title" content="{{ $appName }}" />
    <meta property="twitter:description" content="{{ getAppSettings('description') }}" />
    <meta property="twitter:image" content="{{ getAppSettings('logo_image_url') }}" />

    <!-- FAVICON -->
    <link href="{{ getAppSettings('favicon_image_url') }}" rel="icon">
    {!! __yesset([
    'static-assets/packages/fontawesome/css/all.css',
    'static-assets/packages/bootstrap-icons/font/bootstrap-icons.css'
    ]) !!}
    <!-- Google fonts-->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
<body class="lw-outer-home-page">
    {!! __yesset([
        'dist/css/app-public.css',
    ], true,
    ) !!}

    <body id="page-top">
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm" id="mainNav">
            <div class="container px-5">
                <!-- Logo -->
                <!-- Brand -->
                <a class="navbar-brand pt-0" href="{{ url('/') }}">
                    <img src="{{ getAppSettings('logo_image_url') }}" class="navbar-brand-img"
                    alt="{{ getAppSettings('name') }}">
                </a>
                <!-- Logo -->
                <button class="navbar-toggler lw-btn-block-mobile" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                    aria-label="{{ __tr('Toggle navigation') }}">
                    {{  __tr('Menu') }}
                    <i class="bi-list"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ms-auto me-4 my-3 my-lg-0">
                        <!-- Menu -->
                        <li class="nav-item"><a class="nav-link me-lg-3" href="#features">{{ __tr('Fonctionnalités') }}</a>
                        </li>
                        <li class="nav-item"><a class="nav-link me-lg-3" href="#pricing">{{ __tr('Tarification') }}</a></li>
                        <li class="nav-item"><a class="nav-link me-lg-3" href="{{ route('user.contact.form') }}">{{
                                __tr('Contact') }}</a></li>
                                <!-- pages -->
                                  <!-- pages -->
                                  <li class="nav-item">
                                    @include('layouts.navbars.navs.pages-menu-partial')
                                 </li>

                                 <!-- /pages -->
                               <!-- /pages -->
                        @if(!isLoggedIn())
                        @if(getAppSettings('enable_vendor_registration') or getAppSettings('message_for_disabled_registration'))
                        <li class="nav-item"><a class="nav-link me-lg-3 text-danger fw-bold" href="{{ route('auth.register') }}">{{
                                __tr('Register') }}</a></li>
                         @endif
                        <li class="nav-item"><a class="nav-link me-lg-3" href="{{ route('auth.login') }}">{{
                                __tr('Connexion') }}</a></li>
                        @endif
                        @if(isLoggedIn())
                        <li class="nav-item"><a class="nav-link me-lg-3 btn btn-danger text-white" href="{{ route('central.console') }}">{{
                                __tr('Tableau de bord') }}</a></li>
                        @endif
                        @include('layouts.navbars.locale-menu')
                        <!-- /Menu -->
                    </ul>

                </div>
            </div>
        </nav>
        <!-- /Navigation -->

        <!-- Mashead header-->
        <header class="masthead">
            <div class="container p-5">
                <div class="row gx-5 align-items-center my-2">
                    <div class="col-lg-12">
                        <div class="col-lg-12 text-center">
                            <!-- Masthead device mockup feature-->
                            <div class="masthead-device-mockup h-100" >
                                <div class="lw-io-icon">
                                    <i class="bi bi-whatsapp"></i>
                                    <i class="fa fa-bullhorn"></i>
                                    <i class="fa fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Mashead text and app badges-->
                        <div class="mb-5 mb-lg-0 text-center text-lg-start">
                            <h1 class="display-1  mb-3">{{ __tr('Engagez vos clients sur WhatsApp comme jamais auparavant') }}</h1>
                            <div class="lead display-6 text-muted mt-5 ">{{ __tr('Libérez tout le potentiel des engagements clients avec __appName__ - votre plateforme marketing WhatsApp complète.', [
                                '__appName__' => $appName
                            ]) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- quote/testimonial aside-->
        <aside class="text-center bg-gradient-primary-to-secondary">
            <div class="container px-5">
                <div class="row gx-5 justify-content-center">
                    <div class="col-xl-8">
                        <h2 class="display-1 text-white text-center">{!! __tr('Pourquoi choisir __appName__?', [
                            '__appName__' => $appName
                        ]) !!}</h2>
                        <div class="h2 text-light my-5">{{ __tr('Découvrez les avantages inégalés de notre plateforme WhatsApp Marketing, __appName__.', [
                            '__appName__' => $appName
                        ])  }}</div>
                        <ul class="text-center list-group mb-5">
                            <li class="list-group-item py-4 "><h4>{{  __tr('Un engagement accru') }}</h4> <p class="text-success">{{  __tr('Dialoguez directement avec vos clients en temps réel sur WhatsApp.') }}</p></li>
                            <li class="list-group-item py-4 "><h4>{{  __tr('Des taux de conversion plus élevés') }}</h4> <p class="text-success">{{  __tr('Transformez les conversations en conversions grâce à des messages ciblés. __appName__.', [
                                '__appName__' => $appName
                            ]) }}</p></li>
                            <li class="list-group-item py-4 "><h4>{{  __tr('Assistance à la clientèle 24h/24, 7j/7') }}</h4> <p class="text-success">{{  __tr('Les réponses automatisées vous permettent de rester à la disposition de vos clients grâce à __appName__.', [
                                '__appName__' => $appName
                            ]) }}</p></li>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>
        <!-- Advance Feature Section -->
        <section class="py-5 lw-io-feaures" id="features">
            <div class="container py-5">
                <div class="row">
                    <h2 class="display-4  mb-4 text-center">{{ __tr('Des fonctionnalités puissantes') }}</h2>
                    <p class="lead fw-normal text-muted mb-md-5 mb-lg-0 text-center">{{ __tr('Des fonctionnalités qui vous faciliteront la vie avec WhatsApp Marketing') }}</p>
                </div>
                <!-- /Row End -->
                <div class="row mt-5    ">
                    <div class="lw-io-single-item col-lg-3 col-md-6">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('WhatsApp Chat') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('La fonction de chat WhatsApp intégrée dans __appName__ reflète la même interface native de WhatsApp, garantissant aux utilisateurs une expérience de messagerie transparente et familière.', [
                                    '__appName__' => $appName
                                ]) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-bullhorn"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Campagnes') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Créez des campagnes instantanées ou programmées pour tous les contacts ou des groupes spécifiques. Vous avez ainsi la possibilité de toucher votre public immédiatement ou de planifier des campagnes au moment le plus opportun.', [
                                    '__appName__' => $appName
                                ]) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-md-0 my-3">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-user"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Gérer les contacts') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Importez et exportez des contacts sans effort en utilisant le format XLSX pour faciliter le transfert de contacts, ainsi que la fonctionnalité Ajouter/Modifier sur la plate-forme.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-bars"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Champs personnalisés') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Personnalisez vos messages à partir des informations recueillies auprès des utilisateurs et des champs personnalisés en fonction de votre public. __appName__.', [
                                    '__appName__' => $appName
                                ]) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Row End -->
                <div class="row mt-1 mt-md-4 d-flex justify-content-center">
                    <div class="lw-io-single-item col-lg-3 col-md-6">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-robot"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Réponses Auto / Chat Bot') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Automatisez les réponses et engagez les clients 24h/24 et 7j/7 grâce aux réponses intelligentes des robots. __appName__.', [
                                    '__appName__' => $appName
                                ]) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-bolt"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{!! __tr('Mises à jour en temps réel') !!}</h3>
                                <p class="text-muted mb-0 text-center">{!! __tr('Mises à jour en temps réel des messages et de la campagne pour connaître les performances de votre campagne et de vos messages') !!}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-language"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Multilingue') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Insistez sur le fait que le produit ou le service est disponible en plusieurs langues et répond aux besoins de différents publics dans le monde entier.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-tachometer-alt"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Tableau de bord') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Fournir une visibilité instantanée sur la performance et le statut de leurs campagnes marketing. ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Row End -->
                <!-- /Row End -->
                <div class="row mt-1 mt-md-4 d-flex justify-content-center">
                    <div class="lw-io-single-item col-lg-3 col-md-6">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <span class="h1">AI</span> <i class="fa fa-robot"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('Chat Bots AI') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Les vendeurs peuvent créer leur flux de chat à partir de Flowise et les intégrer de manière transparente.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{!! __tr('Agents') !!}</h3>
                                <p class="text-muted mb-0 text-center">{!! __tr('Déléguer le travail en créant des utilisateurs avec différentes permissions') !!}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-cogs"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('API') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Connecter des services ou des scripts à partir des API disponibles') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="lw-io-single-item col-lg-3 col-md-6 my-3 my-md-0">
                        <div class="lw-io-item">
                            <div class="lw-io-icon">
                                <i class="fa fa-qrcode"></i>
                            </div>
                            <div class="lw-ion-feat-info">
                                <h3 class="font-alt text-center mb-4">{{ __tr('QR Code') }}</h3>
                                <p class="text-muted mb-0 text-center">{{ __tr('Un code QR sera généré pour le numéro de téléphone WhatsApp afin de connecter rapidement les utilisateurs.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Row End -->
            </div>
            <!-- /Container End -->
        </section>
        <!-- /Advance Feature Section -->
        <!-- Basic features section-->
        <section class="bg-light">
            <div class="container px-5">
                <div class="row gx-5 align-items-center justify-content-center justify-content-lg-between">
                    <div class="col-12 col-lg-5">
                        <h2 class="display-4  mb-4">{!! __tr('Conçu pour la fidélisation clientèle') !!}</h2>
                        <p class="lead fw-normal text-muted mb-5 mb-lg-0">{{ __tr('une plateforme robuste dédiée à optimiser tous les aspects de la relation avec les clients. Des canaux de communication transparents aux stratégies de communications personnalisées, __appName__ permet aux entreprises de développer des relations fortes et durables avec leurs clients, tout en générant des résultats positifs et de la croissance.', [
                            '__appName__' => $appName
                        ]) }}</p>
                    </div>
                    <div class="col-sm-8 col-md-6">
                        <div class="px-5 px-sm-0"><img class="img-fluid rounded-circle"
                                src="{{ asset('imgs/outer-home/photo-1633354931133-27ac1ee5d853.jpeg') }}" alt="..." /></div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Basic features section-->


        <section id="pricing" class="pricing-content section-padding">
            <div class="container">
                <div class="section-title text-center">
                    <div class="pricing-titles mb-4">
                        <h1 class="display-6">{{ __tr('Des plans de tarification simples et clairs') }}</h1>
                    </div>
                </div>
                <div class="row text-center">
                    @php
                    $freePlanDetails = getFreePlan();
                    $freePlanStructure = getConfigFreePlan();
                    $paidPlans = getPaidPlans();
                    $planStructure = getConfigPaidPlans();
                    @endphp
                        <!-- Free Plan -->
                        @if ($freePlanDetails['enabled'])
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.3s" data-wow-offset="0" style="visibility: visible; animation-duration: 1s; animation-delay: 0.3s; animation-name: fadeInUp;">
                        <div class="pricing_design">
                            <div class="single-pricing">
                                <div class="price-head">
                                    <h6 class="display-5 mb-4 text-uppercase">{{ $freePlanDetails['title']}}</h6>
                                    <hr class="bg-success">
                                    <h2 class="price mb-1">{{ formatAmount(0, true, true) }}</h2>
                                    <span>{{  __tr('Mensuel') }}</span>
                                    <br><br>
                                    <h2 class="price mb-1">{{ formatAmount(0, true, true) }}</h2>
                                    <span>{{  __tr('Annuel') }}</span>
                                    <br><br>
                                    <small><a class="text-muted" target="_blank" href="https://business.whatsapp.com/products/platform-pricing">{{  __tr('+ Frais de messagerie WhatsApp Cloud') }} <i class="fas fa-external-link-alt"></i></a></small>
                                </div>
                                <hr class="bg-success mt-4">
                                <ul>
                                    @foreach ($freePlanStructure['features'] as $featureKey => $featureValue)
                                @php
                                    $configFeatureValue = $featureValue;
                                    $featureValue = $freePlanDetails['features'][$featureKey];
                                @endphp
                                    <li>
                                        @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                                        @if (isset($featureValue['limit']) and $featureValue['limit'])
                                        <i class="fa fa-check mr-3 bg-success"></i>
                                        @else
                                        <i class="fa fa-times mr-3 bg-danger"></i>
                                        @endif
                                        {{ ($configFeatureValue['description']) }}
                                        @else
                                        <strong>@if (isset($featureValue['limit']) and $featureValue['limit'] < 0) {{ __tr('Unlimited') }} @elseif(isset($featureValue['limit'])) {{ __tr($featureValue['limit']) }} 
                                        @endif </strong> {{ ($configFeatureValue['description']) }} {{ ($configFeatureValue['limit_duration_title'] ?? '') }} @endif</li>
                                @endforeach
                                </ul>
                                <div class="pricing-price">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @foreach ($planStructure as $planKey => $plan)
                            @php
                            $planId = $plan['id'];
                            $features = $plan['features'];
                            $savedPlan = $paidPlans[$planKey];
                            $charges = $savedPlan['charges'];
                            if (!$savedPlan['enabled']) {
                                continue;
                            }
                            @endphp
                                <div class="col-lg-4 col-md-6 col-sm-12 mb-4 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.3s" data-wow-offset="0" style="visibility: visible; animation-duration: 1s; animation-delay: 0.3s; animation-name: fadeInUp;">
                                        <div class="pricing_design">
                                            <div class="single-pricing {{ $plan['popular']  ? 'lw-pricing-popular' : ''}}">
                                                <div class="price-head">
                                                    <h6 class="display-5 mb-4 text-uppercase">{{ $savedPlan['title'] ?? $plan['title']}}</h6>
                                                    <hr class="bg-success">
                                                    @foreach ($charges as $itemKey => $itemValue)
                                                    @php
                                                        if(!$itemValue['enabled']) {
                                                            continue;
                                                        }
                                                    @endphp
                                                    <h2 class="price mb-1">{{ formatAmount($itemValue['charge'], true, true) }}</h2>
                                                    <span>{{ Arr::get($plan['charges'][$itemKey], 'title', '') }}</span>
                                                    <br><br>
                                                    @endforeach
                                                    <small><a class="text-muted" target="_blank" href="https://business.whatsapp.com/products/platform-pricing">{{  __tr('+ Frais de messagerie WhatsApp Cloud') }} <i class="fas fa-external-link-alt"></i></a></small>
                                                </div>
                                                <hr class="bg-success mt-4">
                                                <ul>
                                                    @foreach ($plan['features'] as $featureKey => $featureValue)
                                                @php
                                                    $configFeatureValue = $featureValue;
                                                    $featureValue = $savedPlan['features'][$featureKey];
                                                @endphp
                                                    <li>
                                                        @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                                                        @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                        <i class="fa fa-check mr-3 bg-success"></i>
                                                        @else
                                                        <i class="fa fa-times mr-3 bg-danger"></i>
                                                        @endif
                                                        {{ ($configFeatureValue['description']) }}
                                                        @else
                                                        <strong>@if (isset($featureValue['limit']) and $featureValue['limit'] < 0) {{ __tr('∞') }} @elseif(isset($featureValue['limit'])) {{ __tr($featureValue['limit']) }} @endif </strong> {{ ($configFeatureValue['description']) }} {{ ($configFeatureValue['limit_duration_title'] ?? '') }}
                                                        @endif
                                                    </li>
                                                @endforeach
                                                </ul>
                                                <div class="pricing-price">
                                                </div>
                                            </div>
                                        </div>
                                    </div><!--- END COL -->
                            @endforeach
                </div><!--- END ROW -->
            </div><!--- END CONTAINER -->
        </section>
        <!-- Call to action section-->
        <section class="cta d-none">
            <div class="cta-content">
                <div class="container px-5 text-center">
                    <img class="rounded shadow mb-5" src="{{ asset('imgs/outer-home/qr-code-sample.jpeg') }}"
                    alt="">
                    <h2 class="text-white display-1  mb-4">
                        {{ __tr('Essayez maintenant') }}
                    </h2>
                    <p class="text-white">{{  __tr('Scannez le code QR ou cliquez sur le bouton ci-dessous pour démarrer le chat de démonstration.') }}</p>
                    <a class="btn btn-success btn-lg py-3 px-4 rounded-pill"
                        href="{{ route('auth.register') }}">{{ __tr('Commencer le chat maintenant') }}</a>
                </div>
            </div>
        </section>
        <!-- App badge section-->
        <section class="bg-gradient-primary-to-secondary text-white" >
            <div class="container px-5">
                <h2 class="text-center text-white font-alt mb-4">{{  __tr('Témoignages de la communauté __appName__', [
                    '__appName__' => $appName
                ]) }}</h2>
                <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="testimonial text-center">
                                            <p class="lead">"{{  __tr('Utiliser __appName__ a transformé notre stratégie de mobilisation des clients. La fonction importation/exportation change la donne pour la gestion efficace de nos contacts.', [
                                                '__appName__' => $appName
                                            ]) }}"</p>
                                            <cite>— {{  __tr('Bah Amamdou, Marketing Manager BABISHOP') }}</cite>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="testimonial text-center">
                                            <p class="lead">"{{  __tr('Les capacités automatisées de __appName__, en particulier les réponses des robots, ont considérablement réduit nos temps de réponse et amélioré la satisfaction de nos clients.', [
                                                '__appName__' => $appName
                                            ]) }}"</p>
                                            <cite>— {{  __tr('Koutou Ehouman, SONEP-CI') }}</cite>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="testimonial text-center">
                                            <p class="lead">"{{  __tr('La conception intuitive de __appName__\ et la facilité à intégrer Facebook WhatsApp Business nous ont permis de lancer rapidement nos campagnes de marketing.', [
                                                '__appName__' => $appName
                                            ]) }}"</p>
                                            <cite>— {{  __tr('Cissé ASAP, Expert Communication') }}</cite>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Additional Testimonial Item -->
        <div class="carousel-item">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="testimonial text-center">
                            <p class="lead">"{{  __tr('__appName__ a considérablement amélioré notre portée marketing. Leurs outils de gestion de campagne sont incroyablement conviviaux et efficaces.', [
                                '__appName__' => $appName
                            ]) }}"</p>
                            <cite>— {{  __tr('4A Dev, Marketing Director') }}</cite>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">{{  __tr('Précédent') }}</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">{{  __tr('Suivant') }}</span>
                    </button>
                </div>

            </div>
        </section>
        <section class="bg-white">
             <div class="container my-5">
                <h2 class="mb-5 text-center display-3 ">{{  __tr('Questions fréquemment posées') }}</h2>
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h6 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                               {{  __tr(' Comment puis-je rejoindre __appName__?',[
                                '__appName__' => $appName
                               ]) }}
                            </button>
                        </h6>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                {{  __tr('Pour rejoindre __appName__ est simple et direct. Il vous suffit de visiter notre page de souscription, de remplir vos coordonnées et de suivre les instructions pour commencer.',[
                                    '__appName__' => $appName
                                ]) }}
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h6 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                {{  __tr('Puis-je importer des contacts à partir de ma base de données clients existante ?') }}
                            </button>
                        </h6>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                {{  __tr('Oui, __appName__ prend en charge les importations de contacts par le biais de fichiers XLSX. Vous pouvez facilement télécharger votre base de données clients existante et commencer à envoyer des messages immédiatement.',[
                                    '__appName__' => $appName
                                ]) }}
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h6 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                {{  __tr('Quel type de soutien offre __appName__ ?',[
                                    '__appName__' => $appName
                                ]) }}
                            </button>
                        </h6>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                {{  __tr('__appName__ offre un service clientèle 24h/24 et 7j/7 par chat en direct, par e-mail et par téléphone. Notre équipe dévouée est là pour vous aider à résoudre tous les problèmes ou questions que vous pourriez avoir.',[
                                    '__appName__' => $appName
                                ]) }}
                            </div>
                        </div>
                    </div>
                    <!-- Additional FAQ items as needed -->
                </div>
            </div>
        </section>
        <!-- Footer-->
        <footer class="bg-black text-center py-5">
            <div class="container px-5">
                <div class="text-white-50 small">
                    <div class="mb-2">&copy; {{ getAppSettings('name') }} {{ __tr(date('Y')) }}. {{  __tr('Par ') }}<strong>4A Dev</strong>{{  __tr(' Tous droits réservés.') }}</div>
                </div>
            </div>
        </footer>
        <script>
            (function() {
                'use strict';
                window.appConfig = {
                    debug: "{{ config('app.debug') }}",
                    csrf_token: "{{ csrf_token() }}",
                    locale : '{{ app()->getLocale() }}',
                }
            })();
        </script>
{!! __yesset([
    'dist/js/common-vendorlibs.js',
    'dist/js/vendorlibs.js',
    'dist/packages/bootstrap/js/bootstrap.bundle.min.js',
    'dist/js/jsware.js',
    ]) !!}
    {!! getAppSettings('page_footer_code_all') !!}
    @if(isLoggedIn())
    {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
    </body>
</html>