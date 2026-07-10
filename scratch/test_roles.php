<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());
$roles = DB::table('user_roles')->get();
foreach ($roles as $role) {
    echo $role->_id . " : " . $role->title . "\n";
}
