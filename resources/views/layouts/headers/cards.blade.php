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
                    {{-- Banner Conversations Actives (Refined & Non-intrusive) --}}
                    @if (hasVendorAccess('manage_campaigns') && ($activeContacts24hCount ?? 0) > 0)
                    <div class="alert alert-dismissible fade show mb-4 p-3 border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.08)) !important; border-left: 4px solid #10b981 !important; border-radius: 12px !important;">
                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-3 shadow-sm pulsing-green-icon" style="width: 36px; height: 36px; background-color: #10b981 !important; flex-shrink: 0;">
                                <i class="fas fa-bolt" style="font-size: 0.95rem;"></i>
                            </div>
                            <div>
                                <span class="font-weight-bold text-dark" style="font-size: 0.92rem;">
                                    <i class="fas fa-gift text-success mr-1"></i> {{ __tr('Campagne Gratuite Disponible !') }}
                                </span>
                                <span class="badge badge-success font-weight-bold ml-2 px-2 py-1" style="font-size: 0.72rem; border-radius: 6px; background-color: #10b981;">
                                    {{ __tr('__count__ client(s) actif(s) 24h (0 FCFA)', ['__count__' => $activeContacts24hCount ?? 0]) }}
                                </span>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem; line-height: 1.3;">
                                    {{ __tr('Envoyez une diffusion sans frais Meta aux clients qui vous ont écrit ces dernières 24h.') }}
                                </p>
                            </div>
                        </div>
                        @if(!$vendorViewBySuperAdmin)
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold mr-2 px-3 py-1 border-success" data-toggle="modal" data-target="#lwActiveContactsModal" style="border-radius: 6px !important; font-size: 0.8rem;">
                                <i class="fas fa-users mr-1"></i> {{ __tr('Voir contacts') }}
                            </button>
                            <a href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}" class="btn btn-sm btn-success text-white font-weight-bold px-3 py-1 shadow-sm mr-3" style="border-radius: 6px !important; background-color: #10b981; border-color: #10b981; font-size: 0.8rem;">
                                <i class="fas fa-paper-plane mr-1"></i> {{ __tr('Lancer (0 FCFA)') }}
                            </a>
                            <button type="button" class="close position-relative p-0 text-muted ml-2" data-dismiss="alert" aria-label="Close" style="font-size: 1.25rem; line-height: 1; outline: none;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif
                    </div>
                    <!-- Modal for Active Contacts -->
                    <x-lw.modal id="lwActiveContactsModal" :header="__tr('Contacts Actifs (Dernières 24h) (__count__)', ['__count__' => $activeContacts24hCount ?? 0])" modalSize="modal-md">
                        <div style="max-height: 400px; overflow-y: auto;">
                            @if(isset($activeContacts24h) && !$activeContacts24h->isEmpty())
                                <div class="list-group list-group-flush">
                                    @foreach($activeContacts24h as $activeContact)
                                        <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-success text-white rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #10b981 !important;">
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
                    @endif
                    
                    <style>
                    @keyframes pulse-green {
                        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
                        70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
                    }
                    .pulsing-green-icon { animation: pulse-green 2s infinite; }
                    .card-stats-pro {
                        border-radius: 14px !important;
                        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04) !important;
                        border: 1px solid rgba(226, 232, 240, 0.8) !important;
                        transition: all 0.25s ease !important;
                        background: #ffffff !important;
                    }
                    .card-stats-pro:hover {
                        transform: translateY(-3px) !important;
                        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08) !important;
                    }
                    </style>
                    {{-- /Banner Conversations Actives --}}

                    <div class="row mb-4">
                        {{-- Orders Card --}}
                        @php
                            $hasEcommerce = vendorPlanDetails('ecommerce_catalog', 1)['is_limit_available'];
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            @if ($hasEcommerce)
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Commandes') }}</h5>
                                            <div class="d-flex align-items-baseline">
                                                <span class="h2 font-weight-bold mb-0 text-success mr-2" style="font-size: 1.7rem; font-weight: 800;">{{ $ordersTodayCount ?? 0 }}</span>
                                                @if(($ordersDiffPercent ?? 0) > 0)
                                                    <span class="badge badge-success font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-up mr-1"></i>+{{ $ordersDiffPercent }}%</span>
                                                @elseif(($ordersDiffPercent ?? 0) < 0)
                                                    <span class="badge badge-danger font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-down mr-1"></i>{{ $ordersDiffPercent }}%</span>
                                                @else
                                                    <span class="badge badge-secondary text-muted font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}">= 0%</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #10b981, #059669) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-shopping-basket" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-xs">
                                        <span class="font-weight-bold text-dark">{{ __tr('Aujourd\'hui: __today__', ['__today__' => $ordersTodayCount ?? 0]) }}</span>
                                        <span class="text-muted ml-1">{{ __tr('| Hier: __yesterday__', ['__yesterday__' => $ordersYesterdayCount ?? 0]) }}</span>
                                        <br>
                                        <a href="{{ route('vendor.settings.read', ['pageType' => 'orders']) }}" class="text-success font-weight-bold mt-1 d-inline-block"><i class="fas fa-shopping-cart mr-1"></i> {{ __tr('Gérer les commandes') }}</a>
                                    </p>
                                </div>
                            </div>
                            @else
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0" style="opacity: 0.7; background-color: rgba(248, 249, 250, 0.85) !important;">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Commandes') }}</h5>
                                            <span class="h2 font-weight-bold mb-0 text-muted" style="font-size: 1.7rem;">0</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: #adb5bd !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-shopping-basket" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-sm">
                                        <span class="text-danger font-weight-bold" style="font-size: 0.78rem; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-lock mr-1"></i> {{ __tr('Disponible uniquement dans le plan E-Commerce Pro') }}</span>
                                    </p>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Messages Received Today --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr("Reçus Aujourd'hui") }}</h5>
                                            <div class="d-flex align-items-baseline">
                                                <span class="h2 font-weight-bold mb-0 text-info mr-2" style="font-size: 1.7rem; font-weight: 800;">{{ $messagesReceivedTodayCount ?? 0 }}</span>
                                                @if(($messagesReceivedDiffPercent ?? 0) > 0)
                                                    <span class="badge badge-success font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-up mr-1"></i>+{{ $messagesReceivedDiffPercent }}%</span>
                                                @elseif(($messagesReceivedDiffPercent ?? 0) < 0)
                                                    <span class="badge badge-danger font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-down mr-1"></i>{{ $messagesReceivedDiffPercent }}%</span>
                                                @else
                                                    <span class="badge badge-secondary text-muted font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}">= 0%</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #0ea5e9, #0284c7) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-comments" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-xs">
                                        <span class="font-weight-bold text-dark">{{ __tr('Aujourd\'hui: __today__', ['__today__' => $messagesReceivedTodayCount ?? 0]) }}</span>
                                        <span class="text-muted ml-1">{{ __tr('| Hier: __yesterday__', ['__yesterday__' => $messagesReceivedYesterdayCount ?? 0]) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Unread Messages --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Non Lus') }}</h5>
                                            <span class="h2 font-weight-bold mb-0 {{ ($unreadMessagesCount ?? 0) > 0 ? 'text-danger' : 'text-muted' }}" style="font-size: 1.7rem; font-weight: 800;">{{ $unreadMessagesCount ?? 0 }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #f97316, #ea580c) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-envelope-open-text" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.chat_message.contact.view') }}" class="font-weight-bold" style="color: #ea580c;"><i class="fas fa-comment-dots mr-1"></i> {{ __tr('Ouvrir la messagerie') }}</a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- AI Credits Card --}}
                        @if (vendorPlanDetails('ai_chat_bot', 1)['is_limit_available'])
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Crédits IA') }}</h5>
                                            <span class="h2 font-weight-bold mb-0 text-primary" style="font-size: 1.7rem; font-weight: 800;">{{ $ai_credits['display_credits'] ?? 0 }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #6366f1, #4f46e5) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-brain" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <span>{{ __tr('Crédits restants ce mois') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="row mb-2">
                        @if (hasVendorAccess('manage_contacts'))
                        {{-- total contacts --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Total Contacts') }}</h5>
                                            <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($totalContacts) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.contact.read.list_view') }}" class="font-weight-bold text-primary">{{  __tr('Manage Contacts') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total contacts --}}
                        @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                            {{-- total groups --}}
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                                <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Total Groups') }}</h5>
                                                <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($totalGroups) }}</span>
                                            </div>
                                            <div class="col-auto">
                                                <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #8b5cf6, #6d28d9) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-users" style="font-size: 1.1rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        @if(!$vendorViewBySuperAdmin)
                                        <p class="mt-3 mb-0 text-muted text-sm">
                                            <a href="{{ route('vendor.contact.group.read.list_view') }}" class="font-weight-bold text-purple" style="color: #8b5cf6;">{{  __tr('Manage Groups') }}</a>
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
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Total Campaigns') }}</h5>
                                            <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($totalCampaigns) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #ec4899, #be185d) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-bullhorn" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.campaign.read.list_view') }}" class="font-weight-bold text-pink" style="color: #ec4899;">{{  __tr('Manage Campaigns') }}</a>
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
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Total Templates') }}</h5>
                                            <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($totalTemplates) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #14b8a6, #0d9488) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-layer-group" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}" class="font-weight-bold text-teal" style="color: #14b8a6;">{{  __tr('Manage Templates') }}</a>
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
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Total Active Bots') }}</h5>
                                            <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($totalBotReplies) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #64748b, #334155) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-robot" style="font-size: 1.1rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.bot_reply.read.list_view') }}" class="font-weight-bold text-slate" style="color: #64748b;">{{  __tr('Manage Bots') }}</a>
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
                             <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                 <div class="card-body">
                                     <div class="row">
                                         <div class="col">
                                             <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Active Team Members') }}</h5>
                                             <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($activeTeamMembers) }}</span>
                                         </div>
                                         <div class="col-auto">
                                             <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #eab308, #ca8a04) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                 <i class="fas fa-user-tie" style="font-size: 1.1rem;"></i>
                                             </div>
                                         </div>
                                     </div>
                                     @if(!$vendorViewBySuperAdmin)
                                     <p class="mt-3 mb-0 text-muted text-sm">
                                         <a href="{{ route('vendor.user.read.list_view') }}" class="font-weight-bold text-warning" style="color: #eab308;">{{  __tr('Manage Team Member') }}</a>
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
                            <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Messages in Queue') }}</h5>
                                            <span class="h2 font-weight-bold mb-0" style="font-size: 1.7rem; font-weight: 800; color: #0f172a;">{{ __tr($messagesInQueue) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #6366f1, #4338ca) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-stream" style="font-size: 1.1rem;"></i>
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
                             <div class="card card-stats card-stats-pro mb-4 mb-xl-0 border-0">
                                 <div class="card-body">
                                     <div class="row">
                                         <div class="col">
                                             <h5 class="card-title text-uppercase mb-1" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; color: #64748b;">{{ __tr('Messages Traités') }}</h5>
                                             <div class="d-flex align-items-baseline">
                                                 <span class="h2 font-weight-bold mb-0 text-primary mr-2" style="font-size: 1.7rem; font-weight: 800;">{{ __tr($totalMessagesProcessed) }}</span>
                                                 @if(($messagesProcessedDiffPercent ?? 0) > 0)
                                                     <span class="badge badge-success font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-up mr-1"></i>+{{ $messagesProcessedDiffPercent }}%</span>
                                                 @elseif(($messagesProcessedDiffPercent ?? 0) < 0)
                                                     <span class="badge badge-danger font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}"><i class="fas fa-arrow-down mr-1"></i>{{ $messagesProcessedDiffPercent }}%</span>
                                                 @else
                                                     <span class="badge badge-secondary text-muted font-weight-bold" style="font-size: 0.72rem; border-radius: 6px;" title="{{ __tr('Évolution vs hier') }}">= 0%</span>
                                                 @endif
                                             </div>
                                         </div>
                                         <div class="col-auto">
                                             <div class="icon icon-shape text-white rounded-circle shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #0284c7, #0369a1) !important; border-radius: 12px !important; display: flex; align-items: center; justify-content: center;">
                                                 <i class="fas fa-tasks" style="font-size: 1.1rem;"></i>
                                             </div>
                                         </div>
                                     </div>
                                     <p class="mt-3 mb-0 text-muted text-xs">
                                         <span class="font-weight-bold text-dark">{{ __tr('Aujourd\'hui: __today__', ['__today__' => $messagesProcessedTodayCount ?? 0]) }}</span>
                                         <span class="text-muted ml-1">{{ __tr('| Hier: __yesterday__', ['__yesterday__' => $messagesProcessedYesterdayCount ?? 0]) }}</span>
                                     </p>
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
    @endifv>
    </div>
    @endif