@php
/**
* Component : Contact
* Controller : ContactController
* File : contact.list.blade.php
* ----------------------------------------------------------------------------- */
$currentGroup = $groupUid ? $vendorContactGroups->where('_uid', $groupUid)->first() : null;
@endphp
@extends('layouts.app', ['title' => __tr('Contacts')])
@section('content')
@include('users.partials.header', [
'title' => $groupUid ? __tr('__groupName__ group contacts', [
'__groupName__' => $currentGroup->title
]) : __tr('Contacts'),
'class' => 'col-lg-7'
])
@php
$groupDescription = $groupUid ? $currentGroup->description : '';
@endphp

<style>
    .lw-contact-page-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        border-radius: 16px;
    }
    .lw-action-btn-pill {
        border-radius: 30px !important;
        font-weight: 600;
        font-size: 0.825rem;
        padding: 0.45rem 1rem;
        transition: all 0.2s ease;
    }
    .lw-action-btn-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .lw-badge-group {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 500;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .lw-badge-country {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        font-weight: 500;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .lw-table-header-custom th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 12px 16px;
    }
    .lw-checkboxes {
        width: 19px !important;
        height: 19px !important;
        cursor: pointer !important;
        accent-color: #2563eb !important;
        transform: scale(1.15);
        transition: transform 0.15s ease;
    }
    .lw-checkboxes:hover {
        transform: scale(1.3);
    }
    .lw-checkbox-cell-wrapper {
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    .lw-checkbox-cell-wrapper:hover {
        background-color: rgba(37, 99, 235, 0.08);
    }
    #lwContactList tbody tr {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    #lwContactList tbody tr:hover {
        background-color: rgba(37, 99, 235, 0.05) !important;
    }
    .lw-contact-action-group .btn {
        padding: 0.35rem 0.65rem;
        font-size: 0.8rem;
        border-radius: 8px;
    }
    /* Modern Datatable Search Input Styling */
    div.dataTables_filter {
        padding: 8px 12px;
        margin-bottom: 8px;
    }
    div.dataTables_filter label {
        font-weight: 600;
        color: #475569;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        margin-bottom: 0;
    }
    div.dataTables_filter input[type="search"],
    div.dataTables_filter input.form-control,
    div.dataTables_filter input {
        height: 38px !important;
        padding: 6px 16px !important;
        margin-left: 8px !important;
        font-size: 0.875rem !important;
        border-radius: 20px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.2s ease !important;
        width: 240px !important;
    }
    div.dataTables_filter input[type="search"]:focus,
    div.dataTables_filter input.form-control:focus,
    div.dataTables_filter input:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        width: 280px !important;
    }
    .lw-filter-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #cbd5e1;
        border-radius: 12px;
    }
</style>

