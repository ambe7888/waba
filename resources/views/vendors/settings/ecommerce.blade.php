@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
$manualProducts = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)
    ->latest()
    ->get();
$activeIntegration = getVendorSettings('ecommerce_integration') ?: 'none';

$isShopifyConnected = !empty(getVendorSettings('shopify_shop_url'));
$isWooCommerceConnected = !empty(getVendorSettings('woocommerce_shop_url')) && !empty(getVendorSettings('woocommerce_consumer_key')) && !empty(getVendorSettings('woocommerce_consumer_secret'));
$isWhatsAppCatalogConnected = !empty(getVendorSettings('whatsapp_catalog_id'));
$isManualConnected = true;
@endphp

<style>
.platform-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.platform-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
}
.platform-card.selected-shopify {
    border-color: #96bf48;
    background-color: rgba(150, 191, 72, 0.04);
    box-shadow: 0 4px 15px rgba(150, 191, 72, 0.15);
}
.platform-card.selected-woocommerce {
    border-color: #7f54b3;
    background-color: rgba(127, 84, 179, 0.04);
    box-shadow: 0 4px 15px rgba(127, 84, 179, 0.15);
}
.platform-card.selected-whatsapp_catalog {
    border-color: #25d366;
    background-color: rgba(37, 211, 102, 0.04);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.15);
}
.platform-card.selected-manual {
    border-color: #17a2b8;
    background-color: rgba(23, 162, 184, 0.04);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.15);
}
.platform-card.active-green-card {
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.04) !important;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.15) !important;
}
.platform-card .selected-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<div class="row" x-data="{
    integration: '{{ getVendorSettings('ecommerce_integration') ?: 'none' }}',
    isSyncing: false,
    syncMessage: '',
    manualTab: 'add',
    allProducts: {{ json_encode($manualProducts) }},
    catalogSearch: '',
    catalogSourceFilter: '',
    filteredCatalogProducts() {
        return this.allProducts.filter(p => {
            var matchesSearch = !this.catalogSearch || p.name.toLowerCase().includes(this.catalogSearch.toLowerCase()) || (p.description && p.description.toLowerCase().includes(this.catalogSearch.toLowerCase()));
            var matchesSource = !this.catalogSourceFilter || p.source === this.catalogSourceFilter;
            return matchesSearch && matchesSource;
        });
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
                self.syncMessage = response.message || '{{ __tr('Failed to synchronize products.') }}';
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
    submitProductForm() {
        var form = document.getElementById('addProductForm');
        var formData = new FormData(form);
        
        fetch('{{ route("vendor.ecommerce.products.add") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.reaction_code == 1) {
                showSuccessMessage(data.message || 'Produit ajouté avec succès.');
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                showErrorMessage(data.message || 'Erreur lors de l\'ajout.');
            }
        })
        .catch(error => {
            showErrorMessage('Erreur réseau.');
        });
    },
    submitImportForm() {
        var form = document.getElementById('importProductForm');
        var formData = new FormData(form);
        
        fetch('{{ route("vendor.ecommerce.products.import") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.reaction_code == 1) {
                showSuccessMessage(data.message || 'Importation réussie.');
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                showErrorMessage(data.message || 'Erreur lors de l\'importation.');
            }
        })
        .catch(error => {
            showErrorMessage('Erreur réseau.');
        });
    },
    isDetectingCatalog: false,
    metaCatalogs: [],
    showCatalogList: false,
    detectMetaCatalog() {
        this.isDetectingCatalog = true;
        this.metaCatalogs = [];
        this.showCatalogList = false;
        var self = this;
        fetch('{{ route('vendor.ecommerce.meta_catalogs') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            self.isDetectingCatalog = false;
            if (data.reaction_code == 1) {
                self.metaCatalogs = data.data.catalogs;
                self.showCatalogList = true;
                showSuccessMessage('{{ __tr("Catalogues récupérés avec succès.") }}');
            } else {
                showErrorMessage(data.data.message || '{{ __tr("Erreur lors de la récupération des catalogues depuis Meta.") }}');
            }
        })
        .catch(error => {
            self.isDetectingCatalog = false;
            showErrorMessage('{{ __tr("Erreur réseau.") }}');
        });
    },
    selectCatalog(catalogId) {
        document.getElementById('whatsapp_catalog_id').value = catalogId;
        document.getElementById('whatsapp_catalog_id').dispatchEvent(new Event('input'));
        this.showCatalogList = false;
        showSuccessMessage('{{ __tr("Catalogue sélectionné avec succès.") }}');
    }
}">
    <div class="col-md-12">
        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">
            <?= __tr('E-commerce et Catalogue') ?>
        </h1>
        <p class="mb-4 text-muted">
            {{ __tr('Sélectionnez et configurez votre catalogue produits pour le lier à votre compte WhatsApp. Recommandez des produits directement dans les chats et suivez vos ventes.') }}
        </p>

        @if ($vendorPlanDetails['is_limit_available'])
        
        <!-- Platform Selection Cards -->
        <div class="row mb-5">
            <!-- Shopify Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="platform-card" :class="[
                    integration === 'shopify' ? 'selected-shopify' : '',
                    {{ $isShopifyConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                ]" @click="integration = 'shopify'">
                    <template x-if="integration === 'shopify'">
                        <div class="selected-badge"><i class="fas fa-check"></i></div>
                    </template>
                    <i class="fab fa-shopify mb-3 text-success" style="font-size: 3rem; color: #96bf48 !important;"></i>
                    <h4 class="font-weight-bold mb-1 text-dark">Shopify</h4>
                    <p class="text-xs text-muted mb-0">{{ __tr('Synchronisation anonyme sans clé API nécessaire.') }}</p>
                    @if($isShopifyConnected)
                        <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fas fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                    @endif
                </div>
            </div>

            <!-- WooCommerce Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="platform-card" :class="[
                    integration === 'woocommerce' ? 'selected-woocommerce' : '',
                    {{ $isWooCommerceConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                ]" @click="integration = 'woocommerce'">
                    <template x-if="integration === 'woocommerce'">
                        <div class="selected-badge"><i class="fas fa-check"></i></div>
                    </template>
                    <i class="fab fa-wordpress mb-3" style="font-size: 3rem; color: #7f54b3 !important;"></i>
                    <h4 class="font-weight-bold mb-1 text-dark">WooCommerce</h4>
                    <p class="text-xs text-muted mb-0">{{ __tr('Liaison via clés d\'API Consumer Key / Secret.') }}</p>
                    @if($isWooCommerceConnected)
                        <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fas fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                    @endif
                </div>
            </div>

            <!-- WhatsApp Catalog Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="platform-card" :class="[
                    integration === 'whatsapp_catalog' ? 'selected-whatsapp_catalog' : '',
                    {{ $isWhatsAppCatalogConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                ]" @click="integration = 'whatsapp_catalog'">
                    <template x-if="integration === 'whatsapp_catalog'">
                        <div class="selected-badge"><i class="fas fa-check"></i></div>
                    </template>
                    <i class="fab fa-whatsapp mb-3 text-success" style="font-size: 3rem; color: #25d366 !important;"></i>
                    <h4 class="font-weight-bold mb-1 text-dark">{{ __tr('Catalogue WhatsApp') }}</h4>
                    <p class="text-xs text-muted mb-0">{{ __tr('Associer l\'ID de votre catalogue Meta natif.') }}</p>
                    @if($isWhatsAppCatalogConnected)
                        <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fas fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                    @endif
                </div>
            </div>

            <!-- Manual Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="platform-card" :class="[
                    integration === 'manual' ? 'selected-manual' : '',
                    {{ $isManualConnected ? 'true' : 'false' }} ? 'active-green-card' : ''
                ]" @click="integration = 'manual'">
                    <template x-if="integration === 'manual'">
                        <div class="selected-badge"><i class="fas fa-check"></i></div>
                    </template>
                    <i class="fas fa-edit mb-3 text-info" style="font-size: 3rem;"></i>
                    <h4 class="font-weight-bold mb-1 text-dark">{{ __tr('Manuel / Excel') }}</h4>
                    <p class="text-xs text-muted mb-0">{{ __tr('Créez vos produits manuellement ou via import Excel.') }}</p>
                    @if($isManualConnected)
                        <div class="mt-2"><span class="badge badge-success px-3 py-1" style="border-radius: 20px;"><i class="fas fa-check-circle mr-1"></i> {{ __tr('Connecté') }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Configuration Details Container -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <span x-show="integration === 'shopify'"><i class="fab fa-shopify mr-1"></i> {{ __tr('Configuration Shopify') }}</span>
                    <span x-show="integration === 'woocommerce'"><i class="fab fa-wordpress mr-1"></i> {{ __tr('Configuration WooCommerce') }}</span>
                    <span x-show="integration === 'whatsapp_catalog'"><i class="fab fa-whatsapp mr-1"></i> {{ __tr('Configuration Catalogue WhatsApp') }}</span>
                    <span x-show="integration === 'manual'"><i class="fas fa-edit mr-1"></i> {{ __tr('Gestion du Catalogue Manuel') }}</span>
                    <span x-show="integration === 'none'"><i class="fas fa-cogs mr-1"></i> {{ __tr('Plateforme de vente non configurée') }}</span>
                </h6>
            </div>
            
            <div class="card-body">
                
                <!-- Main Form for saving settings (Shopify / WooCommerce / WhatsApp / None) -->
                <form x-show="integration !== 'manual'" class="lw-ajax-form lw-form" method="post" action="<?= route('vendor.settings.write.update', ['pageType' => 'internals']) ?>">
                    <input type="hidden" name="pageType" value="internals">
                    <input type="hidden" name="ecommerce_integration" :value="integration">

                    <!-- Shopify Config -->
                    <div x-show="integration === 'shopify'" class="p-4 border rounded bg-white shadow-sm mb-4" x-cloak>
                        <div class="form-group">
                            <label class="font-weight-bold" for="shopify_shop_url">{{ __tr('Shopify Shop URL') }}</label>
                            <input type="text" class="form-control form-control-lg" id="shopify_shop_url" value="{{ getVendorSettings('shopify_shop_url') }}" name="shopify_shop_url" placeholder="e.g. mystore.myshopify.com">
                            <small class="form-text text-muted">{{ __tr('Entrez le sous-domaine .myshopify.com de votre boutique.') }}</small>
                        </div>
                    </div>

                    <!-- WooCommerce Config -->
                    <div x-show="integration === 'woocommerce'" class="p-4 border rounded bg-white shadow-sm mb-4" x-cloak>
                        <div class="form-group">
                            <label class="font-weight-bold" for="woocommerce_shop_url">{{ __tr('WooCommerce Shop URL') }}</label>
                            <input type="text" class="form-control form-control-lg" id="woocommerce_shop_url" value="{{ getVendorSettings('woocommerce_shop_url') }}" name="woocommerce_shop_url" placeholder="e.g. https://mywordpressstore.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" for="woocommerce_consumer_key">{{ __tr('WooCommerce Consumer Key') }}</label>
                                <input type="text" class="form-control" id="woocommerce_consumer_key" value="{{ getVendorSettings('woocommerce_consumer_key') }}" name="woocommerce_consumer_key" placeholder="ck_...">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" for="woocommerce_consumer_secret">{{ __tr('WooCommerce Consumer Secret') }}</label>
                                <input type="password" class="form-control" id="woocommerce_consumer_secret" value="{{ getVendorSettings('woocommerce_consumer_secret') }}" name="woocommerce_consumer_secret" placeholder="cs_...">
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp Catalog Config -->
                    <div x-show="integration === 'whatsapp_catalog'" class="p-4 border rounded bg-white shadow-sm mb-4" x-cloak>
                        <div class="form-group">
                            <label class="font-weight-bold" for="whatsapp_catalog_id">{{ __tr('WhatsApp Catalog ID') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" id="whatsapp_catalog_id" value="{{ getVendorSettings('whatsapp_catalog_id') }}" name="whatsapp_catalog_id" placeholder="e.g. 128392193892182">
                                <div class="input-group-append">
                                    <button type="button" @click="detectMetaCatalog()" class="btn btn-success font-weight-bold shadow-sm" :disabled="isDetectingCatalog">
                                        <span x-show="!isDetectingCatalog"><i class="fas fa-magic mr-1"></i> {{ __tr('Détecter depuis Facebook') }}</span>
                                        <span x-show="isDetectingCatalog"><i class="fas fa-spinner fa-spin mr-1"></i> {{ __tr('Recherche...') }}</span>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">{{ __tr('Liez l\'identifiant unique de votre catalogue Meta Business Manager. Ou cliquez sur le bouton pour le récupérer automatiquement.') }}</small>

                            <!-- Meta Catalogs List Selector -->
                            <div x-show="showCatalogList" class="mt-3 border rounded p-3 bg-light" x-cloak>
                                <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-list mr-1 text-success"></i> Sélectionnez un catalogue disponible sur votre compte :</h6>
                                <div class="list-group">
                                    <template x-for="cat in metaCatalogs" :key="cat.id">
                                        <button type="button" @click="selectCatalog(cat.id)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3">
                                            <div>
                                                <span class="font-weight-bold text-dark text-sm" x-text="cat.name"></span>
                                                <div class="text-xs text-muted">ID: <span class="text-monospace" x-text="cat.id"></span></div>
                                            </div>
                                            <span class="badge badge-success px-2 py-1 text-xs"><i class="fas fa-check mr-1"></i> Sélectionner</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 mt-3" style="background-color: rgba(40, 167, 69, 0.08) !important; border-left: 5px solid #28a745 !important; border-radius: 8px; color: #000000 !important;">
                            <h5 class="font-weight-bold mb-1" style="color: #28a745 !important; font-size: 0.95rem;">
                                <i class="fas fa-info-circle mr-1"></i> {{ __tr('Liaison Facebook Requise') }}
                            </h5>
                            <p class="mb-0 text-dark" style="color: #000000 !important; font-size: 0.88rem; line-height: 1.4;">
                                {{ __tr('Dans votre Meta Business Suite / WhatsApp Manager, vous devez aller dans Paramètres > Catalogues et associer le catalogue à votre numéro.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Action buttons for platforms -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="fas fa-save mr-2"></i> {{ __tr('Save Settings') }}</button>
                        
                        <span x-show="integration === 'shopify' || integration === 'woocommerce' || integration === 'whatsapp_catalog'" x-cloak>
                            <button type="button" @click="syncProducts()" class="btn btn-success btn-lg ml-2 shadow-sm" :disabled="isSyncing">
                                <span x-show="!isSyncing"><i class="fas fa-sync mr-2"></i> {{ __tr('Sync Products Now') }}</span>
                                <span x-show="isSyncing"><i class="fas fa-spinner fa-spin mr-2"></i> {{ __tr('Synchronizing...') }}</span>
                            </button>
                        </span>
                    </div>
                </form>

                <!-- Local Manual / Excel Catalog Panel -->
                <div x-show="integration === 'manual'" x-cloak>
                    
                    <!-- Manual Sub-Tabs -->
                    <div class="d-flex border-bottom mb-4">
                        <button type="button" @click="manualTab = 'add'" class="btn btn-link nav-link font-weight-bold px-4 py-3" :class="manualTab === 'add' ? 'active border-bottom border-primary text-primary' : 'text-muted'" style="text-decoration: none;">
                            <i class="fas fa-plus-circle mr-2"></i> Créer manuellement
                        </button>
                        <button type="button" @click="manualTab = 'import'" class="btn btn-link nav-link font-weight-bold px-4 py-3" :class="manualTab === 'import' ? 'active border-bottom border-primary text-primary' : 'text-muted'" style="text-decoration: none;">
                            <i class="fas fa-file-excel mr-2"></i> Importer via CSV / Excel
                        </button>
                    </div>

                    <!-- TAB 2: Add Product Form -->
                    <div x-show="manualTab === 'add'">
                        <form id="addProductForm" @submit.prevent="submitProductForm()" class="p-4 border rounded bg-white shadow-sm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" for="prod_name">Nom du Produit *</label>
                                    <input type="text" class="form-control" id="prod_name" name="name" required placeholder="e.g. T-Shirt Coton Premium">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" for="prod_price">Prix (CFA) *</label>
                                    <input type="number" class="form-control" id="prod_price" name="price" required placeholder="e.g. 15000">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold" for="prod_desc">Description</label>
                                <textarea class="form-control" id="prod_desc" name="description" rows="3" placeholder="Description courte du produit..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" for="prod_img_file">Image du produit (Uploader)</label>
                                    <input type="file" class="form-control-file" id="prod_img_file" name="image_file" accept="image/*">
                                    <small class="form-text text-muted">Format PNG, JPG ou WEBP. Maximum 5 Mo.</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" for="prod_img_url">Ou URL de l'image externe</label>
                                    <input type="url" class="form-control" id="prod_img_url" name="image_url" placeholder="https://site.com/image.jpg">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold" for="prod_link">Lien Direct / Lien d'achat</label>
                                <input type="url" class="form-control" id="prod_link" name="direct_link" placeholder="https://maboutique.com/produit/acheter">
                            </div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-success px-4 py-2 shadow-sm"><i class="fas fa-plus mr-1"></i> Créer le produit</button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 3: Import CSV -->
                    <div x-show="manualTab === 'import'">
                        <div class="p-4 border rounded bg-white shadow-sm">
                            <h4 class="font-weight-bold text-dark mb-3"><i class="fas fa-file-csv mr-1"></i> Importer des produits en masse</h4>
                            <p class="text-muted">Importez facilement votre liste de produits à partir d'un fichier CSV. Assurez-vous que votre fichier comporte les en-têtes corrects.</p>

                            <div class="bg-white p-3 border rounded mb-4">
                                <h6 class="font-weight-bold text-dark"><i class="fas fa-info-circle text-primary mr-1"></i> Format et en-têtes requis (CSV) :</h6>
                                <p class="text-sm text-muted mb-2">Les colonnes suivantes doivent figurer dans la première ligne du fichier (séparées par une virgule) :</p>
                                <ul>
                                    <li><code>name</code> (ou <code>nom</code>) : Le nom du produit *(Obligatoire)*</li>
                                    <li><code>price</code> (ou <code>prix</code>) : Le prix du produit (uniquement des chiffres)</li>
                                    <li><code>description</code> : Description textuelle</li>
                                    <li><code>image_url</code> : Lien complet vers l'image du produit</li>
                                    <li><code>direct_link</code> (ou <code>lien</code>) : Le lien d'achat direct du produit</li>
                                </ul>

                                <a href="data:text/csv;charset=utf-8,name,description,price,image_url,direct_link%0AExemple%20Produit,Description%20du%20produit%20ici,15000,https://example.com/image.jpg,https://example.com/buy" download="template_produits.csv" class="btn btn-sm btn-outline-primary shadow-sm">
                                    <i class="fas fa-download mr-1"></i> Télécharger le modèle CSV
                                </a>
                            </div>

                            <form id="importProductForm" @submit.prevent="submitImportForm()" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="font-weight-bold" for="csv_file">Sélectionner le fichier CSV *</label>
                                    <input type="file" class="form-control-file" id="csv_file" name="file" accept=".csv,.txt" required>
                                    <small class="form-text text-muted">Fichiers autorisés : .csv, .txt (taille maximale 10 Mo).</small>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-success px-4 py-2 shadow-sm"><i class="fas fa-upload mr-1"></i> Lancer l'importation</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Hidden configuration field to persist integration value -->
                    <form id="persistManualForm" class="lw-ajax-form lw-form d-none" method="post" action="<?= route('vendor.settings.write.update', ['pageType' => 'internals']) ?>">
                        <input type="hidden" name="pageType" value="internals">
                        <input type="hidden" name="ecommerce_integration" value="manual">
                    </form>
                    <div class="mt-4">
                        <button type="button" @click="document.getElementById('persistManualForm').querySelector('button[type=submit] || input[type=submit]').click() || __DataRequest.post('{{ route('vendor.settings.write.update', ['pageType' => 'internals']) }}', {ecommerce_integration: 'manual', pageType: 'internals'}, function(response) { if(response.reaction_code==1){ showSuccessMessage('Mode Manuel sauvegardé.'); } });" class="btn btn-primary btn-lg px-5 shadow-sm">
                            <i class="fas fa-save mr-2"></i> {{ __tr('Activer le mode Manuel') }}
                        </button>
                    </div>
                </div>

                <div x-show="syncMessage" class="alert mt-3" style="background-color: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; color: #000;" x-text="syncMessage" x-cloak></div>
            </div>
        </div>

        <!-- Unified Product Catalog Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-boxes mr-1"></i> {{ __tr('Tous les produits du catalogue') }}
                </h6>
            </div>
            <div class="card-body">
                <!-- Filters & Search -->
                <div class="row mb-4">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small mb-1">{{ __tr('Rechercher un produit') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="{{ __tr('Nom ou description...') }}" x-model="catalogSearch">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small mb-1">{{ __tr('Filtrer par source') }}</label>
                        <select class="form-control" x-model="catalogSourceFilter">
                            <option value="">{{ __tr('Toutes les sources') }}</option>
                            <option value="manual">{{ __tr('Catalogue Manuel') }}</option>
                            <option value="shopify">Shopify</option>
                            <option value="woocommerce">WooCommerce</option>
                            <option value="whatsapp_catalog">{{ __tr('WhatsApp Catalog') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-items-center">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ __tr('Image') }}</th>
                                <th>{{ __tr('Nom') }}</th>
                                <th>{{ __tr('Source') }}</th>
                                <th>{{ __tr('Prix') }}</th>
                                <th>{{ __tr('Description') }}</th>
                                <th>{{ __tr('Lien Direct') }}</th>
                                <th class="text-right">{{ __tr('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in filteredCatalogProducts()" :key="product._uid">
                                <tr>
                                    <td>
                                        <template x-if="product.image_url">
                                            <img :src="product.image_url" class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                        </template>
                                        <template x-if="!product.image_url">
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; color: #adb5bd;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="font-weight-bold text-dark" x-text="product.name"></td>
                                    <td>
                                        <span class="badge border text-capitalize px-3 py-1 font-weight-bold" 
                                              :class="{
                                                  'badge-success border-success text-white': product.source === 'whatsapp_catalog',
                                                  'badge-primary border-primary text-white': product.source === 'woocommerce',
                                                  'badge-info border-info text-white': product.source === 'shopify',
                                                  'badge-secondary border-secondary text-white': product.source === 'manual'
                                              }"
                                              x-text="product.source === 'whatsapp_catalog' ? 'WhatsApp' : product.source">
                                        </span>
                                    </td>
                                    <td class="text-success font-weight-bold" x-text="Number(product.price).toLocaleString() + ' CFA'"></td>
                                    <td class="text-muted text-xs" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="product.description || 'NA'"></td>
                                    <td>
                                        <template x-if="product.direct_link">
                                            <a :href="product.direct_link" target="_blank" class="badge badge-primary px-3 py-2 text-white shadow-inner" style="border-radius: 6px;"><i class="fas fa-external-link-alt mr-1"></i> {{ __tr('Ouvrir') }}</a>
                                        </template>
                                        <template x-if="!product.direct_link">
                                            <span class="text-muted text-xs">-</span>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" @click="deleteProduct(product._uid)" class="btn btn-sm btn-danger shadow-sm" title="{{ __tr('Supprimer') }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="filteredCatalogProducts().length === 0" class="text-center py-4 text-muted" x-cloak>
                        {{ __tr('Aucun produit correspondant trouvé.') }}
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-danger">
            {{ __tr('E-commerce & Catalogue feature is not available in your subscription plan. Please upgrade your plan to access this feature.') }}
        </div>
        @endif
    </div>
</div>
