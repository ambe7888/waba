@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
$orders = \App\Yantrana\Components\ECommerce\Models\OrderModel::with('contact')
    ->where('vendors__id', $vendorId)
    ->latest()
    ->get();
$contactsList = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->orderBy('first_name')->get();
$productsList = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)->orderBy('name')->get();
$teamMembers = \DB::table('users')
    ->join('vendor_users', 'vendor_users.users__id', '=', 'users._id')
    ->where('vendor_users.vendors__id', $vendorId)
    ->select('users._id', 'users.first_name', 'users.last_name', 'users.username')
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

/* PERFECT CSS PRINT STYLES */
@media print {
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    /* Hide layout chrome & ALL other modals (including #lwScanMeDialog QR Code modal) */
    nav, header, sidebar, footer, .navbar, .sidebar, .lw-main-navbar, 
    #lwScanMeDialog, .modal:not(#orderDetailsModal),
    .no-print, .modal-backdrop, .modal-header .close, .modal-footer {
        display: none !important;
    }

    /* WHEN MODAL RECEIPT IS OPEN: Print ONLY #printableInvoiceArea inside #orderDetailsModal */
    body.modal-open #printableOrdersListArea,
    body.modal-open .card,
    body.modal-open .container-fluid > div:not(#orderDetailsModal) {
        display: none !important;
    }

    body.modal-open #orderDetailsModal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        overflow: visible !important;
        background: #ffffff !important;
        box-shadow: none !important;
        border: none !important;
    }

    body.modal-open #orderDetailsModal .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }

    body.modal-open #orderDetailsModal .modal-content {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #ffffff !important;
    }

    body.modal-open #printableInvoiceArea {
        display: block !important;
        padding: 10px !important;
        margin: 0 !important;
    }

    /* WHEN PRINTING MAIN ORDERS TABLE LIST (MODAL NOT OPEN): Print ONLY the table */
    body:not(.modal-open) #printableOrdersListArea {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    body:not(.modal-open) #printableOrdersListArea .card-body {
        padding: 0 !important;
    }
}
</style>

