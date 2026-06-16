@extends('layouts.app', ['title' => __tr('Support Tickets')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Support Tickets'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <!-- Action Button and Filters -->
        <div class="col-xl-12 mb-4">
            <div class="card shadow border-0">
                <div class="card-body bg-gradient-neutral rounded">
                    <form action="{{ route('support_ticket.index') }}" method="GET" class="row align-items-end">
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <label class="form-control-label text-muted" for="search">{{ __tr('Search') }}</label>
                            <div class="input-group input-group-merge">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" name="search" id="search" class="form-control" placeholder="{{ __tr('Subject, description...') }}" value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <label class="form-control-label text-muted" for="status">{{ __tr('Status') }}</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">{{ __tr('All Statuses') }}</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>{{ __tr('Open') }}</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>{{ __tr('In Progress') }}</option>
                                <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>{{ __tr('Resolved') }}</option>
                                <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>{{ __tr('Closed') }}</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <label class="form-control-label text-muted" for="priority">{{ __tr('Priority') }}</label>
                            <select name="priority" id="priority" class="form-control text-capitalize">
                                <option value="">{{ __tr('All Priorities') }}</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>{{ __tr('Low') }}</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>{{ __tr('Normal') }}</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>{{ __tr('High') }}</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <label class="form-control-label text-muted" for="label">{{ __tr('Label') }}</label>
                            <select name="label" id="label" class="form-control">
                                <option value="">{{ __tr('All Labels') }}</option>
                                @foreach($labels as $lbl)
                                    <option value="{{ $lbl->_id }}" {{ request('label') == $lbl->_id ? 'selected' : '' }}>{{ $lbl->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-12 d-flex">
                            <button type="submit" class="btn btn-primary flex-fill mr-2"><i class="fas fa-filter"></i> {{ __tr('Filter') }}</button>
                            <a href="{{ route('support_ticket.index') }}" class="btn btn-secondary flex-fill mr-2"><i class="fas fa-undo"></i> {{ __tr('Reset') }}</a>
                            <a class="btn btn-success flex-fill" href="{{ route('support_ticket.create') }}"><i class="fas fa-plus"></i> {{ __tr('New') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-12">
            <div class="card shadow border-0">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="mb-0">{{ __tr('Tickets List') }}</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ __tr('ID') }}</th>
                                    <th>{{ __tr('Subject') }}</th>
                                    <th>{{ __tr('Status') }}</th>
                                    <th>{{ __tr('Priority') }}</th>
                                    @if(hasCentralAccess())
                                    <th>{{ __tr('Vendor') }}</th>
                                    <th>{{ __tr('Assignee') }}</th>
                                    @endif
                                    <th>{{ __tr('Labels') }}</th>
                                    <th>{{ __tr('Last Updated') }}</th>
                                    <th class="text-right">{{ __tr('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                <tr>
                                    <td>
                                        <span class="text-muted font-weight-bold">#{{ substr($ticket->_uid, 0, 8) }}</span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $ticket->subject }}</div>
                                        <small class="text-muted">{{ Str::limit($ticket->description, 60) }}</small>
                                    </td>
                                    <td>
                                        @if($ticket->status == 1)
                                            <span class="badge badge-dot mr-4"><i class="bg-warning"></i> <span class="status">{{ __tr('Open') }}</span></span>
                                        @elseif($ticket->status == 2)
                                            <span class="badge badge-dot mr-4"><i class="bg-info"></i> <span class="status">{{ __tr('In Progress') }}</span></span>
                                        @elseif($ticket->status == 3)
                                            <span class="badge badge-dot mr-4"><i class="bg-success"></i> <span class="status">{{ __tr('Resolved') }}</span></span>
                                        @else
                                            <span class="badge badge-dot mr-4"><i class="bg-danger"></i> <span class="status">{{ __tr('Closed') }}</span></span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ticket->priority == 'high')
                                            <span class="badge badge-danger text-uppercase">{{ __tr('High') }}</span>
                                        @elseif($ticket->priority == 'normal')
                                            <span class="badge badge-info text-uppercase">{{ __tr('Normal') }}</span>
                                        @else
                                            <span class="badge badge-light text-uppercase">{{ __tr('Low') }}</span>
                                        @endif
                                    </td>
                                    @if(hasCentralAccess())
                                    <td>
                                        <span class="font-weight-bold text-primary">{{ $ticket->vendor->title ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($ticket->assignedUser)
                                            <span class="avatar avatar-sm rounded-circle bg-gradient-info mr-1 text-white text-uppercase font-weight-bold" style="width:24px; height:24px; font-size:10px; line-height:24px; display:inline-block; text-align:center; vertical-align:middle;">
                                                {{ substr($ticket->assignedUser->first_name, 0, 1) }}{{ substr($ticket->assignedUser->last_name, 0, 1) }}
                                            </span>
                                            <span class="align-middle">{{ $ticket->assignedUser->first_name }} {{ $ticket->assignedUser->last_name }}</span>
                                        @else
                                            <span class="text-muted font-italic">{{ __tr('Unassigned') }}</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td>
                                        @forelse($ticket->labels as $label)
                                            <span class="badge badge-pill badge-primary">{{ $label->title }}</span>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                    <td>{{ formatDiffForHumans($ticket->updated_at) }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('support_ticket.show', ['uid' => $ticket->_uid]) }}" class="btn btn-neutral btn-sm"><i class="fas fa-eye text-primary mr-1"></i>{{ __tr('View') }}</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ hasCentralAccess() ? 9 : 7 }}" class="text-center py-4 text-muted">
                                        <i class="fas fa-ticket-alt fa-3x mb-3 text-light"></i>
                                        <p class="mb-0 font-weight-bold">{{ __tr('No tickets found matching the filters.') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    @if($tickets->hasPages())
                    <div class="card-footer py-4 bg-transparent border-0">
                        {{ $tickets->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
