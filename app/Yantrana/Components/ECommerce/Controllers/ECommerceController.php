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
    public function syncProducts(Request $request)
    {
        $processReaction = $this->ecommerceEngine->syncProducts(null, $request->source);
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

        if ($request->filled('source')) {
            $query->where('source', $request->source);
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
        $description = $product->description ? strip_tags(html_entity_decode($product->description)) : '';
        $sendRequest = [
            'messageBody' => "*{$product->name}*\n" . $description . "\nPrix: " . number_format($product->price, 0, ',', ' ') . " CFA",
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

    /**
     * Add Local Product Manually
     */
    public function addProduct(Request $request)
    {
        $vendorId = getVendorId();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'direct_link' => 'nullable|url|max:1000',
            'image_url' => 'nullable|url|max:1000',
            'image_file' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $imageUrl = $request->image_url;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $targetDir = public_path('media/products');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $filename = uniqid('prod_') . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $filename);
            $imageUrl = asset('media/products/' . $filename);
        }

        ProductModel::create([
            '_uid' => \Str::uuid()->toString(),
            'vendors__id' => $vendorId,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image_url' => $imageUrl,
            'direct_link' => $request->direct_link,
            'source' => 'manual',
        ]);

        return $this->processResponse(1, [], [
            'message' => __tr('Produit ajouté avec succès.')
        ]);
    }

    /**
     * Delete Local Product
     */
    public function deleteProduct($productUid)
    {
        $vendorId = getVendorId();

        $product = ProductModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $productUid
        ])->first();

        if (empty($product)) {
            return $this->processResponse(2, [], [
                'message' => __tr('Produit introuvable.')
            ]);
        }

        $product->delete();

        return $this->processResponse(1, [], [
            'message' => __tr('Produit supprimé avec succès.')
        ]);
    }

    /**
     * Import Products from CSV
     */
    public function importProducts(Request $request)
    {
        $vendorId = getVendorId();
        
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $importedCount = 0;

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            // Get header row
            $headers = fgetcsv($handle, 1000, ',');
            if ($headers !== false) {
                // Trim header columns
                $headers = array_map('trim', $headers);
                // Match header column index
                $nameIdx = array_search('name', $headers);
                $descIdx = array_search('description', $headers);
                $priceIdx = array_search('price', $headers);
                $imageIdx = array_search('image_url', $headers);
                $linkIdx = array_search('direct_link', $headers);

                // If simple headers are not found, let's try French ones
                if ($nameIdx === false) $nameIdx = array_search('nom', $headers);
                if ($descIdx === false) $descIdx = array_search('description', $headers);
                if ($priceIdx === false) $priceIdx = array_search('prix', $headers);
                if ($imageIdx === false) $imageIdx = array_search('image_url', $headers);
                if ($linkIdx === false) $linkIdx = array_search('lien', $headers);

                // Fallback to indices if nothing matches
                if ($nameIdx === false) $nameIdx = 0;
                if ($descIdx === false) $descIdx = 1;
                if ($priceIdx === false) $priceIdx = 2;
                if ($imageIdx === false) $imageIdx = 3;
                if ($linkIdx === false) $linkIdx = 4;

                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    $name = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';
                    if (empty($name)) continue;

                    $description = isset($row[$descIdx]) ? trim($row[$descIdx]) : '';
                    $price = isset($row[$priceIdx]) ? floatval(trim($row[$priceIdx])) : 0.00;
                    $imageUrl = isset($row[$imageIdx]) ? trim($row[$imageIdx]) : '';
                    $directLink = isset($row[$linkIdx]) ? trim($row[$linkIdx]) : '';

                    ProductModel::create([
                        '_uid' => \Str::uuid()->toString(),
                        'vendors__id' => $vendorId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'image_url' => $imageUrl,
                        'direct_link' => $directLink,
                        'source' => 'manual',
                    ]);
                    $importedCount++;
                }
            }
            fclose($handle);
        }

        return $this->processResponse(1, [], [
            'message' => __tr('Importation réussie de __count__ produits.', ['__count__' => $importedCount])
        ]);
    }

    /**
     * Get Catalogs associated with WABA from Meta
     */
    public function getMetaCatalogs()
    {
        $vendorId = getVendorId();
        $wabaId = getVendorSettings('whatsapp_business_account_id', null, null, $vendorId);
        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);

        if (empty($wabaId) || empty($accessToken)) {
            return $this->processResponse(2, [], [
                'message' => __tr('WhatsApp Business Account must be connected first.')
            ]);
        }

        // Decrypt values if they are encrypted
        try {
            $wabaId = decrypt($wabaId);
        } catch (\Exception $e) {}

        try {
            $accessToken = decrypt($accessToken);
        } catch (\Exception $e) {}

        $catalogsList = [];

        // 1. Try to get catalogs via WABA owning business
        try {
            $wabaResponse = \Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v25.0/{$wabaId}", [
                    'fields' => 'owning_business'
                ]);

            if ($wabaResponse->successful()) {
                $businessId = $wabaResponse->json()['owning_business']['id'] ?? null;
                if ($businessId) {
                    $catalogsResponse = \Http::withToken($accessToken)
                        ->get("https://graph.facebook.com/v25.0/{$businessId}/owned_product_catalogs");
                    if ($catalogsResponse->successful()) {
                        $catalogsData = $catalogsResponse->json()['data'] ?? [];
                        foreach ($catalogsData as $cat) {
                            $catalogsList[] = [
                                'id' => $cat['id'],
                                'name' => $cat['name'] ?? ('Catalog #' . $cat['id'])
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        // 2. If empty, fallback to WABA commerce settings (currently linked catalog)
        if (empty($catalogsList)) {
            try {
                $commResponse = \Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/v25.0/{$wabaId}/commerce_settings");

                if ($commResponse->successful()) {
                    $commData = $commResponse->json()['data'] ?? [];
                    foreach ($commData as $comm) {
                        if (isset($comm['catalog_id'])) {
                            $catalogsList[] = [
                                'id' => $comm['catalog_id'],
                                'name' => __tr('Linked Catalog')
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        // Remove duplicates
        $catalogsList = array_values(array_unique($catalogsList, SORT_REGULAR));

        if (!empty($catalogsList)) {
            return $this->processResponse(1, [], [
                'catalogs' => $catalogsList
            ]);
        }

        return $this->processResponse(2, [], [
            'message' => __tr('No catalogs found. Please link a catalog to your WhatsApp number in your Meta Business Suite.')
        ]);
    }
}
