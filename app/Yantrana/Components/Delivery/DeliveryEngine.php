<?php
namespace App\Yantrana\Components\Delivery;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\Delivery\Repositories\DeliveryDriverRepository;
use App\Yantrana\Components\ECommerce\Models\OrderModel;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;

class DeliveryEngine extends BaseEngine
{
    /**
     * @var DeliveryDriverRepository
     */
    protected $deliveryDriverRepository;

    /**
     * Constructor
     *
     * @param DeliveryDriverRepository $deliveryDriverRepository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(DeliveryDriverRepository $deliveryDriverRepository)
    {
        $this->deliveryDriverRepository = $deliveryDriverRepository;
    }

    /**
     * Prepare drivers datatable data
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareDriversDataTable()
    {
        return $this->deliveryDriverRepository->fetchDriversDataTableSource();
    }

    /**
     * Prepare delivery-tracking datatable data
     *
     * @param string $statusFilter
     * @param string|null $driverUidFilter
     * @return array
     *---------------------------------------------------------------- */
    public function prepareTrackingDataTable($statusFilter = 'in_delivery', $driverUidFilter = null)
    {
        return $this->deliveryDriverRepository->fetchTrackingDataTableSource($statusFilter, $driverUidFilter);
    }

    /**
     * Recap counts for the delivery-tracking page
     *
     * @param int $vendorId
     * @return array
     *---------------------------------------------------------------- */
    public function fetchDeliveryRecapCounts($vendorId)
    {
        return $this->deliveryDriverRepository->fetchDeliveryRecapCounts($vendorId);
    }

    /**
     * Process add or update driver
     *
     * @param object $request
     * @param string|null $driverUid
     * @return array
     *---------------------------------------------------------------- */
    public function processAddOrUpdateDriver($request, $driverUid = null)
    {
        $inputData = $request->all();

        if ($driverUid) {
            $driver = $this->deliveryDriverRepository->fetch($driverUid);
            if (__isEmpty($driver)) {
                return $this->engineResponse(18, null, __tr('Livreur introuvable.'));
            }
            if ($this->deliveryDriverRepository->updateDriver($driver, $inputData)) {
                return $this->engineResponse(1, null, __tr('Livreur mis à jour avec succès.'));
            }
            return $this->engineResponse(2, null, __tr('Livreur non mis à jour.'));
        }

        if ($this->deliveryDriverRepository->storeDriver($inputData)) {
            return $this->engineResponse(1, null, __tr('Livreur ajouté avec succès.'));
        }
        return $this->engineResponse(2, null, __tr('Livreur non ajouté.'));
    }

    /**
     * Process delete driver
     *
     * @param string $driverUid
     * @return array
     *---------------------------------------------------------------- */
    public function processDeleteDriver($driverUid)
    {
        $driver = $this->deliveryDriverRepository->fetch($driverUid);
        if (__isEmpty($driver)) {
            return $this->engineResponse(18, null, __tr('Livreur introuvable.'));
        }
        if ($this->deliveryDriverRepository->deleteDriver($driver)) {
            return $this->engineResponse(1, null, __tr('Livreur supprimé avec succès.'));
        }
        return $this->engineResponse(2, null, __tr('Livreur non supprimé.'));
    }

