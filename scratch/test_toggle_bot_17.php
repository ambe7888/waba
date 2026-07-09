<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
Auth::loginUsingId(17);
$controller = app()->make(App\Yantrana\Components\Dashboard\Controllers\DashboardController::class);
echo json_encode($controller->toggleBotReply());
