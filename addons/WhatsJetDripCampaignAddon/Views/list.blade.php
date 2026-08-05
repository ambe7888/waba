@extends('layouts.app', ['title' => __tr('Drip Campaigns')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Drip Campaigns'),
    'description' => __tr('Automate your marketing with scheduled message sequences.'),
])

<div class="container-fluid pt-4 pb-5">
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px; background: #d1fae5; color: #065f46;">
                    <i class="fa fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px; background: #fee2e2; color: #991b1b;">
                    <i class="fa fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 pt-4 pb-3" style="background: #ecfdf5; border-bottom: 2px solid #a7f3d0;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                        <div>
                            <h3 class="mb-1 font-weight-bold" style="color: #065f46; font-size: 1.2rem;">
                                <i class="fas fa-stream mr-2" style="color: #10b981;"></i>{{ __tr('Liste des Campagnes Drip') }}
                            </h3>
                            <p class="text-muted small mb-0" style="color: #047857;">{{ __tr('Gérez et suivez la diffusion de vos séquences de messages automatisées.') }}</p>
                        </div>
                        <div>
                            <button type="button" class="btn text-white font-weight-bold shadow-sm" data-toggle="modal" data-target="#createCampaignModal" 
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; padding: 8px 20px;">
                                <i class="fa fa-plus mr-1"></i> {{ __tr('Créer une campagne de goutte à goutte') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 bg-white">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #f0fdf4; border-bottom: 2px solid #d1fae5;">
                                    <th class="px-4 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Titre de la campagne') }}</th>
                                    <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Mesures') }}</th>
                                    <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Abonnés') }}</th>
                                    <th class="px-3 py-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Statut') }}</th>
                                    <th class="px-4 py-3 text-uppercase text-right" style="font-size: 0.74rem; letter-spacing: 0.05em; color: #065f46; font-weight: 700;">{{ __tr('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $campaign)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 align-middle font-weight-bold" style="color: #0f172a; font-size: 0.95rem;">
                                        <i class="fas fa-paper-plane mr-2" style="color: #10b981;"></i>{{ $campaign->title }}
                                    </td>
                                    <td class="px-3 py-3 align-middle">
                                        <span class="badge font-weight-bold" style="background: #e0f2fe; color: #0284c7; border-radius: 12px; padding: 6px 12px; font-size: 0.82rem;">
                                            <i class="fas fa-list-ol mr-1"></i>{{ $campaign->steps_count }} {{ __tr('étape(s)') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 align-middle">
                                        <span class="badge font-weight-bold mr-1" style="background: {{ $campaign->active_subscribers_count > 0 ? '#ecfdf5' : '#f1f5f9' }}; color: {{ $campaign->active_subscribers_count > 0 ? '#047857' : '#64748b' }}; border-radius: 12px; padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="fas fa-users mr-1"></i>{{ $campaign->active_subscribers_count }} {{ __tr('actif(s)') }}
                                        </span>
                                        @if($campaign->subscribers_count > 0)
                                            <small class="text-muted" style="font-size: 0.78rem;">({{ $campaign->subscribers_count }} {{ __tr('total') }})</small>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 align-middle">
                                        @if($campaign->status == 1)
                                            <span class="badge" style="background: #d1fae5; color: #047857; border-radius: 20px; padding: 6px 14px; font-size: 0.78rem; font-weight: 700;">
                                                <i class="fa fa-check-circle mr-1"></i> {{ __tr('Actif') }}
                                            </span>
                                        @else
                                            <span class="badge" style="background: #fee2e2; color: #dc2626; border-radius: 20px; padding: 6px 14px; font-size: 0.78rem; font-weight: 700;">
                                                <i class="fa fa-pause-circle mr-1"></i> {{ __tr('Désactivé') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle text-right" style="white-space: nowrap;">
                                        <form action="{{ route('addon.WhatsJetDripCampaignAddon.toggle_status', $campaign->_uid) }}" method="POST" class="d-inline">
                                            @csrf
                                            @if($campaign->status == 1)
                                                <button type="submit" class="btn btn-sm btn-outline-warning mr-1" title="{{ __tr('Désactiver la séquence') }}" style="border-radius: 8px; font-weight: 600; padding: 6px 12px;">
                                                    <i class="fa fa-pause mr-1"></i> {{ __tr('Désactiver') }}
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline-success mr-1" title="{{ __tr('Activer la séquence') }}" style="border-radius: 8px; font-weight: 600; padding: 6px 12px;">
                                                    <i class="fa fa-play mr-1"></i> {{ __tr('Activer') }}
                                                </button>
                                            @endif
                                        </form>

                                        <a href="{{ route('addon.WhatsJetDripCampaignAddon.builder', $campaign->_uid) }}" class="btn btn-sm btn-primary mr-1" style="background: #2563eb; border: none; border-radius: 8px; font-size: 0.82rem; padding: 6px 14px; font-weight: 600;">
                                            <i class="fa fa-cogs mr-1"></i> {{ __tr('Constructeur') }}
                                        </a>

                                        <form action="{{ route('addon.WhatsJetDripCampaignAddon.delete_campaign', $campaign->_uid) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __tr('Êtes-vous sûr de vouloir supprimer cette campagne ?') }}');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __tr('Supprimer') }}" style="border-radius: 8px; padding: 6px 12px;">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div style="font-size: 2.5rem; color: #a7f3d0; margin-bottom: 0.8rem;">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <h5 class="font-weight-normal text-muted">{{ __tr('Aucune campagne séquentielle trouvée') }}</h5>
                                        <p class="small text-muted mb-0">{{ __tr('Créez votre première séquence de messages automatiques.') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
