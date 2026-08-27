@extends('layouts.app', ['title' => __tr('WhatsApp Chat')])
@section('content')
@include('users.partials.header', [
// 'title' => __tr('WhatsApp Chat'),
'description' => '',
// 'class' => 'col-lg-7'
])
@push('head')
{!! __yesset('dist/css/whatsapp-chat.css', true) !!}
@endpush
<div x-data="initialMessageData" @chat-message-sent.window="cancelReply()"> 
{{-- @if ($contact) --}}
<div class="container-fluid lw-chat-main-container" x-data="{myAssignedUnreadMessagesCount:null,myUnassignedUnreadMessagesCount:null,showUnreadContactsOnly:false,usersUnreadMessagesCounts:{}}">
    <!-- Lightbox Overlay -->
    <div x-cloak x-show="lightboxOpen" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); z-index: 99999;" @click="lightboxOpen = false">
        <div style="display: flex; width: 100%; height: 100%; justify-content: center; align-items: center;">
            <span style="position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer; text-shadow: 0 2px 4px rgba(0,0,0,0.5); z-index: 100000;" @click="lightboxOpen = false">&times;</span>
            <img :src="lightboxImageSrc" style="max-width: 90%; max-height: 90%; box-shadow: 0 5px 25px rgba(0,0,0,0.5); border-radius: 8px; z-index: 100000;" @click.stop="">
        </div>
    </div>

    <div class="">
        <div class="card lw-whatsapp-chat-block-container">
            @if (!getVendorSettings('current_phone_number_number'))
            <div class="card-header">
            <div class="text-danger">
                {{  __tr('Phone number does not configured yet.') }}
            </div>
            </div>
            @endif
            <div id="lwWhatsAppChatWindow"
                class="card-body lw-whatsapp-chat-window p-sm-4" x-init="$watch('messagePaginatePage', function(value) {window.messagePaginatePage = value;});$watch('contactsPaginatePage', function(value) {window.contactsPaginatePage = value; });" :data-paginate-page="messagePaginatePage" :data-unread-only="showUnreadContactsOnly" :data-search-value="search" :data-contact-uid="contact?._uid">
                <div class="row" x-cloak x-data="{isContactListOpened:true,isContactCrmBlockOpened:false}">
                    <div class="col-sm-12 col-md-3 col-lg-3 col-xl-3 mb-4 lw-contact-list-block" x-show="isContactListOpened">
                        {{-- <h1>{{  __tr('WhatsApp Chat') }}</h1> --}}
                        {{-- <hr class="my-2"> --}}
                        <!-- Mobile Top Line for Back Button -->
                        <div class="d-md-none mb-2 w-100 clearfix">
                            <button type="button" class="btn btn-sm btn-light font-weight-700 shadow-sm float-right" @click.prevent="isContactListOpened = false" style="border-radius: 20px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #0f172a; padding: 5px 14px;">
                                <i class="fa fa-arrow-left mr-1.5 text-primary"></i> {{ __tr('Retour à la discussion') }}
                            </button>
                        </div>
                        <div class="tab-content lw-contact-list-header" id="nav-tabContent" x-cloak>
                            <div class="tab-pane fade show active pl-2" id="lwAllContactsTab" role="tabpanel" aria-labelledby="lw-all-contacts-tab" x-data="{isExpandedLabels:false}">
                                <div class="px-2 pt-2">
                                    <!-- Search Bar Row (3-Dots Dropdown + Search Input + Label Filter Funnel Button) -->
                                    <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                                        <!-- 1. Left: 3-Dots Assignment Dropdown Button -->
                                        <div class="dropdown" style="flex-shrink: 0;">
                                            <button class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" type="button" id="lwChatFilterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width: 42px; height: 42px; background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #0f172a; padding: 0;" title="{{ __tr('Filtres d\'assignation') }}">
                                                <i class="fas fa-ellipsis-v" style="font-size: 1.05rem; color: #334155;"></i>
                                            </button>
                                            <div class="dropdown-menu shadow-lg border-0" aria-labelledby="lwChatFilterDropdown" style="border-radius: 14px; min-width: 220px; font-size: 0.9rem; z-index: 1050;">
                                                @if (isVendorAdmin(getVendorId()) or !hasVendorAccess('assigned_chats_only'))
                                                <a class="dropdown-item py-2.5 d-flex align-items-center justify-content-between {{ ($assigned ?? null) ? '' : 'active' }}" href="{{ route('vendor.chat_message.contact.view') }}">
                                                    <span><i class="fas fa-comments text-primary mr-2.5"></i> {{ __tr('Tous') }}</span>
                                                    <span x-cloak x-show="unreadMessagesCount" class="badge bg-yellow text-dark rounded-pill ml-2" x-text="unreadMessagesCount"></span>
                                                </a>
                                                @endif
                                                <a class="dropdown-item py-2.5 d-flex align-items-center justify-content-between {{ (($assigned ?? null) == 'to-me') ? 'active' : '' }}" href="{{ route('vendor.chat_message.contact.view', ['assigned' => 'to-me']) }}">
                                                    <span><i class="fas fa-user text-info mr-2.5"></i> {{ __tr('Moi seul') }}</span>
                                                    <span x-cloak x-show="myAssignedUnreadMessagesCount" class="badge bg-yellow text-dark rounded-pill ml-2" x-text="myAssignedUnreadMessagesCount"></span>
                                                </a>
                                                @if (isVendorAdmin(getVendorId()) or !hasVendorAccess('assigned_chats_only'))
                                                <a class="dropdown-item py-2.5 d-flex align-items-center justify-content-between {{ ($assigned ?? null) == 'unassigned' ? 'active' : '' }}" href="{{ route('vendor.chat_message.contact.view', ['assigned' => 'unassigned']) }}">
                                                    <span><i class="fas fa-user-clock text-warning mr-2.5"></i> {{ __tr('Non assignés') }}</span>
                                                    <span x-cloak x-show="myUnassignedUnreadMessagesCount" class="badge bg-yellow text-dark rounded-pill ml-2" x-text="myUnassignedUnreadMessagesCount"></span>
                                                </a>
                                                @if(!__isEmpty($vendorMessagingUsers) and ($vendorMessagingUsers->count() > 1))
                                                    <div class="dropdown-divider"></div>
                                                    <div class="dropdown-header small font-weight-800 text-uppercase text-muted" style="letter-spacing: 0.5px;">{{ __tr('Autres agents') }}</div>
                                                    @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                                        @if($vendorMessagingUser->_uid != getUserUID())
                                                        <a class="dropdown-item py-2 d-flex align-items-center justify-content-between {{ ($assigned ?? null) == $vendorMessagingUser->_id ? 'active' : '' }}" href="{{ route('vendor.chat_message.contact.view', ['assigned' => $vendorMessagingUser->_id]) }}">
                                                            <span><i class="fas fa-user-tag text-muted mr-2.5"></i> {{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }}</span>
                                                            <span x-cloak x-show="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']" class="badge bg-yellow text-dark rounded-pill ml-2" x-text="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']"></span>
                                                        </a>
                                                        @endif
                                                    @endforeach
                                                @endif
                                                @endif
                                            </div>
                                        </div>

                                        <!-- 2. Center: Search Field -->
                                        <div class="flex-grow-1 position-relative">
                                            <i class="fa fa-search lw-search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.92rem;"></i>
                                            <input x-model="search" x-on:keyup.debounce.500ms="function(value) {
                                                window.searchValue = this.search;
                                                window.searchContacts();
                                            }" x-ref="searchField" placeholder="{{ __tr('Rechercher...') }}" type="text" class="form-control rounded-pill border-0 shadow-sm font-weight-600" style="padding-left: 38px !important; padding-right: 32px !important; background: #ffffff; border: 1.5px solid #cbd5e1 !important; height: 42px; font-size: 0.92rem; color: #0f172a;">
                                            <i class="fa fa-times lw-clear-search-icon" x-show="search.length > 0" x-on:click="search = ''; window.searchValue = ''; window.searchContacts(); $refs.searchField.focus();" style="cursor: pointer; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; padding: 4px;" title="{{ __tr('Clear search') }}"></i>
                                        </div>

                                        <!-- 3. Right: Label Filter Funnel Button -->
                                        @if (isset($allLabels) && count($allLabels) > 0)
                                        <button type="button" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center"
                                                :class="isExpandedLabels ? 'btn-primary text-white' : ''"
                                                @click="isExpandedLabels = !isExpandedLabels"
                                                style="width: 42px; height: 42px; background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #0f172a; padding: 0; flex-shrink: 0;"
                                                title="{{ __tr('Filtrer par étiquettes') }}">
                                            <i class="fas fa-filter" style="font-size: 0.95rem;" :style="isExpandedLabels ? 'color: #ffffff;' : 'color: #334155;'"></i>
                                        </button>
                                        @endif
                                    </div>

                                    <!-- Expanded Label Filter Drawer (When funnel button is clicked) -->
                                    @if (isset($allLabels) && count($allLabels) > 0)
                                    <div x-show="isExpandedLabels" class="p-3 mb-2 shadow-sm" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 14px;" x-transition x-cloak>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="font-weight-800 text-dark small"><i class="fa fa-tags mr-1.5 text-primary"></i> {{ __tr('Filtrer par étiquettes') }}</span>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-muted" @click="isExpandedLabels = false"><i class="fa fa-times"></i></button>
                                        </div>
                                        <div x-on:click="function(){ _.defer(function() { window.searchContacts(); }); }" class="btn-group-toggle d-flex flex-wrap" style="gap: 4px;" data-toggle="buttons">
                                            <label class="btn btn-outline-secondary btn-sm active mb-1 font-weight-700" style="border-radius: 8px;">
                                                <input class="lw-search-labels" type="radio" checked name="selected_label" value="" autocomplete="off"> <i class="fa fa-times ml-0"></i> {{ __tr('Toutes') }}
                                            </label>
                                            @foreach($allLabels as $label)
                                            <label style="--lbl-bg: {{ $label['bg_color'] }}; --lbl-color: {{ $label['text_color'] }}; background-color: {{ $label['bg_color'] }}; color: {{ $label['text_color'] }}; border-radius: 8px;" class="btn btn-sm mb-1 lw-contact-list-label-tag font-weight-700">
                                              <input class="lw-search-labels" type="radio" name="selected_label" value="{{ $label['_id'] }}" autocomplete="off"> {{ $label['title'] }}
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- 4. Underneath Search Bar: Long Read/Unread Filter Toggle Bar -->
                                    <div class="lw-modern-toggle-wrapper d-flex align-items-center justify-content-between p-2.5 mb-3 shadow-sm"
                                         style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; cursor: pointer; user-select: none; transition: all 0.2s ease;"
                                         @click.prevent="showUnreadContactsOnly = !showUnreadContactsOnly; window.showUnreadContactsOnly = showUnreadContactsOnly ? 1 : 0; _.defer(function() { window.searchContacts(); });">
                                        <div class="d-flex align-items-center" style="gap: 10px;">
                                            <span class="lw-toggle-switch" :class="{ 'active': showUnreadContactsOnly }" style="position: relative; display: inline-block; width: 36px; height: 20px; background: #cbd5e1; border-radius: 20px; transition: background 0.25s ease; flex-shrink: 0;" >
                                                <span style="position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform 0.25s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" :style="showUnreadContactsOnly ? 'transform: translateX(16px)' : ''"></span>
                                            </span>
                                            <span x-show="!showUnreadContactsOnly" class="font-weight-700" style="font-size: 0.85rem; color: #334155;">{{ __tr('Afficher toutes les discussions') }}</span>
                                            <span x-show="showUnreadContactsOnly" class="font-weight-800" style="font-size: 0.85rem; color: #f97316;">{{ __tr('Non lus uniquement') }}</span>
                                        </div>
                                        <span class="badge badge-pill font-weight-700 px-2.5 py-1" :class="showUnreadContactsOnly ? 'badge-warning text-dark' : 'badge-light text-muted'" style="font-size: 0.78rem;">
                                            <i class="fas" :class="showUnreadContactsOnly ? 'fa-envelope-open-text' : 'fa-list'"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="lw-modern-contact-list shadow-none" style="height: auto !important; max-height: calc(100vh - 240px) !important; min-height: 450px; overflow-y: auto;">
                                    
                                    <!-- Skeleton Loader -->
                                    <template x-if="isLoadingContacts && filteredContacts.length === 0">
                                        <div class="p-3">
                                            <div class="d-flex align-items-center mb-4 skeleton-pulse">
                                                <div class="rounded-circle" style="width: 48px; height: 48px; background-color: #e2e8f0;"></div>
                                                <div class="ml-3 flex-grow-1">
                                                    <div class="rounded mb-2" style="height: 14px; width: 60%; background-color: #e2e8f0;"></div>
                                                    <div class="rounded" style="height: 12px; width: 40%; background-color: #e2e8f0;"></div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-4 skeleton-pulse">
                                                <div class="rounded-circle" style="width: 48px; height: 48px; background-color: #e2e8f0;"></div>
                                                <div class="ml-3 flex-grow-1">
                                                    <div class="rounded mb-2" style="height: 14px; width: 70%; background-color: #e2e8f0;"></div>
                                                    <div class="rounded" style="height: 12px; width: 50%; background-color: #e2e8f0;"></div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center skeleton-pulse">
                                                <div class="rounded-circle" style="width: 48px; height: 48px; background-color: #e2e8f0;"></div>
                                                <div class="ml-3 flex-grow-1">
                                                    <div class="rounded mb-2" style="height: 14px; width: 50%; background-color: #e2e8f0;"></div>
                                                    <div class="rounded" style="height: 12px; width: 30%; background-color: #e2e8f0;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Empty State -->
                                    <template x-if="filteredContacts.length === 0 && !isLoadingContacts">
                                        <div class="text-center p-5 text-muted">
                                            <i class="fa fa-users fa-3x mb-3" style="color: #cbd5e1;"></i>
                                            <h5 style="color: #64748b; font-weight: 600;">{{ __tr('No Contacts Found') }}</h5>
                                            <p style="font-size: 0.85rem;">{{ __tr('Try adjusting your search or filters.') }}</p>
                                        </div>
                                    </template>

                                    <template x-for="contactItem in filteredContacts" :key="contactItem._uid">
                                        @if (($assigned ?? null))
                                        {{-- <template x-if="contactItem.assigned_users__id == '{{ getUserId() }}'"> --}}
                                        @endif
                                        <a x-show="(contact && contact._uid == contactItem._uid) || (showUnreadContactsOnly && contactItem.unread_messages_count) || !showUnreadContactsOnly" 
                                           :data-messaged-at="contactItem.last_message?.messaged_at" 
                                           @click="isContactListOpened = false; whatsappMessageLogs = []; messagePaginatePage = 0; contact = contactItem; assignedLabelIds = (contactItem.labels || []).map(function(l) { return l._id; }); appFuncs.resetForm(); window.history.pushState({}, '', '{{ route('vendor.chat_message.contact.view') }}/' + contactItem._uid);"
                                           :class="[(contact && (contact._uid == contactItem._uid)) ? 'lw-contact-card-selected' : '']"
                                           :href="__Utils.apiURL('{{ route('vendor.chat_message.contact.view', ['contactUid', 'assigned' => ($assigned ?? '')]) }}',{'contactUid': contactItem._uid})"
                                           class="lw-contact-card lw-ajax-link-action lw-action-change-url" data-callback="updateContactInfo">
                                            
                                            <div class="lw-contact-card-body">                                                 
                                                <!-- Avatar -->
                                                <div class="lw-contact-avatar-wrapper">
                                                    <div class="lw-contact-avatar-modern text-white text-center">
                                                        <span x-text="contactItem.name_initials"></span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Info -->
                                                <div class="lw-contact-info">
                                                    <!-- Row 1: Name & Badges -->
                                                    <div class="lw-contact-title-row d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center text-truncate" style="gap: 6px;">
                                                            <span class="lw-contact-name text-truncate" x-show="contactItem.full_name" x-text="contactItem.full_name"></span>
                                                            <span class="lw-contact-name text-truncate" x-show="!contactItem.full_name">
                                                                @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                                    <span x-text="contactItem.wa_id"></span>
                                                                @else
                                                                    <span x-text="__Utils.formatAsLocaleNumber(Number(contactItem.wa_id))"></span>
                                                                @endif
                                                            </span>

                                                            <!-- Pin, Drip Campaign & Reminder Badges -->
                                                            <template x-if="contactItem && contactItem.is_pinned">
                                                                <span class="badge px-1 py-0 shadow-sm" style="font-size: 10px; border-radius: 6px; background-color: #10b981 !important; color: #ffffff !important;" title="{{ __tr('Conversation épinglée') }}">
                                                                    <i class="fas fa-thumbtack text-white" style="font-size: 10px; transform: rotate(-45deg);"></i>
                                                                </span>
                                                            </template>
                                                            <template x-if="contactItem && contactItem.active_reminder">
                                                                <span class="badge badge-warning px-1 py-0 shadow-sm" style="font-size: 10px; border-radius: 6px; background-color: #f59e0b !important; color: #ffffff !important;" :title="(contactItem.active_reminder && contactItem.active_reminder.scheduled_at_formatted) ? ('{{ __tr('Rappel prévu le :') }} ' + contactItem.active_reminder.scheduled_at_formatted) : ''">
                                                                    <i class="fas fa-bell text-white lw-bell-pulse" style="font-size: 10px;"></i>
                                                                </span>
                                                            </template>
                                                            <template x-if="contactItem && contactItem.active_drip_campaign">
                                                                <span class="badge badge-success px-1 py-0 shadow-sm" style="font-size: 10px; border-radius: 6px; background-color: #10b981 !important; color: #ffffff !important;" :title="(contactItem.active_drip_campaign && contactItem.active_drip_campaign.title) ? ('{{ __tr('Campagne Drip en cours :') }} ' + contactItem.active_drip_campaign.title) : ''">
                                                                    <i class="fas fa-clock text-white" style="font-size: 10px;"></i>
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Row 2: Phone and Unread Badge -->
                                                    <div class="lw-contact-meta-row d-flex align-items-center justify-content-between">
                                                        <span class="lw-contact-phone">
                                                            <span x-show="contactItem.full_name">
                                                                @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                                    <span x-text="contactItem.wa_id"></span>
                                                                @else
                                                                    <span x-text="__Utils.formatAsLocaleNumber(Number(contactItem.wa_id))"></span>
                                                                @endif
                                                            </span>
                                                            <span x-show="!contactItem.full_name" class="lw-contact-phone-placeholder">&nbsp;</span>
                                                        </span>
                                                        <span x-show="contactItem && contactItem.unread_messages_count"
                                                              class="lw-contact-unread-badge lw-unread-pulse-badge ml-2"
                                                              x-text="contactItem.unread_messages_count"></span>
                                                    </div>

                                                    <!-- Row 2.5: Matched Search Message -->
                                                    <template x-if="search && search.trim() !== '' && contactItem.matched_search_message">
                                                        <div class="lw-contact-search-snippet" style="margin-top: 4px; font-size: 0.8rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-style: italic;">
                                                            <i class="fa fa-search text-muted mr-1" style="font-size: 0.7rem;"></i> 
                                                            <span x-html="contactItem.matched_search_message.replace(new RegExp('(' + search.trim().replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + ')', 'gi'), '<strong class=\'text-dark bg-warning px-1 rounded\'>$1</strong>')"></span>
                                                        </div>
                                                    </template>
                                                    
                                                    <!-- Row 3: Labels -->
                                                    <div class="lw-contact-labels-wrapper" style="margin-top: 4px;" x-show="contactItem.labels && contactItem.labels.length > 0">
                                                        <template x-for="contactLabel in contactItem.labels">
                                                            <span class="lw-contact-label-badge" :style="'background-color: ' + contactLabel.bg_color + '15; color: ' + contactLabel.bg_color + '; border: 1px solid ' + contactLabel.bg_color + '30;'" :title="contactLabel.title">
                                                                <span class="lw-label-dot" :style="'background-color: ' + contactLabel.bg_color"></span>
                                                                <span x-text="contactLabel.title"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    
                                                    <!-- Row 4: Time -->
                                                    <div class="lw-contact-time-row" style="display: flex; justify-content: flex-end; margin-top: 4px;">
                                                        <span class="lw-contact-time" style="font-size: 0.72rem; color: #64748b; font-weight: 500;" x-text="contactItem.last_message?.formatted_message_ago_time"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        @if (($assigned ?? null))
                                        {{-- </template> --}}
                                        @endif
                                    </template>
                                    <div class="p-4" x-show="contactsPaginatePage">
                                        <button x-cloak class="btn btn-sm btn-block btn-secondary d-flex justify-content-center align-items-center" @click="loadMoreContacts" x-bind:disabled="isLoadingMoreContacts" style="gap: 8px;">
                                            <i class="fa fa-download" x-show="!isLoadingMoreContacts"></i>
                                            <i class="fa fa-spinner fa-spin" x-show="isLoadingMoreContacts" x-cloak></i>
                                            <span x-text="isLoadingMoreContacts ? '{{ __tr('Loading...') }}' : '{{ __tr('Load More') }}'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page chat-container col-sm-12 col-md-6 col-lg-6 col-xl-6 d-flex flex-column" :class="[(!contact) ? 'lw-disabled-block-content' : '', isContactListOpened ? 'd-none d-md-block' : '']" x-cloak>
                        {{-- <h2>{{ __tr('Chat') }}</h2> --}}
                        <div class="marvel-device nexus5 flex-grow-1 d-flex flex-column w-100" style="height: auto !important;">
                            <div class="screen flex-grow-1 d-flex flex-column" style="height: auto !important;">
                                <div class="screen-container flex-grow-1 d-flex flex-column" style="height: auto !important;">
                                    <div class="chat flex-grow-1 d-flex flex-column" id="lwChatWindowBox" style="height: auto !important;">
                                        {{-- <template x-if="contact"> --}}
                                            <div class="d-flex flex-column flex-grow-1 w-100">
                                                <template x-if="contact">
                                                <div class="user-bar">
                                                    <div class="back d-md-none flex-shrink-0" @click.prevent="isContactListOpened = true">
                                                        <i class="fa fa-users"></i>
                                                    </div>
                                                    <div class="avatar d-none d-md-flex text-white text-center align-items-center justify-content-center flex-shrink-0" style="background-color: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3);">
                                                        <span x-text="contact.name_initials" style="font-weight: 600; font-size: 15px; letter-spacing: 0.5px;"></span>
                                                    </div>
                                                    <div class="name d-flex flex-column justify-content-center" style="width: auto; max-width: 65%;">
                                                        <div class="contact-name-main text-truncate" style="line-height: 1.2; letter-spacing: 0.3px;">
                                                            <span style="font-size: 17px; font-weight: 600;" x-text="contact.full_name"></span>
                                                            <span style="font-size: 14px; font-weight: 400; opacity: 0.85; margin-left: 6px;">
                                                                <span x-show="contact.wa_id">- </span>
                                                                @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                                    <span x-text="contact.wa_id"></span>
                                                                @else
                                                                    <a target="_blank" class="text-white" style="text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'" x-bind:href="'https://api.whatsapp.com/send?phone=' + contact.wa_id" x-text="contact.wa_id ? __Utils.formatAsLocaleNumber(Number(contact.wa_id)) : ''"></a>
                                                                @endif
                                                            </span>
                                                            <template x-if="contact && contact.active_drip_campaign">
                                                                <span class="badge badge-pill badge-info ml-2 px-2 py-1" style="font-size: 11px; font-weight: 600; background: rgba(255, 255, 255, 0.25); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 12px; vertical-align: middle;" :title="'{{ __tr('Campagne Drip en cours :') }} ' + contact.active_drip_campaign.title">
                                                                    <i class="fas fa-clock text-success mr-1"></i>
                                                                    <span x-text="contact.active_drip_campaign.title"></span>
                                                                </span>
                                                            </template>
                                                            <template x-if="contact && contact.active_reminder">
                                                                <span class="badge badge-pill badge-warning ml-2 px-2 py-1" style="font-size: 11px; font-weight: 600; background: rgba(255, 193, 7, 0.35); color: #ffffff; border: 1px solid rgba(255, 193, 7, 0.6); border-radius: 12px; vertical-align: middle;" :title="'{{ __tr('Rappel prévu le :') }} ' + contact.active_reminder.scheduled_at_formatted + ' (' + contact.active_reminder.title_note + ')'">
                                                                    <i class="fas fa-bell text-warning mr-1"></i>
                                                                    <span x-text="contact.active_reminder.scheduled_at_formatted"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <template x-if="isDirectMessageDeliveryWindowOpened">
                                                            <span class="status text-success " x-text="directMessageDeliveryWindowOpenedTillMessage"></span>
                                                        </template>
                                                            <template x-if="!isDirectMessageDeliveryWindowOpened">
                                                            <span class="status text-yellow " title="{{ __tr("As you may not received any response in last 24 hours, your direct message may not get delivered. However you can send template messages.") }}">
                                                                <i class="fas fa-exclamation-triangle" style="color: #eab308; font-size: 14px;"></i> 
                                                                <span>{{  __tr('You can\'t reply, they needs to reply back to start conversion.') }}</span>
                                                            </span>
                                                             </template>
                                                    </div>
                                                    
                                                    <template x-if="contact">
                                                    <div class="actions more lw-user-new-actions" x-data="{isAiChatBotEnabled:!contact.disable_ai_bot}" x-cloak>                                                         {{-- Whatsapp call button --}}
                                                         @stack('whatsappCallButton')
                                                         @if(isset($whatsjetCallingAddonActive) && $whatsjetCallingAddonActive)
                                                             @include('WhatsJetCallingAddon::call-button')
                                                         @endif
                                                         {{-- Whatsapp call button --}}
                                                         <template x-if="contact && contact.active_reminder">
                                                             <a href="#" class="lw-whatsapp-bar-icon-btn mr-2" @click.prevent="openContactReminderModal(contact)" :title="'{{ __tr('Rappel prévu le :') }} ' + contact.active_reminder.scheduled_at_formatted">
                                                                 <i class="fas fa-bell text-warning"></i>
                                                             </a>
                                                         </template>
                                                        <a href="#" class="lw-whatsapp-bar-icon-btn mr-2" @click.prevent="isChatSearchOpened = !isChatSearchOpened; if(isChatSearchOpened) { $nextTick(() => document.getElementById('chatSearchInput').focus()) }" title="Rechercher">
                                                            <i class="fas fa-search text-white"></i>
                                                        </a>
                                                        <a href="#" class="lw-whatsapp-bar-icon-btn" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v text-white"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                        <a x-bind:href="__Utils.apiURL('{{ route('vendor.template_message.contact.view', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})" class="dropdown-item"><i class="fas fa-paper-plane"></i> {{ __tr('Send Template Message') }}</a>
                                                        @if (hasVendorAccess('messaging', 'delete_chat_history'))
                                                        <a x-cloak
                                                            :class="whatsappMessageLogs.length <= 0 ? 'disabled' : ''"
                                                            data-method="post" data-confirm="#lwClearChatHistoryWarning" x-bind:href="__Utils.apiURL('{{ route('vendor.chat_message.delete.process', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                            class="dropdown-item text-danger lw-ajax-link-action"><i class="fas fa-eraser"></i> {{ __tr('Clear Chat History') }}</a>
                                                        <script type="text/template" id="lwClearChatHistoryWarning">
                                                            <h3>{{  __tr('Are you sure you want to clear chat history for this contact?') }}</h3>
                                                                <p class="text-warning">{{  __tr('Only chat history will be deleted permanently, it won\'t delete campaign messages.') }}</p>
                                                            </script>
                                                        @endif

                                                            <template x-if='contact && (_.isEmpty(contact?.wa_blocked_at))'>
                                                                <span :title="isDirectMessageDeliveryWindowOpened == false ? '{!! addslashes(__tr('Blocking is not allowed as no response has been received within the past 24 hours')) !!}' : ''">
                                                                <a x-cloak
                                                                    :class="{ 'disabled': isDirectMessageDeliveryWindowOpened == false }"
                                                                    :style="isDirectMessageDeliveryWindowOpened == false ? {
                                                                        'pointer-events': 'none',
                                                                        'color': 'gray',
                                                                        'cursor': 'not-allowed',
                                                                        'text-decoration': 'none'
                                                                        } : {}"
                                                                    :href="isDirectMessageDeliveryWindowOpened == false ? 'javascript:void(0)' : __Utils.apiURL('{{ route('vendor.contact.write.block', ['contactIdOrUid']) }}', { contactIdOrUid: contact._uid })"
                                                                    @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                    data-method="post"
                                                                    data-confirm="#lwBlockContact-template"
                                                                    title="{{ __tr('Block') }}"
                                                                    data-callback="appFuncs.modelSuccessCallback"
                                                                    class="dropdown-item lw-ajax-link-action-via-confirm"
                                                                    aria-disabled="true">
                                                                    <i class="fa fa-ban"></i> {{ __tr('Block') }}
                                                                </a>
                                                                </span>
                                                            </template>

                                                            <template x-if='contact && (!_.isEmpty(contact?.wa_blocked_at))'>
                                                                <a x-cloak 
                                                                :href="__Utils.apiURL('{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                                @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                data-method="post" class="dropdown-item lw-ajax-link-action-via-confirm" data-confirm="#lwUnblockContact-template" title="{{ __tr('Unblock') }}" data-callback="appFuncs.modelSuccessCallback" aria-disabled="true"><i class="fa fa-ban"></i> {{ __tr('Unblock') }}</a>
                                                            </template>

                                                            <template x-if="contact">
                                                                <a x-cloak
                                                                   :href="__Utils.apiURL('{{ route('vendor.contact.write.pin', ['contactIdOrUid']) }}', { contactIdOrUid: contact._uid })"
                                                                   data-method="post"
                                                                   class="dropdown-item lw-ajax-link-action"
                                                                   data-callback="appFuncs.modelSuccessCallback">
                                                                   <i class="fas fa-thumbtack mr-1" :style="contact?.is_pinned ? 'color:#10b981;' : ''"></i>
                                                                   <span x-text="contact?.is_pinned ? '{{ __tr('Désépingler') }}' : '{{ __tr('Épingler') }}'"></span>
                                                                </a>
                                                            </template>
                                                        </div>

                                                        <span class="lw-whatsapp-bar-icon-btn ml-3 d-md-none" @click.prevent="isContactCrmBlockOpened = true"><i class="fa fa-user-tie"></i></span>
                                                    </div>
                                                    </template>
                                                </div>
                                                </template>
                                                <!-- Search Bar (hidden by default) -->
                                                <div class="chat-search-bar px-3 py-2 bg-light border-bottom" x-show="isChatSearchOpened" x-transition x-cloak>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="chatSearchInput" class="form-control rounded-pill" placeholder="Rechercher dans cette discussion..." x-model="chatSearchText">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary rounded-pill ml-2" type="button" @click="chatSearchText = ''; isChatSearchOpened = false"><i class="fas fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="conversation">
                                                    <div class="conversation-container" id="lwConversionChatContainer">
                                                            <div class="w-100" id="lwEndOfChats">&shy;</div>
                                                            <template x-for="whatsappMessageLogItem in whatsappMessageLogs">
                                                                <div class="lw-chat-message-item"
                                                                    :id="whatsappMessageLogItem._uid"
                                                                    x-show="!chatSearchText || (whatsappMessageLogItem.message && whatsappMessageLogItem.message.toLowerCase().includes(chatSearchText.toLowerCase()))">
                                                                    <template
                                                                        x-if="whatsappMessageLogItem.is_incoming_message && !whatsappMessageLogItem.is_system_message">
                                                                        <div class="message received">
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid">
                                                                                <a href="#"
                                                                                    @click.prevent="lwScrollTo('#'+whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid)"
                                                                                    class="badge d-flex text-muted justify-content-end"><i
                                                                                        class="fa fa-link"></i> {{
                                                                                    __tr('Replied to') }}</a>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.template_message">
                                                                                <div class="lw-template-message"
                                                                                    x-show="whatsappMessageLogItem.template_message"
                                                                                    x-html="whatsappMessageLogItem.template_message">
                                                                                </div>
                                                                            </template>
                                                                            <div x-show="whatsappMessageLogItem.message && !whatsappMessageLogItem.__data?.interaction_message_data"><span class="lw-plain-message-text" x-html="!chatSearchText ? whatsappMessageLogItem.message : whatsappMessageLogItem.message.replace(new RegExp('(' + chatSearchText.trim().replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + ')', 'gi'), '<strong class=\'text-dark bg-warning px-1 rounded\'>$1</strong>')"></span></div>
                                                                            <template
                                                                                x-if="(whatsappMessageLogItem.whatsapp_message_error)">
                                                                                <div class="p-1 mt-2">
                                                                                    <small class="text-danger"> <i
                                                                                            class="fas fa-exclamation-circle text-danger text-shadow"></i>
                                                                                        <em
                                                                                            x-text="whatsappMessageLogItem.whatsapp_message_error"></em></small>
                                                                                </div>
                                                                            </template>
                                                                            <span class="metadata">
                                                                                <span class="time" x-text="whatsappMessageLogItem.formatted_message_time_24h"></span>
                                                                                <a href="#" @click.prevent="setReply(whatsappMessageLogItem)" class="text-muted ml-2 lw-reply-btn" title="{{ __tr('Reply to this message') }}">
                                                                                    <i class="fa fa-reply"></i>
                                                                                </a>
                                                                            </span>
                                                                        </div>
                                                                    </template>
                                                                    <template
                                                                        x-if="!whatsappMessageLogItem.is_incoming_message && !whatsappMessageLogItem.is_system_message">
                                                                        <div class="message sent">
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid">
                                                                                <a href="#"
                                                                                    @click.prevent="lwScrollTo('#'+whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid)"
                                                                                    class="badge d-flex text-muted justify-content-end"><i
                                                                                        class="fa fa-link"></i> {{
                                                                                    __tr('Replied to') }}</a>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.__data?.options?.bot_reply">
                                                                                <span class="badge d-flex text-muted justify-content-end"
                                                                                    :title="whatsappMessageLogItem.__data?.options?.ai_bot_reply ? '{{ __tr('AI Bot Reply') }}' : '{{ __tr('Bot Reply') }}'">
                                                                                    <template x-if="whatsappMessageLogItem.__data?.options?.ai_bot_reply">
                                                                                        <span class="mr-1 text-warning">AI</span>
                                                                                    </template>
                                                                                    <i class="fas fa-robot text-muted"></i>
                                                                                </span>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.campaigns__id">
                                                                                <span class="badge d-flex justify-content-end" title="{{ __tr('Campaign Message') }}">
                                                                                    <i class="fas fa-bullhorn text-info"></i>
                                                                                </span>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.template_message">
                                                                                <div class="lw-template-message"
                                                                                    x-show="whatsappMessageLogItem.template_message"
                                                                                    x-html="whatsappMessageLogItem.template_message">
                                                                                </div>
                                                                            </template>
                                                                            <template x-if="whatsappMessageLogItem.message && !whatsappMessageLogItem.__data?.interaction_message_data">
                                                                                <div class="lw-template-message" x-show="whatsappMessageLogItem.message"><span class="lw-plain-message-text" x-html="!chatSearchText ? whatsappMessageLogItem.message : whatsappMessageLogItem.message.replace(new RegExp('(' + chatSearchText.trim().replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + ')', 'gi'), '<strong class=\'text-dark bg-warning px-1 rounded\'>$1</strong>')"></span>
                                                                                </div>
                                                                            </template>
                                                                            <template
                                                                                x-if="(whatsappMessageLogItem.whatsapp_message_error)">
                                                                                <div class="p-1 mt-2">
                                                                                    <small class="text-danger"> <i
                                                                                            class="fas fa-exclamation-circle text-danger text-shadow"></i>
                                                                                        <em
                                                                                            x-text="whatsappMessageLogItem.whatsapp_message_error"></em></small>
                                                                                </div>
                                                                            </template>
                                                                            <span class="metadata">
                                                                                <span class="time"
                                                                                    x-text="whatsappMessageLogItem.formatted_message_time_24h"></span>
                                                                                <span class="tick">
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'read'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-read.svg') }}" title="{{ __tr('Read') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'played'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-read.svg') }}" title="{{ __tr('Played') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'delivered'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-delivered.svg') }}" title="{{ __tr('Delivered') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'sent'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-sent.svg') }}" title="{{ __tr('Sent') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'failed'">
                                                                                        <i title="{{ __tr('Failed') }}"
                                                                                            class="fas fa-exclamation-circle text-danger"></i>
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="(whatsappMessageLogItem.status == 'accepted')">
                                                                                        <i title="{{ __tr('Accepted') }}"
                                                                                            class="far fa-clock text-muted"></i>
                                                                                    </template>
                                                                                </span>
                                                                                <a href="#" @click.prevent="setReply(whatsappMessageLogItem)" class="text-muted ml-2 lw-reply-btn" title="{{ __tr('Reply to this message') }}">
                                                                                    <i class="fa fa-reply"></i>
                                                                                </a>
                                                                            </span>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="whatsappMessageLogItem.is_system_message">
                                                                        <div>
                                                                            <div class="text-center align-content-center lw-system-message-container p-2">
                                                                                <div class="text-center align-content-center lw-chat-history-container">
                                                                                    <div class="lw-chat-history-message mb-1" x-html="!chatSearchText ? whatsappMessageLogItem.message : whatsappMessageLogItem.message.replace(new RegExp('(' + chatSearchText.trim().replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + ')', 'gi'), '<strong class=\'text-dark bg-warning px-1 rounded\'>$1</strong>')"></div>
                                                                                </div>
                                                                                <small><small class="small text-muted mt-2" x-text="whatsappMessageLogItem.formatted_updated_time"></small></small>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <div class="w-100 px-4 mb-2" id="lwEndOfChats">&shy; 
                                                                <button x-cloak x-show="messagePaginatePage" class="btn btn-sm btn-block btn-secondary d-flex justify-content-center align-items-center" @click="loadEarlierMessages" x-bind:disabled="isLoadingEarlierMessages" style="gap: 8px;">
                                                                    <i class="fa fa-download" x-show="!isLoadingEarlierMessages"></i>
                                                                    <i class="fa fa-spinner fa-spin" x-show="isLoadingEarlierMessages" x-cloak></i>
                                                                    <span x-text="isLoadingEarlierMessages ? '{{ __tr('Loading...') }}' : '{{ __tr('Load earlier messages') }}'"></span>
                                                                </button>
                                                            </div>
                                                    </div>
                                                    <template x-if="contact && (!_.isEmpty(contact?.wa_blocked_at))">
                                                        <div class="alert alert-light text-center align-content-center">
                                                            <strong class="text-danger" title="{{ __tr('Unblock') }}">{{  __tr('This contact is blocked, ') }}
                                                                <a data-method="post" 
                                                                class="lw-ajax-link-action-via-confirm"
                                                                :href="__Utils.apiURL('{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                                @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                data-confirm="#lwUnblockContact-template" title="{{ __tr('Click here to unblock.') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-ban"></i> {{  __tr('Click here to unblock.') }}</a></strong>
                                                        </div>
                                                    </template>
                                                    <div class="w-100 flex-shrink-0" x-show="contact && (_.isEmpty(contact?.wa_blocked_at))">
                                                    <x-lw.form data-event-stream-update="true" data-callback="window.chatFormReset" id="whatsAppMessengerForm"
                                                        class="conversation-compose flex-shrink-0" data-show-processing="false"
                                                        :action="route('vendor.chat_message.send.process')"
                                                        @submit="cancelReply()" style="background-color: #ffffff; border-radius: 16px; padding: 10px 15px; box-shadow: 0 -2px 15px rgba(0,0,0,0.05); margin: 0 5px 5px 5px;">
                                                        <input type="hidden" name="contact_uid" x-bind:value="contact?._uid">
                                                        <input type="hidden" name="reply_to_message_wamid" x-bind:value="replyingToMessage ? replyingToMessage.wamid : ''">
                                                        
                                                        <div class="d-flex flex-column w-100">
                                                            <template x-if="replyingToMessage">
                                                                <div class="lw-reply-preview px-3 py-2 bg-light w-100 d-flex justify-content-between align-items-center" style="border-left: 4px solid var(--waba-primary); border-top-left-radius: 10px; border-top-right-radius: 10px; margin-bottom: 2px;">
                                                                    <div class="text-truncate" style="max-width: 90%;">
                                                                        <small class="text-muted d-block" style="font-weight: 600;" x-text="replyingToMessage.is_incoming_message ? contact.full_name : '{{ __tr('You') }}'"></small>
                                                                        <span class="text-truncate d-block" x-html="replyingToMessage.message" style="font-size: 0.9em; opacity: 0.8;"></span>
                                                                    </div>
                                                                    <a href="#" @click.prevent="cancelReply()" class="text-muted" style="font-size: 1.2em;"><i class="fa fa-times"></i></a>
                                                                </div>
                                                            </template>
                                                            
                                                            <div class="lw-compose-pill d-flex align-items-center flex-grow-1" style="min-height: 52px;">
                                                                {{-- emoji following blank tag as removing it may break input layout
                                                                --}}
                                                                <div class="emoji d-flex align-items-center justify-content-center" style="width: 30px;">
                                                                </div>
                                                                <textarea id="lwChatWindowMessageBody" name="message_body" required class="input-msg lw-input-emoji flex-grow-1 border-0 bg-transparent m-0" style="resize: none; outline: none; font-size: 15px; padding-top: 8px; line-height: 1.5; min-width: 50px; height: 40px; box-shadow: none;" placeholder="{{ __tr('Type a message') }}" autocomplete="off" autofocus></textarea>
                                                            <template x-if="contact">
                                                                <div class="photo action-mic d-flex align-items-center justify-content-center ml-2" style="width: 30px;">
                                                                    <a title="{!! __tr('Record & Send') !!}" class="lw-ajax-link-action lw-whatsapp-bar-icon-btn d-flex align-items-center justify-content-center" href="#" data-toggle="modal" data-target="#lwSendRecording"><i class="fa fa-microphone text-muted" style="font-size: 18px;"></i> </a>
                                                                </div>
                                                            </template>
                                                            <div class="photo dropup action-attach d-flex align-items-center justify-content-center ml-2" style="width: 30px;">
                                                            <!-- Default dropup button -->
                                                            <a href="#" class="lw-whatsapp-bar-icon-btn" data-toggle="dropdown" aria-expanded="false">
                                                                <i class=" fa fa-paperclip text-muted"></i>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-right">

                                                                <!-- Quick Bot Reply -->
                                                                <template x-if="contact">
                                                                    <a title="{{ __tr('Quick Bot Reply') }}"
                                                                        class="lw-ajax-link-action dropdown-item" data-response-template="#lwQuickReplyContentBody" x-bind:href="__Utils.apiURL('{{ route('vendor.bot_reply.read.all.active.bots', ['contactIdOrUid']) }}', { 'contactIdOrUid': contact._uid})"  data-toggle="modal" data-target="#lwQuickReply"><i class="fa fa-bolt text-muted"></i> {{ __tr('Quick Bot Reply') }}
                                                                    </a>
                                                                </template>
                                                                <!-- /Quick Bot Reply -->
                                                                @if(vendorPlanDetails('ecommerce_catalog', 1)['is_limit_available'])
                                                                <template x-if="contact">
                                                                    <a title="{{ __tr('Send Product') }}"
                                                                        class="dropdown-item"
                                                                        data-toggle="modal"
                                                                        data-target="#lwECommerceProductPicker">
                                                                        <i class="fa fa-shopping-cart text-muted"></i> {{ __tr('Send Product') }}
                                                                    </a>
                                                                </template>
                                                                @endif
                                                                <a title="{{ __tr('Send Document') }}"
                                                                    class="lw-ajax-link-action dropdown-item" data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'document' ]) }}"><i class="fa fa-file text-muted"></i> {{ __tr('Send Document') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Image') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', ['mediaType' => 'image']) }}"><i class="fa fa-image text-muted"></i> {{ __tr('Send Image') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Video') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'video']) }}"><i class="fa fa-video text-muted"></i> {{ __tr('Send Video') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Audio') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'audio']) }}"><i class="fa fa-headphones text-muted"></i> {{ __tr('Send Audio') }}
                                                                </a>
                                                            </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button class="send ml-3" type="submit" style="background: transparent; border: none; outline: none; flex-shrink: 0; padding: 0;">
                                                            <div class="square-btn d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #25D366, #1da851); width: 48px; height: 48px; border-radius: 12px; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.3); transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 14px rgba(37, 211, 102, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 10px rgba(37, 211, 102, 0.3)'">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="1.4em" height="1.4em" viewBox="0 0 24 24" style="margin-right: 2px;">
                                                                    <path fill="#ffffff" d="M2.01 21L23 12L2.01 3L2 10l15 2l-15 2z" />
                                                                </svg>
                                                            </div>
                                                        </button>
                                                    </x-lw.form>

                                                    <!-- Format Buttons for Chat -->
                                                    <div class="px-3 pb-2 pt-1 d-none">
                                                        <x-whatsapp-format-buttons inputId="lwChatWindowMessageBody" />
                                                    </div>
                                                    <!-- /Format Buttons for Chat -->

                                                    {{-- error container --}}
                                                    <div data-form-id="#whatsAppMessengerForm"
                                                        class="lw-error-container-message_body p-2">
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>
                                        {{-- </template> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <style>
                        /* Sidebar block styling */
                        .lw-contact-crm-block {
                            background-color: #f8fafc !important;
                            border-left: 1px solid #e2e8f0 !important;
                            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        }

                        /* Card container for sections */
                        .lw-crm-card {
                            background: #ffffff;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                            margin: 12px;
                            padding: 18px;
                            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                            position: relative;
                        }

                        .lw-crm-card:hover {
                            transform: translateY(-1px);
                            box-shadow: 0 4px 12px -2px rgba(148, 163, 184, 0.12), 0 2px 6px -1px rgba(148, 163, 184, 0.08);
                            border-color: #cbd5e1;
                        }

                        /* Profile specific card */
                        .lw-crm-profile-card {
                            text-align: center;
                            padding: 24px 18px;
                        }

                        /* Avatar styling */
                        .lw-crm-avatar {
                            width: 96px;
                            height: 96px;
                            border-radius: 16px;
                            font-size: 38px;
                            font-weight: 700;
                            background: #1B6F20;
                            color: #ffffff;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto 16px auto;
                            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
                            letter-spacing: 1px;
                        }

                        .lw-crm-contact-name {
                            font-size: 1.15rem;
                            font-weight: 700;
                            color: #0f172a;
                            margin-bottom: 4px;
                        }

                        .lw-crm-contact-phone {
                            font-size: 0.88rem;
                            color: #64748b;
                            font-weight: 500;
                        }

                        /* Section Headings with border-left accent */
                        .lw-crm-section-header {
                            border-left: 3px solid #1B6F20;
                            padding-left: 10px;
                            font-size: 0.8rem;
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            color: #1B6F20;
                            margin-bottom: 16px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }

                        .lw-crm-section-header a,
                        .lw-crm-section-header button {
                            text-transform: none;
                            letter-spacing: normal;
                        }

                        /* Grid & Info Rows */
                        .lw-crm-info-row {
                            display: flex;
                            align-items: center;
                            margin-bottom: 12px;
                        }

                        .lw-crm-info-row:last-child {
                            margin-bottom: 0;
                        }

                        .lw-crm-icon-badge {
                            width: 32px;
                            height: 32px;
                            border-radius: 8px;
                            background-color: #f1f5f9;
                            color: #64748b;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-right: 12px;
                            font-size: 14px;
                            flex-shrink: 0;
                            transition: all 0.2s ease;
                        }

                        .lw-crm-info-row:hover .lw-crm-icon-badge {
                            background-color: #e2e8f0;
                            color: #0f172a;
                        }

                        .lw-crm-info-text {
                            font-size: 0.92rem;
                            color: #334155;
                            font-weight: 500;
                            word-break: break-all;
                        }

                        /* iOS Style Switch */
                        .lw-ios-switch {
                            position: relative;
                            display: inline-block;
                            width: 44px;
                            height: 24px;
                        }

                        .lw-ios-switch input {
                            opacity: 0;
                            width: 0;
                            height: 0;
                        }

                        .lw-ios-slider {
                            position: absolute;
                            cursor: pointer;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background-color: #e2e8f0;
                            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
                            border-radius: 24px;
                        }

                        .lw-ios-slider:before {
                            position: absolute;
                            content: "";
                            height: 18px;
                            width: 18px;
                            left: 3px;
                            bottom: 3px;
                            background-color: white;
                            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
                            border-radius: 50%;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                        }

                        .lw-ios-switch input:checked + .lw-ios-slider {
                            background-color: #1B6F20;
                        }

                        .lw-ios-switch input:checked + .lw-ios-slider:before {
                            transform: translateX(20px);
                        }

                        /* Edit profile floating icon button */
                        .lw-crm-edit-btn {
                            position: absolute;
                            top: 14px;
                            right: 14px;
                            width: 32px;
                            height: 32px;
                            border-radius: 50%;
                            background-color: #f8fafc;
                            border: 1px solid #e2e8f0;
                            color: #64748b;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                            cursor: pointer;
                        }

                        .lw-crm-edit-btn:hover {
                            background-color: #f1f5f9;
                            color: #1B6F20;
                            transform: scale(1.05);
                            border-color: #cbd5e1;
                        }

                        /* Round action button (e.g. plus button) */
                        .lw-crm-btn-round {
                            width: 28px;
                            height: 28px;
                            border-radius: 50%;
                            background-color: #f1f5f9;
                            border: 1px solid #e2e8f0;
                            color: #64748b;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                            cursor: pointer;
                            font-size: 12px;
                            text-decoration: none !important;
                        }

                        .lw-crm-btn-round:hover {
                            background-color: #e2e8f0;
                            color: #1B6F20;
                            transform: scale(1.05);
                            border-color: #cbd5e1;
                        }

                        /* Interactive elements (Selectize) */
                        .lw-crm-card select,
                        .lw-crm-card textarea {
                            font-size: 0.9rem !important;
                        }

                        .lw-crm-card .selectize-input {
                            border: 1px solid #e2e8f0 !important;
                            border-radius: 8px !important;
                            background: #f8fafc !important;
                            box-shadow: none !important;
                            padding: 8px 12px !important;
                            transition: all 0.2s ease;
                        }

                        .lw-crm-card .selectize-input.focus {
                            border-color: #1B6F20 !important;
                            background: #ffffff !important;
                            box-shadow: 0 0 0 3px rgba(27, 111, 32, 0.15) !important;
                        }

                        /* Custom switch wa alignment wrapper */
                        .lw-crm-switch-wrapper {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 16px;
                        }

                        .lw-crm-switch-wrapper:last-child {
                            margin-bottom: 0;
                        }

                        /* Notes section styles */
                        .lw-crm-notes-display {
                            font-size: 0.92rem;
                            color: #334155;
                            line-height: 1.5;
                            background: #f8fafc;
                            border-radius: 8px;
                            padding: 12px;
                            border: 1px dashed #cbd5e1;
                        }

                        .lw-crm-action-link {
                            color: #1B6F20;
                            font-weight: 600;
                            transition: color 0.15s ease;
                            cursor: pointer;
                            text-decoration: none !important;
                        }

                        .lw-crm-action-link:hover {
                            color: #114b15;
                        }
                    </style>
                    <div class="col-sm-12 col-md-3 col-lg-3 col-xl-3 lw-contact-crm-block p-0 m-0" :class="(!contact) ? 'lw-disabled-block-content' : ''" x-show="isContactCrmBlockOpened" style="overflow-y: auto; height: 100%;">
                        <!-- Header with Back Button (Mobile) -->
                        <div class="d-md-none bg-white p-3 border-bottom d-flex align-items-center mb-2" style="border-color: #e2e8f0 !important;">
                            <span class="btn btn-light btn-sm mr-3" @click.prevent="isContactCrmBlockOpened = false"><i class="fa fa-arrow-left"></i></span>
                            <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ __tr('Contact Info') }}</span>
                        </div>
                        
                        <template x-if="contact">
                            <div>
                                <!-- Profile Section Card -->
                                <div class="lw-crm-card lw-crm-profile-card">
                                    @if (hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                                    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Edit') }}" class="lw-crm-edit-btn lw-ajax-link-action" data-response-template="#lwEditContactBody" x-bind:href="__Utils.apiURL('{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})" data-toggle="modal" data-target="#lwEditContact">
                                        <i class="fa fa-pencil-alt"></i>
                                    </a>
                                    @endif
                                    @if (isVendorAdmin(getVendorId()) or hasVendorAccess('manage_contacts', 'delete_contacts'))
                                    <button type="button" class="btn btn-link p-0" style="position: absolute; right: 54px; top: 14px; width: 32px; height: 32px; border-radius: 50%; background-color: #fff0f2; border: 1px solid #ffe4e6; color: #e11d48; display: flex; align-items: center; justify-content: center; z-index: 2; transition: all 0.2s;" onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='#ffe4e6';" onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='#fff0f2';" @click.stop.prevent="deleteSingleContact(contact)" title="{{ __tr('Supprimer ce contact') }}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    @endif
                                    
                                    <!-- Avatar -->
                                    <div class="lw-crm-avatar" x-text="contact.name_initials || 'C'"></div>
                                    
                                    <!-- Contact Info -->
                                    <h2 class="lw-crm-contact-name" x-text="contact.full_name"></h2>
                                    <div class="lw-crm-contact-phone">
                                        @if(hasVendorAccess('hide_contact_phone_numbers'))
                                            <span x-text="contact.wa_id"></span>
                                        @else
                                            <span x-text="__Utils.formatAsLocaleNumber(Number(contact.wa_id))"></span>
                                        @endif
                                    </div>
                                </div>

                                <!-- About Section Card -->
                                <div class="lw-crm-card">
                                    <div class="lw-crm-section-header">
                                        <span>{{ __tr('À propos') }}</span>
                                    </div>
                                    <div class="lw-crm-info-row">
                                        <div class="lw-crm-icon-badge">
                                            <i class="fa fa-envelope"></i>
                                        </div>
                                        <div class="lw-crm-info-text" x-text="contact.email ? contact.email : '-'"></div>
                                    </div>
                                    <div class="lw-crm-info-row">
                                        <div class="lw-crm-icon-badge">
                                            <i class="fa fa-globe"></i>
                                        </div>
                                        <div class="lw-crm-info-text" x-text="contact.language_code ? contact.language_code : '-'"></div>
                                    </div>
                                </div>

                                <!-- Settings & Assignment Card -->
                                <div class="lw-crm-card">
                                    <div class="lw-crm-section-header">
                                        <span>{{ __tr('Paramètres et Assignation') }}</span>
                                    </div>
                                    <x-lw.form id="lwAssignSystemUserForm" :action="route('vendor.chat.assign_user.process')" data-callback="window.assignTeamMember">
                                        <input type="hidden" name="contactIdOrUid" :value="contact?._uid">
                                        
                                        @if(isAiBotAvailable())
                                        <div class="lw-crm-switch-wrapper">
                                            <div class="d-flex align-items-center">
                                                <div class="lw-crm-icon-badge">
                                                    <i class="fa fa-robot"></i>
                                                </div>
                                                <span class="lw-crm-info-text">{{ __tr('Enable AI Bot') }}</span>
                                            </div>
                                            <label class="lw-ios-switch">
                                                <input type="checkbox" x-model="isAiChatBotEnabled" id="lwEnableAiBot" value="1" @change="$el.closest('form').querySelector('button[type=submit]').click()">
                                                <span class="lw-ios-slider"></span>
                                                <input type="hidden" name="enable_ai_bot" :value="isAiChatBotEnabled ? '1' : ''">
                                            </label>
                                        </div>
                                        @endif
                                        
                                        <div class="lw-crm-switch-wrapper">
                                            <div class="d-flex align-items-center">
                                                <div class="lw-crm-icon-badge">
                                                    <i class="fa fa-reply-all"></i>
                                                </div>
                                                <span class="lw-crm-info-text">{{ __tr('Enable Reply Bot') }}</span>
                                            </div>
                                            <label class="lw-ios-switch">
                                                <input type="checkbox" x-model="isReplyBotEnable" id="lwEnableReplyBot" value="1" @change="$el.closest('form').querySelector('button[type=submit]').click()">
                                                <span class="lw-ios-slider"></span>
                                                <input type="hidden" name="enable_reply_bot" :value="isReplyBotEnable ? '1' : ''">
                                            </label>
                                        </div>

                                        <div class="mt-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="lw-crm-icon-badge">
                                                    <i class="fa fa-user-tag"></i>
                                                </div>
                                                <span class="lw-crm-info-text" style="font-weight: 600;">{{ __tr('Assigné à') }}</span>
                                            </div>
                                            <div class="pl-0">
                                                <x-lw.input-field id="lwCurrentlyAssignedUserUid" type="selectize" data-form-group-class="m-0" name="assigned_users_uid" class="custom-select custom-select-sm" data-selected="{{ $currentlyAssignedUserUid }}" x-model="currentlyAssignedUserUid" @change="$el.closest('form').querySelector('button[type=submit]').click()" style="border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; font-size: 15px; color: #1f2937; height: auto;">
                                                    <x-slot name="selectOptions">
                                                        <option value="">{{  __tr('Unassigned') }}</option>
                                                        <option value="no_one">{{  __tr('Unassigned') }}</option>
                                                        @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                                        <option value="{{ $vendorMessagingUser->_uid }}">{{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} @if($vendorMessagingUser->_uid == getUserUID()) ({{  __tr('You') }}) @endif</option>
                                                        @endforeach
                                                    </x-slot>
                                                </x-lw.input-field>
                                                <button type="submit" class="d-none"></button>
                                            </div>
                                        </div>
                                    </x-lw.form>
                                </div>

                                <!-- Labels/Tags Card -->
                                <div class="lw-crm-card" style="position: relative; z-index: 10; overflow: visible;">
                                    <div class="lw-crm-section-header">
                                        <span>{{ __tr('Étiquettes') }}</span>
                                        <a data-pre-callback="appFuncs.clearContainer" title="{{ __tr('Manage Labels') }}" class="lw-crm-btn-round lw-ajax-link-action" data-response-template="#lwManageContactLabelsBody" x-bind:href="__Utils.apiURL('{{ route('vendor.chat.contact_labels.read', [ 'contactUid']) }}', {'contactUid': contact._uid})" data-toggle="modal" data-target="#lwManageContactLabels">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </div>
                                    <x-lw.form data-callback="onUpdateLabels" id="lwAssignContactLabelsForm" :action="route('vendor.chat.assign_labels.process')">
                                        <input type="hidden" name="contactUid" x-bind:value="contact._uid" />
                                        <div class="mb-2 pl-0" x-show="labelsElement"></div>
                                        <div>
                                            <select class="border-0 lw-borderers-selectize" id="lwAssignLabelsField" data-form-group-class="m-0" x-bind:data-selected="assignedLabelIds" name="contact_labels[]" multiple>
                                                <option value="">{{ __tr('Select Labels') }}</option>
                                                @foreach($allLabels as $label)
                                                    <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex justify-content-end mt-2">
                                                <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 24px; padding: 4px 16px; font-weight: 500;">{{ __tr('Valider') }}</button>
                                            </div>
                                        </div>
                                    </x-lw.form>
                                </div>

                                 <!-- Rappels & Relances Card -->
                                 <div class="lw-crm-card" style="position: relative;">
                                     <div class="lw-crm-section-header">
                                         <span><i class="fas fa-bell text-warning mr-1"></i> {{ __tr('Rappels & Relances') }}</span>
                                         <button type="button" class="lw-crm-btn-round" @click="openContactReminderModal(contact)" title="{{ __tr('Programmer un rappel') }}">
                                             <i class="fa fa-plus"></i>
                                         </button>
                                     </div>
                                     
                                     <template x-if="contact && contact.active_reminder">
                                         <div class="p-3 rounded border border-warning" style="background: rgba(255, 193, 7, 0.08);">
                                             <div class="d-flex align-items-center justify-content-between mb-2">
                                                 <span class="badge badge-warning px-2 py-1" style="font-size: 11px;">
                                                     <i class="fas fa-clock mr-1"></i>
                                                     <span x-text="contact.active_reminder.scheduled_at_formatted"></span>
                                                 </span>
                                                 <span class="badge badge-secondary" x-text="contact.active_reminder.action_type == 'auto_message' ? '{{ __tr('WhatsApp Auto') }}' : '{{ __tr('Notification') }}'"></span>
                                             </div>
                                             <p class="small text-dark font-weight-500 mb-2" x-text="contact.active_reminder.title_note"></p>
                                             <div class="d-flex justify-content-end">
                                                 <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px; border-radius: 12px;" @click="cancelContactReminder(contact)">
                                                     <i class="fas fa-times mr-1"></i> {{ __tr('Annuler le rappel') }}
                                                 </button>
                                             </div>
                                         </div>
                                     </template>
                                     
                                     <template x-if="contact && !contact.active_reminder">
                                         <div class="text-center py-2 text-muted small">
                                             <i class="far fa-bell-slash d-block mb-1 opacity-5" style="font-size: 20px;"></i>
                                             <span>{{ __tr('Aucune relance programmée') }}</span>
                                             <div class="mt-2">
                                                 <button type="button" class="btn btn-sm btn-outline-success px-3" style="border-radius: 20px;" @click="openContactReminderModal(contact)">
                                                     <i class="fas fa-plus mr-1"></i> {{ __tr('Ajouter un rappel') }}
                                                 </button>
                                             </div>
                                         </div>
                                     </template>
                                 </div>

                                 <!-- Notes Card -->
                                <div class="lw-crm-card" x-data="{openNotesEdit:false,contactNotes:''}" x-effect="contactNotes = contact?.__data?.contact_notes || ''">
                                    <div class="lw-crm-section-header">
                                        <span>{{ __tr('Notes / Remarques') }}</span>
                                        <button type="button" class="lw-crm-btn-round shadow-none" @click="openNotesEdit = true" x-show="!openNotesEdit" title="{{ __tr('Edit') }}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </div>
                                    <div x-show="!openNotesEdit" class="lw-ws-pre-line lw-crm-notes-display mb-0" x-text="contact?.__data?.contact_notes || 'Aucune note.'"></div>
                                    
                                    <x-lw.form x-show="openNotesEdit" id="lwNotesForm" :action="route('vendor.chat.update_notes.process')" x-cloak>
                                        <input type="hidden" name="contactIdOrUid" :value="contact?._uid || contact?._id || contact?.wa_id">
                                        <div class="form-group mb-2">
                                            <textarea name="contact_notes" id="lwContactNotes" class="form-control" style="font-size: 14px; border: 1px solid #d1d7db; border-radius: 8px; box-shadow: none;" x-model="contactNotes" rows="4" placeholder="{{ __tr('Saisissez vos notes ici...') }}"></textarea>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2" style="gap: 8px;">
                                            <button type="button" class="btn btn-sm text-secondary" style="background: transparent; font-weight: 500;" @click="openNotesEdit = false; contactNotes = contact?.__data?.contact_notes || '';">{{ __tr('Cancel') }}</button>
                                            <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 24px; padding: 4px 16px; font-weight: 500;" @click="openNotesEdit = false; if(!contact['__data']) { contact['__data'] = {}} contact['__data']['contact_notes'] = contactNotes;">{!! __tr('Save') !!}</button>
                                        </div>
                                    </x-lw.form>
                                </div>

                                <!-- Client Orders Card inside WhatsApp Chat CRM Sidebar -->
                                @if(vendorPlanDetails('ecommerce_catalog', 1)['is_limit_available'])
                                <div class="lw-crm-card" x-data="{
                                    ordersList: [],
                                    isLoadingOrders: false,
                                    fetchOrders() {
                                        var cUid = contact?._uid || contact?._id || contact?.wa_id;
                                        if(!cUid) return;
                                        this.isLoadingOrders = true;
                                        var self = this;
                                        __DataRequest.get('{{ route('vendor.ecommerce.contact_orders', ['contactUid' => 'CONTACT_UID']) }}'.replace('CONTACT_UID', cUid), {}, function(response) {
                                            self.isLoadingOrders = false;
                                            var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                                            if (isSuccess) {
                                                var rawOrders = (response.data && response.data.orders) ? response.data.orders : (response.orders || []);
                                                self.ordersList = Array.isArray(rawOrders) ? rawOrders : [];
                                            }
                                        });
                                    },
                                    updateStatus(orderUid, newStatus) {
                                        var self = this;
                                        __DataRequest.post('{{ route("vendor.ecommerce.orders.update_status", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), { status: newStatus }, function(response) {
                                            var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                                            var msg = response.message || (response.data && response.data.message) || 'Statut mis à jour avec succès.';
                                            if (isSuccess) {
                                                showSuccessMessage(msg);
                                                var ord = self.ordersList.find(o => o._uid === orderUid);
                                                if(ord) ord.status = newStatus;
                                            } else {
                                                showErrorMessage(msg || 'Erreur de mise à jour.');
                                            }
                                        });
                                    },
                                    deleteOrder(orderUid) {
                                        var self = this;
                                        if (confirm('{{ __tr('Voulez-vous vraiment supprimer cette commande ?') }}')) {
                                            __DataRequest.post('{{ route("vendor.ecommerce.orders.delete", ["orderUid" => "ORDER_UID"]) }}'.replace('ORDER_UID', orderUid), {}, function(response) {
                                                var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                                                var msg = response.message || (response.data && response.data.message) || 'Commande supprimée.';
                                                if (isSuccess) {
                                                    showSuccessMessage(msg);
                                                    self.ordersList = self.ordersList.filter(o => o._uid !== orderUid);
                                                    if (typeof window.onUpdateContactDetails === 'function') {
                                                        window.onUpdateContactDetails();
                                                    }
                                                } else {
                                                    showErrorMessage(msg || 'Erreur lors de la suppression.');
                                                }
                                            });
                                        }
                                    },
                                    getOrderTotal(ord) {
                                        if (!ord || !ord.order_details) return 0;
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        if (details && details.total_price) return Number(details.total_price);
                                        var total = 0;
                                        if (details && details.items && Array.isArray(details.items)) {
                                            details.items.forEach(i => { total += (Number(i.price) || 0) * (Number(i.quantity) || 1); });
                                        }
                                        return total;
                                    },
                                    getOrderSummaryText(ord) {
                                        if (!ord || !ord.order_details) return '';
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        if (details && details.items && Array.isArray(details.items) && details.items.length > 0) {
                                            return details.items.map(i => (i.name || 'Produit') + ' (x' + (i.quantity || 1) + ')').join(', ');
                                        }
                                        return 'Commande WhatsApp';
                                    },
                                    selectedReceiptOrder: null,
                                    openOrderReceiptModal(ord) {
                                        this.selectedReceiptOrder = ord;
                                        this.$nextTick(function() {
                                            $('#chatOrderReceiptModal').appendTo('body').modal('show');
                                        });
                                    },
                                    getItems(ord) {
                                        if (!ord || !ord.order_details) return [];
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        if (details && details.items && Array.isArray(details.items)) {
                                            return details.items;
                                        }
                                        return [];
                                    },
                                    getAdditionalFee(ord) {
                                        if (!ord || !ord.order_details) return 0;
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        return details && details.additional_fee ? Number(details.additional_fee) : 0;
                                    },
                                    getAdditionalFeeLabel(ord) {
                                        if (!ord || !ord.order_details) return 'Frais additionnels';
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        return details && details.additional_fee_label ? details.additional_fee_label : 'Frais additionnels';
                                    },
                                    getDeliveryAddress(ord) {
                                        if (!ord || !ord.order_details) return '';
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        return details && details.delivery_address ? details.delivery_address : '';
                                    },
                                    getDeliveryDate(ord) {
                                        if (!ord || !ord.order_details) return '';
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        return details && details.delivery_date ? details.delivery_date : '';
                                    },
                                    getSource(ord) {
                                        if (!ord || !ord.order_details) return 'Manuel';
                                        var details = ord.order_details;
                                        if (typeof details === 'string') {
                                            try { details = JSON.parse(details); } catch(e) {}
                                        }
                                        return details && details.source ? details.source : 'Manuel';
                                    },
                                    formatDate(dateStr) {
                                        if(!dateStr) return '';
                                        return new Date(dateStr).toLocaleString('fr-FR', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
                                    },
                                    productsList: [],
                                    productSearchTerm: '',
                                    categoriesList: [],
                                    categoryFilter: '',
                                    isCreatingOrder: false,
                                    showCreateOrderForm: false,
                                    orderItems: [
                                        { product_id: '', quantity: 1, custom_price: '' }
                                    ],
                                    orderAdditionalFee: 0,
                                    orderAdditionalFeeLabel: 'Frais',
                                    orderAddress: '',
                                    orderDate: '',
                                    fetchProducts() {
                                        var self = this;
                                        __DataRequest.get('<?= route("vendor.ecommerce.products") ?>', {}, function(response) {
                                            var rawProds = (response.data && response.data.products) ? response.data.products : (response.products || []);
                                            if (rawProds && rawProds.data && Array.isArray(rawProds.data)) {
                                                self.productsList = rawProds.data;
                                            } else if (Array.isArray(rawProds)) {
                                                self.productsList = rawProds;
                                            } else {
                                                self.productsList = [];
                                            }
                                        });
                                    },
                                    filteredProductsList() {
                                        var list = this.productsList;
                                        if (this.productSearchTerm) {
                                            var term = this.productSearchTerm.toLowerCase();
                                            list = list.filter(p => p.name && p.name.toLowerCase().includes(term));
                                        }
                                        if (this.categoryFilter) {
                                            list = list.filter(p => this.categoryFilter === 'uncategorized'
                                                ? !p.category
                                                : (p.category && p.category._uid === this.categoryFilter));
                                        }
                                        return list;
                                    },
                                    fetchCategories() {
                                        var self = this;
                                        __DataRequest.get('<?= route("vendor.ecommerce.categories") ?>', {}, function(response) {
                                            if (response.reaction == 1 || response.reaction_code == 1) {
                                                self.categoriesList = response.data.categories || [];
                                            }
                                        });
                                    },
                                    openCreateOrderForm() {
                                        this.showCreateOrderForm = !this.showCreateOrderForm;
                                        if(this.showCreateOrderForm) {
                                            this.fetchProducts();
                                            this.fetchCategories();
                                        }
                                    },
                                    addOrderItem() {
                                        this.orderItems.push({ product_id: '', quantity: 1, custom_price: '' });
                                    },
                                    removeOrderItem(index) {
                                        if (this.orderItems.length > 1) {
                                            this.orderItems.splice(index, 1);
                                        }
                                    },
                                    onItemProductChange(index) {
                                        var item = this.orderItems[index];
                                        if (!item || !item.product_id) return;
                                        var p = this.productsList.find(i => i._id == item.product_id || i._uid == item.product_id);
                                        if (p) {
                                            item.custom_price = p.price;
                                        }
                                    },
                                    formatProductOptionLabel(prod) {
                                        if(!prod || !prod.name) return '';
                                        var shortName = prod.name.length > 32 ? prod.name.substring(0, 32) + '...' : prod.name;
                                        return shortName + ' (' + Number(prod.price).toLocaleString() + ' CFA)';
                                    },
                                    getOrderSubtotal() {
                                        var sub = 0;
                                        for (var i = 0; i < this.orderItems.length; i++) {
                                            var qty = Number(this.orderItems[i].quantity) || 1;
                                            var price = Number(this.orderItems[i].custom_price) || 0;
                                            sub += qty * price;
                                        }
                                        return sub;
                                    },
                                    getOrderTotal() {
                                        return this.getOrderSubtotal() + (Number(this.orderAdditionalFee) || 0);
                                    },
                                    saveManualOrder() {
                                        if (this.isCreatingOrder) return;
                                        var cUid = contact?._uid || contact?._id || contact?.wa_id;
                                        if(!cUid) {
                                            showErrorMessage('Client non identifié.');
                                            return;
                                        }
                                        var validItems = this.orderItems.filter(it => !!it.product_id);
                                        if (validItems.length === 0) {
                                            showErrorMessage('Veuillez sélectionner au moins un produit.');
                                            return;
                                        }
                                        this.isCreatingOrder = true;
                                        var self = this;
                                        __DataRequest.post('<?= route("vendor.ecommerce.orders.create_manual") ?>', {
                                            contact_id: cUid,
                                            items: validItems,
                                            additional_fee: this.orderAdditionalFee,
                                            additional_fee_label: this.orderAdditionalFeeLabel,
                                            delivery_address: this.orderAddress,
                                            delivery_date: this.orderDate
                                        }, function(response) {
                                            self.isCreatingOrder = false;
                                            var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                                            if (isSuccess) {
                                                var msg = response.message || (response.data && response.data.message) || 'Commande créée avec succès !';
                                                showSuccessMessage(msg);
                                                self.showCreateOrderForm = false;
                                                self.orderItems = [{ product_id: '', quantity: 1, custom_price: '' }];
                                                self.orderAdditionalFee = 0;
                                                self.orderAddress = '';
                                                self.orderDate = '';
                                                var newOrd = (response.data && response.data.order) ? response.data.order : response.order;
                                                if (newOrd) {
                                                    self.ordersList.unshift(newOrd);
                                                }
                                                self.fetchOrders();
                                            } else {
                                                var errMsg = response.message || (response.data && response.data.message) || 'Erreur de création.';
                                                showErrorMessage(errMsg);
                                            }
                                        });
                                    }
                                }" x-init="fetchOrders(); fetchProducts();" x-effect="if(contact?._uid || contact?._id || contact?.wa_id) fetchOrders()">
                                    <div class="lw-crm-section-header d-flex justify-content-between align-items-center mb-2">
                                        <span><i class="fas fa-shopping-bag text-emerald mr-1" style="color: #10b981;"></i> {{ __tr('Commandes du client') }}</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-muted" @click="fetchOrders()" title="{{ __tr('Rafraîchir') }}">
                                            <i class="fas fa-sync-alt" :class="isLoadingOrders ? 'fa-spin' : ''"></i>
                                        </button>
                                    </div>

                                    <!-- Quick Order Creation Button -->
                                    <button type="button" class="btn btn-sm btn-block text-white font-weight-bold mb-3 shadow-sm" style="background: #10b981; border: none; border-radius: 8px;" @click="openCreateOrderForm()">
                                        <span x-text="showCreateOrderForm ? '{{ __tr('Fermer Formulaire') }}' : '{{ __tr('+ Enregistrer une Commande') }}'"></span>
                                    </button>

                                    <!-- Inline Manual Order Form -->
                                    <div x-show="showCreateOrderForm" class="p-2 mb-3 rounded shadow-sm" style="background: #f8fafc; border: 1.5px solid #10b981; max-width: 100%; box-sizing: border-box;" x-cloak>
                                        <div class="font-weight-bold text-xs text-dark mb-2">{{ __tr('Nouvelle Commande Vendeur') }}</div>
                                        
                                        <!-- MULTI-PRODUCTS SECTION -->
                                        <div class="border rounded p-2 mb-2 bg-white">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="text-xs font-weight-bold text-dark mb-0">{{ __tr('Produit(s) *') }}</label>
                                                <button type="button" class="btn btn-xs btn-outline-success py-0 px-1 font-weight-bold text-xs" style="border-radius: 4px; font-size: 0.75rem;" @click="addOrderItem()">+ Ajouter un produit</button>
                                            </div>

                                            <select class="form-control form-control-sm text-xs mb-2" style="height: 26px; border-radius: 4px;" x-model="categoryFilter" x-show="categoriesList.length > 0">
                                                <option value="">{{ __tr('Toutes les catégories') }}</option>
                                                <option value="uncategorized">{{ __tr('Sans catégorie') }}</option>
                                                <template x-for="cat in categoriesList" :key="cat._uid">
                                                    <option :value="cat._uid" x-text="cat.name"></option>
                                                </template>
                                            </select>

                                            <template x-for="(item, idx) in orderItems" :key="idx">
                                                <div class="mb-2 p-1 border rounded bg-light">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <select class="form-control form-control-sm text-xs font-weight-bold" style="height: 26px; border-radius: 4px;" x-model="item.product_id" @change="onItemProductChange(idx)">
                                                            <option value="">-- {{ __tr('Choisir Produit') }} --</option>
                                                            <template x-for="prod in filteredProductsList()" :key="prod._id">
                                                                <option :value="prod._id" x-text="formatProductOptionLabel(prod)"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button" class="btn btn-link text-danger p-0 ml-1" @click="removeOrderItem(idx)" x-show="orderItems.length > 1" title="Supprimer">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                    </div>
                                                    <div class="row no-gutters" style="gap: 4px;">
                                                        <div class="col">
                                                            <input type="number" min="1" class="form-control form-control-sm text-xs" style="height: 24px; border-radius: 4px;" x-model="item.quantity" placeholder="Qté">
                                                        </div>
                                                        <div class="col">
                                                            <input type="number" class="form-control form-control-sm text-xs" style="height: 24px; border-radius: 4px;" x-model="item.custom_price" placeholder="Prix CFA">
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- ADDITIONAL FEES -->
                                        <div class="row no-gutters mb-2" style="gap: 4px;">
                                            <div class="col">
                                                <label class="text-xs font-weight-bold text-dark mb-0" style="font-size: 0.72rem;">{{ __tr('Frais Livraison (CFA)') }}</label>
                                                <input type="number" min="0" class="form-control form-control-sm text-xs" style="height: 26px; border-radius: 4px;" x-model="orderAdditionalFee" placeholder="ex: 2000">
                                            </div>
                                            <div class="col">
                                                <label class="text-xs font-weight-bold text-dark mb-0" style="font-size: 0.72rem;">{{ __tr('Libellé') }}</label>
                                                <input type="text" class="form-control form-control-sm text-xs" style="height: 26px; border-radius: 4px;" x-model="orderAdditionalFeeLabel" placeholder="ex: Livraison">
                                            </div>
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="text-xs font-weight-bold text-dark mb-0" style="font-size: 0.72rem;">{{ __tr('Adresse Livraison') }}</label>
                                            <input type="text" class="form-control form-control-sm text-xs custom-input-white" style="border-radius: 6px; height: 26px;" x-model="orderAddress" placeholder="ex: Cocody">
                                        </div>

                                        <!-- TOTAL SUMMARY -->
                                        <div class="p-2 mb-2 rounded bg-white border text-xs" style="border-radius: 6px; border-color: #a7f3d0 !important; background: #ecfdf5 !important;">
                                            <div class="d-flex justify-content-between font-weight-bold text-dark mb-1">
                                                <span>{{ __tr('Sous-total:') }}</span>
                                                <span x-text="getOrderSubtotal().toLocaleString() + ' CFA'"></span>
                                            </div>
                                            <div class="d-flex justify-content-between text-dark mb-1" x-show="Number(orderAdditionalFee) > 0">
                                                <span x-text="(orderAdditionalFeeLabel || 'Frais') + ':'"></span>
                                                <span x-text="Number(orderAdditionalFee).toLocaleString() + ' CFA'"></span>
                                            </div>
                                            <div class="d-flex justify-content-between text-emerald font-weight-bold border-top pt-1" style="color: #059669;">
                                                <span>{{ __tr('Total Commande:') }}</span>
                                                <span x-text="getOrderTotal().toLocaleString() + ' CFA'"></span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end" style="gap: 5px;">
                                            <button type="button" class="btn btn-sm btn-light text-xs font-weight-bold" style="border-radius: 6px;" @click="showCreateOrderForm = false">{{ __tr('Annuler') }}</button>
                                            <button type="button" class="btn btn-sm btn-emerald text-white text-xs font-weight-bold" style="background: #10b981; border: none; border-radius: 6px;" @click="saveManualOrder()" :disabled="isCreatingOrder">
                                                <span x-show="!isCreatingOrder">{{ __tr('Créer') }}</span>
                                                <span x-show="isCreatingOrder"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div x-show="ordersList.length === 0 && !isLoadingOrders && !showCreateOrderForm" class="text-muted text-xs">
                                        {{ __tr('Aucune commande enregistrée pour ce client.') }}
                                    </div>

                                    <div x-show="ordersList.length > 0" class="space-y-2">
                                        <template x-for="ord in ordersList" :key="ord._uid">
                                            <div class="p-2 border rounded mb-2 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1.5px solid #cbd5e1 !important;">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="font-weight-bold text-dark text-xs" x-text="'#' + ord._uid.substring(0, 8)"></span>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge text-white" 
                                                              :class="{
                                                                  'bg-success': ord.status === 'delivered',
                                                                  'bg-info': ord.status === 'shipped' || ord.status === 'processing',
                                                                  'bg-primary': ord.status === 'confirmed',
                                                                  'bg-warning text-dark': ord.status === 'validated',
                                                                  'bg-danger': ord.status === 'cancelled'
                                                              }"
                                                              style="font-size: 0.65rem; border-radius: 12px;"
                                                              x-text="ord.status === 'delivered' ? 'Livrée' : (ord.status === 'shipped' ? 'En livraison' : (ord.status === 'confirmed' ? 'Confirmée' : (ord.status === 'cancelled' ? 'Annulée' : 'Nouvelle')))">
                                                        </span>
                                                        <button type="button" @click="deleteOrder(ord._uid)" class="btn btn-sm btn-link text-danger p-0 ml-2" style="font-size: 0.8rem; line-height: 1; text-decoration: none;" title="{{ __tr('Supprimer la commande') }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="text-xs font-weight-bold text-emerald mb-1" style="color: #059669;" x-text="getOrderTotal(ord).toLocaleString() + ' CFA'"></div>
                                                <div class="text-xs text-dark mb-1 font-weight-bold" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="getOrderSummaryText(ord)"></div>
                                                <div class="text-xs text-muted mb-2" x-text="new Date(ord.created_at).toLocaleDateString('fr-FR', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'})"></div>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <button type="button" @click="openOrderReceiptModal(ord)" class="btn btn-sm btn-link p-0 text-xs font-weight-bold text-emerald" style="color: #10b981;">
                                                        <i class="fa fa-receipt mr-1"></i> {{ __tr('Voir Reçu') }}
                                                    </button>
                                                    <select class="form-control form-control-sm text-xs font-weight-bold custom-input-white" style="border-radius: 6px; height: 26px; padding: 2px 6px; width: 110px;" :value="ord.status" @change="updateStatus(ord._uid, $event.target.value)">
                                                        <option value="validated">Nouvelle</option>
                                                        <option value="confirmed">Confirmer</option>
                                                        <option value="processing">En préparation</option>
                                                        <option value="shipped">En livraison</option>
                                                        <option value="delivered">Livrée</option>
                                                        <option value="cancelled">Annuler</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- CHAT ORDER RECEIPT MODAL -->
                                    <div class="modal fade" id="chatOrderReceiptModal" tabindex="-1" role="dialog" aria-hidden="true" x-cloak>
                                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                                                <div class="modal-header bg-emerald text-white p-4" style="background: #10b981;">
                                                    <div class="d-flex align-items-center justify-content-between w-100">
                                                        <div>
                                                            <h5 class="modal-title font-weight-bold mb-1 text-white" x-text="'Reçu & Fiche de Commande #' + (selectedReceiptOrder ? selectedReceiptOrder._uid.substring(0, 8) : '')"></h5>
                                                            <span class="badge badge-light font-weight-bold px-3 py-1" style="border-radius: 12px;" x-text="selectedReceiptOrder ? formatDate(selectedReceiptOrder.created_at) : ''"></span>
                                                        </div>
                                                        <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close" style="outline: none;">
                                                            <span aria-hidden="true" style="font-size: 1.8rem; color: #ffffff;">&times;</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 p-3 rounded" style="background: #f8fafc; border: 1.5px solid #cbd5e1; gap: 10px;">
                                                        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                                            <button type="button" @click="window.print()" class="btn btn-emerald font-weight-bold text-white shadow-sm" style="background: #10b981; border: none; border-radius: 8px;">
                                                                <i class="fa fa-print mr-1"></i> {{ __tr('Imprimer ce reçu') }}
                                                            </button>
                                                            <a href="<?= route('vendor.settings.read', ['pageType' => 'orders']) ?>" target="_blank" class="btn btn-outline-secondary font-weight-bold" style="border-radius: 8px;">
                                                                <i class="fa fa-external-link-alt mr-1"></i> {{ __tr('Gestion des commandes') }}
                                                            </a>
                                                        </div>

                                                        <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">
                                                            <i class="fa fa-times mr-1"></i> {{ __tr('Fermer') }}
                                                        </button>
                                                    </div>

                                                    <!-- Receipt Header Info Grid -->
                                                    <div class="row mb-4">
                                                        <div class="col-md-6 mb-3 mb-md-0">
                                                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-user text-emerald mr-1"></i> {{ __tr('Informations Client') }}</h6>
                                                                <h6 class="font-weight-bold text-dark mb-1" x-text="contact ? (contact.first_name + ' ' + contact.last_name) : '{{ __tr('Client WhatsApp') }}'"></h6>
                                                                <p class="mb-1 text-dark small" x-show="contact && contact.wa_id">
                                                                    <strong>WhatsApp:</strong> <span x-text="contact ? contact.wa_id : ''"></span>
                                                                </p>
                                                                <template x-if="getDeliveryAddress(selectedReceiptOrder)">
                                                                    <p class="small text-dark mb-1"><strong>{{ __tr('Livraison à:') }}</strong> <span x-text="getDeliveryAddress(selectedReceiptOrder)"></span></p>
                                                                </template>
                                                                <template x-if="getDeliveryDate(selectedReceiptOrder)">
                                                                    <p class="small text-dark mb-0"><strong>{{ __tr('Date souhaitée:') }}</strong> <span x-text="getDeliveryDate(selectedReceiptOrder)"></span></p>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 rounded h-100" style="background: #f1f5f9; border: 1.5px solid #cbd5e1;">
                                                                <h6 class="font-weight-bold text-uppercase text-muted small mb-2"><i class="fa fa-info-circle text-emerald mr-1"></i> {{ __tr('Détails Commande & Source') }}</h6>
                                                                <p class="small text-dark mb-1"><strong>{{ __tr('Référence:') }}</strong> <span x-text="selectedReceiptOrder ? '#' + selectedReceiptOrder._uid.substring(0, 8) : ''"></span></p>
                                                                <p class="small text-dark mb-1"><strong>{{ __tr('Source / Agent:') }}</strong> <span class="font-weight-bold text-primary" x-text="getSource(selectedReceiptOrder)"></span></p>
                                                                <p class="small text-dark mb-0"><strong>{{ __tr('Date de création:') }}</strong> <span x-text="selectedReceiptOrder ? formatDate(selectedReceiptOrder.created_at) : ''"></span></p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Formatted Products Table -->
                                                    <div class="table-responsive mb-4">
                                                        <table class="table table-bordered mb-0" style="border-radius: 10px; overflow: hidden; border: 1.5px solid #cbd5e1;">
                                                            <thead class="bg-light text-uppercase small font-weight-bold text-dark">
                                                                <tr>
                                                                    <th>{{ __tr('Article / Produit') }}</th>
                                                                    <th class="text-center" style="width: 100px;">{{ __tr('Quantité') }}</th>
                                                                    <th class="text-right" style="width: 140px;">{{ __tr('Prix Unitaire') }}</th>
                                                                    <th class="text-right" style="width: 160px;">{{ __tr('Sous-Total') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <template x-for="(item, idx) in getItems(selectedReceiptOrder)" :key="idx">
                                                                    <tr>
                                                                        <td class="align-middle font-weight-bold text-dark" x-text="item.name || 'Produit'"></td>
                                                                        <td class="align-middle text-center font-weight-bold" x-text="'x' + (item.quantity || 1)"></td>
                                                                        <td class="align-middle text-right" x-text="Number(item.price || 0).toLocaleString() + ' CFA'"></td>
                                                                        <td class="align-middle text-right font-weight-bold text-dark" x-text="(Number(item.price || 0) * Number(item.quantity || 1)).toLocaleString() + ' CFA'"></td>
                                                                    </tr>
                                                                </template>
                                                                <template x-if="getItems(selectedReceiptOrder).length === 0">
                                                                    <tr>
                                                                        <td colspan="4" class="text-center py-3 text-muted">
                                                                            {{ __tr('Détails des articles enregistrés.') }}
                                                                        </td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                            <tfoot style="background: #ecfdf5;">
                                                                <template x-if="getAdditionalFee(selectedReceiptOrder) > 0">
                                                                    <tr>
                                                                        <td colspan="3" class="text-right font-weight-bold text-dark small">
                                                                            <span x-text="getAdditionalFeeLabel(selectedReceiptOrder)"></span>:
                                                                        </td>
                                                                        <td class="text-right font-weight-bold text-dark small" x-text="getAdditionalFee(selectedReceiptOrder).toLocaleString() + ' CFA'">
                                                                        </td>
                                                                    </tr>
                                                                </template>
                                                                <tr>
                                                                    <td colspan="3" class="text-right font-weight-bold text-uppercase text-dark" style="font-size: 1.05rem;">
                                                                        {{ __tr('Montant Total à Payer:') }}
                                                                    </td>
                                                                    <td class="text-right font-weight-bold text-emerald" style="font-size: 1.2rem; color: #059669;" x-text="getOrderTotal(selectedReceiptOrder).toLocaleString() + ' CFA'">
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Additional Links and buttons Card --}}
                                @if ($__env->yieldPushContent('chatRightSidebarAdditionalLinksAndButtons'))
                                <div class="lw-crm-card">
                                    <div class="lw-crm-section-header">
                                        <span>{{  __tr('Links and buttons') }}</span>
                                    </div>
                                    <div>
                                        @stack('chatRightSidebarAdditionalLinksAndButtons')
                                    </div>
                                </div>
                                @endif
                                
                                <div class="pb-5 px-3">
                                    @stack('chatRightSidebarFooter')
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<x-lw.modal id="lwMediaUploadAndSend" :header="__tr('Send Media')" :hasForm="true"
    data-pre-callback="clearModelContainer">
    <!--  document form -->
    <x-lw.form id="lwMediaUploadAndSendForm" :action="route('vendor.chat_message_media.send.process')"
        data-callback="appFuncs.modelSuccessCallback" :data-callback-params="['modalId' => '#lwMediaUploadAndSend']">
        <!-- form body -->
        <input type="hidden" name="contact_uid" x-bind:value="contact?._uid">
        <div id="lwWhatsappAttachment" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwWhatsappAttachment-template">
            <% if(__tData.mediaType == 'document') { %>
            <div class="form-group col-sm-12">
                <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                    data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader" data-instant-upload="true"
                    data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>" id="lwDocumentField" data-file-input-element="#lwDocumentMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                <input id="lwDocumentMedia" type="hidden" value="" name="uploaded_media_file_name" />
                <input type="hidden" value="document" name="media_type" />
            </div>
            <% } else if(__tData.mediaType == 'image') { %>
                <div class="form-group col-sm-12">
                    <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" id="lwImageField" data-file-input-element="#lwImageMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                    <input id="lwImageMedia" type="hidden" value="" name="uploaded_media_file_name" />
                    <input type="hidden" value="image" name="media_type" />
                </div>
                <% } else if(__tData.mediaType == 'video') { %>
                    <div class="form-group col-sm-12">
                        <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" id="lwVideoField" data-file-input-element="#lwVideoMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                        <input id="lwVideoMedia" type="hidden" value="" name="uploaded_media_file_name" />
                        <input type="hidden" value="video" name="media_type" />
                    </div>
                <% } else if(__tData.mediaType == 'audio') { %>
                    <div class="form-group col-sm-12">
                        <input id="lwAudioMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Audio') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_audio') ?>" id="lwAudioField" data-file-input-element="#lwAudioMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_audio') ?>' />
                        <input id="lwAudioMedia" type="hidden" value="" name="uploaded_media_file_name" />
                        <input type="hidden" value="audio" name="media_type" />
                    </div>
                <% } %>
                <input id="lwRawDocumentMedia" type="hidden" value="" name="raw_upload_data"/>
                <% if(__tData.mediaType != 'audio') { %>
                <div>
                    <label for="lwMediaCaptionText">{{  __tr('Caption/Text') }}</label>
                    <textarea name="caption" id="lwCaptionField" class="form-control" rows="2"></textarea>
                </div>
                <% } %>
        </script>
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Send') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Cancel') }}</button>
        </div>
    </x-lw.form>
    <!--/  document form -->
