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
        font-size: 1.6rem;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    .saas-login-subtitle {
        color: #6b7280;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.5;
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

    @media (max-width: 576px) {
        .saas-login-card {
            border-radius: 1.25rem;
        }
        .saas-login-header {
            padding: 2rem 1.25rem 1.25rem;
        }
        .saas-login-title {
            font-size: 1.4rem;
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
                            <i class="fa fa-key fa-lg"></i>
                        </div>
                        <h1 class="saas-login-title mb-0">{{  __tr('Forgot Password?') }}</h1>
                    </div>
                    <p class="saas-login-subtitle mb-0">
                        {{ __tr('No problem. Just let us know your email address and we will email you a password reset link.') }}
                    </p>
                </div>

                <div class="card-body px-4 px-md-5 py-4">
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4 text-success font-weight-bold" :status="session('status')" />
                    <!-- Validation Errors -->
                    <x-auth-validation-errors class="mb-4 text-danger font-weight-bold" :errors="$errors" />

                    <x-lw.form action="{{ route('auth.password.request') }}" class="lw-ajax-form" data-secured="true">
                        <!-- Email Address -->
                        <div class="form-group mb-4">
                            <label class="form-control-label text-dark font-weight-bold mb-2">{{ __tr('Email') }}</label>
                            <div class="saas-input-wrapper">
                                <i class="fa fa-at saas-input-icon"></i>
                                <input id="email" class="form-control saas-form-control" placeholder="{{ __tr('Email') }}" type="email" name="email" required autofocus>
                            </div>
                        </div>

                        <div class="text-center mt-2">
                            <button type="submit" class="btn btn-primary saas-btn-primary btn-block w-100 mb-3">
                                {{ __tr('Email Password Reset Link') }}
                            </button>
                        </div>
                    </x-lw.form>
                </div>

                <div class="card-footer bg-transparent border-0 text-center pb-4 pt-0">
                    <a href="{{ route('auth.login') }}" class="btn saas-btn-outline font-weight-bold">
                        <i class="fa fa-arrow-left mr-2"></i> {{ __tr('Back to Sign In') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection