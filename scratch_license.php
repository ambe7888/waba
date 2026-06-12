<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$config = DB::table('configurations')->where('name', 'product_registration')->first();
if ($config) {
    print_r(json_decode($config->value, true));
} else {
    echo "product_registration not found in DB\n";
}
