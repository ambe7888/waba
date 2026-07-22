@extends('layouts.app', ['class' => 'main-content-has-bg'])

@section('content')
@include('layouts.headers.guest')
<style>
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
    .saas-login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 1.5rem;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .saas-login-header {
        background: transparent;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 2.2rem 1.5rem 1.5rem;
    }
    .saas-login-title {
        font-family: 'Plus Jakarta Sans', sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
        font-weight: 700;
        font-size: 1.6rem;
        color: #1f2937;
        margin-bottom: 0;
    }
    .saas-form-control {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        background-color: #f9fafb !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        padding: 0.8rem 1.25rem !important;
        font-size: 0.95rem;
        color: #1f2937 !important;
        transition: all 0.2s ease;
        box-sizing: border-box !important;
    }
    .saas-form-control:focus {
        background-color: #ffffff !important;
        border-color: #198754 !important;
        box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1) !important;
    }
    .saas-input-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 1.25rem;
        color: #9ca3af;
        z-index: 10;
        font-size: 1.05rem;
    }
    .saas-input-wrapper {
        position: relative;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .saas-input-wrapper .saas-form-control {
        padding-left: 3rem !important;
    }
    .saas-input-wrapper .saas-form-control.has-toggle {
        padding-right: 3rem !important;
    }
    .saas-password-toggle-btn {
        position: absolute;
        right: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        z-index: 10;
        padding: 0.25rem;
        transition: color 0.2s ease;
    }
    .saas-password-toggle-btn:hover {
        color: #198754;
    }
    .saas-password-toggle-btn:focus {
        outline: none;
    }
    .btn.btn-primary.saas-btn-primary,
    .btn.btn-primary.saas-btn-primary:focus,
    .btn.btn-primary.saas-btn-primary:active,
    .btn.btn-primary.saas-btn-primary.active,
    .btn.btn-primary.saas-btn-primary:not(:disabled):not(.disabled):active {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
    }
    .btn.btn-primary.saas-btn-primary {
        border-radius: 0.75rem;
        font-weight: 600;
        padding: 0.8rem 1.5rem;
        font-size: 1.05rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(25, 135, 84, 0.2);
    }
    .btn.btn-primary.saas-btn-primary:hover,
    .btn.btn-primary.saas-btn-primary:focus:hover,
    .btn.btn-primary.saas-btn-primary:active:hover {
        background-color: #146c43 !important;
        border-color: #146c43 !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 12px -2px rgba(25, 135, 84, 0.3) !important;
    }
    .btn.saas-btn-outline,
    .btn.saas-btn-outline:focus,
    .btn.saas-btn-outline:active,
    .btn.saas-btn-outline.active {
        border: 2px solid #198754 !important;
        color: #198754 !important;
        background-color: transparent !important;
    }
    .btn.saas-btn-outline {
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        font-weight: 600;
        width: 100%;
        display: block;
        box-sizing: border-box;
    }
    .btn.saas-btn-outline:hover,
    .btn.saas-btn-outline:focus:hover,
    .btn.saas-btn-outline:active:hover,
    .btn.saas-btn-outline:not(:disabled):not(.disabled):active {
        background-color: #198754 !important;
        color: #ffffff !important;
        border-color: #198754 !important;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(25, 135, 84, 0.2) !important;
    }
    .btn.saas-step-btn-back,
    .btn.saas-step-btn-back:focus,
    .btn.saas-step-btn-back:active,
    .btn.saas-step-btn-back.active {
        border: 2px solid #198754 !important;
        color: #198754 !important;
        background-color: transparent !important;
    }
    .btn.saas-step-btn-back {
        border-radius: 0.75rem;
        padding: 0.8rem 1.5rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        font-weight: 600;
        box-sizing: border-box;
    }
    .btn.saas-step-btn-back:hover,
    .btn.saas-step-btn-back:focus:hover,
    .btn.saas-step-btn-back:active:hover {
        background-color: #e8f8ee !important;
        color: #146c43 !important;
        border-color: #146c43 !important;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .saas-steps-indicator {
        position: relative;
    }
    .step-indicator-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 2;
        width: 70px;
    }
    .step-num {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .step-label {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 0.5rem;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }
    .step-line {
        height: 3px;
        background-color: #e5e7eb;
        border-radius: 2px;
        margin-top: -1.4rem;
        transition: all 0.3s ease;
    }
    .step-indicator-item.active .step-num {
        background-color: #e8f8ee;
        color: #198754;
        border-color: #198754;
    }
    .step-indicator-item.active .step-label {
        color: #198754;
    }
    .step-indicator-item.completed .step-num {
        background-color: #198754;
        color: #ffffff;
    }
    .step-indicator-item.completed .step-label {
        color: #198754;
    }
    .step-line.filled {
        background-color: #198754;
    }
    .step-indicator-item {
        cursor: pointer;
    }
    .step-indicator-item.has-error .step-num {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
    }
    .step-indicator-item.has-error .step-label {
        color: #dc2626 !important;
        font-weight: 700;
    }
    @media (max-width: 576px) {
        .saas-login-card {
            border-radius: 1.25rem;
        }
        .saas-login-header {
            padding: 2rem 1.25rem 1.25rem;
        }
        .saas-login-title {
            font-size: 1.35rem;
        }
        .step-label {
            display: none; /* Hide labels on mobile to prevent squishing */
        }
        .step-line {
            margin-top: -1.1rem;
        }
    }
</style>

<div class="container lw-guest-page-container-block pb-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9 col-sm-11">
            @if(getAppSettings('enable_vendor_registration'))
            <div class="card saas-login-card">
                <div class="saas-login-header text-center">
                    <div class="d-flex align-items-center justify-content-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mr-3" style="width: 42px; height: 42px; background-color: #e8f8ee; color: #198754; flex-shrink: 0;">
                            <i class="fa fa-store fa-lg"></i>
                        </div>
                        <h1 class="saas-login-title">{{  __tr('Register as Vendor/Company') }}</h1>
                    </div>

                    <!-- Step Progress Bar -->
                    <div class="saas-steps-indicator d-flex justify-content-between align-items-center px-4 mt-3">
                        <div class="step-indicator-item active" id="ind-1" onclick="goToStep(1)" title="{{ __tr('Step 1: Company') }}">
                            <span class="step-num">1</span>
                            <span class="step-label">{{ __tr('Company') }}</span>
                        </div>
                        <div class="step-line flex-grow-1 mx-2" id="line-1"></div>
                        <div class="step-indicator-item" id="ind-2" onclick="goToStep(2)" title="{{ __tr('Step 2: Profile') }}">
                            <span class="step-num">2</span>
                            <span class="step-label">{{ __tr('Profile') }}</span>
                        </div>
                        <div class="step-line flex-grow-1 mx-2" id="line-2"></div>
                        <div class="step-indicator-item" id="ind-3" onclick="goToStep(3)" title="{{ __tr('Step 3: Security') }}">
                            <span class="step-num">3</span>
                            <span class="step-label">{{ __tr('Security') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body px-4 px-md-5 py-4">
                    @php
                    $formSignUpRoute = route('auth.register.process');
                    if (getAppSettings('activation_required_for_new_user')) {
                        $formSignUpRoute = route('activation_required.auth.register.process');
                    }
                    @endphp

                    <x-lw.form :action="$formSignUpRoute" data-secured="true" id="saasMultiStepForm">
                        
                        <!-- Error Banner for Form Steps -->
                        <div id="saasFormStepErrorAlert" class="alert alert-danger mb-4" style="display: none; border-radius: 0.75rem;">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-exclamation-triangle fa-lg mr-3"></i>
                                <div>
                                    <strong class="d-block">{{ __tr('Saisie incomplète ou incorrecte') }}</strong>
                                    <span id="saasFormStepErrorMessage" class="small">{{ __tr('Des erreurs sont survenues. Vous avez été réorienté vers l\'étape contenant le champ à corriger.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 1: COMPANY INFO -->
                        <div class="saas-step" id="step-1">
                            <div class="form-group mb-4">
                                <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Vendor/Company Name') }}</label>
                                <div class="saas-input-wrapper">
                                    <i class="fa fa-store saas-input-icon"></i>
                                    <input class="form-control saas-form-control" placeholder="{{ __tr('Vendor/Company Name') }}" type="text" name="vendor_title" id="vendor_title" value="{{ old('vendor_title') }}" required autofocus>
                                </div>
                            </div>
                            <div class="text-right mt-4 pt-2">
                                <button type="button" class="btn btn-primary saas-btn-primary px-4" onclick="nextStep()">
                                    {{ __tr('Next') }} <i class="fa fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: ADMIN PROFILE -->
                        <div class="saas-step" id="step-2" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Username -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Username') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-id-card saas-input-icon"></i>
                                            <input class="form-control saas-form-control" placeholder="{{ __tr('Username') }}" type="text" name="username" id="username" value="{{ old('username') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Email Address -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Email') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-at saas-input-icon"></i>
                                            <input class="form-control saas-form-control" placeholder="{{ __tr('Email') }}" type="email" name="email" id="email" value="{{ old('email') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <!-- First Name -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('First Name') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-user saas-input-icon"></i>
                                            <input class="form-control saas-form-control" placeholder="{{ __tr('First Name') }}" type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Last Name -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Last Name') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-user saas-input-icon"></i>
                                            <input class="form-control saas-form-control" placeholder="{{ __tr('Last Name') }}" type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- mobile no -->
                            <div class="form-group mb-4">
                                <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Mobile Number') }}</label>
                                <div class="saas-input-wrapper">
                                    <i class="fas fa-mobile-alt saas-input-icon"></i>
                                    <input class="form-control saas-form-control" placeholder="{{ __tr('Mobile Number') }}" type="text" name="mobile_number" id="mobile_number" value="{{ old('mobile_number') }}" required>
                                </div>
                                <small class="text-muted mt-2 d-block font-weight-500">{{__tr("Mobile number should be with country code without 0 or +")}}</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-2" style="gap: 15px;">
                                <button type="button" class="btn saas-step-btn-back px-4" onclick="prevStep()">
                                    <i class="fa fa-arrow-left mr-2"></i> {{ __tr('Back') }}
                                </button>
                                <button type="button" class="btn btn-primary saas-btn-primary px-4" onclick="nextStep()">
                                    {{ __tr('Next') }} <i class="fa fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: SECURITY & PRIVACY -->
                        <div class="saas-step" id="step-3" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Password -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Password') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-key saas-input-icon"></i>
                                            <input id="lwPassword" class="form-control saas-form-control has-toggle" placeholder="{{ __tr('Password') }}" type="password" name="password" required>
                                            <button type="button" class="saas-password-toggle-btn" onclick="togglePasswordVisibility('lwPassword', this)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Confirm Password -->
                                    <div class="form-group mb-4">
                                        <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Confirm Password') }}</label>
                                        <div class="saas-input-wrapper">
                                            <i class="fa fa-key saas-input-icon"></i>
                                            <input id="lwConfirmPassword" class="form-control saas-form-control has-toggle" placeholder="{{ __tr('Confirm Password') }}" type="password" name="password_confirmation" required>
                                            <button type="button" class="saas-password-toggle-btn" onclick="togglePasswordVisibility('lwConfirmPassword', this)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- privacy policy -->
                            @if (getAppSettings('user_terms') or getAppSettings('vendor_terms') or getAppSettings('privacy_policy'))
                            <div class="row my-4">
                                <div class="col-12">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" name="terms_and_conditions" id="itemsAccept" type="checkbox" required>
                                        <label class="custom-control-label text-muted font-weight-500" for="itemsAccept">
                                            <span class="align-middle pt-1 d-inline-block">{{ __tr('I agree with the') }}
                                                @if (getAppSettings('user_terms'))
                                                <a class="hover-primary font-weight-bold" style="color: #198754;" href="{{ route('app.terms_and_policies', ['contentName' => 'user_terms']) }}">{{ __tr('User Terms And Conditions') }}</a>,
                                                @endif
                                                @if (getAppSettings('vendor_terms'))
                                                <a class="hover-primary font-weight-bold" style="color: #198754;" href="{{ route('app.terms_and_policies', ['contentName' => 'vendor_terms']) }}">{{ __tr('Vendor Terms And Conditions') }}</a>,
                                                @endif
                                                @if (getAppSettings('privacy_policy'))
                                                <a class="hover-primary font-weight-bold" style="color: #198754;" href="{{ route('app.terms_and_policies', ['contentName' => 'privacy_policy']) }}">{{ __tr('Privacy Policy') }}</a>
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-2" style="gap: 15px;">
                                <button type="button" class="btn saas-step-btn-back px-4" onclick="prevStep()">
                                    <i class="fa fa-arrow-left mr-2"></i> {{ __tr('Back') }}
                                </button>
                                <button type="submit" class="btn btn-primary saas-btn-primary px-4">
                                    {{ __tr('Create Account') }} <i class="fa fa-user-plus ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </x-lw.form>
                </div>

                <div class="card-footer bg-transparent border-0 text-center pb-4 pt-0">
                    <p class="text-muted mb-2 font-weight-500">{{ __tr('Already have an Account?') }}</p>
                    <a href="{{ route('auth.login') }}" class="btn saas-btn-outline font-weight-bold">
                        {{ __tr('Click here to login') }} <i class="fa fa-sign-in-alt ml-2"></i>
                    </a>
                </div>
            </div>
            @else
            <div class="card saas-login-card">
                <div class="card-header text-center">
                    @if (getAppSettings('message_for_disabled_registration'))
                        {!! getAppSettings('message_for_disabled_registration') !!}
                    @else
                        {{ __tr('Vendor Registrations are closed now.') }}
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    let currentStep = 1;

    function showStep(step) {
        document.querySelectorAll('.saas-step').forEach(el => el.style.display = 'none');
        const targetStep = document.getElementById('step-' + step);
        if (targetStep) {
            targetStep.style.display = 'block';
        }

        // Update Indicators
        for (let i = 1; i <= 3; i++) {
            const ind = document.getElementById('ind-' + i);
            const line = document.getElementById('line-' + (i - 1));
            
            if (ind) {
                if (i < step) {
                    ind.classList.add('completed');
                    ind.classList.remove('active');
                } else if (i === step) {
                    ind.classList.add('active');
                    ind.classList.remove('completed');
                } else {
                    ind.classList.remove('active', 'completed');
                }
            }

            if (line) {
                if (i < step) {
                    line.classList.add('filled');
                } else {
                    line.classList.remove('filled');
                }
            }
        }
        currentStep = step;
        
        // Re-evaluate step errors to highlight indicators
        checkStepErrors();

        // Scroll to card top smoothly
        const card = document.querySelector('.saas-login-card');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function checkStepErrors() {
        let firstErrorStep = null;

        for (let i = 1; i <= 3; i++) {
            const stepEl = document.getElementById('step-' + i);
            const ind = document.getElementById('ind-' + i);
            if (!stepEl || !ind) continue;

            // Check for fields with validation errors or server error labels
            const errorNodes = stepEl.querySelectorAll('.is-invalid, .has-danger, label.error, div.error, .invalid-feedback');
            let hasError = false;
            
            errorNodes.forEach(err => {
                if (err.classList.contains('is-invalid') || err.classList.contains('has-danger')) {
                    hasError = true;
                } else if (err.textContent && err.textContent.trim().length > 0 && getComputedStyle(err).display !== 'none') {
                    hasError = true;
                }
            });

            if (hasError) {
                ind.classList.add('has-error');
                if (!firstErrorStep) {
                    firstErrorStep = i;
                }
            } else {
                ind.classList.remove('has-error');
            }
        }

        const alertEl = document.getElementById('saasFormStepErrorAlert');
        if (alertEl) {
            if (firstErrorStep) {
                alertEl.style.display = 'block';
            } else {
                alertEl.style.display = 'none';
            }
        }

        return firstErrorStep;
    }

    function validateStep(step) {
        const stepContainer = document.getElementById('step-' + step);
        if (!stepContainer) return true;

        const inputs = stepContainer.querySelectorAll('input[required]');
        let isValid = true;
        for (let input of inputs) {
            if (!input.checkValidity()) {
                showStep(step);
                input.reportValidity();
                isValid = false;
                break;
            }
        }
        return isValid;
    }

    function nextStep() {
        if (validateStep(currentStep)) {
            if (currentStep < 3) {
                showStep(currentStep + 1);
            }
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }

    function goToStep(step) {
        if (step < currentStep) {
            showStep(step);
        } else if (step > currentStep) {
            for (let s = currentStep; s < step; s++) {
                if (!validateStep(s)) {
                    return;
                }
            }
            showStep(step);
        }
    }

    function togglePasswordVisibility(fieldId, buttonEl) {
        const field = document.getElementById(fieldId);
        const icon = buttonEl.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('saasMultiStepForm');
        if (!form) return;

        // Monitor DOM changes for server-side validation error injection
        const observer = new MutationObserver(function() {
            const errorStep = checkStepErrors();
            if (errorStep && errorStep !== currentStep) {
                showStep(errorStep);
                const firstErrInput = document.querySelector('#step-' + errorStep + ' .is-invalid, #step-' + errorStep + ' input:invalid');
                if (firstErrInput) {
                    firstErrInput.focus();
                }
            }
        });

        observer.observe(form, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });

        // Hook into jQuery AJAX completion if available
        if (window.jQuery) {
            $(document).ajaxComplete(function() {
                setTimeout(function() {
                    const errorStep = checkStepErrors();
                    if (errorStep) {
                        showStep(errorStep);
                    }
                }, 50);
            });
        }

        // Pre-validate all steps when submit is clicked
        form.addEventListener('submit', function(e) {
            for (let step = 1; step <= 3; step++) {
                if (!validateStep(step)) {
                    showStep(step);
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            }
        }, true);
    });
</script>
@endsection