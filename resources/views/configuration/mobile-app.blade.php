@extends('layouts.app', ['title' => __tr('Mobile App Configurations')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Mobile App Configurations'),
'description' =>'',
'class' => 'col-lg-7'
])

@php
$isDemoMode = isDemo();
$demoContent = 'XXXXXXXXXXXXX MASKED FOR DEMO XXXXXXXXXXXXX';
@endphp

<div class="container-fluid ">
    <div class="row p-4">
     {{--    <div class="col-12 mb-3 alert alert-success ">
            <?= __tr('If you have purchase Flutter Mobile App or Bundle of for this application. You need following configurations contents for app_config.dart file for Flutter Mobile apps.') ?>
        </div> --}}
        <!-- button -->
        <div class="col-12 p-0">
            @if($isDemoMode)
            <div class="alert alert-warning">
                <strong>{{  __tr('Information masked for demo') }}</strong>
            </div>
@else
<code class="form-control bg-white  lw-mobile-app " readonly name="mobile_app_config" id="mobileAppConfig" rows="100">
// This is the mobile app configuration file content you can make
// changes to the file as per your requirements

// Warning do not change >>> -------------------------------------------

const String baseUrl = '{{ url('/') }}/';
const String baseApiUrl = '${baseUrl}api/';
// key for form encryption/decryptions
{{-- -----BEGIN PUBLIC KEY----- --}}
const String publicKey = '''{!! $isDemoMode ? $demoContent : YesSecurity::getPublicRsaKey() !!}''';
{{-- -----END PUBLIC KEY----- --}}
// ------------------------------------------- <<<<< do not change

// if you want to enable debug mode set it to true
// for the production make it false
const bool debug = {{ config('app.debug') ? 'true' : 'false' }};
const String version = '1.0.0';
const Map configItems = {
    'debug': debug,
    'appTitle': '{{ getAppSettings('default_language') }}',
    'default_language_code': '{{ getAppSettings('default_language') }}',
    'services': {
        'pusher': {
            'apiKey': '{{ $isDemoMode ? $demoContent : getAppSettings('pusher_app_key') }}',
            'cluster': '{{ $isDemoMode ? $demoContent : getAppSettings('pusher_app_cluster') }}'
        }
    }
};
</code>
@endif

{{-- Server Maintenance / Migration Notice Settings --}}
<fieldset x-data="{panelOpened:true}" class="mt-4" x-cloak>
    <legend @click="panelOpened = !panelOpened" style="cursor: pointer;">
        <i class="fas fa-bullhorn text-warning mr-2"></i>{{ __tr('Notification de Migration / Maintenance Serveur (Dashboard Vendeurs)') }}
        <small class="text-muted">{{ __tr('Cliquer pour plier/déplier') }}</small>
    </legend>
    <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" action="<?= route('manage.configuration.write', ['pageType' => 'mobile_app_configuration']) ?>">
        <div class="alert alert-warning">
            {{ __tr('Activez ce bandeau d\'alerte pour informer vos vendeurs d\'une intervention technique sur leur tableau de bord.') }}
        </div>

        <x-lw.checkbox id="lwEnableServerMaintenanceNotice" name="enable_server_maintenance_notice" :offValue="0" :checked="getAppSettings('enable_server_maintenance_notice')" data-lw-plugin="lwSwitchery" :label="__tr('Afficher le bandeau d\'information de migration sur le Dashboard')" />

        <div class="form-group mt-3">
            <label for="lwServerMaintenanceNoticeMessage">{{ __tr('Message de la notification') }}</label>
            <textarea rows="3" id="lwServerMaintenanceNoticeMessage" class="lw-form-field form-control" placeholder="{{ __tr('Ex: Une migration importante aura lieu ce soir...') }}" name="server_maintenance_notice_message">{!! getAppSettings('server_maintenance_notice_message', 'Une migration importante de nos serveurs est programmée ce soir entre Minuit (00h00) et 06h00 du matin. Des perturbations temporaires de la plateforme peuvent survenir pendant cette période.') !!}</textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">{{ __tr('Enregistrer l\'annonce') }}</button>
        </div>
    </form>
</fieldset>
{{-- /Server Maintenance / Migration Notice Settings --}}