</x-lw.modal>
 <!-- Edit Contact Modal -->
 @include('contact.contact-edit-modal-partial')
 @include('whatsapp.quick-reply-modal')
 @include('whatsapp.ecommerce-product-picker-modal')
 @include('whatsapp.recording-modal')
 <!--/ Edit Contact Modal -->
 <!-- Manage Labels Modal -->
  <x-lw.modal id="lwManageContactLabels" :header="__tr('Gérer les étiquettes')" :hasForm="true">
        <!-- form body -->
        <div id="lwManageContactLabelsBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwManageContactLabelsBody-template">
            <fieldset class="pb-3 mb-3 border-bottom">
                <x-lw.form data-callback="onNewLabelCreated" id="lwManageContactLabelsForm" :action="route('vendor.chat.label.create.write')">
                    <div class="d-flex flex-column" style="gap: 12px;">
                        <h6 class="font-weight-bold text-dark m-0">{{ __tr('Nouvelle étiquette') }}</h6>
                        
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <!-- Label Title -->
                            <input type="text" id="lwLabelFieldTitle" name="title" x-model="newLabelTitle" class="form-control form-control-sm flex-grow-1" placeholder="{{ __tr('Nom...') }}" required="true" style="border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: none;">
                            
                            <!-- Text Color -->
                            <div class="d-flex align-items-center" style="gap: 4px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; background: #fff;" title="{{ __tr('Couleur du texte') }}">
                                <span class="text-muted" style="font-size: 0.65rem; font-weight: 700;">TXT</span>
                                <input type="color" name="text_color" x-model="newLabelTextColor" style="width: 20px; height: 20px; padding: 0; border: none; cursor: pointer; background: none;">
                            </div>
                            
                            <!-- BG Color -->
                            <div class="d-flex align-items-center" style="gap: 4px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; background: #fff;" title="{{ __tr('Couleur de fond') }}">
                                <span class="text-muted" style="font-size: 0.65rem; font-weight: 700;">FOND</span>
                                <input type="color" name="bg_color" x-model="newLabelBgColor" style="width: 20px; height: 20px; padding: 0; border: none; cursor: pointer; background: none;">
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-dark btn-sm p-0" style="border-radius: 6px; height: 32px; width: 36px; display: flex; align-items: center; justify-content: center; background-color: #0f172a; border-color: #0f172a;">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        
                        <!-- Real-time Preview Box -->
                        <div class="d-flex align-items-center" style="gap: 8px; margin-top: -4px;">
                            <span class="text-muted" style="font-size: 0.75rem;">{{ __tr('Aperçu :') }}</span>
                            <span class="px-2 py-1 font-weight-bold rounded-pill text-truncate shadow-sm d-inline-block"
                                  x-text="newLabelTitle || '{{ __tr('Exemple') }}'"
                                  :style="'background-color: ' + newLabelBgColor + '; color: ' + newLabelTextColor + '; font-size: 0.7rem; border: 1px solid rgba(0,0,0,0.1); max-width: 150px; line-height: 1;'">
                            </span>
                        </div>
                    </div>
                </x-lw.form>
            </fieldset>

            <fieldset class="border-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="font-weight-bold text-dark m-0">{{  __tr('Étiquettes existantes') }}</h6>
                    <span class="badge badge-light border text-muted" x-text="allLabels.length" style="border-radius: 10px;"></span>
                </div>
                
                <div class="list-group" style="max-height: 280px; overflow-y: auto; padding-right: 4px; gap: 6px;">
                    <template x-for="labelItem in allLabels">
                        <div x-bind:class="'lw-contact-label-'+labelItem._uid" class="list-group-item p-2" style="border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f8fafc; transition: all 0.2s ease;">
                            <x-lw.form data-callback="onUpdateContactDetails" class="w-100" :action="route('vendor.chat.label.update.write')">
                                <input type="hidden" name="labelUid" x-bind:value="labelItem._uid" />
                                
                                <div class="d-flex align-items-center justify-content-between" style="gap: 8px;">
                                    
                                    <!-- Rendered Preview badge -->
                                    <div style="flex: 0 0 85px; display: flex; justify-content: center;">
                                        <span class="px-2 py-1 font-weight-bold rounded-pill text-truncate shadow-sm d-block text-center w-100"
                                              x-text="labelItem.title || '{{ __tr('Nom') }}'"
                                              :style="'background-color: ' + labelItem.bg_color + '; color: ' + labelItem.text_color + '; font-size: 0.7rem; border: 1px solid rgba(0,0,0,0.1); line-height: 1.2;'">
                                        </span>
                                    </div>
                                    
                                    <!-- Label Title Edit -->
                                    <input type="text" name="title" x-model="labelItem.title" class="form-control form-control-sm flex-grow-1" required="true" placeholder="{{ __tr('Nom...') }}" style="border-radius: 4px; border: 1px solid #cbd5e1; padding: 2px 6px; font-size: 0.8rem; height: 28px;">
                                    
                                    <!-- Text Color Picker -->
                                    <input type="color" name="text_color" x-model="labelItem.text_color" title="{{ __tr('Texte') }}" style="border: 1px solid #cbd5e1; width: 26px; height: 26px; border-radius: 4px; cursor: pointer; padding: 0; background: #fff; flex-shrink: 0;">
                                    
                                    <!-- BG Color Picker -->
                                    <input type="color" name="bg_color" x-model="labelItem.bg_color" title="{{ __tr('Fond') }}" style="border: 1px solid #cbd5e1; width: 26px; height: 26px; border-radius: 4px; cursor: pointer; padding: 0; background: #fff; flex-shrink: 0;">
                                    
                                    <!-- Action Buttons -->
                                    <div class="d-flex align-items-center" style="gap: 4px; flex-shrink: 0;">
                                        <button type="submit" class="btn btn-sm btn-light p-0 shadow-sm" title="{{ __tr('Enregistrer') }}" style="border-radius: 4px; border: 1px solid #cbd5e1; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: white;">
                                            <i class="fa fa-save text-success" style="font-size: 0.8rem;"></i>
                                        </button>
                                        <a class="btn btn-sm btn-light p-0 shadow-sm lw-ajax-link-action" data-confirm="{{ __tr('Confirmer la suppression ?') }}" data-callback="updateManageLabelsList" data-method="post" x-bind:href="__Utils.apiURL('{{ route('vendor.chat.label.delete.write', ['labelUid']) }}',{'labelUid': labelItem._uid})" title="{{ __tr('Supprimer') }}" style="border-radius: 4px; border: 1px solid #cbd5e1; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: white;">
                                            <i class="fa fa-trash text-danger" style="font-size: 0.8rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </x-lw.form>
                        </div>
                    </template>
                </div>
            </fieldset>
    </script>
        <!-- form footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 20px; padding: 6px 20px;">{{ __tr('Fermer') }}</button>
        </div>
    <!--/  Edit Contact Form -->
