@extends('layouts.app', ['title' => __tr('View Support Ticket')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Support Ticket').' #'.substr($ticket->_uid, 0, 8),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-8">
            <div class="card shadow border-0">
                <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 text-dark font-weight-bold">{{ $ticket->subject }}</h3>
                    <div>
                        @if($ticket->status == 1)
                            <span class="badge badge-warning text-uppercase">{{ __tr('Open') }}</span>
                        @elseif($ticket->status == 2)
                            <span class="badge badge-info text-uppercase">{{ __tr('In Progress') }}</span>
                        @elseif($ticket->status == 3)
                            <span class="badge badge-success text-uppercase">{{ __tr('Resolved') }}</span>
                        @else
                            <span class="badge badge-danger text-uppercase">{{ __tr('Closed') }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <!-- Original message -->
                        <div class="p-3 bg-white border border-light text-dark rounded mb-4 shadow-sm" style="border-left: 5px solid #5e72e4 !important;">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 border-light">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm rounded-circle bg-primary text-white text-uppercase font-weight-bold mr-2">
                                        {{ substr($ticket->vendor->title ?? 'V', 0, 1) }}
                                    </span>
                                    <div>
                                        <h5 class="mb-0 text-dark">{{ $ticket->vendor->title ?? 'Vendor' }}</h5>
                                        <small class="text-muted">{{ __tr('Created by') }} {{ $ticket->vendorUser && $ticket->vendorUser->user ? ($ticket->vendorUser->user->first_name . ' ' . $ticket->vendorUser->user->last_name) : 'User' }}</small>
                                    </div>
                                </div>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ formatDateTime($ticket->created_at) }}</small>
                            </div>
                            <div class="mt-3 text-dark lw-ws-pre-line text-sm leading-relaxed">
                                {{ $ticket->description }}
                            </div>

                            @if(!empty($ticket->__data['attachment']))
                            <div class="mt-4 pt-3 border-top border-light">
                                <span class="text-muted text-xs d-block mb-1">{{ __tr('Attached File:') }}</span>
                                <div class="d-inline-flex align-items-center p-2 bg-secondary text-dark rounded border border-light">
                                    <i class="fas fa-paperclip text-primary mr-2"></i>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                        {{ $ticket->__data['attachment']['file_name'] }}
                                    </a>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}" class="btn btn-sm btn-primary py-1 px-2" title="{{ __tr('Download File') }}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Replies Header -->
                        <h4 class="mb-3 text-muted"><i class="far fa-comments mr-2"></i>{{ __tr('Conversation History') }}</h4>

                        <!-- Replies -->
                        @forelse($ticket->replies as $reply)
                            @php
                                $isAdminReply = $reply->user && $reply->user->user_roles__id == 1;
                            @endphp
                            <div class="p-3 mb-3 rounded shadow-sm border text-dark {{ $isAdminReply ? 'bg-white ml-lg-5' : 'bg-light mr-lg-5' }}" style="border-left: 5px solid {{ $isAdminReply ? '#11cdef' : '#adb5bd' }} !important;">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 border-light">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm rounded-circle {{ $isAdminReply ? 'bg-info text-white' : 'bg-primary text-white' }} text-uppercase font-weight-bold mr-2">
                                            @if($isAdminReply)
                                                <i class="fas fa-user-shield"></i>
                                            @else
                                                {{ substr($ticket->vendor->title ?? 'V', 0, 1) }}
                                            @endif
                                        </span>
                                        <div>
                                            <h5 class="mb-0 text-dark font-weight-bold">
                                                {{ $reply->user ? ($reply->user->first_name . ' ' . $reply->user->last_name) : 'User' }}
                                            </h5>
                                            <small class="text-muted">
                                                @if($isAdminReply)
                                                    {{ __tr('Support Administrator') }}
                                                @else
                                                    {{ __tr('Vendor Team Member') }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ formatDateTime($reply->created_at) }}</small>
                                </div>
                                <div class="mt-2 lw-ws-pre-line text-sm leading-relaxed">
                                    {{ $reply->message }}
                                </div>

                                @if(!empty($reply->__data['attachment']))
                                <div class="mt-3 pt-2 border-top border-light">
                                    <span class="d-block mb-1 text-xs text-muted">{{ __tr('Attached File:') }}</span>
                                    <div class="d-inline-flex align-items-center p-2 bg-secondary text-dark rounded border border-light">
                                        <i class="fas fa-paperclip text-primary mr-2"></i>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                            {{ $reply->__data['attachment']['file_name'] }}
                                        </a>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}" class="btn btn-sm btn-primary py-1 px-2">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 bg-light rounded text-muted mb-4">
                                <i class="far fa-comment-dots fa-2x mb-2"></i>
                                <p class="mb-0">{{ __tr('No replies yet. Use the form below to reply.') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if($ticket->status != 4)
                    <hr class="my-4">
                    <form action="{{ route('support_ticket.reply', ['uid' => $ticket->_uid]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted" for="message">{{ __tr('Post Reply') }}</label>
                            <textarea name="message" id="message" rows="4" class="form-control" required placeholder="{{ __tr('Type your reply here...') }}"></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-control-label text-muted" for="attachment">{{ __tr('Attach File (Optional)') }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="attachment" id="attachment" onchange="document.getElementById('attachment-label').innerHTML = this.files[0].name">
                                <label class="custom-file-label" for="attachment" id="attachment-label">{{ __tr('Choose file') }}</label>
                            </div>
                            <small class="form-text text-muted">{{ __tr('Max: 10MB. Images, PDFs, Zip.') }}</small>
                        </div>

                        <div class="form-group text-right mb-0">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-reply mr-2"></i>{{ __tr('Send Reply') }}</button>
                        </div>
                    </form>
                    @else
                    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle fa-lg mr-3"></i>
                        <div>{{ __tr('This ticket is closed. You can no longer reply to it.') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow border-0">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="mb-0 text-dark font-weight-bold">{{ __tr('Ticket Meta') }}</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                            <span class="text-muted text-sm">{{ __tr('Priority') }}</span>
                            <span>
                                @if($ticket->priority == 'high')
                                    <span class="badge badge-danger text-uppercase">{{ __tr('High') }}</span>
                                @elseif($ticket->priority == 'normal')
                                    <span class="badge badge-info text-uppercase">{{ __tr('Normal') }}</span>
                                @else
                                    <span class="badge badge-light text-uppercase">{{ __tr('Low') }}</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                            <span class="text-muted text-sm">{{ __tr('Assignee') }}</span>
                            <span>
                                @if($ticket->assignedUser)
                                    <span class="font-weight-bold">{{ $ticket->assignedUser->first_name }} {{ $ticket->assignedUser->last_name }}</span>
                                @else
                                    <span class="text-muted font-italic">{{ __tr('Unassigned') }}</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                            <span class="text-muted text-sm">{{ __tr('Created At') }}</span>
                            <span class="text-dark font-weight-bold text-sm">{{ formatDateTime($ticket->created_at) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                            <span class="text-muted text-sm">{{ __tr('Last Activity') }}</span>
                            <span class="text-dark font-weight-bold text-sm">{{ formatDiffForHumans($ticket->updated_at) }}</span>
                        </li>
                    </ul>

                    @if($ticket->labels->count() > 0)
                        <div class="mb-4">
                            <span class="text-muted text-sm d-block mb-2">{{ __tr('Ticket Labels') }}</span>
                            @foreach($ticket->labels as $label)
                                <span class="badge badge-pill badge-primary mb-1 mr-1">{{ $label->title }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(hasCentralAccess())
                    <hr class="my-3">
                    
                    <!-- Assignee Form -->
                    <form action="{{ route('support_ticket.assign', ['uid' => $ticket->_uid]) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted text-sm" for="assigned_users__id">{{ __tr('Assign Ticket') }}</label>
                            <select name="assigned_users__id" id="assigned_users__id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">{{ __tr('-- Select Admin --') }}</option>
                                @foreach($admins as $admin)
                                    <option value="{{ $admin->_id }}" {{ $ticket->assigned_users__id == $admin->_id ? 'selected' : '' }}>
                                        {{ $admin->first_name }} {{ $admin->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    <!-- Priority Form -->
                    <form action="{{ route('support_ticket.update_priority', ['uid' => $ticket->_uid]) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted text-sm" for="priority_select">{{ __tr('Change Priority') }}</label>
                            <select name="priority" id="priority_select" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="low" {{ $ticket->priority == 'low' ? 'selected' : '' }}>{{ __tr('Low') }}</option>
                                <option value="normal" {{ $ticket->priority == 'normal' ? 'selected' : '' }}>{{ __tr('Normal') }}</option>
                                <option value="high" {{ $ticket->priority == 'high' ? 'selected' : '' }}>{{ __tr('High') }}</option>
                            </select>
                        </div>
                    </form>

                    <!-- Status Form -->
                    <form action="{{ route('support_ticket.update_status', ['uid' => $ticket->_uid]) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted text-sm" for="status_select">{{ __tr('Update Status') }}</label>
                            <select name="status" id="status_select" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="1" {{ $ticket->status == 1 ? 'selected' : '' }}>{{ __tr('Open') }}</option>
                                <option value="2" {{ $ticket->status == 2 ? 'selected' : '' }}>{{ __tr('In Progress') }}</option>
                                <option value="3" {{ $ticket->status == 3 ? 'selected' : '' }}>{{ __tr('Resolved') }}</option>
                                <option value="4" {{ $ticket->status == 4 ? 'selected' : '' }}>{{ __tr('Closed') }}</option>
                            </select>
                        </div>
                    </form>

                    <!-- Labels Form -->
                    <form action="{{ route('support_ticket.update_labels', ['uid' => $ticket->_uid]) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted text-sm" for="labels">{{ __tr('Manage Labels') }}</label>
                            <input type="text" name="labels[]" id="labels" class="form-control form-control-sm" value="{{ $ticket->labels->pluck('title')->implode(', ') }}" placeholder="{{ __tr('Urgent, Billing, Technical') }}">
                            <small class="form-text text-muted">{{ __tr('Comma-separated label names.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary btn-block">{{ __tr('Update Labels') }}</button>
                    </form>
                    
                    @else
                    
                    <!-- Vendor options (can close/resolve ticket) -->
                    @if($ticket->status != 4 && $ticket->status != 3)
                    <hr class="my-3">
                    <form action="{{ route('support_ticket.update_status', ['uid' => $ticket->_uid]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="3">
                        <button type="submit" class="btn btn-outline-success btn-sm btn-block"><i class="fas fa-check-double mr-1"></i>{{ __tr('Mark as Resolved') }}</button>
                    </form>
                    @endif
                    
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
