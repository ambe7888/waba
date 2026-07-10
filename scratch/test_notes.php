<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

Illuminate\Support\Facades\Auth::loginUsingId(17);
$request = Illuminate\Http\Request::create('/api/vendor/whatsapp/contact/chat/update-notes', 'POST', [
    'contactIdOrUid' => '1d62c7dc-eb72-46a2-9bd6-de34b2fde6d8',
    'contact_notes' => 'Test Notes via script'
]);
$response = $kernel->handle($request);
echo $response->getContent();
