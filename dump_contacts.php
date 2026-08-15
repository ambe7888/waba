<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Auth::loginUsingId(79);
$engine = app(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
$response = $engine->contactsData();
file_put_contents('dump_contacts.json', json_encode(['reaction' => $response->reaction(), 'data' => $response->data()], JSON_PRETTY_PRINT));
