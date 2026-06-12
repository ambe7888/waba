<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Yantrana\Services\YesTokenAuth\YesTokenAuthFacade as YesTokenAuth;

$user = \App\Yantrana\Components\Auth\Models\AuthModel::first();
if (!$user) {
    die("No user found");
}

$_SERVER['HTTP_USER_AGENT'] = 'okhttp/4.12.0';
request()->headers->set('api-request-signature', 'mobile-app-request');

$authToken = YesTokenAuth::issueToken([
    'aud' => $user->_id,
    'uaid' => $user->user_authority_id,
]);

echo "GENERATED TOKEN: \n$authToken\n\n";

// Now try to verify the token EXACTLY as the middleware does
request()->headers->set('authorization', 'Bearer ' . $authToken);
request()->headers->set('User-Agent', 'okhttp/4.12.0');

$isVerified = YesTokenAuth::verifyToken();
echo "VERIFY RESULT: \n";
print_r($isVerified);