</x-lw.modal>
 {{-- /Manage labels Modal --}}
    <!-- Toasts Container -->
    <div x-on:show-toast.window="toasts.push({ id: Date.now(), msg: $event.detail.msg, type: $event.detail.type || 'success' }); setTimeout(() => { toasts.shift() }, 3000)" class="position-fixed p-3" style="z-index: 9999; right: 0; bottom: 0;">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast show mb-2 shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 10px; overflow: hidden; opacity: 0.95; min-width: 250px;">
                <div class="toast-body d-flex align-items-center" :class="toast.type === 'success' ? 'bg-success text-white' : 'bg-dark text-white'">
                    <i class="fa fa-check-circle mr-2" x-show="toast.type === 'success'"></i>
                    <i class="fa fa-info-circle mr-2" x-show="toast.type === 'info'"></i>
                    <span x-text="toast.msg" style="font-weight: 500;"></span>
                </div>
            </div>
        </template>
    </div>

</div>
<script>
     (function() {
        'use strict';
     document.addEventListener('alpine:init', () => {
        Alpine.data('initialMessageData', () => ({
            // whatsappMessageLogs: @json($whatsappMessageLogs),
            whatsappMessageLogs: [],
            messagePaginatePage: 0,
            contactsPaginatePage: 0,
            isDirectMessageDeliveryWindowOpened: {{ $isDirectMessageDeliveryWindowOpened ?: 0 }},
            directMessageDeliveryWindowOpenedTillMessage: '{{ $directMessageDeliveryWindowOpenedTillMessage }}',
            contact:@json($contact),
            isContactDetailsUpdated: false,
            currentlyAssignedUserUid:'{{ $currentlyAssignedUserUid }}',
            isAiChatBotEnabled: "{{ $isAiChatBotEnabled }}",
            isReplyBotEnable: "{{ $isReplyBotEnable }}",
            search: "",
            chatSearchText: "",
            isChatSearchOpened: false,
            search_labels: "",
            isLoadingContacts: false,
            newLabelTitle: "",
            newLabelTextColor: "#ffffff",
            newLabelBgColor: "#00a884",
            
            // Lightbox state
            lightboxOpen: false,
            lightboxImageSrc: '',
            lastProcessedMsgUid: null,

            init() {
                // Watch for new messages to play sound
                this.$watch('whatsappMessageLogs', (newValue) => {
                    if (newValue && newValue.length > 0) {
                        let latestMsg = newValue[newValue.length - 1];
                        
                        // Check if this is a genuinely new message (different from the last one we saw)
                        if (this.lastProcessedMsgUid && this.lastProcessedMsgUid !== latestMsg._uid) {
                            if (latestMsg.is_incoming_message == 1 && !latestMsg.is_read) {
                                try {
                                    var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                                    var oscillator = audioCtx.createOscillator();
                                    var gainNode = audioCtx.createGain();
                                    oscillator.connect(gainNode);
                                    gainNode.connect(audioCtx.destination);
                                    
                                    oscillator.type = 'triangle';
                                    oscillator.frequency.setValueAtTime(440, audioCtx.currentTime); // A4 note
                                    oscillator.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.1);
                                    
                                    gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime); // Louder volume
                                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
                                    
                                    oscillator.start(audioCtx.currentTime);
                                    oscillator.stop(audioCtx.currentTime + 0.4);
                                } catch (e) {
                                    console.warn('Web Audio API not supported or blocked', e);
                                }
                            }
                        }
                        // Update the tracker
                        this.lastProcessedMsgUid = latestMsg._uid;
                    }
                });

                // Watch for labels changes to keep Selectize widget updated
                this.$watch('assignedLabelIds', (newValue) => {
                    var selectizeEl = $('#lwAssignLabelsField');
                    if (selectizeEl.length && selectizeEl[0].selectize) {
                        var selectizeInstance = selectizeEl[0].selectize;
                        var currentValue = selectizeInstance.getValue();
                        var expectedValue = _.values(newValue);
                        if (_.difference(currentValue, expectedValue).length > 0 || _.difference(expectedValue, currentValue).length > 0) {
                            selectizeInstance.clear(true);
                            selectizeInstance.setValue(['']);
                            selectizeInstance.setValue(expectedValue);
                        }
                    }
                });
            },
            isLoadingMoreContacts: false,
            isLoadingEarlierMessages: false,
            toasts: [],
            contacts: {},
            assignedLabelIds: [],
            allLabels: @json($allLabels),
            replyingToMessage: null,
            setReply: function(messageLog) {
                this.replyingToMessage = messageLog;
                setTimeout(function() {
                    $('.lw-input-emoji')[0].emojioneArea.setFocus();
                }, 100);
            },
            cancelReply: function() {
                this.replyingToMessage = null;
            },
            deleteSingleContact: function(contactItem) {
                if(confirm('{{ __tr("Êtes-vous sûr de vouloir effacer la conversation et masquer ce contact de la liste ?") }}')) {
                    var self = this;
                    var url = '{{ route("vendor.chat_message.delete.process", ["contactUid" => "CONTACT_UID"]) }}'.replace('CONTACT_UID', contactItem._uid);
                    __DataRequest.post(url, {}, function(response) {
                        var isSuccess = response.reaction == 1 || (response.data && response.data.reaction == 1);
                        var msg = response.message || (response.data && response.data.message) || 'Conversation effacée avec succès.';
                        if (isSuccess) {
                            showSuccessMessage(msg);
                            if (self.contacts[contactItem._uid]) {
                                delete self.contacts[contactItem._uid];
                            } else if (self.contacts[contactItem._id]) {
                                delete self.contacts[contactItem._id];
                            } else {
                                for (var key in self.contacts) {
                                    if (self.contacts[key]._id == contactItem._id || self.contacts[key]._uid == contactItem._uid) {
                                        delete self.contacts[key];
                                    }
                                }
                            }
                            if (self.contact && (self.contact._id == contactItem._id || self.contact._uid == contactItem._uid)) {
                                self.contact = null;
                            }
                        } else {
                            var errMsg = response.message || (response.data && response.data.message) || 'Erreur de suppression du contact.';
                            showErrorMessage(errMsg);
                        }
                    });
                }
            },
            get filteredContacts() {
                return _.reverse(_.sortBy(this.contacts, [function(o) { return o.last_message?.messaged_at; }]));
            },
            labelsElement : function() {
                var selectizeEl = $('#lwAssignLabelsField');
                if (!selectizeEl.length) return;
                
                var selectizeInstance = selectizeEl[0].selectize;
                if (!selectizeInstance) {
                    var $labelsElement = selectizeEl.selectize({
                        maxItems: null,
                        items: _.values(this.assignedLabelIds),
                        valueField: '_id',
                        labelField: 'title',
                        searchField: 'title',
                        options: this.allLabels,
                        create: false,
                        closeAfterSelect: true,
                        render: {
                            item: function (item, escape) {
                                return (
                                '<div class="" style="color:'+item.text_color+';background-color:'+item.bg_color+';" >' +
                                (item.title
                                    ? '<span>' + escape(item.title) + "</span>"
                                    : "") +
                                "</div>"
                                );
                            },
                            option: function (item, escape) {
                                return (
                                '<div class="p-1 rounded m-2" style="color:'+item.text_color+';background-color:'+item.bg_color+';">' +
                                '<span>' +
                                escape(item.title) +
                                "</span>" +
                                "</div>"
                                );
                            },
                        }
                    });
                    selectizeInstance = $labelsElement[0].selectize;
                }
                
                var currentValue = selectizeInstance.getValue();
                var expectedValue = _.values(this.assignedLabelIds);
                if (_.difference(currentValue, expectedValue).length > 0 || _.difference(expectedValue, currentValue).length > 0) {
                    selectizeInstance.clear(true);
                    selectizeInstance.setValue(['']);
                    selectizeInstance.setValue(expectedValue);
                }
            }
        }));
    });
})();
</script>
@if (vendorPlanDetails('ecommerce_catalog', 1)['is_limit_available'])
    @push('chatRightSidebarAdditionalLinksAndButtons')
        <button type="button" class="btn btn-success btn-sm lw-btn-block-mobile mb-2" data-toggle="modal" data-target="#lwECommerceProductPicker">
            <i class="fa fa-shopping-cart"></i> {{ __tr('Send Shopify Products') }}
        </button>
    @endpush
