<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/vendor/support-tickets/b8da9333-92de-4c62-b540-ecfae93f7176', 'GET');
$request->headers->set('Accept', 'application/json');

// We need to login a user
$user = App\Yantrana\Components\Auth\Models\AuthModel::find(1);
Auth::login($user);

$response = $kernel->handle($request);
echo "STATUS: " . $response->getStatusCode() . "\n";
echo "BODY: " . $response->getContent() . "\n";
