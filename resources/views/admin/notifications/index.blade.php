@extends('layouts.app', ['title' => __tr('Notifications Mobiles')])

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h3 mb-2 text-gray-800">
                <i class="fas fa-bell text-primary mr-2"></i> {{ __tr('Notifications Mobiles') }}
            </h2>
            <p class="mb-4">{{ __tr('Envoyez des alertes et des messages directement sur l\'application mobile de vos vendeurs.') }}</p>
        </div>
    </div>

    @if(session('message'))
        <div class="alert alert-{{ session('messageType') }} alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- Formulaire d'envoi -->
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __tr('Nouvelle Notification') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('central.notifications.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="vendors__id">{{ __tr('Destinataire') }}</label>
                            <select name="vendors__id" id="vendors__id" class="form-control">
                                <option value="">{{ __tr('Tous les Vendeurs (Global)') }}</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->_id }}">{{ $vendor->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">{{ __tr('Type de Message') }}</label>
                            <select name="type" id="type" class="form-control">
                                <option value="info">{{ __tr('Information (Bleu)') }}</option>
                                <option value="success">{{ __tr('Succès (Vert)') }}</option>
                                <option value="warning">{{ __tr('Attention (Orange)') }}</option>
                                <option value="danger">{{ __tr('Alerte (Rouge)') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="title">{{ __tr('Titre') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255" placeholder="Ex: Maintenance serveur">
                        </div>

                        <div class="form-group">
                            <label for="message">{{ __tr('Message') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Tapez votre message ici..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane mr-2"></i> {{ __tr('Envoyer la Notification') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Historique des notifications -->
        <div class="col-lg-8 col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __tr('Historique des envois') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __tr('Date') }}</th>
                                    <th>{{ __tr('Type') }}</th>
                                    <th>{{ __tr('Destinataire') }}</th>
                                    <th>{{ __tr('Titre') }}</th>
                                    <th>{{ __tr('Message') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notif)
                                    <tr>
                                        <td>{{ $notif->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($notif->type == 'info')
                                                <span class="badge badge-info">Info</span>
                                            @elseif($notif->type == 'success')
                                                <span class="badge badge-success">Succès</span>
                                            @elseif($notif->type == 'warning')
                                                <span class="badge badge-warning">Attention</span>
                                            @else
                                                <span class="badge badge-danger">Alerte</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notif->vendors__id)
                                                <span class="badge badge-secondary">{{ $notif->vendor->title ?? ('Vendeur #' . $notif->vendors__id) }}</span>
                                            @else
                                                <span class="badge badge-primary">Global</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $notif->title }}</strong></td>
                                        <td>{{ Str::limit($notif->message, 50) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __tr('Aucune notification envoyée.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($notifications->hasPages())
                        <div class="mt-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
