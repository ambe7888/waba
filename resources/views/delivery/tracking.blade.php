@php
/**
* Component     : Delivery
* File          : delivery.tracking.blade.php
----------------------------------------------------------------------------- */
@endphp

@extends('layouts.app', ['title' => __tr('Livraison')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Suivi des livraisons'),
    'description' => __tr('Commandes actuellement assignées à un livreur et en cours de livraison.'),
])

<div class="container-fluid mt-lg--6">
    <div class="row mb-3">
        <div class="col-xl-12">
            <div class="float-right">
                <a href="{{ route('vendor.delivery.drivers.view') }}" class="btn btn-outline-primary btn-sm lw-btn">
                    <i class="fa fa-users"></i> {{ __tr('Gérer les livreurs') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <x-lw.datatable id="lwDeliveryTrackingList" :url="route('vendor.delivery.tracking.list')">
                        <th data-orderable="true" data-name="_uid" data-template="#deliveryRefTemplate">{{ __tr('Réf / Assignée le') }}</th>
                        <th data-orderable="false" data-name="client_formatted">{{ __tr('Client') }}</th>
                        <th data-orderable="false" data-name="address_formatted">{{ __tr('Adresse') }}</th>
                        <th data-orderable="false" data-name="driver_formatted">{{ __tr('Livreur') }}</th>
                        <th data-orderable="false" data-name="total_formatted">{{ __tr('Montant') }}</th>
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

<script type="text/template" id="deliveryActionsTemplate">
    <div class="d-inline-flex align-items-center" style="gap: 6px;">
        <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="markDelivery('<%- __tData._uid %>', 'delivered')">
            <i class="fa fa-check"></i> {{ __tr('Livré') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold" onclick="markDelivery('<%- __tData._uid %>', 'failed')">
            <i class="fa fa-times"></i> {{ __tr('Non livré') }}
        </button>
    </div>
</script>

<div x-show="false" x-cloak class="d-none">{{-- $drivers available if a future driver filter is added --}}</div>

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
                        showSuccessMessage(response.message || (response.data && response.data.message) || "{{ __tr('Statut mis à jour.') }}");
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
@endsection
