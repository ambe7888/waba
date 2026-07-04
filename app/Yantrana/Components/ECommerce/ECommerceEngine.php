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
    public function syncProducts($vendorId = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }

        $integration = getVendorSettings('ecommerce_integration', null, null, $vendorId);
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
        $accessToken = getVendorSettings('shopify_access_token', null, null, $vendorId);

        if (empty($shopUrl) || empty($accessToken)) {
            return [
                'reaction_code' => 2,
                'message' => __tr('Shopify URL or Access Token is missing.')
            ];
        }

        // Clean shop URL
        $shopUrl = preg_replace('/^https?:\/\//', '', $shopUrl);
        $shopUrl = rtrim($shopUrl, '/');

        $url = "https://{$shopUrl}/admin/api/2023-04/products.json";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get($url);

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
}
