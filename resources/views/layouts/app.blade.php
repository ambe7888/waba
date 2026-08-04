@php
$hasActiveLicense = true;
$bypassLicense = env('BYPASS_LICENSE', true);
if(!$bypassLicense) {
    if(isLoggedIn() and (request()->route()->getName() != 'manage.configuration.product_registration') and
    (!getAppSettings('product_registration', 'registration_id') or sha1(array_get($_SERVER, 'HTTP_HOST', '') .
    getAppSettings('product_registration', 'registration_id') . '4.5+') !== getAppSettings('product_registration',
    'signature'))) {
        $hasActiveLicense = false;
        if(hasCentralAccess()) {
            header("Location: " . route('manage.configuration.product_registration'));
            exit;
        }
    }
}
$currentAppTheme ='';
 // Default theme from settings
 $currentAppTheme = getUserAppTheme()

@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION }}" lw-theme-mode="{{ $currentAppTheme }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {!! (isset($title) and $title) ? $title : __tr('Welcome') !!} - {{ getAppSettings('name') }}</title>
    <!-- Light Theme Favicon -->
    <link href="{{getAppSettings('favicon_image_url') }}" rel="icon">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#2dce89">
    <link rel="apple-touch-icon" href="{{ getAppSettings('favicon_image_url') }}">
    <!-- iOS/Safari PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ getAppSettings('name') }}">

    <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=TikTok+Sans:opsz,wght@12..36,300..900&display=swap" rel="stylesheet">
    @stack('head')
    {!! __yesset(
    [
    // Icons
    'static-assets/packages/fontawesome/css/all.css',
    'dist/css/common-vendorlibs.css',
    'dist/css/vendorlibs.css',
    'argon/css/argon.min.css',
    'dist/css/app.css',
    // 'dist/css/dark-theme.css'
    ]) !!}
        <!-- App Theme Change -->
        @if($currentAppTheme=='dark' ) 
        <link rel="stylesheet" href="{{ __yesset('dist/css/dark-theme.css')}}">
        <!-- Dark Theme Favicon -->
        <link href="{{getAppSettings('dark_theme_favicon_image_url') }}" rel="icon" media="(prefers-color-scheme: dark)">
        @elseif( $currentAppTheme=='system_default')
        <link rel="stylesheet" href="{{ __yesset('dist/css/dark-theme.css')}}"  media="(prefers-color-scheme: dark)">
        @endif
        <!-- /App Theme Change -->

    {{-- custom app css --}}
    <link href="{{ route('app.load_custom_style') }}" rel="stylesheet"  />
    @if(getAppSettings('page_head_code'))
    {!! getAppSettings('page_head_code') !!}
    @endif
    <style>
        /* Sidebar must stay above main-content elements (.navbar-top z-index:3, .header position:relative) */
        .navbar-vertical.fixed-left.lw-sidebar-container {
            z-index: 5 !important;
        }
        @media (min-width: 768px) {
            body:not(.lw-minimized-menu):not(.lw-guest-page) .main-content {
                margin-left: 250px !important;
                width: calc(100% - 250px) !important;
            }
            body.lw-minimized-menu:not(.lw-guest-page) .main-content {
                margin-left: 60px !important;
                width: calc(100% - 60px) !important;
            }
            body.lw-guest-page .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            #sidenav-collapse-main, .navbar-collapse {
                background-color: #ffffff !important;
                opacity: 1 !important;
            }
            .lw-app-theme-dark #sidenav-collapse-main, .lw-app-theme-dark .navbar-collapse {
                background-color: #172b4d !important;
            }
        }
    </style>
</head>

