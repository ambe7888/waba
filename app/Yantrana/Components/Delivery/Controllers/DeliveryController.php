<?php
namespace App\Yantrana\Components\Delivery\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Components\Delivery\DeliveryEngine;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends BaseController
{
    /**
     * @var DeliveryEngine
     */
    protected $deliveryEngine;

    /**
     * Constructor
     *
     * @param DeliveryEngine $deliveryEngine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(DeliveryEngine $deliveryEngine)
    {
        $this->deliveryEngine = $deliveryEngine;
    }

    /**
     * Show drivers management view
     *
     * @return view
     *---------------------------------------------------------------- */
    public function showDriversView()
    {
        validateVendorAccess('manage_orders');
        return $this->loadView('delivery.drivers');
    }

    /**
     * Prepare drivers datatable data
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function driversDataTable()
    {
        validateVendorAccess('manage_orders');
        return $this->deliveryEngine->prepareDriversDataTable();
    }

    /**
     * Process add or update driver
     *
     * @param BaseRequest $request
     * @param string|null $driverUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processAddOrUpdateDriver(BaseRequest $request, $driverUid = null)
    {
        if (!hasVendorAccess('manage_orders', 'add_edit_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'max:100'],
            'last_name' => ['nullable', 'max:100'],
            'phone' => ['required', 'max:30'],
            'zone' => ['nullable', 'max:150'],
            'address' => ['nullable', 'max:500'],
            'vehicle_type' => ['nullable', 'max:100'],
        ]);
        $validator->validate();

        $processReaction = $this->deliveryEngine->processAddOrUpdateDriver($request, $driverUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Process delete driver
     *
     * @param string $driverUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processDeleteDriver($driverUid)
    {
        if (!hasVendorAccess('manage_orders', 'delete_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $processReaction = $this->deliveryEngine->processDeleteDriver($driverUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Show delivery tracking view
     *
     * @return view
     *---------------------------------------------------------------- */
    public function showTrackingView()
    {
        validateVendorAccess('manage_orders');
        $vendorId = getVendorId();
        $drivers = \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel::where('vendors__id', $vendorId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
        // Viewing this page acknowledges any delivered/failed outcomes, clearing the sidebar bubble.
        $this->deliveryEngine->markDeliveryOutcomesSeen($vendorId);
        return $this->loadView('delivery.tracking', compact('drivers'));
    }

    /**
     * Prepare delivery-tracking datatable data
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function trackingDataTable()
    {
        validateVendorAccess('manage_orders');
        return $this->deliveryEngine->prepareTrackingDataTable();
    }

    /**
     * Manually mark a delivery as delivered/failed from the tracking page
     *
     * @param BaseRequest $request
     * @param string $orderUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateDeliveryStatusManually(BaseRequest $request, $orderUid)
    {
        if (!hasVendorAccess('manage_orders', 'add_edit_orders')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $request->validate([
            'action' => 'required|string|in:delivered,failed',
        ]);

        $vendorId = getVendorId();
        $updated = $this->deliveryEngine->updateOrderDeliveryStatus($orderUid, $request->action, $vendorId, 'manual');

        if (!$updated) {
            return $this->processResponse(2, [2 => __tr('Commande introuvable.')], ['message' => __tr('Commande introuvable.')]);
        }

        return $this->processResponse(1, [1 => __tr('Statut de livraison mis à jour.')], ['message' => __tr('Statut de livraison mis à jour.')]);
    }
}