{{-- Mobile Apps Visibility & Download Settings --}}
<fieldset x-data="{panelOpened:true}" class="mt-4" x-cloak>
    <legend @click="panelOpened = !panelOpened" style="cursor: pointer;">
        <i class="fas fa-mobile-alt text-primary mr-2"></i>{{ __tr('Disponibilité des Applications Mobiles (Android & iPhone / iOS)') }}
        <small class="text-muted">{{ __tr('Cliquer pour plier/déplier') }}</small>
    </legend>
    <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" action="<?= route('manage.configuration.write', ['pageType' => 'mobile_app_configuration']) ?>">
        <div class="alert alert-info">
            {{ __tr('Configurez l\'affichage et les liens de vos applications Android et iPhone. Si une option est désactivée, elle sera masquée du tableau de bord et du menu latéral des vendeurs.') }}
        </div>

        <!-- Android App Settings -->
        <div class="card mb-3 border p-3">
            <h5 class="font-weight-bold text-dark mb-2"><i class="fab fa-android text-success mr-2"></i>{{ __tr('Application Android') }}</h5>
            <x-lw.checkbox id="lwEnableAndroidApp" name="enable_android_app" :offValue="0" :checked="getAppSettings('enable_android_app')" data-lw-plugin="lwSwitchery" :label="__tr('Activer et afficher l\'application Android (APK / Play Store)')" />
            <div class="form-group mt-2">
                <label for="lwAndroidAppUrl">{{ __tr('Lien de téléchargement Android (Fichier APK ou Lien Play Store)') }}</label>
                <input type="text" id="lwAndroidAppUrl" class="lw-form-field form-control" placeholder="{{ url('downloads/whatsclick.apk') }}" name="android_app_url" value="{{ getAppSettings('android_app_url', url('downloads/whatsclick.apk')) }}" />
            </div>
        </div>

        <!-- iOS / iPhone App Settings -->
        <div class="card mb-3 border p-3">
            <h5 class="font-weight-bold text-dark mb-2"><i class="fab fa-apple text-dark mr-2"></i>{{ __tr('Application iPhone (iOS)') }}</h5>
            <x-lw.checkbox id="lwEnableIosApp" name="enable_ios_app" :offValue="0" :checked="getAppSettings('enable_ios_app')" data-lw-plugin="lwSwitchery" :label="__tr('Activer et afficher l\'application iPhone / iOS')" />
            <div class="form-group mt-2">
                <label for="lwIosAppUrl">{{ __tr('Lien App Store pour iPhone (URL App Store)') }}</label>
                <input type="text" id="lwIosAppUrl" class="lw-form-field form-control" placeholder="https://apps.apple.com/app/id..." name="ios_app_url" value="{{ getAppSettings('ios_app_url') }}" />
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">{{ __tr('Enregistrer les applications') }}</button>
        </div>
    </form>
</fieldset>
{{-- /Mobile Apps Visibility & Download Settings --}}

{{-- Firebase Notification Settings --}}
<fieldset x-data="{panelOpened:false}" class="mt-4" x-cloak>
    <legend @click="panelOpened = !panelOpened" style="cursor: pointer;">{{ __tr('Firebase Notification Settings') }} <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small></legend>
        <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" action="<?= route('manage.configuration.write', ['pageType' => 'mobile_app_configuration']) ?>">

        <div class="alert alert-light">
            {{ __tr('Please paste the contents of your Firebase service account JSON here..') }}
        </div>

        <x-lw.checkbox id="lwEnableFirebaseNotification" name="enable_firebase_notification" :offValue="0" :checked="getAppSettings('enable_firebase_notification')" data-lw-plugin="lwSwitchery" :label="__tr('Enable')" />
        
        <div class="mb-3 mb-sm-0">
            <label id="lwFooterCode">{{  __tr('Firebase Service Account JSON') }} </label>
            <textarea rows="10" id="lwFooterCode" class="lw-form-field form-control" placeholder="{{ __tr('Firebase Service Account JSON') }}" name="firebase_service_account_data">{!! getAppSettings('firebase_service_account_data') !!}</textarea>
        </div>
        <div class="form-group" name="footer_code">
            <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">{{ __tr('Save') }}</button>
        </div>
    </form>
</fieldset>
{{-- /Firebase Notification Settings --}}

        </div>
    </div>
</div>
@endsection()
