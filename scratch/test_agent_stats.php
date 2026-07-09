<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
Auth::loginUsingId(7);
$eng = app()->make(App\Yantrana\Components\Dashboard\DashboardEngine::class);
echo json_encode($eng->prepareVendorDashboardData());
