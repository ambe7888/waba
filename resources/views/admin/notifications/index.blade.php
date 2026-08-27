@extends('layouts.app', ['title' => __tr('Notifications Mobiles Push')])

@section('content')
<div class="container-fluid py-4" style="margin-top: 25px; padding-top: 10px;" x-data="{
    audience: 'all',
    selectedVendor: '',
    notifTypeTab: 'text',
    typeLevel: 'info',
    title: '',
    message: '',
    imageUrl: '',
    clickUrl: '',
    fileNameLabel: 'Choisir une image depuis l\'ordinateur...',
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
    },
    previewImage(event) {
        var file = event.target.files[0];
        if (file) {
            this.fileNameLabel = file.name;
            var reader = new FileReader();
            reader.onload = (e) => {
                this.imageUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
}">
    @if(session('message'))
        <div class="alert alert-{{ session('messageType') }} alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 12px; font-weight: 600;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Header Banner Card (Spaced below top navbar) -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="mr-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #0f172a; border-radius: 14px; color: #ffffff;">
                <i class="fas fa-paper-plane fa-lg"></i>
            </div>
            <div>
                <h3 class="h4 font-weight-800 text-dark mb-1" style="color: #0f172a; font-weight: 800; letter-spacing: -0.3px;">Manual Web Push</h3>
                <p class="mb-0 font-weight-500" style="font-size: 0.92rem; color: #475569;">Send web push notifications for subscribed users & vendors</p>
            </div>
        </div>
    </div>

    <form action="{{ route('central.notifications.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="audience_type" :value="audience">
        <input type="hidden" name="vendors__id" :value="selectedVendor">
        <input type="hidden" name="type" :value="typeLevel">

        <div class="row mb-4">
            <!-- Left Column: Select Audience & Notification Type -->
            <div class="col-lg-7 col-md-12">
                <!-- 1. Select Audience Card -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-800 mb-3 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">🎯</span> {{ __tr('Select Audience') }}
                        </h5>

                        <div class="row">
                            <!-- Option 1: Tout (All) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'all' ? 'border: 2px solid #0f172a !important; background: #f8fafc; border-radius: 14px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);' : 'border: 1.5px solid #cbd5e1 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'all'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #0f172a; color: #ffffff; border-radius: 8px;">
                                                <i class="fas fa-th-large" style="font-size: 0.85rem;"></i>
                                            </div>
                                            <strong class="text-dark font-weight-800" style="font-size: 0.95rem; color: #0f172a;">Tout</strong>
                                        </div>
                                        <span class="badge px-2.5 py-1 font-weight-800" style="background: #0f172a; color: #ffffff; border-radius: 8px; font-size: 0.8rem;" x-text="totalVendors"></span>
                                    </div>
                                    <p class="mb-0 font-weight-500" style="font-size: 0.82rem; color: #334155; line-height: 1.35;">Send to all subscribed users & agents</p>
                                </div>
                            </div>

                            <!-- Option 2: Vendeurs en ligne (Online Vendors) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'online' ? 'border: 2px solid #d97706 !important; background: #fffbeb; border-radius: 14px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.12);' : 'border: 1.5px solid #cbd5e1 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'online'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #fef3c7; color: #b45309; border-radius: 8px;">
                                                <i class="fas fa-user-clock" style="font-size: 0.85rem;"></i>
                                            </div>
                                            <strong class="text-dark font-weight-800" style="font-size: 0.92rem; color: #0f172a;">Vendeurs en ligne</strong>
                                        </div>
                                        <span class="badge px-2.5 py-1 font-weight-800" style="background: #f59e0b; color: #ffffff; border-radius: 8px; font-size: 0.8rem;" x-text="onlineVendors"></span>
                                    </div>
                                    <p class="mb-0 font-weight-500" style="font-size: 0.82rem; color: #334155; line-height: 1.35;">Vendeurs actuellement actifs sur la plateforme</p>
                                </div>
                            </div>

                            <!-- Option 3: Manuel (Manual Select) -->
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-xl cursor-pointer transition-all h-100 position-relative"
                                     :style="audience === 'manual' ? 'border: 2px solid #2563eb !important; background: #eff6ff; border-radius: 14px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);' : 'border: 1.5px solid #cbd5e1 !important; background: #ffffff; border-radius: 14px;'"
                                     @click="audience = 'manual'">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 mr-2 rounded-lg d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #dbeafe; color: #1d4ed8; border-radius: 8px;">
                                                <i class="fas fa-user-check" style="font-size: 0.85rem;"></i>
                                            </div>
                                            <strong class="text-dark font-weight-800" style="font-size: 0.95rem; color: #0f172a;">Manuel</strong>
                                        </div>
                                        <span class="badge px-2.5 py-1 font-weight-800" style="background: #2563eb; color: #ffffff; border-radius: 8px; font-size: 0.8rem;" x-text="selectedVendor ? 1 : 0"></span>
                                    </div>
                                    <p class="mb-0 font-weight-500" style="font-size: 0.82rem; color: #334155; line-height: 1.35;">Handpick specific recipients from list</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown manual selection -->
                        <div x-show="audience === 'manual'" class="mt-3 p-3" style="background: #f1f5f9; border-radius: 12px; border: 1px solid #cbd5e1;" x-cloak>
                            <label class="form-label font-weight-800 small mb-2" style="color: #0f172a;"><i class="fas fa-store mr-1 text-primary"></i> Sélectionner le Vendeur :</label>
                            <select class="form-control font-weight-600 text-dark border-0 shadow-sm" x-model="selectedVendor" style="border-radius: 10px; color: #0f172a !important;">
                                <option value="">-- Choisissez un vendeur dans la liste --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->_id }}">{{ $vendor->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. Notification Type & Form Details Card -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-800 mb-3 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">📝</span> {{ __tr('Notification Type') }}
                        </h5>

                        <!-- Tabs: Text Only vs With Image -->
                        <div class="d-flex mb-4 p-1" style="background: #f1f5f9; border-radius: 10px; width: fit-content; border: 1px solid #e2e8f0;">
                            <button type="button" class="btn btn-sm px-3 font-weight-800 transition-all"
                                    :class="notifTypeTab === 'text' ? 'btn-white shadow-sm text-dark' : 'text-muted'"
                                    style="border-radius: 8px; color: #0f172a !important;"
                                    @click="notifTypeTab = 'text'">
                                <i class="fas fa-font mr-1"></i> Text Only
                            </button>
                            <button type="button" class="btn btn-sm px-3 font-weight-800 transition-all"
                                    :class="notifTypeTab === 'image' ? 'btn-white shadow-sm text-dark' : 'text-muted'"
                                    style="border-radius: 8px; color: #0f172a !important;"
                                    @click="notifTypeTab = 'image'">
                                <i class="far fa-image mr-1"></i> With Image
                            </button>
                        </div>

                        <!-- Type Level Selector -->
                        <div class="form-group mb-4">
                            <label class="form-label font-weight-800 small mb-2" style="color: #0f172a;">Style / Gravité de l'Alerte :</label>
                            <div class="d-flex flex-wrap" style="gap: 8px;">
                                <button type="button" class="btn btn-sm px-3 font-weight-700" :class="typeLevel === 'info' ? 'btn-info shadow-sm' : 'btn-outline-info'" @click="typeLevel = 'info'" style="border-radius: 8px;">Info (Bleu)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-700" :class="typeLevel === 'success' ? 'btn-success shadow-sm' : 'btn-outline-success'" @click="typeLevel = 'success'" style="border-radius: 8px;">Succès (Vert)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-700" :class="typeLevel === 'warning' ? 'btn-warning shadow-sm' : 'btn-outline-warning'" @click="typeLevel = 'warning'" style="border-radius: 8px;">Attention (Orange)</button>
                                <button type="button" class="btn btn-sm px-3 font-weight-700" :class="typeLevel === 'danger' ? 'btn-danger shadow-sm' : 'btn-outline-danger'" @click="typeLevel = 'danger'" style="border-radius: 8px;">Alerte (Rouge)</button>
                            </div>
                        </div>

                        <!-- Title Input -->
                        <div class="form-group mb-3 position-relative">
                            <label class="form-label font-weight-800 small mb-1" style="color: #0f172a;">Notification Title</label>
                            <input type="text" name="title" x-model="title" required maxlength="100" class="form-control font-weight-600 py-3 px-3 shadow-sm" placeholder="Ex: Update de l'application disponible" style="border-radius: 12px; font-size: 0.95rem; border: 1.5px solid #cbd5e1; color: #0f172a;">
                            <div class="text-muted text-right font-weight-600 small mt-1" style="font-size: 0.78rem; color: #64748b;" x-text="title.length + '/100'">0/100</div>
                        </div>

                        <!-- Body Textarea -->
                        <div class="form-group mb-3 position-relative">
                            <label class="form-label font-weight-800 small mb-1" style="color: #0f172a;">Notification Body</label>
                            <textarea name="message" x-model="message" required maxlength="300" rows="4" class="form-control font-weight-600 py-3 px-3 shadow-sm" placeholder="Notification Body message..." style="border-radius: 12px; font-size: 0.95rem; border: 1.5px solid #cbd5e1; color: #0f172a;"></textarea>
                            <div class="text-muted text-right font-weight-600 small mt-1" style="font-size: 0.78rem; color: #64748b;" x-text="message.length + '/300'">0/300</div>
                        </div>

                        <!-- Image File / URL (when With Image is selected) -->
                        <div x-show="notifTypeTab === 'image'" class="form-group mb-3" x-cloak>
                            <label class="form-label font-weight-800 small mb-1" style="color: #0f172a;"><i class="fas fa-camera text-primary mr-1"></i> Sélectionner une Image (Fichier ou URL)</label>
                            
                            <!-- Local File Input Button -->
                            <div class="mb-2">
                                <label class="btn btn-outline-primary btn-block py-2.5 font-weight-700 shadow-sm cursor-pointer d-flex align-items-center justify-content-center" style="border-radius: 12px; border: 1.5px dashed #2563eb; background: #f8fafc; color: #1d4ed8;">
                                    <i class="fas fa-cloud-upload-alt fa-lg mr-2"></i>
                                    <span x-text="fileNameLabel">Choisir une image depuis l'ordinateur...</span>
                                    <input type="file" name="image_file" accept="image/*" class="d-none" @change="previewImage($event)">
                                </label>
                            </div>

                            <small class="text-muted d-block mb-1 font-weight-600" style="font-size: 0.78rem; color: #64748b;">Ou saisir directement une URL d'image :</small>
                            <input type="url" name="image_url" x-model="imageUrl" class="form-control font-weight-600 py-3 px-3 shadow-sm" placeholder="https://example.com/image.png" style="border-radius: 12px; font-size: 0.9rem; border: 1.5px solid #cbd5e1; color: #0f172a;">
                        </div>

                        <!-- Click URL (optional) -->
                        <div class="form-group mb-0">
                            <label class="form-label font-weight-800 small mb-1" style="color: #0f172a;">Click URL (optional)</label>
                            <input type="text" name="click_url" x-model="clickUrl" class="form-control font-weight-600 py-3 px-3 shadow-sm" placeholder="https://whats-click.com/..." style="border-radius: 12px; font-size: 0.95rem; border: 1.5px solid #cbd5e1; color: #0f172a;">
                        </div>
                    </div>
                </div>

                <!-- High-Contrast Vibrant Submit Button -->
                <button type="submit" class="btn btn-block py-3 font-weight-800 shadow-lg mb-4 transition-all"
                        style="background: #0f172a !important; color: #ffffff !important; border-radius: 14px; font-size: 1.05rem; border: none; letter-spacing: 0.3px;">
                    <i class="fas fa-paper-plane mr-2 text-warning"></i> Send Now (<span x-text="totalRecipientsCount"></span> recipients)
                </button>
            </div>

            <!-- Right Column: Live Preview & Summary -->
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm border-0 sticky-top" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; top: 30px;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-800 mb-4 d-flex align-items-center" style="color: #0f172a; font-size: 1.05rem;">
                            <span class="mr-2">👁️</span> {{ __tr('Live Preview') }}
                        </h5>

                        <!-- Mobile Push Card Preview -->
                        <div class="p-3 mb-4" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 16px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                            <div class="d-flex align-items-start">
                                <div class="mr-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #0f172a; color: #f59e0b; border-radius: 50%; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.25);">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="flex-grow-1" style="overflow: hidden;">
                                    <strong class="d-block mb-1 text-truncate font-weight-800" style="font-size: 0.98rem; color: #0f172a;" x-text="title || 'Notification Title'">Notification Title</strong>
                                    <p class="mb-0 small font-weight-500" style="font-size: 0.86rem; color: #475569; line-height: 1.45; word-break: break-word;" x-text="message || 'Your notification message will appear here...'">Your notification message will appear here...</p>
                                </div>
                            </div>
                            <template x-if="notifTypeTab === 'image' && imageUrl">
                                <div class="mt-3 rounded-lg overflow-hidden position-relative" style="max-height: 180px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                    <img :src="imageUrl" class="w-100 h-100 style-cover" alt="Preview Image" style="object-fit: cover; max-height: 180px;">
                                </div>
                            </template>
                        </div>

                        <!-- Recipients Summary -->
                        <div class="pt-2">
                            <h6 class="text-uppercase font-weight-800 mb-3" style="font-size: 0.78rem; letter-spacing: 0.6px; color: #64748b;">RECIPIENTS SUMMARY</h6>
                            
                            <div class="d-flex align-items-center justify-content-between py-2.5 border-bottom">
                                <span class="font-weight-600 small" style="color: #475569;">Total, Recipients</span>
                                <span class="font-weight-800 badge px-3 py-1.5" style="background: #0f172a; color: #ffffff; border-radius: 8px; font-size: 0.85rem;" x-text="totalRecipientsCount">0</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between py-2.5 border-bottom">
                                <span class="font-weight-600 small" style="color: #475569;">Vendeurs en ligne</span>
                                <span class="font-weight-800 badge px-3 py-1.5" style="background: #fef3c7; color: #b45309; border-radius: 8px; font-size: 0.85rem;" x-text="onlineVendors">0</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between py-2.5">
                                <span class="font-weight-600 small" style="color: #475569;">Mode d'audience</span>
                                <span class="font-weight-800" style="font-size: 0.88rem; color: #0f172a;" x-text="audienceModeLabel">Tout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Historique des notifications (Tableau Ultra-Lisible & Haute Qualité) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
                <div class="card-header py-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-800 text-dark d-flex align-items-center" style="font-size: 1.08rem; color: #0f172a !important;">
                        <i class="fas fa-history text-primary mr-2.5"></i> {{ __tr('Historique des envois') }}
                    </h5>
                    <span class="badge badge-light px-3 py-1.5 font-weight-700" style="background: #f1f5f9; color: #475569; border-radius: 8px;">
                        {{ $notifications->total() }} notification(s)
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush table-hover mb-0">
                            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <tr>
                                    <th style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #0f172a; padding: 16px 20px;">{{ __tr('Date & Heure') }}</th>
                                    <th style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #0f172a; padding: 16px 20px;">{{ __tr('Type') }}</th>
                                    <th style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #0f172a; padding: 16px 20px;">{{ __tr('Destinataire') }}</th>
                                    <th style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #0f172a; padding: 16px 20px;">{{ __tr('Titre') }}</th>
                                    <th style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #0f172a; padding: 16px 20px;">{{ __tr('Message') }}</th>
                                </tr>
                            </thead>
                            <tbody style="background: #ffffff;">
                                @forelse($notifications as $notif)
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td class="font-weight-700" style="color: #0f172a; font-size: 0.88rem; padding: 16px 20px;">
                                            <i class="far fa-calendar-alt text-muted mr-1.5"></i> {{ $notif->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td style="padding: 16px 20px;">
                                            @if($notif->type == 'info')
                                                <span class="badge px-2.5 py-1.5 font-weight-700" style="background: #dbeafe; color: #1d4ed8; border-radius: 8px; font-size: 0.78rem;">Info</span>
                                            @elseif($notif->type == 'success')
                                                <span class="badge px-2.5 py-1.5 font-weight-700" style="background: #d1fae5; color: #047857; border-radius: 8px; font-size: 0.78rem;">Succès</span>
                                            @elseif($notif->type == 'warning')
                                                <span class="badge px-2.5 py-1.5 font-weight-700" style="background: #fef3c7; color: #b45309; border-radius: 8px; font-size: 0.78rem;">Attention</span>
                                            @else
                                                <span class="badge px-2.5 py-1.5 font-weight-700" style="background: #fee2e2; color: #b91c1c; border-radius: 8px; font-size: 0.78rem;">Alerte</span>
                                            @endif
                                        </td>
                                        <td style="padding: 16px 20px;">
                                            @if($notif->vendors__id)
                                                <span class="badge px-2.5 py-1.5 font-weight-800" style="background: #f1f5f9; color: #0f172a; border-radius: 8px; font-size: 0.82rem; border: 1px solid #cbd5e1;">
                                                    <i class="fas fa-store mr-1 text-primary"></i> {{ $notif->vendor->title ?? ('Vendeur #' . $notif->vendors__id) }}
                                                </span>
                                            @else
                                                <span class="badge px-2.5 py-1.5 font-weight-800" style="background: #e0e7ff; color: #3730a3; border-radius: 8px; font-size: 0.82rem;">
                                                    <i class="fas fa-globe mr-1"></i> Global (Tous)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="font-weight-800" style="color: #0f172a; font-size: 0.9rem; padding: 16px 20px;">{{ $notif->title }}</td>
                                        <td class="font-weight-500" style="color: #334155; font-size: 0.88rem; max-width: 320px; white-space: normal; line-height: 1.4; padding: 16px 20px;">
                                            {{ Str::limit($notif->message, 90) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5" style="font-size: 0.95rem; font-weight: 500;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                            {{ __tr('Aucune notification envoyée pour le moment.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($notifications->hasPages())
                        <div class="p-3 border-top bg-white">
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
