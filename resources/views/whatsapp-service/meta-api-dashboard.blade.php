@extends('layouts.app', ['title' => __tr('Tableau API Meta')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Tableau API Meta WhatsApp'),
    'description' => __tr('Vue d\'ensemble officielle, état de santé et raccordement du compte WhatsApp Business Meta'),
    'class' => 'col-lg-12'
])

<div class="container-fluid mt-4">
    <!-- Row 1: Primary KPI Metrics Cards -->
    <div class="row">
        <!-- Numéro de téléphone -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats shadow-sm border-0 h-100" style="border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="card-title text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.6px; font-weight: 700;">
                                {{ __tr('Numéro de téléphone') }}
                            </h6>
                            <span class="h3 font-weight-bold mb-0 text-dark d-block" style="font-size: 1.25rem;">
                                {{ $displayPhoneNumber }}
                            </span>
                            <div class="mt-2 text-muted small" style="font-size: 0.78rem;">
                                {{ __tr('ID') }}: <strong class="text-dark font-weight-600">{{ $phoneNumberId }}</strong>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-phone-alt" style="font-size: 1.1rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nom vérifié & Statut -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats shadow-sm border-0 h-100" style="border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="card-title text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.6px; font-weight: 700;">
                                {{ __tr('Nom vérifié & Statut') }}
                            </h6>
                            <span class="h3 font-weight-bold mb-1 text-dark d-block" style="font-size: 1.2rem;">
                                {{ $verifiedName }}
                            </span>
                            <div>
                                <span class="badge badge-success px-2 py-1 font-weight-bold" style="border-radius: 6px; font-size: 0.72rem; background-color: #d1fae5; color: #065f46;">
                                    <i class="fas fa-check-circle mr-1"></i> {{ $status }}
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shield-alt" style="font-size: 1.1rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Limite de messagerie -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats shadow-sm border-0 h-100" style="border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="card-title text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.6px; font-weight: 700;">
                                {{ __tr('Limite d\'envoi 24h') }}
                            </h6>
                            <span class="h3 font-weight-bold mb-0 text-dark d-block" style="font-size: 1.2rem;">
                                {{ $messagingLimit }}
                            </span>
                            <div class="mt-2 text-muted small" style="font-size: 0.78rem;">
                                {{ __tr('Quota quotidien Meta') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-tachometer-alt" style="font-size: 1.1rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- État de l'intégration -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats shadow-sm border-0 h-100" style="border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="card-title text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.6px; font-weight: 700;">
                                {{ __tr('Intégration WhatsApp') }}
                            </h6>
                            <span class="h3 font-weight-bold mb-1 text-primary d-block" style="font-size: 1.1rem;">
                                {{ $onboardingStatus }}
                            </span>
                            <div class="text-muted small" style="font-size: 0.78rem;">
                                {{ __tr('WABA') }}: <strong class="text-dark font-weight-600">{{ $wabaAccountId }}</strong>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape text-white rounded-circle shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-rocket" style="font-size: 1.1rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: 3 Cartes d'accès rapide Meta WhatsApp Manager -->
    <div class="row mb-4">
        <!-- Card 1: Gérer les numéros de téléphone -->
        <div class="col-md-4 mb-3">
            <a href="https://business.facebook.com/wa/manage/phone-numbers/" target="_blank" class="card shadow-sm border-0 h-100 text-decoration-none" style="border-radius: 16px; transition: all 0.25s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape text-white rounded-circle shadow-sm mr-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); width: 46px; height: 46px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0" style="font-size: 0.98rem;">{{ __tr('Gérer les numéros') }}</h5>
                            <span class="text-muted small" style="font-size: 0.78rem;">{{ __tr('Paramètres Meta Business') }}</span>
                        </div>
                    </div>
                    <i class="fas fa-external-link-alt text-primary" style="font-size: 0.95rem;"></i>
                </div>
            </a>
        </div>

        <!-- Card 2: Gestionnaire WhatsApp -->
        <div class="col-md-4 mb-3">
            <a href="https://business.facebook.com/wa/manage/" target="_blank" class="card shadow-sm border-0 h-100 text-decoration-none" style="border-radius: 16px; transition: all 0.25s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape text-white rounded-circle shadow-sm mr-3" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); width: 46px; height: 46px; display: flex; align-items: center; justify-content: center;">
                            <i class="fab fa-whatsapp" style="font-size: 1.25rem;"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0" style="font-size: 0.98rem;">{{ __tr('Gestionnaire WhatsApp') }}</h5>
                            <span class="text-muted small" style="font-size: 0.78rem;">{{ __tr('Portail Officiel Meta') }}</span>
                        </div>
                    </div>
                    <i class="fas fa-external-link-alt text-success" style="font-size: 0.95rem;"></i>
                </div>
            </a>
        </div>

        <!-- Card 3: Gérer les paiements -->
        <div class="col-md-4 mb-3">
            <a href="https://business.facebook.com/wa/manage/payments/" target="_blank" class="card shadow-sm border-0 h-100 text-decoration-none" style="border-radius: 16px; transition: all 0.25s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape text-white rounded-circle shadow-sm mr-3" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); width: 46px; height: 46px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0" style="font-size: 0.98rem;">{{ __tr('Gérer les paiements') }}</h5>
                            <span class="text-muted small" style="font-size: 0.78rem;">{{ __tr('Facturation Meta API') }}</span>
                        </div>
                    </div>
                    <i class="fas fa-external-link-alt text-warning" style="font-size: 0.95rem;"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Row 3: Technical Details & Health Status -->
    <div class="row">
        <!-- Compte Entreprise WABA Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9 !important;">
                    <h5 class="mb-0 font-weight-bold text-dark" style="font-size: 1rem;">
                        <i class="fas fa-building text-primary mr-2"></i> {{ __tr('Compte Entreprise WhatsApp (WABA)') }}
                    </h5>
                    <span class="badge badge-light text-muted px-2 py-1 font-weight-600" style="font-size: 0.75rem;">{{ $statusAt }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Numéro de téléphone (ID)') }}</th>
                                    <td class="font-weight-bold text-dark py-3" style="font-size: 0.88rem;">{{ $phoneNumberId }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Nom vérifié') }}</th>
                                    <td class="font-weight-bold text-dark py-3" style="font-size: 0.88rem;">{{ $verifiedName }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Statut du compte') }}</th>
                                    <td class="py-3"><span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.72rem; background-color: #d1fae5; color: #065f46;">{{ $status }}</span></td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Limite de messagerie') }}</th>
                                    <td class="font-weight-bold text-dark py-3" style="font-size: 0.88rem;">{{ $messagingLimit }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Afficher le numéro de téléphone') }}</th>
                                    <td class="font-weight-bold text-dark py-3" style="font-size: 0.88rem;">{{ $displayPhoneNumber }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('État actuel de l\'intégration') }}</th>
                                    <td class="py-3"><span class="badge badge-info px-2 py-1 font-weight-bold" style="font-size: 0.72rem;">{{ $onboardingStatus }}</span></td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Identifiant d’entreprise WhatsApp (WABA)') }}</th>
                                    <td class="font-weight-bold text-dark py-3" style="font-size: 0.88rem;">{{ $wabaAccountId }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Santé globale & Capacité d'envoi Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3" style="border-bottom: 1px solid #f1f5f9 !important;">
                    <h5 class="mb-0 font-weight-bold text-dark" style="font-size: 1rem;">
                        <i class="fas fa-heartbeat text-danger mr-2"></i> {{ __tr('Santé & Capacité d\'Envoi Meta') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Dernier contrôle du statut') }}</th>
                                    <td class="text-muted py-3 small font-weight-bold">{{ $statusAt }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Santé globale') }}</th>
                                    <td class="py-3">
                                        <span class="badge badge-warning text-dark px-3 py-1 font-weight-bold" style="font-size: 0.78rem; background-color: #fef3c7; color: #92400e;">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $globalHealth }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted font-weight-normal pl-4 py-3" style="font-size: 0.85rem;">{{ __tr('Peut envoyer un message') }}</th>
                                    <td class="py-3">
                                        <span class="badge badge-warning text-dark px-2 py-1 font-weight-bold mr-1" style="font-size: 0.72rem; background-color: #fef3c7; color: #92400e;">{{ $canSendMessage }}</span>
                                        <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.72rem; background-color: #d1fae5; color: #065f46;">DISPONIBLE</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Diagnostic Meta Alert Card (7 Cols) + Webhook Raccordement Card (5 Cols) -->
    <div class="row mb-4">
        <!-- Diagnostic Meta Alert Card -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; border-left: 5px solid #f59e0b !important;">
                <div class="card-header bg-white border-0 py-3" style="border-bottom: 1px solid #f1f5f9 !important;">
                    <h5 class="mb-0 font-weight-bold text-warning" style="font-size: 1rem;">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ __tr('Diagnostic Meta & Remarques') }}
                    </h5>
                </div>
                <div class="card-body pt-3">
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase font-weight-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">{{ __tr('Erreur de description') }}</label>
                        <div class="p-3 rounded text-danger font-weight-bold" style="font-family: monospace; font-size: 0.88rem; background-color: #fff7ed; border: 1px solid #ffedd5;">
                            {{ $errorDescription }}
                        </div>
                    </div>
                    <div>
                        <label class="text-muted small text-uppercase font-weight-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">{{ __tr('Solution possible') }}</label>
                        <div class="p-3 rounded text-dark font-weight-500" style="font-size: 0.88rem; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            {{ $possibleSolution }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raccordement Webhook & Quick Actions Card -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3" style="border-bottom: 1px solid #f1f5f9 !important;">
                    <h5 class="mb-0 font-weight-bold text-dark" style="font-size: 1rem;">
                        <i class="fas fa-link text-primary mr-2"></i> {{ __tr('Raccordement Webhook') }}
                    </h5>
                </div>
                <div class="card-body pt-3 d-flex flex-column justify-content-between">
                    <div>
                        <label class="text-muted small text-uppercase font-weight-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">{{ __tr('URL du Webhook Officiel') }}</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-sm" value="{{ $webhookUrl }}" readonly style="font-size: 0.82rem; background-color: #f8fafc; border-radius: 8px 0 0 8px;">
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-outline-primary px-3" type="button" style="border-radius: 0 8px 8px 0;" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); alert('{{ __tr('URL Webhook copiée !') }}');">
                                    {{ __tr('Copier') }}
                                </button>
                            </div>
                        </div>
                        <p class="text-muted small mb-3" style="line-height: 1.45; font-size: 0.8rem;">
                            {{ __tr('Cette URL reçoit en temps réel les notifications et messages clients depuis les serveurs Meta.') }}
                        </p>
                    </div>

                    <div>
                        <a href="{{ route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) }}" class="btn btn-outline-primary btn-block py-2 mb-2 font-weight-bold" style="border-radius: 10px; font-size: 0.88rem;">
                            {{ __tr('Paramètres API') }}
                        </a>
                        <button type="button" class="btn btn-primary text-white btn-block py-2 font-weight-bold shadow-sm" style="border: 0; border-radius: 10px; font-size: 0.88rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);" onclick="window.location.reload();">
                            {{ __tr('Actualiser') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
