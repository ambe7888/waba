<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/vendor/contact/contacts-data', 'GET', ['assigned' => 'unassigned']);
$response = $kernel->handle($request);
Auth::loginUsingId(17);
$data = app(\App\Yantrana\Components\Contact\Controllers\ContactController::class)->prepareContactList();
print_r(array_keys($data));
