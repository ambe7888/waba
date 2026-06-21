@extends('layouts.app', ['title' => __tr('Drip Campaigns')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Drip Campaigns'),
    'description' => __tr('Automate your marketing with scheduled message sequences.'),
])

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-3 text-right">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createCampaignModal">
                <i class="fa fa-plus"></i> {{ __tr('Create Drip Campaign') }}
            </button>
        </div>
        
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __tr('Title') }}</th>
                                <th>{{ __tr('Steps') }}</th>
                                <th>{{ __tr('Subscribers') }}</th>
                                <th>{{ __tr('Status') }}</th>
                                <th class="text-right">{{ __tr('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $campaign)
                            <tr>
                                <td><strong>{{ $campaign->title }}</strong></td>
                                <td><span class="badge badge-info">{{ $campaign->steps_count }}</span></td>
                                <td><span class="badge badge-success">{{ $campaign->subscribers_count }}</span></td>
                                <td>
                                    @if($campaign->status == 1)
                                        <span class="badge badge-primary">{{ __tr('Active') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __tr('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('addon.WhatsJetDripCampaignAddon.builder', $campaign->_uid) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-cogs"></i> {{ __tr('Builder') }}
                                    </a>
                                    <form action="{{ route('addon.WhatsJetDripCampaignAddon.delete_campaign', $campaign->_uid) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __tr('Are you sure you want to delete this campaign?') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __tr('No Drip Campaigns found. Create your first sequence!') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Campaign Modal -->
<div class="modal fade" id="createCampaignModal" tabindex="-1" role="dialog" aria-labelledby="createCampaignModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('addon.WhatsJetDripCampaignAddon.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCampaignModalLabel">{{ __tr('New Drip Campaign') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __tr('Campaign Title') }}</label>
                        <input type="text" name="title" class="form-control" required placeholder="{{ __tr('E.g. Welcome Sequence') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __tr('Create & Continue') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