<div class="container-fluid pb-5" x-data="ordersPageData()">

    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('Gestion des Commandes WhatsApp') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Suivez, filtrez par date, agent ou statut, et imprimez les reçus individuels ou le rapport de liste complet') }}</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex align-items-center flex-wrap" style="gap: 10px;">
            @if (hasVendorAccess('manage_orders', 'add_edit_orders'))
            <button type="button" @click="$('#createManualOrderModal').modal('show')" class="btn btn-emerald font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 10px;">
                <i class="fa fa-plus-circle mr-1"></i> {{ __tr('Enregistrer une Commande') }}
            </button>
            @endif

            <button type="button" @click="exportOrdersCSV()" class="btn btn-outline-success font-weight-bold" style="border-radius: 10px; border-color: #10b981; color: #10b981;">
                <i class="fa fa-file-excel mr-1"></i> {{ __tr('Exporter Excel / CSV') }}
            </button>

            <button type="button" @click="printOrdersListOnly()" class="btn btn-outline-dark font-weight-bold" style="border-radius: 10px;">
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
                        <h3 class="font-weight-bold text-dark mb-0" x-text="allOrders.length"></h3>
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
                        <h3 class="font-weight-bold text-dark mb-0" x-text="allOrders.filter(o => o.status === 'validated').length"></h3>
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
                        <h3 class="font-weight-bold text-dark mb-0" x-text="allOrders.filter(o => o.status === 'processing' || o.status === 'shipped').length"></h3>
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
                        <h3 class="font-weight-bold text-emerald mb-0" style="color: #10b981;" x-text="allOrders.filter(o => o.status === 'delivered').length"></h3>
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
            <p class="text-muted small mb-0">{{ __tr('Filtrez par date, statut ou agent/source, puis cliquez sur le reçu pour voir les infos détaillées') }}</p>
        </div>

        <div class="card-body p-4">
            <!-- Search & Filters Row 1 -->
            <div class="row mb-3 no-print">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Rechercher Client / #Réf') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control p-3 custom-input-white" style="border-radius: 10px 0 0 10px !important;" placeholder="{{ __tr('Nom, tel ou #Réf...') }}" x-model="orderSearch">
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
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Agent / Source') }}</label>
                    <select class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderSourceFilter">
                        <option value="">{{ __tr('Toutes les sources & agents') }}</option>
                        <option value="whatsapp">🤖 {{ __tr('Bot / IA WhatsApp') }}</option>
                        <option value="manuel">👤 {{ __tr('Vendeur Manuel') }}</option>
                        <template x-for="u in teamMembers" :key="u._id">
                            <option :value="u.first_name + ' ' + u.last_name" x-text="'👨‍💼 Agent: ' + u.first_name + ' ' + u.last_name"></option>
                        </template>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark small mb-1">{{ __tr('Jour spécifique (Date)') }}</label>
                    <div class="d-flex align-items-center" style="gap: 5px;">
                        <input type="date" class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="orderDateFilter">
                        <template x-if="orderDateFilter">
                            <button type="button" @click="orderDateFilter = ''" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;" title="{{ __tr('Réinitialiser la date') }}">&times;</button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sort & Quick Filters Row 2 -->
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-4 pb-2 border-bottom no-print" style="gap: 10px;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <span class="small font-weight-bold text-muted">{{ __tr('Trier par date:') }}</span>
                    <select class="form-control form-control-sm custom-input-white font-weight-bold" style="border-radius: 8px !important; width: 200px;" x-model="orderDateSort">
                        <option value="desc">{{ __tr('Du plus récent au plus ancien') }}</option>
                        <option value="asc">{{ __tr('Du plus ancien au plus récent') }}</option>
                    </select>

                    <button type="button" @click="setTodayFilter()" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-calendar-day mr-1"></i> {{ __tr('Commandes du jour') }}
                    </button>
                </div>

                <div class="small font-weight-bold text-dark">
                    <span class="badge badge-emerald text-white px-2 py-1" style="background: #10b981; font-size: 0.85rem;" x-text="getFilteredOrders().length + ' commande(s) affichée(s)'"></span>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0" style="border-radius: 12px; overflow: hidden; border: 1.5px solid #cbd5e1;">
                    <thead class="text-muted small text-uppercase" style="background: #f8fafc !important;">
                        <tr>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Réf / Date') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Client WhatsApp') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Articles & Montant Total') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Source / Agent') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Statut Actuel') }}</th>
                            <th style="border-bottom: 2px solid #cbd5e1;" class="text-right no-print">{{ __tr('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="order in getFilteredOrders()" :key="order._uid">
                            <tr>
                                <td class="align-middle">
                                    <button type="button" @click="viewOrderDetails(order)" class="btn btn-link p-0 font-weight-bold text-emerald text-left" style="color: #059669; text-decoration: underline;" title="{{ __tr('Cliquer pour voir la fiche complète') }}">
                                        <span x-text="'#' + order._uid.substring(0, 8)"></span>
                                    </button>
                                    <small class="text-muted d-block" x-text="formatDate(order.created_at)"></small>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" x-text="order.contact ? (order.contact.first_name + ' ' + order.contact.last_name) : '{{ __tr('Client Inconnu') }}'"></div>
                                    <template x-if="order.contact && order.contact._uid">
                                        <a :href="getChatUrl(order.contact._uid)" target="_blank" class="text-emerald font-weight-bold small" style="color: #059669;" title="{{ __tr('Ouvrir la conversation WhatsApp') }}">
                                            <i class="fab fa-whatsapp mr-1"></i><span x-text="order.contact.wa_id"></span>
                                        </a>
                                    </template>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" style="font-size: 1.05rem;" x-text="getTotal(order).toLocaleString() + ' CFA'"></div>
                                    <div class="small text-dark font-weight-bold mt-1">
                                        <template x-for="(it, i) in getItems(order)" :key="i">
                                            <div class="text-truncate" style="max-width: 280px;" x-text="'• ' + (it.name || 'Produit') + ' (x' + (it.quantity || 1) + ')'"></div>
                                        </template>
                                        <template x-if="getItems(order).length === 0">
                                            <small class="text-muted italic">{{ __tr('Aucun article détaillé') }}</small>
                                        </template>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-light border px-2 py-1 font-weight-bold text-dark" style="border-radius: 8px;" x-text="getSource(order)"></span>
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
                                            <a :href="getChatUrl(order.contact._uid)" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" title="{{ __tr('Ouvrir la conversation WhatsApp') }}">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </template>

                                        @if (hasVendorAccess('manage_orders', 'add_edit_orders'))
                                        <select class="form-control form-control-sm font-weight-bold custom-input-white" style="border-radius: 8px !important; width: 130px;" :value="order.status" @change="updateOrderStatus(order._uid, $event.target.value)">
                                            <option value="validated">{{ __tr('Nouvelle') }}</option>
                                            <option value="confirmed">{{ __tr('Confirmer') }}</option>
                                            <option value="processing">{{ __tr('En préparation') }}</option>
                                            <option value="shipped">{{ __tr('En livraison') }}</option>
                                            <option value="delivered">{{ __tr('Livrée') }}</option>
                                            <option value="cancelled">{{ __tr('Annuler') }}</option>
                                        </select>
                                        @endif
                                        
                                        @if (hasVendorAccess('manage_orders', 'delete_orders'))
                                        <button type="button" @click="deleteOrder(order._uid)" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;" title="{{ __tr('Supprimer') }}">
                                            <i class="fa fa-trash-alt"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="getFilteredOrders().length === 0" class="text-center py-5 text-muted">
                    <i class="fa fa-shopping-basket fa-3x text-muted mb-3 d-block"></i>
                    <p class="mb-0 font-weight-bold">{{ __tr('Aucune commande ne correspond à votre recherche.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: CREATE MANUAL ORDER BY VENDOR -->
    <div class="modal fade" id="createManualOrderModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-emerald text-white p-4" style="background: #10b981;">
                    <h5 class="modal-title font-weight-bold text-white"><i class="fa fa-cart-plus mr-2"></i> {{ __tr('Enregistrer une Commande Client (Vendeur)') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 1.8rem;">&times;</span>
                    </button>
                </div>
                <form @submit.prevent="submitManualOrder()">
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">{{ __tr('Sélectionner le Client WhatsApp *') }}</label>
                            <select class="form-control custom-input-white p-2" x-model="newOrderContactId" required>
                                <option value="">-- {{ __tr('Choisir un client') }} --</option>
                                <template x-for="c in allContacts" :key="c._id">
                                    <option :value="c._id" x-text="(c.first_name + ' ' + c.last_name + ' (' + c.wa_id + ')')"></option>
                                </template>
                            </select>
                        </div>

                        <!-- PRODUCTS LIST (MULTI-PRODUCTS) -->
                        <div class="border rounded p-3 mb-3" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold text-dark mb-0">{{ __tr('Produit(s) de la commande *') }}</label>
                                <button type="button" @click="addOrderItem()" class="btn btn-sm btn-outline-success font-weight-bold" style="border-radius: 6px;">
                                    + {{ __tr('Ajouter un autre produit') }}
                                </button>
                            </div>

                            <template x-for="(item, idx) in newOrderItems" :key="idx">
                                <div class="row align-items-center bg-white p-2 mb-2 rounded border" style="border-color: #e2e8f0 !important;">
                                    <div class="col-md-5 form-group mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted mb-1">{{ __tr('Produit') }}</label>
                                        <select class="form-control form-control-sm" x-model="item.product_id" @change="onItemProductChange(idx)" required>
                                            <option value="">-- {{ __tr('Choisir un produit') }} --</option>
                                            <template x-for="p in allProducts" :key="p._id">
                                                <option :value="p._id" x-text="p.name + ' — ' + Number(p.price).toLocaleString() + ' CFA'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted mb-1">{{ __tr('Qté') }}</label>
                                        <input type="number" min="1" class="form-control form-control-sm" x-model="item.quantity" required>
                                    </div>
                                    <div class="col-md-4 form-group mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted mb-1">{{ __tr('Prix Unitaire (CFA)') }}</label>
                                        <input type="number" class="form-control form-control-sm" x-model="item.custom_price" placeholder="Prix" required>
                                    </div>
                                    <div class="col-md-1 text-right">
                                        <label class="small d-block mb-1">&nbsp;</label>
                                        <button type="button" @click="removeOrderItem(idx)" class="btn btn-sm btn-link text-danger p-0" title="Supprimer ce produit" x-show="newOrderItems.length > 1">
                                            <i class="fa fa-times-circle fa-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- ADDITIONAL FEES (FRAIS DE LIVRAISON / AUTRES) -->
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark">{{ __tr('Frais additionnels / Livraison (CFA)') }}</label>
                                <input type="number" min="0" class="form-control custom-input-white" x-model="newOrderAdditionalFee" placeholder="ex: 2000">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark">{{ __tr('Libellé des frais') }}</label>
                                <input type="text" class="form-control custom-input-white" x-model="newOrderAdditionalFeeLabel" placeholder="ex: Frais de livraison">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark">{{ __tr('Adresse / Lieu de livraison') }}</label>
                                <input type="text" class="form-control custom-input-white" x-model="newOrderAddress" placeholder="ex: Abidjan, Cocody Angré">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark">{{ __tr('Date de livraison souhaitée') }}</label>
                                <input type="date" class="form-control custom-input-white" x-model="newOrderDate">
                            </div>
                        </div>

                        <!-- TOTAL SUMMARY CARD -->
                        <div class="p-3 rounded" style="background: #ecfdf5; border: 1.5px solid #a7f3d0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-dark small font-weight-bold">{{ __tr('Sous-total produits:') }}</span>
                                <span class="font-weight-bold text-dark" x-text="getNewOrderSubtotal().toLocaleString() + ' CFA'"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="Number(newOrderAdditionalFee) > 0">
                                <span class="text-dark small font-weight-bold"><span x-text="newOrderAdditionalFeeLabel || 'Frais additionnels'"></span>:</span>
                                <span class="font-weight-bold text-dark" x-text="Number(newOrderAdditionalFee).toLocaleString() + ' CFA'"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                                <span class="font-weight-bold text-uppercase text-dark" style="font-size: 1.05rem;">{{ __tr('Total Commande:') }}</span>
                                <span class="font-weight-bold text-emerald" style="font-size: 1.25rem; color: #059669;" x-text="getNewOrderTotal().toLocaleString() + ' CFA'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light p-3" style="border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;">{{ __tr('Annuler') }}</button>
                        <button type="submit" class="btn btn-emerald font-weight-bold px-4 text-white" style="background: #10b981; border: none; border-radius: 8px;" :disabled="isSavingManualOrder">
                            <span x-show="!isSavingManualOrder">{{ __tr('Valider & Créer la Commande') }}</span>
                            <span x-show="isSavingManualOrder"><i class="fa fa-spinner fa-spin mr-1"></i> {{ __tr('Enregistrement...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: ORDER DETAILS & PRINTABLE RECEIPT MODAL (FICHE COMPLÈTE) -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                <!-- Modal Header -->
                <div class="modal-header bg-emerald text-white p-4" style="background: #10b981;">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div>
                            <h5 class="modal-title font-weight-bold mb-1 text-white" x-text="'🧾 Reçu & Fiche de Commande #' + (selectedOrder ? selectedOrder._uid.substring(0, 8) : '')"></h5>
                            <span class="badge badge-light font-weight-bold px-3 py-1" style="border-radius: 12px;" x-text="selectedOrder ? formatDate(selectedOrder.created_at) : ''"></span>
                        </div>
                        <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true" style="font-size: 1.8rem; color: #ffffff;">&times;</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Body (Printable Invoice Area) -->
                <div class="modal-body p-4" id="printableInvoiceArea" style="max-height: 80vh; overflow-y: auto;">
                    <!-- TOP ACTION TOOLBAR (DIRECTLY BELOW THE HEADER) -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 p-3 rounded no-print" style="background: #f8fafc; border: 1.5px solid #cbd5e1; gap: 10px;">
                        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                            <button type="button" @click="printReceiptOnly()" class="btn btn-emerald font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 8px;">
                                <i class="fa fa-print mr-1"></i> {{ __tr('Imprimer ce reçu') }}
                            </button>
                            
                            <template x-if="selectedOrder && selectedOrder.contact && selectedOrder.contact._uid">
                                <a :href="getChatUrl(selectedOrder.contact._uid)" target="_blank" class="btn btn-outline-emerald font-weight-bold" style="border-radius: 8px; color: #10b981; border-color: #10b981;">
                                    <i class="fab fa-whatsapp mr-1"></i> {{ __tr('Voir la conversation Chat') }}
                                </a>
                            </template>
                        </div>

                        <div class="d-flex align-items-center" style="gap: 10px;">
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

                            <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">
                                <i class="fa fa-times mr-1"></i> {{ __tr('Fermer') }}
                            </button>
                        </div>
                    </div>

                    <!-- Receipt Header Info Grid -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-user text-emerald mr-1"></i> {{ __tr('Informations Client') }}</h6>
                                <h6 class="font-weight-bold text-dark mb-1" x-text="selectedOrder && selectedOrder.contact ? (selectedOrder.contact.first_name + ' ' + selectedOrder.contact.last_name) : '{{ __tr('Client Inconnu') }}'"></h6>
                                <template x-if="selectedOrder && selectedOrder.contact && selectedOrder.contact._uid">
                                    <p class="mb-1">
                                        <a :href="getChatUrl(selectedOrder.contact._uid)" target="_blank" class="text-emerald font-weight-bold small" style="color: #059669;" title="{{ __tr('Ouvrir la conversation WhatsApp') }}">
                                            WhatsApp: <span x-text="selectedOrder.contact.wa_id"></span>
                                        </a>
                                    </p>
                                </template>
                                <template x-if="getDeliveryAddress(selectedOrder)">
                                    <p class="small text-dark mb-1"><strong>{{ __tr('Livraison à:') }}</strong> <span x-text="getDeliveryAddress(selectedOrder)"></span></p>
                                </template>
                                <template x-if="getDeliveryDate(selectedOrder)">
                                    <p class="small text-dark mb-0"><strong>{{ __tr('Date souhaitée:') }}</strong> <span x-text="getDeliveryDate(selectedOrder)"></span></p>
                                </template>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-info-circle text-emerald mr-1"></i> {{ __tr('Détails Commande & Source') }}</h6>
                                <p class="small text-dark mb-1"><strong>{{ __tr('Référence:') }}</strong> <span x-text="selectedOrder ? '#' + selectedOrder._uid.substring(0, 8) : ''"></span></p>
                                <p class="small text-dark mb-1"><strong>{{ __tr('Source / Agent:') }}</strong> <span class="font-weight-bold text-primary" x-text="getSource(selectedOrder)"></span></p>
                                <p class="small text-dark mb-0"><strong>{{ __tr('Date de création:') }}</strong> <span x-text="selectedOrder ? formatDate(selectedOrder.created_at) : ''"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Formatted Products Table -->
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
                                <template x-for="(item, idx) in getItems(selectedOrder)" :key="idx">
                                    <tr>
                                        <td class="align-middle font-weight-bold text-dark" x-text="item.name || 'Produit'"></td>
                                        <td class="align-middle text-center font-weight-bold" x-text="'x' + (item.quantity || 1)"></td>
                                        <td class="align-middle text-right" x-text="Number(item.price || 0).toLocaleString() + ' CFA'"></td>
                                        <td class="align-middle text-right font-weight-bold text-dark" x-text="(Number(item.price || 0) * Number(item.quantity || 1)).toLocaleString() + ' CFA'"></td>
                                    </tr>
                                </template>
                                <template x-if="getItems(selectedOrder).length === 0">
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">
                                            {{ __tr('Détails des articles enregistrés.') }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot style="background: #ecfdf5;">
                                <template x-if="getAdditionalFee(selectedOrder) > 0">
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold text-dark small">
                                            <span x-text="getAdditionalFeeLabel(selectedOrder)"></span>:
                                        </td>
                                        <td class="text-right font-weight-bold text-dark small" x-text="getAdditionalFee(selectedOrder).toLocaleString() + ' CFA'">
                                        </td>
                                    </tr>
                                </template>
                                <tr>
                                    <td colspan="3" class="text-right font-weight-bold text-uppercase text-dark" style="font-size: 1.05rem;">
                                        {{ __tr('Montant Total à Payer:') }}
                                    </td>
                                    <td class="text-right font-weight-bold text-emerald" style="font-size: 1.2rem; color: #059669;" x-text="getTotal(selectedOrder).toLocaleString() + ' CFA'">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Contact Notes -->
                    <div x-show="selectedOrder && selectedOrder.contact && selectedOrder.contact.contact_notes" class="p-3 rounded mb-2" style="background: #f8fafc; border: 1.5px dashed #cbd5e1;">
                        <h6 class="font-weight-bold text-muted small mb-1"><i class="fa fa-sticky-note text-warning mr-1"></i> {{ __tr('Historique & Notes Client') }}</h6>
                        <pre class="small text-dark mb-0" style="white-space: pre-wrap; font-family: inherit;" x-text="selectedOrder && selectedOrder.contact ? selectedOrder.contact.contact_notes : ''"></pre>
                    </div>
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

<script>
function ordersPageData() {
    return {
        allOrders: {!! json_encode($orders) !!},
        allContacts: {!! json_encode($contactsList) !!},
        allProducts: {!! json_encode($productsList) !!},
        teamMembers: {!! json_encode($teamMembers) !!},
        orderSearch: '',
        orderStatusFilter: '',
        orderSourceFilter: '',
        orderDateFilter: '',
        orderDateSort: 'desc',
        newOrderContactId: '',
        newOrderItems: [
            { product_id: '', quantity: 1, custom_price: '' }
        ],
        newOrderAdditionalFee: 0,
        newOrderAdditionalFeeLabel: 'Frais de livraison',
        newOrderAddress: '',
        newOrderDate: '',
        isSavingManualOrder: false,

        printReceiptOnly: function() {
            var printContent = document.getElementById('printableInvoiceArea');
            if (!printContent) return;

            var orderRef = this.selectedOrder ? ('#' + this.selectedOrder._uid.substring(0, 8)) : '';
            
            var iframe = document.getElementById('receipt_print_frame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'receipt_print_frame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }

            var doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!DOCTYPE html><html><head><title>Reçu de Commande ' + orderRef + '</title>');
            doc.write('<link rel="stylesheet" href="' + window.location.origin + '/static-assets/packages/bootstrap/css/bootstrap.min.css">');
            doc.write('<link rel="stylesheet" href="' + window.location.origin + '/static-assets/packages/fontawesome/css/all.min.css">');
            doc.write('<style>');
            doc.write('body { font-family: system-ui, -apple-system, sans-serif; background: #fff; color: #000; padding: 20px; margin: 0; }');
            doc.write('.no-print { display: none !important; }');
            doc.write('.table-bordered th, .table-bordered td { border: 1px solid #cbd5e1 !important; }');
            doc.write('</style>');
            doc.write('</head><body>');
            doc.write(printContent.innerHTML);
            doc.write('</body></html>');
            doc.close();

            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 400);
        },

        printOrdersListOnly: function() {
            var printContent = document.getElementById('printableOrdersListArea');
            if (!printContent) return;

            var iframe = document.getElementById('orders_list_print_frame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'orders_list_print_frame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }

            var doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!DOCTYPE html><html><head><title>Liste des Commandes</title>');
            doc.write('<link rel="stylesheet" href="' + window.location.origin + '/static-assets/packages/bootstrap/css/bootstrap.min.css">');
            doc.write('<link rel="stylesheet" href="' + window.location.origin + '/static-assets/packages/fontawesome/css/all.min.css">');
            doc.write('<style>');
            doc.write('body { font-family: system-ui, -apple-system, sans-serif; background: #fff; color: #000; padding: 20px; margin: 0; }');
            doc.write('.no-print { display: none !important; }');
            doc.write('.table-bordered th, .table-bordered td { border: 1px solid #cbd5e1 !important; }');
            doc.write('</style>');
            doc.write('</head><body>');
            doc.write('<h3 class="mb-3 font-weight-bold">Rapport des Commandes (' + this.getFilteredOrders().length + ')</h3>');
            doc.write(printContent.innerHTML);
            doc.write('</body></html>');
            doc.close();

            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 400);
        },

        setTodayFilter: function() {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            this.orderDateFilter = yyyy + '-' + mm + '-' + dd;
        },

        getFilteredOrders: function() {
            var self = this;
            var result = this.allOrders.filter(function(o) {
                if (!o) return false;
                
                // Search filter
                var contactName = o.contact ? ((o.contact.first_name || '') + ' ' + (o.contact.last_name || '') + ' ' + (o.contact.wa_id || '')) : '';
                var orderRef = o._uid ? o._uid : '';
                var matchesSearch = !self.orderSearch || 
                    contactName.toLowerCase().indexOf(self.orderSearch.toLowerCase()) !== -1 || 
                    orderRef.toLowerCase().indexOf(self.orderSearch.toLowerCase()) !== -1;
                
                // Status filter
                var matchesStatus = !self.orderStatusFilter || o.status === self.orderStatusFilter;
                
                // Source / Agent filter
                var orderSource = self.getSource(o);
                var matchesSource = !self.orderSourceFilter || 
                    orderSource.toLowerCase().indexOf(self.orderSourceFilter.toLowerCase()) !== -1;

                // Date filter (YYYY-MM-DD match)
                var matchesDate = true;
                if (self.orderDateFilter && o.created_at) {
                    try {
                        var d = new Date(o.created_at);
                        if (!isNaN(d.getTime())) {
                            var yyyy = d.getFullYear();
                            var mm = String(d.getMonth() + 1).padStart(2, '0');
                            var dd = String(d.getDate()).padStart(2, '0');
                            var orderCreatedStr = yyyy + '-' + mm + '-' + dd;
                            matchesDate = (orderCreatedStr === self.orderDateFilter);
                        } else {
                            matchesDate = (String(o.created_at).indexOf(self.orderDateFilter) === 0);
                        }
                    } catch(e) {
                        matchesDate = (String(o.created_at).indexOf(self.orderDateFilter) === 0);
                    }
                }

                return matchesSearch && matchesStatus && matchesSource && matchesDate;
            });

            result.sort(function(a, b) {
                var dateA = new Date(a.created_at || 0);
                var dateB = new Date(b.created_at || 0);
                return self.orderDateSort === 'asc' ? dateA - dateB : dateB - dateA;
            });

            return result;
        },

        formatDate: function(dateStr) {
            if (!dateStr) return '';
            try {
                var d = new Date(dateStr);
                if (isNaN(d.getTime())) return String(dateStr);
                return d.toLocaleDateString('fr-FR', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
            } catch(e) {
                return String(dateStr);
            }
        },

        getItems: function(order) {
            if (!order || !order.order_details) return [];
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return []; }
            }
            if (!details || typeof details !== 'object') return [];

            var rawItems = details.items || details.product_items || details.products || [];
            if (!Array.isArray(rawItems)) {
                if (details.name || details.product_name || details.title) {
                    rawItems = [details];
                } else {
                    return [];
                }
            }

            return rawItems.map(function(item) {
                if (!item || typeof item !== 'object') return { name: 'Produit', quantity: 1, price: 0 };
                var name = item.name || item.title || item.product_name || (item.product_retailer_id ? ('Réf: ' + item.product_retailer_id) : 'Produit');
                var qty = Number(item.quantity || item.qty || 1);
                var price = Number(item.price || item.item_price || item.unit_price || 0);
                return {
                    name: name,
                    quantity: qty,
                    price: price
                };
            });
        },

        getTotal: function(order) {
            if (!order || !order.order_details) return 0;
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return 0; }
            }
            if (!details || typeof details !== 'object') return 0;

            if (details.total_price !== undefined && details.total_price !== null) {
                return Number(details.total_price) || 0;
            }
            if (details.total !== undefined && details.total !== null) {
                return Number(details.total) || 0;
            }

            var items = this.getItems(order);
            var total = 0;
            for (var i = 0; i < items.length; i++) {
                total += (Number(items[i].price) || 0) * (Number(items[i].quantity) || 1);
            }
            return total;
        },

        getSource: function(order) {
            if (!order || !order.order_details) return 'WhatsApp';
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return 'WhatsApp'; }
            }
            if (!details || typeof details !== 'object') return 'WhatsApp';

            var src = details.source || details.created_by_vendor || '';
            if (!src) return 'WhatsApp';
            if (src === 'whatsapp_ai') return '🤖 Bot / IA WhatsApp';
            if (src === 'manual' || src === 'manuel') return '👤 Vendeur Manuel';
            return src;
        },

        getDeliveryAddress: function(order) {
            if (!order || !order.order_details) return '';
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return ''; }
            }
            if (!details || typeof details !== 'object') return '';
            return details.delivery_address || details.address || details.shipping_address || '';
        },

        getDeliveryDate: function(order) {
            if (!order || !order.order_details) return '';
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return ''; }
            }
            if (!details || typeof details !== 'object') return '';
            return details.delivery_date || details.shipping_date || '';
        },

        getChatUrl: function(contactUid) {
            if (!contactUid) return '#';
            var baseUrl = '{{ route("vendor.chat_message.contact.view", ["contactUid" => "CONTACT_UID"]) }}';
            return baseUrl.replace('CONTACT_UID', contactUid);
        },

        viewOrderDetails: function(order) {
            this.selectedOrder = order;
            $('#orderDetailsModal').modal('show');
        },

        updateOrderStatus: function(orderUid, newStatus) {
            var self = this;
            __DataRequest.post('{{ route("vendor.ecommerce.orders.update_status", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), { status: newStatus }, function(response) {
                var isSuccess = response.reaction_code == 1 || response.reaction == 1 || (response.data && (response.data.reaction_code == 1 || response.data.reaction == 1));
                var msg = response.message || (response.data && response.data.message) || 'Statut mis à jour avec succès.';
                if (isSuccess) {
                    showSuccessMessage(msg);
                    var ord = self.allOrders.find(function(o) { return o._uid === orderUid; });
                    if (ord) ord.status = newStatus;
                } else {
                    showErrorMessage(msg || 'Erreur de mise à jour.');
                }
            });
        },

        deleteOrder: function(orderUid) {
            if (confirm('{{ __tr("Voulez-vous supprimer cette commande ?") }}')) {
                var self = this;
                __DataRequest.post('{{ route("vendor.ecommerce.orders.delete", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), {}, function(response) {
                    var isSuccess = response.reaction_code == 1 || response.reaction == 1 || (response.data && (response.data.reaction_code == 1 || response.data.reaction == 1));
                    var msg = response.message || (response.data && response.data.message) || 'Commande supprimée avec succès.';
                    if (isSuccess) {
                        showSuccessMessage(msg);
                        self.allOrders = self.allOrders.filter(function(o) {
                            return o._uid !== orderUid && o._id !== orderUid;
                        });
                    } else {
                        showErrorMessage(msg || 'Erreur de suppression.');
                    }
                });
            }
        },

        getAdditionalFee: function(order) {
            if (!order || !order.order_details) return 0;
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return 0; }
            }
            if (!details || typeof details !== 'object') return 0;
            return Number(details.additional_fee || details.shipping_fee || details.delivery_fee || 0);
        },

        getAdditionalFeeLabel: function(order) {
            if (!order || !order.order_details) return 'Frais additionnels';
            var details = order.order_details;
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { return 'Frais additionnels'; }
            }
            if (!details || typeof details !== 'object') return 'Frais additionnels';
            return details.additional_fee_label || details.shipping_fee_label || 'Frais additionnels / Livraison';
        },

        addOrderItem: function() {
            this.newOrderItems.push({ product_id: '', quantity: 1, custom_price: '' });
        },

        removeOrderItem: function(index) {
            if (this.newOrderItems.length > 1) {
                this.newOrderItems.splice(index, 1);
            }
        },

        onItemProductChange: function(index) {
            var item = this.newOrderItems[index];
            if (!item || !item.product_id) return;
            var self = this;
            var prod = this.allProducts.find(function(p) { return p._id == item.product_id || p._uid == item.product_id; });
            if (prod) {
                item.custom_price = prod.price;
            }
        },

        getNewOrderSubtotal: function() {
            var sub = 0;
            for (var i = 0; i < this.newOrderItems.length; i++) {
                var qty = Number(this.newOrderItems[i].quantity) || 1;
                var price = Number(this.newOrderItems[i].custom_price) || 0;
                sub += qty * price;
            }
            return sub;
        },

        getNewOrderTotal: function() {
            return this.getNewOrderSubtotal() + (Number(this.newOrderAdditionalFee) || 0);
        },

        submitManualOrder: function() {
            if (this.isSavingManualOrder) return;
            if (!this.newOrderContactId) {
                showErrorMessage('Veuillez sélectionner un client.');
                return;
            }
            var validItems = this.newOrderItems.filter(function(it) { return !!it.product_id; });
            if (validItems.length === 0) {
                showErrorMessage('Veuillez sélectionner au moins un produit.');
                return;
            }
            this.isSavingManualOrder = true;
            var self = this;
            __DataRequest.post('<?= route("vendor.ecommerce.orders.create_manual") ?>', {
                contact_id: this.newOrderContactId,
                items: validItems,
                additional_fee: this.newOrderAdditionalFee,
                additional_fee_label: this.newOrderAdditionalFeeLabel,
                delivery_address: this.newOrderAddress,
                delivery_date: this.newOrderDate
            }, function(response) {
                self.isSavingManualOrder = false;
                var isSuccess = response.reaction_code == 1 || response.reaction == 1 || (response.data && (response.data.reaction_code == 1 || response.data.reaction == 1));
                if (isSuccess) {
                    var msg = response.message || (response.data && response.data.message) || 'Commande enregistrée avec succès !';
                    showSuccessMessage(msg);
                    $('#createManualOrderModal').modal('hide');
                    self.newOrderItems = [{ product_id: '', quantity: 1, custom_price: '' }];
                    self.newOrderAdditionalFee = 0;
                    self.newOrderAddress = '';
                    self.newOrderDate = '';
                    var newOrd = (response.data && response.data.order) ? response.data.order : response.order;
                    if (newOrd) {
                        self.allOrders.unshift(newOrd);
                    } else {
                        setTimeout(function() { window.location.reload(); }, 1000);
                    }
                } else {
                    var errMsg = response.message || (response.data && response.data.message) || 'Erreur lors de la création.';
                    showErrorMessage(errMsg);
                }
            });
        },

        exportOrdersCSV: function() {
            var list = this.getFilteredOrders();
            if (list.length === 0) {
                showErrorMessage('Aucune commande à exporter.');
                return;
            }
            var self = this;
            var csvRows = [];
            csvRows.push(['Reference', 'Date', 'Client', 'Telephone WhatsApp', 'Produits', 'Montant Total (CFA)', 'Statut', 'Source'].join(';'));

            for (var i = 0; i < list.length; i++) {
                var o = list[i];
                var ref = '#' + o._uid.substring(0, 8);
                var dateStr = self.formatDate(o.created_at);
                var clientName = o.contact ? (o.contact.first_name + ' ' + o.contact.last_name) : 'Inconnu';
                var phone = o.contact ? o.contact.wa_id : '';
                var items = self.getItems(o).map(function(it) { return (it.name || 'Produit') + ' (x' + (it.quantity||1) + ')'; }).join(' | ');
                var total = self.getTotal(o);
                var status = o.status;
                var source = self.getSource(o);

                var row = [
                    '"' + ref + '"',
                    '"' + dateStr + '"',
                    '"' + clientName + '"',
                    '"' + phone + '"',
                    '"' + items + '"',
                    '"' + total + '"',
                    '"' + status + '"',
                    '"' + source + '"'
                ];
                csvRows.push(row.join(';'));
            }

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
    };
}
</script>
