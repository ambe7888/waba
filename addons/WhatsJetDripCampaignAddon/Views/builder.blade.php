@extends('layouts.app', ['title' => __tr('Drip Builder: :title', ['title' => $campaign->title])])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Drip Builder: ') . $campaign->title,
    'description' => __tr('Manage the steps and delays for this campaign sequence.'),
])

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="alert alert-info shadow-sm">
                <h5><i class="fa fa-info-circle"></i> {{ __tr('How Drip Campaigns Work') }}</h5>
                <p class="mb-2">{{ __tr("A Drip Campaign automatically sends a sequence of messages to a contact over time. They are subscribed to the campaign when they trigger a specific Bot Reply.") }}</p>
                <p class="mb-0"><strong>{{ __tr("Important Meta Rule (24h Window):") }}</strong> {{ __tr("You can send Custom Text Messages ONLY if the delay is within 24 hours of the contact's last message. For delays over 24 hours, you MUST use approved WhatsApp Templates.") }}</p>
            </div>
        </div>
        <div class="col-12 mb-3">
            <a href="{{ route('addon.WhatsJetDripCampaignAddon.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __tr('Back to Campaigns') }}
            </a>
            <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addStepModal">
                <i class="fa fa-plus"></i> {{ __tr('Add Step') }}
            </button>
        </div>

        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="timeline mt-5">
                @forelse($steps as $step)
                <div class="card shadow-sm mb-4 border-left-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-primary font-weight-bold mb-1">
                                    <i class="fas fa-clock"></i> 
                                    @if($step->delay_value == 0)
                                        {{ __tr('Immediately') }}
                                    @else
                                        {{ __tr('After :value :type', ['value' => $step->delay_value, 'type' => __tr($step->delay_type)]) }}
                                    @endif
                                </h5>
                                <p class="text-muted mb-0">
                                    @if($step->template)
                                        <i class="fa fa-whatsapp text-success"></i> {{ __tr('Template:') }} <strong>{{ $step->template->template_name }}</strong>
                                    @elseif($step->custom_message)
                                        <i class="fa fa-comment text-info"></i> {{ __tr('Custom Message') }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <form action="{{ route('addon.WhatsJetDripCampaignAddon.delete_step', $step->_uid) }}" method="POST" onsubmit="return confirm('{{ __tr('Delete this step?') }}');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa fa-trash"></i> {{ __tr('Delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-info text-center">
                    {{ __tr('No steps have been added yet. Click "Add Step" to begin building your sequence.') }}
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Step Modal -->
<div class="modal fade" id="addStepModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('addon.WhatsJetDripCampaignAddon.store_step', $campaign->_uid) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __tr('Add Campaign Step') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __tr('Wait') }} <small class="text-danger">*</small></label>
                                <input type="number" min="0" name="delay_value" class="form-control" required value="1">
                                <small class="text-muted">{{ __tr('0 = Send immediately.') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __tr('Time Unit') }} <small class="text-danger">*</small></label>
                                <select name="delay_type" class="form-control" required>
                                    <option value="minutes">{{ __tr('Minutes') }}</option>
                                    <option value="hours">{{ __tr('Hours') }}</option>
                                    <option value="days" selected>{{ __tr('Days') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>{{ __tr('Send WhatsApp Template') }}</label>
                        <select name="whatsapp_templates__id" class="form-control">
                            <option value="">{{ __tr('-- Select Template --') }}</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->_id }}">{{ $template->template_name }} ({{ $template->language }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __tr('Templates must be approved by Meta.') }}</small>
                    </div>
                    
                    <div class="form-group text-center">
                        <span class="text-muted">{{ __tr('OR') }}</span>
                    </div>

                    <div class="form-group">
                        <label>{{ __tr('Custom Text Message') }}</label>
                        <textarea id="lwDripCustomMessage" name="custom_message" class="form-control" rows="3" placeholder="{{ __tr('Only allowed within 24h of last interaction.') }}"></textarea>
                        <x-whatsapp-format-buttons inputId="lwDripCustomMessage" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __tr('Add Step') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
