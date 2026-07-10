@php
$hasManageAccess = hasVendorAccess('manage_campaigns');
@endphp

@extends('layouts.app', ['title' => __tr('Audiences')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Audiences de Campagne'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                @if($hasManageAccess)
                <button type="button" class="btn btn-primary btn-sm lw-btn" data-toggle="modal" data-target="#lwCreateAudienceModal">
                    <i class="fa fa-plus"></i> <?= __tr('Créer une Audience') ?>
                </button>
                @endif
            </div>
        </div>
    </div>

<!-- Datatable Container -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <x-lw.datatable id="lwAudienceList" :url="route('vendor.campaign_audience.read.list')">
                    <th data-orderable="true" data-name="title"><?= __tr('Titre') ?></th>
                    <th data-orderable="false" data-name="contacts_formatted"><?= __tr('Contacts') ?></th>
                    <th data-orderable="false" data-name="groups_formatted"><?= __tr('Groupes') ?></th>
                    <th data-orderable="false" data-name="labels_formatted"><?= __tr('Étiquettes') ?></th>
                    <th data-orderable="true" data-name="created_at"><?= __tr('Créé le') ?></th>
                    <th data-template="#audienceActionsTemplate" data-name="_uid"><?= __tr('Actions') ?></th>
                </x-lw.datatable>
            </div>
        </div>
    </div>
</div>

<!-- Audience Actions Template -->
<script type="text/template" id="audienceActionsTemplate">
    <div class="btn-group">
        <button type="button" class="btn btn-black btn-sm dropdown-toggle lw-datatable-action-dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            @if($hasManageAccess)
            <a class="dropdown-item lw-ajax-link-action" href="#" onclick="editAudience('<%- __tData._uid %>', '<%- __tData.title %>', <%- JSON.stringify(__tData.contacts_raw) %>, <%- JSON.stringify(__tData.groups_raw) %>, <%- JSON.stringify(__tData.labels_raw) %>); return false;"><i class="fa fa-edit"></i> <?= __tr('Modifier') ?></a>
            <a data-method="post" href="<%= __Utils.apiURL('{{ route('vendor.campaign_audience.write.delete', ['audienceUid' => 'audienceUid']) }}', {'audienceUid': __tData._uid}) %>" class="dropdown-item lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteAudience-template"><i class="fa fa-trash text-danger"></i> <?= __tr('Supprimer') ?></a>
            @endif
        </div>
    </div>
</script>

<script type="text/template" id="lwDeleteAudience-template">
    <h2><?= __tr('Êtes-vous sûr ?') ?></h2>
    <p><?= __tr('Voulez-vous vraiment supprimer cette audience ?') ?></p>
</script>

<!-- Create / Edit Audience Modal -->
<div class="modal fade" id="lwCreateAudienceModal" tabindex="-1" role="dialog" aria-labelledby="lwCreateAudienceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lwCreateAudienceModalLabel"><?= __tr('Créer / Modifier Audience') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="lw-ajax-form lw-form" data-callback="onAudienceSaved" method="post" id="audienceForm" action="{{ route('vendor.campaign_audience.write.process') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title"><?= __tr('Titre de l\'audience') ?></label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="contacts"><?= __tr('Contacts Individuels') ?></label>
                        <select name="contacts[]" id="contacts" class="form-control" multiple data-lw-plugin="lwSelectize">
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->_id }}">{{ $contact->first_name }} {{ $contact->last_name }} (+{{ $contact->wa_id }})</option>
                            @endforeach
                        </select>
                        <small><?= __tr('Sélectionnez les contacts pour cette audience') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="groups"><?= __tr('Groupes de contacts') ?></label>
                        <select name="groups[]" id="groups" class="form-control" multiple data-lw-plugin="lwSelectize">
                            @foreach($groups as $group)
                                <option value="{{ $group->_id }}">{{ $group->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="labels"><?= __tr('Étiquettes') ?></label>
                        <select name="labels[]" id="labels" class="form-control" multiple data-lw-plugin="lwSelectize">
                            @foreach($labels as $label)
                                <option value="{{ $label->_id }}">{{ $label->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __tr('Fermer') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __tr('Enregistrer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('appScripts')
<script>
    function onAudienceSaved(response) {
        if (response.reaction == 1) {
            $('#lwCreateAudienceModal').modal('hide');
            window.lwDataTablesInstance.lwAudienceList.ajax.reload();
        }
    }

    function editAudience(uid, title, contacts, groups, labels) {
        let form = $('#audienceForm');
        form.attr('action', "{{ route('vendor.campaign_audience.write.process') }}/" + uid);
        form.find('#title').val(title);

        let parseItems = function(data) {
            if (!data) return [];
            if (Array.isArray(data)) return data.map(String);
            if (typeof data === 'string') {
                if (data.startsWith('[') && data.endsWith(']')) {
                    try {
                        return JSON.parse(data).map(String);
                    } catch(e) {}
                }
                return data.split(',').map(s => s.trim()).filter(Boolean);
            }
            return [String(data)];
        };

        if(form.find('#contacts')[0].selectize) {
            form.find('#contacts')[0].selectize.setValue(parseItems(contacts));
        }
        if(form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.setValue(parseItems(groups));
        }
        if(form.find('#labels')[0].selectize) {
            form.find('#labels')[0].selectize.setValue(parseItems(labels));
        }
        
        $('#lwCreateAudienceModal').modal('show');
    }

    $('#lwCreateAudienceModal').on('hidden.bs.modal', function () {
        let form = $('#audienceForm');
        form.attr('action', "{{ route('vendor.campaign_audience.write.process') }}");
        form.trigger('reset');
        if(form.find('#contacts')[0].selectize) {
            form.find('#contacts')[0].selectize.clear();
        }
        if(form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.clear();
        }
        if(form.find('#labels')[0].selectize) {
            form.find('#labels')[0].selectize.clear();
        }
    });
</script>
@endpush
</div>
@endsection
