<?php
require __DIR__ . '/../../vendor/autoload.class.php'; // Wait, it's Laravel, we can bootstrap it:
require __DIR__ . '/../bootstrap/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Instead of bootstrap, let's just make a simple cURL call with their WooCommerce settings:
$shopUrl = 'https://babishop.ci/';
$consumerKey = 'ck_d23628b78ecac5890085c35a9a2bbf0293c40ade';
$consumerSecret = 'cs_99be4713c71a396bb21e7d825c7cc68e1a8fa00d'; // Let's check the DB settings for the actual value!
