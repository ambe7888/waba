<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());
Auth::loginUsingId(17);
echo "VendorID for 17 (Admin): " . getVendorId() . "\n";
$controller = app()->make(App\Yantrana\Components\Dashboard\Controllers\DashboardController::class);
$res = $controller->toggleBotReply();
print_r($res->getData(true));
