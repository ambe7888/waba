<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Yantrana\Components\BotReply\Controllers\BotReplyController;
use Illuminate\Support\Facades\Auth;

Auth::loginUsingId(2); // Assuming vendor admin is ID 2

$controller = app(BotReplyController::class);
$response = $controller->apiIndex();

echo json_encode($response, JSON_PRETTY_PRINT);
