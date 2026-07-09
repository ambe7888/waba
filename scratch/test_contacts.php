<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
Auth::loginUsingId(17);
$controller = app()->make(App\Yantrana\Components\Contact\Controllers\ContactController::class);
$request = new \App\Yantrana\Support\CommonRequest();
$request->merge(['assigned_to' => 'to-me']);
$response1 = $controller->contactData($request);
$request->merge(['assigned_to' => 'unassigned']);
$response2 = $controller->contactData($request);

echo json_encode([
    'to_me' => $response1->getData(true)['data'] ?? [],
    'unassigned' => $response2->getData(true)['data'] ?? []
]);
