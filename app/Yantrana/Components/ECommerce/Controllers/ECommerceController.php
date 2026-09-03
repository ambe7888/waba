<?php
namespace App\Yantrana\Components\ECommerce\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\ECommerce\ECommerceEngine;
use App\Yantrana\Components\ECommerce\Models\ProductModel;
use App\Yantrana\Components\ECommerce\Models\ProductCategoryModel;
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

        $query = ProductModel::with('category')->where('vendors__id', $vendorId);

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

        if ($request->filled('category_uid')) {
            if ($request->category_uid === 'uncategorized') {
                $query->whereNull('product_categories__id');
            } else {
                $category = ProductCategoryModel::where([
                    'vendors__id' => $vendorId,
                    '_uid' => $request->category_uid,
                ])->first();
                $query->where('product_categories__id', $category->_id ?? 0);
            }
        }

        $products = $query->latest()->get();

        return $this->processResponse(1, [], [
            'products' => $products
        ]);
    }

    /**
     * List product categories for the vendor
     */
    public function getCategories()
    {
        $vendorId = getVendorId();

        $categories = ProductCategoryModel::where('vendors__id', $vendorId)
            ->withCount(['products' => function ($q) use ($vendorId) {
                $q->where('vendors__id', $vendorId);
            }])
            ->orderBy('name')
            ->get();

        return $this->processResponse(1, [], [
            'categories' => $categories
        ]);
    }

    /**
     * Create a product category
     */
    public function addCategory(Request $request)
    {
        $vendorId = getVendorId();

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($request->name);

        $existing = ProductCategoryModel::where([
            'vendors__id' => $vendorId,
        ])->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            return $this->processResponse(2, [], [
                'message' => __tr('Cette catégorie existe déjà.')
            ]);
        }

        $category = ProductCategoryModel::create([
            '_uid' => \Str::uuid()->toString(),
            'vendors__id' => $vendorId,
            'name' => $name,
        ]);

        return $this->processResponse(1, [], [
            'message' => __tr('Catégorie ajoutée avec succès.'),
            'category' => $category,
        ]);
    }

    /**
     * Delete a product category. Products in it become uncategorized rather
     * than being deleted along with it.
     */
    public function deleteCategory($categoryUid)
    {
        $vendorId = getVendorId();

        $category = ProductCategoryModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $categoryUid,
        ])->first();

        if (empty($category)) {
            return $this->processResponse(2, [], [
                'message' => __tr('Catégorie introuvable.')
            ]);
        }

        ProductModel::where([
            'vendors__id' => $vendorId,
            'product_categories__id' => $category->_id,
        ])->update(['product_categories__id' => null]);

        $category->delete();

        return $this->processResponse(1, [], [
            'message' => __tr('Catégorie supprimée avec succès.')
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

        $description = $product->description ? strip_tags(html_entity_decode($product->description)) : '';
        // Truncate description to max 200 characters to keep the message readable
        if (mb_strlen($description) > 200) {
            $description = mb_substr($description, 0, 200) . '...';
        }
        $messageBody = "*{$product->name}*\n\n" . $description . "\n\n💰 *Prix:* " . number_format($product->price, 0, ',', ' ') . " CFA";

        // Construct interactive message data (cta_url)
        $interactionMessageData = [
            'interactive_type' => 'cta_url',
            'header_type' => (!empty($product->image_url) && isValidUrl($product->image_url)) ? 'image' : 'text',
            'media_link' => $product->image_url ?: '',
            'header_text' => mb_substr($product->name, 0, 60), // Meta limit is 60 chars
            'body_text' => ($description ?: $product->name) . "\n\n" . __tr('Prix:') . " " . number_format($product->price, 0, ',', ' ') . " CFA",
            'cta_url' => [
                'display_text' => mb_substr(__tr('Détails du produit'), 0, 20), // Meta limit is 20 chars
                'url' => $product->direct_link ?: ''
            ]
        ];

        $sendRequest = [
            'messageBody' => $messageBody,
            'contactUid' => $request->contact_uid,
        ];

        // Get the vendor's real phone number ID from whatsapp_phone_numbers (already decrypted as JSON array)
        $phoneNumbers = getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: [];
        $currentPhoneNumberId = !empty($phoneNumbers) ? ($phoneNumbers[0]['id'] ?? null) : null;

        // Set the phone number for this request so the API service uses the correct one
        fromPhoneNumberIdForRequest($currentPhoneNumberId);

        // Ask WhatsAppServiceEngine to send this chat message
        $whatsAppServiceEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);

        $options = [
            'from_phone_number_id' => $currentPhoneNumberId,
            'interaction_message_data' => $interactionMessageData,
        ];

        $processReaction = $whatsAppServiceEngine->processSendChatMessage(
            $sendRequest,
            false, // Not a standard media message
            $vendorId,
            $options
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
            'category_uid' => 'nullable|string',
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

        $categoryId = null;
        if ($request->filled('category_uid')) {
            $category = ProductCategoryModel::where([
                'vendors__id' => $vendorId,
                '_uid' => $request->category_uid,
            ])->first();
            $categoryId = $category->_id ?? null;
        }

        ProductModel::create([
            '_uid' => \Str::uuid()->toString(),
            'vendors__id' => $vendorId,
            'product_categories__id' => $categoryId,
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
            'category_uid' => 'nullable|string',
        ]);

        $categoryId = null;
        if ($request->filled('category_uid')) {
            $category = ProductCategoryModel::where([
                'vendors__id' => $vendorId,
                '_uid' => $request->category_uid,
            ])->first();
            $categoryId = $category->_id ?? null;
        }

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
                        'product_categories__id' => $categoryId,
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
            return $this->processResponse(1, [
                1 => __tr('Catalogues récupérés avec succès.')
            ], [
                'catalogs' => $catalogsList
            ]);
        }

        return $this->processResponse(2, [
            2 => __tr('Aucun catalogue trouvé. Veuillez lier un catalogue à votre numéro WhatsApp dans Meta Business Suite.')
        ], [
            'message' => __tr('Aucun catalogue trouvé. Veuillez lier un catalogue à votre numéro WhatsApp dans Meta Business Suite.')
        ]);
    }

    /**
     * Clear products for a specific source or all sources
     */
    public function clearProducts(Request $request)
    {
        $vendorId = getVendorId();
        $source = $request->source;

        if ($source && $source !== 'all') {
            ProductModel::where([
                'vendors__id' => $vendorId,
                'source' => $source
            ])->delete();
            $message = __tr('Tous les produits de la source "__source__" ont été supprimés.', ['__source__' => $source]);
        } else {
            ProductModel::where('vendors__id', $vendorId)->delete();
            $message = __tr('Tous les produits du catalogue ont été supprimés.');
        }

        return $this->processResponse(1, [], [
            'message' => $message
        ]);
    }

    /**
     * Update Order Status
     */
    public function updateOrderStatus(Request $request, $orderUid)
    {
        if (!hasVendorAccess('manage_orders', 'add_edit_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $vendorId = getVendorId();
        $request->validate([
            'status' => 'required|string|in:validated,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $order = \App\Yantrana\Components\ECommerce\Models\OrderModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $orderUid
        ])->first();

        if (empty($order)) {
            return $this->processResponse(2, [
                2 => __tr('Commande introuvable.')
            ], [
                'message' => __tr('Commande introuvable.')
            ]);
        }

        $order->status = $request->status;
        $order->save();

        // Update contact notes & broadcast real-time contact update to chat frontend
        if ($order->contacts__id) {
            $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
                'vendors__id' => $vendorId,
                '_id' => $order->contacts__id
            ])->first();

            if ($contact) {
                $statusLabels = [
                    'validated' => __tr('Nouvelle / Validée'),
                    'confirmed' => __tr('Confirmée'),
                    'processing' => __tr('En préparation'),
                    'shipped' => __tr('En livraison'),
                    'delivered' => __tr('Livrée'),
                    'cancelled' => __tr('Annulée'),
                ];
                $statusName = $statusLabels[$request->status] ?? $request->status;
                // Status updates are tracked via the order object itself.

                $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
                if ($vendor) {
                    updateModelsViaVendorBroadcast($vendor->_uid, [
                        'contact' => $contact
                    ]);
                }
            }
        }

        return $this->processResponse(1, [
            1 => __tr('Statut de la commande mis à jour avec succès.')
        ], [
            'message' => __tr('Statut de la commande mis à jour avec succès.'),
            'order' => $order
        ]);
    }

    /**
     * Delete Order
     */
    public function deleteOrder($orderUid)
    {
        if (!hasVendorAccess('manage_orders', 'delete_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $vendorId = getVendorId();

        $order = \App\Yantrana\Components\ECommerce\Models\OrderModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $orderUid
        ])->first();

        if (empty($order)) {
            return $this->processResponse(2, [
                2 => __tr('Order not found.')
            ], [
                'message' => __tr('Order not found.')
            ]);
        }

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::find($order->contacts__id);
        if ($contact) {
            $orderRef = '#' . substr($order->_uid, 0, 8);
            $systemMsg = "🗑️ Commande {$orderRef} supprimée par l'agent";
            storeWhatsAppLogChatHistory([
                'status' => 'initialize',
                'contacts__id' => $contact->_id,
                'vendors__id' => $vendorId,
                'contact_wa_id' => $contact->wa_id,
                'is_system_message' => 1,
                'is_incoming_message' => 0,
                'messaged_at' => now(),
                'message' => $systemMsg,
                '__data' => [
                    'system_message_data' => [
                        'message' => $systemMsg
                    ]
                ]
            ]);
        }

        $order->delete();

        return $this->processResponse(1, [
            1 => __tr('Order deleted successfully.')
        ], [
            'message' => __tr('Order deleted successfully.')
        ]);
    }

    /**
     * Get Orders for a specific Contact (used in Live Chat CRM)
     */
    public function getContactOrders($contactUid)
    {
        $vendorId = getVendorId();
        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->where(function($q) use ($contactUid) {
                $q->where('_uid', $contactUid)
                  ->orWhere('_id', $contactUid)
                  ->orWhere('wa_id', $contactUid);
            })->first();

        if (empty($contact)) {
            return $this->processResponse(2, [], ['orders' => []]);
        }

        $orders = \App\Yantrana\Components\ECommerce\Models\OrderModel::where([
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id
        ])->latest()->get();

        return $this->processResponse(1, [], [
            'orders' => $orders
        ]);
    }

    /**
     * Create Manual Order by Vendor
     */
    public function createManualOrder(Request $request)
    {
        if (!hasVendorAccess('manage_orders', 'add_edit_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $vendorId = getVendorId();
        $request->validate([
            'contact_id' => 'required',
        ]);

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->where(function($q) use ($request) {
                $q->where('_id', $request->contact_id)
                  ->orWhere('_uid', $request->contact_id)
                  ->orWhere('wa_id', $request->contact_id);
            })->first();

        if (empty($contact)) {
            return $this->processResponse(2, [2 => __tr('Client introuvable.')], ['message' => __tr('Client introuvable.')]);
        }

        $items = [];
        $itemsTotal = 0;

        // Support multi-items array
        if ($request->filled('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                $pId = $itemData['product_id'] ?? null;
                $product = null;
                if ($pId) {
                    $product = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)
                        ->where(function($q) use ($pId) {
                            $q->where('_id', $pId)
                              ->orWhere('_uid', $pId);
                        })->first();
                }

                $name = $product ? $product->name : ($itemData['name'] ?? 'Produit');
                $qty = max(1, intval($itemData['quantity'] ?? 1));
                $unitPrice = isset($itemData['custom_price']) && $itemData['custom_price'] !== '' 
                    ? floatval($itemData['custom_price']) 
                    : floatval($product ? $product->price : 0);

                $itemSubtotal = $qty * $unitPrice;
                $itemsTotal += $itemSubtotal;

                $items[] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'currency' => 'CFA'
                ];
            }
        }

        // Backward compatibility: single product_id
        if (empty($items) && $request->filled('product_id')) {
            $product = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)
                ->where(function($q) use ($request) {
                    $q->where('_id', $request->product_id)
                      ->orWhere('_uid', $request->product_id);
                })->first();

            if (empty($product)) {
                return $this->processResponse(2, [2 => __tr('Produit introuvable.')], ['message' => __tr('Produit introuvable.')]);
            }

            $qty = intval($request->quantity) ?: 1;
            $unitPrice = floatval($request->custom_price) ?: floatval($product->price);
            $itemsTotal = $qty * $unitPrice;
            $items[] = [
                'name' => $product->name,
                'quantity' => $qty,
                'price' => $unitPrice,
                'currency' => 'CFA'
            ];
        }

        if (empty($items)) {
            return $this->processResponse(2, [2 => __tr('Veuillez sélectionner au moins un produit.')], ['message' => __tr('Veuillez sélectionner au moins un produit.')]);
        }

        $additionalFee = max(0, floatval($request->additional_fee ?? $request->shipping_fee ?? 0));
        $additionalFeeLabel = trim($request->additional_fee_label ?: 'Frais additionnels / Livraison');
        $totalPrice = $itemsTotal + $additionalFee;
        $vendorName = getUserAuthInfo('profile.full_name') ?: 'Vendeur';

        $orderDetails = [
            'source' => 'Manuel (Vendeur: ' . $vendorName . ')',
            'items' => $items,
            'additional_fee' => $additionalFee,
            'additional_fee_label' => $additionalFeeLabel,
            'total_price' => $totalPrice,
            'currency' => 'CFA',
            'delivery_address' => $request->delivery_address ?: '',
            'delivery_date' => $request->delivery_date ?: '',
            'created_by_vendor' => $vendorName
        ];

        $newOrder = \App\Yantrana\Components\ECommerce\Models\OrderModel::create([
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id,
            'order_details' => $orderDetails,
            'status' => 'validated',
        ]);

        $newOrder->load('contact');

        return $this->processResponse(1, [
            1 => __tr('Order created successfully!')
        ], [
            'message' => __tr('Order created successfully!'),
            'order' => $newOrder
        ]);
    }

    /**
     * Send a formatted order summary directly to the contact on WhatsApp
     */
    public function sendOrderSummaryMessage($orderUid)
    {
        if (!hasVendorAccess('manage_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $vendorId = getVendorId();
        $order = \App\Yantrana\Components\ECommerce\Models\OrderModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $orderUid,
        ])->with('contact')->first();

        if (empty($order) || empty($order->contact)) {
            return $this->processResponse(2, [2 => __tr('Commande introuvable.')], ['message' => __tr('Commande introuvable.')]);
        }

        $details = $order->order_details ?? [];
        $contact = $order->contact;
        $currency = $details['currency'] ?? 'CFA';

        // Same labels/values as the status dropdown in the orders panel, so
        // resending after a status change reflects it faithfully.
        $statusLabels = [
            'validated' => __tr('Nouvelle'),
            'confirmed' => __tr('Confirmée'),
            'processing' => __tr('En préparation'),
            'shipped' => __tr('En livraison'),
            'delivered' => __tr('Livrée'),
            'cancelled' => __tr('Annulée'),
        ];

        $lines = [
            '*Résumé de votre commande — ' . ($contact->full_name ?: $contact->wa_id) . '*',
            '📞 ' . $contact->wa_id,
            '📦 *Statut :* ' . ($statusLabels[$order->status] ?? $order->status),
            '------',
        ];

        foreach (($details['items'] ?? []) as $item) {
            $itemCurrency = $item['currency'] ?? $currency;
            $lines[] = '🛒 ' . ($item['name'] ?? __tr('Produit')) . ' x' . ($item['quantity'] ?? 1)
                . ' — ' . number_format((float) ($item['price'] ?? 0), 0, ',', ' ') . ' ' . $itemCurrency;
        }

        $lines[] = '------';
        $lines[] = '💰 *Total :* ' . number_format((float) ($details['total_price'] ?? 0), 0, ',', ' ') . ' ' . $currency;

        if (!empty($details['additional_fee'])) {
            $feeLabel = $details['additional_fee_label'] ?: __tr('Frais additionnels / Livraison');
            $lines[] = '🚚 *' . $feeLabel . ' :* ' . number_format((float) $details['additional_fee'], 0, ',', ' ') . ' ' . $currency;
        }

        if (!empty($details['delivery_address'])) {
            $lines[] = '📍 *Adresse :* ' . $details['delivery_address'];
        }

        if (!empty($details['delivery_date'])) {
            $lines[] = '🗓️ *Livraison prévue :* ' . $details['delivery_date'];
        }

        $sendRequest = [
            'messageBody' => implode("\n", $lines),
            'contactUid' => $contact->_uid,
        ];

        $phoneNumbers = getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: [];
        $currentPhoneNumberId = !empty($phoneNumbers) ? ($phoneNumbers[0]['id'] ?? null) : null;
        fromPhoneNumberIdForRequest($currentPhoneNumberId);

        $whatsAppServiceEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
        $processReaction = $whatsAppServiceEngine->processSendChatMessage(
            $sendRequest,
            false,
            $vendorId,
            ['from_phone_number_id' => $currentPhoneNumberId]
        );

        return $this->processResponse($processReaction);
    }

    /**
     * Create Test Order for testing notifications and order flow
     */
    public function createTestOrder(Request $request)
    {
        $vendorId = getVendorId();
        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->first();
        
        if (empty($contact)) {
            return $this->processResponse(2, [], [
                'message' => __tr('Please add at least one WhatsApp contact to create a test order.')
            ]);
        }

        $testDetails = [
            'catalog_id' => 'TEST_CATALOG_' . rand(1000, 9999),
            'items' => [
                ['name' => 'Produit de Test WhatsApp', 'quantity' => 1, 'price' => 15000, 'currency' => 'CFA']
            ]
        ];

        $newOrder = \App\Yantrana\Components\ECommerce\Models\OrderModel::create([
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id,
            'order_details' => $testDetails,
            'status' => 'validated',
        ]);

        return $this->processResponse(1, [], [
            'message' => __tr('Test order created (#__uid__). Reload page to see notification.', ['__uid__' => substr($newOrder->_uid, 0, 8)])
        ]);
    }

    /**
     * Webhook endpoint to receive orders from external websites (Shopify, WooCommerce, Custom E-commerce)
     */
    public function externalOrderWebhook(Request $request, $vendorUid)
    {
        \Illuminate\Support\Facades\Log::info('[WEBHOOK-ORDER-DEBUG] Incoming external order webhook for vendorUid=' . $vendorUid, [
            'method' => $request->method(),
            'payload' => $request->all(),
        ]);

        $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::where('_uid', $vendorUid)->first();
        if (empty($vendor)) {
            return response()->json(['reaction_code' => 2, 'message' => 'Vendor not found for UID: ' . $vendorUid], 404);
        }
        $vendorId = $vendor->_id;

        // Try extracting phone from various webhook schemas (Shopify, WooCommerce, Custom, Query string)
        $phone = $request->phone 
            ?: ($request->mobile_number 
            ?: ($request->mobile
            ?: ($request->tel
            ?: ($request->customer['phone'] ?? ($request->billing['phone'] ?? ($request->shipping['phone'] ?? null))))));

        if (empty($phone)) {
            return response()->json([
                'reaction_code' => 2,
                'message' => 'Customer phone number is required (pass `phone` or `mobile_number` field).'
            ], 400);
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            'vendors__id' => $vendorId,
            'wa_id' => $phone
        ])->first();

        if (empty($contact)) {
            $firstName = $request->first_name ?: ($request->name ?: ($request->customer['first_name'] ?? ($request->billing['first_name'] ?? 'Client')));
            $lastName = $request->last_name ?: ($request->customer['last_name'] ?? ($request->billing['last_name'] ?? 'Web'));
            $contact = \App\Yantrana\Components\Contact\Models\ContactModel::create([
                '_uid' => (string) \Illuminate\Support\Str::uuid(),
                'vendors__id' => $vendorId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'wa_id' => $phone,
                'phone_number' => $phone,
            ]);
        }

        $items = $request->items ?: ($request->line_items ?: []);
        if (empty($items) && $request->filled('product_name')) {
            $items = [
                [
                    'name' => $request->product_name,
                    'quantity' => $request->quantity ?: 1,
                    'price' => $request->price ?: 0,
                ]
            ];
        }

        $orderDetails = [
            'source' => $request->source ?: 'website',
            'items' => $items,
            'total_price' => $request->total_price ?: ($request->price ?: ($request->total_price_set['shop_money']['amount'] ?? 0)),
            'currency' => $request->currency ?: 'CFA',
        ];

        $newOrder = \App\Yantrana\Components\ECommerce\Models\OrderModel::create([
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id,
            'order_details' => $orderDetails,
            'status' => 'validated',
        ]);

        // Store system message log in chat history
        $totalPrice = $request->total_price ?: ($request->price ?: 0);
        $currency = $request->currency ?: 'CFA';
        $orderRef = '#' . substr($newOrder->_uid, 0, 8);
        $systemMsg = "📦 Nouvelle commande créée {$orderRef} ({$totalPrice} {$currency})";

        storeWhatsAppLogChatHistory([
            'status' => 'initialize',
            'contacts__id' => $contact->_id,
            'vendors__id' => $vendorId,
            'contact_wa_id' => $contact->wa_id,
            'is_system_message' => 1,
            'is_incoming_message' => 0,
            'messaged_at' => now(),
            'message' => $systemMsg,
            '__data' => [
                'system_message_data' => [
                    'message' => $systemMsg
                ]
            ]
        ]);

        return response()->json([
            'reaction_code' => 1,
            'message' => 'Order created successfully',
            'order_uid' => $newOrder->_uid,
            'order_reference' => '#' . substr($newOrder->_uid, 0, 8)
        ]);
    }
}
