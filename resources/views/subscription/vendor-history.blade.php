@php
/**
* Component : Subscription
* Controller : ManualSubscriptionController
* File : subscription.vendor-history.blade.php
*/
@endphp
@extends('layouts.app', ['title' => __tr('Mes Factures & Abonnements')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Mes Factures & Abonnements'),
    'description' => __tr('Retrouvez ici l\'historique complet de vos abonnements et téléchargez vos factures.'),
    'class' => 'col-lg-8'
])
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-0">

                    @if($subscriptions->isEmpty())
                        <div class="text-center py-5">
                            <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h5 class="text-muted font-weight-normal">{{ __tr('Aucun abonnement trouvé') }}</h5>
                            <p class="text-muted small mb-4">{{ __tr('Vos abonnements apparaîtront ici une fois que vous en aurez souscrit un.') }}</p>
                            <a href="{{ route('subscription.read.show') }}" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-plus mr-1"></i> {{ __tr('Voir les plans') }}
                            </a>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.92rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e9ecef;">
                                    <th class="px-4 py-3 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Plan') }}</th>
                                    <th class="px-3 py-3 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Montant') }}</th>
                                    <th class="px-3 py-3 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Date début') }}</th>
                                    <th class="px-3 py-3 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Expire le') }}</th>
                                    <th class="px-3 py-3 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Statut') }}</th>
                                    <th class="px-3 py-3 text-uppercase text-muted text-right" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">{{ __tr('Facture') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscriptions as $sub)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 flex-shrink-0"
                                                style="width: 36px; height: 36px; background: {{ $sub['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(100,116,139,0.1)' }}; color: {{ $sub['is_active'] ? '#10b981' : '#64748b' }};">
                                                <i class="fas fa-id-card" style="font-size: 0.85rem;"></i>
                                            </div>
                                            <div>
                                                <div style="color: #1e293b; font-weight: 600;">{{ $sub['plan_title'] }}</div>
                                                <div class="text-muted" style="font-size: 0.78rem;">{{ $sub['freq_title'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span style="color: #1e293b; font-weight: 600;">{{ $sub['charges'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-muted">{{ $sub['created_at'] }}</td>
                                    <td class="px-3 py-3 text-muted">
                                        @if($sub['is_expired'])
                                            <span class="text-danger">{{ $sub['ends_at'] }}</span>
                                        @else
                                            {{ $sub['ends_at'] }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($sub['is_active'])
                                            <span class="badge" style="background: rgba(16,185,129,0.12); color: #059669; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                <i class="fas fa-check-circle mr-1"></i> {{ __tr('Actif') }}
                                            </span>
                                        @elseif($sub['is_expired'])
                                            <span class="badge" style="background: rgba(239,68,68,0.1); color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                <i class="fas fa-times-circle mr-1"></i> {{ __tr('Expiré') }}
                                            </span>
                                        @else
                                            <span class="badge" style="background: rgba(251,191,36,0.12); color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                <i class="fas fa-clock mr-1"></i> {{ $sub['status'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('vendor.subscription.invoice.print', ['subscriptionUid' => $sub['_uid']]) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-light"
                                           style="border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; font-size: 0.82rem; padding: 5px 12px;"
                                           title="{{ __tr('Voir / Imprimer la facture') }}">
                                            <i class="fas fa-file-invoice mr-1"></i> {{ __tr('Facture') }}
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
