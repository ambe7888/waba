<?php
namespace App\Yantrana\Components\Delivery\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Delivery\Models\DeliveryDriverModel;
use App\Yantrana\Components\ECommerce\Models\OrderModel;

class DeliveryDriverRepository extends BaseRepository
{
    /**
     * Fetch a driver by id or uid, scoped to the current vendor
     *
     * @param int|string $idOrUid
     * @return \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel|null
     *---------------------------------------------------------------- */
    public function fetch($idOrUid)
    {
        $vendorId = getVendorId();
        if (is_numeric($idOrUid)) {
            return DeliveryDriverModel::where('vendors__id', $vendorId)->where('_id', $idOrUid)->first();
        }
        return DeliveryDriverModel::where('vendors__id', $vendorId)->where('_uid', $idOrUid)->first();
    }

    /**
     * Fetch datatable source for the drivers list, with an active-deliveries count
     *
     * @return array
     *---------------------------------------------------------------- */
    public function fetchDriversDataTableSource()
    {
        $vendorId = getVendorId();
        $dataTableConfig = [
            'searchable' => [
                'first_name',
                'last_name',
                'phone',
                'zone',
            ],
        ];
        $data = DeliveryDriverModel::where('vendors__id', $vendorId)
            ->dataTables($dataTableConfig)
            ->toArray();

        if (!empty($data['data'])) {
            $driverIds = array_column($data['data'], '_id');
            $activeCounts = OrderModel::where('vendors__id', $vendorId)
                ->whereIn('assigned_driver__id', $driverIds)
                ->where('status', 'in_delivery')
                ->selectRaw('assigned_driver__id, count(*) as cnt')
                ->groupBy('assigned_driver__id')
                ->pluck('cnt', 'assigned_driver__id')
                ->toArray();

            foreach ($data['data'] as &$row) {
                $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
                $row['active_deliveries_count'] = $activeCounts[$row['_id']] ?? 0;
                $row['status_formatted'] = $row['is_active'] ? __tr('Actif') : __tr('Inactif');
            }
        }

        return $data;
    }

    /**
     * Fetch datatable source for the delivery-tracking page (orders currently in delivery)
     *
     * @return array
     *---------------------------------------------------------------- */
    public function fetchTrackingDataTableSource()
    {
        $vendorId = getVendorId();
        $dataTableConfig = [
            'searchable' => [],
        ];
        $data = OrderModel::where('vendors__id', $vendorId)
            ->where('status', 'in_delivery')
            ->with(['contact', 'driver'])
            ->dataTables($dataTableConfig)
            ->toArray();

        if (!empty($data['data'])) {
            foreach ($data['data'] as &$row) {
                $contact = $row['contact'] ?? null;
                $driver = $row['driver'] ?? null;
                $details = $row['order_details'] ?? [];
                if (is_string($details)) {
                    $decoded = json_decode($details, true);
                    $details = is_array($decoded) ? $decoded : [];
                }

                $row['client_formatted'] = $contact
                    ? trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')) . ' (' . ($contact['wa_id'] ?? '-') . ')'
                    : __tr('Client Inconnu');
                $row['driver_formatted'] = $driver
                    ? trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? '')) . ' - ' . ($driver['phone'] ?? '')
                    : '-';
                $row['address_formatted'] = $details['delivery_address'] ?? '-';
                $row['total_formatted'] = number_format((float) ($details['total_price'] ?? 0), 0, ',', ' ') . ' ' . ($details['currency'] ?? 'CFA');
                $row['assigned_at_formatted'] = !empty($row['delivery_assigned_at'])
                    ? \Carbon\Carbon::parse($row['delivery_assigned_at'])->format('d/m/Y H:i')
                    : '-';
            }
        }

        return $data;
    }

    /**
     * Store a new driver
     *
     * @param array $inputData
     * @return \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel|bool
     *---------------------------------------------------------------- */
    public function storeDriver($inputData)
    {
        $driver = new DeliveryDriverModel();
        if ($driver->assignInputsAndSave($inputData, [
            'vendors__id' => getVendorId(),
            'is_active' => true,
            'first_name',
            'last_name',
            'zone',
            'phone',
            'address',
            'vehicle_type',
        ])) {
            return $driver;
        }
        return false;
    }

    /**
     * Update an existing driver
     *
     * @param \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel $driver
     * @param array $inputData
     * @return bool
     *---------------------------------------------------------------- */
    public function updateDriver($driver, $inputData)
    {
        return $driver->assignInputsAndSave($inputData, [
            'first_name',
            'last_name',
            'zone',
            'phone',
            'address',
            'vehicle_type',
        ]);
    }

    /**
     * Delete a driver
     *
     * @param \App\Yantrana\Components\Delivery\Models\DeliveryDriverModel $driver
     * @return bool
     *---------------------------------------------------------------- */
    public function deleteDriver($driver)
    {
        // Unassign any orders currently pointed at this driver instead of
        // leaving them referencing a deleted driver.
        OrderModel::where('vendors__id', $driver->vendors__id)
            ->where('assigned_driver__id', $driver->_id)
            ->update(['assigned_driver__id' => null, 'delivery_assigned_at' => null]);

        return $driver->delete();
    }
}