@endif
@push('head')
    {!! __yesset('dist/emojionearea/emojionearea.min.css', true) !!}
    @if(isset($whatsjetCallingAddonActive) && $whatsjetCallingAddonActive)
        <link rel="stylesheet" href="{{ route('addon.WhatsJetCallingAddon.assets', ['path' => 'calling.css']) }}?v={{ time() }}">
    @endif
@endpush
@push('appScripts')
{!! __yesset('dist/emojionearea/emojionearea.min.js', true) !!}
@if(isset($whatsjetCallingAddonActive) && $whatsjetCallingAddonActive)
    <script src="{{ route('addon.WhatsJetCallingAddon.assets', ['path' => 'calling.js']) }}?v={{ time() }}"></script>
@endif

<!-- Contact block template -->
<script type="text/template" id="lwBlockContact-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to block this Contact?') }}</p>
</script>
<!-- /Contact block template -->

 <!-- Contact unblock template -->
<script type="text/template" id="lwUnblockContact-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to unblock this Contact?') }}</p>
</script>
<!-- /Contact unblock template -->
    
<script>
(function($) {
    'use strict';
    window.isPageRefresh = false;
    window.messagePaginatePage = 1;
    window.searchValue = '';
    window.showUnreadContactsOnly = 0;

    // Delegate click event for Lightbox
    $(document).on('click', '.lw-chat-message-item img', function(e) {
        // Exclude system icons and emoji
        if ($(this).hasClass('emojionearea-emoji') || $(this).width() < 30) return;
        e.preventDefault();
        var src = $(this).attr('src');
        if (src) {
            var chatData = document.querySelector('[x-data="initialMessageData"]');
            if (chatData && chatData.__x) {
                chatData.__x.$data.lightboxImageSrc = src;
                chatData.__x.$data.lightboxOpen = true;
            } else {
                // Alpine v3 way
                Alpine.$data(chatData).lightboxImageSrc = src;
                Alpine.$data(chatData).lightboxOpen = true;
            }
        }
    });

    window.loadEarlierMessages = function(responseData, callbackParams) {
        __DataRequest.updateModels({ isLoadingEarlierMessages: true });
        __DataRequest.get(__Utils.apiURL('{!! route('vendor.chat_message.contact.view', ['contactUid', 'way' => 'prepend', 'page', 'assigned' => ($assigned ?? '')]) !!}',{'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.messagePaginatePage}),{}, function() {
            __DataRequest.updateModels({ isLoadingEarlierMessages: false });
        });
        if(callbackParams) {
            appFuncs.modelSuccessCallback(responseData, callbackParams);
        }
    };
    window.onUpdateContactDetails = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL('{!! route('vendor.chat_message.contact.view', ['contactUid', 'current_page', 'assigned' => ($assigned ?? '')]) !!}',{'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'current_page':'current_page='+ window.messagePaginatePage}),{}, function() {});
        if(callbackParams) {
            appFuncs.modelSuccessCallback(responseData, callbackParams);
        }
    };
    window.contactsPaginatePage = 1;
    window.loadMoreContacts = function(responseData, callbackParams) {
        __DataRequest.updateModels({ isLoadingMoreContacts: true });
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'way' => 'append', 'search' => '', 'unread_only' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&', 'search':'search='+ window.searchValue + '&', 'unread_only':'unread_only='+ window.showUnreadContactsOnly + '&'}),{}, function() {
            __DataRequest.updateModels({ isLoadingMoreContacts: false });
        });
    };
    window.searchContacts = function(responseData, callbackParams) {
        // Find all checked inputs and retrieve their values
        var selectedLabels = $('.lw-search-labels:checked').val();
        selectedLabels = selectedLabels ? selectedLabels : '';
        window.contactsPaginatePage = 1;
        __DataRequest.updateModels({
            contactsPaginatePage: 1,
        });
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'way' => '', 'search' => '','selected_labels' => '', 'unread_only' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&', 'search':'search='+ window.searchValue + '&','selected_labels':'selected_labels='+ selectedLabels + '&', 'unread_only':'unread_only='+ window.showUnreadContactsOnly + '&'}),{}, function() {});
    };
    window.updateContactList = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&'}),{}, function() {});
    };
    window.updateContactInfo = function(responseData) {
        var assignUserEl = $('#lwCurrentlyAssignedUserUid');
        if(assignUserEl.length && assignUserEl[0].selectize && responseData.data && responseData.data.currentlyAssignedUserUid !== undefined) {
            assignUserEl[0].selectize.setValue(responseData.data.currentlyAssignedUserUid);
        }
    };
    window.onNewLabelCreated = function(responseData) {
        $('#lwLabelFieldTitle').val('');
        var chatData = document.querySelector('[x-data="initialMessageData"]');
        if (chatData) {
            var alpineData = Alpine.$data(chatData) || chatData.__x?.$data;
            if (alpineData) {
                alpineData.newLabelTitle = '';
                alpineData.newLabelTextColor = '#ffffff';
                alpineData.newLabelBgColor = '#00a884';
            }
        }
    };
    window.updateManageLabelsList = function(responseData) {
        if(responseData.reaction == 1) {
            window.onUpdateContactDetails();
        }
    };
    window.onUpdateLabels = function(responseData) {
        if(responseData.reaction == 1) {
            // Updated automatically via client_models for the top-level contact
            // But we also need to update it in the contacts array/object
            if (responseData.client_models && responseData.client_models.contact) {
                var updatedContact = responseData.client_models.contact;
                var chatData = document.querySelector('[x-data="initialMessageData"]');
                if (chatData) {
                    var alpineData = Alpine.$data(chatData) || chatData.__x?.$data;
                    if (alpineData && alpineData.contacts) {
                        if (Array.isArray(alpineData.contacts)) {
                            var index = alpineData.contacts.findIndex(function(c) {
                                return c._uid === updatedContact._uid;
                            });
                            if (index !== -1) {
                                alpineData.contacts.splice(index, 1, updatedContact);
                            }
                        } else {
                            alpineData.contacts[updatedContact._uid] = updatedContact;
                            alpineData.contacts = Object.assign({}, alpineData.contacts);
                        }
                    }
                }
            }
        }
    };
    window.updateContactList();
    window.onUpdateContactDetails();
    window.chatFormReset = function(response) {
        appFuncs.resetForm(response);
        window.dispatchEvent(new CustomEvent('chat-message-sent'));
    };
    window.lwMessengerEmojiArea = $(".lw-input-emoji").emojioneArea({
    useInternalCDN: true,
    pickerPosition: "top",
    searchPlaceholder: "{{ __tr('Search') }}",
    buttonTitle: "{{ __tr('Use the TAB key to insert emoji faster') }}",
    events: {
        'emojibtn.click': function (editor, event) {
            this.hidePicker();
        },
        keyUp: function (editor, event) {
            if (event && event.which == 13 && !event.shiftKey && $.trim(this.getText())) { // On Enter
                $('.lw-input-emoji').val(this.getText());
                $('#whatsAppMessengerForm').submit();
                this.hidePicker();
                window.chatFormReset();
            }
        }
    }
}); 
})(jQuery);
window.openContactReminderModal = function(contact) {
    if (!contact) return;
    var form = document.getElementById('lwContactReminderForm');
    if (form) {
        form.action = __Utils.apiURL("{{ route('vendor.contact.reminder.store', ['contactUid']) }}", {'contactUid': contact._uid});
        if (contact.active_reminder) {
            $('#lwContactReminderActiveNotice').show();
            $('#lwReminderNoticeTime').text(contact.active_reminder.scheduled_at_formatted);
        } else {
            $('#lwContactReminderActiveNotice').hide();
        }
        $('#lwContactReminderModal').modal('show');
    }
};

