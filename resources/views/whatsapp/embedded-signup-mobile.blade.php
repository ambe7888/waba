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
            border-radius: 50%;
            background: #1877f2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        h1 { font-size: 20px; color: #0f172a; margin: 0 0 8px; }
        p { font-size: 14px; color: #64748b; margin: 0 0 28px; max-width: 320px; line-height: 1.5; }
        button {
            background: #1877f2;
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
        .spinner.on { display: flex; align-items: center; justify-content: center; gap: 10px; }
        .ring {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(16, 185, 129, 0.25);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) {
            .ring { animation-duration: 2.4s; }
        }
        .steps {
            display: none;
            margin-top: 18px;
            max-width: 320px;
            width: 100%;
            text-align: left;
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
        }
        .steps.on { display: block; }
        .steps li { list-style: none; }
        .steps li::before { content: '○ '; color: #cbd5e1; }
        .steps li.done { color: #0f172a; }
        .steps li.done::before { content: '✓ '; color: #10b981; font-weight: 700; }
        .steps ul { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div class="icon">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
    </div>
    <h1>Activer votre compte WhatsApp API</h1>
    <p>Activez votre compte WhatsApp API en toute sécurité via Meta. Aucune clé technique à saisir.</p>

    @if(getAppSettings('enable_embedded_signup'))
        <button id="launchBtn" onclick="launchWhatsAppSignup()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff" style="vertical-align:-4px;margin-right:8px;" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>Connecter avec Facebook
        </button>
        <div class="spinner" id="spinner"><span class="ring"></span><span id="spinnerText">Connexion en cours, veuillez patienter…</span></div>
        <div class="steps" id="steps">
            <ul>
                <li id="step1">Autorisation Facebook</li>
                <li id="step2">Récupération de votre compte WhatsApp</li>
                <li id="step3">Synchronisation avec WhatsClick</li>
            </ul>
        </div>
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

                // Meta delivers the two halves of this flow independently:
                // FB.login's callback carries the auth code, while the WABA
                // and phone-number ids arrive separately as a postMessage
                // from the popup. Nothing guarantees their order.
                //
                // Posting from inside the FB.login callback (as the web
                // dashboard does) therefore only works when the popup's
                // message happens to land first - which it does in a desktop
                // browser, and does not reliably in the app's webview. When
                // it lost that race the ids went up empty, the backend's
                // required|numeric validation rejected them, and the page
                // showed a bare "Une erreur est survenue" even though the
                // account had been created on Meta's side.
                //
                // So: collect both halves, and only submit once we have them.
                var tempAccessCode = '', phoneNumberId = '', waBaId = '',
                    isAppOnboarding = false, submitted = false, fallbackTimer = null;

                var el = function(id) { return document.getElementById(id); };

                function markStep(id) {
                    var node = el(id);
                    if (node) node.className = 'done';
                }

                function setStatus(text) {
                    el('spinnerText').textContent = text;
                }

                function showError(msg) {
                    if (fallbackTimer) { clearTimeout(fallbackTimer); fallbackTimer = null; }
                    el('errorBox').textContent = msg;
                    el('spinner').className = 'spinner';
                    el('steps').className = 'steps';
                    el('launchBtn').disabled = false;
                }

                // Turns a Laravel validation payload into something a vendor
                // can act on, instead of the generic fallback.
                function describeFailure(data) {
                    if (data && data.errors) {
                        for (var key in data.errors) {
                            if (Object.prototype.hasOwnProperty.call(data.errors, key)) {
                                var list = data.errors[key];
                                if (list && list.length) return list[0];
                            }
                        }
                    }
                    return (data && data.data && data.data.message)
                        || (data && data.message)
                        || 'La connexion a échoué. Réessayez, ou contactez le support si cela persiste.';
                }

                function submitIfReady(force) {
                    if (submitted || !tempAccessCode) return;
                    // Without app-onboarding, the backend requires waba_id and
                    // phone_number_id. Hold until the popup has reported them,
                    // unless the fallback timer has given up waiting.
                    if (!force && !isAppOnboarding && (!waBaId || !phoneNumberId)) return;

                    submitted = true;
                    if (fallbackTimer) { clearTimeout(fallbackTimer); fallbackTimer = null; }

                    markStep('step2');
                    setStatus('Synchronisation avec WhatsClick…');

                    fetch('{{ route('vendor.whatsapp_setup.embedded_signup.mobile.complete', ['token' => $token]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            request_code: tempAccessCode,
                            waba_id: waBaId,
                            phone_number_id: phoneNumberId,
                            is_app_onboarding: isAppOnboarding
                        })
                    }).then(function(r) {
                        return r.json().catch(function() { return {}; });
                    }).then(function(data) {
                        if (data.reaction_code === 1 || data.reaction_code === 21) {
                            markStep('step3');
                            setStatus('Compte connecté, finalisation…');
                            window.location.href = '{{ route('vendor.whatsapp_setup.embedded_signup.mobile.done', ['token' => $token]) }}';
                        } else {
                            submitted = false;
                            showError(describeFailure(data));
                        }
                    }).catch(function() {
                        submitted = false;
                        showError('Connexion au serveur impossible. Vérifiez votre réseau et réessayez.');
                    });
                }

                // Registered before FB.login, so a popup that reports back
                // quickly cannot arrive before anything is listening.
                window.addEventListener('message', function(event) {
                    if (event.origin !== 'https://www.facebook.com') return;
                    try {
                        var data = JSON.parse(event.data);
                        if (data.type !== 'WA_EMBEDDED_SIGNUP') return;
                        if (data.event === 'FINISH' || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                            phoneNumberId = data.data.phone_number_id || '';
                            waBaId = data.data.waba_id || '';
                            @if(getAppSettings('enable_business_app_onboarding'))
                            if (data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                                isAppOnboarding = 'YES';
                            }
                            @endif
                            markStep('step2');
                            submitIfReady(false);
                        }
                    } catch (e) {
                        // Not our JSON - ignore.
                    }
                });

                window.launchWhatsAppSignup = function() {
                    el('errorBox').textContent = '';
                    el('launchBtn').disabled = true;
                    el('spinner').className = 'spinner on';
                    el('steps').className = 'steps on';
                    setStatus('Connexion à Facebook…');

                    FB.login(function (response) {
                        if (response.authResponse) {
                            tempAccessCode = response.authResponse.code;
                            if (tempAccessCode) {
                                markStep('step1');
                                setStatus('Récupération de votre compte WhatsApp…');
                                submitIfReady(false);
                                // If the popup's message never arrives, try
                                // anyway rather than hanging: the backend may
                                // still resolve the account, and if it cannot
                                // its validation message is more useful than
                                // a spinner that never stops.
                                if (!submitted && !fallbackTimer) {
                                    fallbackTimer = setTimeout(function() {
                                        fallbackTimer = null;
                                        submitIfReady(true);
                                    }, 12000);
                                }
                            } else {
                                showError('Facebook n\'a pas renvoyé de code d\'autorisation. Réessayez.');
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
                };
            })();
        </script>
    @else
        <div class="error">La connexion WhatsApp n'est pas disponible pour le moment. Contactez le support.</div>
    @endif
</body>
</html>
