@php
/**
* Component     : Delivery
* File          : delivery.drivers.blade.php
----------------------------------------------------------------------------- */
@endphp

@extends('layouts.app', ['title' => __tr('Livreurs')])
@section('content')
<style>
/* The app-wide .modal .modal-footer rule is position:fixed;bottom:0 --
   meant for tall, scrolling forms, but it detaches a SHORT modal's
   footer from its (short) card, pinning it near the bottom of the
   viewport instead. Restore normal in-flow footer positioning for
   the driver create/edit modal specifically. */
#lwCreateDriverModal .modal-footer {
    position: static !important;
    width: auto !important;
}
</style>
@include('users.partials.header', [
    'title' => __tr('Livraison — Livreurs'),
    'description' => __tr('Gérez votre équipe de livreurs et suivez leur charge de livraisons en cours.'),
])

<div class="container-fluid mt-lg--6">
    <div class="row mb-3">
        <div class="col-xl-12">
            <div class="float-right d-flex" style="gap: 10px;">
                <a href="{{ route('vendor.delivery.tracking.view') }}" class="btn btn-outline-primary btn-sm lw-btn mr-2">
                    <i class="fa fa-truck"></i> {{ __tr('Suivi des livraisons') }}
                </a>
                <button type="button" class="btn btn-primary btn-sm lw-btn" data-toggle="modal" data-target="#lwCreateDriverModal">
                    <i class="fa fa-plus"></i> {{ __tr('Ajouter un livreur') }}
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <x-lw.datatable id="lwDriversList" :url="route('vendor.delivery.drivers.list')">
                        <th data-orderable="true" data-name="full_name">{{ __tr('Nom') }}</th>
                        <th data-orderable="false" data-name="phone">{{ __tr('Numéro') }}</th>
                        <th data-orderable="false" data-name="zone">{{ __tr('Zone') }}</th>
                        <th data-orderable="false" data-name="vehicle_type">{{ __tr('Engin') }}</th>
                        <th data-orderable="false" data-name="active_deliveries_count">{{ __tr('En cours') }}</th>
                        <th data-orderable="false" data-name="delivered_count">{{ __tr('Livrées') }}</th>
                        <th data-orderable="false" data-name="failed_count">{{ __tr('Non livrées') }}</th>
                        <th data-orderable="false" data-name="success_rate_formatted">{{ __tr('Taux de réussite') }}</th>
                        <th data-orderable="false" data-name="window_formatted">{{ __tr('Fenêtre 24h') }}</th>
                        <th data-orderable="false" data-name="status_formatted">{{ __tr('Statut') }}</th>
                        <th data-template="#driverActionsTemplate" data-name="_uid">{{ __tr('Actions') }}</th>
                    </x-lw.datatable>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Driver Actions Template -->
<script type="text/template" id="driverActionsTemplate">
    <div class="btn-group">
        <button type="button" class="btn btn-black btn-sm dropdown-toggle lw-datatable-action-dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="#" onclick="editDriver(<%- JSON.stringify(__tData) %>); return false;"><i class="fa fa-edit"></i> {{ __tr('Modifier') }}</a>
            <a data-method="post" data-callback="appFuncs.modelSuccessCallback" data-callback-params="{{ json_encode(['datatableId' => '#lwDriversList']) }}" href="<%= __Utils.apiURL('{{ route('vendor.delivery.drivers.write.delete', ['driverUid' => 'driverUid']) }}', {'driverUid': __tData._uid}) %>" class="dropdown-item lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteDriver-template"><i class="fa fa-trash text-danger"></i> {{ __tr('Supprimer') }}</a>
        </div>
    </div>
</script>

<script type="text/template" id="lwDeleteDriver-template">
    <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
    <p>{{ __tr('Voulez-vous vraiment supprimer ce livreur ? Les commandes qui lui sont assignées seront désassignées.') }}</p>
</script>

<!-- Create / Edit Driver Modal -->
<div class="modal fade" id="lwCreateDriverModal" tabindex="-1" role="dialog" aria-labelledby="lwCreateDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lwCreateDriverModalLabel">{{ __tr('Ajouter / Modifier un livreur') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="lw-ajax-form lw-form" data-callback="onDriverSaved" method="post" id="driverForm" action="{{ route('vendor.delivery.drivers.write.process') }}">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="driverFirstName">{{ __tr('Prénom') }}</label>
                            <input type="text" name="first_name" id="driverFirstName" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="driverLastName">{{ __tr('Nom') }}</label>
                            <input type="text" name="last_name" id="driverLastName" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="driverPhone">{{ __tr('Numéro WhatsApp') }}</label>
                            <input type="text" name="phone" id="driverPhone" class="form-control" placeholder="ex. 22501020304" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="driverZone">{{ __tr('Zone') }}</label>
                            <input type="text" name="zone" id="driverZone" class="form-control" placeholder="ex. Cocody, Yopougon...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="driverAddress">{{ __tr('Adresse') }}</label>
                        <input type="text" name="address" id="driverAddress" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="driverVehicleType">{{ __tr('Engin') }}</label>
                        <input type="text" name="vehicle_type" id="driverVehicleType" class="form-control" placeholder="ex. Moto, Voiture, Vélo...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Fermer') }}</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">{{ __tr('Enregistrer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('appScripts')
<script>
    function onDriverSaved(response) {
        var $btn = $('#driverForm').find('button[type="submit"]');
        $btn.prop('disabled', false);
        if (response.reaction == 1) {
            $('#lwCreateDriverModal').modal('hide');
            if (window.lwDataTablesInstance && window.lwDataTablesInstance.lwDriversList) {
                window.lwDataTablesInstance.lwDriversList.ajax.reload();
            } else {
                location.reload();
            }
        }
    }

    function editDriver(data) {
        let form = $('#driverForm');
        form.attr('action', "{{ route('vendor.delivery.drivers.write.process') }}/" + data._uid);
        form.find('#driverFirstName').val(data.first_name);
        form.find('#driverLastName').val(data.last_name);
        form.find('#driverPhone').val(data.phone);
        form.find('#driverZone').val(data.zone);
        form.find('#driverAddress').val(data.address);
        form.find('#driverVehicleType').val(data.vehicle_type);
        $('#lwCreateDriverModal').modal('show');
    }

    $('#lwCreateDriverModal').on('hidden.bs.modal', function () {
        let form = $('#driverForm');
        form.attr('action', "{{ route('vendor.delivery.drivers.write.process') }}");
        form.trigger('reset');
    });
</script>
@endpush
@endsection
