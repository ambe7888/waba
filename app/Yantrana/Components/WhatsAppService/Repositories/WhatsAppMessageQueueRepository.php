<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
* WhatsAppMessageQueueRepository.php - Repository file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppMessageQueueRepositoryInterface;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageQueueModel;
use Illuminate\Support\Facades\DB;

class WhatsAppMessageQueueRepository extends BaseRepository implements WhatsAppMessageQueueRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = WhatsAppMessageQueueModel::class;

    /**
     * Stuck message processing
     *
     * @return int
     */
    public function stuckInProcessing()
    {
        $where = [
            'status' => 3, // processing
            [
                'scheduled_at', '<=', now()
            ],
            [
                'updated_at', '<=', now()->subMinutes(5)
            ]
        ];
        $stuckItemsCount = $this->primaryModel::where($where)->count();
        if ($stuckItemsCount) {
            // stuck queue items in processing more than 5 minutes
            $this->updateItAll($where, [
                'status' => 6, // processed & response awaited
                '__data' => [
                    'process_response' => [
                        'error_status'  => 'awaited_response_error',
                        'error_message' => 'Responses awaited from WhatsApp',
                    ]
                    
                ]
            ]);
        }
        return $stuckItemsCount;
    }

    /**
     * Take the items from database for message process
     *
     * @return Eloquent Objects
     */
    public function getQueueItemsForProcess()
    {
        $this->updateItAll([
            'status' => 1,
            [
                '__data->expiry_at', '<=', now()
            ]
        ], [
            'status' => 5, // Expired
            '__data' => [
                'process_response' => [
                    'error_message' => 'message expired',
                    'error_status' => 'campaign_expired_error',
                ]
            ]
        ]);

        // go grab queue records for processing.
        //
        // This used to be a plain SELECT: whatsapp:campaign:process runs
        // every 5 seconds, and if a run ever takes longer than 5 seconds
        // (large campaign, slow WhatsApp API response) — or a second trigger
        // reaches this code (e.g. a stuck scheduler lock, schedule:work and
        // crontab schedule:run both active) — a concurrent call could SELECT
        // the exact same status=1 rows before the first call gets around to
        // marking them status=3 a few lines later in processCampaignSchedule(),
        // and every message in the overlap gets sent twice. Confirmed this
        // is possible: nothing between the SELECT here and the later UPDATE
        // stopped a second reader from seeing the same "waiting" rows.
        //
        // Claim the rows atomically instead: lock the candidate rows with a
        // real row lock inside a transaction, flip them to status=3
        // (processing) before releasing the lock, and only then read them
        // back. A concurrent call's SELECT ... FOR UPDATE on the same rows
        // blocks until this transaction commits, and by then those rows no
        // longer match status=1, so the second call correctly gets nothing.
        $claimedIds = DB::transaction(function () {
            $ids = $this->primaryModel::where([
                    'status' => 1,
                    [
                        'scheduled_at', '<=', now()
                    ],
                ])
                ->take((getAppSettings('cron_process_messages_per_lot') ?: 60))
                ->inRandomOrder()
                ->lockForUpdate()
                ->pluck('_id');

            if ($ids->isNotEmpty()) {
                $this->primaryModel::whereIn('_id', $ids)->update(['status' => 3]);
            }

            return $ids;
        });

        if ($claimedIds->isEmpty()) {
            return collect();
        }

        return $this->primaryModel::select([
            '_id',
            '_uid',
            'status',
            'scheduled_at',
            'phone_with_country_code',
            'campaigns__id',
            'contacts__id',
            'vendors__id',
            'full_name',
            'retries',
            '__data',
            'updated_at',
        ])->whereIn('_id', $claimedIds)->get();
    }
    /**
     * Queued messages count
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function campaignQueueItemsCount($campaignId)
    {
        return $this->primaryModel::where([
            'status' => 1,
            'campaigns__id' => $campaignId,
        ])->count();
    }

    /**
     * Get in queue messages in chunk
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function fetchInQueueMessageInChunks($campaignId)
    {
        return $this->primaryModel::where([
            'status' => 1,
            'campaigns__id' => $campaignId,
        ])->update([
            'status' => 7
        ]);
    }

    /**
     * Get in queue messages in chunk
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function storeCampaignMessageQueData($storeData)
    {
        return $this->primaryModel::insert($storeData);
    }
}