window.cancelContactReminder = function(contact) {
    if (!contact || !contact.active_reminder) return;
    if (confirm("{{ __tr('Voulez-vous vraiment annuler cette relance ?') }}")) {
        var cancelUrl = __Utils.apiURL("{{ route('vendor.contact.reminder.cancel', ['contactUid']) }}", {'contactUid': contact._uid});
        __DataRequest.post(cancelUrl, {}, function(response) {
            if (response && response.reaction == 1) {
                showSuccessNotification(response.message || '{{ __tr("Relance annulée avec succès !") }}');
                var chatData = document.querySelector('[x-data="initialMessageData"]');
                if (chatData) {
                    var alpineData = Alpine.$data(chatData) || chatData.__x?.$data;
                    if (alpineData) {
                        if (alpineData.contact) {
                            alpineData.contact.active_reminder = null;
                        }
                        if (alpineData.contacts && alpineData.contact) {
                            if (Array.isArray(alpineData.contacts)) {
                                var item = alpineData.contacts.find(function(c) { return c._uid === alpineData.contact._uid; });
                                if (item) item.active_reminder = null;
                            } else if (alpineData.contacts[alpineData.contact._uid]) {
                                alpineData.contacts[alpineData.contact._uid].active_reminder = null;
                                alpineData.contacts = Object.assign({}, alpineData.contacts);
                            }
                        }
                    }
                }
                if (typeof window.onUpdateContactDetails === 'function') {
                    window.onUpdateContactDetails();
                }
            }
        });
    }
};
</script>

