<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use Illuminate\Support\Arr;

class SyncVendorCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:sync-credits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize AI credits for all active vendors based on their active subscription and custom overrides';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vendors = VendorModel::all();
        $this->info("Found " . $vendors->count() . " vendors. Starting sync...");

        foreach ($vendors as $vendor) {
            $subscription = getVendorCurrentActiveSubscription($vendor->_id);
            if (__isEmpty($subscription)) {
                $this->line("Vendor {$vendor->title} (ID: {$vendor->_id}) has no active subscription. Skipping.");
                continue;
            }

            $activePlanId = $subscription->plan_id ?? $subscription->type;
            if (!$activePlanId) {
                $this->line("Vendor {$vendor->title} has no plan ID. Skipping.");
                continue;
            }

            $planDetails = getPaidPlans($activePlanId);
            $aiCreditsLimit = Arr::get($planDetails, 'features.ai_credits.limit', 0);

            // Apply custom overrides
            if ($vendor->plan_custom_limits) {
                $customLimits = is_string($vendor->plan_custom_limits) ? json_decode($vendor->plan_custom_limits, true) : $vendor->plan_custom_limits;
                if (is_array($customLimits) && isset($customLimits['ai_credits'])) {
                    $aiCreditsLimit = $customLimits['ai_credits'];
                }
            }

            $planAiCredits = ($aiCreditsLimit == -1) ? 999999999 : (int)$aiCreditsLimit;
            
            VendorModel::where('_id', $vendor->_id)
                ->update(['plan_ai_credits' => $planAiCredits]);

            $this->info("Vendor {$vendor->title} (ID: {$vendor->_id}): Set plan_ai_credits to " . ($planAiCredits === 999999999 ? 'Unlimited (-1)' : $planAiCredits));
        }

        $this->info("Synchronization completed successfully!");
    }
}
