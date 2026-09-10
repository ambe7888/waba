@extends('layouts.app', ['title' => __tr('Pipeline de Vente')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Pipeline de Vente'),
    'description' => __tr('Suivez vos opportunités commerciales, de prospect à client, en un coup d\'œil.'),
])

<style>
    .pipeline-board {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        align-items: flex-start;
    }
    .pipeline-column {
        background: #f8fafc;
        border-radius: 12px;
        width: 300px;
        min-width: 300px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 220px);
    }
    .pipeline-column-header {
        padding: 12px 14px;
        border-radius: 12px 12px 0 0;
        border-top: 4px solid #64748b;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 0.92rem;
    }
    .pipeline-column-body {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-height: 60px;
    }
    .pipeline-column-body.lw-drag-over {
        background: #ecfdf5;
    }
    .pipeline-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        cursor: grab;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .pipeline-card:active { cursor: grabbing; }
    .pipeline-card-title { font-weight: 600; font-size: 0.9rem; color: #0f172a; }
    .pipeline-card-contact { font-size: 0.78rem; color: #64748b; margin-top: 2px; }
    .pipeline-card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 6px; }
    .pipeline-card-value { font-size: 0.8rem; font-weight: 700; color: #10b981; }
    .pipeline-card-assignee { font-size: 0.72rem; color: #94a3b8; }
    .pipeline-add-column {
        background: transparent;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        width: 220px;
        min-width: 220px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60px;
    }
    .lw-contact-search-results {
        position: absolute;
        z-index: 20;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        width: 100%;
        max-height: 220px;
        overflow-y: auto;
    }
    .lw-contact-search-results .item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 0.88rem;
    }
    .lw-contact-search-results .item:hover { background: #f1f5f9; }
</style>

<div class="container-fluid pt-4 pb-5" x-data="pipelineBoard()" x-init="init()">

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm mr-2" @click="openStageModal()">
            <i class="fa fa-plus mr-1"></i> {{ __tr('Nouvelle étape') }}
        </button>
        <button type="button" class="btn btn-emerald text-white btn-sm" style="background:#10b981;" @click="openDealModal()">
            <i class="fa fa-plus mr-1"></i> {{ __tr('Nouvelle opportunité') }}
        </button>
    </div>

    <div class="pipeline-board">
        <template x-for="stage in stages" :key="stage._uid">
            <div class="pipeline-column">
                <div class="pipeline-column-header" :style="{ borderTopColor: stage.color }">
                    <span>
                        <span x-text="stage.title"></span>
                        <span class="badge badge-light ml-1" x-text="dealsFor(stage._id).length"></span>
                    </span>
                    <div class="dropdown">
                        <a href="#" class="text-muted" data-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="#" class="dropdown-item" @click.prevent="openStageModal(stage)">{{ __tr('Modifier') }}</a>
                            <a href="#" class="dropdown-item text-danger" @click.prevent="deleteStage(stage)">{{ __tr('Supprimer') }}</a>
                        </div>
                    </div>
                </div>
                <div class="pipeline-column-body"
                     @dragover.prevent="$el.classList.add('lw-drag-over')"
                     @dragleave="$el.classList.remove('lw-drag-over')"
                     @drop.prevent="$el.classList.remove('lw-drag-over'); onDrop(stage)">
                    <template x-for="deal in dealsFor(stage._id)" :key="deal._uid">
                        <div class="pipeline-card" draggable="true" @dragstart="draggingDealUid = deal._uid" @click="openDealModal(deal)">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="pipeline-card-title" x-text="deal.title"></div>
                                <a :href="chatUrl(deal)" @click.stop title="{{ __tr('Ouvrir la conversation') }}"
                                   x-show="deal.contact" class="text-muted ml-1" style="flex-shrink:0;">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                            <div class="pipeline-card-contact" x-text="contactLabel(deal)"></div>
                            <div class="pipeline-card-footer">
                                <span class="pipeline-card-value" x-text="formatValue(deal.value)"></span>
                                <span class="pipeline-card-assignee" x-text="assigneeLabel(deal)"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="dealsFor(stage._id).length === 0">
                        <div class="text-center text-muted small py-3">{{ __tr('Aucune opportunité') }}</div>
                    </template>
                </div>
            </div>
        </template>
        <div class="pipeline-add-column">
            <button type="button" class="btn btn-link text-muted" @click="openStageModal()">
                <i class="fa fa-plus mr-1"></i> {{ __tr('Ajouter une étape') }}
            </button>
        </div>
    </div>

    <!-- Deal Modal -->
    <div class="modal fade" id="lwDealModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingDeal._uid ? '{{ __tr('Modifier l\'opportunité') }}' : '{{ __tr('Nouvelle opportunité') }}'"></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group" style="position: relative;">
                        <label>{{ __tr('Contact') }} *</label>
                        <input type="text" class="form-control" x-model="contactSearchText" @input.debounce.300ms="searchContacts()" placeholder="{{ __tr('Rechercher un contact...') }}" autocomplete="off">
                        <div class="lw-contact-search-results" x-show="contactSearchResults.length > 0" @click.outside="contactSearchResults = []">
                            <template x-for="result in contactSearchResults" :key="result.value">
                                <div class="item" @click="selectContact(result)" x-text="result.text"></div>
                            </template>
                        </div>
                        <small class="text-muted" x-show="editingDeal.contactUid" x-text="'{{ __tr('Sélectionné') }} : ' + contactSearchText"></small>
                    </div>
                    <div class="form-group">
                        <label>{{ __tr('Titre de l\'opportunité') }} *</label>
                        <input type="text" class="form-control" x-model="editingDeal.title" placeholder="{{ __tr('ex: Commande 50 sacs de riz') }}">
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label>{{ __tr('Valeur estimée (FCFA)') }}</label>
                            <input type="number" class="form-control" x-model="editingDeal.value" min="0">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ __tr('Étape') }}</label>
                            <select class="form-control" x-model="editingDeal.stageUid">
                                <template x-for="stage in stages" :key="stage._uid">
                                    <option :value="stage._uid" x-text="stage.title"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __tr('Assigné à') }}</label>
                        <select class="form-control" x-model="editingDeal.assignedUserUid">
                            <option value="">{{ __tr('Non assigné') }}</option>
                            @foreach($vendorMessagingUsers as $user)
                                <option value="{{ $user->_uid }}">{{ $user->first_name . ' ' . $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __tr('Notes') }}</label>
                        <textarea class="form-control" rows="3" x-model="editingDeal.notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <template x-if="editingDeal._uid">
                        <button type="button" class="btn btn-outline-danger mr-auto" @click="deleteDeal(editingDeal)">{{ __tr('Supprimer') }}</button>
                    </template>
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ __tr('Annuler') }}</button>
                    <button type="button" class="btn btn-primary" :disabled="isSavingDeal" @click="saveDeal()">
                        <span x-show="!isSavingDeal">{{ __tr('Enregistrer') }}</span>
                        <span x-show="isSavingDeal"><i class="fa fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stage Modal -->
    <div class="modal fade" id="lwStageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingStage._uid ? '{{ __tr('Modifier l\'étape') }}' : '{{ __tr('Nouvelle étape') }}'"></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __tr('Nom de l\'étape') }} *</label>
                        <input type="text" class="form-control" x-model="editingStage.title" placeholder="{{ __tr('ex: Devis envoyé') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __tr('Couleur') }}</label>
                        <input type="color" class="form-control" style="max-width: 100px; height: 40px;" x-model="editingStage.color">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ __tr('Annuler') }}</button>
                    <button type="button" class="btn btn-primary" :disabled="isSavingStage" @click="saveStage()">
                        <span x-show="!isSavingStage">{{ __tr('Enregistrer') }}</span>
                        <span x-show="isSavingStage"><i class="fa fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function pipelineBoard() {
        return {
            stages: @json($stages),
            deals: @json($deals),
            draggingDealUid: null,
            contactSearchText: '',
            contactSearchResults: [],
            isSavingDeal: false,
            isSavingStage: false,
            editingDeal: {},
            editingStage: {},

            init() {
                var dealUid = new URLSearchParams(window.location.search).get('dealUid');
                if (dealUid) {
                    var deal = this.deals.find(d => d._uid === dealUid);
                    if (deal) {
                        this.$nextTick(() => this.openDealModal(deal));
                    }
                }
            },

            dealsFor(stageId) {
                return this.deals.filter(d => d.pipeline_stages__id === stageId);
            },

            contactLabel(deal) {
                if (!deal.contact) return '';
                return (deal.contact.first_name + ' ' + (deal.contact.last_name || '')).trim() + ' (+' + deal.contact.wa_id + ')';
            },

            chatUrl(deal) {
                if (!deal.contact) return '#';
                return '{{ route('vendor.chat_message.contact.view', ['contactUid' => 'CONTACT_UID']) }}'.replace('CONTACT_UID', deal.contact._uid);
            },

            assigneeLabel(deal) {
                if (!deal.assigned_user) return '';
                return deal.assigned_user.first_name;
            },

            formatValue(value) {
                var n = Number(value) || 0;
                return n.toLocaleString('fr-FR') + ' FCFA';
            },

            searchContacts() {
                var self = this;
                __DataRequest.get('{{ route('vendor.pipeline.contacts.search') }}', { q: this.contactSearchText }, function(response) {
                    self.contactSearchResults = Array.isArray(response) ? response : [];
                });
            },

            selectContact(result) {
                this.editingDeal.contactUid = result.value;
                this.contactSearchText = result.text;
                this.contactSearchResults = [];
            },

            openDealModal(deal) {
                if (deal && deal._uid) {
                    this.editingDeal = {
                        _uid: deal._uid,
                        contactUid: deal.contact ? deal.contact._uid : null,
                        title: deal.title,
                        value: deal.value,
                        notes: deal.notes,
                        stageUid: (this.stages.find(s => s._id === deal.pipeline_stages__id) || {})._uid,
                        assignedUserUid: deal.assigned_user ? deal.assigned_user._uid : '',
                    };
                    this.contactSearchText = this.contactLabel(deal);
                } else {
                    this.editingDeal = {
                        stageUid: this.stages.length ? this.stages[0]._uid : null,
                        assignedUserUid: '',
                        value: 0,
                    };
                    this.contactSearchText = '';
                }
                this.contactSearchResults = [];
                $('#lwDealModal').modal('show');
            },

            saveDeal() {
                if (!this.editingDeal.contactUid || !this.editingDeal.title || !this.editingDeal.stageUid) {
                    showErrorMessage('{{ __tr('Contact, titre et étape sont obligatoires.') }}');
                    return;
                }
                this.isSavingDeal = true;
                var self = this;
                var url = this.editingDeal._uid
                    ? '{{ route('vendor.pipeline.deals.write', ['dealUid' => 'DEAL_UID']) }}'.replace('DEAL_UID', this.editingDeal._uid)
                    : '{{ route('vendor.pipeline.deals.write') }}';
                __DataRequest.post(url, this.editingDeal, function(response) {
                    self.isSavingDeal = false;
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        showSuccessMessage(response.message || '{{ __tr('Opportunité enregistrée.') }}');
                        $('#lwDealModal').modal('hide');
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        showErrorMessage(response.message || '{{ __tr('Erreur lors de l\'enregistrement.') }}');
                    }
                });
            },

            deleteDeal(deal) {
                if (!confirm('{{ __tr('Voulez-vous vraiment supprimer cette opportunité ?') }}')) return;
                var url = '{{ route('vendor.pipeline.deals.delete', ['dealUid' => 'DEAL_UID']) }}'.replace('DEAL_UID', deal._uid);
                __DataRequest.post(url, {}, function(response) {
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        $('#lwDealModal').modal('hide');
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        showErrorMessage(response.message || '{{ __tr('Erreur.') }}');
                    }
                });
            },

            onDrop(stage) {
                if (!this.draggingDealUid) return;
                var dealUid = this.draggingDealUid;
                this.draggingDealUid = null;
                var deal = this.deals.find(d => d._uid === dealUid);
                if (!deal || deal.pipeline_stages__id === stage._id) return;

                var url = '{{ route('vendor.pipeline.deals.move', ['dealUid' => 'DEAL_UID']) }}'.replace('DEAL_UID', dealUid);
                var self = this;
                __DataRequest.post(url, { stageUid: stage._uid }, function(response) {
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        deal.pipeline_stages__id = stage._id;
                    } else {
                        showErrorMessage(response.message || '{{ __tr('Erreur lors du déplacement.') }}');
                    }
                });
            },

            openStageModal(stage) {
                if (stage && stage._uid) {
                    this.editingStage = { _uid: stage._uid, title: stage.title, color: stage.color };
                } else {
                    this.editingStage = { title: '', color: '#64748b' };
                }
                $('#lwStageModal').modal('show');
            },

            saveStage() {
                if (!this.editingStage.title) {
                    showErrorMessage('{{ __tr('Le nom de l\'étape est obligatoire.') }}');
                    return;
                }
                this.isSavingStage = true;
                var self = this;
                var url = this.editingStage._uid
                    ? '{{ route('vendor.pipeline.stages.update', ['stageUid' => 'STAGE_UID']) }}'.replace('STAGE_UID', this.editingStage._uid)
                    : '{{ route('vendor.pipeline.stages.write') }}';
                __DataRequest.post(url, this.editingStage, function(response) {
                    self.isSavingStage = false;
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        $('#lwStageModal').modal('hide');
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        showErrorMessage(response.message || '{{ __tr('Erreur.') }}');
                    }
                });
            },

            deleteStage(stage) {
                if (!confirm('{{ __tr('Voulez-vous vraiment supprimer cette étape ?') }}')) return;
                var url = '{{ route('vendor.pipeline.stages.delete', ['stageUid' => 'STAGE_UID']) }}'.replace('STAGE_UID', stage._uid);
                __DataRequest.post(url, {}, function(response) {
                    var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                    if (isSuccess) {
                        window.location.reload();
                    } else {
                        showErrorMessage(response.message || '{{ __tr('Erreur.') }}');
                    }
                });
            },
        };
    }
</script>
@endsection
