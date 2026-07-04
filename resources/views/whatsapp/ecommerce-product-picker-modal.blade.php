<x-lw.modal id="lwECommerceProductPicker" :header="__tr('Produits & Catalogues')" :hasForm="false">
    <div class="p-3" x-data="{
        products: [],
        search: '',
        source: '',
        page: 1,
        lastPage: 1,
        isLoading: false,
        selectedProductUid: null,
        isSending: false,
        fetchProducts() {
            this.isLoading = true;
            var self = this;
            __DataRequest.get('{{ route('vendor.ecommerce.products') }}', {
                search: this.search,
                source: this.source,
                page: this.page
            }, function(response) {
                self.isLoading = false;
                if (response.reaction == 1 || response.reaction_code == 1) {
                    var productsData = response.data.products.data ? response.data.products.data : response.data.products;
                    self.products = productsData || [];
                    self.lastPage = response.data.products.last_page || 1;
                    if (self.products.length > 0 && !self.selectedProductUid) {
                        self.selectedProductUid = self.products[0]._uid;
                    }
                } else {
                    console.error('Products fetch failed:', response);
                }
            });
        },
        searchProducts() {
            this.page = 1;
            this.fetchProducts();
        },
        nextPage() {
            if (this.page < this.lastPage) {
                this.page++;
                this.fetchProducts();
            }
        },
        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.fetchProducts();
            }
        },
        sendSelectedProduct() {
            if (!this.selectedProductUid) {
                showErrorMessage('{{ __tr('Please select a product first.') }}');
                return;
            }
            this.isSending = true;
            var self = this;
            
            // Get current active contact UID from Alpine context
            var contactUid = null;
            var chatData = document.querySelector('[x-data=\'initialMessageData\']');
            if (chatData) {
                var alpineData = Alpine.$data(chatData) || chatData.__x?.$data;
                if (alpineData && alpineData.contact) {
                    contactUid = alpineData.contact._uid;
                }
            }
            
            if (!contactUid) {
                showErrorMessage('{{ __tr('No active chat selected.') }}');
                this.isSending = false;
                return;
            }

            __DataRequest.post('{{ route('vendor.ecommerce.send_product') }}', {
                contact_uid: contactUid,
                product_uid: this.selectedProductUid
            }, function(response) {
                self.isSending = false;
                if (response.reaction == 1 || response.reaction_code == 1) {
                    showSuccessMessage('{{ __tr('Product sent successfully!') }}');
                    $('#ecommerceProductPickerModal').modal('hide');
                } else {
                    showErrorMessage(response.message || '{{ __tr('Failed to send product.') }}');
                }
            });
        }
    }" x-init="fetchProducts()">
        
        <div class="alert alert-warning py-2 mb-3 text-center small">
            <i class="fas fa-info-circle mr-1"></i> {{ __tr('Send interactive product cards with direct store checkout links.') }}
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <select class="form-control form-control-sm" x-model="source" @change="searchProducts()" style="border-radius: 8px;">
                    <option value="">{{ __tr('Toutes les sources de produits') }}</option>
                    <option value="manual">{{ __tr('Catalogue Manuel') }}</option>
                    <option value="shopify">Shopify</option>
                    <option value="woocommerce">WooCommerce</option>
                    <option value="whatsapp_catalog">WhatsApp Catalog</option>
                </select>
            </div>
        </div>

        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="{{ __tr('Type to filter products...') }}" x-model="search" @input.debounce.300ms="searchProducts()">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" @click="searchProducts()"><i class="fa fa-search"></i></button>
            </div>
        </div>

        <div x-show="isLoading" class="text-center my-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <div x-show="!isLoading && products.length === 0" class="text-center text-muted my-4">
            {{ __tr('No products found.') }}
        </div>

        <div class="list-group mb-3" style="max-height: 350px; overflow-y: auto;">
            <template x-for="product in products" :key="product._uid">
                <label class="list-group-item list-group-item-action d-flex align-items-center mb-0 py-2" style="cursor: pointer;">
                    <input type="radio" name="selected_product" :value="product._uid" x-model="selectedProductUid" class="mr-3">
                    <img :src="product.image_url || 'https://via.placeholder.com/50'" class="img-thumbnail mr-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                    <div class="flex-grow-1">
                        <span class="d-block font-weight-500 text-dark" x-text="product.name"></span>
                        <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                            <span class="badge badge-success small" x-text="product.price + ' CFA'"></span>
                            <span class="badge badge-light border text-capitalize text-xs text-muted" x-text="product.source === 'whatsapp_catalog' ? 'WhatsApp' : product.source"></span>
                        </div>
                    </div>
                </label>
            </template>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mb-3" x-show="lastPage > 1">
            <button class="btn btn-sm btn-outline-secondary" :disabled="page === 1" @click="prevPage()">{{ __tr('Previous') }}</button>
            <span class="text-muted small" x-text="page + ' / ' + lastPage"></span>
            <button class="btn btn-sm btn-outline-secondary" :disabled="page === lastPage" @click="nextPage()">{{ __tr('Next') }}</button>
        </div>

        <hr>

        <div class="d-flex justify-content-end" style="gap: 8px;">
            <button type="button" class="btn btn-success" @click="sendSelectedProduct()" :disabled="isSending || !selectedProductUid">
                <span x-show="!isSending"><i class="fa fa-paper-plane mr-1"></i> {{ __tr('Send') }}</span>
                <span x-show="isSending"><i class="fa fa-spinner fa-spin mr-1"></i> {{ __tr('Sending...') }}</span>
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </div>
</x-lw.modal>
