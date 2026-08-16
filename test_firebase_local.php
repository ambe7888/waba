<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = new Google\Client();
$c->setAuthConfig(config('firebase.credentials'));
$c->addScope('https://www.googleapis.com/auth/datastore');
try {
    print_r($c->fetchAccessTokenWithAssertion());
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
