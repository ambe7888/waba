@extends('layouts.app', ['title' => __tr('Pipeline de Vente')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Pipeline de Vente'),
    'description' => '',
])

<div class="container-fluid pt-4 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 text-center p-5">
                <i class="fa fa-columns text-muted mb-3" style="font-size: 3rem;"></i>
                <h3>{{ __tr('Pipeline de Vente (CRM)') }}</h3>
                <p class="text-muted">{{ __tr('Suivez vos opportunités commerciales par étapes, du premier contact à la vente, avec un tableau Kanban complet.') }}</p>
                <div class="alert alert-warning mb-0">
                    {{ __tr('Cette fonctionnalité n\'est pas disponible dans votre offre actuelle. Contactez-nous pour passer à un forfait supérieur.') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