<body
    class="p-0 @if(hasVendorAccess() or hasVendorUserAccess()) lw-minimized-menu @endif @if(isLoggedIn()) lw-authenticated-page @else lw-guest-page @endif {{ $class ?? '' }} lw-app-theme-{{ $currentAppTheme }}"
    x-cloak
    x-data="{disableSoundForMessageNotification:{{ getVendorSettings('is_disabled_message_sound_notification') ? 1 : 0 }},unreadMessagesCount:null}">
    @auth()
    @include('layouts.navbars.sidebar')
    @endauth

    <div class="main-content">
        <!-- Sleek Top Progress Line -->
        <div id="top-progress-bar" style="position: fixed; top: 0; left: 0; width: 0%; height: 3px; background: linear-gradient(90deg, #25D366, #10b981, #2563eb); box-shadow: 0 0 10px rgba(37, 211, 102, 0.7); z-index: 999999; transition: width 0.4s ease, opacity 0.3s ease; pointer-events: none; opacity: 0;"></div>
        <script>
            window.addEventListener('beforeunload', function() {
                var bar = document.getElementById('top-progress-bar');
                if (bar) {
                    bar.style.opacity = '1';
                    bar.style.width = '75%';
                }
            });
            window.addEventListener('pageshow', function() {
                var bar = document.getElementById('top-progress-bar');
                if (bar) {
                    bar.style.width = '100%';
                    setTimeout(function() {
                        bar.style.opacity = '0';
                        setTimeout(function() { bar.style.width = '0%'; }, 350);
                    }, 150);
                }
            });
        </script>
        <!-- /Sleek Top Progress Line -->

        @include('layouts.navbars.navbar')

        {{-- Full-Width Top E-Commerce Style Server Maintenance Announcement Bar --}}
        @if (getAppSettings('enable_server_maintenance_notice'))
        <div class="alert alert-dismissible fade show mb-0 border-0 shadow-sm rounded-0 w-100 py-2.5 px-4" role="alert" style="background: linear-gradient(90deg, #c2410c 0%, #ea580c 50%, #f97316 100%) !important; color: #ffffff !important; z-index: 1050; position: relative;">
            <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap">
                <div class="d-flex align-items-center py-1 flex-grow-1 mr-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.22); color: white; flex-shrink: 0;">
                        <i class="fas fa-server" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="text-white">
                        <strong style="font-size: 0.9rem; letter-spacing: 0.03em;" class="mr-2">
                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i> {{ __tr('MIGRATION & MAINTENANCE SERVEUR PRÉVUE :') }}
                        </strong>
                        <span style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.95); font-weight: 500;">
                            {!! getAppSettings('server_maintenance_notice_message') ?: __tr('Une migration importante de nos serveurs est programmée ce soir entre Minuit (00h00) et 06h00 du matin. Des perturbations temporaires de la plateforme peuvent survenir pendant cette période.') !!}
                        </span>
                    </div>
                </div>
                <button type="button" class="close text-white position-relative p-0 m-0" data-dismiss="alert" aria-label="Close" style="font-size: 1.3rem; outline: none; opacity: 0.95; line-height: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        @endif

        @if(isDemo())
         <div class="lw-alert-dismissible-container top-3">
             <h3 class="alert alert-danger text-center text-white">
                <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                 {{ __tr('Please Note: We sell this script only through CodeCanyon only, Click following links to purchase') }} -> <strong><a class="text-white" target="_blank"
                    href="https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362">
                    {{  __tr('Regular License') }}</strong> | <strong><a class="text-white" target="_blank"
                    href="https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362?license=extended">
                    {{  __tr('Extended License') }}
                </a></strong>
                </h3>
        </div>
        @endif
        @if ($hasActiveLicense)
        @if(hasVendorAccess())
        <div class="lw-alert-dismissible-container top-6">
            <div class="text-center">
                    @php
                    $vendorPlanDetails = vendorPlanDetails(null, null, getVendorId());
                    @endphp
                    @if(!$vendorPlanDetails->hasActivePlan())
                    <div class="alert alert-danger">
                        <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                        {{ $vendorPlanDetails->message }} - <a class="text-white text-underline"
                        href="{{ route('subscription.read.show') }}">
                        {{ __tr('Go to My Subscription') }}
                    </a>
                    </div>
                    @elseif($vendorPlanDetails->is_expiring)
                    <div class="alert alert-warning">
                        <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                        {{ __tr('Your subscription plan is expiring on __endAt__', [
                        '__endAt__' => formatDate($vendorPlanDetails->ends_at)
                        ]) }} - 
                        '<a class="text-white text-underline"
                        href="{{ route('subscription.read.show') }}">
                        {{ __tr('Go to My Subscription') }}
                    </a>'
                    </div>
                    @endif
                </div>
        </div>
        @endif
        @yield('content')
        @else
        <div class="container">
            <div class="row">
                <div class="col-12 my-5 py-5 text-center">
                    <div class="card my-5 p-5">
                        <i class="fas fa-exclamation-triangle fa-6x mb-4 text-warning"></i>
                        <div class="alert alert-danger my-5">
                            {{ __tr('Product has not been verified yet, please contact via profile or product page.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @guest()
    @include('layouts.footers.guest')
    @endguest
    <?= __yesset(['dist/js/common-vendorlibs.js','dist/js/vendorlibs.js', 'argon/bootstrap/dist/js/bootstrap.bundle.min.js', 'argon/js/argon.js', 'dist/push-js/push.min.js'], true) ?>
    @stack('js')
    @if (hasVendorAccess() or hasVendorUserAccess())
    <style>
    /* QR Code Modal Pro-Max Styling */
    #lwScanMeDialog .modal-content {
        border: none !important;
        border-radius: 20px !important;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.18) !important;
        overflow: hidden !important;
        background: #ffffff !important;
    }
    #lwScanMeDialog .modal-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        color: #ffffff !important;
        padding: 1.25rem 1.75rem !important;
        border-bottom: none !important;
    }
    #lwScanMeDialog .modal-title {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 1.15rem !important;
    }
    #lwScanMeDialog .close {
        color: #ffffff !important;
        opacity: 0.9 !important;
        transition: transform 0.2s ease;
    }
    #lwScanMeDialog .close:hover {
        transform: scale(1.1);
        opacity: 1 !important;
    }
    .lw-qr-card-container {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 18px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    .lw-qr-card-container:hover {
        border-color: #cbd5e1;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .lw-qr-image-wrapper {
        background: #ffffff;
        display: inline-block;
        padding: 16px;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .lw-qr-image-wrapper:hover {
        transform: scale(1.02);
        box-shadow: 0 12px 35px rgba(16, 185, 129, 0.15);
    }
    .lw-qr-image-wrapper img {
        max-width: 220px;
        height: auto;
        display: block;
    }
    .lw-qr-phone-badge {
        display: inline-block;
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        font-weight: 700;
        font-size: 1.1rem;
        padding: 6px 18px;
        border-radius: 20px;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .lw-qr-input-group {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1.5px solid #cbd5e1;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .lw-qr-input-group:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    .lw-qr-input-group .form-control {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        font-size: 0.88rem;
        color: #334155;
    }
    .lw-qr-btn-copy {
        border: none !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.82rem !important;
        padding: 0 16px !important;
        transition: all 0.2s ease !important;
    }
    .lw-qr-btn-copy:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
    }
    .lw-qr-btn-wa {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        padding: 8px 18px !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25) !important;
        transition: all 0.2s ease !important;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
    }
    .lw-qr-btn-wa:hover {
        color: #ffffff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35) !important;
    }
    #lwScanMeDialog .modal-footer {
        background: #f8fafc !important;
        border-top: 1px solid #f1f5f9 !important;
        padding: 1rem 1.75rem !important;
    }
    </style>

    {{-- QR CODE model --}}
    <x-lw.modal id="lwScanMeDialog" :header="__tr('Scan QR Code to Start Chat')">
        @if (getVendorSettings('current_phone_number_number'))
        <div class="alert text-center py-2 px-3 mb-4" style="border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-weight: 600; font-size: 0.88rem;">
            <i class="fas fa-qrcode mr-2"></i> {{ __tr('You can use following QR Codes to invite people to get connect with you on this platform.') }}
        </div>
        @if (!empty(getVendorSettings('whatsapp_phone_numbers')))
        @foreach (getVendorSettings('whatsapp_phone_numbers') as $whatsappPhoneNumber)
        <div class="lw-qr-card-container text-center">
            @if(!empty($whatsappPhoneNumber['verified_name']))
            <h4 class="font-weight-700 text-dark mb-3" style="font-size: 1.1rem; color: #1e293b;">{{ $whatsappPhoneNumber['verified_name'] }}</h4>
            @endif

            <div class="lw-qr-image-wrapper mb-3">
                <img class="lw-qr-image" src="{{ route('vendor.whatsapp_qr', [
                    'vendorUid' => getVendorUid(),
                    'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
                ]) }}" alt="QR Code">
            </div>

            <div class="mb-4">
                <div class="text-muted small font-weight-600 mb-1" style="font-size: 0.8rem;">{{ __tr('Phone Number') }}</div>
                <div class="lw-qr-phone-badge">
                    <i class="fab fa-whatsapp mr-1"></i> {{ $whatsappPhoneNumber['display_phone_number'] }}
                </div>
            </div>

            <!-- URL for QR Image -->
            <div class="form-group text-left mb-3">
                <label class="form-label font-weight-700 text-dark small mb-1" style="font-size: 0.83rem;">{{ __tr('URL for QR Image:') }}</label>
                <div class="input-group lw-qr-input-group">
                    <input type="text" class="form-control" readonly id="lwWhatsAppQRImage{{ $loop->index }}" value="{{ route('vendor.whatsapp_qr', [
                        'vendorUid' => getVendorUid(),
                        'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
                    ]) }}">
                    <div class="input-group-append">
                        <button class="btn lw-qr-btn-copy" type="button" onclick="lwCopyToClipboard('lwWhatsAppQRImage{{ $loop->index }}')">
                            <i class="far fa-copy mr-1"></i> {{ __tr('Copy') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- WhatsApp URL -->
            <div class="form-group text-left mb-0">
                <label class="form-label font-weight-700 text-dark small mb-1" style="font-size: 0.83rem;">{{ __tr('WhatsApp URL:') }}</label>
                <div class="input-group lw-qr-input-group">
                    <input type="text" class="form-control" readonly id="lwWhatsAppUrl{{ $loop->index }}"
                        value="https://wa.me/{{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}">
                    <div class="input-group-append">
                        <button class="btn lw-qr-btn-copy" type="button" onclick="lwCopyToClipboard('lwWhatsAppUrl{{ $loop->index }}')">
                            <i class="far fa-copy mr-1"></i> {{ __tr('Copy') }}
                        </button>
                        <a class="btn lw-qr-btn-wa" target="_blank"
                            href="https://api.whatsapp.com/send?phone={{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}">
                            <i class="fab fa-whatsapp mr-1.5" style="font-size: 1.1rem;"></i> {{ __tr('WhatsApp Now') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @else
        <div class="alert alert-info text-center py-3" style="border-radius: 12px;">{{ __tr('Please resync phone numbers.') }}</div>
        @endif
        @else
        <div class="alert alert-warning text-center py-3" style="border-radius: 12px;">
            {{ __tr('Phone number does not configured yet.') }}
        </div>
        @endif
    </x-lw.modal>
    {{-- /QR CODE model --}}
    <template x-if="!disableSoundForMessageNotification">
        <audio id="lwMessageAlertTone">
            <source src="<?= asset('/static-assets/audio/whatsapp-notification-tone.mp3'); ?>" type="audio/mpeg">
        </audio>
    </template>

    {{-- Global View Stack --}}
    @stack('globalViewsStack')
    {{-- /Global View Stack --}}

    @endif
    <script>
        (function($) {
            'use strict';
            window.appConfig = {
                debug: "{{ config('app.debug') }}",
                csrf_token: "{{ csrf_token() }}",
                locale : '{{ app()->getLocale() }}',
                vendorUid : '{{ getVendorUid() }}',
                broadcast_connection_driver: "{{ getAppSettings('broadcast_connection_driver') }}",
                hide_contact_phone_number: "{{ hasVendorAccess('hide_contact_phone_numbers') }}",
                pusher : {
                    key : "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster : "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    host : "{{ config('broadcasting.connections.pusher.options.host') }}",
                    port : "{{ config('broadcasting.connections.pusher.options.port') }}",
                    useTLS : "{{ config('broadcasting.connections.pusher.options.useTLS') }}",
                    encrypted : "{{ config('broadcasting.connections.pusher.options.encrypted') }}",
                    authEndpoint : "{{ url('/broadcasting/auth') }}"
                },
            }
        })(jQuery);
    </script>
    <?= __yesset(
        [
            'dist/js/jsware.js',
            'dist/js/app.js',
            // keep it last
            'dist/js/alpinejs.min.js',
        ],
        true,
    ) ?>
    @if(hasVendorAccess() or hasVendorUserAccess())
    {{-- app bootstrap --}}
    {!! __yesset('dist/js/bootstrap.js', true) !!}
    @endif
    @stack('vendorLibs')
    <script src="{{ route('vendor.load_server_compiled_js') }}"></script>
    @stack('footer')
    @stack('appScripts')
    <script>
        (function($) {
        'use strict';
        @if (session('alertMessage'))
            showAlert("{{ session('alertMessage') }}", "{{ session('alertMessageType') ?? 'info' }}");
            @php
                session('alertMessage', null);
                session('alertMessageType', null);
            @endphp
        @endif
        @php
        $isRestrictedVendorUser = (!hasVendorAccess() ? hasVendorAccess('assigned_chats_only') : false);
        @endphp
        var isRestrictedVendorUser = {{ $isRestrictedVendorUser ? 1 : 0 }},
            loggedInUserId = '{{ getUserId() }}';
        __Utils.setTranslation({
            'processing': "{{ __tr('processing') }}",
            'uploader_default_text': "<span class='filepond--label-action'>{!! __tr('Drag & Drop Files or Browse') !!}</span>",
            "confirmation_yes": "{{ __tr('Yes') }}",
            "confirmation_no": "{{ __tr('No') }}",
            "whatsapp_delete_msg_text": "{{ __tr('Messages before __date__ will be deleted') }}"
        });
        /* filepond translations */
        if(window['FilePond']) {
            FilePond.setOptions({
                labelInvalidField: '{{ __tr("Field contains invalid files") }}',
                labelFileWaitingForSize: '{{ __tr("Waiting for size") }}',
                labelFileSizeNotAvailable: '{{ __tr("Size not available") }}',
                labelFileCountSingular: '{{ __tr("file in list") }}',
                labelFileCountPlural: '{{ __tr("files in list") }}',
                labelFileLoading: '{{ __tr("Loading") }}',
                labelFileAdded: '{{ __tr("Added") }}',
                labelFileLoadError: '{{ __tr("Error during load") }}',
                labelFileRemoved: '{{ __tr("Removed") }}',
                labelFileRemoveError: '{{ __tr("Error during remove") }}',
                labelFileProcessing: '{{ __tr("Uploading") }}',
                labelFileProcessingComplete: '{{ __tr("Upload complete") }}',
                labelFileProcessingAborted: '{{ __tr("Upload cancelled") }}',
                labelFileProcessingError: '{{ __tr("Error during upload") }}',
                labelFileProcessingRevertError: '{{ __tr("Error during revert") }}',
                labelTapToCancel: '{{ __tr("tap to cancel") }}',
                labelTapToRetry: '{{ __tr("tap to retry") }}',
                labelTapToUndo: '{{ __tr("tap to undo") }}',
                labelButtonRemoveItem: '{{ __tr("Remove") }}',
                labelButtonAbortItemLoad: '{{ __tr("Cancel") }}',
                labelButtonRetryItemLoad: '{{ __tr("Retry") }}',
                labelButtonAbortItemProcessing: '{{ __tr("Cancel") }}',
                labelButtonUndoItemProcessing: '{{ __tr("Undo") }}',
                labelButtonRetryItemProcessing: '{{ __tr("Retry") }}',
                labelButtonProcessItem: '{{ __tr("Upload") }}'
            });
        };
        // push notification
        if (!Push.Permission.has()) {
            Push.Permission.request();
        }
        // register service worker for push notifications (unified with PWA sw.js)
        navigator.serviceWorker.register('/sw.js');
        // check if the window tab is active
        var isWindowTabActive = true;
        $(window).on("blur focus", function(e) {
            var prevType = $(this).data("prevType");
            //  reduce double fire issues
            if (prevType != e.type) {
                switch (e.type) {
                    case "blur":
                        isWindowTabActive = false;
                        break;
                    case "focus":
                        isWindowTabActive = true;
                        break;
                };
            };
            $(this).data("prevType", e.type);
        });

        @if(hasVendorAccess() or hasVendorUserAccess())
            var broadcastActionDebounce,
                campaignActionDebounce,
                lastEventData,
                lastCampaignStatus,
                demoNumbers,
                isUnreadRequestInProgress = false;
                function arrayContains(arr, item) {
                    // Handle case where arr is null/undefined
                    if (arr == null) return false;
                    // Use the most compatible iteration method
                    var length = arr.length;
                    for (var i = 0; i < length; i++) {
                        if (arr[i] == item) {  // Loose equality comparison
                        return true;
                        }
                    }
                    return false;
                    }
                @if(isDemo())
                demoNumbers = @json(array_unique(array_filter(array_merge([config('__misc.demo_test_recipient_contact_number')], session('__demoAccountTestPhoneNumbers') ?: []))));
                @endif
            window.Echo.private(`vendor-channel.${window.appConfig.vendorUid}`).listen('.VendorChannelBroadcast', function (data) {
                // if the event data matched does not need to process it
                if(_.isEqual(lastEventData, data)) {
                    return true;
                }
                @if(isDemo())
                // prevent for other demo numbers to process
                    if(data.contactWaId && !arrayContains(demoNumbers, data.contactWaId)) {
                        return true;
                    }
                @endif
                if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                    // Play sound & push notification for any new incoming message
                    if(data.isNewIncomingMessage) {
                        if($('#lwMessageAlertTone').length && $('#lwMessageAlertTone')[0]) {
                            try {
                                $('#lwMessageAlertTone')[0].play();
                            } catch(e) {}
                        }
                        if (!isWindowTabActive && typeof Push !== 'undefined') {
                            Push.create("{{ __tr('__siteName__ - New Message', [
                                '__siteName__' => getAppSettings('name')
                            ])}}", {
                                body: data.contactDescription,
                                icon: "{{ getAppSettings('small_logo_image_url') }}",
                                onClick: function () {
                                    window.focus();
                                    this.close();
                                }
                            });
                        }
                    }

                    // Only prepend chat messages & mark as read if the user currently HAS THIS SPECIFIC CONTACT OPEN IN THE CHAT WINDOW
                    var activeChatContactUid = $('#lwWhatsAppChatWindow').length ? ($('#lwWhatsAppChatWindow').attr('data-contact-uid') || $('#lwWhatsAppChatWindow').data('contact-uid')) : null;
                    if(isWindowTabActive && data.contactUid && activeChatContactUid && activeChatContactUid === data.contactUid) {
                        __DataRequest.get(__Utils.apiURL("{{ route('vendor.chat_message.data.read', ['contactUid', 'way']) }}{{ ((isset($assigned) and $assigned) ? '?assigned=to-me' : '') }}", {'contactUid': data.contactUid, 'way':'prepend'}),{}, function(responseData) {
                            __DataRequest.updateModels({
                                '@whatsappMessageLogs' : 'append',
                                'whatsappMessageLogs':responseData.client_models.whatsappMessageLogs
                            });
                            window.lwScrollTo('#lwEndOfChats', true);
                        });
                    }
                };

                lastEventData = _.cloneDeep(data);
                clearTimeout(broadcastActionDebounce);
                broadcastActionDebounce = setTimeout(function() {
                    // generic model updates
                    if(data.eventModelUpdate) {
                        __DataRequest.updateModels(data.eventModelUpdate);
                    }
                    @if(hasVendorAccess('messaging'))
                    if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                        // is incoming message
                        if(data.isNewIncomingMessage && !isUnreadRequestInProgress) {
                            isUnreadRequestInProgress = true;
                            __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function(responseData) {
                                isUnreadRequestInProgress = false;
                            });
                        };
                        // contact list update
                        if($('.lw-whatsapp-chat-window').length) {
                            __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid','way' => 'append','request_contact' => '', 'assigned'=> ($assigned ?? '')]); !!}", {'contactUid': $('#lwWhatsAppChatWindow').data('contact-uid'),'request_contact' : 'request_contact=' + data.contactUid + '&'}),{}, function() {});
                        }
                    }
                    @endif
                }, 1000);
                @if(hasVendorAccess('messaging'))
                // 10 seconds for campaign
                    clearTimeout(campaignActionDebounce);
                    campaignActionDebounce = setTimeout(function() {
                        // campaign data update
                        if(data.campaignUid && $('.lw-campaign-window-' + data.campaignUid).length) {
                            __DataRequest.get(__Utils.apiURL("{{ route('vendor.campaign.status.data', ['campaignUid']) }}", {'campaignUid': data.campaignUid}),{}, function(responseData) {
                                if(responseData.data.campaignStatus != lastCampaignStatus) {
                                    window.reloadDT('#lwCampaignQueueLog');
                                }
                                lastCampaignStatus = responseData.data.campaignStatus;
                            });
                        };
                    }, 10000);
                @endif
                // stack for to process data whenever the vendor channel broadcasts through Pusher or Socket
                @stack('vendorChannelBroadcastStack')
                // Check if data reload event fired, if yes then reload page
                if (data.reload) {
                    location.reload();
                }
            });
        @if(hasVendorAccess('messaging'))
        // initially get the unread count on page loads
        __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function() {});
        @endif
    @endif
    })(jQuery);
    </script>
    {!! getAppSettings('page_footer_code_all') !!}
    @if(isLoggedIn())
    {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
    
    <!-- PWA Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('PWA ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                console.log('PWA ServiceWorker registration failed: ', err);
            });
        });
    }
    </script>
</body>

</html>