<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$authEngine = app()->make(App\Yantrana\Components\Auth\AuthEngine::class);
$request = Illuminate\Http\Request::create('/api/user/login-process', 'POST', [
    'email' => 'ambeange8@gmail.com', // Admin
    'password' => 'password123', // I don't know the password
]);
// Let's just create a token to see what it contains
Auth::loginUsingId(1);
$user = Auth::user();
$token = YesTokenAuth::issueToken([
    'aud' => $user->_id,
    'uaid' => $user->user_authority_id,
]);
echo "Token: " . $token . "\n";
echo "Auth info: " . json_encode(getUserAuthInfo()) . "\n";
