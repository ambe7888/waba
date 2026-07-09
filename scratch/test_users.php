<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$users = \App\Models\User::get(['_id', 'email', 'user_roles__id', 'vendors__id']);
foreach($users as $u) {
    echo json_encode($u) . "\n";
}
