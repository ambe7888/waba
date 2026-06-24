<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Yantrana\Components\Subscription\Models\ManualSubscriptionModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessSaaSAutomations extends Command
{
    protected $signature = 'saas:process-automations';
    protected $description = 'Process SaaS automated messages (WhatsApp and Email) for subscription expiries and monthly reminders.';

    public function handle()
    {
        $this->info("Starting SaaS Automation Processing...");

        $saasAdminVendorId = getAppSettings('saas_admin_vendor_id');
        $expiryReminderTemplate = getAppSettings('saas_expiry_reminder_template');
        $expiredTemplate = getAppSettings('saas_expired_template');

        if (empty($saasAdminVendorId)) {
            $this->error("SaaS Admin Vendor ID is not configured. Exiting.");
            return;
        }

        $waEngine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
        $today = Carbon::now();
        
        // Let's check manual subscriptions that expire in exactly 3 days for reminder
        $reminderTargetDateStart = $today->copy()->addDays(3)->startOfDay();
        $reminderTargetDateEnd = $today->copy()->addDays(3)->endOfDay();
        
        $expiringSubscriptions = ManualSubscriptionModel::whereBetween('ends_at', [$reminderTargetDateStart, $reminderTargetDateEnd])
            ->where('status', 'active') // Or whatever active status is
            ->get();
            
        $this->info("Found " . $expiringSubscriptions->count() . " subscriptions expiring in 3 days.");

        foreach ($expiringSubscriptions as $sub) {
            $this->sendAutomatedMessageToVendor(
                $sub->vendors__id, 
                $saasAdminVendorId, 
                $expiryReminderTemplate, 
                $waEngine,
                'Subscription Reminder'
            );
        }

        // Let's check manual subscriptions that expired yesterday
        $expiredTargetDateStart = $today->copy()->subDays(1)->startOfDay();
        $expiredTargetDateEnd = $today->copy()->subDays(1)->endOfDay();
        
        $expiredSubscriptions = ManualSubscriptionModel::whereBetween('ends_at', [$expiredTargetDateStart, $expiredTargetDateEnd])
            ->get();
            
        $this->info("Found " . $expiredSubscriptions->count() . " subscriptions that expired yesterday.");

        foreach ($expiredSubscriptions as $sub) {
            $this->sendAutomatedMessageToVendor(
                $sub->vendors__id, 
                $saasAdminVendorId, 
                $expiredTemplate, 
                $waEngine,
                'Subscription Expired'
            );
        }

        $this->info("SaaS Automation Processing Complete.");
    }

    private function sendAutomatedMessageToVendor($targetVendorId, $adminVendorId, $templateName, $waEngine, $context)
    {
        if (empty($templateName)) {
            return;
        }

        // Find the owner/admin user of the target vendor
        // user_roles__id 2 is usually vendor admin
        $vendorAdmin = User::where('vendors__id', $targetVendorId)
            ->whereNotNull('mobile_number')
            ->where('mobile_number', '!=', '')
            ->first();

        if (!$vendorAdmin) {
            $this->info("No valid mobile number found for vendor ID: {$targetVendorId}");
            return;
        }

        $waId = preg_replace('/[^0-9]/', '', $vendorAdmin->mobile_number);
        if (empty($waId)) {
            return;
        }

        try {
            $this->info("Sending {$context} WhatsApp message to Vendor ID {$targetVendorId} (wa_id: {$waId})");
            
            $waEngine->sendActualWhatsAppTemplateMessage(
                (int)$adminVendorId, 
                0, 
                $waId, 
                '', 
                $templateName, 
                'fr', 
                ['name' => $templateName, 'language' => 'fr'], 
                [], 
                [], 
                null 
            );

            // Trigger an email using Laravel Mail / BaseMailer
            try {
                $baseMailer = app(\App\Yantrana\Base\BaseMailer::class);
                $emailData = [
                    'fullName' => $vendorAdmin->first_name,
                    'email' => $vendorAdmin->email,
                    'context' => $context
                ];
                $subject = $context == 'Subscription Reminder' ? __tr('Rappel d\'abonnement - Bientôt expiré') : __tr('Votre abonnement a expiré');
                $baseMailer->notifyToUser($subject, 'user.subscription-reminder', $emailData, $vendorAdmin->email);
            } catch (\Exception $emailEx) {
                Log::error("SaaS Automation Email Error ({$context}): " . $emailEx->getMessage());
            }

        } catch (\Exception $e) {
            Log::error("SaaS Automation Error ({$context}): " . $e->getMessage());
        }
    }
}
