@extends('layouts.app', ['title' => __tr('Logs Messages Envoyés')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Logs Messages Envoyés'),
    'description' => __tr('Historique des messages WhatsApp envoyés par la plateforme aux vendeurs (bienvenue, relances abonnement, diffusions manuelles).'),
    'class' => 'col-lg-12'
])

<div class="container-fluid mt--7 pb-5">
    <div class="row">
        <div class="col-xl-12 mb-5 mb-xl-0">
            <div class="card shadow">
                <div class="card-body">

                    @if(empty($saasAdminVendorId))
                        <div class="alert alert-warning">
                            {{ __tr("Vous devez d'abord configurer le compte expéditeur dans Configurations > SaaS Automation.") }}
                        </div>
                    @elseif($logs->isEmpty())
                        <div class="alert alert-light">
                            {{ __tr("Aucun message envoyé par la plateforme pour le moment.") }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __tr('Date') }}</th>
                                        <th>{{ __tr('Vendeur') }}</th>
                                        <th>{{ __tr('Numéro') }}</th>
                                        <th>{{ __tr('Message') }}</th>
                                        <th>{{ __tr('Statut') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td class="text-nowrap">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ $log->matched_vendor_title ?: __tr('Inconnu') }}</td>
                                            <td class="text-nowrap">{{ $log->contact_wa_id }}</td>
                                            <td style="max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->message }}">
                                                {{ $log->message }}
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'read' => 'badge-primary',
                                                        'delivered' => 'badge-success',
                                                        'sent' => 'badge-info',
                                                        'accepted' => 'badge-secondary',
                                                        'failed' => 'badge-danger',
                                                    ];
                                                    $statusClass = $statusColors[$log->status] ?? 'badge-light';
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ $log->status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mt-2">{{ __tr('Les __count__ derniers messages sont affichés.', ['__count__' => $logs->count()]) }}</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
