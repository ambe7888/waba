@extends('layouts.app', ['title' => __tr('Statut de la Campagne')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Tableau de Bord de la Campagne'),
    'description' => __tr('Consultez les statistiques d\'envoi en temps réel et les performances de votre campagne.'),
])
@php
$campaignData = $campaign->__data;
$selectedGroups = Arr::get($campaignData, 'selected_groups', []);
$isRestrictByTemplateContactLanguage = Arr::get($campaignData, 'is_for_template_language_only');
$isAllContacts = Arr::get($campaignData, 'is_all_contacts');
$campaignUid=$campaign->_uid;
@endphp

<div class="container-fluid pt-4 pb-5 lw-campaign-window-{{ $campaign->_uid }}" x-cloak x-data="initialRequiredData">
    <div class="row" x-data="{ failedCampaignType: '', modalHeader: '', campaignId: '{{ $campaign->_id }}', recampaignType: '' }">
        <!-- Boutons d'Action Principaux -->
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
            <div>
                <a class="btn btn-outline-secondary font-weight-bold shadow-xs" href="{{ route('vendor.campaign.read.list_view') }}" style="border-radius: 10px; padding: 8px 18px;">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __tr('Retour aux Campagnes') }}
                </a>
            </div>
            <div class="d-flex align-items-center" style="gap: 10px;">
                <a class="btn text-white font-weight-bold shadow-sm" href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; border-radius: 10px; padding: 8px 18px; font-size: 0.88rem;">
                    <i class="fas fa-bolt mr-2"></i>{{ __tr('Nouvelle Campagne Libre') }}
                </a>
                <a class="btn text-white font-weight-bold shadow-sm" href="{{ route('vendor.campaign.new.view') }}" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; padding: 8px 20px; font-size: 0.88rem;">
                    <i class="fas fa-plus-circle mr-2"></i>{{ __tr('Créer une Campagne') }}
                </a>
            </div>
        </div>

        <!-- Carte Principale d'Information de la Campagne -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
                <div class="card-header border-0 pt-4 pb-3 d-flex justify-content-between align-items-center flex-wrap" style="background: #ecfdf5; border-bottom: 2px solid #a7f3d0;">
                    <div>
                        <span class="text-uppercase font-weight-bold" style="color: #059669; font-size: 0.8rem; letter-spacing: 0.5px;">{{ __tr('Titre de la Campagne') }}</span>
                        <h2 class="font-weight-bold mb-0" style="color: #065f46; font-size: 1.5rem;">{{ $campaign->title }}</h2>
                    </div>
                    <div class="d-flex align-items-center mt-2 mt-sm-0" style="gap: 8px;">
                        <span class="badge font-weight-bold px-3 py-2 text-white shadow-xs" style="border-radius: 20px; font-size: 0.85rem; background: #10b981;" x-text="statusText"></span>
                        @if($campaign->status == 5) 
                            <span class="badge font-weight-bold px-3 py-2 badge-dark text-white" style="border-radius: 20px; font-size: 0.85rem;">{{ __tr('Archivée') }}</span> 
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <template x-if="campaignStatus == 'executed'">
                                <div class="p-3 mb-3 rounded-lg" style="background: #f0fdf4; border-left: 4px solid #10b981; color: #166534; font-size: 0.92rem;">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {!! __tr('L\'exécution complète a pris __time__ à partir de l\'heure programmée (incluant les réessais).', [
                                        '__time__' => '<strong x-text="timeTookFromScheduledAtFormatted"></strong>'
                                    ]) !!}
                                </div>
                            </template>
                            <template x-if="campaignStatus == 'processing'">
                                <div class="p-3 mb-3 rounded-lg" style="background: #fefce8; border-left: 4px solid #f59e0b; color: #854d0e; font-size: 0.92rem;">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    {!! __tr('Envois en cours depuis __time__, __contactsInQueue__ contact(s) encore en file d\'attente.', [
                                        '__time__' => '<strong x-text="timeTookFromScheduledAtFormatted"></strong>',
                                        '__contactsInQueue__' => '<strong x-text="__Utils.formatAsLocaleNumber(inQueuedCount)"></strong>'
                                    ]) !!}
                                </div>
                            </template>

                            <div class="mb-3">
                                <span class="font-weight-bold text-dark d-block mb-1" style="font-size: 0.95rem;">
                                    <i class="fas fa-calendar-alt text-success mr-2"></i>{{ __tr('Exécution programmée le :') }}
                                </span>
                                @if ($campaign->timezone and getVendorSettings('timezone') != $campaign->timezone)
                                    <div class="text-dark font-weight-bold">{!! __tr('__scheduledAt__ (selon votre fuseau horaire : __selectedTimezone__)', [
                                        '__scheduledAt__' => formatDateTime($campaign->scheduled_at),
                                        '__selectedTimezone__' => '<strong class="text-success">'. getVendorSettings('timezone') .'</strong>'
                                    ]) !!}</div>
                                @else
                                    <span class="h4 font-weight-bold text-dark mb-0">{{ formatDateTime($campaign->scheduled_at) }}</span>
                                @endif

                                @if(data_get($campaign->__data, 'send_message_via_marketing_message_api'))
                                    <small class="d-block text-muted mt-1"><i class="fas fa-paper-plane mr-1 text-primary"></i> {{ __tr('Canal : Marketing Messages API (Marketing Lite)') }}</small>
                                @else
                                    <small class="d-block text-muted mt-1"><i class="fab fa-whatsapp mr-1 text-success"></i> {{ __tr('Canal : WhatsApp Cloud API') }}</small>
                                @endif
                            </div>

                            <template x-if="campaignStatus == 'processing'">
                                <a class="lw-ajax-link-action-via-confirm btn btn-danger font-weight-bold shadow-sm" data-confirm="#lwCampaignAbort-template" data-method="post" href="{{ route('vendor.campaign.write.abort', ['campaignIdOrUid' => $campaign->_uid]) }}" data-callback="__Utils.viewReload" style="border-radius: 8px;">
                                    <i class="fas fa-ban mr-1"></i> {{ __tr('Interrompre la Campagne') }}
                                </a>
                            </template>

                            @if ($campaign->scheduled_at > now())
                                <div class="badge badge-warning p-2 font-weight-bold" style="font-size: 0.85rem;"><i class="fas fa-clock mr-1"></i> {{ formatDiffForHumans($campaign->scheduled_at, 3) }}</div>
                            @else
                                <template x-if="(executedCount == 0) && inQueuedCount && !totalFailed">
                                    <div class="text-warning font-weight-bold my-2">
                                        {{ __tr('En attente d\'exécution par le serveur') }} <i class="fas fa-circle-notch fa-spin ml-1"></i>
                                    </div>
                                </template>
                            @endif

                            @if(!__isEmpty(data_get($campaign, '__data.expiry_at')))
                                <div class="text-danger font-weight-bold mt-2" style="font-size: 0.88rem;">
                                    <i class="fas fa-hourglass-end mr-1"></i> {{ __tr('Expire le :') }} {{ formatDateTime(data_get($campaign, '__data.expiry_at')) }}
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-5 border-left pl-lg-4 mt-3 mt-lg-0">
                            @if ($campaign->template_name)
                                <div class="p-3 mb-2 rounded-lg" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <span class="text-uppercase text-muted font-weight-bold d-block" style="font-size: 0.75rem;">{{ __tr('Modèle Meta WhatsApp') }}</span>
                                    <span class="h4 font-weight-bold text-dark mb-0"><i class="fab fa-whatsapp text-success mr-2"></i>{{ $campaign->template_name }}</span>
                                    <small class="badge badge-secondary ml-2 font-weight-bold">{{ $campaign->template_language }}</small>
                                </div>
                            @endif
                            @if ($campaign->__data['preset_message_name'] ?? null)
                                <div class="p-3 rounded-lg" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <span class="text-uppercase text-muted font-weight-bold d-block" style="font-size: 0.75rem;">{{ __tr('Message Pré-enregistré (Bot)') }}</span>
                                    <span class="h4 font-weight-bold text-dark mb-0"><i class="fas fa-robot text-primary mr-2"></i>{{ $campaign->__data['preset_message_name'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille des Statistiques KPI -->
        <div class="col-12 mb-4">
            <div class="row" style="row-gap: 16px;">
                {{-- Total Contacts --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('Total Contacts') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="totalContacts"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #eff6ff; color: #2563eb;">
                                    <i class="fas fa-users" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top">
                                @if ($isAllContacts)
                                    <span class="font-weight-bold text-dark">{{ __tr('Tous les contacts') }}</span>
                                @elseif(isset($campaign->__data['audience_title']))
                                    <span class="font-weight-bold text-primary">{{ __tr('Audience : ') }}{{ $campaign->__data['audience_title'] }}</span>
                                @else
                                    <span class="text-truncate d-block">{{ __tr('Groupes cibles') }}</span>
                                @endif

                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="mt-2 text-right">
                                        <button type="button" x-on:click="recampaignType = 'total'; $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour tous les contacts') }}');" :disabled="!['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-primary font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Delivered --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('Total Livrés') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="totalDeliveredInPercent"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #e0f2fe; color: #0284c7;">
                                    <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(totalDelivered)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'delivered', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour les contacts livrés') }}')" :disabled="totalDelivered == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-info font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Read --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('Total Lus') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="totalReadInPercent"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #ecfdf5; color: #10b981;">
                                    <i class="fas fa-check-double" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(totalRead)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'read', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour les contacts lus') }}')" :disabled="totalRead == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-success font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Failed --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('Total Échecs') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="__Utils.formatAsLocaleNumber(totalFailedInPercent)"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #fef2f2; color: #ef4444;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(totalFailed)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'failed', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour les échecs') }}')" :disabled="totalFailed == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-danger font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Expired --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('Total Expirés') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="__Utils.formatAsLocaleNumber(totalExpiredInPercent)"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #fff7ed; color: #f97316;">
                                    <i class="fas fa-clock" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(expiredCount)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'expired', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour les expirés') }}')" :disabled="expiredCount == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-warning font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Sent --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('En Cours d\'Envoi') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="__Utils.formatAsLocaleNumber(totalSentInPercent)"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #f3e8ff; color: #a855f7;">
                                    <i class="fas fa-paper-plane" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(totalSent)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'sent', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour les envois') }}')" :disabled="totalSent == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-purple font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- In Queue --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem;">{{ __tr('En File d\'Attente') }}</span>
                                    <h2 class="font-weight-bold text-dark mb-0 mt-1" x-text="__Utils.formatAsLocaleNumber(totalInQueueInPercent)"></h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px; background: #f1f5f9; color: #475569;">
                                    <i class="fas fa-hourglass-half" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="text-muted text-sm pt-2 border-top d-flex justify-content-between align-items-center">
                                <span><strong x-text="__Utils.formatAsLocaleNumber(inQueueCount)"></strong> {{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" x-on:click="recampaignType = 'in_queue', $dispatch('set-modal-title', '{{ __tr('Créer un groupe pour la file d\'attente') }}')" :disabled="inQueueCount == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="btn btn-xs btn-outline-secondary font-weight-bold" style="border-radius: 6px;">{{ __tr('Relancer') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section des Journaux de Logs (Tabs) -->
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 bg-white pt-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                        <!-- Onglets du Log -->
                        <ul class="nav nav-pills p-1" id="myTab" role="tablist" style="background: #f1f5f9; border-radius: 12px; gap: 4px;">
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold px-4 py-2 <?= $pageType == 'queue' ? 'active' : '' ?>" 
                                   style="border-radius: 9px; font-size: 0.88rem; <?= $pageType == 'queue' ? 'background: #10b981; color: white;' : 'color: #64748b;' ?>" 
                                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'queue', 'logStatus' => 'all']) ?>#logData">
                                    <i class="fas fa-list-ol mr-2"></i><?= __tr('File d\'attente') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold px-4 py-2 <?= $pageType == 'executed' ? 'active' : '' ?>" 
                                   style="border-radius: 9px; font-size: 0.88rem; <?= $pageType == 'executed' ? 'background: #10b981; color: white;' : 'color: #64748b;' ?>" 
                                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'executed', 'logStatus' => 'all']) ?>#logData">
                                    <i class="fas fa-check-circle mr-2"></i><?= __tr('Exécutés') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold px-4 py-2 <?= $pageType == 'expired' ? 'active' : '' ?>" 
                                   style="border-radius: 9px; font-size: 0.88rem; <?= $pageType == 'expired' ? 'background: #10b981; color: white;' : 'color: #64748b;' ?>" 
                                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'expired']) ?>#logData">
                                    <i class="fas fa-clock mr-2"></i><?= __tr('Expirés') ?>
                                </a>
                            </li>
                        </ul>

                        <!-- Actions des Logs (Réactualiser / Rapports) -->
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            @if (($pageType == "queue") and ($campaign->status == 1))
                                <template x-if="campaignStatus == 'executed' && (queueFailedCount > 0)">
                                    <a class="btn btn-warning btn-sm font-weight-bold lw-ajax-link-action" data-confirm="#requeueFailedMessageConfirm-template" data-method="post" href="{{ route('vendor.campaign.requeue.log.write.failed', ['campaignUid' => $campaignUid]) }}" style="border-radius: 8px;">
                                        <i class="fas fa-redo-alt mr-1"></i> {{ __tr('Réessayer les échecs') }}
                                    </a>
                                </template>
                            @endif
                            
                            <button @click="window.reloadDT('#lwCampaignQueueLog');" class="btn btn-outline-secondary btn-sm font-weight-bold" style="border-radius: 8px;">
                                <i class="fas fa-sync-alt mr-1"></i> {{ __tr('Actualiser') }}
                            </button>

                            @if($campaignStatus == "executed")
                                @if($pageType == "queue")
                                    <a href="{{ route('vendor.campaign.queue.log.report.write', ['campaignUid' => $campaignUid]) }}" data-method="post" class="btn btn-dark btn-sm font-weight-bold" style="border-radius: 8px;">
                                        <i class="fas fa-download mr-1"></i> {{ __tr('Télécharger Rapport') }}
                                    </a>
                                @elseif($pageType == "executed")
                                    <a href="{{ route('vendor.campaign.executed.report.write', ['campaignUid' => $campaignUid]) }}" data-method="post" class="btn btn-dark btn-sm font-weight-bold" style="border-radius: 8px;">
                                        <i class="fas fa-download mr-1"></i> {{ __tr('Télécharger Rapport') }}
                                    </a>
                                @elseif($pageType == "expired")
                                    <a href="{{ route('vendor.campaign.expired.log.report.write', ['campaignUid' => $campaignUid]) }}" data-method="post" class="btn btn-dark btn-sm font-weight-bold" style="border-radius: 8px;">
                                        <i class="fas fa-download mr-1"></i> {{ __tr('Télécharger Rapport') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 lw-nested-nav-tabs-container" id="logData">
                    @if($pageType == "queue")
                        @include('whatsapp.campaign-queue-log-partial')
                    @elseif($pageType == "executed")
                        @include('whatsapp.campaign-executed-log-partial')
                    @elseif($pageType == "expired")
                        @include('whatsapp.campaign-expired-log-partial')
                    @endif
                </div>
            </div>
        </div>

        <script type="text/template" id="requeueFailedMessageConfirm-template">
            <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
            <p>{{ __tr('Voulez-vous remettre en file d\'attente tous les messages échoués pour les retraiter ?') }}</p>
        </script>
    </div>

    <!-- Modale Ajout de Groupe -->
    <x-lw.modal id="lwAddNewGroup" :header="__tr('Créer un nouveau groupe de contacts')" :hasForm="true">
        <x-lw.form id="lwAddNewGroupForm" :action="route('vendor.contact.group.write.create')"
            :data-callback-params="['modalId' => '#lwAddNewGroup', 'datatableId' => '#lwGroupList']"
            data-callback="appFuncs.modelSuccessCallback">

                <!-- form body -->
                <div class="lw-form-modal-body">

                    <input type="hidden" x-model="failedCampaignType" name="failed_campaign_type">
                    <input type="hidden" x-model="recampaignType" name="recampaign_type">
                    <input type="hidden" x-model="campaignId" name="campaign_id">

                    <div class="alert alert-warning">
                        {{  __tr('The new group only will be created for contacts available.') }}
                    </div>

                    <!-- form fields form fields -->
                    <!-- Title -->
                    <x-lw.input-field type="text" id="lwTitleField" data-form-group-class="" :label="__tr('Title')" name="title" required="true" />
                    <!-- /Title -->
                    <!-- Description -->
                    <div class="form-group">
                        <label for="lwDescriptionField">{{ __tr('Description') }}</label>
                        <textarea cols="10" rows="3" id="lwDescriptionField" class="lw-form-field form-control"
                            placeholder="{{ __tr('Description') }}" name="description"></textarea>
                    </div>
                    <!-- /Description -->
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
            </x-lw.form>
            <!--/  Add New Group Form -->
        </x-lw.modal>
        <!--/ Add New Group Modal -->
    </div>
</div>
@php
$totalContacts = (int) Arr::get($campaignData, 'total_contacts');
@endphp

<!-- Campaign abort template -->
<script type="text/template" id="lwCampaignAbort-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to abort this campaign?') }}</p>
</script>
<!-- /Campaign abort template -->

<script>
    (function() {
        'use strict';
        document.addEventListener('alpine:init', () => {
            Alpine.data('initialRequiredData', () => ({
                timeTookFromScheduledAtFormatted:'',
                totalContacts:'{{ __tr($totalContacts) }}',
                totalDeliveredInPercent:'',
                totalDelivered:'',
                totalRead:'',
                totalReadInPercent:'',
                totalFailed:'',
                totalFailedInPercent:'',
                executedCount:'',
                inQueuedCount:"",
                statusText:'',
                campaignStatus:'',
                queueFailedCount:'',
                expiredCount:'',
                totalExpiredInPercent:'',
                totalSent: '',
                totalSentInPercent: '',
                inQueueCount: '',
                totalInQueueInPercent: '',
                acceptedCount: '',
                totalAcceptedInPercent: ''
            }));
        });
    })();
</script>
@push('appScripts')
<script>
(function($) {
    'use strict';
    // initial request
    __DataRequest.get("{{ route('vendor.campaign.status.data', ['campaignUid' => $campaignUid ]) }}");

    $('#lwAddNewGroup').on('hidden.bs.modal', function (hiddenEvent) {
        var $targetForm = $('#lwAddNewGroupForm');
        $targetForm[0].reset();
        $targetForm.find('input[name="failed_campaign_type"]').val('');
        $targetForm.find('input[name="recampaign_type"]').val('');
        var validator = $targetForm.validate();
        validator.resetForm();
    });
})(jQuery);
</script>
@endpush
@endsection()