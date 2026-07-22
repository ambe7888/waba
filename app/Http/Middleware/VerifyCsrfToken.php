<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'stripe/*',
        'razorpay/*',
        'subscription/*',
        'whatsapp-webhook/*',
        'paystack/*',
        'yoomoney/*',
        'yoomoney/yoomoney-webhook-order-payment',
        'webhook/wave',
<<<<<<< HEAD
=======
        'webhook/moneyfusion',
>>>>>>> cbd36d040e200715c7cd741e355f6ca8ead310db
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            return __apiResponse([
                'message' => __tr('Token Expired, Please reload and try again.'),
                'auth_info' => getUserAuthInfo(5),
                'show_message' => true,
            ], 2);
        }
    }
}
