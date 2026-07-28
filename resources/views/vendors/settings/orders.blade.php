@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
$orders = \App\Yantrana\Components\ECommerce\Models\OrderModel::with('contact')
    ->where('vendors__id', $vendorId)
    ->latest()
    ->get();
$contactsList = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->orderBy('first_name')->get();
$productsList = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)->orderBy('name')->get();
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

<script>
    window.INITIAL_ORDER_DATA = {
        allOrders: @json($orders),
        allContacts: @json($contactsList),
        allProducts: @json($productsList)
    };
</script>
<div class="container-fluid pb-5" x-data="{
    allOrders: window.INITIAL_ORDER_DATA.allOrders,
    allContacts: window.INITIAL_ORDER_DATA.allContacts,
    allProducts: window.INITIAL_ORDER_DATA.allProducts,
    orderSearch: '',
    orderStatusFilter: '',
    orderDateFilter: '',
    orderSourceFilter: '',
    orderDateSort: 'desc',
    selectedOrder: null,
    
    // Manual order form
    newOrderContactId: '',
    newOrderProductId: '',
    newOrderQuantity: 1,
    newOrderCustomPrice: '',
    newOrderAddress: '',
    newOrderDate: '',
    isSavingManualOrder: false,

    filteredOrders() {
        let result = this.allOrders.filter(o => {
            var contactName = o.contact ? (o.contact.first_name + ' ' + o.contact.last_name + ' ' + o.contact.wa_id) : '';
            var orderRef = o._uid ? o._uid : '';
            var matchesSearch = !this.orderSearch || contactName.toLowerCase().includes(this.orderSearch.toLowerCase()) || orderRef.toLowerCase().includes(this.orderSearch.toLowerCase());
            var matchesStatus = !this.orderStatusFilter || o.status === this.orderStatusFilter;
            
            var orderSource = o.order_details ? (o.order_details.source || '') : '';
            var matchesSource = !this.orderSourceFilter || orderSource.toLowerCase().includes(this.orderSourceFilter.toLowerCase());

            var matchesDate = true;
            if (this.orderDateFilter) {
                var orderCreatedStr = new Date(o.created_at).toISOString().split('T')[0];
                matchesDate = (orderCreatedStr === this.orderDateFilter);
            }
            return matchesSearch && matchesStatus && matchesSource && matchesDate;
        });

        return result.sort((a, b) => {
            var dateA = new Date(a.created_at || 0);
            var dateB = new Date(b.created_at || 0);
            return this.orderDateSort === 'asc' ? dateA - dateB : dateB - dateA;
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
            var isSuccess = response.reaction_code == 1 || (response.data && response.data.reaction_code == 1);
            var msg = response.message || (response.data && response.data.message) || 'Statut mis à jour avec succès.';
            if (isSuccess) {
                showSuccessMessage(msg);
                var ord = self.allOrders.find(o => o._uid === orderUid);
                if (ord) ord.status = newStatus;
            } else {
                showErrorMessage(msg || 'Erreur de mise à jour.');
            }
        });
    },
    deleteOrder(orderUid) {
        if (confirm('{{ __tr("Voulez-vous supprimer cette commande ?") }}')) {
            var self = this;
            __DataRequest.post('{{ route("vendor.ecommerce.orders.delete", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), {}, function(response) {
                var isSuccess = response.reaction_code == 1 || (response.data && response.data.reaction_code == 1);
                var msg = response.message || (response.data && response.data.message) || 'Commande supprimée avec succès.';
                if (isSuccess) {
                    showSuccessMessage(msg);
                    self.allOrders = self.allOrders.filter(o => o._uid !== orderUid);
                } else {
                    showErrorMessage(msg || 'Erreur de suppression.');
                }
            });
        }
    },
    viewOrderDetails(order) {
        this.selectedOrder = order;
        $('#orderDetailsModal').modal('show');
    },
    parseOrderItems(order) {
        if (!order || !order.order_details) return [];
        var details = order.order_details;
        if (typeof details === 'string') {
            try { details = JSON.parse(details); } catch(e) {}
        }
        if (details && details.items && Array.isArray(details.items)) {
            return details.items;
        }
        return [];
    },
    getOrderTotal(order) {
        if (!order || !order.order_details) return 0;
        var details = order.order_details;
        if (typeof details === 'string') {
            try { details = JSON.parse(details); } catch(e) {}
        }
        if (details && details.total_price) {
            return Number(details.total_price);
        }
        var items = this.parseOrderItems(order);
        var total = 0;
        items.forEach(i => { total += (Number(i.price) || 0) * (Number(i.quantity) || 1); });
        return total;
    },
    getOrderSourceLabel(order) {
        if (!order || !order.order_details) return 'WhatsApp';
        var details = order.order_details;
        if (typeof details === 'string') {
            try { details = JSON.parse(details); } catch(e) {}
        }
        return details.source || 'WhatsApp';
    },
    onProductSelectChange() {
        var prod = this.allProducts.find(p => p._id == this.newOrderProductId || p._uid == this.newOrderProductId);
        if (prod) {
            this.newOrderCustomPrice = prod.price;
        }
    },
    submitManualOrder() {
        if (this.isSavingManualOrder) return;
        if (!this.newOrderContactId || !this.newOrderProductId) {
            showErrorMessage('Veuillez sélectionner un client et un produit.');
            return;
        }
        this.isSavingManualOrder = true;
        var self = this;
        var postUrl = '<?= route("vendor.ecommerce.orders.create_manual") ?>';
        __DataRequest.post(postUrl, {
            contact_id: this.newOrderContactId,
            product_id: this.newOrderProductId,
            quantity: this.newOrderQuantity,
            custom_price: this.newOrderCustomPrice,
            delivery_address: this.newOrderAddress,
            delivery_date: this.newOrderDate
        }, function(response) {
            self.isSavingManualOrder = false;
            var isSuccess = response.reaction_code == 1 || (response.data && response.data.reaction_code == 1);
            if (isSuccess) {
                var msg = response.message || (response.data && response.data.message) || 'Commande enregistrée avec succès !';
                showSuccessMessage(msg);
                $('#createManualOrderModal').modal('hide');
                var newOrd = (response.data && response.data.order) ? response.data.order : response.order;
                if (newOrd) {
                    self.allOrders.unshift(newOrd);
                } else {
                    setTimeout(() => { window.location.reload(); }, 1000);
                }
            } else {
                var errMsg = response.message || (response.data && response.data.message) || 'Erreur lors de la création.';
                showErrorMessage(errMsg);
            }
        });
    },
    exportOrdersCSV() {
        var list = this.filteredOrders();
        if (list.length === 0) {
            showErrorMessage('Aucune commande à exporter.');
            return;
        }
        var csvRows = [];
        csvRows.push(['Reference', 'Date', 'Client', 'Telephone WhatsApp', 'Produits', 'Montant Total (CFA)', 'Statut', 'Source'].join(';'));

        list.forEach(o => {
            var ref = '#' + o._uid.substring(0, 8);
            var dateStr = new Date(o.created_at).toLocaleString('fr-FR');
            var clientName = o.contact ? (o.contact.first_name + ' ' + o.contact.last_name) : 'Inconnu';
            var phone = o.contact ? o.contact.wa_id : '';
            var items = this.parseOrderItems(o).map(i => (i.name || 'Produit') + ' (x' + (i.quantity||1) + ')').join(' | ');
            var total = this.getOrderTotal(o);
            var status = o.status;
            var source = this.getOrderSourceLabel(o);

            var row = [
                '\"' + ref + '\"',
                '\"' + dateStr + '\"',
                '\"' + clientName + '\"',
                '\"' + phone + '\"',
                '\"' + items + '\"',
                '\"' + total + '\"',
                '\"' + status + '\"',
                '\"' + source + '\"'
            ];
            csvRows.push(row.join(';'));
        });

        var csvString = '\uFEFF' + csvRows.join('\n');
        var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'export_commandes_' + (this.orderDateFilter || 'toutes') + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}">

    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('Gestion des Commandes WhatsApp') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Suivez, enregistrez, exportez vos rapports et imprimer vos reçus de commande') }}</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex align-items-center flex-wrap" style="gap: 10px;">
            <button type="button" @click="$('#createManualOrderModal').modal('show')" class="btn btn-emerald font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 10px;">
                <i class="fa fa-plus-circle mr-1"></i> {{ __tr('Enregistrer une Commande') }}
            </button>

            <button type="button" @click="exportOrdersCSV()" class="btn btn-outline-success font-weight-bold" style="border-radius: 10px; border-color: #10b981; color: #10b981;">
                <i class="fa fa-file-excel mr-1"></i> {{ __tr('Télécharger Excel / CSV') }}
            </button>

            <button type="button" onclick="window.print()" class="btn btn-outline-dark font-weight-bold" style="border-radius: 10px;">
                <i class="fa fa-print mr-1"></i> {{ __tr('Imprimer la Liste') }}
            </button>
        </div>
    </div>

    @if ($vendorPlanDetails['is_limit_available'])

    <!-- Top Key Metrics Cards -->
    <div class="row mb-4 no-print">
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
    <div class="card sharp-card mb-4" id="printableOrdersListArea">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 no-print">
            <h5 class="font-weight-bold text-dark mb-1"><i class="fa fa-list text-emerald mr-2"></i>{{ __tr('Liste Complète des Commandes') }}</h5>
            <p class="text-muted small mb-0">{{ __tr('Filtrez par date, source ou statut, et cliquez sur le reçu pour voir/imprimer la facture') }}</p>
        </div>

        <div class="card-body p-4">
            <!-- Search & Filters -->
            <div class="row mb-4 no-print">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Rechercher un client ou #Réf') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control p-3 custom-input-white" style="border-radius: 10px 0 0 10px !important;" placeholder="{{ __tr('Nom, numéro ou #Réf...') }}" x-model="orderSearch">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white" style="border: 2px solid #94a3b8; border-left: none; border-radius: 0 10px 10px 0;"><i class="fa fa-search text-muted"></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
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

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Filtrer par source') }}</label>
                    <select class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderSourceFilter">
                        <option value="">{{ __tr('Toutes les sources') }}</option>
                        <option value="whatsapp">{{ __tr('WhatsApp AI / Bot') }}</option>
                        <option value="manuel">{{ __tr('Vendeur Manuel') }}</option>
                        <option value="web">{{ __tr('Site Web / Webhook') }}</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Jour spécifique & Export') }}</label>
                    <div class="d-flex align-items-center" style="gap: 5px;">
                        <input type="date" class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderDateFilter">
                        <template x-if="orderDateFilter">
                            <button type="button" @click="orderDateFilter = ''" class="btn btn-sm btn-link text-danger p-0" title="{{ __tr('Effacer la date') }}">&times;</button>
                        </template>
                        <button type="button" @click="exportOrdersCSV()" class="btn btn-sm btn-success font-weight-bold shadow-sm" style="border-radius: 8px; padding: 6px 12px;" title="{{ __tr('Télécharger les commandes affichées au format Excel') }}">
                            <i class="fa fa-download"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Trier par date') }}</label>
                    <select class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderDateSort">
                        <option value="desc">{{ __tr('Du plus récent au plus ancien') }}</option>
                        <option value="asc">{{ __tr('Du plus ancien au plus récent') }}</option>
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
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Montant Total') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Source') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Statut Actuel') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;" class="text-right no-print">{{ __tr('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="order in filteredOrders()" :key="order._uid">
                            <tr>
                                <td class="align-middle">
                                    <button type="button" @click="viewOrderDetails(order)" class="btn btn-link p-0 font-weight-bold text-emerald text-left" style="color: #059669; text-decoration: underline;" title="{{ __tr('Cliquer pour voir le reçu') }}">
                                        <span x-text="'#' + order._uid.substring(0, 8)"></span>
                                    </button>
                                    <small class="text-muted d-block" x-text="new Date(order.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})"></small>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" x-text="order.contact ? (order.contact.first_name + ' ' + order.contact.last_name) : '{{ __tr('Client Inconnu') }}'"></div>
                                    <small class="text-emerald font-weight-bold" style="color: #059669;" x-text="order.contact ? order.contact.wa_id : ''"></small>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" style="font-size: 1.05rem;" x-text="getOrderTotal(order).toLocaleString() + ' CFA'"></div>
                                    <small class="text-muted" x-text="parseOrderItems(order).length + ' article(s)'"></small>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-light border px-2 py-1 font-weight-bold text-dark" style="border-radius: 8px;" x-text="getOrderSourceLabel(order)"></span>
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
                                <td class="align-middle text-right no-print">
                                    <div class="d-inline-flex align-items-center" style="gap: 8px;">
                                        <button type="button" @click="viewOrderDetails(order)" class="btn btn-sm btn-outline-emerald font-weight-bold" style="border-radius: 8px; color: #10b981; border-color: #10b981;" title="{{ __tr('Voir le reçu officiel') }}">
                                            <i class="fa fa-receipt mr-1"></i> {{ __tr('Reçu') }}
                                        </button>

                                        <template x-if="order.contact && order.contact._uid">
                                            <a :href="'<?= url('/vendor/contact/chat') ?>/' + order.contact._uid" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" title="{{ __tr('Ouvrir la conversation WhatsApp') }}">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </template>

                                        <select class="form-control form-control-sm font-weight-bold custom-input-white" style="border-radius: 8px !important; width: 130px;" :value="order.status" @change="updateOrderStatus(order._uid, $event.target.value)">
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
                    <p class="mb-0 font-weight-bold">{{ __tr('Aucune commande enregistrée pour le moment.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: CREATE MANUAL ORDER BY VENDOR -->
    <div class="modal fade" id="createManualOrderModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-emerald text-white p-4" style="background: #10b981;">
                    <h5 class="modal-title font-weight-bold text-white"><i class="fa fa-cart-plus mr-2"></i> {{ __tr('Enregistrer une Commande Client (Vendeur)') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 1.8rem;">&times;</span>
                    </button>
                </div>
                <form @submit.prevent="submitManualOrder()" class="p-4">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Sélectionner le Client WhatsApp *') }}</label>
                            <select class="form-control custom-input-white p-2" x-model="newOrderContactId" required>
                                <option value="">-- {{ __tr('Choisir un client') }} --</option>
                                <template x-for="c in allContacts" :key="c._id">
                                    <option :value="c._id" x-text="(c.first_name + ' ' + c.last_name + ' (' + c.wa_id + ')')"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Sélectionner le Produit du Catalogue *') }}</label>
                            <select class="form-control custom-input-white p-2" x-model="newOrderProductId" @change="onProductSelectChange()" required>
                                <option value="">-- {{ __tr('Choisir un produit') }} --</option>
                                <template x-for="p in allProducts" :key="p._id">
                                    <option :value="p._id" x-text="p.name + ' — ' + Number(p.price).toLocaleString() + ' CFA'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Quantité *') }}</label>
                            <input type="number" min="1" class="form-control custom-input-white p-3" x-model="newOrderQuantity" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Prix Unitaire (CFA) *') }}</label>
                            <input type="number" class="form-control custom-input-white p-3" x-model="newOrderCustomPrice" required placeholder="ex: 15000">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Adresse / Lieu de livraison') }}</label>
                            <input type="text" class="form-control custom-input-white p-3" x-model="newOrderAddress" placeholder="ex: Abidjan, Cocody Angré">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-dark">{{ __tr('Date de livraison souhaitée') }}</label>
                            <input type="date" class="form-control custom-input-white p-3" x-model="newOrderDate">
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0 border-top mt-3 pt-3">
                        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;">{{ __tr('Annuler') }}</button>
                        <button type="submit" class="btn btn-emerald font-weight-bold px-4 text-white" style="background: #10b981; border: none; border-radius: 8px;" :disabled="isSavingManualOrder">
                            <span x-show="!isSavingManualOrder"><i class="fa fa-save mr-1"></i> {{ __tr('Valider & Créer la Commande') }}</span>
                            <span x-show="isSavingManualOrder"><i class="fa fa-spinner fa-spin mr-1"></i> {{ __tr('Enregistrement...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: ORDER DETAILS & PRINTABLE RECEIPT MODAL -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <!-- Modal Header -->
                <div class="modal-header bg-emerald text-white p-4" style="background: #10b981;">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div>
                            <h5 class="modal-title font-weight-bold mb-1 text-white" x-text="'🧾 Reçu de Commande #' + (selectedOrder ? selectedOrder._uid.substring(0, 8) : '')"></h5>
                            <span class="badge badge-light font-weight-bold px-3 py-1" style="border-radius: 12px;" x-text="selectedOrder ? new Date(selectedOrder.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'}) : ''"></span>
                        </div>
                        <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true" style="font-size: 1.8rem; color: #ffffff;">&times;</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Body (Printable Invoice Area) -->
                <div class="modal-body p-4" id="printableInvoiceArea">
                    <!-- Actions Toolbar (Hidden during print) -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 p-3 rounded no-print" style="background: #f8fafc; border: 1.5px solid #cbd5e1;">
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <button type="button" onclick="window.print()" class="btn btn-emerald font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 8px;">
                                <i class="fa fa-print mr-1"></i> {{ __tr('Imprimer le reçu') }}
                            </button>
                            
                            <template x-if="selectedOrder && selectedOrder.contact">
                                <a :href="'<?= url('/vendor/contact/chat') ?>/' + selectedOrder.contact._uid" target="_blank" class="btn btn-outline-emerald font-weight-bold" style="border-radius: 8px; color: #10b981; border-color: #10b981;">
                                    <i class="fab fa-whatsapp mr-1"></i> {{ __tr('Voir la conversation Chat') }}
                                </a>
                            </template>
                        </div>

                        <div x-show="selectedOrder">
                            <span class="order-status-badge text-white"
                                  :class="{
                                      'bg-success': selectedOrder && selectedOrder.status === 'delivered',
                                      'bg-info': selectedOrder && (selectedOrder.status === 'shipped' || selectedOrder.status === 'processing'),
                                      'bg-primary': selectedOrder && selectedOrder.status === 'confirmed',
                                      'bg-warning text-dark': selectedOrder && selectedOrder.status === 'validated',
                                      'bg-danger': selectedOrder && selectedOrder.status === 'cancelled'
                                  }"
                                  x-text="selectedOrder ? (selectedOrder.status === 'delivered' ? 'Livrée' : (selectedOrder.status === 'shipped' ? 'En livraison' : (selectedOrder.status === 'confirmed' ? 'Confirmée' : (selectedOrder.status === 'cancelled' ? 'Annulée' : 'Nouvelle')))) : ''">
                            </span>
                        </div>
                    </div>

                    <!-- Receipt Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-user text-emerald mr-1"></i> {{ __tr('Informations Client') }}</h6>
                                <h6 class="font-weight-bold text-dark mb-1" x-text="selectedOrder && selectedOrder.contact ? (selectedOrder.contact.first_name + ' ' + selectedOrder.contact.last_name) : '{{ __tr('Client Inconnu') }}'"></h6>
                                <p class="text-emerald font-weight-bold mb-1" style="color: #059669;" x-text="selectedOrder && selectedOrder.contact ? '📱 WhatsApp: ' + selectedOrder.contact.wa_id : ''"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-info-circle text-emerald mr-1"></i> {{ __tr('Détails de Commande') }}</h6>
                                <p class="small text-dark mb-1"><strong>{{ __tr('Référence:') }}</strong> <span x-text="selectedOrder ? '#' + selectedOrder._uid.substring(0, 8) : ''"></span></p>
                                <p class="small text-dark mb-1"><strong>{{ __tr('Source:') }}</strong> <span x-text="getOrderSourceLabel(selectedOrder)"></span></p>
                                <p class="small text-dark mb-0"><strong>{{ __tr('Date de création:') }}</strong> <span x-text="selectedOrder ? new Date(selectedOrder.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'}) : ''"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Formatted Products Table (NO RAW JSON!) -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered mb-0" style="border-radius: 10px; overflow: hidden; border: 1.5px solid #cbd5e1;">
                            <thead class="bg-light text-uppercase small font-weight-bold text-dark">
                                <tr>
                                    <th>{{ __tr('Article / Produit') }}</th>
                                    <th class="text-center" style="width: 100px;">{{ __tr('Quantité') }}</th>
                                    <th class="text-right" style="width: 140px;">{{ __tr('Prix Unitaire') }}</th>
                                    <th class="text-right" style="width: 160px;">{{ __tr('Sous-Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in parseOrderItems(selectedOrder)" :key="idx">
                                    <tr>
                                        <td class="align-middle font-weight-bold text-dark" x-text="item.name || item.title || 'Produit'"></td>
                                        <td class="align-middle text-center font-weight-bold" x-text="'x' + (item.quantity || 1)"></td>
                                        <td class="align-middle text-right" x-text="Number(item.price || 0).toLocaleString() + ' CFA'"></td>
                                        <td class="align-middle text-right font-weight-bold text-dark" x-text="(Number(item.price || 0) * Number(item.quantity || 1)).toLocaleString() + ' CFA'"></td>
                                    </tr>
                                </template>
                                <template x-if="parseOrderItems(selectedOrder).length === 0">
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">
                                            {{ __tr('Détails des articles enregistrés.') }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot style="background: #ecfdf5;">
                                <tr>
                                    <td colspan="3" class="text-right font-weight-bold text-uppercase text-dark" style="font-size: 1.05rem;">
                                        💰 {{ __tr('Montant Total à Payer:') }}
                                    </td>
                                    <td class="text-right font-weight-bold text-emerald" style="font-size: 1.2rem; color: #059669;" x-text="getOrderTotal(selectedOrder).toLocaleString() + ' CFA'">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Contact Notes / Customer Note -->
                    <div x-show="selectedOrder && selectedOrder.contact && selectedOrder.contact.contact_notes" class="p-3 rounded mb-2" style="background: #f8fafc; border: 1.5px dashed #cbd5e1;">
                        <h6 class="font-weight-bold text-muted small mb-1"><i class="fa fa-sticky-note text-warning mr-1"></i> {{ __tr('Historique & Notes Client') }}</h6>
                        <pre class="small text-dark mb-0" style="white-space: pre-wrap; font-family: inherit;" x-text="selectedOrder && selectedOrder.contact ? selectedOrder.contact.contact_notes : ''"></pre>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light no-print">
                    <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;">
                        {{ __tr('Fermer') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINT STYLESHEET -->
    <style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printableInvoiceArea, #printableInvoiceArea *,
        #printableOrdersListArea, #printableOrdersListArea * {
            visibility: visible;
        }
        #printableInvoiceArea, #printableOrdersListArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .modal {
            position: absolute;
            left: 0;
            top: 0;
            margin: 0;
            padding: 0;
            overflow: visible;
        }
        .modal-dialog {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
        }
        .modal-content {
            border: none !important;
            box-shadow: none !important;
        }
    }
    </style>

    @else
    <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <i class="fa fa-lock mr-2"></i> {{ __tr('La gestion des commandes n\'est pas incluse dans votre formule d\'abonnement actuelle. Veuillez mettre à niveau votre compte.') }}
    </div>
    @endif

</div>

