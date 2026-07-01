<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendorExportTest extends TestCase
{
    public function test_vendor_export_returns_csv_download(): void
    {
        $this->withoutMiddleware();

        $response = $this->get(route('central.vendors.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $response->assertHeaderContains('content-disposition', '.csv');
    }
}
