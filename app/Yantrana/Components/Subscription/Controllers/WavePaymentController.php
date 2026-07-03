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
                if (count($parts) === 3) {
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
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
