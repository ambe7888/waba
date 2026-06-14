<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('wa_id', '22589304502')->first();
if ($contact) {
    echo "Contact WA_ID: {$contact->wa_id}\n";
    print_r($contact->__data);
} else {
    echo "Contact not found.\n";
}