    /**
     * Assign one or more orders to a driver: updates each order, moves it to
     * the "in_delivery" status, and notifies the driver on WhatsApp with a
     * "Livré" / "Non livré" quick-reply message.
     *
     * @param array $orderUids
     * @param string $driverUid
     * @param int $vendorId
     * @return array
     *---------------------------------------------------------------- */
    public function assignDriverToOrders($orderUids, $driverUid, $vendorId)
    {
        $driver = $this->deliveryDriverRepository->fetch($driverUid);
        if (__isEmpty($driver)) {
            return $this->engineResponse(18, null, __tr('Livreur introuvable.'));
        }

        $orders = OrderModel::where('vendors__id', $vendorId)
            ->whereIn('_uid', $orderUids)
            ->with('contact')
            ->get();

        if ($orders->isEmpty()) {
            return $this->engineResponse(2, null, __tr('Aucune commande valide sélectionnée.'));
        }

        $assignedCount = 0;
        $messageFailures = [];
        // WhatsApp only allows free-form (non-template) messages within 24h
        // of the recipient's own last message -- checked once for the whole
        // batch since it's the same driver, so a closed window can be
        // reported clearly instead of failing silently order by order.
        $windowOpen = $driver->is_24h_window_open;

        foreach ($orders as $order) {
            $order->assigned_driver__id = $driver->_id;
            $order->delivery_assigned_at = now();
            $order->status = 'in_delivery';
            $order->save();
            $assignedCount++;

            if ($order->contacts__id) {
                $contact = $order->contact;
                $orderRef = '#' . substr($order->_uid, 0, 8);
                $systemMsg = __tr('🚚 Commande __ref__ assignée au livreur __driver__', [
                    '__ref__' => $orderRef,
                    '__driver__' => $driver->full_name,
                ]);
                storeWhatsAppLogChatHistory([
                    'status' => 'initialize',
                    'contacts__id' => $order->contacts__id,
                    'vendors__id' => $vendorId,
                    'contact_wa_id' => $contact->wa_id ?? null,
                    'is_system_message' => 1,
                    'is_incoming_message' => 0,
                    'messaged_at' => now(),
                    'message' => $systemMsg,
                    '__data' => [
                        'system_message_data' => [
                            'message' => $systemMsg,
                        ],
                    ],
                ]);

                $vendor = VendorModel::find($vendorId);
                if ($vendor) {
                    updateModelsViaVendorBroadcast($vendor->_uid, [
                        'contact' => $contact,
                        'delivery_status_update' => [
                            'order_uid' => $order->_uid,
                            'order_ref' => $orderRef,
                            'event' => 'assigned',
                            'driver_name' => $driver->full_name,
                        ],
                    ]);
                }
            }

            if ($windowOpen) {
                try {
                    $sendResult = $this->sendDeliveryAssignmentMessage($order, $driver, $vendorId);
                } catch (\Throwable $e) {
                    $sendResult = false;
                }
                if (!$sendResult) {
                    $messageFailures[] = substr($order->_uid, 0, 8);
                }
            }
        }

        if (!$windowOpen) {
            return $this->engineResponse(1, null, __tr('__count__ commande(s) assignée(s) à __driver__, mais aucune notification WhatsApp n\'a pu être envoyée : la fenêtre de 24h est fermée (le livreur n\'a pas écrit depuis plus de 24h, ou jamais). Demandez-lui d\'envoyer un message (ex. "Je suis disponible") pour rouvrir la fenêtre, puis réassignez.', [
                '__count__' => $assignedCount,
                '__driver__' => $driver->full_name,
            ]));
        }

        if (!empty($messageFailures)) {
            return $this->engineResponse(1, null, __tr('__count__ commande(s) assignée(s), mais le message WhatsApp au livreur a échoué pour : __refs__', [
                '__count__' => $assignedCount,
                '__refs__' => implode(', #', $messageFailures),
            ]));
        }

        return $this->engineResponse(1, null, __tr('__count__ commande(s) assignée(s) à __driver__ et notification WhatsApp envoyée.', [
            '__count__' => $assignedCount,
            '__driver__' => $driver->full_name,
        ]));
    }

    /**
     * Build and send the driver-facing WhatsApp delivery notification with
     * "Livré" / "Non livré" quick-reply buttons.
     *
     * @param OrderModel $order
     * @param \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel $driver
     * @param int $vendorId
     * @return bool
     *---------------------------------------------------------------- */
    public function sendDeliveryAssignmentMessage($order, $driver, $vendorId)
    {
        $details = $order->order_details ?? [];
        $contact = $order->contact;
        $currency = $details['currency'] ?? 'CFA';

        $lines = [
            __tr('🚚 *Nouvelle livraison assignée*'),
            '------',
            __tr('*Client :* __name__', ['__name__' => $contact->full_name ?? ($contact->wa_id ?? __tr('Client'))]),
        ];

        if (!empty($contact->wa_id)) {
            $lines[] = __tr('*Téléphone :* __phone__', ['__phone__' => $contact->wa_id]);
        }

        if (!empty($details['delivery_address'])) {
            $lines[] = __tr('*Adresse :* __address__', ['__address__' => $details['delivery_address']]);
        }

        $lines[] = '------';
        foreach (($details['items'] ?? []) as $item) {
            $lines[] = '🛒 ' . ($item['name'] ?? __tr('Produit')) . ' x' . ($item['quantity'] ?? 1);
        }
        $lines[] = '------';
        $lines[] = __tr('*Total à encaisser :* __total__ __currency__', [
            '__total__' => number_format((float) ($details['total_price'] ?? 0), 0, ',', ' '),
            '__currency__' => $currency,
        ]);
        $lines[] = '';
        $lines[] = __tr('Merci de confirmer la livraison ci-dessous :');

        $phoneNumbers = getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: [];
        $currentPhoneNumberId = !empty($phoneNumbers) ? ($phoneNumbers[0]['id'] ?? null) : null;
        fromPhoneNumberIdForRequest($currentPhoneNumberId);

        $whatsAppApiService = app(WhatsAppApiService::class);
        $response = $whatsAppApiService->sendInteractiveMessage($driver->phone, [
            'interactive_type' => 'button',
            'body_text' => implode("\n", $lines),
            'buttons' => [__tr('✅ Livré'), __tr('❌ Non livré')],
            'button_ids' => [
                'delivery_delivered_' . $order->_uid,
                'delivery_failed_' . $order->_uid,
            ],
        ], $vendorId);

        return isset($response['messages'][0]['id']);
    }

    /**
     * Update an order's delivery outcome, whether triggered manually from
     * the tracking page or by the driver tapping a WhatsApp quick-reply
     * button.
     *
     * @param string $orderUid
     * @param string $action - 'delivered' or 'failed'
     * @param int $vendorId
     * @param string $source - 'manual' or 'driver_reply'
     * @return bool
     *---------------------------------------------------------------- */
    public function updateOrderDeliveryStatus($orderUid, $action, $vendorId, $source = 'manual')
    {
        $order = OrderModel::where('vendors__id', $vendorId)
            ->where('_uid', $orderUid)
            ->with(['contact', 'driver'])
            ->first();

        if (__isEmpty($order)) {
            return false;
        }

        $order->status = $action === 'delivered' ? 'delivered' : 'delivery_failed';
        $order->delivery_outcome_seen_at = null;
        $order->save();

        $orderRef = '#' . substr($order->_uid, 0, 8);
        $driverName = $order->driver->full_name ?? __tr('le livreur');

        if ($order->contacts__id) {
            $systemMsg = $action === 'delivered'
                ? __tr('✅ Commande __ref__ livrée par __driver__', ['__ref__' => $orderRef, '__driver__' => $driverName])
                : __tr('⚠️ Commande __ref__ signalée non livrée par __driver__', ['__ref__' => $orderRef, '__driver__' => $driverName]);

            storeWhatsAppLogChatHistory([
                'status' => 'initialize',
                'contacts__id' => $order->contacts__id,
                'vendors__id' => $vendorId,
                'contact_wa_id' => $order->contact->wa_id ?? null,
                'is_system_message' => 1,
                'is_incoming_message' => 0,
                'messaged_at' => now(),
                'message' => $systemMsg,
                '__data' => [
                    'system_message_data' => [
                        'message' => $systemMsg,
                    ],
                ],
            ]);
        }

        $vendor = VendorModel::find($vendorId);
        if ($vendor) {
            updateModelsViaVendorBroadcast($vendor->_uid, [
                'contact' => $order->contact,
                'delivery_status_update' => [
                    'order_uid' => $order->_uid,
                    'order_ref' => $orderRef,
                    'event' => $action === 'delivered' ? 'delivered' : 'failed',
                    'driver_name' => $driverName,
                ],
            ]);
        }

        return true;
    }

    /**
     * Count delivered/failed deliveries the vendor hasn't acknowledged yet
     * (used for the sidebar "Livraison" notification bubble)
     *
     * @param int $vendorId
     * @return int
     *---------------------------------------------------------------- */
    public function countUnseenDeliveryOutcomes($vendorId)
    {
        return OrderModel::where('vendors__id', $vendorId)
            ->whereNotNull('assigned_driver__id')
            ->whereIn('status', ['delivered', 'delivery_failed'])
            ->whereNull('delivery_outcome_seen_at')
            ->count();
    }

    /**
     * Mark all delivered/failed deliveries as seen (called when the vendor
     * opens the delivery tracking page)
     *
     * @param int $vendorId
     * @return void
     *---------------------------------------------------------------- */
    public function markDeliveryOutcomesSeen($vendorId)
    {
        OrderModel::where('vendors__id', $vendorId)
            ->whereNotNull('assigned_driver__id')
            ->whereIn('status', ['delivered', 'delivery_failed'])
            ->whereNull('delivery_outcome_seen_at')
            ->update(['delivery_outcome_seen_at' => now()]);
    }
}
