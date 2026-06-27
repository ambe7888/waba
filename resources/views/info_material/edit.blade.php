@extends('layouts.app', ['title' => __tr('Edit Resource')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Edit Resource'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">{{ __tr('Resource Details') }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('info_material.update', ['uid' => $material->_uid]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="title">{{ __tr('Title') }}</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ $material->title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="description">{{ __tr('Description (Optional)') }}</label>
                            <textarea name="description" id="description" rows="10" class="form-control">{!! $material->description !!}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="file">{{ __tr('File (Optional) - Leave blank to keep current file') }}</label>
                            @if(!empty($material->__data['file_name']))
                                <div class="mb-2">
                                    <small>{{ __tr('Current file:') }} <strong>{{ $material->__data['file_name'] }}</strong></small>
                                </div>
                            @endif
                            <input type="file" name="file" id="file" class="form-control">
                            <small class="form-text text-muted">{{ __tr('Max file size: 10MB.') }}</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __tr('Update') }}</button>
                            <a href="{{ route('info_material.index') }}" class="btn btn-secondary">{{ __tr('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
@endpush

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<script>
    $(document).ready(function() {
        $('#description').trumbowyg({
            btns: [
                ['viewHTML'],
                ['undo', 'redo'], // Only supported in Blink browsers
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['insertImage'],
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ]
        });
    });
</script>
@endpush
