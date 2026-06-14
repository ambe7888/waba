@extends('layouts.app', ['title' => __tr('WhatsApp Calling Addon - Setup')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('WhatsApp Calling Addon - Configuration'),
    'description' => __tr('Configure your custom WhatsApp calling features and webhook consent template.'),
    'class' => 'col-lg-7'
])

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-8 offset-xl-2">
            <div class="card shadow">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted ls-1 mb-1">{{ __tr('Configuration') }}</h6>
                            <h2 class="mb-0">{{ __tr('Paramètres de l\'Addon') }}</h2>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form class="lw-ajax-form lw-form" method="post" action="{{ route('addon.WhatsJetCallingAddon.setup_process') }}">
                        @csrf
                        <div class="form-group">
                            <x-lw.input-field type="text" 
                                id="lwCallingTemplateName" 
                                name="calling_template_name" 
                                value="{{ getAppSettings('whatsjet_calling_template_name', 'call_permission_request') }}" 
                                :label="__tr('Nom du modèle de permission (Meta Template Name)')" 
                                placeholder="ex: call_permission_request" />
                            <small class="text-muted">
                                {{ __tr('C\'est le nom exact du template interactif de demande de permission d\'appel configuré et approuvé dans votre Business Manager Meta.') }}
                            </small>
                        </div>

                        <div class="form-group">
                            <x-lw.input-field type="text" 
                                id="lwCallingTemplateLang" 
                                name="calling_template_lang" 
                                value="{{ getAppSettings('whatsjet_calling_template_lang', 'fr') }}" 
                                :label="__tr('Code de langue du modèle (Language Code)')" 
                                placeholder="ex: fr" />
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __tr('Enregistrer les paramètres') }}</button>
                            <a href="{{ route('manage.configuration.read', ['pageType' => 'addons']) }}" class="btn btn-secondary">{{ __tr('Retour aux Addons') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