@php
    $vendorApprovedTemplates = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel::where('vendors__id', getVendorId())
        ->whereIn('status', ['APPROVED', 'approved', 1])
        ->get(['_uid', 'template_name', 'language', '__data']);

    $vendorBotReplies = \App\Yantrana\Components\BotReply\Models\BotReplyModel::where('vendors__id', getVendorId())
        ->where('status', 1)
        ->get(['_uid', 'name', 'reply_text']);
@endphp

<style>
/* UI-UX Pro Max Modal Custom Styling */
#lwContactReminderModal .modal-content {
    border: none !important;
    border-radius: 20px !important;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15) !important;
    overflow: hidden !important;
    background: #ffffff !important;
}
#lwContactReminderModal .modal-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    padding: 1.25rem 1.75rem !important;
    border-bottom: none !important;
}
#lwContactReminderModal .modal-title {
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 1.15rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}
#lwContactReminderModal .close {
    color: #ffffff !important;
    opacity: 0.9 !important;
    text-shadow: none !important;
    transition: transform 0.2s ease;
}
#lwContactReminderModal .close:hover {
    transform: scale(1.1);
    opacity: 1 !important;
}

/* Time Shortcut Chips */
.lw-reminder-chip {
    border-radius: 25px !important;
    padding: 6px 14px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    border: 1.5px solid #e2e8f0 !important;
    background: #f8fafc !important;
    color: #475569 !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    cursor: pointer !important;
}
.lw-reminder-chip:hover {
    border-color: #10b981 !important;
    color: #059669 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.12) !important;
}
.lw-reminder-chip.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border-color: #10b981 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3) !important;
}

/* Option Cards for Action Type */
.lw-action-type-card {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 8px;
    text-align: center;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.25s ease;
    position: relative;
    user-select: none;
}
.lw-action-type-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.04);
}
.lw-action-type-card.active-notif {
    border-color: #f59e0b !important;
    background: rgba(245, 158, 11, 0.06) !important;
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.15) !important;
}
.lw-action-type-card.active-auto {
    border-color: #10b981 !important;
    background: rgba(16, 185, 129, 0.06) !important;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.15) !important;
}
.lw-action-type-card.active-tmpl {
    border-color: #3b82f6 !important;
    background: rgba(59, 130, 246, 0.06) !important;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.15) !important;
}

.lw-action-icon-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    font-size: 1rem;
    transition: transform 0.2s ease;
}
.lw-action-type-card:hover .lw-action-icon-circle {
    transform: scale(1.1);
}

.lw-custom-textarea {
    border-radius: 12px !important;
    border: 1.5px solid #cbd5e1 !important;
    padding: 12px 16px !important;
    font-size: 0.9rem !important;
    box-shadow: none !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
}
.lw-custom-textarea:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
}

.lw-custom-select {
    border-radius: 12px !important;
    border: 1.5px solid #cbd5e1 !important;
    height: 44px !important;
    padding: 8px 14px !important;
    font-size: 0.9rem !important;
    font-weight: 600 !important;
}
.lw-custom-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}

