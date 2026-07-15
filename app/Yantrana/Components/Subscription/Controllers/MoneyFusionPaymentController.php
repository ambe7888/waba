<?php

namespace App\Yantrana\Components\Subscription\Controllers;

use App\Yantrana\Base\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Yantrana\Components\Subscription\Models\ManualSubscriptionModel;
use Carbon\Carbon;

class MoneyFusionPaymentController extends BaseController
{
    /**
     * Create Checkout Session for MoneyFusion Subscription
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan' => 'required',
            'numeroSend' => 'required|string',
            'nomclient' => 'required|string',
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

        $apiUrl = getAppSettings('moneyfusion_api_url');
        if (!getAppSettings('enable_moneyfusion') || !$apiUrl) {
            return redirect()->back()->with(['error' => __tr('MoneyFusion is not configured.')]);
        }

        $clientReference = $vendorId . '|' . $planId . '|' . $planFrequency;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($apiUrl, [
            'totalPrice' => (float) $planCharge,
            'article' => [
                [
                    "Abonnement " . $planId . " (" . $planFrequency . ")" => (float) $planCharge
                ]
            ],
            'personal_Info' => [
                [
                    'clientReference' => $clientReference,
                ]
            ],
            'numeroSend' => trim($request->numeroSend),
            'nomclient' => trim($request->nomclient),
            'return_url' => route('subscription.read.show', ['status' => 'success']),
            'webhook_url' => route('moneyfusion.webhook'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['statut']) && !empty($data['url'])) {
                return redirect($data['url']);
            }
            $errorMessage = $data['message'] ?? __tr('Failed to initiate MoneyFusion checkout.');
        } else {
            $errorMessage = __tr('HTTP request to MoneyFusion failed.');
        }

        Log::error('MoneyFusion Checkout Error', ['response' => $response->body()]);
        return redirect()->route('subscription.read.show')->with([
            'error' => $errorMessage
        ]);
    }

    /**
     * Create Checkout Session for MoneyFusion AI Credits
     */
    public function createAiCreditsCheckoutSession(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'credits' => 'required|numeric',
            'numeroSend' => 'required|string',
            'nomclient' => 'required|string',
        ]);

        $vendorId = getVendorId();
        $amount = $request->amount;
        $credits = $request->credits;
        
        $apiUrl = getAppSettings('moneyfusion_api_url');
        if (!getAppSettings('enable_moneyfusion') || !$apiUrl) {
            return redirect()->back()->with(['error' => __tr('MoneyFusion is not configured.')]);
        }

        $clientReference = 'aicredits|' . $vendorId . '|' . $credits;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($apiUrl, [
            'totalPrice' => (float) $amount,
            'article' => [
                [
                    "Recharge de " . $credits . " credits IA" => (float) $amount
                ]
            ],
            'personal_Info' => [
                [
                    'clientReference' => $clientReference,
                ]
            ],
            'numeroSend' => trim($request->numeroSend),
            'nomclient' => trim($request->nomclient),
            'return_url' => route('vendor.ai_credits.topup', ['status' => 'success']),
            'webhook_url' => route('moneyfusion.webhook'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['statut']) && !empty($data['url'])) {
                return redirect($data['url']);
            }
            $errorMessage = $data['message'] ?? __tr('Failed to initiate MoneyFusion checkout.');
        } else {
            $errorMessage = __tr('HTTP request to MoneyFusion failed.');
        }

        Log::error('MoneyFusion AI Credits Checkout Error', ['response' => $response->body()]);
        return redirect()->route('vendor.ai_credits.topup')->with([
            'error' => $errorMessage
        ]);
    }

    /**
     * Handle incoming webhooks from MoneyFusion
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('MoneyFusion Webhook received', $payload);

        $tokenPay = $payload['tokenPay'] ?? '';

        if (!$tokenPay) {
            return response()->json(['status' => 'error', 'message' => 'Missing tokenPay'], 400);
        }

        // Double-check: verify status directly from MoneyFusion API using GET request
        $verifyResponse = Http::get("https://www.pay.moneyfusion.net/paiementNotif/{$tokenPay}");
        if ($verifyResponse->failed()) {
            Log::error('MoneyFusion Webhook: Verification request failed', ['token' => $tokenPay]);
            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 400);
        }

        $verifyData = $verifyResponse->json();
        Log::info('MoneyFusion Webhook verified data', $verifyData);

        if (empty($verifyData['statut']) || empty($verifyData['data']) || $verifyData['data']['statut'] !== 'paid') {
            Log::warning('MoneyFusion Webhook: Payment not verified as paid', ['token' => $tokenPay, 'verifyData' => $verifyData]);
            return response()->json(['status' => 'error', 'message' => 'Payment status is not paid'], 400);
        }

        // Verified successfully! Process the payment activation
        $verifiedTransaction = $verifyData['data'];
        $personalInfo = $verifiedTransaction['personal_Info'] ?? [];
        if (is_string($personalInfo)) {
            $personalInfo = json_decode($personalInfo, true);
        }
        $clientReference = '';
        if (is_array($personalInfo)) {
            $firstInfo = $personalInfo[0] ?? $personalInfo;
            $clientReference = $firstInfo['clientReference'] ?? '';
        }

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
                        Log::info("MoneyFusion Webhook: Added $credits AI credits to vendor $vendorId");
                    }
                }
            } elseif (count($parts) === 3) {
                // It's a plan subscription: vendorId|planId|planFrequency
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
                    'remarks' => 'MoneyFusion Payment Auto-Activation',
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
                    Log::info("MoneyFusion Webhook: Reset AI credits for vendor $vendorId to $aiCreditsLimit");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
