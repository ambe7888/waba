@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
$orders = \App\Yantrana\Components\ECommerce\Models\OrderModel::with('contact')
    ->where('vendors__id', $vendorId)
    ->latest()
    ->get();
@endphp

<style>
.sharp-card {
    border: 2px solid #cbd5e1 !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
}
.order-status-badge {
    font-size: 0.78rem;
    padding: 0.4rem 0.85rem;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.custom-input-white {
    background: #ffffff !important;
    color: #0f172a !important;
    border: 2px solid #94a3b8 !important;
    border-radius: 10px !important;
}
.custom-input-white:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3.5px rgba(16, 185, 129, 0.25) !important;
}
</style>

<div class="container-fluid pb-5" x-data="{
    allOrders: {{ json_encode($orders) }},
    orderSearch: '',
    orderStatusFilter: '',
    selectedOrder: null,
    filteredOrders() {
        return this.allOrders.filter(o => {
            var contactName = o.contact ? (o.contact.first_name + ' ' + o.contact.last_name + ' ' + o.contact.wa_id) : '';
            var matchesSearch = !this.orderSearch || contactName.toLowerCase().includes(this.orderSearch.toLowerCase()) || (o._uid && o._uid.toLowerCase().includes(this.orderSearch.toLowerCase()));
            var matchesStatus = !this.orderStatusFilter || o.status === this.orderStatusFilter;
            return matchesSearch && matchesStatus;
        });
    },
    countByStatus(status) {
        if (!status) return this.allOrders.length;
        if (status === 'in_progress') return this.allOrders.filter(o => o.status === 'processing' || o.status === 'shipped').length;
        return this.allOrders.filter(o => o.status === status).length;
    },
    updateOrderStatus(orderUid, newStatus) {
        var self = this;
        __DataRequest.post('{{ route("vendor.ecommerce.orders.update_status", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), { status: newStatus }, function(response) {
            if (response.reaction_code == 1) {
                showSuccessMessage(response.message);
                var ord = self.allOrders.find(o => o._uid === orderUid);
                if (ord) ord.status = newStatus;
            } else {
                showErrorMessage(response.message || 'Erreur de mise à jour.');
            }
        });
    },
    deleteOrder(orderUid) {
        if (confirm('{{ __tr("Voulez-vous supprimer cette commande ?") }}')) {
            var self = this;
            __DataRequest.post('{{ route("vendor.ecommerce.orders.delete", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), {}, function(response) {
                if (response.reaction_code == 1) {
                    showSuccessMessage(response.message);
                    self.allOrders = self.allOrders.filter(o => o._uid !== orderUid);
                } else {
                    showErrorMessage(response.message || 'Erreur de suppression.');
                }
            });
        }
    },
    createTestOrder() {
        var self = this;
        __DataRequest.post('{{ route("vendor.ecommerce.test_order") }}', {}, function(response) {
            if (response.reaction_code == 1) {
                showSuccessMessage(response.message);
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                showErrorMessage(response.message || 'Erreur lors de la création.');
            }
        });
    },
    viewOrderDetails(order) {
        this.selectedOrder = order;
        $('#orderDetailsModal').modal('show');
    }
}">

    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('Gestion des Commandes WhatsApp') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Suivez, mettez à jour et gérez l\'ensemble des commandes reçues depuis votre catalogue WhatsApp') }}</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex" style="gap: 8px;">
            <button type="button" @click="createTestOrder()" class="btn btn-warning font-weight-bold px-3 py-2 text-dark shadow-sm" style="border-radius: 10px;">
                <i class="fa fa-vial mr-1"></i> {{ __tr('Tester une commande') }}
            </button>
            <a href="<?= route('vendor.settings.read', ['pageType' => 'ecommerce']) ?>" class="btn btn-outline-emerald font-weight-bold px-4 py-2" style="border-radius: 10px; color: #10b981; border-color: #10b981;">
                <i class="fa fa-boxes mr-1"></i> {{ __tr('Gérer le Catalogue') }}
            </a>
        </div>
    </div>

    @if ($vendorPlanDetails['is_limit_available'])

    <!-- Top Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Total Commandes') }}</small>
                        <h3 class="font-weight-bold text-dark mb-0" x-text="countByStatus('')"></h3>
                    </div>
                    <div class="icon-circle text-primary p-3 rounded-circle" style="background: #eff6ff;">
                        <i class="fa fa-shopping-bag fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Nouvelles (Validées)') }}</small>
                        <h3 class="font-weight-bold text-dark mb-0" x-text="countByStatus('validated')"></h3>
                    </div>
                    <div class="icon-circle text-warning p-3 rounded-circle" style="background: #fffbeb;">
                        <i class="fa fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('En Cours / Livraison') }}</small>
                        <h3 class="font-weight-bold text-dark mb-0" x-text="countByStatus('in_progress')"></h3>
                    </div>
                    <div class="icon-circle text-info p-3 rounded-circle" style="background: #f0f9ff;">
                        <i class="fa fa-truck fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Commandes Livrées') }}</small>
                        <h3 class="font-weight-bold text-emerald mb-0" style="color: #10b981;" x-text="countByStatus('delivered')"></h3>
                    </div>
                    <div class="icon-circle text-emerald p-3 rounded-circle" style="background: #ecfdf5; color: #10b981;">
                        <i class="fa fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN ORDERS TABLE CARD -->
    <div class="card sharp-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h5 class="font-weight-bold text-dark mb-1"><i class="fa fa-list text-emerald mr-2"></i>{{ __tr('Liste Complète des Commandes') }}</h5>
            <p class="text-muted small mb-0">{{ __tr('Filtrez vos commandes et cliquez sur le statut pour le modifier instantanément') }}</p>
        </div>

        <div class="card-body p-4">
            <!-- Search & Filters -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Rechercher un client, numéro ou ID commande') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control p-3 custom-input-white" style="border-radius: 10px 0 0 10px !important;" placeholder="{{ __tr('Nom, numéro WhatsApp ou #Réf...') }}" x-model="orderSearch">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white" style="border: 2px solid #94a3b8; border-left: none; border-radius: 0 10px 10px 0;"><i class="fa fa-search text-muted"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Filtrer par statut') }}</label>
                    <select class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderStatusFilter">
                        <option value="">{{ __tr('Tous les statuts') }}</option>
                        <option value="validated">{{ __tr('Nouvelle / Validée') }}</option>
                        <option value="confirmed">{{ __tr('Confirmée') }}</option>
                        <option value="processing">{{ __tr('En préparation') }}</option>
                        <option value="shipped">{{ __tr('En livraison') }}</option>
                        <option value="delivered">{{ __tr('Livrée') }}</option>
                        <option value="cancelled">{{ __tr('Annulée') }}</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0" style="border-radius: 12px; overflow: hidden; border: 1.5px solid #cbd5e1;">
                    <thead class="text-muted small text-uppercase" style="background: #f8fafc !important;">
                        <tr>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Réf / Date') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Client WhatsApp') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Détails de la commande') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Statut Actuel') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;" class="text-right">{{ __tr('Changer le statut / Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="order in filteredOrders()" :key="order._uid">
                            <tr>
                                <td class="align-middle">
                                    <span class="font-weight-bold text-dark small" x-text="'#' + order._uid.substring(0, 8)"></span>
                                    <small class="text-muted d-block" x-text="new Date(order.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})"></small>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" x-text="order.contact ? (order.contact.first_name + ' ' + order.contact.last_name) : '{{ __tr('Client Inconnu') }}'"></div>
                                    <small class="text-emerald" style="color: #059669;" x-text="order.contact ? order.contact.wa_id : ''"></small>
                                </td>
                                <td class="align-middle">
                                    <div class="text-sm font-weight-bold text-dark" x-text="order.order_details ? (order.order_details.catalog_id ? '{{ __tr('Commande via Catalogue WhatsApp') }}' : '{{ __tr('Commande Directe') }}') : '{{ __tr('Détails enregistrés') }}'"></div>
                                    <button type="button" @click="viewOrderDetails(order)" class="btn btn-sm btn-link p-0 text-emerald font-weight-bold" style="color: #10b981;">
                                        <i class="fa fa-eye mr-1"></i> {{ __tr('Voir le contenu') }}
                                    </button>
                                </td>
                                <td class="align-middle">
                                    <span class="order-status-badge text-white"
                                          :class="{
                                              'bg-success': order.status === 'delivered',
                                              'bg-info': order.status === 'shipped' || order.status === 'processing',
                                              'bg-primary': order.status === 'confirmed',
                                              'bg-warning text-dark': order.status === 'validated',
                                              'bg-danger': order.status === 'cancelled'
                                          }"
                                          x-text="order.status === 'delivered' ? '{{ __tr('Livrée') }}' : (order.status === 'shipped' ? '{{ __tr('En livraison') }}' : (order.status === 'confirmed' ? '{{ __tr('Confirmée') }}' : (order.status === 'cancelled' ? '{{ __tr('Annulée') }}' : '{{ __tr('Nouvelle') }}')))">
                                    </span>
                                </td>
                                <td class="align-middle text-right">
                                    <div class="d-inline-flex align-items-center" style="gap: 8px;">
                                        <select class="form-control form-control-sm font-weight-bold custom-input-white" style="border-radius: 8px !important; width: 140px;" :value="order.status" @change="updateOrderStatus(order._uid, $event.target.value)">
                                            <option value="validated">{{ __tr('Nouvelle') }}</option>
                                            <option value="confirmed">{{ __tr('Confirmer') }}</option>
                                            <option value="processing">{{ __tr('En préparation') }}</option>
                                            <option value="shipped">{{ __tr('En livraison') }}</option>
                                            <option value="delivered">{{ __tr('Livrée') }}</option>
                                            <option value="cancelled">{{ __tr('Annuler') }}</option>
                                        </select>
                                        
                                        <button type="button" @click="deleteOrder(order._uid)" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;" title="{{ __tr('Supprimer') }}">
                                            <i class="fa fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="filteredOrders().length === 0" class="text-center py-5 text-muted">
                    <i class="fa fa-shopping-basket fa-3x text-muted mb-3 d-block"></i>
                    <p class="mb-0 font-weight-bold">{{ __tr('Aucune commande enregistrée.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDER DETAILS MODAL -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow" style="border-radius: 16px; border: 2px solid #cbd5e1;">
                <div class="modal-header border-bottom">
                    <h5 class="modal-header-title font-weight-bold text-dark">
                        <i class="fa fa-file-invoice text-emerald mr-2"></i> {{ __tr('Détails de la Commande') }} <span x-text="selectedOrder ? ('#' + selectedOrder._uid.substring(0, 8)) : ''"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4" x-show="selectedOrder">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <small class="text-muted font-weight-bold text-uppercase d-block mb-1">{{ __tr('Client') }}</small>
                            <h6 class="font-weight-bold text-dark mb-0" x-text="selectedOrder && selectedOrder.contact ? (selectedOrder.contact.first_name + ' ' + selectedOrder.contact.last_name) : '{{ __tr('Inconnu') }}'"></h6>
                            <div class="text-emerald font-weight-bold small" style="color: #10b981;" x-text="selectedOrder && selectedOrder.contact ? selectedOrder.contact.wa_id : ''"></div>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <small class="text-muted font-weight-bold text-uppercase d-block mb-1">{{ __tr('Date de commande') }}</small>
                            <div class="font-weight-bold text-dark" x-text="selectedOrder ? new Date(selectedOrder.created_at).toLocaleString('fr-FR') : ''"></div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4" style="background: #f8fafc; border: 1.5px solid #cbd5e1 !important; border-radius: 12px;">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="fa fa-shopping-cart text-primary mr-1"></i> {{ __tr('Données transmises par WhatsApp :') }}</h6>
                        <pre class="mb-0 text-dark small" style="white-space: pre-wrap; font-family: monospace;" x-text="selectedOrder ? JSON.stringify(selectedOrder.order_details, null, 2) : ''"></pre>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary font-weight-bold px-4" style="border-radius: 8px;" data-dismiss="modal">{{ __tr('Fermer') }}</button>
                </div>
            </div>
        </div>
    </div>

    @else
    <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <i class="fa fa-lock mr-2"></i> {{ __tr('La gestion des commandes n\'est pas incluse dans votre formule d\'abonnement actuelle. Veuillez mettre à niveau votre compte.') }}
    </div>
    @endif

</div>
