<?php

namespace App\Http\Controllers;

use App\Yantrana\Base\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Hands a signed-in mobile user straight into the equivalent web page,
 * already authenticated.
 *
 * Subscribing and paying only exist on the web dashboard - the app has no
 * plan picker or checkout - so the app has to send people there. Without
 * this, that hand-off drops them on a login form: they have an app session,
 * not a web one, and being asked to sign in again mid-purchase is where
 * most of them would stop.
 *
 * The token is deliberately narrow:
 *  - random 48 chars, so it cannot be guessed;
 *  - valid 5 minutes, and consumed on first use, so a URL that leaks from
 *    a browser history or a shared screenshot is already dead;
 *  - it names only a destination *key*, never a URL, so this cannot be
 *    turned into an open redirect;
 *  - it carries the user id server-side only - nothing identifying travels
 *    in the link itself.
 *
 * It does create a real web session (unlike the embedded-signup bridge,
 * which uses Auth::onceUsingId for a single request) because the vendor
 * has to browse plans and complete a payment across several requests.
 */
class MobileWebBridgeController extends BaseController
{
    private const CACHE_PREFIX = 'mobile_web_bridge_';
    private const TTL_MINUTES = 5;

    /**
     * Destinations the app is allowed to ask for. Route names, resolved
     * here - never a URL supplied by the caller.
     */
    private const DESTINATIONS = [
        'subscription' => 'subscription.read.show',
        'subscription_history' => 'vendor.subscription.history',
    ];

    /**
     * API: mint a one-shot auto-login link for the calling vendor user.
     */
    public function issueLink(\Illuminate\Http\Request $request)
    {
        $destination = (string) $request->get('destination', 'subscription');
        if (!isset(self::DESTINATIONS[$destination])) {
            return $this->processResponse(2, [
                2 => __tr('Destination inconnue.'),
            ], [], true);
        }

        $userId = getUserID();
        if (!$userId) {
            return $this->processResponse(2, [
                2 => __tr('Session invalide.'),
            ], [], true);
        }

        $token = Str::random(48);
        Cache::put(self::CACHE_PREFIX . $token, [
            'users__id' => $userId,
            'destination' => $destination,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $this->processResponse(1, [], [
            'url' => route('mobile.web_bridge.consume', ['token' => $token]),
            'expires_in' => self::TTL_MINUTES * 60,
        ]);
    }

    /**
     * Web: consume the token, establish the session, and land the user on
     * the page they asked for.
     */
    public function consume($token)
    {
        $key = self::CACHE_PREFIX . $token;
        $payload = Cache::get($key);

        // Single use: burn it before doing anything else, so a replayed or
        // concurrently-opened link cannot mint a second session.
        Cache::forget($key);

        if (!$payload || empty($payload['users__id'])) {
            return redirect()->route('login')
                ->with('error', __tr('Ce lien a expiré. Rouvrez la page depuis l\'application.'));
        }

        $destination = self::DESTINATIONS[$payload['destination'] ?? 'subscription']
            ?? self::DESTINATIONS['subscription'];

        $user = Auth::loginUsingId($payload['users__id']);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __tr('Connexion impossible. Reconnectez-vous.'));
        }

        // Same protection a normal login gets: a fresh session id, so the
        // pre-login identifier cannot be reused.
        request()->session()->regenerate();

        Log::info('Mobile web bridge used', [
            'users__id' => $payload['users__id'],
            'destination' => $payload['destination'] ?? 'subscription',
        ]);

        return redirect()->route($destination);
    }
}
