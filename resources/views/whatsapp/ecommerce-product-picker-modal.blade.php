<x-lw.modal id="lwECommerceProductPicker" :header="__tr('Send Product')" :hasForm="false">
    <div class="p-3" x-data="{
        products: [],
        search: '',
        page: 1,
        lastPage: 1,
        isLoading: false,
        fetchProducts() {
            this.isLoading = true;
            var self = this;
            __DataRequest.get('{{ route('vendor.ecommerce.products') }}', {
                search: this.search,
                page: this.page
            }, function(response) {
                self.isLoading = false;
                if (response.reaction_code == 1) {
                    self.products = response.data.products.data;
                    self.lastPage = response.data.products.last_page;
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
        insertProduct(product) {
            var text = '*' + product.name + '*\n' + (product.description || '') + '\n\n' + 'Price: ' + product.price + ' CFA\n' + 'Link: ' + (product.direct_link || '');
            if (window.lwMessengerEmojiArea && window.lwMessengerEmojiArea[0] && window.lwMessengerEmojiArea[0].emojioneArea) {
                window.lwMessengerEmojiArea[0].emojioneArea.setText(text);
            } else {
                $('#lwChatWindowMessageBody').val(text);
            }
            $('#lwECommerceProductPicker').modal('hide');
        },
        sendProduct(product) {
            this.insertProduct(product);
            _.defer(function() {
                $('#whatsAppMessengerForm').submit();
            });
        }
    }" x-init="fetchProducts()">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="{{ __tr('Search products...') }}" x-model="search" @keyup.enter="searchProducts()">
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

        <div class="list-group mb-3" style="max-height: 400px; overflow-y: auto;">
            <template x-for="product in products" :key="product._id">
                <div class="list-group-item list-group-item-action d-flex align-items-center">
                    <img :src="product.image_url || 'https://via.placeholder.com/60'" class="img-thumbnail mr-3" style="width: 60px; height: 60px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 font-weight-bold" x-text="product.name"></h6>
                        <small class="text-muted d-block" x-text="product.description ? product.description.substring(0, 80) + '...' : ''"></small>
                        <span class="badge badge-success mt-1" x-text="product.price + ' CFA'"></span>
                    </div>
                    <div class="btn-group-vertical btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" @click="insertProduct(product)">
                            <i class="fa fa-plus"></i> {{ __tr('Insert') }}
                        </button>
                        <button type="button" class="btn btn-primary" @click="sendProduct(product)">
                            <i class="fa fa-paper-plane"></i> {{ __tr('Send') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center" x-show="lastPage > 1">
            <button class="btn btn-sm btn-outline-secondary" :disabled="page === 1" @click="prevPage()">{{ __tr('Previous') }}</button>
            <span class="text-muted" x-text="page + ' / ' + lastPage"></span>
            <button class="btn btn-sm btn-outline-secondary" :disabled="page === lastPage" @click="nextPage()">{{ __tr('Next') }}</button>
        </div>
    </div>
</x-lw.modal>
