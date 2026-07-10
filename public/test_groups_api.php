<?php
// Test rapide de la structure de réponse de l'API groupes
// Appel direct à la BD pour voir les données

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->bootstrap();

$vendorId = \App\Yantrana\Components\Contact\Models\ContactGroupModel::first()?->vendors__id;
$groups = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
    ->orderBy('created_at', 'desc')
    ->get()
    ->map(function ($g) {
        $count = \DB::table('group_contacts')
            ->where('contact_groups__id', $g->_id)
            ->count();
        return [
            '_uid' => $g->_uid,
            'title' => $g->title,
            'total_contacts' => $count,
        ];
    });

echo json_encode([
    'vendor_id' => $vendorId,
    'count' => count($groups),
    'groups' => $groups,
], JSON_PRETTY_PRINT);
