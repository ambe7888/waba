<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Since we want to test exactly what the mobile app does, let's just make cURL requests to the local server
$ch = curl_init('https://wb.4adev.com/api/user/login-process');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@test.com', 'password' => 'password'])); // We don't know the user's password, so maybe we bypass this by creating a mock token
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Api-Request-Signature: mobile-app-request'
]);
$response = curl_exec($ch);
echo "LOGIN RESPONSE: " . $response . "\n";
