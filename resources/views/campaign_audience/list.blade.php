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
                    <div id="audienceSpecificTargetSection">
                        <!-- Contacts Individuels -->
                        <div class="form-group mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="contacts" class="mb-0 font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    <i class="fas fa-user text-primary mr-1"></i><?= __tr('Contacts Individuels') ?>
                                </label>
                                <span class="text-muted font-weight-normal" id="lwSelectedContactsBadge" style="font-size: 0.8rem;">
                                    0 <?= __tr('sélectionné(s)') ?>
                                </span>
                            </div>
                            <select name="contacts[]" id="contacts" class="form-control" multiple placeholder="<?= __tr('Cliquez pour voir la liste ou tapez un nom/numéro...') ?>">
                            </select>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.78rem;"><?= __tr('Cliquez pour dérouler la liste complète ou tapez un nom/numéro.') ?></small>
                        </div>

                        <!-- Groupes de contacts (Sélection visuelle sans touche Control) -->
                        <div class="form-group mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="mb-0 font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    <i class="fas fa-layer-group text-info mr-1"></i><?= __tr('Groupes de contacts') ?>
                                </label>
                                <span class="text-muted font-weight-normal" id="lwSelectedGroupsBadge" style="font-size: 0.8rem;">
                                    0 <?= __tr('sélectionné(s)') ?>
                                </span>
                            </div>
                            @if(count($groups) > 0)
                                <div class="d-flex flex-wrap" style="gap: 8px; max-height: 180px; overflow-y: auto; padding: 4px;">
                                    @foreach($groups as $group)
                                        <label class="custom-control custom-checkbox border rounded px-3 py-2 m-0 lw-checkable-pill" style="cursor: pointer; background: #f8fafc; transition: all 0.2s; user-select: none;">
                                            <input type="checkbox" name="groups[]" value="{{ $group->_id }}" class="custom-control-input group-checkbox" onchange="updateAudienceSelectionCounts()">
                                            <span class="custom-control-label font-weight-bold text-dark" style="font-size: 0.85rem;">
                                                {{ $group->title }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted small italic p-2 border rounded bg-light"><?= __tr('Aucun groupe disponible.') ?></div>
                            @endif
                        </div>

                        <!-- Étiquettes (Sélection visuelle sans touche Control) -->
                        <div class="form-group mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="mb-0 font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    <i class="fas fa-tags text-warning mr-1"></i><?= __tr('Étiquettes') ?>
                                </label>
                                <span class="text-muted font-weight-normal" id="lwSelectedLabelsBadge" style="font-size: 0.8rem;">
                                    0 <?= __tr('sélectionnée(s)') ?>
                                </span>
                            </div>
                            @if(count($labels) > 0)
                                <div class="d-flex flex-wrap" style="gap: 8px; max-height: 180px; overflow-y: auto; padding: 4px;">
                                    @foreach($labels as $label)
                                        <label class="custom-control custom-checkbox border rounded px-3 py-2 m-0 lw-checkable-pill" style="cursor: pointer; background: #f8fafc; transition: all 0.2s; user-select: none;">
                                            <input type="checkbox" name="labels[]" value="{{ $label->_id }}" class="custom-control-input label-checkbox" onchange="updateAudienceSelectionCounts()">
                                            <span class="custom-control-label font-weight-bold text-dark" style="font-size: 0.85rem;">
                                                {{ $label->title }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted small italic p-2 border rounded bg-light"><?= __tr('Aucune étiquette disponible.') ?></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __tr('Fermer') ?></button>
                    <button type="submit" class="btn btn-primary font-weight-bold" style="background: #10b981; border: none;"><?= __tr('Enregistrer l\'Audience') ?></button>
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
            maxOptions: 1000,
            preload: 'focus',
            create: false,
            placeholder: '<?= __tr("Cliquez pour voir la liste ou tapez un nom/numéro...") ?>',
            load: function(query, callback) {
                var self = this;
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    $.ajax({
                        url: '{{ route("vendor.campaign_audience.contacts.search") }}',
                        type: 'GET',
                        data: { q: query || '' },
                        dataType: 'json',
                        error: function() { callback(); },
                        success: function(res) { callback(res); }
                    });
                }, 150);
            },
            onChange: function() {
                updateAudienceSelectionCounts();
            }
        })[0].selectize;
    });

    function updateAudienceSelectionCounts() {
        let contactsCount = contactsSelectize ? contactsSelectize.items.length : 0;
        let groupsCount = $('.group-checkbox:checked').length;
        let labelsCount = $('.label-checkbox:checked').length;

        // Visual highlight for checkable pills
        $('.group-checkbox, .label-checkbox').each(function() {
            if ($(this).is(':checked')) {
                $(this).closest('.lw-checkable-pill').css({
                    'background': '#ecfdf5',
                    'border-color': '#10b981',
                    'color': '#065f46'
                });
            } else {
                $(this).closest('.lw-checkable-pill').css({
                    'background': '#f8fafc',
                    'border-color': '#e2e8f0',
                    'color': '#1e293b'
                });
            }
        });

        $('#lwSelectedContactsBadge').text(contactsCount + ' <?= __tr("sélectionné(s)") ?>');
        $('#lwSelectedGroupsBadge').text(groupsCount + ' <?= __tr("sélectionné(s)") ?>');
        $('#lwSelectedLabelsBadge').text(labelsCount + ' <?= __tr("sélectionnée(s)") ?>');
    }

    // Prevent double-click submission
    $('#audienceForm').on('submit', function() {
        var $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) {
            return false;
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
        $btn.prop('disabled', false).html('<?= __tr("Enregistrer l\'Audience") ?>');
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

        // Set group checkboxes
        $('.group-checkbox').prop('checked', false);
        let parsedGroups = parseItems(groups);
        parsedGroups.forEach(function(gid) {
            $('.group-checkbox[value="' + gid + '"]').prop('checked', true);
        });

        // Set label checkboxes
        $('.label-checkbox').prop('checked', false);
        let parsedLabels = parseItems(labels);
        parsedLabels.forEach(function(lid) {
            $('.label-checkbox[value="' + lid + '"]').prop('checked', true);
        });

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
        $('.group-checkbox, .label-checkbox').prop('checked', false);
        updateAudienceSelectionCounts();
    });
</script>
@endpush
</div>
@endsection

