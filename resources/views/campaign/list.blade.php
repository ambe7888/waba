@php
/**
* Component : Campaign
* Controller : CampaignController
* File : campaign.list.blade.php
* -----------------------------------------------------------------------------
*/
@endphp
@extends('layouts.app', ['title' => __tr('Campagnes')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Campagnes'),
    'description' => __tr('Gérez et suivez vos envois de messages massifs sur WhatsApp.'),
])

<?php $status = request()->status ?? 'active'; ?>
<div class="container-fluid pt-4 pb-4">
    <!-- Barre d'action avec boutons bien visibles -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-5 mb-2 mb-md-0">
            @if($status == 'archived')
                <a href="{{ route('vendor.campaign.read.list_view', ['status' => 'active']) }}" class="btn btn-success font-weight-bold btn-sm text-white shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-arrow-left mr-1"></i> {{ __tr('Retour aux Campagnes Actives') }}
                </a>
            @else
                <a href="{{ route('vendor.campaign.read.list_view', ['status' => 'archived']) }}" class="btn btn-warning text-white font-weight-bold btn-sm shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-archive mr-1"></i> {{ __tr('Voir les Archives') }}
                </a>
            @endif
        </div>
        <div class="col-md-7 text-md-right">
            @if(class_exists('\Addons\WhatsJetDripCampaignAddon\Models\DripCampaign'))
            <a class="btn text-white btn-sm font-weight-bold mr-1 shadow-sm" href="{{ route('addon.WhatsJetDripCampaignAddon.index') }}" style="background: #8b5cf6; border-radius: 8px;">
                <i class="fas fa-clock mr-1"></i> {{ __tr('Campagnes Drip') }}
            </a>
            @endif
            <a class="btn btn-primary btn-sm font-weight-bold mr-1 shadow-sm" href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}" style="border-radius: 8px;">
                <i class="fas fa-bolt mr-1"></i> {{ __tr('Nouvelle Campagne Libre') }}
            </a>
            <a class="btn btn-success btn-sm font-weight-bold shadow-sm" href="{{ route('vendor.campaign.new.view') }}" style="border-radius: 8px; background: #10b981; border: none;">
                <i class="fas fa-plus mr-1"></i> {{ __tr('Créer une Campagne') }}
            </a>
        </div>
    </div>

    <!-- Carte Principale Léger et Ultra-Rapide -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header border-0 bg-white py-3">
            <h3 class="mb-0 font-weight-bold text-dark" style="font-size: 1.15rem;">
                <i class="fas fa-bullhorn text-success mr-2"></i>{{ $status == 'archived' ? __tr('Campagnes Archivées') : __tr('Toutes les Campagnes') }}
            </h3>
        </div>
        <div class="card-body p-0">
            <x-lw.datatable data-page-length="100" id="lwCampaignList" :url="route('vendor.campaign.read.list', ['status' => $status])">
                <th data-orderable="true" data-name="title" style="border-top: none; font-weight: 700;">{{ __tr('Titre') }}</th>
                <th data-orderable="true" data-name="template_name" style="border-top: none; font-weight: 700;">{{ __tr('Modèle / Message') }}</th>
                <th data-name="contacts_count" style="border-top: none; font-weight: 700;">{{ __tr('Contacts') }}</th>
                <th data-orderable="true" data-name="created_at" style="border-top: none; font-weight: 700;">{{ __tr('Créée le') }}</th>
                <th data-orderable="true" data-order-type="desc" data-order-by="true" data-name="scheduled_at" style="border-top: none; font-weight: 700;">{{ __tr('Programmation') }}</th>
                <th data-template="#campaignStatusColumnTemplate" name="null" style="border-top: none; font-weight: 700;">{!! __tr('Statut') !!}</th>
                <th data-template="#campaignActionColumnTemplate" name="null" style="border-top: none; font-weight: 700;" class="text-right">{!! __tr('Actions') !!}</th>
            </x-lw.datatable>
        </div>
    </div>

    <!-- Action Templates -->
    <script type="text/template" id="campaignActionColumnTemplate">
    <div class="text-right d-flex align-items-center justify-content-end" style="gap: 5px;">
        <a href="<%= __Utils.apiURL("{{ route('vendor.campaign.status.view', ['campaignUid' => 'campaignUid',]) }}", {'campaignUid': __tData._uid}) %>" 
           class="btn btn-success btn-sm font-weight-bold py-1 px-2" 
           style="background: #10b981; border: none; border-radius: 6px;"
           title="{{ __tr('Tableau de Bord') }}">
            <i class="fas fa-chart-line mr-1"></i> {{ __tr('Tableau de Bord') }}
        </a>

        <% if(__tData.status != 5) { %>
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.archive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" 
               class="btn btn-warning text-white btn-sm font-weight-bold py-1 px-2 lw-ajax-link-action shadow-xs" 
               style="border-radius: 6px;"
               title="{{ __tr('Archiver') }}" 
               data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" 
               data-callback="appFuncs.modelSuccessCallback">
                <i class="fas fa-archive mr-1"></i> {{ __tr('Archiver') }}
            </a>
        <% } else { %>
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.unarchive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" 
               class="btn btn-info text-white btn-sm font-weight-bold py-1 px-2 lw-ajax-link-action shadow-xs" 
               style="border-radius: 6px;"
               title="{{ __tr('Désarchiver') }}" 
               data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" 
               data-callback="appFuncs.modelSuccessCallback">
                <i class="fas fa-box-open mr-1"></i> {{ __tr('Désarchiver') }}
            </a>
        <% } %>

        <% if(__tData.delete_allowed) { %>
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.delete', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" 
               class="btn btn-outline-danger btn-sm py-1 px-2 lw-ajax-link-action-via-confirm" 
               style="border-radius: 6px;"
               data-confirm="#lwDeleteCampaign-template" 
               title="{{ __tr('Supprimer') }}" 
               data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" 
               data-callback="appFuncs.modelSuccessCallback">
                <i class="fas fa-trash-alt mr-1"></i> {{ __tr('Supprimer') }}
            </a>
        <% } %>

        <% if(__tData.current_status == 'PROCESSING') { %>
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.abort', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" 
               class="btn btn-danger btn-sm py-1 px-2 lw-ajax-link-action-via-confirm" 
               style="border-radius: 6px;"
               data-confirm="#lwCampaignAbort-template" 
               title="{{ __tr('Interrompre') }}" 
               data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" 
               data-callback="appFuncs.modelSuccessCallback">
                <i class="fas fa-ban mr-1"></i> {{ __tr('Interrompre') }}
            </a>
        <% } %>
    </div>
    </script>


    <script type="text/template" id="campaignStatusColumnTemplate">
    <% if(__tData.delete_allowed) { %>
        <span class="badge badge-success px-2 py-1" style="border-radius: 12px; background: #dcfce7; color: #166534;"><%- __tData.scheduled_status %></span>
    <% } else { %>
        <span class="badge badge-warning px-2 py-1" style="border-radius: 12px; background: #fef3c7; color: #b45309;"><%- __tData.scheduled_status %></span>
    <% } %>
    </script>

    <script type="text/template" id="lwDeleteCampaign-template">
        <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
        <p>{{ __tr('Voulez-vous vraiment supprimer cette campagne ?') }}</p>
    </script>

    <script type="text/template" id="lwCampaignAbort-template">
        <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
        <p>{{ __tr('Voulez-vous vraiment interrompre cette campagne ?') }}</p>
    </script>
</div>
@endsection
