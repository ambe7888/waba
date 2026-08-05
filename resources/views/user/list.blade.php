@php
/**
* Component : User
* Controller : UserController
* File : User.list.blade.php
* ----------------------------------------------------------------------------- */
@endphp
@extends('layouts.app', ['title' => __tr('Membres de l\'Équipe')])
@section('content')
@include('users.partials.header', [
    'title' => __tr('Membres de l\'Équipe'),
    'description' => __tr('Gérez les membres de votre équipe et configurez leurs permissions d\'accès.'),
    'class' => 'col-lg-7'
])
<div class="container-fluid pt-4 pb-4">
    <div class="row">
        <!-- Button Action Header -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <button type="button" class="btn btn-success font-weight-bold text-white shadow-sm" style="border-radius: 8px; background: #10b981; border: none;" data-toggle="modal" data-target="#lwAddNewUser">
                    <i class="fas fa-user-plus mr-1"></i> {{ __tr('Ajouter un Membre') }}
                </button>
            </div>
        </div>

        <!-- Add New User Modal -->
        <x-lw.modal id="lwAddNewUser" :header="__tr('Ajouter un Nouveau Membre')" :hasForm="true">
            <x-lw.form id="lwAddNewUserForm" :action="route('vendor.user.write.create')"
                :data-callback-params="['modalId' => '#lwAddNewUser', 'datatableId' => '#lwUserList']"
                data-callback="appFuncs.modelSuccessCallback">
                <div class="lw-form-modal-body">
                    <div class="form-row">
                        <div class="col-md-6">
                            <x-lw.input-field type="text" id="lwFirstNameField" :label="__tr('Prénom')" name="first_name" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-lw.input-field type="text" id="lwLastNameField" :label="__tr('Nom')" name="last_name" required="true" />
                        </div>
                    </div>
                    <x-lw.input-field type="number" id="lwMobileNumberField" :label="__tr('Numéro WhatsApp / Mobile')" name="mobile_number" required="true" minlength="9" />
                    <small class="text-muted d-block mb-3">{{__tr("Format international sans + ni 0 initial (ex: 33612345678)")}}</small>

                    <x-lw.input-field type="text" id="lwUsernameField" :label="__tr('Nom d\'utilisateur')" name="username" required="true" minlength="3" />
                    <x-lw.input-field type="text" id="lwEmailField" :label="__tr('Adresse E-mail')" name="email" required="true" minlength="3" />
                    <x-lw.input-field type="password" id="lwPasswordField" :label="__tr('Mot de Passe')" name="password" required="true" minlength="6" />

                    <fieldset class="border rounded p-3 mt-3">
                        <legend class="w-auto px-2 font-weight-bold text-dark" style="font-size: 0.95rem;">{{ __tr('Droits & Permissions') }}</legend>
                        @foreach (getListOfPermissions() as $permissionKey => $permission)
                        <div class="d-block my-3 p-2 border-bottom" x-data="{ isParentPermissionAllow: false }">
                            <x-lw.checkbox id="lw{{ $permissionKey }}Item" x-model="isParentPermissionAllow" name="permissions[{{ $permissionKey }}]"
                                data-lw-plugin="lwSwitchery" :label="$permission['title']" />
                            @if (isset($permission['description']) and $permission['description'])
                            <p class="text-muted mt-1 small">{{ $permission['description'] }}</p>

                            @if(!__isEmpty(data_get($permission, 'permissions')))
                                <div class="ml-4 pl-2 border-left border-success mt-2">
                                    @foreach($permission['permissions'] as $subPermissionKey => $subPermission)
                                        <div class="my-2">
                                            <x-lw.checkbox id="lw{{ $permissionKey }}{{ $subPermissionKey }}SubItemItem" data-size="small" x-bind:disabled="!isParentPermissionAllow" name="subPermissions[{{ $subPermissionKey }}]" data-lw-plugin="lwSwitchery" :label="$subPermission['title']" />
                                            @if (isset($subPermission['description']) and $subPermission['description'])
                                            <p class="text-muted mt-1 small ml-4">{{ $subPermission['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @endif
                        </div>
                        @endforeach
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success font-weight-bold" style="background: #10b981; border: none;">{{ __tr('Enregistrer le Membre') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Fermer') }}</button>
                </div>
            </x-lw.form>
        </x-lw.modal>

        <!-- Edit User Modal -->
        <x-lw.modal id="lwEditUser" :header="__tr('Modifier le Membre & Permissions')" :hasForm="true">
            <x-lw.form id="lwEditUserForm" :action="route('vendor.user.write.update')"
                :data-callback-params="['modalId' => '#lwEditUser', 'datatableId' => '#lwUserList']"
                data-callback="appFuncs.modelSuccessCallback">
                <div id="lwEditUserBody" class="lw-form-modal-body"></div>
                <script type="text/template" id="lwEditUserBody-template">
                    <input type="hidden" name="userIdOrUid" value="<%- __tData._uid %>" />
                    <div class="form-row">
                        <div class="col-md-6">
                            <x-lw.input-field type="text" id="lwFirstNameEditField" :label="__tr('Prénom')" value="<%- __tData.first_name %>" name="first_name" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-lw.input-field type="text" id="lwLastNameEditField" :label="__tr('Nom')" value="<%- __tData.last_name %>" name="last_name" required="true" />
                        </div>
                    </div>
            
                    @if(hasVendorAccess('hide_contact_emails') and getUserAuthInfo('role_id') !== 2)
                        <x-lw.input-field type="text" id="lwMobileNumberEditField" :label="__tr('Numéro WhatsApp')" value="<%- __tData.mobile_number %>" disabled name="mask_mobile_number" />
                    @else
                        <x-lw.input-field type="text" id="lwMobileNumberEditField" :label="__tr('Numéro WhatsApp')" value="<%- __tData.mobile_number %>" name="mobile_number" />
                    @endif

                    @if(hasVendorAccess('hide_contact_emails') and getUserAuthInfo('role_id') !== 2)
                        <x-lw.input-field type="text" disabled id="lwEmailEditField" :label="__tr('E-mail')" value="<%- __tData.email %>" name="mask_email" />
                    @else
                        <x-lw.input-field type="text" id="lwEmailEditField" :label="__tr('E-mail')" value="<%- __tData.email %>" name="email" />
                    @endif
            
                    <x-lw.input-field type="password" id="lwPasswordEditField" :label="__tr('Nouveau Mot de Passe (laisser vide si inchangé)')" name="password" />

                    <div class="form-group pt-2">
                        <label for="lwIsMemberActiveEditField" class="font-weight-bold text-dark">{{ __tr('Statut du Compte') }}</label>
                        <div>
                            <input type="checkbox" id="lwIsMemberActiveEditField" <%- __tData.status == 1 ? 'checked' : '' %> data-lw-plugin="lwSwitchery" name="status">
                        </div>
                    </div>

                    <fieldset class="border rounded p-3 mt-3">
                        <legend class="w-auto px-2 font-weight-bold text-dark" style="font-size: 0.95rem;">{{ __tr('Droits & Permissions') }}</legend>
                        @foreach(getListOfPermissions() as $permissionKey => $permission)
                        <% var mainPermission = __tData.vendor_user_details?.__data?.permissions?.{{ $permissionKey }}; %>
                            <div class="d-block my-3 p-2 border-bottom" x-data="{ isParentPermissionAllow: <%- __tData.vendor_user_details?.__data?.permissions?.{{ $permissionKey }} == 'allow' ? true : false %> }">
                                <label for="lwEdit{{ $permissionKey }}Permission" class="flex items-center mb-0 style-cursor">
                                    <input id="lwEdit{{ $permissionKey }}Permission" x-model="isParentPermissionAllow" type="checkbox" <%- (mainPermission == 'allow') ? 'checked' : '' %> name="permissions[{{ $permissionKey }}]" class="form-checkbox" data-lw-plugin="lwSwitchery">
                                    <span class="ml-2 font-weight-bold text-dark">{{ $permission['title'] }}</span>
                                </label>
                                @if (isset($permission['description']) and $permission['description'])
                                <p class="text-muted mt-1 small ml-4">{{ $permission['description'] }}</p>
                                @endif
                                @if(!__isEmpty(data_get($permission, 'permissions')))
                                    <div class="ml-4 pl-2 border-left border-success mt-2">
                                        @foreach($permission['permissions'] as $subPermissionKey => $subPermission)
                                            <?php $subPermissionName = $permissionKey.'@'.$subPermissionKey; ?>
                                            <% 
                                                var permissionData = __tData.vendor_user_details?.__data?.permissions;
                                                var subPermissionName = permissionData['{{ $subPermissionName }}'];
                                                var isChecked = ((_.isUndefined(subPermissionName) && mainPermission == 'allow') || (subPermissionName) == 'allow') ? 'checked' : '';
                                            %>
                                            <div class="my-2">
                                                <label for="lwEdit{{ $subPermissionKey }}SubPermission" class="flex items-center mb-0 style-cursor">
                                                    <input id="lwEdit{{ $subPermissionKey }}SubPermission" type="checkbox" <%- isChecked %> :disabled="!isParentPermissionAllow" data-size="small" name="subPermissions[{{ $subPermissionKey }}]" class="form-checkbox" data-lw-plugin="lwSwitchery">
                                                    <span class="ml-2 text-dark font-weight-normal">{{ $subPermission['title'] }}</span>
                                                </label>
                                                @if (isset($subPermission['description']) and $subPermission['description'])
                                                    <p class="text-muted mt-1 small ml-4">{{ $subPermission['description'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </fieldset>
                </script>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success font-weight-bold" style="background: #10b981; border: none;">{{ __tr('Mettre à jour') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Fermer') }}</button>
                </div>
            </x-lw.form>
        </x-lw.modal>

        <!-- DataTable Members -->
        <div class="col-xl-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header border-0 bg-white py-3">
                    <h3 class="mb-0 font-weight-bold text-dark">
                        <i class="fas fa-users text-success mr-2"></i>{{ __tr('Membres de l\'Équipe') }}
                    </h3>
                </div>
                <div class="card-body p-0">
                    <x-lw.datatable id="lwUserList" :url="route('vendor.user.read.list')">
                        <th data-orderable="true" data-name="first_name" style="border-top: none; font-weight: 700;">{{ __tr('Prénom') }}</th>
                        <th data-orderable="true" data-name="last_name" style="border-top: none; font-weight: 700;">{{ __tr('Nom') }}</th>
                        <th data-orderable="true" data-name="username" style="border-top: none; font-weight: 700;">{{ __tr('Nom d\'utilisateur') }}</th>
                        <th data-orderable="true" data-name="email" style="border-top: none; font-weight: 700;">{{ __tr('E-mail') }}</th>
                        <th data-orderable="true" data-name="mobile_number" style="border-top: none; font-weight: 700;">{{ __tr('WhatsApp') }}</th>
                        <th data-orderable="true" data-name="created_at" style="border-top: none; font-weight: 700;">{{ __tr('Créé le') }}</th>
                        <th data-orderable="true" data-name="status" style="border-top: none; font-weight: 700;">{{ __tr('Statut') }}</th>
                        <th data-template="#userActionColumnTemplate" name="null" style="border-top: none; font-weight: 700;" class="text-right">{{ __tr('Actions') }}</th>
                    </x-lw.datatable>
                </div>
            </div>
        </div>

        <!-- Action Template -->
        <script type="text/template" id="userActionColumnTemplate">
            <div class="text-right d-flex align-items-center justify-content-end" style="gap: 5px;">
                <a data-pre-callback="appFuncs.clearContainer" title="{!! __tr('Modifier & Permissions') !!}" class="btn btn-outline-primary btn-sm font-weight-bold lw-ajax-link-action" style="border-radius: 6px;" data-response-template="#lwEditUserBody" href="<%= __Utils.apiURL("{{ route('vendor.user.read.update.data', [ 'userIdOrUid']) }}", {'userIdOrUid': __tData._uid}) %>" data-toggle="modal" data-target="#lwEditUser">
                    <i class="fas fa-user-edit mr-1"></i> {!! __tr('Permissions') !!}
                </a>

                <% if(__tData.status=='Active') { %>
                    <% if(__tData._uid != '{{ getUserUid() }}') { %>
                        <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.user.write.login_as', [ 'userIdOrUid']) }}", {'userIdOrUid': __tData._uid}) %>" class="btn btn-success text-white btn-sm font-weight-bold lw-ajax-link-action" style="border-radius: 6px; background: #10b981; border: none;" data-confirm="#lwLoginAs-template" title="{{ __tr('Se connecter en tant que') }}">
                            <i class="fas fa-sign-in-alt mr-1"></i> {{ __tr('Incarner') }}
                        </a>
                    <% } %>
                <% } %>

                <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.user.write.delete', [ 'userIdOrUid']) }}", {'userIdOrUid': __tData._uid}) %>" class="btn btn-outline-danger btn-sm font-weight-bold lw-ajax-link-action" style="border-radius: 6px;" data-confirm="#lwDeleteUser-template" title="{{ __tr('Supprimer') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwUserList']) }}" data-callback="appFuncs.modelSuccessCallback">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        </script>

        <!-- Delete & Login As Templates -->
        <script type="text/template" id="lwDeleteUser-template">
            <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
            <p>{{ __tr('Voulez-vous vraiment supprimer ce membre d\'équipe ?') }}</p>
        </script>

        <script type="text/template" id="lwLoginAs-template">
            <h2>{{ __tr('Êtes-vous sûr ?') }}</h2>
            <p>{{ __tr('Voulez-vous vous connecter sous le compte de ce membre ?') }}</p>
        </script>
    </div>
</div>
@endsection()