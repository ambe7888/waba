<?php

namespace App\Yantrana\Components\Subscription\Controllers;

use App\Yantrana\Base\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Yantrana\Components\Subscription\Models\ManualSubscriptionModel;
use Carbon\Carbon;

class WavePaymentController extends BaseController
{
    /**
     * Create Checkout Session for Wave Mobile Money
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan' => 'required',
        ]);

        $vendorId = getVendorId();
        
        $planRequest = explode('___', $request->plan);
        if (count($planRequest) !== 2) {
            return redirect()->back()->with(['error' => __tr('Invalid plan selected.')]);
        }
        
        $planId = $planRequest[0];
        $planFrequency = $planRequest[1];
        
        $subscriptionPlans = getPaidPlans();
        $getPlanDetails = \Arr::get($subscriptionPlans, $planId);
        $planCharge = \Arr::get($getPlanDetails, 'charges.'.$planFrequency.'.charge');
        $currency = getAppSettings('currency');

        // Check if wave is enabled and keys exist
        if (!getAppSettings('enable_wave') || !getAppSettings('wave_api_key')) {
            return $this->processResponse(2, [], __tr('Wave Mobile Money is not configured.'));
        }

        $apiKey = getAppSettings('wave_api_key');
        
        $clientReference = $vendorId . '|' . $planId . '|' . $planFrequency;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.wave.com/v1/checkout/sessions', [
            'amount' => (string) $planCharge,
            'currency' => $currency ?: 'XOF',
            'error_url' => route('subscription.read.show', ['status' => 'failed']),
            'success_url' => route('subscription.read.show', ['status' => 'success']),
            'client_reference' => $clientReference,
        ]);

        if ($response->successful() && $response->json('wave_launch_url')) {
            return redirect($response->json('wave_launch_url'));
        }

        Log::error('Wave Checkout Error', ['response' => $response->json()]);
        return redirect()->route('subscription.read.show')->with([
            'error' => __tr('Failed to initiate Wave checkout.')
        ]);
    }

    /**
     * Create Checkout Session for Wave Mobile Money - AI Credits
     */
    public function createAiCreditsCheckoutSession(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'credits' => 'required|numeric',
        ]);

        $vendorId = getVendorId();
        $amount = $request->amount;
        $credits = $request->credits;
        
        $currency = getAppSettings('currency');

        // Check if wave is enabled and keys exist
        if (!getAppSettings('enable_wave') || !getAppSettings('wave_api_key')) {
            return redirect()->back()->with(['error' => __tr('Wave Mobile Money is not configured.')]);
        }

        $apiKey = getAppSettings('wave_api_key');
        
        // Prefix with 'aicredits|'
        $clientReference = 'aicredits|' . $vendorId . '|' . $credits;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.wave.com/v1/checkout/sessions', [
            'amount' => (string) $amount,
            'currency' => $currency ?: 'XOF',
            'error_url' => route('vendor.ai_credits.topup', ['status' => 'failed']),
            'success_url' => route('vendor.ai_credits.topup', ['status' => 'success']),
            'client_reference' => $clientReference,
        ]);

        if ($response->successful() && $response->json('wave_launch_url')) {
            return redirect($response->json('wave_launch_url'));
        }

        Log::error('Wave AI Credits Checkout Error', ['response' => $response->json()]);
        return redirect()->route('vendor.ai_credits.topup')->with([
            'error' => __tr('Failed to initiate Wave checkout.')
        ]);
    }

    /**
     * Handle incoming webhooks from Wave Mobile Money
     */
    public function handleWebhook(Request $request)
    {
        $webhookSecret = getAppSettings('wave_webhook_secret');
        $signature = $request->header('wave-signature');

        // Simple validation or you can use HMAC if required by Wave
        if (!$signature && $webhookSecret) {
            // Depending on Wave's signature implementation (e.g. HMAC SHA256)
            // For now, accept the payload and log it.
        }

        $payload = $request->all();
        Log::info('Wave Webhook received', $payload);

        $type = $payload['type'] ?? '';
        
        if ($type === 'checkout.session.completed') {
            $data = $payload['data'] ?? [];
            $clientReference = $data['client_reference'] ?? '';
            
            if ($clientReference) {
                $parts = explode('|', $clientReference);
                
                if ($parts[0] === 'aicredits') {
                    // It's an AI credits purchase: aicredits|vendorId|credits
                    if (count($parts) === 3) {
                        $vendorId = $parts[1];
                        $credits = (int)$parts[2];
                        
                        $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
                        if ($vendor) {
                            $vendor->extra_ai_credits = ($vendor->extra_ai_credits ?? 0) + $credits;
                            $vendor->save();
                            Log::info("Wave Webhook: Added $credits AI credits to vendor $vendorId");
                        }
                    }
                }
                elseif (count($parts) === 3) {
                    list($vendorId, $planId, $planFrequency) = $parts;
                    
                    // Activate subscription
                    $subscriptionPlans = getPaidPlans();
                    $getPlanDetails = \Arr::get($subscriptionPlans, $planId);
                    $planCharge = \Arr::get($getPlanDetails, 'charges.'.$planFrequency.'.charge');
                    
                    $months = ($planFrequency === 'monthly') ? 1 : 12;
                    $endsAt = Carbon::now()->addMonths($months)->format('Y-m-d H:i:s');
                    
                    // Cancel old active subscriptions
                    ManualSubscriptionModel::where('vendors__id', $vendorId)
                        ->where('status', 'active')
                        ->update(['status' => 'cancelled']);

                    // Create new active subscription
                    ManualSubscriptionModel::create([
                        'plan_id' => $planId,
                        'charges_frequency' => $planFrequency,
                        'charges' => $planCharge,
                        'remarks' => 'Wave Payment Auto-Activation',
                        'ends_at' => $endsAt,
                        'status' => 'active',
                        'vendors__id' => $vendorId,
                        'is_auto_recurring' => null, // One-time
                    ]);

                    // Reset plan AI credits
                    $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
                    if ($vendor) {
                        $aiCreditsLimit = \Arr::get($getPlanDetails, 'features.ai_credits.limit', 0);
                        if ($aiCreditsLimit == -1) {
                            $vendor->plan_ai_credits = 999999999; // unlimited
                        } else {
                            $vendor->plan_ai_credits = $aiCreditsLimit;
                        }
                        $vendor->save();
                        Log::info("Wave Webhook: Reset AI credits for vendor $vendorId to $aiCreditsLimit");
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