<div class="container-fluid mt-lg--6" x-data="initialContactsInfoData">
    <!-- Header Toolbar Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card lw-contact-page-card border-0 p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="mb-2 mb-md-0">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            @if ($groupUid)
                                <i class="fas fa-users text-primary mr-2"></i>{{ $currentGroup->title }}
                            @else
                                <i class="fas fa-address-book text-primary mr-2"></i>{{ __tr('Gestion des Contacts') }}
                            @endif
                        </h3>
                        @if ($groupDescription)
                            <p class="text-muted small mb-0 mt-1">{{ $groupDescription }}</p>
                        @endif
                    </div>
                    
                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                        @if ($groupUid)
                            <a class="btn btn-outline-secondary lw-action-btn-pill" href="{{ route('vendor.contact.group.read.list_view') }}">
                                <i class="fas fa-arrow-left mr-1"></i> {{ __tr('Groupes') }}
                            </a>
                            <a class="btn btn-outline-secondary lw-action-btn-pill" href="{{ route('vendor.contact.read.list_view') }}">
                                <i class="fas fa-users mr-1"></i> {{ __tr('Tous les Contacts') }}
                            </a>
                        @endif
                        @if(hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                            <button type="button" class="btn btn-primary lw-action-btn-pill shadow-sm" data-toggle="modal" data-target="#lwAddNewContact">
                                <i class="fas fa-plus mr-1"></i> {{ __tr('Nouveau Contact') }}
                            </button>
                        @endif
                        @if (!$groupUid)
                            @if(hasVendorAccess('manage_contacts', 'export_contacts'))
                                <button type="button" class="btn btn-outline-primary lw-action-btn-pill" data-toggle="modal" data-target="#lwExportDialog">
                                    <i class="fas fa-download mr-1"></i> {{ __tr('Exporter') }}
                                </button>
                            @endif
                            @if(hasVendorAccess('manage_contacts', 'import_contacts'))
                                <button type="button" class="btn btn-outline-primary lw-action-btn-pill" data-toggle="modal" data-target="#lwImportContactDialog">
                                    <i class="fas fa-upload mr-1"></i> {{ __tr('Importer') }}
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals Section -->
    {{-- import contacts --}}
    <x-lw.modal id="lwImportContactDialog" :header="__tr('Upload Contacts')" :hasForm="true"
        data-pre-callback="appFuncs.clearContainer">
        <x-lw.form id="lwImportContactDialogForm" :action="route('vendor.contact.write.import')"
            :data-callback-params="['modalId' => '#lwImportContactDialog', 'datatableId' => '#lwContactList']"
            data-callback="window.onImportProcessUpdate">
            <div class="lw-form-modal-body p-3">
            <div x-cloak class="text-center" x-show="existingImportRequestData.progress">
                <h1 class="text-success" x-text="existingImportRequestData.progressCountFormatted"></h1>
                <h4 x-show="existingImportRequestData.estimatedRemainingTime" class="text-muted">{{  __tr('Estimated time remaining') }}</h4>
                <h4 class="text-info" x-show="existingImportRequestData.estimatedRemainingTime" x-text="existingImportRequestData.estimatedRemainingTime"></h4>
                <h3>{{  __tr('Please wait ... contacts import is in progress') }}</h3>
                <div class="progress" role="progressbar" aria-label="{{ __tr('contacts import in progress') }}" :aria-valuenow="existingImportRequestData.progress" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar progress-bar-striped progress-bar-animated" :style="{'width':existingImportRequestData.progress + '%'}" style="width: 0%"></div>
                </div>
                <div>
                    <a class="lw-ajax-link-action btn btn-danger btn-sm mt-3" data-confirm="<?= __tr('Are you sure you want to abort importing contacts?') ?>" href="{{ route('vendor.contact.write.abort_import') }}" data-callback="__Utils.viewReload" role="button" data-method="post">{{  __tr('Abort') }}</a>
                    <div class="mt-2">
                        <small class="text-muted">{{  __tr('In case process stuck at some point, please reload page.') }}</small>
                    </div>
                </div>
            </div>
            {{-- if existing request is not in progress --}}
            <div x-cloak x-show="!existingImportRequestData.progress" class="">
                <div class="alert alert-warning border-0 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ __tr('Please use Template from Download contacts dialog') }}
                </div>
                <p>{{ __tr('You can import csv file with new contacts or existing updated.') }}</p>
                <div class="alert alert-light border rounded-lg">
                    <h5 class="font-weight-bold">{{ __tr('Conventions') }}</h5>
                    <h6 class="text-primary font-weight-bold mb-1">{{ __tr('Mobile Number') }}</h6>
                    <small class="text-muted d-block mb-3">
                        {{ __tr('Mobile number treated as unique entity, it should be with country code without prefixing 0 or +, if the Mobile number is found in the records other information for the same will get updated with data from the csv file.') }}
                    </small>
                    <h6 class="text-primary font-weight-bold mb-1">{{ __tr('Group') }}</h6>
                    <small class="text-muted">
                        {{ __tr('Use comma separated group title, make sure groups are already exists into the system. Groups won\'t be deleted, only new groups will be assigned.') }}
                    </small>
                </div>
                <div class="form-group">
                    <input id="lwImportDocumentFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select CSV File') }}" class="lw-file-uploader"
                        data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'vendor_contact_import') ?>"
                        data-file-input-element="#lwImportDocument" data-allowed-media='{{ getMediaRestriction('vendor_contact_import') }}'>
                    <input id="lwImportDocument" type="hidden" value="" name="document_name" />
                </div>
            </div>
            </div>
            <!-- form footer -->
            <div x-cloak x-show="!existingImportRequestData.progress" class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ __tr('Process Import') }}</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </x-lw.form>
    </x-lw.modal>
    {{-- /import contacts --}}

    {{-- export contacts --}}
    <x-lw.modal id="lwExportDialog" :header="__tr('Download Contacts')" :hasForm="true"
        data-pre-callback="appFuncs.clearContainer">
        <div class="lw-form-modal-body p-4">
            <div class="mb-4">
                <h5 class="font-weight-bold text-dark mb-1">{{ __tr('Export with Data') }}</h5>
                <p class="text-muted small">{{ __tr('You can export all contacts csv file and import it back with updated data.') }}</p>
                <a href="{{ route('vendor.contact.write.export', [
                    'exportType' => 'data',
                    'fileType' => 'csv',
                ]) }}" data-method="post" class="btn btn-primary btn-sm rounded-pill"><i class="fa fa-download mr-1"></i> {{ __tr('Télécharger CSV avec données') }}</a>
            </div>
            <hr>
            <div>
                <h5 class="font-weight-bold text-dark mb-1">{{ __tr('Blank CSV Template') }}</h5>
                <p class="text-muted small">{{ __tr('You can download blank csv file and fill with data according to column header and import it for updates.') }}</p>
                <a href="{{ route('vendor.contact.write.export', [
                    'exportType' => 'blank',
                    'fileType' => 'csv',
                ]) }}" data-method="post" class="btn btn-outline-primary btn-sm rounded-pill"><i class="fa fa-download mr-1"></i> {{ __tr('Télécharger modèle vierge') }}</a>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </x-lw.modal>
    {{-- /export contacts --}}

    <!-- Add New Contact Modal -->
    <x-lw.modal id="lwAddNewContact" :header="__tr('Add New Contact')" :hasForm="true"
        data-pre-callback="appFuncs.clearContainer">
        <x-lw.form id="lwAddNewContactForm" :action="route('vendor.contact.write.create')"
            :data-callback-params="['modalId' => '#lwAddNewContact', 'datatableId' => '#lwContactList']"
            data-callback="appFuncs.modelSuccessCallback">
            <div class="lw-form-modal-body p-3">
                <x-lw.input-field type="text" id="lwFirstNameField" data-form-group-class="" :label="__tr('First Name')" name="first_name" />
                <x-lw.input-field type="text" id="lwLastNameField" data-form-group-class="" :label="__tr('Last Name')" name="last_name" />
                <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwCountryField" data-form-group-class="" data-selected=" " :label="__tr('Country')" name="country">
                    <x-slot name="selectOptions">
                        <option value="">{{ __tr('Country') }}</option>
                        @foreach(getCountryPhoneCodes() as $getCountryCode)
                        <option value="{{ $getCountryCode['_id'] }}">{{ $getCountryCode['name'] }}</option>
                        @endforeach
                    </x-slot>
                </x-lw.input-field>
                <x-lw.input-field type="number" id="lwPhoneNumberField" data-form-group-class="" :label="__tr('Mobile Number')" name="phone_number" minlength="9" :helpText="__tr('Number should be with country code without 0 or +')" required="true" maxlength="20" />
                
                <?php $languages = include app_path('Yantrana/Support/languages.php'); ?>
                <div class="form-group mb-3">
                    <label for="lwSelectLanguage" class="font-weight-bold small text-muted"><?= __tr('Language') ?></label>
                    <select id="lwSelectLanguage" name="language_code" class="form-control" required>
                        @if(!__isEmpty($languages))
                            <option value="">{{ __tr('Select Language') }}</option>
                            @foreach($languages as $key => $language)
                            <option value="<?= $language['code'] ?>"><?= $language['language'] ?> (<?= $language['code'] ?>)</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <x-lw.input-field type="email" id="lwEmailField" data-form-group-class="" :label="__tr('Email')" name="email" />
                <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectGroupsField" data-form-group-class="" data-selected=" " :label="__tr('Groups')" name="contact_groups[]" multiple>
                    <x-slot name="selectOptions">
                        <option value="">{{ __tr('Select Groups') }}</option>
                        @foreach($vendorContactGroups as $vendorContactGroup)
                        <option value="{{ $vendorContactGroup['_id'] }}">{{ $vendorContactGroup['title'] }} {{ $vendorContactGroup['status'] == 5  ? __tr('(Archived)') : '' }}</option>
                        @endforeach
                    </x-slot>
                </x-lw.input-field>

                <div class="my-3">
                    <x-lw.checkbox id="lwPromotionalOpt" name="whatsapp_opt_out" data-color="#ff0000" data-size="small" value="1" data-lw-plugin="lwSwitchery" :label="__tr('Opt out Marketing Messages')" />
                </div>
                <div class="my-3">
                    @if(isAiBotAvailable())
                    <x-lw.checkbox id="lwAiBotEnable" :checked="getVendorSettings('default_enable_flowise_ai_bot_for_users')" name="enable_ai_bot" value="1" data-size="small" data-lw-plugin="lwSwitchery" :label="__tr('Enable AI Bot')" />
                    @endif
                </div>
                <div class="my-3">
                    <x-lw.checkbox id="lwReplyBotEnable" :checked="true" name="enable_reply_bot" value="1" data-size="small" data-lw-plugin="lwSwitchery" :label="__tr('Enable Reply Bot')" />
                </div>

                <fieldset class="border p-3 rounded-lg mt-3">
                    <legend class="w-auto px-2 small font-weight-bold text-muted">{{ __tr('Other Information') }}</legend>
                    @foreach ($vendorContactCustomFields as $vendorContactCustomField)
                    <x-lw.input-field type="{{ $vendorContactCustomField->input_type }}" id="lwCustomField{{ $vendorContactCustomField->_id }}" data-form-group-class="" :label="$vendorContactCustomField->input_name" name="custom_input_fields[{{ $vendorContactCustomField->_uid }}]" />
                    @endforeach
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </x-lw.form>
    </x-lw.modal>

    <!-- Details Contact Modal -->
    <x-lw.modal id="lwDetailsContact" :header="__tr('Contact Details')">
        <div id="lwDetailsContactBody" class="lw-form-modal-body p-3"></div>
        <script type="text/template" id="lwDetailsContactBody-template">
            <div class="p-2">
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('First Name') }}:</label>
                    <div class="lw-details-item font-weight-bold text-dark"><%- __tData.first_name %></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('Last Name') }}:</label>
                    <div class="lw-details-item font-weight-bold text-dark"><%- __tData.last_name %></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('Country') }}:</label>
                    <div class="lw-details-item"><%- __tData.country?.name %></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('Mobile Number') }}:</label>
                    <div class="lw-details-item text-primary font-weight-bold"><%- __tData.wa_id %></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('Language Code') }}:</label>
                    <div class="lw-details-item"><%- __tData.language_code %></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold mb-0">{{ __tr('Email') }}:</label>
                    <div class="lw-details-item"><%- __tData.email %></div>
                </div>
                <fieldset class="border p-3 rounded-lg mb-3">
                    <legend class="w-auto px-2 small font-weight-bold text-muted">{{ __tr('Groups') }}</legend>
                    <% _.forEach(__tData.groups, function(value, key) { %>
                        <span class="badge lw-badge-group mr-1 mb-1"><%- value.title %></span>
                    <% } ); %>
                </fieldset>
                <fieldset class="border p-3 rounded-lg">
                    <legend class="w-auto px-2 small font-weight-bold text-muted">{{ __tr('Other Information') }}</legend>
                    @foreach ($vendorContactCustomFields as $vendorContactCustomField)
                    <div class="mb-2">
                        <label class="small text-muted mb-0">{{ $vendorContactCustomField->input_name }}:</label>
                        <div class="lw-details-item"><%- _.get(_.find(__tData.custom_field_values, {'contact_custom_fields__id' : {{ $vendorContactCustomField->_id }} }), 'field_value') %></div>
                    </div>
                    @endforeach
                </fieldset>
            </div>
        </script>
    </x-lw.modal>

    <!-- Edit Contact Modal -->
    @include('contact.contact-edit-modal-partial')

    <!-- Main Table Container -->
    <div class="row">
        <div class="col-12" x-cloak :class="isAllContactDeleteInProgress ? 'lw-delete-all-contact-loader-container' : ''" 
            x-data="{isSelectedAll:false,selectedContacts: [], selectedGroupsForSelectedContacts:[], isAllContactDeleteInProgress: false,
            toggle(id) {
                if (this.selectedContacts.includes(id)) {
                    const index = this.selectedContacts.indexOf(id);
                    this.selectedContacts.splice(index, 1);
                    this.isSelectedAll = false;
                } else {
                    this.selectedContacts.push(id);
                    if($('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').length == this.selectedContacts.length) {
                        this.isSelectedAll = true;
                    }
                };
            },toggleAll() {
                if(!this.isSelectedAll) {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').not(':checked').trigger('click');
                    this.isSelectedAll = true;
                } else {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                    this.isSelectedAll = false;
                }
            },deleteSelectedContacts(groupUid) {
                var that = this,
                    confirmationMessage = groupUid 
                        ? '{{ __tr('Are you sure you want to remove selected contacts from this group?') }}' 
                        : '{{ __tr('Are you sure you want to delete all selected contacts?') }}';
                showConfirmation(confirmationMessage, function() {
                    __DataRequest.post('{{ route('vendor.contacts.selected.write.delete') }}', {
                        'selected_contacts' : that.selectedContacts,
                        'group_uid': groupUid
                    });
                }, {
                    confirmButtonText: '{{ __tr('Yes') }}',
                    cancelButtonText: '{{ __tr('No') }}',
                    type: 'error'
                });
            }, deleteAllContacts(groupId) {
                var that = this,
                    confirmationMessage = groupId ? '#lwRemoveAllContactsFromGroup-template' : '#lwDeleteAllContacts-template';
                showConfirmation(confirmationMessage, function() {
                    that.isAllContactDeleteInProgress = true;
                    __DataRequest.post('{{ route('vendor.contacts.all.write.delete') }}', {
                        'group_id': groupId   
                    }, function(responseData) {
                        that.isAllContactDeleteInProgress = false;
                    });
                }, {
                    confirmButtonText: '{{ __tr('Yes') }}',
                    cancelButtonText: '{{ __tr('No') }}',
                    type: 'error'
                });
            }, assignGroupsToSelectedContacts(){
                var that = this;
                __DataRequest.post('{{ route('vendor.contacts.selected.write.assign_groups') }}', {
                    'selected_contacts' : that.selectedContacts,
                    'selected_groups' : that.selectedGroupsForSelectedContacts
                });
                $('#lwAssignGroups').modal('hide');
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                this.isSelectedAll = false;
            }}" x-init="$('#lwContactList').on( 'draw.dt', function () {
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                isSelectedAll = false;
            } );">

            <div class="lw-all-contact-delete-message" x-show="isAllContactDeleteInProgress">
                <div class="lw-delete-contact-spinner"></div>
                {{ __tr('Please wait, all contacts deleting in progress...') }}
            </div>

            <!-- Table Control Bar: Actions left, Delete all right -->
            <div class="card lw-contact-page-card border-0 mb-4 p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
                    <!-- Left Toolbar Actions -->
                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                        @if(hasVendorAccess('manage_contacts', 'delete_contacts') or hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group') or hasVendorAccess('messaging'))
                            <button x-show="!isSelectedAll" class="btn btn-outline-secondary btn-sm rounded-pill font-weight-bold" @click="toggleAll">
                                <i class="far fa-check-square mr-1"></i> {{ __tr('Tout sélectionner') }}
                            </button>
                            <button x-show="isSelectedAll" class="btn btn-secondary btn-sm rounded-pill font-weight-bold" @click="toggleAll">
                                <i class="far fa-square mr-1"></i> {{ __tr('Tout désélectionner') }}
                            </button>
                        @endif

                        <div class="btn-group">
                            @if(hasVendorAccess('manage_contacts', 'delete_contacts') or hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group') or hasVendorAccess('messaging'))
                                <button :class="!selectedContacts.length ? 'disabled' : ''"
                                    class="btn btn-primary btn-sm rounded-pill dropdown-toggle font-weight-bold" type="button" data-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-layer-group mr-1"></i> {{ __tr('Actions groupées') }}
                                </button>
                            @endif
                            <div class="dropdown-menu shadow border-0 rounded-lg">
                                @if(hasVendorAccess('manage_contacts', 'delete_contacts'))
                                    <a class="dropdown-item text-danger" @click.prevent="deleteSelectedContacts('<?= $groupUid ?>')" href="#">
                                        <i class="fas fa-trash mr-2"></i> {{ $groupUid ? __tr('Remove Selected Contacts') : __tr('Delete Selected Contacts') }}
                                    </a>
                                @endif
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <a class="dropdown-item text-dark" data-toggle="modal" data-target="#lwAssignGroups" href="#">
                                        <i class="fas fa-users-cog mr-2"></i> {{ __tr('Assign Group to Selected Contacts') }}
                                    </a>
                                @endif
                                @if(hasVendorAccess('messaging'))
                                    <a class="dropdown-item text-dark lw-ajax-link-action" data-toggle="modal" data-target="#lwAssignTeamMember" data-response-template="#lwAssignTeamMemberBody" href="{{ route('vendor.team_member.read.list', ['contactIdOrUid' => 'bulk_action']) }}">
                                        <i class="fas fa-user-check mr-2"></i> {{ __tr('Assign Team Member') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if(!$groupUid)
                        <a data-pre-callback="appFuncs.clearContainer" title="{{ __tr('Advanced Contacts Filters') }}" class="btn btn-outline-primary btn-sm rounded-pill font-weight-bold lw-ajax-link-action" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}" data-toggle="modal" data-target="#lwContactFilter">
                            <i class="fas fa-filter mr-1"></i> {{ __tr('Filtres avancés') }}
                        </a>
                        @endif
                    </div>

                    <!-- Right Toolbar: Delete All / Vider le groupe -->
                    <div class="ml-auto">
                        @if(hasVendorAccess('manage_contacts', 'delete_contacts'))
                            <button class="btn btn-outline-danger btn-sm rounded-pill font-weight-bold" @click.prevent="deleteAllContacts('<?= $groupUid ?>')">
                                @if($groupUid)
                                    <i class="fa fa-user-times mr-1"></i> {!! __tr("Vider le groupe") !!}
                                @else
                                    <i class="fa fa-trash mr-1"></i> {{ __tr('Supprimer tout') }}
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                @if(!$groupUid)
                <div x-data="{ isFilterApplied: false, contactCount: 0, countString: '' }">
                    <div class="mt-3 p-3 lw-filter-card" x-show="isFilterApplied">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-filter text-primary mr-1"></i> {{ __tr('Filtre avancé actif') }}</h6>
                            <a x-show="isFilterApplied" type="button" class="btn btn-danger btn-sm rounded-pill font-weight-bold lw-ajax-link-action" data-method="post" x-bind:data-post-data="toJsonString({'clear_filter': true})" href="<?= route('vendor.contact.write.store_contact_filter') ?>" data-callback="__Utils.viewReload">
                                <i class="fas fa-times mr-1"></i> {{ __tr('Effacer filtres') }}
                            </a>
                        </div>
                        <span x-show="contactCount" class="mt-2 d-block">
                            <div x-text="countString" class="small text-muted mb-2"></div>
                            <div class="btn-group" role="group">
                                <a data-pre-callback="appFuncs.clearContainer" title="{{ __tr('Update Filter') }}" class="btn btn-dark btn-sm rounded-pill lw-ajax-link-action" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}" data-toggle="modal" data-target="#lwContactFilter"><i class="fas fa-edit mr-1"></i> {{ __tr('Modifier filtre') }}</a>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill ml-2" data-pre-callback="appFuncs.clearContainer" data-toggle="modal" data-target="#lwAddNewGroup">
                                        {{ __tr('Créer groupe avec ce résultat') }}
                                    </button>
                                @endif
                            </div>
                        </span>
                        <span x-show="!contactCount" class="small text-muted mt-2 d-block">
                            {{ __tr('Aucun contact ne correspond au filtre actuel.') }}
                            <a data-pre-callback="appFuncs.clearContainer" title="{{ __tr('Update Filter') }}" class="btn btn-dark btn-sm rounded-pill ml-2 lw-ajax-link-action" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}" data-toggle="modal" data-target="#lwContactFilter"><i class="fas fa-edit mr-1"></i> {{ __tr('Modifier filtre') }}</a>
                        </span>
                    </div>
                </div>
                @endif
            </div>

            <!-- Datatable Card with Optimized Compact Columns -->
            <div class="card lw-contact-page-card border-0 overflow-hidden">
                <div class="table-responsive py-2">
                    <x-lw.datatable data-page-length="500" id="lwContactList" class="table table-hover align-items-center mb-0" :url="route('vendor.contact.read.list', ['groupUid' => $groupUid])">
                        <th style="width: 1px;padding:0;" data-name="none"></th>
                        <th style="width: 40px;" data-name="none" data-template="#lwSelectMultipleContactsCheckbox"><i class="far fa-check-square"></i></th>
                        <th data-orderable="true" data-name="first_name">{{ __tr('First Name') }}</th>
                        <th data-orderable="true" data-name="last_name">{{ __tr('Last Name') }}</th>
                        <th data-orderable="true" data-name="phone_number">{{ __tr('Mobile Number') }}</th>
                        <th style="width: 70px;" data-orderable="true" data-name="language_code">{{ __tr('Langue') }}</th>
                        <th data-orderable="true" data-name="created_at">{{ __tr('Created on') }}</th>
                        <th data-name="country_name">{{ __tr('Country') }}</th>
                        <th data-orderable="true" data-name="email">{{ __tr('Email') }}</th>
                        <th data-orderable="true" data-name="whatsapp_opt_out">{{ __tr('Marketing') }}</th>
                        <th data-name="groups">{{ __tr('Groups') }}</th>
                        @if (isAiBotAvailable())
                        <th data-orderable="true" data-name="disable_ai_bot">{{ __tr('AI Bot') }}</th>
                        @endif
                        <th style="width: 130px;" data-template="#contactActionColumnTemplate" name="null" class="text-right">{{ __tr('Action') }}</th>
                    </x-lw.datatable>
                </div>
            </div>

            <!-- Assign Groups to the selected contacts -->
            <x-lw.modal id="lwAssignGroups" :header="__tr('Assign Groups to Selected Contacts')" :hasForm="true" data-pre-callback="appFuncs.clearContainer">
                <div class="lw-form-modal-body p-4">
                    <x-lw.input-field x-model="selectedGroupsForSelectedContacts" type="selectize" data-lw-plugin="lwSelectize" id="lwSelectGroupsField" data-form-group-class="" data-selected=" " :label="__tr('Groups')" name="contact_groups[]" multiple>
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('Select Groups') }}</option>
                            @foreach($vendorContactGroups as $vendorContactGroup)
                            <option value="{{ $vendorContactGroup['_id'] }}">{{ $vendorContactGroup['title'] }} {{ $vendorContactGroup['status'] == 5  ? __tr('(Archived)') : '' }}</option>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="assignGroupsToSelectedContacts" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
            </x-lw.modal>

            <!-- Assign Team Member Modal -->
            <x-lw.modal id="lwAssignTeamMember" :header="__tr('Assign Team Member')" :hasForm="true">
                <div id="lwAssignTeamMemberBody" class="lw-form-modal-body"></div>
                <script type="text/template" id="lwAssignTeamMemberBody-template">                    
                    <x-lw.form id="lwAssignTeamMemberForm" :action="route('vendor.chat.assign_user.process')" :data-callback-params="['modalId' => '#lwAssignTeamMember', 'datatableId' => '#lwContactList']" data-callback="appFuncs.modelSuccessCallback">
                        <div class="lw-form-modal-body p-3">
                            <% if(__tData.is_bulk_action) { %>
                                <input type="hidden" name="contactIdOrUid" :value="selectedContacts">
                                <input type="hidden" name="bulk_action" :value="true">
                            <% } else { %>
                                <input type="hidden" name="contactIdOrUid" value="<%= __tData.contact._uid %>">
                            <% } %>
                            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectTeamMemberField" :label="__tr('Select Team Member')" name="assigned_users_uid" data-selected="<%= __tData?.contact?.assigned_user?._uid %>">
                                <x-slot name="selectOptions">
                                    <option value="no_one">{{ __tr('Unassigned') }}</option>
                                    <% _.forEach(__tData.teamMembers, function(item) {%>
                                        <option value="<%= item._uid %>">
                                            <%= item.first_name %> <%= item.last_name %> <% if(item._uid == __tData.userUID) { %> ({{ __tr('You') }}) <% } %>
                                        </option>
                                    <% }); %>
                                </x-slot>
                            </x-lw.input-field>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                        </div>
                    </x-lw.form>
                </script>
            </x-lw.modal>

            <!-- Contact filter modal -->
            @include('contact.contact-filter')

            <!-- Add New Group Modal -->
            <x-lw.modal id="lwAddNewGroup" :header="__tr('Add New Group')" :hasForm="true">
                <x-lw.form id="lwAddNewGroupForm" :action="route('vendor.contact.group.write.create')" :data-callback-params="['modalId' => '#lwAddNewGroup', 'datatableId' => '#lwContactList']" data-callback="appFuncs.modelSuccessCallback">
                    <div class="lw-form-modal-body p-3">
                        <input type="hidden" name="request_from" value="CONTACT_ADVANCE_FILTER">
                        <x-lw.input-field type="text" id="lwTitleField" data-form-group-class="" :label="__tr('Title')" name="title" required="true" />
                        <div class="form-group">
                            <label for="lwDescriptionField" class="font-weight-bold small text-muted">{{ __tr('Description') }}</label>
                            <textarea cols="10" rows="3" id="lwDescriptionField" class="lw-form-field form-control" placeholder="{{ __tr('Description') }}" name="description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                    </div>
                </x-lw.form>
            </x-lw.modal>
        </div>

        <!-- Checkbox Template Large Clickable -->
        <script type="text/template" id="lwSelectMultipleContactsCheckbox">
            <div class="lw-checkbox-cell-wrapper" @click="toggle('<%- __tData._uid %>')">
                <input @click.stop="toggle('<%- __tData._uid %>')" type="checkbox" name="selected_contacts[]" class="lw-checkboxes custom-checkbox" value="<%- __tData._uid %>">
            </div>
        </script>

        <!-- Modern Action Column Template at the End of Row -->
        <script type="text/template" id="contactActionColumnTemplate">
            <div class="d-flex align-items-center justify-content-end lw-contact-action-group" style="gap: 4px;">
                @if(hasVendorAccess('messaging'))
                    <a data-pre-callback="appFuncs.clearContainer" title="{{ __tr('Chat') }}" class="btn btn-sm btn-primary rounded-pill font-weight-bold" href="<%= __Utils.apiURL("{{ route('vendor.chat_message.contact.view', ['contactUid']) }}",{'contactUid': __tData._uid}) %>">
                        <i class="fab fa-whatsapp mr-1"></i> {{ __tr('Chat') }}
                    </a>
                @endif

                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" type="button" data-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px; padding: 0;">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 rounded-lg">
                        <a data-pre-callback="appFuncs.clearContainer" class="dropdown-item text-dark lw-ajax-link-action" data-response-template="#lwDetailsContactBody" href="<%= __Utils.apiURL("{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" data-toggle="modal" data-target="#lwDetailsContact">
                            <i class="fas fa-info-circle text-info mr-2"></i> {{ __tr('Details') }}
                        </a>

                        @if(hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                            <a data-pre-callback="appFuncs.clearContainer" class="dropdown-item text-dark lw-ajax-link-action" data-response-template="#lwEditContactBody" href="<%= __Utils.apiURL("{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" data-toggle="modal" data-target="#lwEditContact">
                                <i class="fas fa-edit text-warning mr-2"></i> {{ __tr('Edit') }}
                            </a>
                        @endif

                        @if(hasVendorAccess('messaging'))
                            <a data-pre-callback="appFuncs.clearContainer" class="dropdown-item text-dark" href="<%= __Utils.apiURL("{{ route('vendor.template_message.contact.view', ['contactUid']) }}",{'contactUid': __tData._uid}) %>">
                                <i class="fas fa-paper-plane text-success mr-2"></i> {{ __tr('Send Template Message') }}
                            </a>
                            <a data-pre-callback="appFuncs.clearContainer" class="dropdown-item text-dark lw-ajax-link-action" data-response-template="#lwAssignTeamMemberBody" href="<%= __Utils.apiURL("{{ route('vendor.team_member.read.list', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" data-toggle="modal" data-target="#lwAssignTeamMember">
                                <i class="fas fa-user-check text-primary mr-2"></i> {{ __tr('Assign') }}
                            </a>
                        @endif

                        @if(hasVendorAccess('messaging'))
                            <% if (__tData.is_blocked) { %>
                                <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="dropdown-item text-danger lw-ajax-link-action-via-confirm" data-confirm="#lwUnblockContact-template" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback">
                                    <i class="fas fa-ban mr-2"></i> {{ __tr('WA Unblock') }}
                                </a>
                            <% } %>
                            <% if(!__tData.is_blocked && __tData.is_direct_message_delivery_window_opened) { %>
                                <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.block', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="dropdown-item text-danger lw-ajax-link-action-via-confirm" data-confirm="#lwBlockContact-template" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback">
                                    <i class="fas fa-ban mr-2"></i> {{ __tr('WA Block') }}
                                </a>
                            <% } %>
                        @endif

                        @if($currentGroup!=null)
                            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.remove',['contactIdOrUid', 'groupUid' => $groupUid]) }}",{ 'contactIdOrUid': __tData._uid }) %>" class="dropdown-item text-warning lw-ajax-link-action-via-confirm" data-confirm="#lwRemoveContact-template" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback">
                                <i class="fas fa-user-times mr-2"></i> {{ __tr('Remove') }}
                            </a>
                        @endif

                        @if(hasVendorAccess('manage_contacts', 'delete_contacts'))
                            <div class="dropdown-divider"></div>
                            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.delete', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="dropdown-item text-danger lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteContact-template" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback">
                                <i class="fas fa-trash mr-2"></i> {{ __tr('Delete') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </script>

        <script type="text/template" id="contactGroupsColumnTemplate">
            <%- __tData.groups %>
        </script>

        <!-- Delete & Action Confirm Templates -->
        <script type="text/template" id="lwDeleteContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to delete this Contact permanently?') }}</p>
        </script>
        <script type="text/template" id="lwRemoveContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to remove this Contact from this group?') }}</p>
        </script>
        <script type="text/template" id="lwBlockContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to block this Contact?') }}</p>
        </script>
        <script type="text/template" id="lwUnblockContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to unblock this Contact?') }}</p>
        </script>
        <script type="text/template" id="lwRemoveAllContactsFromGroup-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('All contacts in this group will be removed. Are you sure you want to proceed?') }}</p>
        </script>
        <script type="text/template" id="lwDeleteAllContacts-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('All your contacts will be deleted permanently, Are you sure you want to delete all your contacts??') }}</p>
        </script>
    </div>
</div>

<script>
(function() {
    'use strict';
        document.addEventListener('alpine:init', () => {
                Alpine.data('initialContactsInfoData', () => ({
                    existingImportRequestData: @json(getVendorSettings('contacts_import_process_data') ?: []),
            }));
        });
    })();
</script>
@push('appScripts')
<script>
(function($) {
    'use strict';    
    window.onUpdateContactDetails = function(responseData, callbackParams) {
        appFuncs.modelSuccessCallback(responseData, callbackParams);
    };
    window.onImportProcessUpdate = function(responseData, callbackParams) {
        if((responseData.reaction == 1) || (responseData === true)) {
            if(_.get(responseData, 'data.progressCount') === 0) {
                appFuncs.modelSuccessCallback(responseData, callbackParams);
            } else if(_.get(responseData, 'data.progressCount') || (responseData === true))  {
                __DataRequest.post('{{ route('vendor.contact.write.import') }}', {
                    'document_name': 'existing'
                }, function(responseData) {
                     _.delay(function() {
                         window.onImportProcessUpdate(responseData, callbackParams);
                    },30);
                });
            } else {
                appFuncs.modelSuccessCallback(responseData, callbackParams);
            }
        }
    };
    var existingImportRequestExist = {{ getVendorSettings('contacts_import_process_data') ? 1 : 0 }};
    if(existingImportRequestExist) {
        _.delay(function() {
            window.onImportProcessUpdate(true);
        },300);
    };
    $('#lwSelectLanguage').selectize({
        create: true,
        valueField: 'code',
        labelField: 'language',
        searchField: ['language', 'code']
    });

    window.clearFilter = function () {
        let filterForm = $('#lwContactFilterForm');
        let formData = filterForm.serializeArray();
        filterForm.append('<input type="hidden" name="clear_filter" value="true">');
        filterForm.submit();
    }
    
    // Row click selection
    $('#lwContactList tbody').on('click', 'tr', function(e) {
        if ($(e.target).closest('a, button, input, select, .dropdown-menu, label').length) {
            return;
        }
        var checkbox = $(this).find('input[type="checkbox"].lw-checkboxes');
        if (checkbox.length) {
            checkbox.trigger('click');
        }
    });

    // Datatable Search box French translation & placeholder
    $('#lwContactList').on('init.dt draw.dt', function() {
        var filterContainer = $(this).closest('.dataTables_wrapper').find('.dataTables_filter');
        if (filterContainer.length) {
            var label = filterContainer.find('label');
            if (label.length) {
                label.contents().filter(function() {
                    return this.nodeType === 3;
                }).replaceWith("{{ __tr('Rechercher :') }} ");
                var input = label.find('input');
                input.attr('placeholder', "{{ __tr('Nom, numéro, email...') }}");
            }
        }
    });

})(jQuery);
</script>
@endpush
@endsection()