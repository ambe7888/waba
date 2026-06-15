@php
if(!isset($vendorViewBySuperAdmin))
$vendorViewBySuperAdmin = false;
@endphp
@if (hasCentralAccess() and !$vendorViewBySuperAdmin )
<div class="header pb-5 pt-2 pt-md-7">
    <div class="container-fluid">
        <div class="header-body" x-cloak x-data="{totalVendors:{{ $totalVendors }},totalActiveVendors:{{ $totalActiveVendors }},totalCampaigns:{{ $totalCampaigns }},messagesInQueue:{{ $messagesInQueue }},totalContacts:{{ $totalContacts }},totalMessagesProcessed:{{ $totalMessagesProcessed }} }">
            <!-- Card stats -->
            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Vendors') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalVendors)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-store text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span>{{ __tr('Total Vendors in the system') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Active Vendors') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalActiveVendors)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-store text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Contacts') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalContacts)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-users text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-md-4">
                <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Campaigns') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalCampaigns)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-bullhorn text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages in Queue') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(messagesInQueue)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-stream text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages Processed') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalMessagesProcessed)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-tasks text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- show.dropdown.result --}}
@elseif(hasVendorAccess() or hasVendorUserAccess() or $vendorViewBySuperAdmin )
<div class="header">
    <div class="container-fluid">
        <div class="header-body">
            <!-- Card stats -->
            <div class="row">
                <div class="col-12">
                    {{-- Banner Conversations Actives --}}
                    @if (hasVendorAccess('manage_campaigns'))
                    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px !important; background: linear-gradient(135deg, #e8f5e9, #c8e6c9) !important; border-left: 6px solid #2dce89 !important;">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-success text-white rounded-circle shadow pulsing-green-icon">
                                        <i class="fas fa-gift text-white" style="font-size: 1.4rem;"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <h3 class="text-success font-weight-bold mb-1" style="font-size: 1.15rem; letter-spacing: 0.5px;">
                                        {{ __tr('Campagne Gratuite Disponible !') }} 
                                        <span class="badge badge-success ml-2 font-weight-normal px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;">
                                            {{ __tr('__count__ client(s) actif(s) en ce moment', ['__count__' => $activeContacts24hCount ?? 0]) }}
                                        </span>
                                    </h3>
                                    <p class="text-dark-50 mb-0 text-sm" style="line-height: 1.5; color: #1e4620 !important;">
                                        <strong>{{ __tr('Astuce pour économiser :') }}</strong> {{ __tr('WhatsApp vous autorise à envoyer des messages gratuitement (sans payer les frais de modèle Meta) aux clients qui vous ont écrit au cours des dernières 24 heures.') }}
                                        <br>
                                        {{ __tr('Profitez-en pour leur envoyer une diffusion instantanée (campagne de messages prédéfinis) et relancer vos ventes à 0 FCFA !') }}
                                    </p>
                                </div>
                                @if(!$vendorViewBySuperAdmin)
                                <div class="col-xl-auto col-12 mt-3 mt-xl-0 text-right">
                                    <button type="button" class="btn btn-outline-success text-success font-weight-bold mr-2 px-4 py-2 border-success" data-toggle="modal" data-target="#lwActiveContactsModal" style="border-radius: 8px !important; background-color: transparent;">
                                        <i class="fas fa-users mr-2"></i> {{ __tr('Voir les contacts') }}
                                    </button>
                                    <a href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}" class="btn btn-success text-white font-weight-bold px-4 py-2 shadow" style="border-radius: 8px !important;">
                                        <i class="fas fa-paper-plane mr-2"></i> {{ __tr('Créer ma campagne gratuite') }}
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Modal for Active Contacts -->
                    <x-lw.modal id="lwActiveContactsModal" :header="__tr('Contacts Actifs (Dernières 24h) (__count__)', ['__count__' => $activeContacts24hCount ?? 0])" modalSize="modal-md">
                        <div style="max-height: 400px; overflow-y: auto;">
                            @if(isset($activeContacts24h) && !$activeContacts24h->isEmpty())
                                <div class="list-group list-group-flush">
                                    @foreach($activeContacts24h as $activeContact)
                                        <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-success text-white rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #2dce89 !important;">
                                                    <span class="font-weight-bold" style="font-size: 0.9rem;">{{ $activeContact->name_initials }}</span>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0 text-sm font-weight-bold text-dark">{{ $activeContact->full_name }}</h4>
                                                    <small class="text-muted">+{{ $activeContact->wa_id }}</small>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="{{ route('vendor.chat_message.contact.view', ['contactUid' => $activeContact->_uid]) }}" class="btn btn-sm btn-success text-white" title="{{ __tr('Ouvrir la discussion') }}" style="border-radius: 6px !important;">
                                                    <i class="fas fa-comments"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5 px-3">
                                    <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="text-muted mb-0">{{ __tr('Aucun contact actif au cours des dernières 24 heures.') }}</p>
                                </div>
                            @endif
                        </div>
                    </x-lw.modal>
                    <style>
                    @keyframes pulse-green {
                        0% {
                            box-shadow: 0 0 0 0 rgba(45, 206, 137, 0.7);
                        }
                        70% {
                            box-shadow: 0 0 0 10px rgba(45, 206, 137, 0);
                        }
                        100% {
                            box-shadow: 0 0 0 0 rgba(45, 206, 137, 0);
                        }
                    }
                    .pulsing-green-icon {
                        animation: pulse-green 2s infinite;
                    }
                    </style>
                    @endif
                    {{-- /Banner Conversations Actives --}}

                    <div class="row mb-2">
                        @if (hasVendorAccess('manage_contacts'))
                        {{-- total contacts --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Contacts') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalContacts) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-info text-white rounded-circle shadow">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.contact.read.list_view') }}">{{  __tr('Manage Contacts') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total contacts --}}
                        @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                            {{-- total groups --}}
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                                <div class="card card-stats mb-4 mb-xl-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Groups') }}</h5>
                                                <span class="h2 font-weight-bold mb-0">{{ __tr($totalGroups) }}</span>
                                            </div>
                                            <div class="col-auto">
                                                <div
                                                    class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                            </div>
                                        </div>
                                        @if(!$vendorViewBySuperAdmin)
                                        <p class="mt-3 mb-0 text-muted text-sm">
                                            <a href="{{ route('vendor.contact.group.read.list_view') }}">{{  __tr('Manage Groups') }}</a>
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            {{-- /total groups --}}
                        @endif
                        @endif
                        @if (hasVendorAccess('manage_campaigns'))
                        {{-- total totalCampaigns --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Campaigns') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalCampaigns) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                                <i class="fa fa-bullhorn"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.campaign.read.list_view') }}">{{  __tr('Manage Campaigns') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalCampaigns --}}
                        @endif
                        @if (hasVendorAccess('manage_templates'))
                        {{-- total totalTemplates --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Templates') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalTemplates) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fa fa-layer-group"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">{{  __tr('Manage Templates') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalTemplates --}}
                        @endif
                        @if (hasVendorAccess('manage_bot_replies'))
                        {{-- total totalBotReplies --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Active Bots') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalBotReplies) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fa fa-robot"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.bot_reply.read.list_view') }}">{{  __tr('Manage Bots') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalBotReplies --}}
                        @endif
                          {{-- total active team member --}}
                          @if (hasVendorAccess('administrative'))
                          <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                             <div class="card card-stats mb-4 mb-xl-0">
                                 <div class="card-body">
                                     <div class="row">
                                         <div class="col">
                                             <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Active Team Members') }}</h5>
                                             <span class="h2 font-weight-bold mb-0">{{ __tr($activeTeamMembers) }}</span>
                                         </div>
                                         <div class="col-auto">
                                             <div
                                                 class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                                 <i class="fas fa-user-tie"></i>
                                             </div>
                                         </div>
                                     </div>
                                     @if(!$vendorViewBySuperAdmin)
                                     <p class="mt-3 mb-0 text-muted text-sm">
                                         <a href="{{ route('vendor.user.read.list_view') }}">{{  __tr('Manage Team Member') }}</a>
                                     </p>
                                     @endif
                                 </div>
                             </div>
                         </div>
                         @endif
                         {{-- /total active team member --}}
                          {{-- manage campaigns --}}
                        @if (hasVendorAccess('manage_campaigns'))
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages in Queue') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($messagesInQueue) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-stream text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        {{-- /manage campaigns --}}
                         {{-- Messaging Processed--}}
                        @if (hasVendorAccess('messaging'))
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages Processed') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalMessagesProcessed) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-tasks text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                         {{-- /Messaging Processed --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif