<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Yantrana\Components\Auth\Controllers\ApiUserController;
use App\Yantrana\Components\Contact\Controllers\ContactController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppTemplateController;
use App\Yantrana\Components\Media\Controllers\MediaController;
use App\Yantrana\Components\User\Controllers\UserController;

use App\Yantrana\Components\{
    Auth\Controllers\AuthController
};
use App\Yantrana\Components\BotReply\Controllers\BotReplyController;
use App\Yantrana\Components\Campaign\Controllers\CampaignController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// external apis
// base url
Route::any('/', function () {
    return 'api endpoint';
})->name('api.base_url');

Route::group([
    'middleware' => 'api.vendor.authenticate',
    'prefix' => '{vendorUid}/',
], function () {
    Route::post('/contact/send-message', [
        WhatsAppServiceController::class,
        'apiSendChatMessage',
    ])->name('api.vendor.chat_message.send.process');
    // Get message status
    Route::get('/contact/message-status', [
        WhatsAppServiceController::class,
        'apiGetMessageStatus',
    ])->name('api.vendor.chat_message.read.status');
    // send media message
    Route::post('/contact/send-media-message', [
        WhatsAppServiceController::class,
        'apiSendMediaChatMessage',
    ])->name('api.vendor.chat_message_media.send.process');
    // Get template list
    Route::get('/contact/template-list', [
        WhatsAppServiceController::class,
        'apiGetTemplateList',
    ])->name('api.vendor.template_list.read.list');
    // send media message
    Route::post('/contact/send-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_template_message.send.process');
    // Send carousel template message
    Route::post('/contact/send-carousel-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_carousel_template_message.send.process');
    // send interactive message
    Route::post('/contact/send-interactive-message', [
        WhatsAppServiceController::class,
        'apiSendInteractiveChatMessage',
    ])->name('api.vendor.chat_message_interactive.send.process');
    // create new contact
    Route::post('/contact/create', [
        ContactController::class,
        'apiProcessContactCreate',
    ])->name('api.vendor.contact.create.process');
    // update contact
    Route::post('/contact/update/{phoneNumber}', [
        ContactController::class,
        'apiProcessContactUpdate',
    ])->name('api.vendor.contact.update.process');
    // assign team member
    Route::post('/contact/assign-team-member', [
        ContactController::class,
        'apiAssignTeamMemberToContact',
    ])->name('api.vendor.contact.assign_member.update.process');
    // get contact list
    Route::get('contacts', [
        ContactController::class,
        'apiGetContactList',
    ])->name('api.vendor.contact.read.list');
    // get contact by phone number or email
    Route::get('contact', [
        ContactController::class,
        'apiGetContact',
    ])->name('api.vendor.contact.read.single_contact');
    // get contact groups
    Route::get('contact/groups', [
        ContactController::class,
        'apiGetContactGroupList',
    ])->name('api.vendor.contact.read.group_list');
    // create contact group
    Route::post('contact/groups/create', [
        \App\Yantrana\Components\Contact\Controllers\ContactGroupController::class,
        'processGroupCreate',
    ])->name('api.vendor.contact.group.write.create');
    // delete contact group
    Route::post('contact/groups/{contactGroupIdOrUid}/delete', [
        \App\Yantrana\Components\Contact\Controllers\ContactGroupController::class,
        'processGroupDelete',
    ])->name('api.vendor.contact.group.write.delete');
    // get contacts in group
    Route::get('contact/groups/{groupUid}/contacts', [
        ContactController::class,
        'apiGetGroupContacts',
    ])->name('api.vendor.contact.group.contacts');
    // get contact labels and tags
    Route::get('contact/labels-tags', [
        ContactController::class,
        'apiGetContactLabelsAndTagsList',
    ])->name('api.vendor.contact.read.labels_and_tags_list');
    // assign groups to contacts
    Route::post('/contact/assign-groups', [
        ContactController::class,
        'apiAssignGroupsToContact',
    ])->name('api.vendor.contact.assign_groups.update.process');
    // unassign groups to contacts
    Route::post('/contact/unassign-groups', [
        ContactController::class,
        'apiUnassignGroupsFromContact',
    ])->name('api.vendor.contact.unassign_groups.update.process');
    // assign labels/tags to contacts
    Route::post('/contact/assign-labels', [
        ContactController::class,
        'apiAssignLabelsToContact',
    ])->name('api.vendor.contact.assign_labels.update.process');
    // unassign labels/tags to contacts
    Route::post('/contact/unassign-labels', [
        ContactController::class,
        'apiUnassignLabelsFromContact',
    ])->name('api.vendor.contact.unassign_labels.update.process');
    // schedule new campaign 
    Route::post('/campaign/schedule', [
        WhatsAppServiceController::class,
        'apiScheduleCampaign',
    ])->name('api.vendor.campaign.write.schedule');
    // Get paginated list of campaign
    Route::get('campaign', [
        CampaignController::class,
        'apiGetCampaignList',
    ])->name('api.vendor.campaign.read.list');
    // Get campaign statuses
    Route::get('campaign-status/{campaignUid}', [
        CampaignController::class,
        'apiGetCampaignStatus',
    ])->name('api.vendor.campaign.read.status_details');
});

Route::get('/app-version', function () {
    return response()->json([
        'version' => '1.0.3',
        'apk_url' => 'https://wb.4adev.com/whatsclick-latest.apk'
    ]);
})->name('api.app_version');

// Mobile app apis
Route::group(['middleware' => 'guest'], function () {

     //vendor registration
    Route::post('/register/vendor', [
        AuthController::class,
        'register',
    ])->name('api.auth.register.process');
     //vendor activation
    Route::post('/register/vendor/activation', [
        AuthController::class,
        'activationRequiredRegister',
    ])->name('api.activation_required.auth.register.process');

    Route::group([
        'prefix' => 'user',
    ], function () {
        // login process
        Route::post('/login-process', [
            ApiUserController::class,
            'loginProcess'
        ])->name('api.user.login.process');

        // User Registration prepare data
        Route::get('/prepare-sign-up', [
            ApiUserController::class,
            'prepareSignUp'
        ])->name('api.user.sign_up.prepare');

        // User Registration
        Route::post('/process-sign-up', [
            ApiUserController::class,
            'processSignUp'
        ])->name('api.user.sign_up.process');
       
        // Verify 2-Factor Authentication
        Route::post('/two-factor-challenge', [
            AuthController::class,
            'verifyTwoFactorAuthentication'
        ])->name('api.two_factor_authentication.verify');
    });
});
// vendor authenticated routes
Route::group([
    'middleware' => 'app_api.vendor.authenticate',
], function () {
    // broadcast private channel check
    Broadcast::routes([]);

    /*
    Media Component Routes Start from here
    ------------------------------------------------------------------- */
    Route::group([
        'prefix' => 'media',
    ], function () {
        // Temp Upload
        Route::post('/upload-temp-media/{uploadItem?}', [
            MediaController::class,
            'uploadTempMedia',
        ])->name('api.media.upload_temp_media');
    });

    /*
    User Device Component Routes Start from here 
    ------------------------------------------------------------------- */
    Route::group([
        'prefix' => 'user-device',
    ], function () {
        // Store User Device Token
        Route::post('/token', [
            ApiUserController::class,
            'storeUserDeviceToken',
        ])->name('api.user.device_token.write');
    });

    Route::group([
        'prefix' => 'vendor/',
    ], function () {
        //unread chat count
        Route::get('/whatsapp/chat/unread-count', [
            WhatsAppServiceController::class,
            'unreadCount',
        ])->name('app_api.vendor.chat_message.read.unread_count');
        // get contacts data
        Route::get('/contact/contacts-data/{contactUid?}', [
            WhatsAppServiceController::class,
            'getContactsData',
        ])->name('app_api.vendor.contacts.data.read');
        // contact chat data
        Route::get('/whatsapp/contact/chat/{contactUid?}', [
            WhatsAppServiceController::class,
            'chatView',
        ])->name('app_api.vendor.chat_message.contact.view');
        // contact chat data via append, prepend etc
        Route::get('/whatsapp/contact/chat-data/{contactUid}/{way?}', [
            WhatsAppServiceController::class,
            'getContactChatData',
        ])->name('app_api.vendor.chat_message.data.read');
        Route::post('/whatsapp/contact/chat/send', [
            WhatsAppServiceController::class,
            'sendChatMessage',
        ])->name('app_api.vendor.chat_message.send.process');

        // Dashboard Stats
        Route::get('/dashboard-stats', [
            \App\Yantrana\Components\Dashboard\Controllers\DashboardController::class,
            'apiVendorDashboardStats',
        ])->name('app_api.vendor.dashboard.stats');

        Route::post('/settings/toggle-bot', [
            \App\Yantrana\Components\Dashboard\Controllers\DashboardController::class,
            'toggleBotReply',
        ])->name('app_api.vendor.settings.toggle_bot');

        // Support Tickets
        Route::get('/support-tickets', [
            \App\Yantrana\Components\SupportTicket\Controllers\SupportTicketController::class,
            'apiIndex',
        ])->name('app_api.vendor.support_tickets.read');

        Route::post('/support-tickets/store', [
            \App\Yantrana\Components\SupportTicket\Controllers\SupportTicketController::class,
            'apiStore',
        ])->name('app_api.vendor.support_tickets.store');

        Route::get('/support-tickets/{uid}', [
            \App\Yantrana\Components\SupportTicket\Controllers\SupportTicketController::class,
            'apiShow',
        ])->name('app_api.vendor.support_tickets.show');

        Route::post('/support-tickets/{uid}/reply', [
            \App\Yantrana\Components\SupportTicket\Controllers\SupportTicketController::class,
            'apiReply',
        ])->name('app_api.vendor.support_tickets.reply');

        // Get WhatsApp templates
        Route::get('/whatsapp/templates', function () {
            validateVendorAccess('messaging');
            $repository = app(App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppTemplateRepository::class);
            $whatsAppApprovedTemplates = $repository->getApprovedTemplatesByNewest();
            return response()->json([
                'reaction' => 1,
                'data' => [
                    'templates' => $whatsAppApprovedTemplates
                ]
            ]);
        })->name('app_api.vendor.whatsapp.templates');

        // Send WhatsApp template message
        Route::post('/whatsapp/contact/send-template-message', function (Illuminate\Http\Request $request) {
            validateVendorAccess('messaging');
            $request->validate([
                'template_uid' => 'required',
                'contact_uid' => 'required',
            ]);
            $engine = app(App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
            $processReaction = $engine->processSendMessageForContact($request);
            return response()->json([
                'reaction' => $processReaction->reaction(),
                'message' => $processReaction->message(),
                'data' => $processReaction->data()
            ]);
        })->name('app_api.vendor.chat_template_message.send.process');


        // Contact get labels and team members data
        Route::get('/whatsapp/contact/chat-box-data/{contactUid}', [
            ContactController::class,
            'getLabelsForApi',
        ])->name('app_api.chat.box.base.data');
            // Contact get the data
        Route::get('/contacts/{contactIdOrUid}/get-update-data', [
            ContactController::class,
            'updateContactData',
        ])->name('app_api.vendor.contact.read.update.data');
        //media type api
        Route::get('/whatsapp/contact/chat/prepare-send-media/{mediaType?}', [
            WhatsAppServiceController::class,
            'prepareSendMediaUploader',
        ])->name('app_api.vendor.chat_message_media.upload.prepare');
        Route::post('/whatsapp/contact/chat/send-media', [
            WhatsAppServiceController::class,
            'sendChatMessageMedia',
        ])->name('app_api.vendor.chat_message_media.send.process');
        Route::post('/whatsapp/contact/chat/update-notes', [
            ContactController::class,
            'updateNotes',
        ])->name('app_api.vendor.chat.update_notes.process');
        Route::post('/whatsapp/contact/chat/assign-user', [
            ContactController::class,
            'assignChatUser',
        ])->name('app_api.vendor.chat.assign_user.process');
        Route::post('/whatsapp/contact/chat/assign-labels', [
            ContactController::class,
            'assignContactLabels',
        ])->name('app_api.vendor.chat.assign_labels.process');
        //clear chat history
        Route::post('/whatsapp/contact/chat/clear-history/{contactUid}', [
            WhatsAppServiceController::class,
            'clearChatHistory',
        ])->name('app_api.vendor.chat_message.delete.process');
            //create whatsapp contact label
         Route::post('/whatsapp/contact/create-label', [
            ContactController::class,
            'createLabel',
        ])->name('app_api.vendor.chat.label.create.write');
        //whatsapp contact edit lable
            Route::post('/whatsapp/contact/chat/edit-label', [
            ContactController::class,
            'updateLabel',
        ])->name('app_api.vendor.chat.label.update.write');
            //whatsapp contact delete lable
             Route::post('/whatsapp/contact/chat/delete-label/{labelUid}', [
            ContactController::class,
            'deleteLabelProcess',
        ])->name('app_api.vendor.chat.label.delete.write');

        // E-commerce products
        Route::get('/ecommerce/products', [
            \App\Yantrana\Components\ECommerce\Controllers\ECommerceController::class,
            'getProducts',
        ])->name('app_api.vendor.ecommerce.products');
        Route::post('/ecommerce/send-product', [
            \App\Yantrana\Components\ECommerce\Controllers\ECommerceController::class,
            'sendProductMessage',
        ])->name('app_api.vendor.ecommerce.send_product');

        // Canned replies
        Route::get('/canned-replies', [
            \App\Yantrana\Components\WhatsAppService\Controllers\CannedReplyController::class,
            'getCannedReplies',
        ])->name('app_api.vendor.canned_replies.read');
        Route::post('/canned-replies/save', [
            \App\Yantrana\Components\WhatsAppService\Controllers\CannedReplyController::class,
            'saveCannedReply',
        ])->name('app_api.vendor.canned_replies.save');
        Route::delete('/canned-replies/{uid}', [
            \App\Yantrana\Components\WhatsAppService\Controllers\CannedReplyController::class,
            'deleteCannedReply',
        ])->name('app_api.vendor.canned_replies.delete');

        // Contact Groups (Mobile) - unique path to avoid conflict with {vendorUid}/contact/groups wildcard
        Route::get('/contact/mobile-groups', function () {
            $vendorId = getVendorId();
            if (!$vendorId) {
                return response()->json(['reaction' => 2, 'message' => 'Non autorisé.'], 401);
            }
            $groups = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($g) {
                    $count = \DB::table('group_contacts')
                        ->where('contact_groups__id', $g->_id)
                        ->count();
                    return [
                        '_uid'           => $g->_uid,
                        'title'          => $g->title,
                        'description'    => $g->description,
                        'total_contacts' => $count,
                    ];
                });
            return response()->json([
                'reaction' => 1,
                'data'     => ['groups' => $groups],
            ]);
        })->name('app_api.vendor.contact.read.mobile_group_list');

        // Group contacts (Mobile) - unique path to avoid {vendorUid} wildcard conflict
        Route::get('/contact/mobile-group-contacts/{groupUid}', function ($groupUid) {
            $vendorId = getVendorId();
            if (!$vendorId) {
                return response()->json(['reaction' => 2, 'message' => 'Non autorisé.'], 401);
            }
            $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where([
                '_uid' => $groupUid,
                'vendors__id' => $vendorId,
            ])->first();
            if (!$group) {
                return response()->json(['reaction' => 2, 'message' => 'Groupe introuvable.']);
            }
            $contactIds = \DB::table('group_contacts')
                ->where('contact_groups__id', $group->_id)
                ->pluck('contacts__id');
            $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::whereIn('_id', $contactIds)
                ->orderBy('first_name', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        '_uid'       => $c->_uid,
                        'first_name' => $c->first_name,
                        'last_name'  => $c->last_name,
                        'wa_id'      => $c->wa_id,
                        'phone'      => $c->phone,
                    ];
                });
            return response()->json([
                'reaction' => 1,
                'data'     => ['contacts' => $contacts],
            ]);
        })->name('app_api.vendor.contact.mobile_group_contacts');

        Route::get('/contacts/simple-list', function () {
            $vendorId = getVendorId();
            if (!$vendorId) {
                return response()->json([
                    'reaction' => 2,
                    'message' => 'Non autorisé.'
                ], 401);
            }
            $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->orderBy('first_name', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        '_uid' => $c->_uid,
                        'name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                        'wa_id' => $c->wa_id,
                    ];
                });
            return response()->json([
                'reaction' => 1,
                'data'     => ['contacts' => $contacts],
            ]);
        })->name('app_api.vendor.contacts.simple_list');
        Route::post('/contact/groups/create', [
            \App\Yantrana\Components\Contact\Controllers\ContactGroupController::class,
            'processGroupCreate',
        ])->name('app_api.vendor.contact.group.write.create');
        Route::post('/contact/groups/{contactGroupIdOrUid}/delete', [
            \App\Yantrana\Components\Contact\Controllers\ContactGroupController::class,
            'processGroupDelete',
        ])->name('app_api.vendor.contact.group.write.delete');
        Route::get('/contact/groups/{groupUid}/contacts', [
            ContactController::class,
            'apiGetGroupContacts',
        ])->name('app_api.vendor.contact.group.contacts');
        Route::post('/contact/assign-groups', [
            ContactController::class,
            'apiAssignGroupsToContact',
        ])->name('app_api.vendor.contact.assign_groups.update.process');

        // Contacts by label (Mobile) - direct query without messages join requirement
        Route::get('/contact/by-label/{labelUid}', function ($labelUid) {
            $vendorId = getVendorId();
            if (!$vendorId) {
                return response()->json(['reaction' => 2, 'message' => 'Non autorisé.'], 401);
            }
            $label = \App\Yantrana\Components\Contact\Models\LabelModel::where(function($q) use ($labelUid) {
                $q->where('_uid', $labelUid)->orWhere('_id', $labelUid);
            })->where('vendors__id', $vendorId)->first();

            if (!$label) {
                return response()->json(['reaction' => 2, 'message' => 'Étiquette introuvable.']);
            }

            $dateFilter = request()->label_date_filter;
            $startDate  = request()->start_date;
            $endDate    = request()->end_date;

            $query = \DB::table('contact_labels')
                ->join('contacts', 'contact_labels.contacts__id', '=', 'contacts._id')
                ->where('contact_labels.labels__id', $label->_id)
                ->where('contacts.vendors__id', $vendorId);

            if ($dateFilter === 'today') {
                $query->whereDate('contact_labels.created_at', \Carbon\Carbon::today());
            } elseif ($dateFilter === 'yesterday') {
                $query->whereDate('contact_labels.created_at', \Carbon\Carbon::yesterday());
            } elseif ($dateFilter === 'day_before') {
                $query->whereDate('contact_labels.created_at', \Carbon\Carbon::today()->subDays(2));
            } elseif ($dateFilter === 'custom' && !empty($startDate) && !empty($endDate)) {
                $query->whereBetween('contact_labels.created_at', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay()
                ]);
            }

            $contacts = $query->select(
                'contacts._uid',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.wa_id',
                'contacts.phone'
            )->orderBy('contacts.first_name')->get()->map(function($c) {
                return [
                    '_uid'       => $c->_uid,
                    'first_name' => $c->first_name,
                    'last_name'  => $c->last_name,
                    'wa_id'      => $c->wa_id,
                    'phone'      => $c->phone,
                ];
            });

            return response()->json([
                'reaction' => 1,
                'data'     => ['contacts' => $contacts, 'total' => count($contacts)],
            ]);
        })->name('app_api.vendor.contact.by_label');

        // Block contact
        Route::post('/whatsapp/contact/{contactIdOrUid}/block-process', [
            ContactController::class,
            'processContactBlock',
        ])->name('app_api.vendor.contact.write.block');
        // unblock contact
        Route::post('/whatsapp/contact/{contactIdOrUid}/unblock-process', [
            ContactController::class,
            'processContactUnblock',
        ])->name('app_api.vendor.contact.write.unblock');
        // Contact datatable list
        Route::get('/contacts/list-data/{groupUid?}', [
            ContactController::class,
            'prepareContactList',
        ])->name('app_api.vendor.contact.read.list');
        // Contact add support data
        Route::get('/contacts/add-support-data', [
            ContactController::class,
            'appApiPrepareContactAddSupportData',
        ])->name('app_api.vendor.contact.read.data');
        // Contact create process
        Route::post('/contacts/add-process', [
            ContactController::class,
            'processContactCreate',
        ])->name('app_api.vendor.contact.write.create');
        // Contact get edit data
        Route::get('/contacts/{contactIdOrUid}/get-edit-support-data', [
            ContactController::class,
            'apiGetContactEditData',
        ])->name('app_api.vendor.contact.read.edit.data');
        // Contact update process
        Route::post('/contacts/update-process', [
            ContactController::class,
            'processContactUpdate',
        ])->name('app_api.vendor.contact.write.update');
        // Contact delete process
        Route::post('/contacts/{contactIdOrUid}/delete-process', [
            ContactController::class,
            'processContactDelete',
        ])->name('app_api.vendor.contact.write.delete');
        // delete selected contacts
        Route::post('/contacts/delete-selected-process', [
            ContactController::class,
            'selectedContactsDelete',
        ])->name('app_api.vendor.contacts.selected.write.delete');
        // assign group to selected contacts
        Route::post('/contacts/assign-groups-selected-process', [
            ContactController::class,
            'assignGroupsToSelectedContacts',
        ])->name('app_api.vendor.contacts.selected.write.assign_groups');
        // Get contact filter support data
        Route::get('/contacts/filter-support-data', [
            ContactController::class,
            'getContactFilterSupportData',
        ])->name('app_api.vendor.contact.read.filter_support_data');
        // Contact store filter data
        Route::post('/contacts/filter-store-process', [
            ContactController::class,
            'processStoreContactFilter',
        ])->name('app_api.vendor.contact.write.store_contact_filter');
        // All Active Bots
        Route::get("/bot-replies/{contactUid}/all-active-bots", [
            BotReplyController::class,
            'getAllActiveBots'
        ])->name('app_api.vendor.bot_reply.read.all.active.bots');
        // Get bot preview
        Route::get("/bot-replies/{botUid}/{contactIdOrUid}/bot-preview", [
            BotReplyController::class,
            'getBotPreview'
        ])->name('app_api.vendor.bot_reply.read.bot_preview');
        // BotReply update process
        Route::post("/bot-replies/quick-reply-process", [
            BotReplyController::class,
            'processBotQuickReply'
        ])->name('app_api.vendor.bot_reply.write.quick-reply');

        // BotReply Mobile Management Routes
        Route::prefix('/bot-replies-management')->group(function () {
            Route::get("/list", [
                BotReplyController::class,
                'apiIndex'
            ])->name('app_api.vendor.bot_reply.management.list');

            Route::post("/add", [
                BotReplyController::class,
                'processBotReplyCreate'
            ])->name('app_api.vendor.bot_reply.management.create');

            Route::post("/update", [
                BotReplyController::class,
                'processBotReplyUpdate'
            ])->name('app_api.vendor.bot_reply.management.update');

            Route::post("/{botReplyIdOrUid}/delete", [
                BotReplyController::class,
                'processBotReplyDelete'
            ])->name('app_api.vendor.bot_reply.management.delete');

            Route::post("/{botReplyIdOrUid}/toggle-status", [
                BotReplyController::class,
                'processToggleBotReplyStatus'
            ])->name('app_api.vendor.bot_reply.management.toggle_status');
        });
        // Campaign list request
        Route::get('/whatsapp/campaign/{status}/list-data', [
            CampaignController::class,
            'prepareCampaignList',
        ])->name('app_api.vendor.campaign.read.list');
        // Campaign Audiences Mobile API Routes
        Route::get('/whatsapp/audiences/list-data', [
            \App\Yantrana\Components\CampaignAudience\Controllers\CampaignAudienceController::class,
            'prepareAudienceDataTable'
        ])->name('app_api.vendor.campaign_audience.list');
        Route::post('/whatsapp/audiences/create', [
            \App\Yantrana\Components\CampaignAudience\Controllers\CampaignAudienceController::class,
            'processAddOrUpdate'
        ])->name('app_api.vendor.campaign_audience.create');

        // Campaign list for mobile app (clean JSON)
        Route::get('/campaign-list', [
            CampaignController::class,
            'apiGetCampaignList',
        ])->name('app_api.vendor.campaign.read.mobile_list');
        // Sync templates from Meta
        Route::post('/whatsapp/templates/sync', [
            WhatsAppTemplateController::class,
            'syncTemplates',
        ])->name('app_api.vendor.whatsapp.templates.sync');
        // Create WhatsApp Template
        Route::post('/whatsapp/templates/create', [
            WhatsAppTemplateController::class,
            'createNewTemplateProcess',
        ])->name('app_api.vendor.whatsapp.templates.create');
        // Create and schedule campaign (supports contact_uids)
        Route::post('/whatsapp/campaign/schedule', function (Illuminate\Http\Request $request) {
            validateVendorAccess('manage_campaigns');
            $contactUids = $request->get('contact_uids');
            if ($contactUids && is_array($contactUids)) {
                $vendorId = getVendorId();
                $groupUid = (string) \Str::uuid();
                // Create a temporary group for this specific campaign audience
                $newGroup = \App\Yantrana\Components\Contact\Models\ContactGroupModel::create([
                    '_uid' => $groupUid,
                    'title' => 'Audience: ' . $request->get('title') . ' (' . now()->format('d-m-Y H:i') . ')',
                    'description' => 'Groupe temporaire créé pour envoi direct',
                    'vendors__id' => $vendorId,
                    'status' => 1
                ]);
                // Fetch contact primary IDs from their UIDs
                $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                    ->whereIn('_uid', $contactUids)
                    ->get();
                $insertData = [];
                foreach ($contacts as $contact) {
                    $insertData[] = [
                        '_uid' => (string) \Str::uuid(),
                        'contact_groups__id' => $newGroup->_id,
                        'contacts__id' => $contact->_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                if (!empty($insertData)) {
                    \DB::table('group_contacts')->insert($insertData);
                }
                // Replace contact_group parameter with the created group
                $request->merge([
                    'contact_group' => [$groupUid]
                ]);
            }
            return app(WhatsAppServiceController::class)->scheduleCampaign($request);
        })->name('app_api.vendor.campaign.schedule.process');
        // Get list of non template message preset
        Route::get('/whatsapp/campaign/non-template-message-presets/{status}/list-data', [
            CampaignController::class,
            'nonTemplateCampaignMessagePresetsList',
        ])->name('app_api.vendor.campaign.read.non_template_message_preset_list');
        // Get campaign dashboard data
        Route::get('/whatsapp/campaign/dashboard/{campaignUid}/status', [
            CampaignController::class,
            'campaignStatusView',
        ])->name('app_api.vendor.campaign.read.dashboard_status');

        // Info materials api list & download
        Route::get('/info-materials', [
            \App\Yantrana\Components\InfoMaterial\Controllers\InfoMaterialController::class,
            'apiList',
        ])->name('app_api.vendor.info_materials.list');
        Route::get('/info-materials/{uid}/download', [
            \App\Yantrana\Components\InfoMaterial\Controllers\InfoMaterialController::class,
            'apiDownload',
        ])->name('app_api.vendor.info_materials.download');
    });

    // logout
    Route::post('/user/logout', [
        ApiUserController::class,
        'logout'
    ])->name('api.user.logout');
    Route::post('/update-password', [
        AuthController::class,
        'updatePassword',
    ])->name('api.auth.password.update.process');
  
    // profile update request
    Route::post('/user/profile-update', [
        UserController::class,
        'updateProfile',
    ])->name('api.user.profile.update');
   
    // Account Activation
    Route::get('/{userUid}/account-activation', [
        AuthController::class,
        'accountActivation',
    ])->name('api.user.account.activation');
});
