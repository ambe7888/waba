@php
$vendorId = getVendorId();
$threeCxPlanDetails = vendorPlanDetails('three_cx_calling', 1, $vendorId);
@endphp
<div class="row">
    <div class="col-md-8" x-cloak>
        <!-- Page Heading -->
        <h1>
            <?= __tr('Appels Téléphoniques via 3CX') ?>
        </h1>
        @if ($threeCxPlanDetails['is_limit_available'])
        <fieldset>
            <legend>{{ __tr('Connexion à votre PBX 3CX') }}</legend>
            <p>{{ __tr('Ce module vous permet d\'appeler un contact par téléphone (via votre propre standard 3CX) sans quitter la discussion WhatsApp. Toute la configuration de votre PBX (postes, trunk SIP, etc.) se fait directement dans votre compte 3CX -- WhatsClick a seulement besoin de l\'adresse de votre Web Client 3CX pour ouvrir l\'appel.') }}</p>
            <form class="lw-ajax-form lw-form" data-show-processing="true" method="post" action="<?= route('vendor.settings.write.update', ['pageType' => 'three_cx_setup']) ?>">
                <div class="form-group">
                    <x-lw.input-field type="text" id="lwThreeCxWebclientUrl" data-form-group-class=""
                        value="{{ getVendorSettings('three_cx_webclient_url') }}"
                        :label="__tr('URL de votre Web Client 3CX')"
                        placeholder="https://mycompany.3cx.com:5001"
                        name="three_cx_webclient_url" />
                    <small class="form-text text-muted">{{ __tr('Exemple : https://mycompany.3cx.com:5001 -- c\'est l\'adresse que vous utilisez déjà pour vous connecter au Web Client de votre standard 3CX.') }}</small>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary btn-user lw-btn-block-mobile">
                        {{ __tr('Save') }}
                    </button>
                </div>
            </form>
        </fieldset>
        @else
        <div class="alert alert-danger">
            {{ __tr('Le module d\'appel 3CX n\'est pas disponible dans votre offre actuelle, veuillez mettre à niveau votre abonnement.') }}
        </div>
        @endif
    </div>
</div>
