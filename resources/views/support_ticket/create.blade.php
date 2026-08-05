@extends('layouts.app', ['title' => __tr('Créer un Ticket d\'Assistance')])

@section('content')
@include('users.partials.header', [
    'title' => __tr('Créer un Ticket d\'Assistance'),
    'description' => __tr('Décrivez votre problème ou question pour recevoir une aide rapide de notre équipe support.'),
])

<div class="container-fluid pt-4 pb-5">
    <div class="row">
        <div class="col-xl-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header border-0 pt-4 pb-3" style="background: #ecfdf5; border-bottom: 2px solid #a7f3d0;">
                    <h3 class="mb-0 font-weight-bold" style="color: #065f46; font-size: 1.25rem;">
                        <i class="fas fa-headset mr-2" style="color: #10b981;"></i>{{ __tr('Formulaire de Demande d\'Assistance') }}
                    </h3>
                </div>
                <div class="card-body p-4 bg-white">
                    <form action="{{ route('support_ticket.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark" for="subject" style="font-size: 0.92rem;">
                                {{ __tr('Sujet du ticket') }} <small class="text-danger">*</small>
                            </label>
                            <input type="text" name="subject" id="subject" class="form-control" required placeholder="{{ __tr('Entrez un bref sujet décrivant votre problème...') }}" style="border-radius: 8px; height: 46px; font-size: 0.95rem;">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark" for="priority" style="font-size: 0.92rem;">
                                        {{ __tr('Niveau de priorité') }} <small class="text-danger">*</small>
                                    </label>
                                    <select name="priority" id="priority" class="form-control" required style="border-radius: 8px; height: 46px; font-size: 0.95rem;">
                                        <option value="low">{{ __tr('Basse - Demande d\'information générale') }}</option>
                                        <option value="normal" selected>{{ __tr('Normale - Par défaut') }}</option>
                                        <option value="high">{{ __tr('Haute - Problème urgent') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark" for="attachments" style="font-size: 0.92rem;">
                                        {{ __tr('Pièces jointes (Optionnel)') }}
                                    </label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="attachments[]" id="attachments" multiple onchange="document.getElementById('attachments-label').innerHTML = this.files.length + ' {{ __tr('fichier(s) sélectionné(s)') }}'">
                                        <label class="custom-file-label" for="attachments" id="attachments-label" style="border-radius: 8px; height: 46px; line-height: 32px;">{{ __tr('Choisir des fichiers...') }}</label>
                                    </div>
                                    <small class="form-text text-muted mt-1">{{ __tr('Taille maximale : 10 Mo. Formats autorisés : Images, PDF, ZIP.') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark" for="description" style="font-size: 0.92rem;">
                                {{ __tr('Description détaillée') }} <small class="text-danger">*</small>
                            </label>
                            <textarea name="description" id="description" rows="6" class="form-control" required placeholder="{{ __tr('Veuillez décrire votre question ou votre problème en détail...') }}" style="border-radius: 10px; font-size: 0.95rem; line-height: 1.6;"></textarea>
                        </div>
                        
                        <div class="form-group mb-0 text-right pt-2">
                            <a href="{{ route('support_ticket.index') }}" class="btn btn-outline-secondary font-weight-bold mr-3" style="border-radius: 10px; padding: 10px 22px;">
                                {{ __tr('Annuler') }}
                            </a>
                            <button type="submit" class="btn text-white font-weight-bold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; padding: 10px 24px; font-size: 0.95rem;">
                                <i class="fas fa-paper-plane mr-2"></i>{{ __tr('Envoyer la demande d\'assistance') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

