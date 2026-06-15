@php
/**
* Component : Campaign
* Controller : CampaignController
* File : campaign.list.blade.php
* -----------------------------------------------------------------------------
*/
@endphp
@extends('layouts.app', ['title' => __tr('Templates')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Templates'),
'description' => '',
'class' => 'col-lg-7'
])

<?php $status = request()->status ?? 'active'; ?>
<div class="container-fluid mt-lg--6">
    <div class="row" x-data="{isAdvanceBot:'interactive',botFlowUid:null,ntCampaignPresetMessage:'yes'}">
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"
                            aria-expanded="false">
                            {{ __tr('Add New Preset Message') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a type="button" @click="isAdvanceBot = 'simple'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Simple Preset Message')
                                }}</a>
                            <a type="button" @click="isAdvanceBot = 'media'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Media Preset Message')
                                }}</a>
                            <a type="button" @click="isAdvanceBot = 'interactive'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Advance Interactive Preset Message') }}</a>
                        </div>
                    </div>
                @endif
                @if(hasVendorAccess('manage_campaigns'))
                    <a class="lw-btn btn btn-secondary " href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}">{{ __tr('Create New Non-Template Campaign') }}</a>
                @endif
            </div>
        </div>
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <x-lw.help-modal :subject="__tr('What are Preset Messages?')">
                    <h3>{{  __tr('About Preset Messages') }}</h3>
                    <p>{{  __tr('Preset Messages are reusable messages that can be used in campaigns as templates. You can create and manage your preset messages here. Preset messages can only be delivered to 24 hours service window opened contacts.') }}</p>
                </x-lw.help-modal>
            </div>
        </div>
        @if(!hasVendorAccess('manage_templates', 'add_edit_templates') and !hasVendorAccess('manage_campaigns'))
            <div class="col-xl-12 mb-3"></div>
        @endif
        <!--/ button -->
       <ul class="nav nav-tabs">
        <!-- Active tab -->
            <li class="nav-item">
                <a class="nav-link {{ markAsActiveLink('vendor.whatsapp_service.templates.read.list_view') }}" data-title="{{ __tr('Active') }}" href="<?= route('vendor.whatsapp_service.templates.read.list_view') ?>">
                    <?= __tr('WhatsApp Templates') ?>
                </a>
            </li>
            <!-- /Active tab -->

            <li class="nav-item">
                <a class="nav-link {{ markAsActiveLink('vendor.campaign.read.non_template_list_view') }}" data-title="{{ __tr('Preset Messages') }}" href="<?= route('vendor.campaign.read.non_template_list_view') ?>">
                    <?= __tr('Preset Messages') ?>
                </a>
            </li>
        </ul>
        <div class="col-xl-12">
            {{-- Visual Preset Message Types Cards --}}
            @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
            <div class="mt-4 mb-2">
                <h3 class="font-weight-bold text-dark" style="font-size: 1.15rem; letter-spacing: 0.3px;">
                    <i class="fas fa-eye text-success mr-2"></i>{{ __tr('Aperçu des modèles et types de messages') }}
                </h3>
                <p class="text-muted text-xs mb-0">
                    {{ __tr('Choisissez le type de message prédéfini qui correspond le mieux à votre besoin pour commencer sa création.') }}
                </p>
            </div>
            <div class="row mb-4">
                <!-- Simple Preset Card -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 12px !important; transition: transform 0.2s; background: white;">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape bg-soft-primary text-primary rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(43, 172, 50, 0.1) !important; color: #2bac32 !important;">
                                    <i class="fas fa-comment-alt" style="font-size: 1.2rem;"></i>
                                </div>
                                <h3 class="font-weight-bold text-dark mb-0" style="font-size: 1rem;">{{ __tr('Message Simple') }}</h3>
                            </div>
                            <p class="text-muted text-xs mb-3 flex-grow-1" style="line-height: 1.5;">
                                {{ __tr('Message texte standard idéal pour relancer une conversation, demander un retour, ou saluer vos clients.') }}
                            </p>
                            <!-- Visual Mockup -->
                            <div class="p-3 mb-3" style="background: #efeae2; border-radius: 8px; font-size: 0.75rem; border: 1px solid #e2ddd5;">
                                <div class="bg-white p-2 text-dark shadow-sm position-relative" style="border-radius: 6px; max-width: 90%;">
                                    {{ __tr('Bonsoir, comment avez vous trouvez nos echange aujoudhui, ou est ce uqe vous avez reçu vote colis, etc.....') }}
                                    <small class="text-muted d-block text-right mt-1" style="font-size: 0.6rem;">12:00</small>
                                </div>
                            </div>
                            <a type="button" @click="isAdvanceBot = 'simple'" data-response-template="#lwAddBotReplyBody" class="btn btn-success btn-block text-white font-weight-bold shadow-sm lw-ajax-link-action mt-auto" href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply" style="border-radius: 8px !important;">
                                <i class="fas fa-plus mr-1"></i> {{ __tr('Créer') }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Media Preset Card -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 12px !important; transition: transform 0.2s; background: white;">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape bg-soft-success text-success rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(43, 172, 50, 0.1) !important; color: #2bac32 !important;">
                                    <i class="fas fa-file-image" style="font-size: 1.2rem;"></i>
                                </div>
                                <h3 class="font-weight-bold text-dark mb-0" style="font-size: 1rem;">{{ __tr('Message Média') }}</h3>
                            </div>
                            <p class="text-muted text-xs mb-3 flex-grow-1" style="line-height: 1.5;">
                                {{ __tr('Envoyez des images (reçus, preuves d\'envoi) ou des catalogues PDF pour faire un suivi de commande.') }}
                            </p>
                            <!-- Visual Mockup -->
                            <div class="p-3 mb-3" style="background: #efeae2; border-radius: 8px; font-size: 0.75rem; border: 1px solid #e2ddd5;">
                                <div class="bg-white p-2 text-dark shadow-sm position-relative" style="border-radius: 6px; max-width: 90%;">
                                    <div class="text-center bg-light p-2 mb-1 text-muted" style="border-radius: 4px; border: 1px dashed #ccc;">
                                        <i class="fas fa-image" style="font-size: 1rem;"></i><br><small>{{ __tr('[📷 Photo du colis / Reçu]') }}</small>
                                    </div>
                                    {{ __tr('Bonjour, voici la photo de votre colis en cours d\'expédition. Est-il conforme ?') }}
                                    <small class="text-muted d-block text-right mt-1" style="font-size: 0.6rem;">12:00</small>
                                </div>
                            </div>
                            <a type="button" @click="isAdvanceBot = 'media'" data-response-template="#lwAddBotReplyBody" class="btn btn-success btn-block text-white font-weight-bold shadow-sm lw-ajax-link-action mt-auto" href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply" style="border-radius: 8px !important;">
                                <i class="fas fa-plus mr-1"></i> {{ __tr('Créer') }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Interactive Preset Card -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 12px !important; transition: transform 0.2s; background: white;">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape bg-soft-info text-info rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(43, 172, 50, 0.1) !important; color: #2bac32 !important;">
                                    <i class="fas fa-tasks" style="font-size: 1.2rem;"></i>
                                </div>
                                <h3 class="font-weight-bold text-dark mb-0" style="font-size: 1rem;">{{ __tr('Interactif Avancé') }}</h3>
                            </div>
                            <p class="text-muted text-xs mb-3 flex-grow-1" style="line-height: 1.5;">
                                {{ __tr('Ajoutez des boutons rapides (ex: Oui/Non, Programmer) et des médias pour obtenir des confirmations instantanées de vos clients.') }}
                            </p>
                            <!-- Visual Mockup -->
                            <div class="p-3 mb-3" style="background: #efeae2; border-radius: 8px; font-size: 0.75rem; border: 1px solid #e2ddd5;">
                                <div class="bg-white p-2 text-dark shadow-sm position-relative mb-1" style="border-radius: 6px; max-width: 90%;">
                                    <div class="text-center bg-light p-2 mb-1 text-muted" style="border-radius: 4px; border: 1px dashed #ccc;">
                                        <i class="fas fa-image" style="font-size: 0.9rem;"></i><br><small style="font-size: 0.6rem;">{{ __tr('[Média optionnel]') }}</small>
                                    </div>
                                    {{ __tr('Votre commande a été expédiée. Souhaitez-vous être livré aujourd\'hui ?') }}
                                    <small class="text-muted d-block text-right mt-1" style="font-size: 0.6rem;">12:00</small>
                                </div>
                                <div class="text-center bg-white py-1 px-2 text-primary shadow-sm mb-1" style="border-radius: 6px; font-size: 0.7rem; border: 1px solid #eee; font-weight: bold; cursor: default;">
                                    🚚 {{ __tr('Oui, livrer aujourd\'hui') }}
                                </div>
                                <div class="text-center bg-white py-1 px-2 text-primary shadow-sm" style="border-radius: 6px; font-size: 0.7rem; border: 1px solid #eee; font-weight: bold; cursor: default;">
                                    🗓️ {{ __tr('Choisir un autre jour') }}
                                </div>
                            </div>
                            <a type="button" @click="isAdvanceBot = 'interactive'" data-response-template="#lwAddBotReplyBody" class="btn btn-success btn-block text-white font-weight-bold shadow-sm lw-ajax-link-action mt-auto" href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply" style="border-radius: 8px !important;">
                                <i class="fas fa-plus mr-1"></i> {{ __tr('Créer') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <x-lw.datatable data-page-length="100" id="lwBotReplyList" :url="route('vendor.campaign.read.non_template_message_preset_list', ['status' => $status])">
                <th data-orderable="true" data-name="name">{{ __tr('Name') }}</th>
                <th data-name="bot_type">{{ __tr('Message Type') }}</th>
                <th data-orderable="true" data-name="created_at">{{ __tr('Created At') }}</th>
                <th data-template="#botReplyActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
        <!-- action template -->
        <script type="text/template" id="campaignActionColumnTemplate">
        <a href="<%= __Utils.apiURL("{{ route('vendor.campaign.status.view', ['campaignUid' => 'campaignUid',]) }}", {'campaignUid': __tData._uid}) %>" class="btn btn-dark btn-sm" title="{{ __tr('Campaign Details') }}"><i class="fa fa-tachometer"></i> {{  __tr('Campaign Dashboard') }}</a>
<!--  Delete Action -->
<% if(__tData.delete_allowed) { %>
<a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.delete', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteCampaign-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
<% } else { %>
    <!--  Archived button -->
    <% if(__tData.status != 5) { %>
        <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.archive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action" title="{{ __tr('Archive') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback">{{  __tr('Archive') }}</a>
        <% } else { %>
             <!--  /Archived button -->
            <!--  UnArchived button -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.unarchive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action" title="{{ __tr('Unarchive') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback">{{  __tr('Unarchive') }}</a>

            <% } %>
               <!--  /UnArchived button -->

    <% } %>
    </script>
        <!-- /action template -->
        <!-- action template -->
        <script type="text/template" id="campaignStatusColumnTemplate">
<!--  status -->
<% if(__tData.delete_allowed) { %>
    <span class="badge badge-success"><%- __tData.scheduled_status %></span>
<% } else { %>
    <span class="badge badge-warning p-2"><%- __tData.scheduled_status %></span>
<% } %>
    </script>
    <!-- /status template -->

    <!-- Campaign delete template -->
    <script type="text/template" id="lwDeleteCampaign-template">
        <h2>{{ __tr('Are You Sure!') }}</h2>
        <p>{{ __tr('You want to delete this Campaign?') }}</p>
    </script>
    <!-- /Campaign delete template -->
    @include('bot-reply.bot-forms-partial')
    <!-- action template -->
    <script type="text/template" id="botReplyActionColumnTemplate">
        @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Edit') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditBotReplyBody" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.read.update.data', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditBotReply"><i class="fa fa-edit"></i> {{  __tr('Edit') }}</a>
        @endif
        @if(hasVendorAccess('manage_templates', 'delete_templates'))
        <!--  Delete Action -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.write.delete', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteBotReply-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwBotReplyList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
        @endif
        @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
        <!--  Duplicate Action -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.write.duplicate', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>" class="btn btn-light btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDuplicateBotReply-template" title="{{ __tr('Duplicate') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwBotReplyList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-copy"></i> {{  __tr('Duplicate') }}</a>
        @endif
    </script>
    <!-- /action template -->

    <!-- Preset Message delete template -->
    <script type="text/template" id="lwDeleteBotReply-template">
        <h2>{{ __tr('Are You Sure!') }}</h2>
        <p>{{ __tr('You want to delete this Preset Message?') }}</p>
    </script>
        <!-- /Preset Message delete template -->

         <!-- Preset Message duplicate template -->
        <script type="text/template" id="lwDuplicateBotReply-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to duplicate this Preset Message?') }}</p>
    </script>
        <!-- /Preset Message duplicate template -->
    </div>
</div>
@endsection()