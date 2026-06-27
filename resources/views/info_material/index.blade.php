@extends('layouts.app', ['title' => __tr('Resource Library')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Resource Library'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        @if(hasCentralAccess())
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-primary" href="{{ route('info_material.create') }}">{{ __tr('Upload New Resource') }}</a>
            </div>
        </div>
        <!--/ button -->
        @endif
        
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __tr('Title') }}</th>
                                    <th>{{ __tr('Description') }}</th>
                                    <th>{{ __tr('Uploaded') }}</th>
                                    <th>{{ __tr('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $material)
                                <tr>
                                    <td>{{ $material->title }}</td>
                                    <td>{{ Str::limit(strip_tags($material->description), 100) }}</td>
                                    <td>{{ formatDateTime($material->created_at) }}</td>
                                    <td>
                                        @if(!empty($material->__data['file_name']))
                                            <a href="{{ route('info_material.download', ['uid' => $material->_uid]) }}" class="btn btn-success btn-sm" target="_blank"><i class="fa fa-download"></i> {{ __tr('Download') }}</a>
                                        @endif
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewModal{{ $material->_uid }}">
                                            <i class="fa fa-eye"></i> {{ __tr('View') }}
                                        </button>
                                        @if(hasCentralAccess())
                                        <a href="{{ route('info_material.edit', ['uid' => $material->_uid]) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> {{ __tr('Edit') }}</a>
                                        
                                        <form action="{{ route('info_material.destroy', ['uid' => $material->_uid]) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __tr('Are you sure you want to delete this material?') }}');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> {{ __tr('Delete') }}</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{ __tr('No resources available at the moment.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $materials->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for viewing descriptions -->
@foreach($materials as $material)
<div class="modal fade" id="viewModal{{ $material->_uid }}" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel{{ $material->_uid }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel{{ $material->_uid }}">{{ $material->title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! $material->description !!}
            </div>
            <div class="modal-footer">
                @if(!empty($material->__data['file_name']))
                    <a href="{{ route('info_material.download', ['uid' => $material->_uid]) }}" class="btn btn-success" target="_blank"><i class="fa fa-download"></i> {{ __tr('Download Attached File') }}</a>
                @endif
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
