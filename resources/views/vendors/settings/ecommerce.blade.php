@php
$vendorId = getVendorId();
$vendorPlanDetails = vendorPlanDetails('ecommerce_catalog', 1, $vendorId);
@endphp

<div class="row" x-data="{
    integration: '{{ getVendorSettings('ecommerce_integration') ?: 'none' }}',
    isSyncing: false,
    syncMessage: '',
    syncProducts() {
        this.isSyncing = true;
        this.syncMessage = '';
        var self = this;
        __DataRequest.post('{{ route('vendor.ecommerce.sync') }}', {}, function(response) {
            self.isSyncing = false;
            if (response.reaction_code == 1) {
                self.syncMessage = response.message;
                showSuccessMessage(response.message);
            } else {
                self.syncMessage = response.message || '{{ __tr('Failed to synchronize products.') }}';
                showErrorMessage(self.syncMessage);
            }
        });
    }
}">
    <div class="col-md-12">
        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">
            <?= __tr('E-commerce & Catalogue') ?>
        </h1>
        <p class="mb-4">
            {{ __tr('Connect your online store (Shopify or WooCommerce) to display and recommend products in chats, sync with WhatsApp catalogs, and automate customer order tracking.') }}
        </p>

        @if ($vendorPlanDetails['is_limit_available'])
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __tr('Platform Settings') }}</h6>
            </div>
            <div class="card-body">
                <form class="lw-ajax-form lw-form" method="post" action="<?= route('vendor.settings.write.update') ?>">
                    
                    <div class="form-group">
                        <label for="ecommerce_integration">{{ __tr('Select Platform') }}</label>
                        <select class="form-control" name="ecommerce_integration" id="ecommerce_integration" x-model="integration">
                            <option value="none">{{ __tr('None / Disabled') }}</option>
                            <option value="shopify">{{ __tr('Shopify') }}</option>
                            <option value="woocommerce">{{ __tr('WooCommerce') }}</option>
                        </select>
                    </div>

                    <!-- Shopify Fields -->
                    <div x-show="integration === 'shopify'" class="p-4 border rounded bg-light mb-4" x-cloak>
                        <h5 class="text-primary mb-3"><i class="fab fa-shopify"></i> Shopify</h5>
                        <div class="form-group">
                            <label for="shopify_shop_url">{{ __tr('Shopify Shop URL') }}</label>
                            <input type="text" class="form-control" id="shopify_shop_url" value="{{ getVendorSettings('shopify_shop_url') }}" name="shopify_shop_url" placeholder="e.g. mystore.myshopify.com">
                        </div>
                    </div>

                    <!-- WooCommerce Fields -->
                    <div x-show="integration === 'woocommerce'" class="p-4 border rounded bg-light mb-4" x-cloak>
                        <h5 class="text-primary mb-3"><i class="fab fa-wordpress"></i> WooCommerce</h5>
                        <div class="form-group">
                            <label for="woocommerce_shop_url">{{ __tr('WooCommerce Shop URL') }}</label>
                            <input type="text" class="form-control" id="woocommerce_shop_url" value="{{ getVendorSettings('woocommerce_shop_url') }}" name="woocommerce_shop_url" placeholder="e.g. https://mywordpressstore.com">
                        </div>
                        <div class="form-group">
                            <label for="woocommerce_consumer_key">{{ __tr('WooCommerce Consumer Key') }}</label>
                            <input type="text" class="form-control" id="woocommerce_consumer_key" value="{{ getVendorSettings('woocommerce_consumer_key') }}" name="woocommerce_consumer_key" placeholder="ck_...">
                        </div>
                        <div class="form-group">
                            <label for="woocommerce_consumer_secret">{{ __tr('WooCommerce Consumer Secret') }}</label>
                            <input type="password" class="form-control" id="woocommerce_consumer_secret" value="{{ getVendorSettings('woocommerce_consumer_secret') }}" name="woocommerce_consumer_secret" placeholder="cs_...">
                        </div>
                    </div>

                    <!-- Meta/WhatsApp Catalog ID -->
                    <div x-show="integration !== 'none'" class="p-4 border rounded bg-light mb-4" x-cloak>
                        <h5 class="text-primary mb-3"><i class="fab fa-whatsapp"></i> {{ __tr('WhatsApp Native Catalog') }}</h5>
                        <div class="form-group">
                            <label for="whatsapp_catalog_id">{{ __tr('WhatsApp Catalog ID (Optional)') }}</label>
                            <input type="text" class="form-control" id="whatsapp_catalog_id" value="{{ getVendorSettings('whatsapp_catalog_id') }}" name="whatsapp_catalog_id" placeholder="e.g. 128392193892182">
                            <small class="form-text text-muted">{{ __tr('Associate your WhatsApp Business Catalog ID to support native interactive product messages.') }}</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">{{ __tr('Save Settings') }}</button>
                        
                        <span x-show="integration !== 'none'" x-cloak>
                            <button type="button" @click="syncProducts()" class="btn btn-success ml-2" :disabled="isSyncing">
                                <span x-show="!isSyncing"><i class="fas fa-sync"></i> {{ __tr('Sync Products Now') }}</span>
                                <span x-show="isSyncing"><i class="fas fa-spinner fa-spin"></i> {{ __tr('Synchronizing...') }}</span>
                            </button>
                        </span>
                    </div>
                </form>

                <div x-show="syncMessage" class="alert alert-info mt-3" x-text="syncMessage" x-cloak></div>
            </div>
        </div>
        @else
        <div class="alert alert-danger">
            {{ __tr('E-commerce & Catalogue feature is not available in your subscription plan. Please upgrade your plan to access this feature.') }}
        </div>
        @endif
    </div>
</div>
