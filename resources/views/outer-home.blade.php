<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION ?? ''}}">
@php
$appName = getAppSettings('name');
@endphp
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ (isset($title) and $title) ? ' - ' . $title : __tr('Welcome') }} - {{ $appName }}</title>
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $appName }}" />
    <meta name="description" content="{{ getAppSettings('description') }}" />
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $appName }}" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="{{ $appName }}" />
    <meta property="og:description" content="{{ getAppSettings('description') }}" />
    <meta property="og:image" content="{{ getAppSettings('logo_image_url') }}" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ url('/') }}" />
    <meta property="twitter:title" content="{{ $appName }}" />
    <meta property="twitter:description" content="{{ getAppSettings('description') }}" />
    <meta property="twitter:image" content="{{ getAppSettings('logo_image_url') }}" />

    <!-- FAVICON -->
    <link href="{{ getAppSettings('favicon_image_url') }}" rel="icon">
    {!! __yesset([
    'static-assets/packages/fontawesome/css/all.css',
    'static-assets/packages/bootstrap-icons/font/bootstrap-icons.css'
    ]) !!}
    <!-- Google Fonts — Plus Jakarta Sans (recommended by design system) + Playfair Display for hero -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════
           DESIGN TOKENS
        ═══════════════════════════════════════════ */
        :root {
            --primary: #198754;
            --primary-dark: #146c43;
            --primary-light: #d1fae5;
            --primary-glow: rgba(25, 135, 84, 0.15);
            --bg: #fafbf8;
            --surface: #ffffff;
            --text: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --radius: 16px;
            --radius-sm: 10px;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-lg: 0 20px 50px rgba(0,0,0,0.08);
            --shadow-glow: 0 8px 30px rgba(25,135,84,0.18);
            --transition: 200ms ease;
        }

        /* ═══════════════════════════════════════════
           RESET & BASE
        ═══════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 1.05rem;
            background: var(--bg) url('{{ asset("imgs/wa-message-bg-faded.png") }}') repeat fixed;
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            margin: 0;
        }
        h1,h2,h3,h4,h5,h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
        }
        a { text-decoration: none; }
        img { max-width: 100%; }
        section { position: relative; }

        /* ═══════════════════════════════════════════
           NAVBAR
        ═══════════════════════════════════════════ */
        nav#mainNav {
            top: 25px;
            width: calc(100% - 40px);
            max-width: 1100px;
            margin: 0 auto;
            border-radius: 100px;
            background: rgba(255,255,255,0.85) !important;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 30px rgba(0,0,0,0.04) !important;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            padding: 0.5rem 0;
        }
        nav#mainNav.scrolled {
            top: 15px;
            padding: 0.3rem 0;
            background: rgba(255,255,255,0.98) !important;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08) !important;
            border: 1px solid rgba(0,0,0,0.05);
        }
        #mainNav .navbar-brand-img {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        nav#mainNav.scrolled .navbar-brand-img {
            transform: scale(0.9);
        }
        #mainNav .navbar-nav .nav-item .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 1.2rem;
            margin: 0 0.15rem;
            border-radius: var(--radius-full);
            transition: all 0.3s ease;
            position: relative;
        }
        /* Animated underline for nav links */
        #mainNav .navbar-nav .nav-item .nav-link::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s ease;
            opacity: 0;
            border-radius: 2px;
        }
        #mainNav .navbar-nav .nav-item .nav-link:hover {
            color: var(--primary) !important;
            background: var(--primary-glow);
        }
        #mainNav .navbar-nav .nav-item .nav-link:hover::after {
            width: 20px;
            opacity: 1;
        }
        .btn-nav-cta,
        #mainNav .navbar-nav .nav-item a[href*="register"] {
            background: var(--primary) !important;
            color: white !important;
            border-radius: var(--radius-full) !important;
            padding: 0.6rem 1.8rem !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            box-shadow: 0 4px 15px rgba(25,135,84,0.2) !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            border: none !important;
            letter-spacing: 0.5px;
        }
        .btn-nav-cta:hover,
        #mainNav .navbar-nav .nav-item a[href*="register"]:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(25,135,84,0.3) !important;
        }
        .btn-cta {
            background: var(--primary) !important;
            color: white !important;
            border-radius: var(--radius-full);
            padding: 0.55rem 1.6rem;
            font-weight: 600;
            box-shadow: var(--shadow-glow);
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }
        .btn-cta:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(25,135,84,0.3);
        }
        
        /* Mobile menu fixes */
        @media (max-width: 991px) {
            nav#mainNav { 
                padding: 0.8rem 0; 
                border-radius: 24px;
                top: 15px;
            }
            nav#mainNav .container {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }
            nav#mainNav .navbar-brand {
                width: auto !important;
                margin-right: auto;
            }
            nav#mainNav .navbar-toggler {
                margin-left: auto;
            }
            nav#mainNav .navbar-collapse {
                width: 100%;
                margin-top: 1rem;
            }
            #mainNav .navbar-nav {
                text-align: center;
                width: 100%;
            }
            #mainNav .navbar-nav .nav-item .nav-link {
                padding: 0.8rem 1.2rem;
                border-radius: var(--radius-sm);
            }
            #mainNav .navbar-nav .nav-item .nav-link::after { display: none; }
            .btn-nav-cta, #mainNav .navbar-nav .nav-item a[href*="register"] {
                display: inline-block;
                margin-top: 0.8rem;
                margin-left: 0;
            }
        }

        /* ═══════════════════════════════════════════
           HERO SECTION
        ═══════════════════════════════════════════ */
        .hero {
            padding: 160px 0 30px;
            min-height: auto;
            display: flex;
            align-items: center;
            position: relative;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 2rem;
            animation: fadeInDown 0.6s ease;
        }
        .hero-badge i { font-size: 1rem; }
        .hero-icons-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.5s ease;
        }
        .hero-icons-row i {
            font-size: 5rem;
            color: var(--primary);
            filter: drop-shadow(3px 3px 6px rgba(0,0,0,0.12));
            transition: transform 0.3s ease;
        }
        .hero-icons-row i:hover {
            transform: scale(1.1) rotate(-5deg);
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.6rem, 5.5vw, 4.5rem);
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 1.5rem;
            color: var(--text);
            animation: fadeInUp 0.6s ease 0.1s both;
        }
        .hero h1 .highlight {
            color: var(--primary);
            position: relative;
        }
        .hero p.lead {
            font-size: clamp(1.15rem, 2vw, 1.4rem);
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
            animation: fadeInUp 0.6s ease 0.2s both;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            animation: fadeInUp 0.6s ease 0.3s both;
        }
        .hero-actions .btn-primary-hero {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-full);
            padding: 1rem 2.5rem;
            font-weight: 700;
            font-size: 1.15rem;
            box-shadow: var(--shadow-glow);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .hero-actions .btn-primary-hero:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(25,135,84,0.3);
        }
        .hero-actions .btn-outline-hero {
            background: transparent;
            color: var(--text);
            border: 2px solid var(--border);
            border-radius: var(--radius-full);
            padding: 1rem 2.5rem;
            font-weight: 700;
            font-size: 1.15rem;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .hero-actions .btn-outline-hero:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Stats bar under hero */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            animation: fadeInUp 0.6s ease 0.4s both;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        /* ═══════════════════════════════════════════
           INTEGRATIONS
        ═══════════════════════════════════════════ */
        .integrations {
            padding: 30px 0;
            text-align: center;
        }
        .integrations .section-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        .integrations h3 {
            font-size: 1.6rem;
            margin-bottom: 0.75rem;
        }
        .integrations p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2.5rem;
            font-size: 1.15rem;
        }
        .integration-grid {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .integration-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            padding: 12px 22px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border);
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--text);
            transition: all 0.25s ease;
            cursor: default;
            box-shadow: var(--shadow-sm);
        }
        .integration-chip:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }
        .integration-chip i {
            font-size: 1.4rem;
        }

        /* ═══════════════════════════════════════════
           SECTION SHARED
        ═══════════════════════════════════════════ */
        .section-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .section-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        .section-header h2 {
            font-size: clamp(2rem, 3.8vw, 2.8rem);
            margin-bottom: 1rem;
        }
        .section-header p {
            color: var(--text-secondary);
            max-width: 650px;
            margin: 0 auto;
            font-size: 1.2rem;
            line-height: 1.7;
        }

        /* ═══════════════════════════════════════════
           FEATURES
        ═══════════════════════════════════════════ */
        .features { padding: 40px 0; }
        .feature-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            border: 1px solid var(--border);
            height: 100%;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            text-align: center;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }
        .feature-icon-wrap {
            width: 64px;
            height: 64px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }
        .feature-card:hover .feature-icon-wrap {
            background: var(--primary);
            color: white;
            transform: scale(1.08);
        }
        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }
        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.65;
            margin: 0;
            font-size: 1.05rem;
        }

        /* ═══════════════════════════════════════════
           TESTIMONIALS
        ═══════════════════════════════════════════ */
        .testimonials { padding: 40px 0; }
        .testimonial-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            border: 1px solid var(--border);
            height: 100%;
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            border-color: var(--primary-light);
            box-shadow: var(--shadow-md);
        }
        .testimonial-stars {
            color: #f59e0b;
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            letter-spacing: 3px;
        }
        .testimonial-card blockquote {
            font-size: 1.15rem;
            font-style: italic;
            color: var(--text-secondary);
            line-height: 1.7;
            margin: 0 0 1.5rem;
            border: none;
            padding: 0;
        }
        .testimonial-author {
            font-weight: 700;
            color: var(--text);
            font-size: 1.1rem;
        }
        .testimonial-role {
            font-size: 0.95rem;
            color: var(--primary);
            font-weight: 500;
        }

        /* ═══════════════════════════════════════════
           PRICING
        ═══════════════════════════════════════════ */
        .pricing { padding: 40px 0; }
        .pricing-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            border: 1px solid var(--border);
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .pricing-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
        }
        .pricing-card.popular {
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-glow);
        }
        .pricing-card.popular::before {
            content: 'Populaire';
            position: absolute;
            top: 16px;
            right: -30px;
            background: var(--primary);
            color: white;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 40px;
            transform: rotate(45deg);
        }
        .pricing-card .plan-name {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .pricing-card .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        .pricing-card .plan-price span {
            font-size: 1rem;
            font-weight: 400;
            color: var(--text-muted);
        }
        .pricing-card ul {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        .pricing-card ul li {
            padding: 0.7rem 0;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 1.02rem;
        }
        .pricing-card ul li:last-child { border-bottom: none; }
        .pricing-card ul li i.check {
            color: var(--primary);
            font-size: 0.85rem;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .pricing-card ul li i.cross {
            color: #e2e8f0;
            font-size: 0.85rem;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .btn-plan {
            display: block;
            width: 100%;
            text-align: center;
            padding: 0.9rem;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .btn-plan-primary {
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: var(--shadow-glow);
        }
        .btn-plan-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }
        .btn-plan-outline {
            background: transparent;
            color: var(--text);
            border: 2px solid var(--border);
        }
        .btn-plan-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* ═══════════════════════════════════════════
           FAQ
        ═══════════════════════════════════════════ */
        .faq { padding: 40px 0; }
        .faq .accordion-item {
            border: 1px solid var(--border) !important;
            border-radius: var(--radius-sm) !important;
            margin-bottom: 0.75rem;
            overflow: hidden;
            background: var(--surface);
            font-size: 1rem;
        }
        .faq .accordion-button {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 600 !important;
            font-size: 1.15rem !important;
            padding: 1.25rem 1.5rem !important;
            background: var(--surface) !important;
            color: var(--text) !important;
            box-shadow: none !important;
            border: none !important;
        }
        .faq .accordion-button:not(.collapsed) {
            color: var(--primary) !important;
            background: var(--primary-light) !important;
        }
        .faq .accordion-button::after {
            background-size: 1rem;
        }
        .faq .accordion-button:focus { box-shadow: none !important; }
        .faq .accordion-body {
            color: var(--text-secondary);
            line-height: 1.7;
            padding: 0 1.5rem 1.5rem;
            font-size: 1.05rem;
        }

        /* ═══════════════════════════════════════════
           CTA SECTION
        ═══════════════════════════════════════════ */
        .cta-final {
            padding: 50px 0;
            background: linear-gradient(135deg, var(--primary) 0%, #0d6e3e 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta-final::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .cta-final::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            border-radius: 50%;
        }
        .cta-final h2 {
            color: white !important;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        .cta-final p {
            opacity: 0.85;
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
            color: white;
        }
        .cta-final .btn-cta-white {
            background: white !important;
            color: var(--primary) !important;
            border: none !important;
            border-radius: var(--radius-full);
            padding: 1rem 3rem;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.25s ease;
            display: inline-block;
            position: relative;
            z-index: 2;
            cursor: pointer;
        }
        .cta-final .btn-cta-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            background: #f0fdf4 !important;
            color: var(--primary-dark) !important;
        }

        /* ═══════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════ */
        .site-footer {
            background: #0f172a;
            color: white;
            padding: 60px 0 0;
        }
        .site-footer .footer-top {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
            padding-bottom: 3rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .site-footer .footer-brand-col {
            max-width: 320px;
        }
        .site-footer .footer-brand-col img {
            filter: brightness(0) invert(1);
            max-height: 40px;
            margin-bottom: 1rem;
        }
        .site-footer .footer-brand-col p {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.7;
            margin: 0;
        }
        .site-footer .footer-links h6 {
            color: white;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1rem;
        }
        .site-footer .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .site-footer .footer-links ul li {
            margin-bottom: 0.5rem;
        }
        .site-footer .footer-links ul li a {
            color: #94a3b8;
            font-size: 0.9rem;
            transition: color 0.2s ease;
            text-decoration: none;
        }
        .site-footer .footer-links ul li a:hover {
            color: white;
        }
        .site-footer .footer-bottom {
            padding: 1.5rem 0;
            text-align: center;
        }
        .site-footer .footer-bottom p {
            color: #475569;
            font-size: 0.85rem;
            margin: 0;
        }

        /* ═══════════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════════ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ═══════════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════════ */
        @media (max-width: 991px) {
            .pricing-card.popular { transform: none; }
        }
        @media (max-width: 768px) {
            .hero { padding: 140px 0 60px; min-height: auto; }
            .hero-icons-row i { font-size: 3.5rem; }
            .stats-bar { gap: 2rem; }
            .stat-number { font-size: 1.5rem; }
            .integration-grid { gap: 0.75rem; }
            .integration-chip { padding: 8px 14px; font-size: 0.85rem; }
            .integration-chip i { font-size: 1.1rem; }
            .hero-actions { flex-direction: column; align-items: center; }
            .hero-actions .btn-primary-hero,
            .hero-actions .btn-outline-hero { width: 100%; max-width: 320px; text-align: center; }
        }
        @media (max-width: 768px) {
            .btn.lw-btn-block-mobile, .lw-btn-block-mobile {
                margin-bottom: 8px;
                width: 100%;
            }
        }
    </style>
</head>
<body id="page-top" class="lw-outer-home-page">
    {!! __yesset(['dist/css/app-public.css'], true) !!}

    <!-- ══════════════ NAVIGATION ══════════════ -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
        <div class="container px-4 px-lg-5">
            <a class="navbar-brand pt-0" href="{{ url('/') }}">
                <img src="{{ getAppSettings('logo_image_url') }}" class="navbar-brand-img" alt="{{ getAppSettings('name') }}" style="max-height: 32px;">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation" style="border:none; background:transparent;">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto my-3 my-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="#features">{{ __tr('Features') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">{{ __tr('Testimonials') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">{{ __tr('Pricing') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.contact.form') }}">{{ __tr('Contact') }}</a></li>

                    @include('layouts.navbars.navs.pages-menu-partial')

                    @if(!isLoggedIn())
                        <li class="nav-item"><a class="nav-link fw-bold" style="color: var(--text) !important; margin-right: 10px;" href="{{ route('auth.login') }}">{{ __tr('Login') }}</a></li>
                        @if(getAppSettings('enable_vendor_registration') or getAppSettings('message_for_disabled_registration'))
                            <li class="nav-item mt-2 mt-lg-0">
                                <a class="nav-link btn-cta text-white" href="{{ route('auth.register') }}">{{ __tr('Get Started') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                            <a class="nav-link btn-cta text-white" href="{{ route('central.console') }}">{{ __tr('Dashboard') }}</a>
                        </li>
                    @endif

                    @include('layouts.navbars.locale-menu')
                </ul>
            </div>
        </div>
    </nav>

    <!-- ══════════════ HERO ══════════════ -->
    <section class="hero">
        <div class="container text-center">
            <!-- Badge -->
            <div class="hero-badge">
                <i class="bi bi-lightning-charge-fill"></i>
                {{ __tr('Plateforme WhatsApp Marketing #1') }}
            </div>

            <!-- Icons row -->
            <div class="hero-icons-row">
                <i class="fab fa-whatsapp"></i>
                <i class="fas fa-bullhorn"></i>
                <i class="fas fa-dollar-sign"></i>
            </div>

            <!-- Heading -->
            <h1>
                {{ __tr('Engage Your Customers on') }}<br>
                <span class="highlight">WhatsApp</span> {{ __tr('Like Never Before') }}
            </h1>

            <!-- Subtitle -->
            <p class="lead">
                {{ __tr('Unlock the full potential of customer engagement with __appName__ — your comprehensive WhatsApp Marketing Platform.', ['__appName__' => $appName]) }}
            </p>

            <!-- CTA Buttons -->
            <div class="hero-actions">
                <a class="btn-primary-hero text-decoration-none" href="{{ route('auth.register') }}">
                    {{ __tr('Start Free Trial') }} <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a class="btn-outline-hero text-decoration-none" href="#features">
                    {{ __tr('Explore Features') }}
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">{{ __tr('Messages / jour') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">{{ __tr('Entreprises') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">{{ __tr('Taux de livraison') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">{{ __tr('Support') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ INTEGRATIONS ══════════════ -->
    <section class="integrations">
        <div class="container">
            <div class="section-label">{{ __tr('Boostez vos performances') }}</div>
            <h3>{{ __tr('Connectez WhatsClick à vos outils préférés') }}</h3>
            <p>{{ __tr('Intégration transparente avec Meta, Google, TikTok, et vos CRM pour automatiser et maximiser votre croissance.') }}</p>

            <div class="integration-grid">
                <div class="integration-chip">
                    <i class="fab fa-facebook" style="color: #1877F2;"></i> Meta
                </div>
                <div class="integration-chip">
                    <i class="fab fa-instagram" style="color: #E4405F;"></i> Instagram
                </div>
                <div class="integration-chip">
                    <i class="fab fa-google" style="color: #4285F4;"></i> Google
                </div>
                <div class="integration-chip">
                    <i class="fab fa-tiktok" style="color: #000;"></i> TikTok
                </div>
                <div class="integration-chip">
                    <i class="fab fa-hubspot" style="color: #FF7A59;"></i> HubSpot
                </div>
                <div class="integration-chip">
                    <i class="fab fa-salesforce" style="color: #00A1E0;"></i> Salesforce
                </div>
                <div class="integration-chip">
                    <i class="fas fa-plug" style="color: var(--primary);"></i> API
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ FEATURES ══════════════ -->
    <section class="features" id="features">
        <div class="container px-4 px-lg-5">
            <div class="section-header">
                <div class="section-label">{{ __tr('Fonctionnalités') }}</div>
                <h2>{{ __tr('Powerful Features for WhatsApp Marketing') }}</h2>
                <p>{{ __tr('Everything you need to scale your customer communications, automate responses, and drive conversions directly on WhatsApp.') }}</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-chat-dots-fill"></i></div>
                        <h3>{{ __tr('Shared Team Inbox') }}</h3>
                        <p>{{ __tr('Collaborate with your entire team. The integrated WhatsApp chat mirrors the native interface for a seamless experience.') }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-robot"></i></div>
                        <h3>{{ __tr('Smart Chatbots & AI') }}</h3>
                        <p>{{ __tr('Automate customer support 24/7 with interactive bots and OpenAI integration to provide immediate answers.') }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-megaphone-fill"></i></div>
                        <h3>{{ __tr('Broadcast Campaigns') }}</h3>
                        <p>{{ __tr('Send bulk messages safely. Our smart queuing system protects your number from getting banned by Meta.') }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <h3>{{ __tr('Contact Management') }}</h3>
                        <p>{{ __tr('Segment your audience with tags and custom fields. Import/Export contacts easily via Excel/CSV.') }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-bar-chart-fill"></i></div>
                        <h3>{{ __tr('Detailed Analytics') }}</h3>
                        <p>{{ __tr('Track delivery, read receipts, and engagement metrics to optimize your messaging strategy.') }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrap"><i class="bi bi-layout-text-window-reverse"></i></div>
                        <h3>{{ __tr('Rich Media Templates') }}</h3>
                        <p>{{ __tr('Use approved Meta templates with buttons, lists, and images to create highly interactive messages.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ TESTIMONIALS ══════════════ -->
    <section class="testimonials" id="testimonials">
        <div class="container px-4 px-lg-5">
            <div class="section-header">
                <div class="section-label">{{ __tr('Témoignages') }}</div>
                <h2>{{ __tr('Success Stories from our Community') }}</h2>
                <p>{{ __tr('See how businesses are transforming their customer relationships with __appName__.', ['__appName__' => $appName]) }}</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                        <blockquote>"{{ __tr('Using __appName__ has transformed our customer engagement strategy. The import/export feature is a game-changer for managing our contacts efficiently.', ['__appName__' => $appName]) }}"</blockquote>
                        <div class="testimonial-author">{{ __tr('Ange Ambé') }}</div>
                        <div class="testimonial-role">{{ __tr('Marketing Manager') }}</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                        <blockquote>"{{ __tr('The automation capabilities of __appName__, especially the bot replies, have significantly reduced our response times and improved customer satisfaction.', ['__appName__' => $appName]) }}"</blockquote>
                        <div class="testimonial-author">{{ __tr('POK Service') }}</div>
                        <div class="testimonial-role">{{ __tr('Customer Service Lead') }}</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                        <blockquote>"{{ __tr('__appName__\'s intuitive design and easy WhatsApp Business integration made it simple for us to start our marketing campaigns quickly.', ['__appName__' => $appName]) }}"</blockquote>
                        <div class="testimonial-author">{{ __tr('Cissé Eddy') }}</div>
                        <div class="testimonial-role">{{ __tr('Digital Marketing Specialist') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ PRICING ══════════════ -->
    <section class="pricing" id="pricing">
        <div class="container px-4 px-lg-5">
            <div class="section-header">
                <div class="section-label">{{ __tr('Tarifs') }}</div>
                <h2>{{ __tr('Simple, Transparent Pricing') }}</h2>
                <p>{{ __tr('Choose the perfect plan to scale your WhatsApp marketing efforts.') }}</p>
            </div>

            <div class="row justify-content-center g-4">
                @php
                    $subscriptionPlans = getPaidPlans();
                    $planStructure = getConfigPaidPlans();
                @endphp

                @if(!empty($subscriptionPlans))
                    @foreach ($subscriptionPlans as $planKey => $savedPlan)
                        @php
                            if (!($savedPlan['enabled'] ?? true)) continue;
                            $planStr = $planStructure[$planKey] ?? ['features' => []];
                        @endphp
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="pricing-card @if($planKey == 'premium' || $loop->iteration == 2) popular @endif h-100">
                                <div class="text-center">
                                    <div class="plan-name">{{ $savedPlan['title'] }}</div>
                                    @if(isset($savedPlan['charges']['monthly']['charge']))
                                        <div class="plan-price">{{ formatAmount($savedPlan['charges']['monthly']['charge'], true, true) }}<span>/mo</span></div>
                                    @endif
                                </div>

                                <ul>
                                    @foreach ($planStr['features'] as $featureKey => $configFeatureValue)
                                        @php
                                            $featureValue = ($savedPlan['features'] ?? [])[$featureKey] ?? null;
                                            if(!$featureValue) continue;
                                        @endphp
                                        <li>
                                            @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                                                @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                    <i class="fa fa-check check"></i>
                                                @else
                                                    <i class="fa fa-times cross"></i>
                                                @endif
                                                <span>{{ ($configFeatureValue['description']) }}</span>
                                            @else
                                                <i class="fa fa-check check"></i>
                                                <span>
                                                    <strong>
                                                    @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                                                        {{ __tr('Unlimited') }}
                                                    @elseif(isset($featureValue['limit']))
                                                        {{ __tr($featureValue['limit']) }}
                                                    @endif
                                                    </strong>
                                                    {{ ($configFeatureValue['description']) }} {{ ($configFeatureValue['limit_duration_title'] ?? '') }}
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>

                                <a href="{{ route('auth.register') }}" class="btn-plan @if($planKey == 'premium' || $loop->iteration == 2) btn-plan-primary @else btn-plan-outline @endif text-decoration-none">
                                    {{ __tr('Commencez') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endif

                <!-- Custom Plan -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card h-100">
                        <div class="text-center">
                            <div class="plan-name">{{ __tr('Personnalisé') }}</div>
                            <div class="plan-price" style="font-size: 2rem;">{{ __tr('Sur mesure') }}</div>
                            <p class="text-muted small mt-2">{{ __tr('Contactez-nous pour une offre adaptée à vos besoins') }}</p>
                        </div>

                        <ul>
                            <li><i class="fa fa-check check"></i> <span>{{ __tr('Toutes les fonctionnalités en illimité') }}</span></li>
                            <li><i class="fa fa-check check"></i> <span>{{ __tr('Support technique prioritaire') }}</span></li>
                            <li><i class="fa fa-check check"></i> <span>{{ __tr('Intégrations avancées sur demande') }}</span></li>
                            <li><i class="fa fa-check check"></i> <span>{{ __tr('Formation et assistance dédiée') }}</span></li>
                        </ul>

                        <a href="https://wa.me/2250100008857?text=Bonjour,%20je%20souhaite%20plus%20d'informations%20sur%20le%20plan%20personnalisé" target="_blank" class="btn-plan btn-plan-outline text-decoration-none">
                            {{ __tr('Contactez-nous') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ FAQ ══════════════ -->
    <section class="faq">
        <div class="container px-4 px-lg-5">
            <div class="section-header">
                <div class="section-label">{{ __tr('FAQ') }}</div>
                <h2>{{ __tr('Frequently Asked Questions') }}</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    {{ __tr('How do I sign up for __appName__?', ['__appName__' => $appName]) }}
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {{ __tr('Signing up for __appName__ is easy and straightforward. Just visit our sign-up page, fill in your details, and follow the instructions to get started.', ['__appName__' => $appName]) }}
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    {{ __tr('Can I import contacts from an existing customer database?') }}
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {{ __tr('Yes, __appName__ supports importing contacts through XLSX files. You can easily upload your existing customer database and start sending messages right away.', ['__appName__' => $appName]) }}
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    {{ __tr('What kind of support does __appName__ offer?', ['__appName__' => $appName]) }}
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {{ __tr('__appName__ offers 24/7 customer support through live chat, email, and phone. Our dedicated team is here to help you with any issues or questions you might have.', ['__appName__' => $appName]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════ CTA FINAL ══════════════ -->
    <section class="cta-final">
        <div class="container px-4 px-lg-5">
            <h2>{{ __tr('Ready to transform your communication?') }}</h2>
            <p>{{ __tr('Join thousands of businesses engaging customers successfully on WhatsApp.') }}</p>
            <a class="btn-cta-white text-decoration-none" href="{{ route('auth.register') }}">{{ __tr('Start Your Free Trial Now') }} <i class="bi bi-arrow-right ms-2"></i></a>
        </div>
    </section>

    <!-- ══════════════ FOOTER ══════════════ -->
    <footer class="site-footer">
        <div class="container px-4 px-lg-5">
            <div class="footer-top">
                <!-- Brand -->
                <div class="footer-brand-col">
                    <img src="{{ getAppSettings('logo_image_url') }}" alt="{{ getAppSettings('name') }}">
                    <p>{{ __tr('Votre plateforme marketing WhatsApp complète pour engager, convertir et fidéliser vos clients.') }}</p>
                </div>
                <!-- Links -->
                <div class="footer-links">
                    <h6>{{ __tr('Produit') }}</h6>
                    <ul>
                        <li><a href="#features">{{ __tr('Features') }}</a></li>
                        <li><a href="#pricing">{{ __tr('Pricing') }}</a></li>
                        <li><a href="#testimonials">{{ __tr('Testimonials') }}</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h6>{{ __tr('Entreprise') }}</h6>
                    <ul>
                        <li><a href="{{ route('user.contact.form') }}">{{ __tr('Contact') }}</a></li>
                        <li><a href="{{ route('auth.login') }}">{{ __tr('Login') }}</a></li>
                        <li><a href="{{ route('auth.register') }}">{{ __tr('Register') }}</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h6>{{ __tr('Connectez-vous') }}</h6>
                    <ul>
                        <li><a href="https://wa.me/2250100008857" target="_blank"><i class="fab fa-whatsapp me-1"></i> WhatsApp</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ getAppSettings('name') }} {{ date('Y') }}. {{ __tr('All Rights Reserved.') }}</p>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            'use strict';
            window.appConfig = {
                debug: "{{ config('app.debug') }}",
                csrf_token: "{{ csrf_token() }}",
                locale : '{{ app()->getLocale() }}',
            }

            // Navbar scroll effect
            const nav = document.getElementById('mainNav');
            if (nav) {
                window.addEventListener('scroll', function() {
                    nav.classList.toggle('scrolled', window.scrollY > 30);
                }, { passive: true });
            }
        })();
    </script>
    {!! __yesset([
        'dist/js/common-vendorlibs.js',
        'dist/js/vendorlibs.js',
        'dist/packages/bootstrap/js/bootstrap.bundle.min.js',
        'dist/js/jsware.js',
    ]) !!}
    {!! getAppSettings('page_footer_code_all') !!}
    @if(isLoggedIn())
        {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
</body>
</html>