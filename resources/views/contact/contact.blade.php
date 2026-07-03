@php
    $currentAppTheme = '';
    $currentAppTheme = getUserAppTheme();
@endphp
@extends('layouts.app', ['class' => 'main-content-has-bg'])

@section('content')
@include('layouts.headers.guest')

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ═══════════════════════════════════════════
       CONTACT PAGE — PRO MAX DESIGN
    ═══════════════════════════════════════════ */
    /* ══ Uniformiser l'arrière-plan avec la page d'accueil ══ */
    html, html > body {
        background: #fafbf8 url('{{ asset("imgs/wa-message-bg-faded.png") }}') repeat fixed !important;
        background-color: #fafbf8 !important;
    }
    .main-content-has-bg::before {
        display: none !important;
    }
    .main-content, .header, .header.py-4 {
        background: transparent !important;
    }
    .contact-page-wrapper {
        padding-top: 100px;
        padding-bottom: 80px;
        min-height: 100vh;
        background: transparent !important;
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Header */
    .contact-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .contact-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(25,135,84,0.08);
        color: #198754;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.5rem 1.2rem;
        border-radius: 50px;
        margin-bottom: 1.2rem;
    }
    .contact-header h1 {
        font-weight: 800;
        font-size: clamp(2rem, 4vw, 2.8rem);
        color: #1f2937;
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    .contact-header p {
        color: #6b7280;
        font-size: 1.15rem;
        max-width: 550px;
        margin: 0 auto;
        line-height: 1.7;
    }

    /* Card */
    .contact-card {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255,255,255,0.6);
        border-radius: 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .row.no-gutters { margin: 0; }
    .row.no-gutters > [class*="col-"] { padding: 0; }

    /* ── Left: Info Panel ── */
    .contact-info-panel {
        background: linear-gradient(160deg, #198754 0%, #0f5132 100%);
        color: white;
        padding: 3rem 2.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .contact-info-panel::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .contact-info-panel::after {
        content: '';
        position: absolute;
        bottom: -40px; left: -40px;
        width: 150px; height: 150px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }
    .contact-info-panel h3 {
        color: white;
        font-weight: 700;
        margin-bottom: 0.6rem;
        font-size: 1.8rem;
        position: relative;
        z-index: 1;
    }
    .contact-info-panel > .info-subtitle {
        color: rgba(255,255,255,0.75);
        font-size: 1rem;
        margin-bottom: 2.5rem;
        position: relative;
        z-index: 1;
    }
    .contact-detail-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.8rem;
        position: relative;
        z-index: 1;
    }
    .contact-detail-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    .contact-detail-icon i {
        font-size: 1.1rem;
        color: #d1fae5;
    }
    .contact-detail-item strong {
        display: block;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 0.3rem;
    }
    .contact-detail-item .detail-value {
        color: white;
        font-size: 1.02rem;
        line-height: 1.5;
    }

    /* Social row */
    .contact-social-row {
        display: flex;
        gap: 0.8rem;
        margin-top: 2rem;
        position: relative;
        z-index: 1;
    }
    .contact-social-row a {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: rgba(255,255,255,0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }
    .contact-social-row a:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
    }

    /* ── Right: Form Panel ── */
    .contact-form-panel {
        padding: 3rem 2.5rem;
    }
    .contact-form-panel h4 {
        font-weight: 700;
        font-size: 1.5rem;
        color: #1f2937;
        margin-bottom: 0.4rem;
    }
    .contact-form-panel .form-subtitle {
        color: #9ca3af;
        font-size: 0.95rem;
        margin-bottom: 2rem;
    }
    .contact-form-panel label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #374151;
        margin-bottom: 0.4rem;
    }
    .contact-form-panel .form-control {
        background-color: #f9fafb;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.85rem 1.1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: none;
        color: #1f2937;
    }
    .contact-form-panel .form-control::placeholder {
        color: #9ca3af;
    }
    .contact-form-panel .form-control:focus {
        background-color: #fff;
        border-color: #198754;
        box-shadow: 0 0 0 4px rgba(25,135,84,0.12);
    }

    /* Submit button */
    .btn-contact-submit {
        background: linear-gradient(135deg, #198754, #146c43);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.95rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(25,135,84,0.25);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
    }
    .btn-contact-submit:hover {
        background: linear-gradient(135deg, #146c43, #0f5132);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(25,135,84,0.35);
        color: white;
    }

    /* ── Responsive ── */
    @media (max-width: 991px) {
        .contact-page-wrapper { padding-top: 120px; padding-bottom: 50px; }
        .contact-form-panel { padding: 2rem 1.5rem; }
        .contact-info-panel { padding: 2.5rem 1.5rem; }
        .contact-header h1 { font-size: 2rem; }
    }
    @media (max-width: 576px) {
        .contact-form-panel { padding: 1.5rem 1.2rem; }
        .contact-info-panel { padding: 2rem 1.2rem; }
    }
</style>

<div class="contact-page-wrapper" id="pageTop">
    <div class="container">
        <!-- Header -->
        <div class="contact-header">
            <div class="contact-badge">
                <i class="fa fa-envelope"></i> {{ __tr('Contact Us') }}
            </div>
            <h1>{{ __tr('We\'d Love to Hear From You') }}</h1>
            <p>{{ __tr('Have a question or need help? Drop us a message and our team will get back to you as soon as possible.') }}</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11">
                <div class="contact-card">
                    <div class="row no-gutters">

                        <!-- Left: Info Panel -->
                        <div class="col-lg-5 order-lg-1 order-2">
                            <div class="contact-info-panel">
                                <h3>{{ __tr('Get in Touch') }}</h3>
                                <p class="info-subtitle">{{ __tr('We usually respond within 24 hours') }}</p>

                                @if (getAppSettings('contact_details'))
                                <div class="contact-detail-item">
                                    <div class="contact-detail-icon">
                                        <i class="fa fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <strong>{{ __tr('Our Details') }}</strong>
                                        <div class="detail-value lw-ws-pre-line">
                                            {!! getAppSettings('contact_details') !!}
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="contact-detail-item">
                                    <div class="contact-detail-icon">
                                        <i class="fa fa-clock"></i>
                                    </div>
                                    <div>
                                        <strong>{{ __tr('Support Hours') }}</strong>
                                        <div class="detail-value">{{ __tr('Mon - Fri, 9:00 AM - 6:00 PM') }}</div>
                                    </div>
                                </div>

                                <div class="contact-detail-item">
                                    <div class="contact-detail-icon">
                                        <i class="fab fa-whatsapp"></i>
                                    </div>
                                    <div>
                                        <strong>{{ __tr('WhatsApp') }}</strong>
                                        <div class="detail-value">{{ __tr('Chat with us anytime') }}</div>
                                    </div>
                                </div>

                                <div class="contact-social-row">
                                    <a href="#" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form Panel -->
                        <div class="col-lg-7 order-lg-2 order-1">
                            <div class="contact-form-panel">
                                <h4>{{ __tr('Send us a Message') }}</h4>
                                <p class="form-subtitle">{{ __tr('Fill in the form below and we\'ll reply shortly') }}</p>

                                <form class="lw-ajax-form" id="lwContactMailForm" method="post" action="<?= route('user.contact.process') ?>" data-show-processing="true">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contactFullName">{{ __tr('Full Name') }}</label>
                                            <input class="form-control" id="contactFullName" placeholder="{{ __tr('John Doe') }}" type="text" name="full_name" value="{{ old('full_name') }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="contactEmail">{{ __tr('Email') }}</label>
                                            <input class="form-control" id="contactEmail" placeholder="{{ __tr('john@example.com') }}" type="email" name="email" value="{{ old('email') }}" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contactSubject">{{ __tr('Subject') }}</label>
                                        <input class="form-control" id="contactSubject" placeholder="{{ __tr('How can we help?') }}" type="text" name="subject" value="{{ old('subject') }}" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="contactMessage">{{ __tr('Message') }}</label>
                                        <textarea class="form-control" id="contactMessage" rows="5" placeholder="{{ __tr('Tell us more about your request...') }}" name="message" required></textarea>
                                    </div>

                                    @if(getAppSettings('enable_recaptcha'))
                                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                    <div class="g-recaptcha mb-4 d-flex justify-content-center" data-sitekey="{{ getAppSettings('recaptcha_site_key') }}"></div>
                                    @endif

                                    <button type="submit" class="btn-contact-submit">
                                        <i class="fa fa-paper-plane"></i> {{ __tr('Send Message') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection