<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configValue = encrypt(json_encode([
    'registration_id' => 'local-testing-id-12345',
    'email' => 'admin@admin.com',
    'licence' => 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9',
    'registered_at' => now(),
    'signature' => sha1('127.0.0.1:8000' . 'local-testing-id-12345' . '4.5+')
]));

DB::table('configurations')->updateOrInsert(
    ['name' => 'product_registration'],
    [
        'value' => $configValue, 
        'data_type' => 4,
        'created_at' => now(),
        'updated_at' => now()
    ]
);

echo "Licence forcee avec succes et valeur encryptee.\n";
