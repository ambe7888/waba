<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Direct file deletion in compiled views folder
$viewsPath = __DIR__ . '/../storage/framework/views';
$files = glob($viewsPath . '/*.php');
$deletedCount = 0;

foreach ($files as $file) {
    if (is_file($file)) {
        if (unlink($file)) {
            $deletedCount++;
        }
    }
}

echo "Views manually deleted: " . $deletedCount . " of " . count($files) . "\n";
echo "Files remaining in " . $viewsPath . ":\n";
foreach (glob($viewsPath . '/*.php') as $filename) {
    echo "- " . basename($filename) . "\n";
}
?>
