<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/vendor/whatsapp/chat/unread-count', 'GET');
$response = $kernel->handle($request);
Auth::loginUsingId(17);
$data = app(\App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController::class)->unreadCount();
echo $data->getContent();
