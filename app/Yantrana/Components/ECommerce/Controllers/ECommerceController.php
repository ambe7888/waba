<?php
namespace App\Yantrana\Components\ECommerce\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\ECommerce\ECommerceEngine;
use App\Yantrana\Components\ECommerce\Models\ProductModel;
use Illuminate\Http\Request;

class ECommerceController extends BaseController
{
    /**
     * @var ECommerceEngine - ECommerce Engine
     */
    protected $ecommerceEngine;

    /**
     * Constructor
     */
    public function __construct(ECommerceEngine $ecommerceEngine)
    {
        $this->ecommerceEngine = $ecommerceEngine;
    }

    /**
     * Synchronize WooCommerce / Shopify Products
     */
    public function syncProducts()
    {
        $processReaction = $this->ecommerceEngine->syncProducts();
        return $this->processResponse($processReaction);
    }

    /**
     * Get Products list for live chat or selection
     */
    public function getProducts(Request $request)
    {
        $vendorId = getVendorId();
        
        $query = ProductModel::where('vendors__id', $vendorId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }

        $products = $query->latest()->paginate(15);

        return $this->processResponse(1, [], [
            'products' => $products
        ]);
    }

    /**
     * Send Product Message
     */
    public function sendProductMessage(Request $request)
    {
        $vendorId = getVendorId();
        
        $request->validate([
            'contact_uid' => 'required|string',
            'product_uid' => 'required|string',
        ]);

        $product = ProductModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $request->product_uid
        ])->first();

        if (empty($product)) {
            return $this->processResponse(2, [], [
                'message' => __tr('Product not found.')
            ]);
        }

        // Construct interactive message data
        $interactionMessageData = [
            'interactive_type' => 'cta_url',
            'header_type' => $product->image_url ? 'image' : 'text',
            'media_link' => $product->image_url ?: '',
            'header_text' => $product->name,
            'body_text' => $product->description ?: $product->name,
            'cta_url' => [
                'display_text' => __tr('Product Details'),
                'url' => $product->direct_link ?: ''
            ]
        ];

        // Ask WhatsAppServiceEngine to send this chat message
        $whatsAppServiceEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);

        // We simulate a request with message_body for the preview and log
        $sendRequest = [
            'messageBody' => "*{$product->name}*\n" . ($product->description ?: '') . "\nPrice: " . number_format($product->price) . " CFA",
            'contactUid' => $request->contact_uid,
        ];

        $processReaction = $whatsAppServiceEngine->processSendChatMessage(
            $sendRequest,
            false,
            $vendorId,
            ['interaction_message_data' => $interactionMessageData]
        );

        return $this->processResponse($processReaction);
    }
}
