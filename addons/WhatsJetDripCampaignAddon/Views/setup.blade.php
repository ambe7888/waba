@extends('layouts.app', ['title' => __tr('Drip Campaign Addon - Setup')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Drip Campaign Addon'),
    'description' => __tr('Module d\'automatisation de séquences de messages WhatsApp.'),
    'class' => 'col-lg-7'
])

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-8 offset-xl-2">
            <div class="card shadow">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted ls-1 mb-1">{{ __tr('Informations') }}</h6>
                            <h2 class="mb-0">{{ __tr('État du Module') }}</h2>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> {{ __tr('Le module Drip Campaign est installé et actif.') }}
                    </div>

                    <h4 class="mt-4">{{ __tr('Comment ça marche ?') }}</h4>
                    <p class="text-muted">
                        {{ __tr("Ce module n'a pas besoin de configuration globale. Il est directement mis à la disposition des vendeurs (clients).") }}
                        <br><br>
                        {{ __tr("Les vendeurs peuvent créer leurs séquences (jours/heures/minutes) depuis leur propre interface. Lorsqu'un utilisateur final déclenche un Bot WhatsApp configuré pour une campagne Drip, il est automatiquement inscrit à la séquence.") }}
                    </p>

                    <hr>

                    <h4 class="text-danger"><i class="fa fa-exclamation-triangle"></i> {{ __tr('Pré-requis Système (Important)') }}</h4>
                    <p class="text-muted">
                        {{ __tr("Pour que les messages automatisés s'envoient à la minute près, vous devez vous assurer que le CRON principal de l'application est configuré pour s'exécuter chaque minute sur votre serveur (cPanel, Forge, ou Crontab linux).") }}
                    </p>
                    <div class="bg-dark text-white p-3 rounded mb-4">
                        <code>* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1</code>
                    </div>

                    <div class="form-group text-center mt-5">
                        <a href="{{ route('manage.configuration.read', ['pageType' => 'addons']) }}" class="btn btn-primary">{{ __tr('Retour aux Addons') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
