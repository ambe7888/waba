<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion WhatsApp</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 24px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 36px;
        }
        h1 { font-size: 20px; color: #0f172a; margin: 0 0 8px; }
        p { font-size: 14px; color: #64748b; margin: 0 0 28px; max-width: 320px; line-height: 1.5; }
        button {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px 28px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            max-width: 320px;
        }
        button:disabled { opacity: 0.6; }
        .error {
            margin-top: 16px;
            color: #dc2626;
            font-size: 13px;
            max-width: 320px;
        }
        .spinner {
            display: none;
            margin-top: 20px;
            font-size: 13px;
            color: #10b981;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="icon">📱</div>
    <h1>Connecter votre WhatsApp Business</h1>
    <p>Connectez votre compte WhatsApp Business en toute sécurité via Meta. Aucune clé technique à saisir.</p>

    @if(getAppSettings('enable_embedded_signup'))
        <button id="launchBtn" onclick="launchWhatsAppSignup()">Connecter avec Facebook</button>
        <div class="spinner" id="spinner">Connexion en cours, veuillez patienter...</div>
        <div class="error" id="errorBox"></div>

        <script>
            window.fbAsyncInit = function() {
                FB.init({
                    appId: '{{ getAppSettings('embedded_signup_app_id') }}',
                    autoLogAppEvents: true,
                    xfbml: true,
                    version: 'v25.0'
                });
            };
        </script>
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
        <script>
            (function() {
                'use strict';

                function showError(msg) {
                    document.getElementById('errorBox').textContent = msg;
                    document.getElementById('spinner').style.display = 'none';
                    document.getElementById('launchBtn').disabled = false;
                }

                window.launchWhatsAppSignup = function() {
                    document.getElementById('errorBox').textContent = '';
                    document.getElementById('launchBtn').disabled = true;
                    document.getElementById('spinner').style.display = 'block';

                    var tempAccessCode = '', phoneNumberId = '', waBaId = '', isAppOnboarding = false;

                    FB.login(function (response) {
                        if (response.authResponse) {
                            tempAccessCode = response.authResponse.code;
                            if (tempAccessCode) {
                                fetch('{{ route('vendor.whatsapp_setup.embedded_signup.mobile.complete', ['token' => $token]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        request_code: tempAccessCode,
                                        waba_id: waBaId,
                                        phone_number_id: phoneNumberId,
                                        is_app_onboarding: isAppOnboarding
                                    })
                                }).then(function(r) { return r.json(); }).then(function(data) {
                                    if (data.reaction_code === 1 || data.reaction_code === 21) {
                                        window.location.href = '{{ route('vendor.whatsapp_setup.embedded_signup.mobile.done', ['token' => $token]) }}';
                                    } else {
                                        showError((data.data && data.data.message) || data.message || 'Une erreur est survenue.');
                                    }
                                }).catch(function() {
                                    showError('Erreur réseau.');
                                });
                            } else {
                                showError('Échec de la connexion Facebook.');
                            }
                        } else {
                            showError('Connexion annulée.');
                        }
                    }, {
                        config_id: '{{ getAppSettings('embedded_signup_config_id') }}',
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: {
                            setup: {},
                            featureType: '{{ getAppSettings('enable_business_app_onboarding') ? 'whatsapp_business_app_onboarding' : '' }}',
                            sessionInfoVersion: '3',
                            "features": [
                                { "name": "marketing_messages_lite" }
                            ],
                            "version": "v3"
                        }
                    });

                    var sessionInfoListener = function(event) {
                        if (event.origin !== "https://www.facebook.com") return;
                        try {
                            var data = JSON.parse(event.data);
                            if (data.type === 'WA_EMBEDDED_SIGNUP') {
                                if ((data.event === 'FINISH') || (data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING')) {
                                    phoneNumberId = data.data.phone_number_id;
                                    waBaId = data.data.waba_id;
                                    @if(getAppSettings('enable_business_app_onboarding'))
                                    if (data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                                        isAppOnboarding = 'YES';
                                    }
                                    @endif
                                }
                            }
                        } catch (e) {
                            // Non-JSON message, ignore
                        }
                    };
                    window.addEventListener('message', sessionInfoListener);
                };
            })();
        </script>
    @else
        <div class="error">La connexion WhatsApp n'est pas disponible pour le moment. Contactez le support.</div>
    @endif
</body>
</html>
