<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = \DB::table('vendor_settings')->where('vendors__id', 24)->get();
foreach ($settings as $setting) {
    echo "name: {$setting->name} | value: {$setting->value}\n";
}