/* Modal Footer */
#lwContactReminderModal .modal-footer {
    background: #f8fafc !important;
    border-top: 1px solid #f1f5f9 !important;
    padding: 1.1rem 1.75rem !important;
}
.lw-btn-submit-reminder {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 25px !important;
    padding: 10px 24px !important;
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3) !important;
    transition: all 0.2s ease !important;
}
.lw-btn-submit-reminder:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4) !important;
    color: #ffffff !important;
}
.lw-btn-cancel-reminder {
    border-radius: 25px !important;
    padding: 10px 22px !important;
    font-weight: 600 !important;
    font-size: 0.9rem !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #64748b !important;
    background: #ffffff !important;
}
.lw-btn-cancel-reminder:hover {
    background: #f1f5f9 !important;
    color: #334155 !important;
}

/* Unread Badge Green Pulse Animation */
.lw-unread-pulse-badge {
    box-shadow: 0 0 0 0 rgba(38, 211, 102, 0.7);
    animation: lw-badge-pulse 2s infinite;
}
@keyframes lw-badge-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(38, 211, 102, 0.7);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(38, 211, 102, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(38, 211, 102, 0);
    }
}
</style>

<!-- Contact Reminder Modal (Project Standard x-lw.modal) -->
<x-lw.modal id="lwContactReminderModal" :header="__tr('Schedule a Reminder')" :hasForm="true">
    <x-lw.form id="lwContactReminderForm" method="post" data-callback="onReminderSaved" action="" x-data="{
        presetTime: 'tomorrow_same_time', 
        actionType: 'notification', 
        customDatetime: '', 
        selectedTemplateName: '', 
        selectedTemplateLanguage: 'fr',
        titleNote: '',
        templatesList: {{ json_encode($vendorApprovedTemplates->keyBy('template_name')) }},
        templateFields: [],
        templateFieldValues: {},
        onTemplateChange(name) {
            this.selectedTemplateName = name;
            this.templateFields = [];
            this.templateFieldValues = {};
            if (!name || !this.templatesList[name]) return;
            
            var tmplObj = this.templatesList[name];
            this.selectedTemplateLanguage = tmplObj.language || 'fr';
            var components = (tmplObj.__data && tmplObj.__data.template && tmplObj.__data.template.components) ? tmplObj.__data.template.components : [];
            
            var fields = [];
            components.forEach(function(comp) {
                if (comp.type === 'BODY' && comp.text) {
                    var matches = comp.text.match(/\{\{\d+\}\}/g);
                    if (matches) {
                        var uniqueVars = Array.from(new Set(matches));
                        uniqueVars.forEach(function(v) {
                            var num = v.replace(/[\{\}]/g, '');
                            var exampleVal = (comp.example && comp.example.body_text && comp.example.body_text[0] && comp.example.body_text[0][num - 1]) ? comp.example.body_text[0][num - 1] : '';
                            fields.push({
                                key: 'field_' + num,
                                label: '{{ __tr('Variable') }} {{' + num + '}}',
                                example: exampleVal
                            });
                        });
                    }
                } else if (comp.type === 'HEADER' && comp.format === 'TEXT' && comp.text) {
                    var matches = comp.text.match(/\{\{\d+\}\}/g);
                    if (matches) {
                        var uniqueVars = Array.from(new Set(matches));
                        uniqueVars.forEach(function(v) {
                            var num = v.replace(/[\{\}]/g, '');
                            fields.push({
                                key: 'header_field_' + num,
                                label: '{{ __tr('En-tête Variable') }} {{' + num + '}}',
                                example: ''
                            });
                        });
                    }
                }
            });
            this.templateFields = fields;
        }
    }">
        <div class="lw-form-modal-body p-4">
            <div id="lwContactReminderActiveNotice" class="alert alert-warning mb-4 py-2 px-3" style="display:none; border-radius: 12px; border: 1.5px solid #fcd34d; background: #fffbeb;">
                <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                {{ __tr('A reminder is already scheduled for:') }} <strong id="lwReminderNoticeTime" class="text-dark"></strong>. {{ __tr('Saving a new reminder will overwrite the previous one.') }}
            </div>

            <!-- Shortcut Buttons -->
            <label class="form-label font-weight-700 text-dark mb-2" style="font-size: 0.88rem; color: #1e293b;">
                <i class="fas fa-clock text-emerald mr-1" style="color: #10b981;"></i> {{ __tr('Time Shortcut:') }}
            </label>
            <div class="d-flex flex-wrap mb-4" style="gap: 8px;">
                <button type="button" class="lw-reminder-chip" :class="presetTime == 'in_2_hours' ? 'active' : ''" @click="presetTime = 'in_2_hours'"><i class="fas fa-bolt mr-1"></i> {{ __tr('In 2h') }}</button>
                <button type="button" class="lw-reminder-chip" :class="presetTime == 'today_14h' ? 'active' : ''" @click="presetTime = 'today_14h'"><i class="fas fa-sun mr-1"></i> {{ __tr('Today 2pm') }}</button>
                <button type="button" class="lw-reminder-chip" :class="presetTime == 'today_18h' ? 'active' : ''" @click="presetTime = 'today_18h'"><i class="fas fa-moon mr-1"></i> {{ __tr('Today 6pm') }}</button>
                <button type="button" class="lw-reminder-chip" :class="presetTime == 'tomorrow_same_time' ? 'active' : ''" @click="presetTime = 'tomorrow_same_time'"><i class="far fa-calendar-alt mr-1"></i> {{ __tr('Tomorrow Same Time') }}</button>
                <button type="button" class="lw-reminder-chip" :class="presetTime == 'custom' ? 'active' : ''" @click="presetTime = 'custom'"><i class="fas fa-sliders-h mr-1"></i> {{ __tr('Custom') }}</button>
            </div>
            <input type="hidden" name="preset_time" :value="presetTime">

            <div x-show="presetTime == 'custom'" class="form-group mb-4" x-cloak>
                <label class="form-label font-weight-700 text-dark" style="font-size: 0.88rem;">{{ __tr('Date & Time:') }}</label>
                <input type="datetime-local" class="form-control lw-custom-select" name="custom_datetime" x-model="customDatetime">
            </div>

            <!-- Action Type Card Selection -->
            <label class="form-label font-weight-700 text-dark mb-2" style="font-size: 0.88rem; color: #1e293b;">
                <i class="fas fa-layer-group text-primary mr-1"></i> {{ __tr('Reminder Type:') }}
            </label>
            <div class="row mb-4">
                <div class="col-4 px-1">
                    <div class="lw-action-type-card" :class="actionType == 'notification' ? 'active-notif' : ''" @click="actionType = 'notification'">
                        <input type="radio" name="action_type" value="notification" x-model="actionType" class="d-none">
                        <div class="lw-action-icon-circle" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="font-weight-700 text-dark" style="font-size: 0.83rem;">{{ __tr('Internal Notification') }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ __tr('Alert for agent') }}</div>
                    </div>
                </div>
                <div class="col-4 px-1">
                    <div class="lw-action-type-card" :class="actionType == 'auto_message' ? 'active-auto' : ''" @click="actionType = 'auto_message'">
                        <input type="radio" name="action_type" value="auto_message" x-model="actionType" class="d-none">
                        <div class="lw-action-icon-circle" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="font-weight-700 text-dark" style="font-size: 0.83rem;">{{ __tr('WhatsApp Message') }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ __tr('Direct message on WhatsApp') }}</div>
                    </div>
                </div>
                <div class="col-4 px-1">
                    <div class="lw-action-type-card" :class="actionType == 'template_message' ? 'active-tmpl' : ''" @click="actionType = 'template_message'">
                        <input type="radio" name="action_type" value="template_message" x-model="actionType" class="d-none">
                        <div class="lw-action-icon-circle" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="font-weight-700 text-dark" style="font-size: 0.83rem;">{{ __tr('Message After 24h') }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ __tr('Message to send after 24h') }}</div>
                    </div>
                </div>
            </div>

            <!-- Bot Reply Selector (when actionType == 'auto_message') -->
            <div x-show="actionType == 'auto_message'" class="form-group mb-3" x-cloak>
                <label class="form-label font-weight-700 text-dark mb-1" style="font-size: 0.85rem; color: #1e293b;">
                    <i class="fas fa-robot text-primary mr-1"></i> {{ __tr('Insert Predefined Bot Reply:') }}
                </label>
                <select class="form-control lw-custom-select" @change="if ($el.value) { titleNote = $el.value; }">
                    <option value="">{{ __tr('-- Select a Bot Reply --') }}</option>
                    @foreach($vendorBotReplies as $bot)
                        <option value="{{ $bot->reply_text }}">{{ $bot->name }} ({{ Str::limit($bot->reply_text, 45) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- WhatsApp Template Selector (when actionType == 'template_message') -->
            <div x-show="actionType == 'template_message'" class="form-group mb-3" x-cloak>
                <label class="form-label font-weight-700 text-dark" style="font-size: 0.88rem;">{{ __tr('Select WhatsApp Template:') }}</label>
                <select class="form-control lw-custom-select" name="template_name" x-model="selectedTemplateName" @change="onTemplateChange($el.value)">
                    <option value="">{{ __tr('-- Select a template --') }}</option>
                    @foreach($vendorApprovedTemplates as $tmpl)
                        <option value="{{ $tmpl->template_name }}" data-lang="{{ $tmpl->language ?? 'fr' }}">{{ $tmpl->template_name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="template_language" :value="selectedTemplateLanguage">
                <small class="form-text text-muted mt-1"><i class="fas fa-shield-alt text-success mr-1"></i> {{ __tr('Meta compliant for messaging outside 24h window.') }}</small>
            </div>

            <!-- Meta Template Dynamic Variables -->
            <div x-show="actionType == 'template_message' && templateFields.length > 0" class="card p-3 mb-4" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px;" x-cloak>
                <div class="font-weight-700 text-dark mb-2 small d-flex align-items-center">
                    <i class="fas fa-sliders-h text-primary mr-1.5"></i> {{ __tr('Variables du modèle Meta :') }}
                </div>
                <div class="row">
                    <template x-for="field in templateFields" :key="field.key">
                        <div class="col-6 mb-2">
                            <label class="form-label text-muted mb-1" style="font-size: 0.78rem; font-weight: 600;" x-text="field.label"></label>
                            <input type="text" :name="field.key" x-model="templateFieldValues[field.key]" class="form-control form-control-sm lw-custom-select" :placeholder="field.example ? ('Ex: ' + field.example) : '{{ __tr('Ex: Valentin ou {first_name}') }}'" required>
                        </div>
                    </template>
                </div>
                <small class="text-muted mt-1" style="font-size: 0.73rem;">
                    <i class="fas fa-info-circle text-info mr-1"></i> {{ __tr('Vous pouvez écrire du texte fixe ou utiliser {first_name}, {full_name}, {phone}.') }}
                </small>
            </div>

            <!-- Note / Message Body -->
            <div class="form-group mb-0">
                <label class="form-label font-weight-700 text-dark" style="font-size: 0.88rem;" x-text="actionType == 'template_message' ? '{{ __tr('Reminder Note:') }}' : (actionType == 'auto_message' ? '{{ __tr('WhatsApp Text Message:') }}' : '{{ __tr('Internal Reminder Note:') }}')"></label>
                <textarea class="form-control lw-custom-textarea" name="title_note" x-model="titleNote" rows="3" placeholder="{{ __tr('Ex: Follow up with customer to confirm order...') }}" :required="actionType != 'template_message'"></textarea>
            </div>
        </div>
        <div class="modal-footer d-flex justify-content-between align-items-center">
            <button type="button" class="btn lw-btn-cancel-reminder" data-dismiss="modal">{{ __tr('Close') }}</button>
            <button type="submit" class="btn lw-btn-submit-reminder">
                <i class="fas fa-check-circle mr-2"></i> {{ __tr('Save') }}
            </button>
        </div>
    </x-lw.form>
</x-lw.modal>

<script type="text/javascript">
function onReminderSaved(response) {
    if (response && response.reaction == 1) {
        $('#lwContactReminderModal').modal('hide');
        showSuccessNotification(response.message || '{{ __tr("Rappel enregistré !") }}');
        
        var chatData = document.querySelector('[x-data="initialMessageData"]');
        if (chatData) {
            var alpineData = Alpine.$data(chatData) || chatData.__x?.$data;
            if (alpineData) {
                if (alpineData.contact && response.data && response.data.reminder) {
                    alpineData.contact.active_reminder = response.data.reminder;
                }
                if (alpineData.contacts && response.data && response.data.reminder) {
                    if (Array.isArray(alpineData.contacts)) {
                        var item = alpineData.contacts.find(function(c) { return c._uid === alpineData.contact._uid; });
                        if (item) item.active_reminder = response.data.reminder;
                    } else if (alpineData.contact && alpineData.contacts[alpineData.contact._uid]) {
                        alpineData.contacts[alpineData.contact._uid].active_reminder = response.data.reminder;
                        alpineData.contacts = Object.assign({}, alpineData.contacts);
                    }
                }
            }
        }
        if (typeof window.onUpdateContactDetails === 'function') {
            window.onUpdateContactDetails();
        }
    }
}
</script>
@endpush
@endsection()