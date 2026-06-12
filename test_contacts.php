<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$user = \App\Yantrana\Components\Auth\Models\AuthModel::where('vendors__id', '!=', null)->first();
\Auth::loginUsingId($user->_id);

$c = app()->make(\App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController::class);
echo $c->apiGetTemplateList()->getContent();
