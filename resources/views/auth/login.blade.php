@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<style>
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
        padding: 2.5rem 1.5rem 1.5rem;
    }
    .saas-login-title {
        font-family: 'Plus Jakarta Sans', sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
        font-weight: 700;
        font-size: 1.75rem;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    .saas-login-subtitle {
        color: #6b7280;
        font-size: 1rem;
        font-weight: 400;
    }
    .saas-form-control {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        background-color: #f9fafb !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        padding: 0.875rem 1.25rem !important;
        font-size: 1rem;
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
        font-size: 1.1rem;
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
        padding: 0.875rem 1.5rem;
        font-size: 1.1rem;
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
    .saas-social-btn {
        border-radius: 0.75rem;
        padding: 0.75rem;
        font-weight: 600;
        transition: all 0.2s ease;
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        color: #374151;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-bottom: 0.75rem;
        box-sizing: border-box;
    }
    .saas-social-btn:hover {
        background-color: #f9fafb;
        border-color: #d1d5db;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        color: #111827;
        text-decoration: none;
    }
    .saas-divider {
        display: flex;
        align-items: center;
        text-align: center;
        color: #9ca3af;
        font-size: 0.875rem;
        margin: 1.75rem 0;
        font-weight: 500;
    }
    .saas-divider::before, .saas-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #e5e7eb;
    }
    .saas-divider::before { margin-right: 1em; }
    .saas-divider::after { margin-left: 1em; }
    .hover-primary:hover { color: #198754 !important; text-decoration: none; }

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
        font-size: 1rem;
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

    @media (min-height: 650px) {
        html, body {
            height: 100vh !important;
            overflow: hidden !important;
        }
        .main-content {
            min-height: 100vh !important;
            height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            overflow: hidden !important;
        }
        .lw-guest-page-container-block {
            margin: auto !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            width: 100% !important;
        }
        .header.py-4.mb-2 {
            display: none !important;
        }
        footer.py-5 {
            display: none !important;
        }
    }

    @media (min-width: 576px) {
        .saas-social-btn {
            margin-bottom: 0;
        }
    }
    @media (max-width: 576px) {
        .saas-login-card {
            border-radius: 1.25rem;
        }
        .saas-login-header {
            padding: 2rem 1.25rem 1.25rem;
        }
        .saas-login-title {
            font-size: 1.5rem;
        }
        .saas-social-btn-group {
            flex-direction: column;
        }
    }
</style>
<div class="container lw-guest-page-container-block pb-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-11">
            <div class="card saas-login-card">
                <div class="saas-login-header text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mr-3" style="width: 42px; height: 42px; background-color: #e8f8ee; color: #198754; flex-shrink: 0;">
                            <i class="fa fa-lock fa-lg"></i>
                        </div>
                        <h1 class="saas-login-title mb-0">{{  __tr('Welcome Back') }}</h1>
                    </div>
                    <p class="saas-login-subtitle mb-0">{{  __tr('Please enter your details to sign in.') }}</p>
                </div>
                
                @if (isDemo())
                    <div class="px-4 pt-4 text-center">
                        <button onclick="document.getElementById('lwLoginEmail').value='demosuperadmin';document.getElementById('lwLoginPassword').value='demopass12';" class="btn btn-sm btn-outline-success rounded-pill mb-2 px-3 fw-bold">{{  __tr('Demo Super Admin') }}</button>
                        <button onclick="document.getElementById('lwLoginEmail').value='testcompany';document.getElementById('lwLoginPassword').value='demopass12';" class="btn btn-sm btn-outline-success rounded-pill mb-2 px-3 fw-bold">{{  __tr('Demo Company') }}</button>
                    </div>
                @endif

                <div class="card-body px-4 px-md-5 py-4">
                    <x-lw.form id="lwLoginForm" data-secured="true" :action="route('auth.login.process')">
                        
                        <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }} mb-4">
                            <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Email or Username or Mobile Number') }}</label>
                            <div class="saas-input-wrapper">
                                <i class="fa fa-user saas-input-icon"></i>
                                <input id="lwLoginEmail" class="form-control saas-form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="{{ __tr('Email or Username or Mobile Number') }}" type="text" name="email" value="" required autofocus autocomplete="email">
                            </div>
                            <small class="text-muted mt-2 d-block font-weight-500">{{__tr("If using mobile number, include country code without 0 or +")}}</small>
                        </div>
                        
                        <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }} mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-control-label text-dark font-weight-bold mb-0">{{ __tr('Password') }}</label>
                                @if (Route::has('auth.password.request'))
                                    <a href="{{ route('auth.password.request') }}" class="text-muted small font-weight-bold hover-primary" style="transition: color 0.2s;">
                                        {{ __tr('Forgot password?') }}
                                    </a>
                                @endif
                            </div>
                            <div class="saas-input-wrapper">
                                <i class="fa fa-key saas-input-icon"></i>
                                <input id="lwLoginPassword" class="form-control saas-form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="••••••••" type="password" value="" required autocomplete="current-password">
                            </div>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-4">
                            <input class="custom-control-input" name="remember" id="customCheckLogin" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <label class="custom-control-label text-muted font-weight-500" for="customCheckLogin">
                                <span class="align-middle pt-1 d-inline-block">{{ __tr('Remember me for 30 days') }}</span>
                            </label>
                        </div>
                        
                        <div class="text-center mt-2">
                            <button type="submit" class="btn btn-primary saas-btn-primary btn-block w-100">{{ __tr('Sign In') }}</button>
                        </div>
                    </x-lw.form>

                    @if(getAppSettings('allow_google_login') || getAppSettings('allow_facebook_login'))
                        <div class="saas-divider">{{ __tr('Or continue with') }}</div>
                        <div class="d-flex saas-social-btn-group">
                            @if(getAppSettings('allow_google_login'))
                            <a href="<?= route('login.google') ?>" class="btn saas-social-btn flex-fill {{ getAppSettings('allow_facebook_login') ? 'mr-sm-2' : '' }}">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google" width="18" class="mr-2"> Google
                            </a>
                            @endif
                            
                            @if(getAppSettings('allow_facebook_login'))
                            <a href="<?= route('login.facebook') ?>" class="btn saas-social-btn flex-fill">
                                <i class="fab fa-facebook-f text-primary mr-2" style="font-size: 18px; color: #1877F2 !important;"></i> Facebook
                            </a>
                            @endif
                        </div>
                    @endif
                </div>

                @if(getAppSettings('enable_vendor_registration') || getAppSettings('message_for_disabled_registration'))
                <div class="card-footer bg-transparent border-0 text-center pb-4 pt-0">
                    <p class="text-muted mb-3 font-weight-500">{{ __tr("Don't have an account?") }}</p>
                    <a href="{{ route('auth.register') }}" class="btn saas-btn-outline font-weight-bold">
                        {{ __tr('Create an account') }} <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection