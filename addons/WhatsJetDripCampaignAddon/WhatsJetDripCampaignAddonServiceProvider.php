<?php

namespace Addons\WhatsJetDripCampaignAddon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WhatsJetDripCampaignAddonServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Load the views from the addon's Views folder
        $this->loadViewsFrom(__DIR__ . '/Views', 'WhatsJetDripCampaignAddon');

        // Register all routes for this addon
        $this->registerRoutes();
    }

    /**
     * Register addon routes.
     */
    protected function registerRoutes()
    {
        // Setup routes (Central console / Admin only)
        Route::middleware([
            'web',
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\CentralAccessCheckpost::class,
        ])->prefix('central-console')->group(function () {
            Route::get('/manage/WhatsJetDripCampaignAddon/setup', function () {
                return view('WhatsJetDripCampaignAddon::setup');
            })->name('addon.WhatsJetDripCampaignAddon.setup_view');
        });

        // Vendor Console
        Route::middleware([
            'web',
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\VendorAccessCheckpost::class,
        ])->prefix('vendor-console')->group(function () {
            
            Route::get('/drip-campaigns', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'index'
            ])->name('addon.WhatsJetDripCampaignAddon.index');

            Route::post('/drip-campaigns/store', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'store'
            ])->name('addon.WhatsJetDripCampaignAddon.store');

            Route::post('/drip-campaigns/{campaignUid}/delete', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'deleteCampaign'
            ])->name('addon.WhatsJetDripCampaignAddon.delete_campaign');

            Route::get('/drip-campaigns/{campaignId}/builder', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'builder'
            ])->name('addon.WhatsJetDripCampaignAddon.builder');

            Route::post('/drip-campaigns/{campaignId}/step/store', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'storeStep'
            ])->name('addon.WhatsJetDripCampaignAddon.store_step');

            Route::post('/drip-campaigns/step/{stepUid}/delete', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'deleteStep'
            ])->name('addon.WhatsJetDripCampaignAddon.delete_step');

            Route::post('/drip-campaigns/step/{stepUid}/update', [
                \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
                'updateStep'
            ])->name('addon.WhatsJetDripCampaignAddon.update_step');
            
        });

        // Public route for calling assets (JS/Images)
        Route::get('/addons/WhatsJetDripCampaignAddon/assets/{path}', [
            \Addons\WhatsJetDripCampaignAddon\Controllers\DripCampaignController::class,
            'assets'
        ])->where('path', '.*')->name('addon.WhatsJetDripCampaignAddon.assets');
    }
}
