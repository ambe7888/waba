<?php

namespace Addons\WhatsJetCallingAddon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WhatsJetCallingAddonServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/Views', 'WhatsJetCallingAddon');

        // Register all routes for this addon
        $this->registerRoutes();

        // Inject calling scripts and styles to the main chat page dynamically
        view()->composer('whatsapp.chat', function ($view) {
            // We can push scripts or inject variables to the view
            $view->with('whatsjetCallingAddonActive', true);
        });
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
            Route::get('/manage/WhatsJetCallingAddon/setup', function () {
                return view('WhatsJetCallingAddon::setup');
            })->name('addon.WhatsJetCallingAddon.setup_view');

            Route::post('/manage/WhatsJetCallingAddon/setup-process', [
                \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
                'processSetup'
            ])->name('addon.WhatsJetCallingAddon.setup_process');
        });

        // Calling routes (Vendor Console / Vendor access checkpost)
        Route::middleware([
            'web',
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\VendorAccessCheckpost::class,
        ])->prefix('vendor-console')->group(function () {
            // Call Permission Request (Consent Template)
            Route::post('/calling/request-permission/{contactUid}', [
                \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
                'requestPermission'
            ])->name('addon.WhatsJetCallingAddon.request_permission');

            // Outbound Calling (Initiate call & send SDP)
            Route::post('/calling/initiate/{contactUid}', [
                \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
                'initiateCall'
            ])->name('addon.WhatsJetCallingAddon.initiate_call');

            // Inbound/Outbound call management (Accept/Hangup)
            Route::post('/calling/accept/{contactUid}', [
                \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
                'acceptCall'
            ])->name('addon.WhatsJetCallingAddon.accept_call');

            Route::post('/calling/terminate/{contactUid}', [
                \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
                'terminateCall'
            ])->name('addon.WhatsJetCallingAddon.terminate_call');
        });

        // Public route for calling assets (JS/Images)
        Route::get('/addons/WhatsJetCallingAddon/assets/{path}', [
            \Addons\WhatsJetCallingAddon\Controllers\CallingController::class,
            'assets'
        ])->where('path', '.*')->name('addon.WhatsJetCallingAddon.assets');
    }
}
