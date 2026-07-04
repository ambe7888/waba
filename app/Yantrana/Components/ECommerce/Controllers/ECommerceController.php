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
}
