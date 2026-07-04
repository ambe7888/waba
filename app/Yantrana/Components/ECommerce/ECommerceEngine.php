<?php
namespace App\Yantrana\Components\ECommerce;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\ECommerce\Models\ProductModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ECommerceEngine extends BaseEngine
{
    /**
     * Synchronize products from Shopify or WooCommerce
     *
     * @param int|null $vendorId
     * @return array
     */
    public function syncProducts($vendorId = null, $source = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }

        $integration = $source ?: getVendorSettings('ecommerce_integration', null, null, $vendorId);
        if (!$integration || $integration == 'none') {
            return [
                'reaction_code' => 2,
                'message' => __tr('No e-commerce integration configured.')
            ];
        }

        try {
            if ($integration == 'shopify') {
                return $this->syncShopify($vendorId);
            } elseif ($integration == 'woocommerce') {
                return $this->syncWooCommerce($vendorId);
            } elseif ($integration == 'whatsapp_catalog') {
                return $this->syncWhatsAppCatalog($vendorId);
            }
        } catch (\Exception $e) {
            return [
                'reaction_code' => 2,
                'message' => __tr('Sync failed: ') . $e->getMessage()
            ];
        }

        return [
            'reaction_code' => 2,
            'message' => __tr('Unknown integration type.')
        ];
    }

    /**
     * Sync Shopify Products
     */
    protected function syncShopify($vendorId)
    {
        $shopUrl = getVendorSettings('shopify_shop_url', null, null, $vendorId);

        if (empty($shopUrl)) {
            return [
                'reaction_code' => 2,
                'message' => __tr('Shopify URL is missing.')
            ];
        }

        // Clean shop URL
        $shopUrl = preg_replace('/^https?:\/\//', '', $shopUrl);
        $shopUrl = rtrim($shopUrl, '/');

        $url = "https://{$shopUrl}/products.json";

        $response = Http::get($url);

        if (!$response->successful()) {
            return [
                'reaction_code' => 2,
                'message' => __tr('Shopify API error: ') . $response->body()
            ];
        }

        $data = $response->json();
        $products = $data['products'] ?? [];

        $syncedCount = 0;
        foreach ($products as $product) {
            $retailerId = (string) $product['id'];
            $name = $product['title'];
            $description = strip_tags($product['body_html'] ?? '');
            
            // Get price of first variant
            $price = 0.00;
            if (!empty($product['variants'])) {
                $price = (float) $product['variants'][0]['price'];
            }

            // Get first image
            $imageUrl = null;
            if (!empty($product['images'])) {
                $imageUrl = $product['images'][0]['src'];
            }

            $directLink = "https://{$shopUrl}/products/" . ($product['handle'] ?? '');

            ProductModel::updateOrCreate([
                'vendors__id' => $vendorId,
                'retailer_id' => $retailerId,
                'source' => 'shopify',
            ], [
                '_uid' => (string) Str::uuid(),
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $imageUrl,
                'direct_link' => $directLink,
            ]);

            $syncedCount++;
        }

        return [
            'reaction_code' => 1,
            'message' => __tr('Successfully synced __count__ products from Shopify.', ['__count__' => $syncedCount])
        ];
    }

    /**
     * Sync WooCommerce Products
     */
    protected function syncWooCommerce($vendorId)
    {
        $shopUrl = getVendorSettings('woocommerce_shop_url', null, null, $vendorId);
        $consumerKey = getVendorSettings('woocommerce_consumer_key', null, null, $vendorId);
        $consumerSecret = getVendorSettings('woocommerce_consumer_secret', null, null, $vendorId);

        if (empty($shopUrl) || empty($consumerKey) || empty($consumerSecret)) {
            return [
                'reaction_code' => 2,
                'message' => __tr('WooCommerce URL, Consumer Key, or Consumer Secret is missing.')
            ];
        }

        $shopUrl = rtrim($shopUrl, '/');

        $url = "{$shopUrl}/wp-json/wc/v3/products";

        $response = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->get($url, [
                'per_page' => 100
            ]);

        if (!$response->successful()) {
            return [
                'reaction_code' => 2,
                'message' => __tr('WooCommerce API error: ') . $response->body()
            ];
        }

        $products = $response->json();

        $syncedCount = 0;
        foreach ($products as $product) {
            $retailerId = (string) $product['id'];
            $name = $product['name'];
            $description = strip_tags($product['description'] ?? '');
            $price = (float) ($product['price'] ?? 0.00);

            // Get first image
            $imageUrl = null;
            if (!empty($product['images'])) {
                $imageUrl = $product['images'][0]['src'];
            }

            $directLink = $product['permalink'] ?? null;

            ProductModel::updateOrCreate([
                'vendors__id' => $vendorId,
                'retailer_id' => $retailerId,
                'source' => 'woocommerce',
            ], [
                '_uid' => (string) Str::uuid(),
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $imageUrl,
                'direct_link' => $directLink,
            ]);

            $syncedCount++;
        }

        return [
            'reaction_code' => 1,
            'message' => __tr('Successfully synced __count__ products from WooCommerce.', ['__count__' => $syncedCount])
        ];
    }

    /**
     * Synchronize products from WhatsApp Catalog (Meta)
     *
     * @param int $vendorId
     * @return array
     */
    protected function syncWhatsAppCatalog($vendorId)
    {
        $catalogId = getVendorSettings('whatsapp_catalog_id', null, null, $vendorId);
        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);

        if (!$catalogId || !$accessToken) {
            return [
                'reaction_code' => 2,
                'message' => __tr('WhatsApp Catalog ID or Access Token is missing.')
            ];
        }

        try {
            $accessToken = decrypt($accessToken);
        } catch (\Exception $e) {}

        $url = "https://graph.facebook.com/v20.0/{$catalogId}/products?fields=id,name,description,price,image_url,url";
        $syncedCount = 0;

        do {
            $response = \Http::withToken($accessToken)->get($url);

            if (!$response->successful()) {
                \Log::error('Meta Catalog Sync failed: ' . $response->body());
                if ($syncedCount == 0) {
                    return [
                        'reaction_code' => 2,
                        'message' => __tr('Failed to fetch catalog from Meta.')
                    ];
                }
                break;
            }

            $responseData = $response->json();
            $productsData = $responseData['data'] ?? [];

            foreach ($productsData as $product) {
                $retailerId = $product['id'];
                $name = $product['name'] ?? 'Product ' . $retailerId;
                $description = $product['description'] ?? '';
                // price comes as a string like "100.00 CFA" or "100.00"
                $priceStr = $product['price'] ?? '0';
                $price = floatval(preg_replace('/[^0-9.]/', '', $priceStr));
                $imageUrl = $product['image_url'] ?? null;
                $directLink = $product['url'] ?? null;

                ProductModel::updateOrCreate([
                    'vendors__id' => $vendorId,
                    'retailer_id' => $retailerId,
                    'source' => 'whatsapp_catalog',
                ], [
                    '_uid' => (string) \Str::uuid(),
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'image_url' => $imageUrl,
                    'direct_link' => $directLink,
                ]);

                $syncedCount++;
            }

            $url = $responseData['paging']['next'] ?? null;
        } while ($url);

        return [
            'reaction_code' => 1,
            'message' => __tr('Successfully synced __count__ products from WhatsApp Catalog.', ['__count__' => $syncedCount])
        ];
    }
}
