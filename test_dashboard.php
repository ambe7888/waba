<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$engine = app(\App\Yantrana\Components\Dashboard\DashboardEngine::class);
echo json_encode($engine->prepareVendorDashboardData(1));

