@php
    $vendorId = getVendorId();
    $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
    $planCredits = $vendor->plan_ai_credits ?? 0;
    $extraCredits = $vendor->extra_ai_credits ?? 0;
    $totalCredits = $planCredits + $extraCredits;
    
    $planCreditsDisplay = $planCredits >= 99999999 ? __tr('Illimité') : number_format($planCredits);
    $totalCreditsDisplay = $planCredits >= 99999999 ? __tr('Illimité') : number_format($totalCredits);
    $extraCreditsDisplay = number_format($extraCredits);
@endphp

<div class="container-fluid pb-5">
    <!-- Header Title -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('Recharge de Crédits IA') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Rechargez vos crédits pour alimenter le bot WhatsApp et l\'assistance automatique') }}</p>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
    @endif
    @if(request('status') == 'success')
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fa fa-check-circle mr-2"></i> {{ __tr('Félicitations ! Vos crédits IA ont été rechargés avec succès.') }}
        </div>
    @endif

    <!-- Balance Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <span class="badge px-3 py-1 font-weight-bold text-uppercase mb-2" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px; font-size: 0.75rem;">
                                <i class="fa fa-bolt mr-1"></i> {{ __tr('Solde Actuel') }}
                            </span>
                            <h2 class="display-4 font-weight-bold text-white mb-2" style="font-size: 2.5rem;">
                                {{ $totalCreditsDisplay }} <span class="h4 text-white-50 font-weight-normal">{{ __tr('Crédits IA') }}</span>
                            </h2>
                            <div class="d-flex flex-wrap align-items-center text-white-50 small">
                                <span class="mr-3"><i class="fa fa-calendar-alt mr-1"></i> {{ __tr('Crédits Abonnement:') }} <strong class="text-white">{{ $planCreditsDisplay }}</strong></span>
                                <span><i class="fa fa-shopping-bag mr-1"></i> {{ __tr('Crédits Achetés:') }} <strong class="text-white">{{ $extraCreditsDisplay }}</strong></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <div class="p-3 rounded text-white" style="background: rgba(0,0,0,0.15); border-radius: 12px !important;">
                                <small class="d-block text-white-50 font-weight-bold mb-1">{{ __tr('Consommation IA') }}</small>
                                <span class="small">{{ __tr('Décompte dynamique basé sur les jetons consommés (Google Gemini 1.5 Flash / OpenAI)') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Packs Grid -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h4 class="font-weight-bold text-dark mb-1">{{ __tr('Choisissez un Pack de Crédits') }}</h4>
            <p class="text-muted small mb-0">{{ __tr('Cliquez sur un pack pour le sélectionner et validez votre paiement') }}</p>
        </div>
        
        <div class="card-body p-4" x-data="{ selectedAmount: '10', selectedCredits: '700' }">
            <div class="row">
                
                <!-- Starter Pack ($5 - 300 Credits) -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 position-relative transition-all"
                         @click="selectedAmount = '5'; selectedCredits = '300'"
                         :style="selectedAmount == '5' 
                            ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4; cursor: pointer; border-radius: 16px; transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15);' 
                            : 'border: 1px solid #e2e8f0; background-color: #ffffff; cursor: pointer; border-radius: 16px; transition: all 0.2s ease;'">
                        
                        <div x-show="selectedAmount == '5'" class="position-absolute" style="top: -12px; right: 20px; z-index: 10;">
                            <span class="badge badge-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 20px; font-size: 0.78rem; background-color: #10b981;">
                                <i class="fa fa-check-circle mr-1"></i> {{ __tr('Sélectionné') }}
                            </span>
                        </div>

                        <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge px-3 py-1 font-weight-bold text-uppercase mb-3" style="background: #f1f5f9; color: #475569; border-radius: 8px;">
                                    {{ __tr('Pack Starter') }}
                                </span>
                                <h3 class="font-weight-bold text-dark mb-1">300</h3>
                                <p class="text-muted small mb-3">{{ __tr('Crédits IA') }}</p>
                            </div>
                            <div>
                                <div class="h2 font-weight-bold text-emerald mb-3" style="color: #059669;">
                                    $5
                                </div>
                                <div class="form-check custom-radio d-inline-block">
                                    <input type="radio" class="form-check-input" name="pack_option" id="pack_5" value="5" x-model="selectedAmount">
                                    <label class="form-check-label font-weight-bold text-muted small" style="cursor: pointer;" for="pack_5">
                                        {{ __tr('Sélectionner ce pack') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pro Pack ($10 - 700 Credits - Populaire) -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 position-relative transition-all"
                         @click="selectedAmount = '10'; selectedCredits = '700'"
                         :style="selectedAmount == '10' 
                            ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4; cursor: pointer; border-radius: 16px; transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15);' 
                            : 'border: 1px solid #e2e8f0; background-color: #ffffff; cursor: pointer; border-radius: 16px; transition: all 0.2s ease;'">
                        
                        <!-- Populaire Badge -->
                        <div class="position-absolute" style="top: -12px; left: 20px; z-index: 10;">
                            <span class="badge px-3 py-2 font-weight-bold shadow-sm text-white" style="border-radius: 20px; font-size: 0.75rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                🔥 {{ __tr('Plus Populaire') }}
                            </span>
                        </div>

                        <div x-show="selectedAmount == '10'" class="position-absolute" style="top: -12px; right: 20px; z-index: 10;">
                            <span class="badge badge-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 20px; font-size: 0.78rem; background-color: #10b981;">
                                <i class="fa fa-check-circle mr-1"></i> {{ __tr('Sélectionné') }}
                            </span>
                        </div>

                        <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge px-3 py-1 font-weight-bold text-uppercase mb-3" style="background: #ecfdf5; color: #047857; border-radius: 8px;">
                                    {{ __tr('Pack Pro') }}
                                </span>
                                <h3 class="font-weight-bold text-dark mb-1">700</h3>
                                <p class="text-muted small mb-3">{{ __tr('Crédits IA') }}</p>
                            </div>
                            <div>
                                <div class="h2 font-weight-bold text-emerald mb-3" style="color: #059669;">
                                    $10
                                </div>
                                <div class="form-check custom-radio d-inline-block">
                                    <input type="radio" class="form-check-input" name="pack_option" id="pack_10" value="10" x-model="selectedAmount">
                                    <label class="form-check-label font-weight-bold text-muted small" style="cursor: pointer;" for="pack_10">
                                        {{ __tr('Sélectionner ce pack') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Elite Pack ($20 - 1,500 Credits) -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 position-relative transition-all"
                         @click="selectedAmount = '20'; selectedCredits = '1500'"
                         :style="selectedAmount == '20' 
                            ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4; cursor: pointer; border-radius: 16px; transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15);' 
                            : 'border: 1px solid #e2e8f0; background-color: #ffffff; cursor: pointer; border-radius: 16px; transition: all 0.2s ease;'">
                        
                        <div x-show="selectedAmount == '20'" class="position-absolute" style="top: -12px; right: 20px; z-index: 10;">
                            <span class="badge badge-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 20px; font-size: 0.78rem; background-color: #10b981;">
                                <i class="fa fa-check-circle mr-1"></i> {{ __tr('Sélectionné') }}
                            </span>
                        </div>

                        <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge px-3 py-1 font-weight-bold text-uppercase mb-3" style="background: #f1f5f9; color: #475569; border-radius: 8px;">
                                    {{ __tr('Pack Élite') }}
                                </span>
                                <h3 class="font-weight-bold text-dark mb-1">1,500</h3>
                                <p class="text-muted small mb-3">{{ __tr('Crédits IA') }}</p>
                            </div>
                            <div>
                                <div class="h2 font-weight-bold text-emerald mb-3" style="color: #059669;">
                                    $20
                                </div>
                                <div class="form-check custom-radio d-inline-block">
                                    <input type="radio" class="form-check-input" name="pack_option" id="pack_20" value="20" x-model="selectedAmount">
                                    <label class="form-check-label font-weight-bold text-muted small" style="cursor: pointer;" for="pack_20">
                                        {{ __tr('Sélectionner ce pack') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Payment Forms -->
            <div class="mt-4 pt-3 border-top">
                @if(getAppSettings('enable_moneyfusion'))
                    <form method="post" action="{{ route('vendor.ai_credits.moneyfusion.checkout') }}" id="moneyfusion-ai-form">
                        @csrf
                        <input type="hidden" name="amount" :value="selectedAmount">
                        <input type="hidden" name="credits" :value="selectedCredits">
                        <button type="submit" class="btn btn-block text-white font-weight-bold py-3 shadow-sm transition-all"
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-size: 1.15rem; border-radius: 12px; cursor: pointer;">
                            <i class="fa fa-mobile-alt mr-2"></i> {{ __tr('Payer par Orange Money, MTN, Moov, Wave, Carte') }}
                        </button>
                    </form>
                @endif

                @if(getAppSettings('enable_wave'))
                    <form method="post" action="{{ route('vendor.ai_credits.checkout') }}" id="wave-ai-form" class="mt-2">
                        @csrf
                        <input type="hidden" name="amount" :value="selectedAmount">
                        <input type="hidden" name="credits" :value="selectedCredits">
                        <button type="submit" class="btn btn-info btn-block font-weight-bold py-3" style="border-radius: 12px;">
                            <i class="fa fa-money-bill-wave mr-2"></i> {{ __tr('Payer via Wave') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
