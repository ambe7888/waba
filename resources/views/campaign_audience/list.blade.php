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

                    <div class="card bg-secondary border-0 mb-3">
                        <div class="card-body py-2 px-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="is_all_contacts" class="custom-control-input" id="isAllContactsCheck" onchange="toggleAllContactsOption(this.checked)">
                                <label class="custom-control-label font-weight-bold text-dark mb-0" for="isAllContactsCheck" style="cursor: pointer;">
                                    <?= __tr('Cibler tous les contacts du compte (Même les nouveaux)') ?>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <?= __tr('Cochez cette option si vous souhaitez cibler 100% de vos contacts sans ralentissement.') ?>
                            </small>
                        </div>
                    </div>

                    <div id="allContactsNotice" class="alert alert-success d-none mb-3">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong><?= __tr('Mode Base Complète Activé !') ?></strong> <?= __tr('Cette audience ciblera l\'intégralité de vos contacts actuels et futurs.') ?>
                    </div>

                    <div id="audienceSpecificTargetSection">
                        <div class="form-group">
                            <label for="contacts"><?= __tr('Contacts Individuels') ?></label>
                            <select name="contacts[]" id="contacts" class="form-control" multiple placeholder="<?= __tr('Tapez pour rechercher un contact...') ?>">
                            </select>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted"><?= __tr('Tapez un nom ou un numéro pour rechercher') ?></small>
                                <small class="badge badge-pill badge-primary font-weight-bold" id="lwSelectedContactsBadge" style="font-size: 0.85rem;">
                                    0 <?= __tr('contact(s) sélectionné(s)') ?>
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="groups" class="mb-0"><?= __tr('Groupes de contacts') ?></label>
                                <small class="badge badge-pill badge-info font-weight-bold" id="lwSelectedGroupsBadge" style="font-size: 0.85rem;">
                                    0 <?= __tr('groupe(s) sélectionné(s)') ?>
                                </small>
                            </div>
                            <select name="groups[]" id="groups" class="form-control" multiple data-lw-plugin="lwSelectize" data-max-options="100000">
                                @foreach($groups as $group)
                                    <option value="{{ $group->_id }}">{{ $group->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="labels" class="mb-0"><?= __tr('Étiquettes') ?></label>
                                <small class="badge badge-pill badge-info font-weight-bold" id="lwSelectedLabelsBadge" style="font-size: 0.85rem;">
                                    0 <?= __tr('étiquette(s) sélectionnée(s)') ?>
                                </small>
                            </div>
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
    // ── Selectize AJAX Remote for Contacts ──
    var contactsSelectize;
    var searchDebounceTimer;
    $(document).ready(function() {
        contactsSelectize = $('#contacts').selectize({
            plugins: ['remove_button'],
            valueField: 'value',
            labelField: 'text',
            searchField: ['text'],
            maxOptions: 50,
            create: false,
            placeholder: '<?= __tr("Tapez pour rechercher un contact...") ?>',
            load: function(query, callback) {
                if (!query.length || query.length < 2) return callback();
                var self = this;
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    $.ajax({
                        url: '{{ route("vendor.campaign_audience.contacts.search") }}',
                        type: 'GET',
                        data: { q: query },
                        dataType: 'json',
                        error: function() { callback(); },
                        success: function(res) { callback(res); }
                    });
                }, 300);
            },
            onChange: function() {
                updateAudienceSelectionCounts();
            }
        })[0].selectize;
    });

    function updateAudienceSelectionCounts() {
        let contactsCount = contactsSelectize ? contactsSelectize.items.length : 0;
        let groupsCount = ($('#groups').val() || []).length;
        let labelsCount = ($('#labels').val() || []).length;

        $('#lwSelectedContactsBadge').text(contactsCount + ' <?= __tr("contact(s) sélectionné(s)") ?>');
        $('#lwSelectedGroupsBadge').text(groupsCount + ' <?= __tr("groupe(s) sélectionné(s)") ?>');
        $('#lwSelectedLabelsBadge').text(labelsCount + ' <?= __tr("étiquette(s) sélectionnée(s)") ?>');
    }

    $('#groups, #labels').on('change', updateAudienceSelectionCounts);

    // Prevent double-click submission
    $('#audienceForm').on('submit', function() {
        var $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) {
            return false; // Already submitting
        }
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?= __tr("Enregistrement...") ?>');
    });

    function selectAllAudienceContacts() {
        // Not applicable with AJAX mode — show a hint
        alert('<?= __tr("Utilisez l\'option \"Cibler tous les contacts\" pour sélectionner la totalité de vos contacts.") ?>');
    }

    function deselectAllAudienceContacts() {
        if (contactsSelectize) {
            contactsSelectize.clear();
            updateAudienceSelectionCounts();
        }
    }

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

        // Pre-load existing contacts via AJAX for edit mode
        if (contactsSelectize && parsedContacts.length > 0 && !isAll) {
            $.ajax({
                url: '{{ route("vendor.campaign_audience.contacts.fetch") }}',
                type: 'GET',
                data: { ids: parsedContacts },
                dataType: 'json',
                success: function(res) {
                    contactsSelectize.clearOptions();
                    contactsSelectize.clear(true);
                    res.forEach(function(item) {
                        contactsSelectize.addOption(item);
                    });
                    contactsSelectize.setValue(res.map(function(i) { return i.value; }), true);
                    updateAudienceSelectionCounts();
                }
            });
        }

        if(form.find('#groups')[0] && form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.setValue(parseItems(groups));
        }
        if(form.find('#labels')[0] && form.find('#labels')[0].selectize) {
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
        if(contactsSelectize) {
            contactsSelectize.clearOptions();
            contactsSelectize.clear();
        }
        if(form.find('#groups')[0] && form.find('#groups')[0].selectize) {
            form.find('#groups')[0].selectize.clear();
        }
        if(form.find('#labels')[0] && form.find('#labels')[0].selectize) {
            form.find('#labels')[0].selectize.clear();
        }
        updateAudienceSelectionCounts();
    });
</script>
@endpush
</div>
@endsection
