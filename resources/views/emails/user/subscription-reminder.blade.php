<?php
    $webSiteName = getAppSettings('name');
?>

<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center">
            <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <!-- Email Body -->
                <tr>
                    <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                        <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0"
                            role="presentation">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell">
                                    <div class="f-fallback">
                                        <?= __tr('Bonjour') ?> {{$fullName}},
                                        <br><br>
                                        @if($context == 'Subscription Reminder')
                                            <?= __tr("Ceci est un rappel vous informant que votre abonnement expire bientôt.") ?>
                                            <br> <br>
                                            <?= __tr("Afin d'éviter toute interruption de service, merci de bien vouloir vérifier et renouveler votre abonnement.") ?>
                                        @elseif($context == 'Subscription Expired')
                                            <?= __tr("Votre abonnement est arrivé à expiration.") ?>
                                            <br> <br>
                                            <?= __tr("Veuillez renouveler votre abonnement pour pouvoir continuer à utiliser nos services.") ?>
                                        @else
                                            <?= __tr("Ceci est une notification concernant votre abonnement.") ?>
                                        @endif
                                        <br> <br>
                                        <?=__tr("Merci pour votre confiance !") ?>
                                        <br> <br>
                                    </div>
                                    <p class="f-fallback sub align-center">
                                        <br><?= __tr('Cordialement,') ?>
                                        <br><?= __tr('L\'équipe') ?> {{$webSiteName}}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
