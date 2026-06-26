<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Addons\WhatsJetDripCampaignAddon\Models\DripSubscriber;
use Addons\WhatsJetDripCampaignAddon\Models\DripStep;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessDripCampaigns extends Command
{
    protected $signature = 'drip:process';
    protected $description = 'Process all active Drip Campaigns and send scheduled messages';

    public function handle()
    {
        $this->info("Processing Drip Campaigns...");
        
        $subscribers = DripSubscriber::where('status', 1)->with(['campaign', 'contact'])->get();

        foreach ($subscribers as $sub) {
            if (!$sub->contact || !$sub->campaign) {
                continue;
            }

            $minutesSinceStart = Carbon::parse($sub->start_date)->diffInMinutes(Carbon::now());

            // Find next steps that are due and haven't been sent
            $nextSteps = DripStep::where('addon_drip_campaigns__id', $sub->campaign->_id)
                ->when($sub->last_step_id, function($query) use ($sub) {
                    return $query->where('_id', '>', $sub->last_step_id);
                })
                ->get()
                ->filter(function($step) use ($minutesSinceStart) {
                    $stepMinutes = $step->delay_value;
                    if ($step->delay_type == 'hours') {
                        $stepMinutes = $step->delay_value * 60;
                    } elseif ($step->delay_type == 'days') {
                        $stepMinutes = $step->delay_value * 1440;
                    }
                    return $stepMinutes <= $minutesSinceStart;
                })
                ->sortBy(function($step) {
                    $stepMinutes = $step->delay_value;
                    if ($step->delay_type == 'hours') {
                        $stepMinutes = $step->delay_value * 60;
                    } elseif ($step->delay_type == 'days') {
                        $stepMinutes = $step->delay_value * 1440;
                    }
                    return $stepMinutes;
                });

            foreach ($nextSteps as $step) {
                try {
                    $this->info("Sending step {$step->_id} to contact {$sub->contact->phone_with_country_code}");
                    
                    if ($step->template) {
                        // Send template message
                        $waEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
                        $templateData = $step->template->__data['template'] ?? [];
                        
                        $components = []; // Simplified
                        $waEngine->sendActualWhatsAppTemplateMessage(
                            $sub->contact->vendors__id,
                            $sub->contact->_id,
                            $sub->contact->wa_id,
                            $sub->contact->_uid,
                            $templateData['name'] ?? '',
                            $templateData['language'] ?? 'fr',
                            $templateData,
                            $components,
                            null,
                            null
                        );
                    } elseif ($step->custom_message) {
                        // Send custom message (non-template)
                        $waEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
                        $waEngine->sendReplyBotMessage($sub->contact->_uid, $step->custom_message, $sub->contact->vendors__id);
                    }
                    
                    $sub->last_step_id = $step->_id;
                    $sub->save();
                    
                } catch (\Exception $e) {
                    Log::error("Failed to send drip step: " . $e->getMessage());
                    $this->error("Failed to send step {$step->_id}: " . $e->getMessage());
                }
            }
            
            // Check if there are no more steps at all
            $totalRemainingSteps = DripStep::where('addon_drip_campaigns__id', $sub->campaign->_id)
                ->where('_id', '>', $sub->last_step_id ?? 0)
                ->count();
                
            if ($totalRemainingSteps === 0) {
                $sub->status = 2; // Completed
                $sub->save();
            }
        }

        $this->info("Drip Campaigns Processed!");
    }
}
