@extends('layouts.app', ['title' => __tr('Create Support Ticket')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Create Support Ticket'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow border-0">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="mb-0">{{ __tr('Submit a New Ticket') }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('support_ticket.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label class="form-control-label text-muted" for="subject">{{ __tr('Subject') }}</label>
                            <input type="text" name="subject" id="subject" class="form-control" required placeholder="{{ __tr('Enter a brief subject for your issue') }}">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label text-muted" for="priority">{{ __tr('Priority') }}</label>
                                    <select name="priority" id="priority" class="form-control" required>
                                        <option value="low">{{ __tr('Low - General inquiry') }}</option>
                                        <option value="normal" selected>{{ __tr('Normal - Default') }}</option>
                                        <option value="high">{{ __tr('High - Urgent issue') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label text-muted" for="attachments">{{ __tr('Attachments (Optional)') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="attachments[]" id="attachments" multiple onchange="document.getElementById('attachments-label').innerHTML = this.files.length + ' {{ __tr('file(s) selected') }}'">
                                        <label class="custom-file-label" for="attachments" id="attachments-label">{{ __tr('Choose files') }}</label>
                                    </div>
                                    <small class="form-text text-muted">{{ __tr('Max size: 10MB. Allowed types: Images, PDFs, Zip.') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label text-muted" for="description">{{ __tr('Detailed Description') }}</label>
                            <textarea name="description" id="description" rows="6" class="form-control" required placeholder="{{ __tr('Please describe your question or issue in detail...') }}"></textarea>
                        </div>
                        
                        <div class="form-group mb-0 text-right">
                            <a href="{{ route('support_ticket.index') }}" class="btn btn-link text-muted mr-3">{{ __tr('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-2"></i>{{ __tr('Submit Ticket') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
