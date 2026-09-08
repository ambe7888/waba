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
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 */

/**
* WhatsAppCallController.php - Controller file
*
* Thin HTTP layer over WhatsAppApiService's calling methods. The actual
* WebRTC offer/answer SDP is built client-side (browser microphone + ICE
* gathering); this controller only relays it to Meta's Calling API and
* reports back whatever Meta returns (including its own error message,
* since baseApiRequest() throws on non-2xx and we want the vendor to see
* WHY a call failed, not a generic 500).
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use Illuminate\Http\Request;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;

class WhatsAppCallController extends BaseController
{
    /**
     * @var WhatsAppApiService
     */
    protected $whatsAppApiService;

    public function __construct(WhatsAppApiService $whatsAppApiService)
    {
        $this->whatsAppApiService = $whatsAppApiService;
    }

    /**
     * Confirm the caller is allowed to use voice calls: plan feature +
     * per-agent permission. Returns null when allowed, or an error
     * response to short-circuit the calling action.
     */
    protected function guardAccess()
    {
        if (!vendorPlanDetails('whatsapp_calling', 1)['is_limit_available']) {
            return $this->processResponse(3, [3 => __tr('Les appels vocaux WhatsApp ne sont pas inclus dans votre formule.')], ['message' => __tr('Les appels vocaux WhatsApp ne sont pas inclus dans votre formule.')]);
        }
        if (!hasVendorAccess('messaging', 'voice_calls')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }
        return null;
    }

    /**
     * Business-initiated call: send our SDP offer to a contact.
     */
    public function connect(Request $request)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $request->validate([
            'contactUid' => 'required|string',
            'sdp' => 'required|string',
        ]);

        $vendorId = getVendorId();
        $contact = ContactModel::where('vendors__id', $vendorId)
            ->where('_uid', $request->contactUid)
            ->first();

        if (!$contact || empty($contact->wa_id)) {
            return $this->processResponse(2, [2 => __tr('Contact introuvable.')], ['message' => __tr('Contact introuvable.')]);
        }

        try {
            $response = $this->whatsAppApiService->connectCall($contact->wa_id, $request->sdp, $vendorId);
        } catch (\Throwable $e) {
            return $this->processResponse(3, [3 => $e->getMessage()], ['message' => $e->getMessage()]);
        }

        return $this->processResponse(1, [], ['message' => __tr('Appel en cours...'), 'meta_response' => $response], true);
    }

    /**
     * Inbound call step 1: establish the WebRTC connection before media flows.
     */
    public function preAccept(Request $request)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $request->validate([
            'call_id' => 'required|string',
            'sdp' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppApiService->preAcceptCall($request->call_id, $request->sdp, getVendorId());
        } catch (\Throwable $e) {
            return $this->processResponse(3, [3 => $e->getMessage()], ['message' => $e->getMessage()]);
        }

        return $this->processResponse(1, [], ['message' => 'ok', 'meta_response' => $response], true);
    }

    /**
     * Inbound call step 2: accept the call, media starts flowing.
     */
    public function accept(Request $request)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $request->validate([
            'call_id' => 'required|string',
            'sdp' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppApiService->acceptCall($request->call_id, $request->sdp, getVendorId());
        } catch (\Throwable $e) {
            return $this->processResponse(3, [3 => $e->getMessage()], ['message' => $e->getMessage()]);
        }

        return $this->processResponse(1, [], ['message' => 'ok', 'meta_response' => $response], true);
    }

    /**
     * Reject an inbound call before accepting it.
     */
    public function reject(Request $request)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $request->validate([
            'call_id' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppApiService->rejectCall($request->call_id, getVendorId());
        } catch (\Throwable $e) {
            return $this->processResponse(3, [3 => $e->getMessage()], ['message' => $e->getMessage()]);
        }

        return $this->processResponse(1, [], ['message' => 'ok', 'meta_response' => $response], true);
    }

    /**
     * End an active call (inbound or outbound).
     */
    public function terminate(Request $request)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $request->validate([
            'call_id' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppApiService->terminateCall($request->call_id, getVendorId());
        } catch (\Throwable $e) {
            return $this->processResponse(3, [3 => $e->getMessage()], ['message' => $e->getMessage()]);
        }

        return $this->processResponse(1, [], ['message' => 'ok', 'meta_response' => $response], true);
    }
}
