@extends('layouts.app', ['title' => __tr('Notifications Mobiles Push')])

@section('content')
<div class="container-fluid py-4" x-data="{
    audience: 'all',
    selectedVendor: '',
    notifTypeTab: 'text',
    typeLevel: 'info',
    title: '',
    message: '',
    imageUrl: '',
    clickUrl: '',
    totalVendors: {{ $totalVendorsCount ?? 0 }},
    onlineVendors: {{ $onlineVendorsCount ?? 0 }},
    get totalRecipientsCount() {
        if (this.audience === 'all') return this.totalVendors;
        if (this.audience === 'online') return this.onlineVendors;
        if (this.audience === 'manual') return this.selectedVendor ? 1 : 0;
        return 0;
    },
    get audienceModeLabel() {
        if (this.audience === 'all') return 'Tout (Tous les vendeurs)';
        if (this.audience === 'online') return 'Vendeurs en ligne';
        if (this.audience === 'manual') return 'Sélection manuelle';
        return 'Tout';
    }
}">
    @if(session('message'))
        <div class="alert alert-{{ session('messageType') }} alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 12px;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Header Banner Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff;">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: #f1f5f9; border-radius: 14px; color: #0f172a;">
                <i class="fas fa-paper-plane fa-lg"></i>
            </div>
            <div>
                <h3 class="h4 font-weight-800 text-dark mb-1" style="color: #0f172a; font-weight: 800;">Manual Web Push</h3>
                <p class="text-muted mb-0" style="font-size: 0.92rem;">Envoyez des notifications push instantanées aux vendeurs et utilisateurs inscrits</p>
            </div>
        </div>
    </div>

    <form action="{{ route('central.notifications.store') }}" method="POST">
        @csrf
        <input type="hidden" name="audience_type" :value="audience">
        <input type="hidden" name="vendors__id" :value="selectedVendor">
        <input type="hidden" name="type" :value="typeLevel">

        <div class="row mb-4">
            <!-- Left Column: Select Audience & Notification Type -->
            <div class="col-lg-7 col-md-12">
                <!-- 1. Select Audience Card -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-700 mb-3 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">🎯</span> {{ __tr('Select Audience') }}
                        </h5>

                        <div class="row">
                            <!-- Option 1: Tout (All) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'all' ? 'border: 2px solid #0f172a !important; background: #f8fafc; border-radius: 14px;' : 'border: 1.5px solid #e2e8f0 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'all'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f1f5f9; color: #0f172a; border-radius: 8px;">
                                                <i class="fas fa-th-large"></i>
                                            </div>
                                            <strong class="text-dark" style="font-size: 0.95rem;">Tout</strong>
                                        </div>
                                        <span class="badge badge-light px-2 py-1 font-weight-700" style="background: #e2e8f0; color: #1e293b; border-radius: 8px; font-size: 0.78rem;" x-text="totalVendors"></span>
                                    </div>
                                    <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.35;">Tous les vendeurs et utilisateurs inscrits sur l'application</p>
                                </div>
                            </div>

                            <!-- Option 2: Vendeur en ligne (Online Vendors) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'online' ? 'border: 2px solid #0f172a !important; background: #f8fafc; border-radius: 14px;' : 'border: 1.5px solid #e2e8f0 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'online'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #fef3c7; color: #d97706; border-radius: 8px;">
                                                <i class="fas fa-user-clock"></i>
                                            </div>
                                            <strong class="text-dark" style="font-size: 0.95rem;">Vendeurs en ligne</strong>
                                        </div>
                                        <span class="badge badge-light px-2 py-1 font-weight-700" style="background: #fef3c7; color: #b45309; border-radius: 8px; font-size: 0.78rem;" x-text="onlineVendors"></span>
                                    </div>
                                    <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.35;">Vendeurs actuellement actifs ou récents sur la plateforme</p>
                                </div>
                            </div>

                            <!-- Option 3: Manuel (Manual Select) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'manual' ? 'border: 2px solid #0f172a !important; background: #f8fafc; border-radius: 14px;' : 'border: 1.5px solid #e2e8f0 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'manual'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #dbeafe; color: #2563eb; border-radius: 8px;">
                                                <i class="fas fa-user-tag"></i>
                                            </div>
                                            <strong class="text-dark" style="font-size: 0.95rem;">Manuel</strong>
                                        </div>
                                        <span class="badge badge-light px-2 py-1 font-weight-700" style="background: #dbeafe; color: #1d4ed8; border-radius: 8px; font-size: 0.78rem;" x-text="selectedVendor ? 1 : 0"></span>
                                    </div>
                                    <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.35;">Sélectionner un vendeur spécifique dans la liste</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown manual selection -->
                        <div x-show="audience === 'manual'" class="mt-3 p-3" style="background: #f1f5f9; border-radius: 12px;" x-cloak>
                            <label class="form-label font-weight-700 text-dark small mb-2"><i class="fas fa-store mr-1 text-primary"></i> Sélectionner le Vendeur :</label>
                            <select class="form-control form-control-alternative border-0 shadow-sm" x-model="selectedVendor" style="border-radius: 10px;">
                                <option value="">-- Choisissez un vendeur --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->_id }}">{{ $vendor->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. Notification Type & Message Body Card -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-700 mb-3 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">📝</span> {{ __tr('Notification Type') }}
                        </h5>

                        <!-- Tabs: Text Only vs With Image -->
                        <div class="d-flex mb-4 p-1" style="background: #f1f5f9; border-radius: 10px; width: fit-content;">
                            <button type="button" class="btn btn-sm px-3 font-weight-700 transition-all"
                                    :class="notifTypeTab === 'text' ? 'btn-white shadow-sm text-dark' : 'text-muted'"
                                    style="border-radius: 8px;"
                                    @click="notifTypeTab = 'text'">
                                <i class="fas fa-font mr-1"></i> Text Only
                            </button>
                            <button type="button" class="btn btn-sm px-3 font-weight-700 transition-all"
                                    :class="notifTypeTab === 'image' ? 'btn-white shadow-sm text-dark' : 'text-muted'"
                                    style="border-radius: 8px;"
                                    @click="notifTypeTab = 'image'">
                                <i class="far fa-image mr-1"></i> With Image
                            </button>
                        </div>

                        <!-- Type Level Selector -->
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-700 text-dark small mb-2">Style d'Alerte :</label>
                            <div class="d-flex" style="gap: 8px;">
                                <button type="button" class="btn btn-sm px-3 font-weight-600" :class="typeLevel === 'info' ? 'btn-info' : 'btn-outline-info'" @click="typeLevel = 'info'" style="border-radius: 8px;">Info (Bleu)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-600" :class="typeLevel === 'success' ? 'btn-success' : 'btn-outline-success'" @click="typeLevel = 'success'" style="border-radius: 8px;">Succès (Vert)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-600" :class="typeLevel === 'warning' ? 'btn-warning' : 'btn-outline-warning'" @click="typeLevel = 'warning'" style="border-radius: 8px;">Attention (Orange)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-600" :class="typeLevel === 'danger' ? 'btn-danger' : 'btn-outline-danger'" @click="typeLevel = 'danger'" style="border-radius: 8px;">Alerte (Rouge)</button>
                            </div>
                        </div>

                        <!-- Title Input -->
                        <div class="form-group mb-3 position-relative">
                            <input type="text" name="title" x-model="title" required maxlength="100" class="form-control form-control-alternative py-3 px-3 border-0 shadow-sm" placeholder="Notification Title" style="border-radius: 12px; font-size: 0.95rem;">
                            <div class="text-muted text-right small mt-1" style="font-size: 0.75rem;" x-text="title.length + '/100'">0/100</div>
                        </div>

                        <!-- Body Textarea -->
                        <div class="form-group mb-3 position-relative">
                            <textarea name="message" x-model="message" required maxlength="300" rows="4" class="form-control form-control-alternative py-3 px-3 border-0 shadow-sm" placeholder="Notification Body" style="border-radius: 12px; font-size: 0.95rem;"></textarea>
                            <div class="text-muted text-right small mt-1" style="font-size: 0.75rem;" x-text="message.length + '/300'">0/300</div>
                        </div>

                        <!-- Image URL (when With Image is selected) -->
                        <div x-show="notifTypeTab === 'image'" class="form-group mb-3" x-cloak>
                            <input type="url" name="image_url" x-model="imageUrl" class="form-control form-control-alternative py-3 px-3 border-0 shadow-sm" placeholder="Image URL (http://...)" style="border-radius: 12px; font-size: 0.95rem;">
                        </div>

                        <!-- Click URL (optional) -->
                        <div class="form-group mb-0">
                            <input type="text" name="click_url" x-model="clickUrl" class="form-control form-control-alternative py-3 px-3 border-0 shadow-sm" placeholder="Click URL (optional)" style="border-radius: 12px; font-size: 0.95rem;">
                        </div>
                    </div>
                </div>

                <!-- Submit Action Button -->
                <button type="submit" class="btn btn-block py-3 font-weight-800 shadow-sm mb-4 transition-all"
                        style="background: #e2e8f0; color: #475569; border-radius: 14px; font-size: 1.05rem; border: none;"
                        :style="title && message ? 'background: #0f172a !important; color: #ffffff !important;' : ''">
                    <i class="fas fa-paper-plane mr-2"></i> Send Now (<span x-text="totalRecipientsCount"></span> recipients)
                </button>
            </div>

            <!-- Right Column: Live Preview & Summary -->
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm border-0 sticky-top" style="border-radius: 16px; background: #ffffff; top: 20px;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-700 mb-4 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">👁️</span> {{ __tr('Live Preview') }}
                        </h5>

                        <!-- Phone-Style Push Notification Card Preview -->
                        <div class="p-3 mb-4" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px;">
                            <div class="d-flex align-items-start">
                                <div class="mr-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #0f172a; color: #f59e0b; border-radius: 50%;">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="flex-grow-1" style="overflow: hidden;">
                                    <strong class="d-block text-dark mb-1 text-truncate" style="font-size: 0.95rem; font-weight: 700;" x-text="title || 'Notification Title'">Notification Title</strong>
                                    <p class="text-muted mb-0 small" style="font-size: 0.85rem; line-height: 1.4; word-break: break-word;" x-text="message || 'Your notification message will appear here...'">Your notification message will appear here...</p>
                                </div>
                            </div>
                            <template x-if="notifTypeTab === 'image' && imageUrl">
                                <div class="mt-3 rounded-lg overflow-hidden" style="max-height: 140px; border-radius: 10px;">
                                    <img :src="imageUrl" class="w-100 h-100 style-cover" alt="Preview Image" style="object-fit: cover;">
                                </div>
                            </template>
                        </div>

                        <!-- Recipients Summary -->
                        <div class="pt-2">
                            <h6 class="text-uppercase text-muted font-weight-700 mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Recipients Summary</h6>
                            
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <span class="text-muted small">Total, Recipients</span>
                                <span class="font-weight-800 text-dark badge badge-light px-2.5 py-1" style="background: #f1f5f9; border-radius: 8px; font-size: 0.85rem;" x-text="totalRecipientsCount">0</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <span class="text-muted small">Vendeurs en ligne</span>
                                <span class="font-weight-800 text-dark badge badge-light px-2.5 py-1" style="background: #fef3c7; color: #b45309; border-radius: 8px; font-size: 0.85rem;" x-text="onlineVendors">0</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between py-2">
                                <span class="text-muted small">Mode d'audience</span>
                                <span class="font-weight-700 text-dark" style="font-size: 0.85rem;" x-text="audienceModeLabel">Tout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Historique des notifications (Tableau Conservé) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 16px; background: #ffffff;">
                <div class="card-header py-3 bg-transparent border-bottom">
                    <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center" style="font-size: 1.05rem;">
                        <i class="fas fa-history text-primary mr-2"></i> {{ __tr('Historique des envois') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="font-size: 0.78rem; text-transform: uppercase;">{{ __tr('Date') }}</th>
                                    <th style="font-size: 0.78rem; text-transform: uppercase;">{{ __tr('Type') }}</th>
                                    <th style="font-size: 0.78rem; text-transform: uppercase;">{{ __tr('Destinataire') }}</th>
                                    <th style="font-size: 0.78rem; text-transform: uppercase;">{{ __tr('Titre') }}</th>
                                    <th style="font-size: 0.78rem; text-transform: uppercase;">{{ __tr('Message') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notif)
                                    <tr>
                                        <td class="small font-weight-600 text-dark">{{ $notif->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($notif->type == 'info')
                                                <span class="badge badge-info px-2 py-1" style="border-radius: 6px;">Info</span>
                                            @elseif($notif->type == 'success')
                                                <span class="badge badge-success px-2 py-1" style="border-radius: 6px;">Succès</span>
                                            @elseif($notif->type == 'warning')
                                                <span class="badge badge-warning px-2 py-1" style="border-radius: 6px;">Attention</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1" style="border-radius: 6px;">Alerte</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notif->vendors__id)
                                                <span class="badge badge-secondary px-2 py-1" style="border-radius: 6px;">{{ $notif->vendor->title ?? ('Vendeur #' . $notif->vendors__id) }}</span>
                                            @else
                                                <span class="badge badge-primary px-2 py-1" style="border-radius: 6px;">Global</span>
                                            @endif
                                        </td>
                                        <td class="font-weight-700 text-dark" style="font-size: 0.9rem;">{{ $notif->title }}</td>
                                        <td class="text-muted small" style="max-width: 300px; white-space: normal;">{{ Str::limit($notif->message, 80) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ __tr('Aucune notification envoyée.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($notifications->hasPages())
                        <div class="p-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease-in-out; }
.btn-white { background-color: #ffffff !important; }
.border-dashed { border-style: dashed !important; }
</style>
@endsection
