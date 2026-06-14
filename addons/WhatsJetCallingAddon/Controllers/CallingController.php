<?php

namespace Addons\WhatsJetCallingAddon\Controllers;

use Illuminate\Http\Request;
use App\Yantrana\Base\BaseController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\Configuration\Repositories\ConfigurationRepository;

class CallingController extends BaseController
{
    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * Constructor
     */
    public function __construct(
        ContactRepository $contactRepository,
        ConfigurationRepository $configurationRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * Process configuration setup for calling addon.
     */
    public function processSetup(Request $request)
    {
        $request->validate([
            'calling_template_name' => 'required|string',
            'calling_template_lang' => 'required|string|max:5',
        ]);

        $this->configurationRepository->storeOrUpdate([
            [
                'name' => 'whatsjet_calling_template_name',
                'value' => $request->get('calling_template_name'),
                'data_type' => 1,
            ],
            [
                'name' => 'whatsjet_calling_template_lang',
                'value' => $request->get('calling_template_lang'),
                'data_type' => 1,
            ]
        ]);

        return $this->processResponse(1, [], [
            'show_message' => true,
            'message' => __tr('Paramètres de l\'addon d\'appel enregistrés avec succès.'),
            'reloadPage' => true,
        ], true);
    }

    /**
     * Send Call Permission Request Template to the customer.
     */
    public function requestPermission($contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->fetchIt($contactUid);

        if (__isEmpty($contact)) {
            return $this->processResponse(2, [], ['message' => __tr('Contact introuvable.')], true);
        }

        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);
        $phoneNumberId = getVendorSettings('current_phone_number_id', null, null, $vendorId);
        
        $templateName = getAppSettings('whatsjet_calling_template_name', 'call_permission_request');
        $templateLang = getAppSettings('whatsjet_calling_template_lang', 'fr');

        if (!$accessToken || !$phoneNumberId) {
            return $this->processResponse(2, [], ['message' => __tr('Paramètres WhatsApp Cloud API non configurés.')], true);
        }

        // Send the template request using standard HTTP client to Meta Graph API
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $contact->wa_id,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $templateLang
                    ]
                ]
            ]);

        if ($response->failed()) {
            \Log::error('Meta Call Permission Request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $errorData = $response->json();
            $errorMsg = data_get($errorData, 'error.message') ?: data_get($errorData, 'error_description') ?: __tr('Erreur Meta (Code __status__): __body__', [
                '__status__' => $response->status(),
                '__body__' => substr($response->body(), 0, 150)
            ]);
            return $this->processResponse(2, [], ['message' => $errorMsg], true);
        }

        // Update contact call permission status to requested
        $contactData = $contact->__data ?? [];
        $contactData['call_permission_status'] = 'requested';
        $contactData['call_permission_requested_at'] = now()->toDateTimeString();

        $this->contactRepository->updateIt($contact, [
            '__data' => $contactData
        ]);

        // Broadcast model updates to instantly reflect in UI
        updateModelsViaVendorBroadcast(getVendorUid(), [
            'contact' => $contact
        ]);

        return $this->processResponse(1, [], [
            'show_message' => true,
            'message' => __tr('Demande d\'autorisation d\'appel envoyée avec succès.'),
        ], true);
    }

    /**
     * Initiate call and send SDP Offer.
     */
    public function initiateCall(Request $request, $contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->fetchIt($contactUid);
        $sdp = $request->get('sdp');

        if (__isEmpty($contact)) {
            return $this->processResponse(2, [], ['message' => __tr('Contact introuvable.')], true);
        }

        if (!$sdp) {
            return $this->processResponse(2, [], ['message' => __tr('Offre SDP manquante.')], true);
        }

        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);
        $phoneNumberId = getVendorSettings('current_phone_number_id', null, null, $vendorId);

        if (!$accessToken || !$phoneNumberId) {
            return $this->processResponse(2, [], ['message' => __tr('Paramètres WhatsApp Cloud API non configurés.')], true);
        }

        // Call Meta graph calling endpoint
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/calls", [
                'messaging_product' => 'whatsapp',
                'to' => $contact->wa_id,
                'direction' => 'outbound',
                'sdp' => $sdp
            ]);

        if ($response->failed()) {
            \Log::error('Meta Call initiation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $errorData = $response->json();
            $errorMsg = data_get($errorData, 'error.message') ?: data_get($errorData, 'error_description') ?: __tr('Erreur d\'appel Meta (Code __status__): __body__', [
                '__status__' => $response->status(),
                '__body__' => substr($response->body(), 0, 150)
            ]);
            return $this->processResponse(2, [], ['message' => $errorMsg], true);
        }

        return $this->processResponse(1, [
            'call_id' => $response->json('call_id'),
            'sdp' => $response->json('sdp')
        ], [], true);
    }

    /**
     * Accept call and send SDP Answer.
     */
    public function acceptCall(Request $request, $contactUid)
    {
        $vendorId = getVendorId();
        $callId = $request->get('call_id');
        $sdp = $request->get('sdp');

        if (!$callId || !$sdp) {
            return $this->processResponse(2, [], ['message' => __tr('Paramètres d\'appel manquants.')], true);
        }

        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);
        $phoneNumberId = getVendorSettings('current_phone_number_id', null, null, $vendorId);

        // Send accept SDP to Meta API
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/calls/{$callId}/accept", [
                'messaging_product' => 'whatsapp',
                'sdp' => $sdp
            ]);

        if ($response->failed()) {
            \Log::error('Meta Call accept failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $errorData = $response->json();
            $errorMsg = data_get($errorData, 'error.message') ?: data_get($errorData, 'error_description') ?: __tr('Erreur Meta (Code __status__): __body__', [
                '__status__' => $response->status(),
                '__body__' => substr($response->body(), 0, 150)
            ]);
            return $this->processResponse(2, [], ['message' => $errorMsg], true);
        }

        return $this->processResponse(1, [], [], true);
    }

    /**
     * Terminate call.
     */
    public function terminateCall(Request $request, $contactUid)
    {
        $vendorId = getVendorId();
        $callId = $request->get('call_id');

        if (!$callId) {
            return $this->processResponse(2, [], ['message' => __tr('ID d\'appel manquant.')], true);
        }

        $accessToken = getVendorSettings('whatsapp_access_token', null, null, $vendorId);
        $phoneNumberId = getVendorSettings('current_phone_number_id', null, null, $vendorId);

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/calls/{$callId}/terminate", [
                'messaging_product' => 'whatsapp'
            ]);

        if ($response->failed()) {
            \Log::error('Meta Call terminate failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $errorData = $response->json();
            $errorMsg = data_get($errorData, 'error.message') ?: data_get($errorData, 'error_description') ?: __tr('Erreur Meta (Code __status__): __body__', [
                '__status__' => $response->status(),
                '__body__' => substr($response->body(), 0, 150)
            ]);
            return $this->processResponse(2, [], ['message' => $errorMsg], true);
        }

        return $this->processResponse(1, [], [], true);
    }

    /**
     * Serve calling assets (like calling.js) directly from addon.
     */
    public function assets($path)
    {
        $safePath = str_replace(['..', '\\'], ['', '/'], $path);
        $filePath = dirname(__DIR__) . '/assets/' . $safePath;

        if (!file_exists($filePath)) {
            abort(404);
        }

        $mimeType = 'text/plain';
        if (str_ends_with($filePath, '.js')) {
            $mimeType = 'application/javascript';
        } elseif (str_ends_with($filePath, '.css')) {
            $mimeType = 'text/css';
        } elseif (str_ends_with($filePath, '.png')) {
            $mimeType = 'image/png';
        }

        return Response::file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    }
}
