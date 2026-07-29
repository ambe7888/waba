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
            <a data-method="post" data-callback="appFuncs.modelSuccessCallback" data-callback-params="{{ json_encode(['datatableId' => '#lwAudienceList']) }}" href="<%= __Utils.apiURL('{{ route('vendor.campaign_audience.write.delete', ['audienceUid' => 'audienceUid']) }}', {'audienceUid': __tData._uid}) %>" class="dropdown-item lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteAudience-template"><i class="fa fa-trash text-danger"></i> <?= __tr('Supprimer') ?></a>
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
                        <input type="text" name="title" id="title" class="form-control" required placeholder="<?= __tr('ex. Tous mes clients, Offre Promo, etc.') ?>">
                    </div>

                    <div class="card border mb-3" style="background-color: #f8fafc; border-color: #e2e8f0 !important; border-radius: 8px;">
                        <div class="card-body py-2 px-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="is_all_contacts" class="custom-control-input" id="isAllContactsCheck" onchange="toggleAllContactsOption(this.checked)">
                                <label class="custom-control-label font-weight-bold text-dark mb-0" for="isAllContactsCheck" style="cursor: pointer;">
                                    ⚡ <?= __tr('Cibler TOUS les contacts du compte (Base complète)') ?>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <?= __tr('Cochez cette option si vous souhaitez cibler 100% de vos contacts sans ralentissement.') ?>
                            </small>
                        </div>
                    </div>

                    <div id="allContactsNotice" class="alert alert-success d-none mb-3" style="border-radius: 8px;">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong><?= __tr('Mode Base Complète Activé !') ?></strong> <?= __tr('Cette audience ciblera l\'intégralité de vos contacts actuels et futurs.') ?>
                    </div>

                    <div id="audienceSpecificTargetSection">
                        <div class="form-group">
                            <label for="contacts" class="form-control-label">
                                <?= __tr('Contacts Individuels') ?>
                                <span id="lwSelectedContactsBadge" class="text-primary font-weight-normal ml-1" style="font-size: 0.82rem;">(0 sélectionné)</span>
                            </label>
                            <select name="contacts[]" id="contacts" class="form-control" multiple data-lw-plugin="lwSelectize" data-max-options="100000">
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->_id }}">{{ $contact->first_name }} {{ $contact->last_name }} (+{{ $contact->wa_id }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1"><?= __tr('Sélectionnez les contacts pour cette audience') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="groups" class="form-control-label">
                                <?= __tr('Groupes de contacts') ?>
                                <span id="lwSelectedGroupsBadge" class="text-primary font-weight-normal ml-1" style="font-size: 0.82rem;">(0 sélectionné)</span>
                            </label>
                            <select name="groups[]" id="groups" class="form-control" multiple data-lw-plugin="lwSelectize" data-max-options="100000">
                                @foreach($groups as $group)
                                    <option value="{{ $group->_id }}">{{ $group->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="labels" class="form-control-label">
                                <?= __tr('Étiquettes') ?>
                                <span id="lwSelectedLabelsBadge" class="text-primary font-weight-normal ml-1" style="font-size: 0.82rem;">(0 sélectionnée)</span>
                            </label>
                            <select name="labels[]" id="labels" class="form-control" multiple data-lw-plugin="lwSelectize" data-max-options="100000">
                                @foreach($labels as $label)
                                    <option value="{{ $label->_id }}">{{ $label->title }}</option>
                                @endforeach
                            </select>
                        </div>
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
    function updateAudienceSelectionCounts() {
        let contactsCount = ($('#contacts').val() || []).length;
        let groupsCount = ($('#groups').val() || []).length;
        let labelsCount = ($('#labels').val() || []).length;

        $('#lwSelectedContactsBadge').text('(' + contactsCount + ' <?= __tr("sélectionné(s)") ?>)');
        $('#lwSelectedGroupsBadge').text('(' + groupsCount + ' <?= __tr("sélectionné(s)") ?>)');
        $('#lwSelectedLabelsBadge').text('(' + labelsCount + ' <?= __tr("sélectionnée(s)") ?>)');
    }

    $('#contacts, #groups, #labels').on('change', updateAudienceSelectionCounts);

    // Prevent double-click submission
    $('#audienceForm').on('submit', function() {
        var $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) {
            return false; // Already submitting
        }
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?= __tr("Enregistrement...") ?>');
    });

    function toggleAllContactsOption(isAll) {
        if (isAll) {
            $('#audienceSpecificTargetSection').addClass('d-none');
            $('#allContactsNotice').removeClass('d-none');
        } else {
            $('#audienceSpecificTargetSection').removeClass('d-none');
            $('#allContactsNotice').addClass('d-none');
        }
    }

    function reEnableSubmitButton() {
        var $btn = $('#audienceForm').find('button[type="submit"]');
        $btn.prop('disabled', false).html('<?= __tr("Enregistrer") ?>');
    }

    function onAudienceSaved(response) {
        reEnableSubmitButton();
        if (response.reaction == 1) {
            $('#lwCreateAudienceModal').modal('hide');
            if (window.lwDataTablesInstance && window.lwDataTablesInstance.lwAudienceList) {
                window.lwDataTablesInstance.lwAudienceList.ajax.reload();
            } else {
                location.reload();
            }
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

        let parsedContacts = parseItems(contacts);
        let isAll = parsedContacts.includes('all_contacts');
        $('#isAllContactsCheck').prop('checked', isAll);
        toggleAllContactsOption(isAll);

        if(form.find('#contacts')[0].selectize) {
            form.find('#contacts')[0].selectize.setValue(parsedContacts);
        }
        if(form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.setValue(parseItems(groups));
        }
        if(form.find('#labels')[0].selectize) {
            form.find('#labels')[0].selectize.setValue(parseItems(labels));
        }
        
        updateAudienceSelectionCounts();
        $('#lwCreateAudienceModal').modal('show');
    }

    $('#lwCreateAudienceModal').on('show.bs.modal hidden.bs.modal', function () {
        setTimeout(updateAudienceSelectionCounts, 100);
    });

    $('#lwCreateAudienceModal').on('hidden.bs.modal', function () {
        let form = $('#audienceForm');
        form.attr('action', "{{ route('vendor.campaign_audience.write.process') }}");
        form.trigger('reset');
        $('#isAllContactsCheck').prop('checked', false);
        toggleAllContactsOption(false);
        reEnableSubmitButton();
        if(form.find('#contacts')[0].selectize) {
            form.find('#contacts')[0].selectize.clear();
        }
        if(form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.clear();
        }
        if(form.find('#labels')[0].selectize) {
            form.find('#labels')[0].selectize.clear();
        }
        updateAudienceSelectionCounts();
    });
</script>
@endpush
</div>
@endsection
