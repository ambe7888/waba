<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Yantrana\Components\ECommerce\ECommerceEngine;

class SyncXmlFeedCatalogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:sync-xml-feeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product catalogs for every vendor whose e-commerce integration is set to an XML feed';

    /**
     * Execute the console command.
     */
    public function handle(ECommerceEngine $eCommerceEngine)
    {
        $vendorIds = DB::table('vendor_settings')
            ->where('name', 'ecommerce_integration')
            ->where('value', 'xml_feed')
            ->pluck('vendors__id');

        $this->info("Found {$vendorIds->count()} vendor(s) with an XML feed integration. Starting sync...");

        foreach ($vendorIds as $vendorId) {
            $result = $eCommerceEngine->syncProducts($vendorId, 'xml_feed');
            $status = ($result['reaction_code'] ?? null) == 1 ? 'OK' : 'FAILED';
            $this->line("Vendor {$vendorId}: {$status} -- " . ($result['message'] ?? ''));
        }

        $this->info('XML feed sync completed.');
    }
}
