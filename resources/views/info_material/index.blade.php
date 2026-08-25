@extends('layouts.app', ['title' => __tr('Resource Library')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Resource Library'),
    'description' => '',
    'class' => 'col-lg-7'
])

<div class="container-fluid pt-4 pb-5">
    <div class="row">
        @if(hasCentralAccess())
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-success" style="background: #10b981; border: none; border-radius: 8px; font-weight: 600;" href="{{ route('info_material.create') }}">
                    <i class="fa fa-plus mr-1"></i> {{ __tr('Upload New Resource') }}
                </a>
            </div>
        </div>
        <!--/ button -->
        @endif
        
        <div class="col-xl-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 pt-4 pb-3" style="background: #ecfdf5; border-bottom: 2px solid #a7f3d0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 font-weight-bold" style="color: #065f46; font-size: 1.2rem;">
                                <i class="fas fa-book mr-2" style="color: #10b981;"></i>{{ __tr('Resource Library') }}
                            </h3>
                            <p class="text-muted small mb-0" style="color: #047857;">{{ __tr('Consultez et téléchargez l\'ensemble des guides, tutoriels et documentations disponibles.') }}</p>
                        </div>
                        <span class="badge" style="background: #10b981; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                            {{ $materials->total() }} {{ __tr('Ressource(s)') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0 bg-white">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #f0fdf4; border-bottom: 2px solid #d1fae5;">
                                    <th class="px-4 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Title') }}</th>
                                    <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Description') }}</th>
                                    <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Uploaded') }}</th>
                                    <th class="px-4 py-3 text-uppercase text-right" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $material)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 align-middle font-weight-bold" style="color: #0f172a; min-width: 200px;">
                                        <i class="far fa-file-alt mr-2" style="color: #10b981;"></i>{{ $material->title }}
                                    </td>
                                    <td class="px-3 py-3 align-middle text-muted" style="max-width: 380px;">
                                        {{ Str::limit(strip_tags($material->description), 110) }}
                                    </td>
                                    <td class="px-3 py-3 align-middle text-muted" style="white-space: nowrap;">
                                        {{ formatDateTime($material->created_at) }}
                                    </td>
                                    <td class="px-4 py-3 align-middle text-right" style="white-space: nowrap;">
                                        @if(!empty($material->__data['file_name']))
                                            <a href="{{ route('info_material.download', ['uid' => $material->_uid]) }}" class="btn btn-sm btn-success mr-1" style="border-radius: 8px; background: #10b981; border: none; font-size: 0.8rem; font-weight: 600;" target="_blank">
                                                <i class="fa fa-download mr-1"></i> {{ __tr('Download') }}
                                            </a>
                                        @endif
                                        @if(!empty($material->__data['video_url']))
                                            <a href="{{ $material->__data['video_url'] }}" class="btn btn-sm btn-danger mr-1" style="border-radius: 8px; background: #dc2626; border: none; font-size: 0.8rem; font-weight: 600;" target="_blank">
                                                <i class="fa fa-play mr-1"></i> {{ __tr('Video') }}
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewModal{{ $material->_uid }}" style="border-radius: 8px; background: #2563eb; border: none; font-size: 0.8rem; font-weight: 600;">
                                            <i class="fa fa-eye mr-1"></i> {{ __tr('Voir') }}
                                        </button>
                                        @if(hasCentralAccess())
                                        <a href="{{ route('info_material.edit', ['uid' => $material->_uid]) }}" class="btn btn-warning btn-sm" style="border-radius: 8px;"><i class="fa fa-edit"></i></a>
                                        
                                        <form action="{{ route('info_material.destroy', ['uid' => $material->_uid]) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __tr('Are you sure you want to delete this material?') }}');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 8px;"><i class="fa fa-trash"></i></button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <div style="font-size: 2.5rem; color: #a7f3d0; margin-bottom: 0.8rem;">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <h5>{{ __tr('Aucune ressource disponible pour le moment.') }}</h5>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    @if($materials->hasPages())
                    <div class="px-4 py-3 border-top">
                        {{ $materials->links() }}
                    </div>
                    @endif
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
                @if(!empty($material->__data['video_url']))
                    <a href="{{ $material->__data['video_url'] }}" class="btn btn-danger" target="_blank"><i class="fa fa-play"></i> {{ __tr('Watch Video') }}</a>
                @endif
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
