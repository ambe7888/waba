@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
$manualProducts = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)
    ->latest()
    ->get();
$orders = \App\Yantrana\Components\ECommerce\Models\OrderModel::with('contact')
    ->where('vendors__id', $vendorId)
    ->latest()
    ->get();
$allContacts = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->orderBy('first_name')->get();
$activeIntegration = getVendorSettings('ecommerce_integration') ?: 'manual';

$isShopifyConnected = !empty(getVendorSettings('shopify_shop_url'));
$isWooCommerceConnected = !empty(getVendorSettings('woocommerce_shop_url')) && !empty(getVendorSettings('woocommerce_consumer_key')) && !empty(getVendorSettings('woocommerce_consumer_secret'));
$isWhatsAppCatalogConnected = !empty(getVendorSettings('whatsapp_catalog_id'));
$isManualConnected = true;
@endphp

<style>
.platform-card-pro {
    border: 2px solid #94a3b8 !important;
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff !important;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.platform-card-pro:hover {
    transform: translateY(-4px);
    border-color: #64748b !important;
    box-shadow: 0 12px 24px -6px rgba(0,0,0,0.15);
}
.platform-card-pro.selected-shopify {
    border: 3px solid #96bf48 !important;
    background-color: #f7fee7 !important;
    box-shadow: 0 6px 20px rgba(150, 191, 72, 0.25) !important;
}
.platform-card-pro.selected-woocommerce {
    border: 3px solid #7f54b3 !important;
    background-color: #faf5ff !important;
    box-shadow: 0 6px 20px rgba(127, 84, 179, 0.25) !important;
}
.platform-card-pro.selected-whatsapp_catalog {
    border: 3px solid #10b981 !important;
    background-color: #ecfdf5 !important;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25) !important;
}
.platform-card-pro.selected-manual {
    border: 3px solid #0284c7 !important;
    background-color: #f0f9ff !important;
    box-shadow: 0 6px 20px rgba(2, 132, 199, 0.25) !important;
}
.platform-card-pro .selected-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
}
.nav-pill-tab {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    color: #334155;
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
}
.nav-pill-tab.active {
    background: #10b981;
    color: #ffffff;
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
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
.sharp-card {
    border: 2px solid #cbd5e1 !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
}
</style>

<div class="container-fluid pb-5" x-data="{
    mainTab: 'products',
    integration: '{{ getVendorSettings('ecommerce_integration') ?: 'manual' }}',
    isSyncing: false,
    syncMessage: '',
    manualTab: 'add',
    allProducts: {{ json_encode($manualProducts) }},
    allOrders: {{ json_encode($orders) }},
    catalogSearch: '',
    catalogSourceFilter: '',
    orderSearch: '',
    orderStatusFilter: '',
    orderDateSort: 'desc',
    selectedOrder: null,
    filteredCatalogProducts() {
        return this.allProducts.filter(p => {
            var matchesSearch = !this.catalogSearch || (p.name && p.name.toLowerCase().includes(this.catalogSearch.toLowerCase())) || (p.description && p.description.toLowerCase().includes(this.catalogSearch.toLowerCase()));
            var matchesSource = !this.catalogSourceFilter || p.source === this.catalogSourceFilter;
            return matchesSearch && matchesSource;
        });
    },
    filteredOrders() {
        let result = this.allOrders.filter(o => {
            var contactName = o.contact ? (o.contact.first_name + ' ' + o.contact.last_name + ' ' + o.contact.wa_id) : '';
            var orderRef = o._uid ? o._uid : '';
            var matchesSearch = !this.orderSearch || contactName.toLowerCase().includes(this.orderSearch.toLowerCase()) || orderRef.toLowerCase().includes(this.orderSearch.toLowerCase());
            var matchesStatus = !this.orderStatusFilter || o.status === this.orderStatusFilter;
            return matchesSearch && matchesStatus;
        });

        return result.sort((a, b) => {
            var dateA = new Date(a.created_at || 0);
            var dateB = new Date(b.created_at || 0);
            return this.orderDateSort === 'asc' ? dateA - dateB : dateB - dateA;
        });
    },
    openOrderModal(order) {
        this.selectedOrder = order;
        $('#orderDetailModal').modal('show');
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
    syncProducts() {
        this.isSyncing = true;
        this.syncMessage = '';
        var self = this;
        __DataRequest.post('{{ route('vendor.ecommerce.sync') }}', { source: this.integration }, function(response) {
            self.isSyncing = false;
            if (response.reaction_code == 1) {
                self.syncMessage = response.message;
                showSuccessMessage(response.message);
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                self.syncMessage = response.message || '{{ __tr('Échec de la synchronisation.') }}';
                showErrorMessage(self.syncMessage);
            }
        });
    },
    deleteProduct(productUid) {
        if (confirm('{{ __tr("Voulez-vous vraiment supprimer ce produit ?") }}')) {
            var self = this;
            __DataRequest.post('{{ route("vendor.ecommerce.products.delete", ["productUid" => "PRODUCT_UID"]) }}'.replace('PRODUCT_UID', productUid), {}, function(response) {
                if (response.reaction_code == 1) {
                    showSuccessMessage(response.message);
                    self.allProducts = self.allProducts.filter(p => p._uid !== productUid);
                } else {
                    showErrorMessage(response.message || 'Erreur lors de la suppression.');
                }
            });
        }
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
    submitProductForm() {
        var form = document.getElementById('addProductForm');
        var formData = new FormData(form);
        fetch('{{ route("vendor.ecommerce.products.add") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.reaction_code == 1) {
                showSuccessMessage(data.message || 'Produit ajouté avec succès.');
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                showErrorMessage(data.message || 'Erreur lors de l\'ajout.');
            }
        })
        .catch(() => showErrorMessage('Erreur réseau.'));
    },
    submitImportForm() {
        var form = document.getElementById('importProductForm');
        var formData = new FormData(form);
        fetch('{{ route("vendor.ecommerce.products.import") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.reaction_code == 1) {
                showSuccessMessage(data.message || 'Importation réussie.');
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                showErrorMessage(data.message || 'Erreur lors de l\'importation.');
            }
        })
        .catch(() => showErrorMessage('Erreur réseau.'));
    },
    isDetectingCatalog: false,
    metaCatalogs: [],
    showCatalogList: false,
    detectMetaCatalog() {
        this.isDetectingCatalog = true;
        this.metaCatalogs = [];
        this.showCatalogList = false;
        var self = this;
        fetch('{{ route('vendor.ecommerce.meta_catalogs') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            self.isDetectingCatalog = false;
            if (data.reaction_code == 1) {
                self.metaCatalogs = data.data.catalogs;
                self.showCatalogList = true;
                showSuccessMessage('{{ __tr("Catalogues récupérés avec succès.") }}');
            } else {
                showErrorMessage(data.data.message || '{{ __tr("Erreur lors de la récupération depuis Meta.") }}');
            }
        })
        .catch(() => { self.isDetectingCatalog = false; showErrorMessage('{{ __tr("Erreur réseau.") }}'); });
    },
    selectCatalog(catalogId) {
        document.getElementById('whatsapp_catalog_id').value = catalogId;
        document.getElementById('whatsapp_catalog_id').dispatchEvent(new Event('input'));
        this.showCatalogList = false;
        showSuccessMessage('{{ __tr("Catalogue sélectionné avec succès.") }}');
    },
    clearProducts(source) {
        var msg = source === 'all' 
            ? '{{ __tr("Voulez-vous vraiment supprimer TOUS les produits du catalogue ?") }}' 
            : '{{ __tr("Voulez-vous vraiment supprimer tous les produits de cette source ?") }}';
        if (confirm(msg)) {
            var self = this;
            __DataRequest.post('{{ route('vendor.ecommerce.products.clear') }}', { source: source }, function(response) {
                if (response.reaction_code == 1) {
                    showSuccessMessage(response.message);
                    if (source === 'all') { self.allProducts = []; } 
                    else { self.allProducts = self.allProducts.filter(p => p.source !== source); }
                } else { showErrorMessage(response.message || 'Erreur lors de la suppression.'); }
            });
        }
    }
}">

    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">{{ __tr('E-Commerce et Gestion du Catalogue') }}</h1>
            <p class="text-muted small mb-0">{{ __tr('Configurez vos canaux de vente (Shopify, WooCommerce, Meta, Manuel) et suivez vos commandes WhatsApp') }}</p>
        </div>
    </div>

    @if ($vendorPlanDetails['is_limit_available'])

    <!-- Top Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Total Produits') }}</small>
                        <h3 class="font-weight-bold text-dark mb-0" x-text="allProducts.length"></h3>
                    </div>
                    <div class="icon-circle text-emerald p-3 rounded-circle" style="background: #ecfdf5; color: #10b981;">
                        <i class="fa fa-boxes fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Commandes Reçues') }}</small>
                        <h3 class="font-weight-bold text-dark mb-0" x-text="allOrders.length"></h3>
                    </div>
                    <div class="icon-circle text-primary p-3 rounded-circle" style="background: #eff6ff;">
                        <i class="fa fa-shopping-cart fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Canal Actif') }}</small>
                        <h5 class="font-weight-bold text-emerald mb-0 text-capitalize" style="color: #10b981;" x-text="integration === 'none' ? '{{ __tr('Manuel') }}' : (integration === 'whatsapp_catalog' ? 'WhatsApp Meta' : integration)"></h5>
                    </div>
                    <div class="icon-circle text-warning p-3 rounded-circle" style="background: #fffbeb;">
                        <i class="fa fa-network-wired fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card sharp-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">{{ __tr('Statut Intégration') }}</small>
                        <span class="badge badge-success px-3 py-1 font-weight-bold" style="border-radius: 12px;">{{ __tr('Actif') }}</span>
                    </div>
                    <div class="icon-circle text-info p-3 rounded-circle" style="background: #f0f9ff;">
                        <i class="fa fa-toggle-on fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 1: PLATFORM INTEGRATION SELECTION (HIGH VISIBILITY BORDERS) -->
    <div class="card sharp-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h5 class="font-weight-bold text-dark mb-1"><i class="fa fa-plug text-emerald mr-2"></i>{{ __tr('Configuration des Canaux & Plateformes E-Commerce') }}</h5>
            <p class="text-muted small mb-0">{{ __tr('Cliquez sur une plateforme ci-dessous pour saisir vos paramètres d\'intégration et synchroniser vos produits') }}</p>
        </div>

        <div class="card-body p-4">
            <!-- Platform Selection Cards -->
            <div class="row mb-4">
                <!-- Shopify Card -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="platform-card-pro" :class="[
                        integration === 'shopify' ? 'selected-shopify' : '',
                        {{ $isShopifyConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                    ]" @click="integration = 'shopify'">
                        <template x-if="integration === 'shopify'">
                            <div class="selected-badge"><i class="fa fa-check"></i></div>
                        </template>
                        <i class="fab fa-shopify mb-2 text-success" style="font-size: 2.8rem; color: #96bf48 !important;"></i>
                        <h5 class="font-weight-bold mb-1 text-dark">Shopify</h5>
                        <p class="text-xs text-muted mb-0">{{ __tr('Boutique Shopify') }}</p>
                        @if($isShopifyConnected)
                            <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fa fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                        @endif
                    </div>
                </div>

                <!-- WooCommerce Card -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="platform-card-pro" :class="[
                        integration === 'woocommerce' ? 'selected-woocommerce' : '',
                        {{ $isWooCommerceConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                    ]" @click="integration = 'woocommerce'">
                        <template x-if="integration === 'woocommerce'">
                            <div class="selected-badge"><i class="fa fa-check"></i></div>
                        </template>
                        <i class="fab fa-wordpress mb-2" style="font-size: 2.8rem; color: #7f54b3 !important;"></i>
                        <h5 class="font-weight-bold mb-1 text-dark">WooCommerce</h5>
                        <p class="text-xs text-muted mb-0">{{ __tr('WordPress / WooCommerce') }}</p>
                        @if($isWooCommerceConnected)
                            <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fa fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                        @endif
                    </div>
                </div>

                <!-- WhatsApp Catalog Card -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="platform-card-pro" :class="[
                        integration === 'whatsapp_catalog' ? 'selected-whatsapp_catalog' : '',
                        {{ $isWhatsAppCatalogConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                    ]" @click="integration = 'whatsapp_catalog'">
                        <template x-if="integration === 'whatsapp_catalog'">
                            <div class="selected-badge"><i class="fa fa-check"></i></div>
                        </template>
                        <i class="fab fa-whatsapp mb-2 text-emerald" style="font-size: 2.8rem; color: #10b981 !important;"></i>
                        <h5 class="font-weight-bold mb-1 text-dark">{{ __tr('Catalogue Meta') }}</h5>
                        <p class="text-xs text-muted mb-0">{{ __tr('WhatsApp Cloud Catalog') }}</p>
                        @if($isWhatsAppCatalogConnected)
                            <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fa fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                        @endif
                    </div>
                </div>

                <!-- Manual Card -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="platform-card-pro" :class="[
                        integration === 'manual' ? 'selected-manual' : '',
                        {{ $isManualConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                    ]" @click="integration = 'manual'">
                        <template x-if="integration === 'manual'">
                            <div class="selected-badge"><i class="fa fa-check"></i></div>
                        </template>
                        <i class="fa fa-edit mb-2 text-info" style="font-size: 2.8rem;"></i>
                        <h5 class="font-weight-bold mb-1 text-dark">{{ __tr('Manuel / Excel') }}</h5>
                        <p class="text-xs text-muted mb-0">{{ __tr('Création directe ou CSV') }}</p>
                        @if($isManualConnected)
                            <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fa fa-check-circle mr-1"></i> {{ __tr('Actif') }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- DYNAMIC PARAMETERS FORM PANEL (HIGH VISIBILITY BORDER) -->
            <div class="p-4 shadow-sm" style="background: #ffffff !important; border: 2.5px solid #94a3b8 !important; border-top: 5px solid #10b981 !important; border-radius: 14px;">
                
                <!-- Main Form for saving settings (Shopify / WooCommerce / WhatsApp) -->
                <form x-show="integration !== 'manual'" class="lw-ajax-form lw-form" method="post" action="<?= route('vendor.settings.write.update', ['pageType' => 'internals']) ?>">
                    <input type="hidden" name="pageType" value="internals">
                    <input type="hidden" name="ecommerce_integration" :value="integration">

                    <!-- Shopify Config Parameters -->
                    <div x-show="integration === 'shopify'" x-cloak>
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fab fa-shopify mr-2 text-success"></i> {{ __tr('Paramètres d\'intégration Shopify') }}</h6>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark" for="shopify_shop_url">{{ __tr('URL de votre boutique Shopify (subdomain.myshopify.com)') }}</label>
                            <input type="text" class="form-control form-control-lg p-3 custom-input-white" id="shopify_shop_url" value="{{ getVendorSettings('shopify_shop_url') }}" name="shopify_shop_url" placeholder="ex: maboutique.myshopify.com">
                            <small class="form-text text-muted mt-1">{{ __tr('Saisissez le sous-domaine .myshopify.com de votre boutique Shopify.') }}</small>
                        </div>
                    </div>

                    <!-- WooCommerce Config Parameters -->
                    <div x-show="integration === 'woocommerce'" x-cloak>
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fab fa-wordpress mr-2" style="color: #7f54b3;"></i> {{ __tr('Paramètres d\'intégration WooCommerce') }}</h6>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark" for="woocommerce_shop_url">{{ __tr('URL de votre boutique WooCommerce') }}</label>
                            <input type="text" class="form-control form-control-lg p-3 custom-input-white" id="woocommerce_shop_url" value="{{ getVendorSettings('woocommerce_shop_url') }}" name="woocommerce_shop_url" placeholder="ex: https://maboutique-wordpress.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark" for="woocommerce_consumer_key">{{ __tr('WooCommerce Consumer Key') }}</label>
                                <input type="text" class="form-control p-3 custom-input-white" id="woocommerce_consumer_key" value="{{ getVendorSettings('woocommerce_consumer_key') }}" name="woocommerce_consumer_key" placeholder="ck_...">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-dark" for="woocommerce_consumer_secret">{{ __tr('WooCommerce Consumer Secret') }}</label>
                                <input type="password" class="form-control p-3 custom-input-white" id="woocommerce_consumer_secret" value="{{ getVendorSettings('woocommerce_consumer_secret') }}" name="woocommerce_consumer_secret" placeholder="cs_...">
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp Catalog Config Parameters -->
                    <div x-show="integration === 'whatsapp_catalog'" x-cloak>
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fab fa-whatsapp mr-2 text-emerald"></i> {{ __tr('Paramètres du Catalogue WhatsApp Meta') }}</h6>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark" for="whatsapp_catalog_id">{{ __tr('ID du Catalogue WhatsApp Meta') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg p-3 custom-input-white" id="whatsapp_catalog_id" value="{{ getVendorSettings('whatsapp_catalog_id') }}" name="whatsapp_catalog_id" placeholder="ex: 128392193892182" style="border-radius: 10px 0 0 10px !important;">
                                <div class="input-group-append">
                                    <button type="button" @click="detectMetaCatalog()" class="btn btn-emerald font-weight-bold text-white shadow-sm px-4" style="background: #10b981; border-radius: 0 10px 10px 0;" :disabled="isDetectingCatalog">
                                        <span x-show="!isDetectingCatalog"><i class="fa fa-magic mr-1"></i> {{ __tr('Détecter depuis Meta') }}</span>
                                        <span x-show="isDetectingCatalog"><i class="fa fa-spinner fa-spin mr-1"></i> {{ __tr('Recherche...') }}</span>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted mt-1">{{ __tr('Renseignez l\'identifiant de votre catalogue Meta Business Manager ou cliquez pour le récupérer.') }}</small>

                            <!-- Meta Catalogs List Selector -->
                            <div x-show="showCatalogList" class="mt-3 p-3" style="background: #ffffff !important; border: 2px solid #cbd5e1; border-radius: 10px;" x-cloak>
                                <h6 class="font-weight-bold text-dark mb-2"><i class="fa fa-list mr-1 text-emerald"></i> {{ __tr('Catalogues trouvés sur votre compte Facebook :') }}</h6>
                                <div class="list-group">
                                    <template x-for="cat in metaCatalogs" :key="cat.id">
                                        <button type="button" @click="selectCatalog(cat.id)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3">
                                            <div>
                                                <span class="font-weight-bold text-dark text-sm" x-text="cat.name"></span>
                                                <div class="text-xs text-muted">ID: <span class="text-monospace" x-text="cat.id"></span></div>
                                            </div>
                                            <span class="badge badge-success px-3 py-1 text-xs" style="border-radius: 12px;"><i class="fa fa-check mr-1"></i> {{ __tr('Sélectionner') }}</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons for platforms -->
                    <div class="form-group mt-4 mb-0">
                        <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                            <i class="fa fa-save mr-1"></i> {{ __tr('Sauvegarder les paramètres') }}
                        </button>
                        
                        <span x-show="integration === 'shopify' || integration === 'woocommerce' || integration === 'whatsapp_catalog'" x-cloak>
                            <button type="button" @click="syncProducts()" class="btn btn-success font-weight-bold px-4 py-2 ml-2" style="border-radius: 8px;" :disabled="isSyncing">
                                <span x-show="!isSyncing"><i class="fa fa-sync mr-1"></i> {{ __tr('Synchroniser les produits') }}</span>
                                <span x-show="isSyncing"><i class="fa fa-spinner fa-spin mr-1"></i> {{ __tr('Synchronisation...') }}</span>
                            </button>
                            
                            <button type="button" @click="clearProducts(integration)" class="btn btn-outline-danger font-weight-bold px-4 py-2 ml-2" style="border-radius: 8px;">
                                <i class="fa fa-trash-alt mr-1"></i> {{ __tr('Vider cette source') }}
                            </button>
                        </span>
                    </div>
                </form>

                <!-- Local Manual / Excel Catalog Panel -->
                <div x-show="integration === 'manual'" x-cloak>
                    <!-- Sub-Tabs -->
                    <div class="d-flex border-bottom mb-4">
                        <button type="button" @click="manualTab = 'add'" class="btn btn-link nav-link font-weight-bold px-4 py-2" :class="manualTab === 'add' ? 'active border-bottom border-emerald text-emerald' : 'text-muted'" style="text-decoration: none; color: #10b981;">
                            <i class="fa fa-plus-circle mr-2"></i> {{ __tr('Créer un Produit Manuellement') }}
                        </button>
                        <button type="button" @click="manualTab = 'import'" class="btn btn-link nav-link font-weight-bold px-4 py-2" :class="manualTab === 'import' ? 'active border-bottom border-emerald text-emerald' : 'text-muted'" style="text-decoration: none; color: #10b981;">
                            <i class="fa fa-file-excel mr-2"></i> {{ __tr('Importer un Fichier CSV / Excel') }}
                        </button>
                    </div>

                    <!-- TAB: Add Product Form -->
                    <div x-show="manualTab === 'add'">
                        <form id="addProductForm" @submit.prevent="submitProductForm()" class="p-3 shadow-sm" style="background: #ffffff !important; border: 2px solid #cbd5e1; border-radius: 12px;" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold text-dark" for="prod_name">{{ __tr('Nom du Produit *') }}</label>
                                    <input type="text" class="form-control p-3 custom-input-white" id="prod_name" name="name" required placeholder="{{ __tr('ex: Produit de soin Premium') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold text-dark" for="prod_price">{{ __tr('Prix (CFA) *') }}</label>
                                    <input type="number" class="form-control p-3 custom-input-white" id="prod_price" name="price" required placeholder="ex: 15000">
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark" for="prod_desc">{{ __tr('Description') }}</label>
                                <textarea class="form-control p-3 custom-input-white" id="prod_desc" name="description" rows="2" placeholder="{{ __tr('Description courte du produit...') }}"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold text-dark" for="prod_img_file">{{ __tr('Image du produit (Téléverser)') }}</label>
                                    <input type="file" class="form-control-file" id="prod_img_file" name="image_file" accept="image/*">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold text-dark" for="prod_img_url">{{ __tr('Ou URL d\'image externe') }}</label>
                                    <input type="url" class="form-control p-3 custom-input-white" id="prod_img_url" name="image_url" placeholder="https://exemple.com/image.jpg">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark" for="prod_link">{{ __tr('Lien direct d\'achat / Détails') }}</label>
                                <input type="url" class="form-control p-3 custom-input-white" id="prod_link" name="direct_link" placeholder="https://maboutique.com/produit/1">
                            </div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                    <i class="fa fa-plus mr-1"></i> {{ __tr('Ajouter le produit au catalogue') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB: Import CSV -->
                    <div x-show="manualTab === 'import'">
                        <div class="p-3 shadow-sm" style="background: #ffffff !important; border: 2px solid #cbd5e1; border-radius: 12px;">
                            <h6 class="font-weight-bold text-dark mb-2"><i class="fa fa-file-csv text-emerald mr-1"></i> {{ __tr('Importer des produits en masse via CSV') }}</h6>
                            
                            <div class="p-3 mb-3" style="background: #f8fafc !important; border: 1.5px solid #cbd5e1; border-radius: 10px;">
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fa fa-info-circle text-info mr-1"></i> {{ __tr('En-têtes requis dans votre fichier CSV :') }}</h6>
                                <p class="small text-muted mb-2"><code>name</code> (ou <code>nom</code>), <code>price</code> (ou <code>prix</code>), <code>description</code>, <code>image_url</code>, <code>direct_link</code> (ou <code>lien</code>).</p>
                                <a href="data:text/csv;charset=utf-8,name,description,price,image_url,direct_link%0AExemple%20Produit,Description%20du%20produit%20ici,15000,https://example.com/image.jpg,https://example.com/buy" download="template_produits.csv" class="btn btn-sm btn-outline-emerald font-weight-bold" style="border-radius: 8px; color: #10b981; border-color: #10b981;">
                                    <i class="fa fa-download mr-1"></i> {{ __tr('Télécharger modèle CSV') }}
                                </a>
                            </div>

                            <form id="importProductForm" @submit.prevent="submitImportForm()" enctype="multipart/form-data">
                                <div class="form-group mb-3">
                                    <input type="file" class="form-control-file" id="csv_file" name="file" accept=".csv,.txt" required>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-emerald font-weight-bold px-4 py-2 text-white" style="background: #10b981; border: none; border-radius: 8px;">
                                        <i class="fa fa-upload mr-1"></i> {{ __tr('Importer les produits') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Hidden configuration field -->
                    <form id="persistManualForm" class="lw-ajax-form lw-form d-none" method="post" action="<?= route('vendor.settings.write.update', ['pageType' => 'internals']) ?>">
                        <input type="hidden" name="pageType" value="internals">
                        <input type="hidden" name="ecommerce_integration" value="manual">
                    </form>
                    <div class="mt-3 pt-3 border-top d-flex align-items-center">
                        <button type="button" @click="document.getElementById('persistManualForm').querySelector('button[type=submit] || input[type=submit]').click() || __DataRequest.post('{{ route('vendor.settings.write.update', ['pageType' => 'internals']) }}', {ecommerce_integration: 'manual', pageType: 'internals'}, function(response) { if(response.reaction_code==1){ showSuccessMessage('Mode Manuel sauvegardé.'); } });" class="btn btn-emerald font-weight-bold px-4 py-2 text-white shadow-sm" style="background: #10b981; border-radius: 8px;">
                            <i class="fa fa-save mr-1"></i> {{ __tr('Activer le mode Manuel') }}
                        </button>
                        
                        <button type="button" @click="clearProducts('manual')" class="btn btn-outline-danger font-weight-bold px-4 py-2 ml-2" style="border-radius: 8px;">
                            <i class="fa fa-trash-alt mr-1"></i> {{ __tr('Vider les produits manuels') }}
                        </button>
                    </div>
                </div>

                <div x-show="syncMessage" class="alert mt-3 border-0 shadow-sm" style="background-color: #ecfdf5; color: #065f46; border-radius: 10px;" x-text="syncMessage" x-cloak></div>
            </div>
        </div>

    </div>

    <!-- SECTION 2: PRODUCTS CATALOG & CLIENT ORDERS TABS -->
    <div class="card sharp-card mb-4">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                <button type="button" @click="mainTab = 'products'" class="nav-pill-tab" :class="mainTab === 'products' ? 'active' : ''">
                    <i class="fa fa-store mr-2"></i> {{ __tr('Catalogue Produits') }}
                </button>
                <button type="button" @click="mainTab = 'orders'" class="nav-pill-tab" :class="mainTab === 'orders' ? 'active' : ''">
                    <i class="fa fa-shopping-bag mr-2"></i> {{ __tr('Commandes Client') }}
                    <span class="badge badge-light ml-1 font-weight-bold" x-text="allOrders.length"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: PRODUCTS CATALOG -->
    <div x-show="mainTab === 'products'" class="space-y-4">
        <div class="card sharp-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h5 class="font-weight-bold text-dark mb-1">{{ __tr('Tous les Produits du Catalogue') }}</h5>
                    <p class="text-muted small mb-0">{{ __tr('Recherchez et filtrez l\'ensemble des produits de votre boutique') }}</p>
                </div>
                <div class="mt-2 mt-sm-0">
                    <button type="button" @click="clearProducts('all')" class="btn btn-outline-danger btn-sm font-weight-bold px-3 py-2" style="border-radius: 8px;">
                        <i class="fa fa-trash-alt mr-1"></i> {{ __tr('Vider tout le catalogue') }}
                    </button>
                </div>
            </div>
            
            <div class="card-body p-4">
                <!-- Filters & Search -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-dark small mb-1">{{ __tr('Rechercher un produit') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control p-3 custom-input-white" style="border-radius: 10px 0 0 10px !important;" placeholder="{{ __tr('Nom ou description...') }}" x-model="catalogSearch">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white" style="border: 2px solid #94a3b8; border-left: none; border-radius: 0 10px 10px 0;"><i class="fa fa-search text-muted"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-dark small mb-1">{{ __tr('Filtrer par source') }}</label>
                        <select class="form-control custom-input-white" style="border-radius: 10px !important;" x-model="catalogSourceFilter">
                            <option value="">{{ __tr('Toutes les sources') }}</option>
                            <option value="manual">{{ __tr('Catalogue Manuel / Excel') }}</option>
                            <option value="shopify">Shopify</option>
                            <option value="woocommerce">WooCommerce</option>
                            <option value="whatsapp_catalog">{{ __tr('WhatsApp Meta Catalog') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-items-center mb-0" style="border-radius: 12px; overflow: hidden; border: 1.5px solid #cbd5e1;">
                        <thead class="text-muted small text-uppercase" style="background: #f8fafc !important;">
                            <tr>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Visuel') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Produit') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Source') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Prix') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Description') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Lien') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;" class="text-right">{{ __tr('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in filteredCatalogProducts()" :key="product._uid">
                                <tr>
                                    <td class="align-middle">
                                        <template x-if="product.image_url">
                                            <img :src="product.image_url" class="rounded shadow-sm" style="width: 52px; height: 52px; object-fit: cover; border-radius: 10px !important;">
                                        </template>
                                        <template x-if="!product.image_url">
                                            <div class="rounded d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; background: #f1f5f9; color: #94a3b8; border-radius: 10px !important;">
                                                <i class="fa fa-image fa-lg"></i>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="align-middle">
                                        <h6 class="font-weight-bold text-dark mb-0" x-text="product.name"></h6>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge border px-3 py-1 font-weight-bold" 
                                              :class="{
                                                  'badge-success text-white': product.source === 'whatsapp_catalog',
                                                  'badge-primary text-white': product.source === 'woocommerce',
                                                  'badge-info text-white': product.source === 'shopify',
                                                  'badge-secondary text-white': product.source === 'manual'
                                              }"
                                              style="border-radius: 12px;"
                                              x-text="product.source === 'whatsapp_catalog' ? 'WhatsApp' : product.source">
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="font-weight-bold text-emerald" style="color: #059669;" x-text="Number(product.price).toLocaleString() + ' CFA'"></span>
                                    </td>
                                    <td class="align-middle text-muted small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="product.description || '-'"></td>
                                    <td class="align-middle">
                                        <template x-if="product.direct_link">
                                            <a :href="product.direct_link" target="_blank" class="badge badge-light px-3 py-2 text-dark font-weight-bold shadow-sm" style="border: 1px solid #cbd5e1; border-radius: 8px;">
                                                <i class="fa fa-external-link-alt mr-1"></i> {{ __tr('Voir') }}
                                            </a>
                                        </template>
                                        <template x-if="!product.direct_link">
                                            <span class="text-muted small">-</span>
                                        </template>
                                    </td>
                                    <td class="align-middle text-right">
                                        <button type="button" @click="deleteProduct(product._uid)" class="btn btn-sm btn-outline-danger shadow-sm" style="border-radius: 8px;" title="{{ __tr('Supprimer') }}">
                                            <i class="fa fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="filteredCatalogProducts().length === 0" class="text-center py-5 text-muted">
                        <i class="fa fa-box-open fa-3x text-muted mb-3 d-block"></i>
                        <p class="mb-0 font-weight-bold">{{ __tr('Aucun produit disponible dans le catalogue.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: CLIENT ORDERS -->
    <div x-show="mainTab === 'orders'" class="space-y-4">
        <div class="card sharp-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="font-weight-bold text-dark mb-1">{{ __tr('Gestion des Commandes Client') }}</h5>
                <p class="text-muted small mb-0">{{ __tr('Suivez les commandes passées par vos clients sur WhatsApp et modifiez leur statut en temps réel') }}</p>
            </div>

            <div class="card-body p-4">
                <!-- Filters & Search -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold text-dark small mb-1">{{ __tr('Rechercher un client ou contact') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control p-3 custom-input-white" style="border-radius: 10px 0 0 10px !important;" placeholder="{{ __tr('Nom, numéro ou #Réf...') }}" x-model="orderSearch">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white" style="border: 2px solid #94a3b8; border-left: none; border-radius: 0 10px 10px 0;"><i class="fa fa-search text-muted"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
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
                    <div class="col-md-4 mb-3">
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
                                <th style="border-bottom: 2px solid #cbd5e1;">{{ __tr('Statut Actuel') }}</th>
                                <th style="border-bottom: 2px solid #cbd5e1;" class="text-right">{{ __tr('Changer le statut / Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="order in filteredOrders()" :key="order._uid">
                                <tr>
                                    <td class="align-middle">
                                        <button type="button" @click="openOrderModal(order)" class="btn btn-link p-0 font-weight-bold text-emerald text-left" style="color: #059669; text-decoration: underline;" title="{{ __tr('Cliquer pour voir le reçu') }}">
                                            <span x-text="'#' + order._uid.substring(0, 8)"></span>
                                        </button>
                                        <small class="text-muted d-block" x-text="new Date(order.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'})"></small>
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
                                            <button type="button" @click="openOrderModal(order)" class="btn btn-sm btn-outline-emerald font-weight-bold" style="border-radius: 8px; color: #10b981; border-color: #10b981;" title="{{ __tr('Voir les détails et imprimer le reçu') }}">
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

        <!-- ORDER DETAILS & PRINTABLE RECEIPT MODAL -->
        <div class="modal fade" id="orderDetailModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                                    <p class="small text-dark mb-1"><strong>{{ __tr('Source:') }}</strong> <span x-text="selectedOrder && selectedOrder.order_details ? (selectedOrder.order_details.source || 'WhatsApp') : 'WhatsApp'"></span></p>
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
            #printableInvoiceArea, #printableInvoiceArea * {
                visibility: visible;
            }
            #printableInvoiceArea {
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
    </div>

    @else
    <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <i class="fa fa-lock mr-2"></i> {{ __tr('La fonctionnalité E-commerce et Catalogue n\'est pas incluse dans votre formule actuelle. Veuillez mettre à niveau votre abonnement.') }}
    </div>
    @endif
</div>
