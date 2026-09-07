@php
/**
* Component     : Delivery
* File          : delivery.tracking.blade.php
----------------------------------------------------------------------------- */
@endphp

@extends('layouts.app', ['title' => __tr('Livraison')])
@section('content')
<style>
.lw-recap-tile {
    border: 1px solid #e4e7ec;
    border-radius: 14px;
    padding: 14px 18px;
    background: #ffffff;
}
.lw-recap-tile .num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.lw-recap-tile .label { font-size: 0.76rem; color: #626a79; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; margin-top: 4px; }
.lw-delivery-tabs a {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.85rem;
    color: #626a79;
    background: #f7f8fa;
    border: 1px solid #e4e7ec;
    margin-right: 8px;
    text-decoration: none !important;
}
.lw-delivery-tabs a.active {
    background: #0c6b53;
    color: #ffffff;
    border-color: #0c6b53;
}
</style>
@include('users.partials.header', [
    'title' => __tr('Suivi des livraisons'),
    'description' => __tr('Toutes les commandes assignées à un livreur : en cours, livrées et non livrées.'),
])

<div class="container-fluid mt-lg--6">
    <div class="row mb-3">
        <div class="col-xl-12">
            <div class="float-right">
                <a href="{{ route('vendor.delivery.drivers.view') }}" class="btn btn-outline-primary btn-sm lw-btn">
                    {{ __tr('Gérer les livreurs') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="lw-recap-tile">
                <div class="num" style="color: #1e40af;">{{ $recapCounts['in_delivery'] }}</div>
                <div class="label">{{ __tr('En cours') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="lw-recap-tile">
                <div class="num" style="color: #04704e;">{{ $recapCounts['delivered'] }}</div>
                <div class="label">{{ __tr('Livrées') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="lw-recap-tile">
                <div class="num" style="color: #9f1239;">{{ $recapCounts['delivery_failed'] }}</div>
                <div class="label">{{ __tr('Non livrées') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="lw-recap-tile">
                <div class="num">{{ $recapCounts['all'] }}</div>
                <div class="label">{{ __tr('Total assigné') }}</div>
            </div>
        </div>
    </div>

    <div class="row mb-3 align-items-center">
        <div class="col-md-8 lw-delivery-tabs mb-2">
            <a href="{{ route('vendor.delivery.tracking.view', ['status_filter' => 'in_delivery', 'driver_filter' => $driverFilter]) }}" class="{{ $statusFilter === 'in_delivery' ? 'active' : '' }}">{{ __tr('En cours') }}</a>
            <a href="{{ route('vendor.delivery.tracking.view', ['status_filter' => 'delivered', 'driver_filter' => $driverFilter]) }}" class="{{ $statusFilter === 'delivered' ? 'active' : '' }}">{{ __tr('Livrées') }}</a>
            <a href="{{ route('vendor.delivery.tracking.view', ['status_filter' => 'delivery_failed', 'driver_filter' => $driverFilter]) }}" class="{{ $statusFilter === 'delivery_failed' ? 'active' : '' }}">{{ __tr('Non livrées') }}</a>
            <a href="{{ route('vendor.delivery.tracking.view', ['status_filter' => 'all', 'driver_filter' => $driverFilter]) }}" class="{{ $statusFilter === 'all' ? 'active' : '' }}">{{ __tr('Toutes') }}</a>
        </div>
        <div class="col-md-4 mb-2">
            <select class="form-control form-control-sm" onchange="window.location.href = '{{ route('vendor.delivery.tracking.view') }}?status_filter={{ $statusFilter }}&driver_filter=' + this.value">
                <option value="">{{ __tr('Tous les livreurs') }}</option>
                @foreach($drivers as $driver)
                <option value="{{ $driver->_uid }}" {{ $driverFilter === $driver->_uid ? 'selected' : '' }}>{{ $driver->full_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <x-lw.datatable id="lwDeliveryTrackingList" :url="route('vendor.delivery.tracking.list', ['status_filter' => $statusFilter, 'driver_filter' => $driverFilter])">
                        <th data-orderable="true" data-name="_uid" data-template="#deliveryRefTemplate">{{ __tr('Réf / Assignée le') }}</th>
                        <th data-orderable="false" data-name="client_formatted">{{ __tr('Client') }}</th>
                        <th data-orderable="false" data-name="address_formatted">{{ __tr('Adresse de livraison') }}</th>
                        <th data-orderable="false" data-name="driver_formatted">{{ __tr('Livreur') }}</th>
                        <th data-orderable="false" data-name="total_formatted">{{ __tr('Montant') }}</th>
                        <th data-orderable="false" data-name="status" data-template="#deliveryStatusTemplate">{{ __tr('Statut') }}</th>
                        <th data-template="#deliveryActionsTemplate" data-name="_uid">{{ __tr('Actions') }}</th>
                    </x-lw.datatable>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/template" id="deliveryRefTemplate">
    <span class="font-weight-bold text-dark"><%- '#' + __tData._uid.substring(0, 8) %></span>
    <small class="text-muted d-block"><%- __tData.assigned_at_formatted %></small>
</script>

<script type="text/template" id="deliveryStatusTemplate">
    <span style="background:<%- __tData.status === 'delivered' ? '#e1f5ec' : (__tData.status === 'delivery_failed' ? '#fde2e1' : '#dbeafe') %>; color:<%- __tData.status === 'delivered' ? '#04704e' : (__tData.status === 'delivery_failed' ? '#9f1239' : '#1e40af') %>; padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 0.76rem;">
        <%- __tData.status === 'delivered' ? '{{ __tr("Livrée") }}' : (__tData.status === 'delivery_failed' ? '{{ __tr("Non livrée") }}' : '{{ __tr("En cours") }}') %>
    </span>
</script>

<script type="text/template" id="deliveryActionsTemplate">
    <% if (__tData.status === 'in_delivery') { %>
    <div class="d-inline-flex align-items-center" style="gap: 6px;">
        <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="markDelivery('<%- __tData._uid %>', 'delivered')">
            {{ __tr('Livré') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold" onclick="markDelivery('<%- __tData._uid %>', 'failed')">
            {{ __tr('Non livré') }}
        </button>
    </div>
    <% } else { %>
    <span class="text-muted small">—</span>
    <% } %>
</script>

@push('appScripts')
<script>
    function markDelivery(orderUid, action) {
        var label = action === 'delivered' ? "{{ __tr('livrée') }}" : "{{ __tr('non livrée') }}";
        showConfirmation("{{ __tr('Confirmer que cette commande est ') }}" + label + " ?", function() {
            __DataRequest.post(
                '{{ route("vendor.delivery.tracking.write.status", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid),
                { action: action },
                function(response) {
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        var movedToTab = action === 'delivered' ? "{{ __tr('Livrées') }}" : "{{ __tr('Non livrées') }}";
                        showSuccessMessage((response.message || (response.data && response.data.message) || "{{ __tr('Statut mis à jour.') }}") + " {{ __tr('— retrouvez-la dans l\'onglet') }} « " + movedToTab + " ».");
                        if (window.lwDataTablesInstance && window.lwDataTablesInstance.lwDeliveryTrackingList) {
                            window.lwDataTablesInstance.lwDeliveryTrackingList.ajax.reload(null, false);
                        }
                    } else {
                        showErrorMessage(response.message || (response.data && response.data.message) || "{{ __tr('Erreur.') }}");
                    }
                }
            );
        }, {
            confirmButtonText: "{{ __tr('Oui') }}",
            cancelButtonText: "{{ __tr('Non') }}",
            type: action === 'delivered' ? 'success' : 'error'
        });
    }
</script>
@endpush

@push('vendorChannelBroadcastStack')
if (data.eventModelUpdate && data.eventModelUpdate.delivery_status_update) {
    if (window.lwDataTablesInstance && window.lwDataTablesInstance.lwDeliveryTrackingList) {
        window.lwDataTablesInstance.lwDeliveryTrackingList.ajax.reload(null, false);
    }
}
@endpush
@endsection
