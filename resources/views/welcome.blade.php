<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('welcome.meta.title') }}</title>
    @php
        $telegramBotUrl = 'https://t.me/lunexai_bot';
        $loginUrl = Route::has('login') ? route('login') : null;
        $dashboardUrl = Route::has('home') ? route('home') : (Route::has('dashboard') ? route('dashboard') : null);
        $isAuthenticated = auth()->check();
        $primaryUrl = $isAuthenticated ? $dashboardUrl : $telegramBotUrl;
        $primaryLabel = $isAuthenticated ? __('welcome.cta.open_dashboard') : __('welcome.cta.get_started');
        $secondaryUrl = $isAuthenticated ? $loginUrl : $telegramBotUrl;
        $secondaryLabel = $isAuthenticated ? __('welcome.cta.account_access') : __('welcome.cta.create_account');
        $navSectionKeys = ['features', 'preview', 'how_it_works', 'security'];
        $heroMetricKeys = ['clarity', 'velocity', 'coverage'];
        $floatingCards = [
            ['key' => 'budget', 'class' => 'float-card--budget', 'speed' => '-0.22'],
            ['key' => 'ai', 'class' => 'float-card--ai', 'speed' => '0.16'],
            ['key' => 'currencies', 'class' => 'float-card--currencies', 'speed' => '-0.12'],
            ['key' => 'goal', 'class' => 'float-card--goal', 'speed' => '0.24'],
        ];
        $featureKeys = ['expenses', 'savings', 'goals', 'ai', 'budget', 'currencies'];
        $processKeys = ['connect', 'track', 'grow'];
        $securityKeys = ['private', 'access', 'guidance'];
        $balanceColumns = ['48%', '68%', '56%', '84%', '66%', '92%'];
    @endphp
    <style>
        :root {
            --bg: #f3f2ed;
            --panel: rgba(255, 255, 255, 0.7);
            --panel-strong: rgba(255, 255, 255, 0.86);
            --panel-dark: rgba(15, 15, 15, 0.96);
            --line: rgba(12, 12, 12, 0.08);
            --line-strong: rgba(12, 12, 12, 0.16);
            --text: #090909;
            --muted: #656565;
            --muted-strong: #404040;
            --shadow: 0 32px 90px rgba(18, 18, 18, 0.08);
            --shadow-soft: 0 18px 40px rgba(15, 15, 15, 0.08);
            --radius-xl: 34px;
            --radius-lg: 28px;
            --radius-md: 22px;
            --radius-sm: 999px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 10%, rgba(255, 255, 255, 0.94), transparent 26%),
                radial-gradient(circle at 80% 18%, rgba(0, 0, 0, 0.06), transparent 18%),
                radial-gradient(circle at 74% 72%, rgba(255, 255, 255, 0.75), transparent 16%),
                linear-gradient(140deg, #ffffff 0%, #f6f5f0 36%, #efeee8 100%);
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        body::before {
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.035) 1px, transparent 1px);
            background-size: 88px 88px;
            mask-image: radial-gradient(circle at center, black 52%, transparent 92%);
        }

        body::after {
            background:
                radial-gradient(circle at 22% 22%, rgba(255, 255, 255, 0.78), transparent 18%),
                radial-gradient(circle at 88% 10%, rgba(0, 0, 0, 0.07), transparent 14%),
                radial-gradient(circle at 70% 88%, rgba(255, 255, 255, 0.84), transparent 18%);
            opacity: 0.85;
        }

        img {
            display: block;
            max-width: 100%;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page-shell {
            position: relative;
            z-index: 1;
            width: min(1280px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 48px;
        }

        .topbar {
            position: sticky;
            top: 16px;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            padding: 16px 20px;
            border: 1px solid rgba(12, 12, 12, 0.08);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-soft);
        }

        .nav-mark,
        .nav-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            letter-spacing: 0.16em;
            text-transform: lowercase;
        }

        .nav-mark strong,
        .nav-tag strong {
            font-weight: 700;
        }

        .nav-mark::before,
        .nav-tag::before,
        .eyebrow::before,
        .section-eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0f0f0f;
            box-shadow: 0 0 0 8px rgba(0, 0, 0, 0.04);
        }

        .topbar-end {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .section-nav,
        .locale-switcher {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.72);
        }

        .section-link,
        .locale-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 12px;
            border-radius: var(--radius-sm);
            color: var(--muted);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: transform 0.25s ease, background-color 0.25s ease, color 0.25s ease;
        }

        .section-link:hover,
        .locale-link:hover,
        .button:hover,
        .text-link:hover {
            transform: translateY(-2px);
        }

        .section-link:hover,
        .locale-link:hover {
            background: rgba(0, 0, 0, 0.04);
            color: var(--text);
        }

        .locale-label {
            padding-left: 8px;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .locale-link.is-active {
            background: #0a0a0a;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }

        .panel {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            background: linear-gradient(180deg, var(--panel-strong), var(--panel));
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.58), transparent 40%, rgba(0, 0, 0, 0.03));
            pointer-events: none;
        }

        .hero-section {
            display: grid;
            grid-template-columns: minmax(0, 0.94fr) minmax(0, 1.06fr);
            gap: 24px;
            align-items: stretch;
        }

        .hero-copy {
            padding: 34px;
        }

        .eyebrow,
        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.72);
            color: var(--muted);
            font-size: 0.76rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .hero-copy h1,
        .section-heading h2 {
            margin: 18px 0;
            letter-spacing: -0.06em;
            line-height: 0.95;
        }

        .hero-copy h1 {
            max-width: 11ch;
            font-size: clamp(3.1rem, 7vw, 6.5rem);
        }

        .hero-copy p,
        .section-heading p,
        .bottom-copy p,
        .security-intro p {
            margin: 0;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.8;
        }

        .cta-row,
        .hero-links,
        .hero-metrics,
        .feature-grid,
        .preview-grid,
        .process-grid,
        .security-grid {
            display: grid;
            gap: 14px;
        }

        .cta-row {
            grid-template-columns: repeat(2, max-content);
            align-items: center;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 22px;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            font-size: 0.95rem;
            font-weight: 600;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
        }

        .button-primary {
            background: #0a0a0a;
            color: #ffffff;
            box-shadow: 0 18px 30px rgba(0, 0, 0, 0.18);
        }

        .button-secondary {
            background: rgba(255, 255, 255, 0.76);
            border-color: var(--line-strong);
            color: var(--text);
        }

        .text-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            color: var(--muted-strong);
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: transform 0.25s ease, color 0.25s ease;
        }

        .text-link::after {
            content: "↘";
        }

        .hero-links {
            grid-template-columns: repeat(4, max-content);
            margin-top: 22px;
        }

        .hero-mini-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.62);
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: transform 0.25s ease, border-color 0.25s ease, color 0.25s ease;
        }

        .hero-mini-link::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.48);
        }

        .hero-mini-link:hover {
            transform: translateY(-2px);
            border-color: var(--line-strong);
            color: var(--text);
        }

        .hero-metrics {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 24px;
        }

        .metric-card,
        .feature-card,
        .preview-card,
        .process-card,
        .security-card,
        .float-card {
            position: relative;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.72);
            box-shadow: var(--shadow-soft);
        }

        .metric-card {
            padding: 16px 18px;
        }

        .metric-card strong,
        .feature-card strong,
        .preview-card strong,
        .float-card strong,
        .security-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 0.82rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .metric-card span,
        .feature-card p,
        .preview-card p,
        .process-card p,
        .security-card p,
        .float-card p {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .hero-stage {
            padding: 24px;
            min-height: 700px;
        }

        .hero-stage-inner {
            position: relative;
            min-height: 100%;
            border-radius: 30px;
            border: 1px solid rgba(12, 12, 12, 0.07);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.34), rgba(255, 255, 255, 0.18)),
                radial-gradient(circle at top center, rgba(255, 255, 255, 0.84), rgba(255, 255, 255, 0.18) 46%, rgba(0, 0, 0, 0.04));
            overflow: hidden;
            isolation: isolate;
        }

        .hero-grid-overlay,
        .hero-glow,
        [data-parallax] {
            transform: translate3d(0, var(--parallax-offset, 0px), 0);
            will-change: transform;
        }

        .hero-grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.045) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.62), transparent 82%);
            opacity: 0.82;
        }

        .hero-glow {
            position: absolute;
            inset: 10% 18% auto;
            height: 42%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.2) 54%, transparent 72%);
            filter: blur(8px);
            opacity: 0.95;
        }

        .hero-centerpiece {
            position: absolute;
            inset: 18% 18% auto;
            display: grid;
            place-items: center;
            padding: 24px;
            border: 1px solid rgba(12, 12, 12, 0.08);
            border-radius: 34px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.08);
        }

        .hero-centerpiece::before,
        .hero-centerpiece::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .hero-centerpiece::before {
            width: 280px;
            height: 280px;
            animation: pulse 10s infinite ease-in-out;
        }

        .hero-centerpiece::after {
            width: 380px;
            height: 380px;
            animation: pulse 12s infinite ease-in-out reverse;
        }

        .centerpiece-label,
        .preview-eyebrow,
        .process-step,
        .security-badge,
        .float-card small {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .centerpiece-label::before,
        .security-badge::before,
        .float-card small::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0a0a0a;
        }

        .hero-centerpiece img {
            position: relative;
            z-index: 1;
            width: min(72%, 280px);
            filter: drop-shadow(0 18px 26px rgba(0, 0, 0, 0.12));
        }

        .centerpiece-note {
            position: relative;
            z-index: 1;
            max-width: 16rem;
            margin: 16px auto 0;
            color: var(--muted);
            text-align: center;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .float-card {
            position: absolute;
            width: min(230px, calc(100% - 36px));
            padding: 16px 16px 18px;
            animation: floatCard 7s ease-in-out infinite;
        }

        .float-card strong {
            margin-bottom: 6px;
            font-size: 1rem;
            letter-spacing: -0.03em;
            text-transform: none;
        }

        .float-card--budget {
            top: 10%;
            left: 4%;
        }

        .float-card--ai {
            top: 13%;
            right: 5%;
            animation-duration: 8s;
        }

        .float-card--currencies {
            bottom: 14%;
            left: 6%;
            animation-duration: 7.5s;
        }

        .float-card--goal {
            bottom: 10%;
            right: 6%;
            animation-duration: 8.6s;
        }

        .content-stack {
            display: grid;
            gap: 24px;
            margin-top: 24px;
        }

        .section-block {
            padding: 30px;
        }

        .section-heading {
            max-width: 42rem;
            margin-bottom: 22px;
        }

        .section-heading h2 {
            font-size: clamp(2.3rem, 4vw, 4.1rem);
        }

        .feature-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .feature-card {
            padding: 22px;
            transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
        }

        .feature-card:hover,
        .preview-card:hover,
        .process-card:hover,
        .security-card:hover {
            transform: translateY(-4px);
            border-color: var(--line-strong);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.08);
        }

        .feature-tag {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: var(--radius-sm);
            background: rgba(0, 0, 0, 0.05);
            color: var(--muted-strong);
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .feature-card h3,
        .preview-balance h3,
        .preview-card h3,
        .process-card h3,
        .security-card h3,
        .security-intro h3,
        .bottom-copy h3 {
            margin: 16px 0 10px;
            font-size: 1.35rem;
            letter-spacing: -0.04em;
        }

        .preview-grid {
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            align-items: start;
        }

        .preview-balance {
            padding: 26px;
            min-height: 100%;
        }

        .preview-balance-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .preview-balance-value {
            margin: 12px 0 10px;
            font-size: clamp(3rem, 6vw, 5rem);
            line-height: 0.95;
            letter-spacing: -0.07em;
        }

        .preview-delta {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: var(--radius-sm);
            background: rgba(0, 0, 0, 0.05);
            color: var(--muted-strong);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .balance-chart {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            align-items: end;
            gap: 12px;
            min-height: 220px;
            margin-top: 24px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.7), rgba(247, 247, 242, 0.92)),
                linear-gradient(90deg, rgba(0, 0, 0, 0.03), transparent);
        }

        .chart-column {
            height: 100%;
            display: flex;
            align-items: flex-end;
        }

        .chart-column span {
            display: block;
            width: 100%;
            min-height: 24px;
            border-radius: 999px 999px 16px 16px;
            background: linear-gradient(180deg, #161616 0%, #505050 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .preview-side {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .preview-card {
            padding: 20px;
        }

        .preview-card--full {
            grid-column: 1 / -1;
        }

        .preview-card strong {
            margin-bottom: 12px;
        }

        .preview-big-value {
            margin: 0 0 10px;
            font-size: 2rem;
            letter-spacing: -0.05em;
        }

        .progress-shell {
            margin-top: 14px;
        }

        .progress-bar {
            height: 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .progress-bar span {
            display: block;
            height: 100%;
            width: 79%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0c0c0c, #666666);
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .process-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .process-card,
        .security-card {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.72);
            box-shadow: var(--shadow-soft);
            transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
        }

        .process-step {
            margin-bottom: 8px;
            color: var(--muted-strong);
            font-weight: 700;
        }

        .security-layout {
            display: grid;
            grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
            gap: 18px;
        }

        .security-intro {
            padding: 26px;
            border-radius: var(--radius-lg);
            background: var(--panel-dark);
            color: #ffffff;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.16);
        }

        .security-intro p {
            color: rgba(255, 255, 255, 0.74);
        }

        .security-badge {
            color: rgba(255, 255, 255, 0.64);
        }

        .security-badge::before {
            background: #ffffff;
        }

        .security-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .bottom-brand {
            padding: 30px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 18px;
            align-items: center;
        }

        .bottom-copy h3 {
            font-size: clamp(2rem, 4vw, 3.2rem);
        }

        .bottom-logo-wrap {
            position: relative;
            min-height: 240px;
            display: grid;
            place-items: center;
            border-radius: 30px;
            border: 1px solid var(--line);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(244, 244, 239, 0.84)),
                linear-gradient(90deg, rgba(0, 0, 0, 0.04), transparent);
        }

        .bottom-logo-wrap::before {
            content: "";
            position: absolute;
            inset: 28px;
            border: 1px dashed rgba(0, 0, 0, 0.12);
            border-radius: 24px;
        }

        .bottom-logo-wrap img {
            position: relative;
            z-index: 1;
            width: min(72%, 440px);
        }

        .page-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            padding: 0 4px;
            color: var(--muted);
            font-size: 0.86rem;
        }

        [data-reveal] {
            opacity: 0;
            transform: translate3d(0, 32px, 0);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        [data-reveal].is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(0.94);
                opacity: 0.42;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.76;
            }
        }

        @keyframes floatCard {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(0, -10px, 0);
            }
        }

        @media (max-width: 1120px) {
            .hero-section,
            .preview-grid,
            .security-layout,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .preview-side,
            .security-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero-copy h1 {
                max-width: none;
            }

            .hero-stage {
                min-height: 760px;
            }
        }

        @media (max-width: 860px) {
            .feature-grid,
            .process-grid,
            .hero-metrics,
            .security-grid,
            .preview-side {
                grid-template-columns: 1fr;
            }

            .hero-links {
                grid-template-columns: repeat(2, max-content);
            }

            .cta-row {
                grid-template-columns: 1fr;
            }

            .button {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .page-shell {
                width: min(100% - 20px, 1280px);
                padding-top: 12px;
                padding-bottom: 28px;
            }

            .topbar {
                position: static;
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-end {
                width: 100%;
                align-items: flex-start;
                justify-content: flex-start;
            }

            .section-nav,
            .locale-switcher {
                flex-wrap: wrap;
            }

            .hero-copy,
            .hero-stage,
            .section-block,
            .bottom-brand {
                padding: 22px 18px;
            }

            .hero-section,
            .content-stack {
                gap: 18px;
            }

            .hero-copy h1 {
                font-size: clamp(2.7rem, 14vw, 4.2rem);
            }

            .hero-stage {
                min-height: 680px;
            }

            .hero-centerpiece {
                inset: 20% 10% auto;
            }

            .float-card {
                width: min(210px, calc(100% - 24px));
            }

            .preview-balance-top,
            .page-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            [data-reveal] {
                opacity: 1;
                transform: none;
            }

            [data-parallax] {
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <a href="{{ route('welcome') }}" class="nav-mark" aria-label="{{ __('welcome.meta.home_aria') }}">
                <strong>{{ __('welcome.nav.brand') }}</strong>
            </a>

            <div class="topbar-end">
                <div class="nav-tag">
                    <strong>{{ __('welcome.nav.grow') }}</strong>
                </div>

                <nav class="section-nav" aria-label="{{ __('welcome.nav.aria') }}">
                    @foreach ($navSectionKeys as $navSectionKey)
                        <a href="#{{ str_replace('_', '-', $navSectionKey) }}" class="section-link">
                            {{ __('welcome.nav.sections.' . $navSectionKey) }}
                        </a>
                    @endforeach
                </nav>

                <nav class="locale-switcher" aria-label="{{ __('welcome.switcher.aria') }}">
                    <span class="locale-label">{{ __('welcome.switcher.label') }}</span>
                    @foreach ($welcomeLocales as $localeOption)
                        <a
                            href="{{ route('welcome.language', ['locale' => $localeOption]) }}"
                            class="locale-link{{ $welcomeLocale === $localeOption ? ' is-active' : '' }}"
                        >
                            {{ __('welcome.switcher.locales.' . $localeOption) }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main>
            <section class="hero-section">
                <section class="panel hero-copy" data-reveal aria-labelledby="hero-title">
                    <span class="eyebrow">{{ __('welcome.hero.eyebrow') }}</span>
                    <h1 id="hero-title">{{ __('welcome.hero.title') }}</h1>
                    <p>{{ __('welcome.hero.description') }}</p>

                    <div class="cta-row">
                        @if ($primaryUrl)
                            <a
                                href="{{ $primaryUrl }}"
                                class="button button-primary"
                                @if (! $isAuthenticated) target="_blank" rel="noopener noreferrer" @endif
                            >{{ $primaryLabel }}</a>
                        @endif

                        @if ($secondaryUrl)
                            <a
                                href="{{ $secondaryUrl }}"
                                class="button button-secondary"
                                @if (! $isAuthenticated) target="_blank" rel="noopener noreferrer" @endif
                            >{{ $secondaryLabel }}</a>
                        @endif
                    </div>

                    <a href="#preview" class="text-link">{{ __('welcome.hero.ghost_link') }}</a>

                    <div class="hero-links">
                        @foreach ($navSectionKeys as $navSectionKey)
                            <a href="#{{ str_replace('_', '-', $navSectionKey) }}" class="hero-mini-link">
                                {{ __('welcome.nav.sections.' . $navSectionKey) }}
                            </a>
                        @endforeach
                    </div>

                    <div class="hero-metrics" aria-label="{{ __('welcome.meta.features_aria') }}">
                        @foreach ($heroMetricKeys as $heroMetricKey)
                            <article class="metric-card">
                                <strong>{{ __('welcome.hero.metrics.' . $heroMetricKey . '.title') }}</strong>
                                <span>{{ __('welcome.hero.metrics.' . $heroMetricKey . '.description') }}</span>
                            </article>
                        @endforeach
                    </div>
                </section>

                <aside class="panel hero-stage" data-reveal aria-label="{{ __('welcome.meta.visual_aria') }}">
                    <div class="hero-stage-inner">
                        <div class="hero-grid-overlay" data-parallax data-parallax-speed="0.08"></div>
                        <div class="hero-glow" data-parallax data-parallax-speed="-0.12"></div>

                        <div class="hero-centerpiece" data-parallax data-parallax-speed="0.03">
                            <span class="centerpiece-label">{{ __('welcome.visual.core_label') }}</span>
                            <img src="{{ asset('assets/logo/lunex.svg') }}" alt="{{ __('welcome.visual.logo_alt') }}">
                            <p class="centerpiece-note">{{ __('welcome.visual.core_note') }}</p>
                        </div>

                        @foreach ($floatingCards as $floatingCard)
                            <article
                                class="float-card {{ $floatingCard['class'] }}"
                                data-parallax
                                data-parallax-speed="{{ $floatingCard['speed'] }}"
                            >
                                <small>{{ __('welcome.hero.floating.' . $floatingCard['key'] . '.eyebrow') }}</small>
                                <strong>{{ __('welcome.hero.floating.' . $floatingCard['key'] . '.title') }}</strong>
                                <p>{{ __('welcome.hero.floating.' . $floatingCard['key'] . '.description') }}</p>
                            </article>
                        @endforeach
                    </div>
                </aside>
            </section>

            <div class="content-stack">
                <section id="features" class="panel section-block" data-reveal>
                    <div class="section-heading">
                        <span class="section-eyebrow">{{ __('welcome.sections.features.eyebrow') }}</span>
                        <h2>{{ __('welcome.sections.features.title') }}</h2>
                        <p>{{ __('welcome.sections.features.description') }}</p>
                    </div>

                    <div class="feature-grid">
                        @foreach ($featureKeys as $featureKey)
                            <article class="feature-card">
                                <span class="feature-tag">{{ __('welcome.feature_grid.items.' . $featureKey . '.tag') }}</span>
                                <h3>{{ __('welcome.feature_grid.items.' . $featureKey . '.title') }}</h3>
                                <p>{{ __('welcome.feature_grid.items.' . $featureKey . '.description') }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="preview" class="panel section-block" data-reveal aria-label="{{ __('welcome.meta.preview_aria') }}">
                    <div class="section-heading">
                        <span class="section-eyebrow">{{ __('welcome.sections.preview.eyebrow') }}</span>
                        <h2>{{ __('welcome.sections.preview.title') }}</h2>
                        <p>{{ __('welcome.sections.preview.description') }}</p>
                    </div>

                    <div class="preview-grid">
                        <article class="preview-balance preview-card">
                            <div class="preview-balance-top">
                                <div>
                                    <span class="preview-eyebrow">{{ __('welcome.dashboard.balance.eyebrow') }}</span>
                                    <h3>{{ __('welcome.dashboard.balance.title') }}</h3>
                                </div>
                                <span class="preview-delta">{{ __('welcome.dashboard.balance.delta') }}</span>
                            </div>

                            <div class="preview-balance-value">{{ __('welcome.dashboard.balance.value') }}</div>
                            <p>{{ __('welcome.dashboard.balance.description') }}</p>

                            <div class="balance-chart" aria-hidden="true">
                                @foreach ($balanceColumns as $balanceColumn)
                                    <div class="chart-column">
                                        <span style="height: {{ $balanceColumn }};"></span>
                                    </div>
                                @endforeach
                            </div>
                        </article>

                        <div class="preview-side">
                            <article class="preview-card">
                                <strong>{{ __('welcome.dashboard.expenses.eyebrow') }}</strong>
                                <h3>{{ __('welcome.dashboard.expenses.title') }}</h3>
                                <div class="preview-big-value">{{ __('welcome.dashboard.expenses.value') }}</div>
                                <p>{{ __('welcome.dashboard.expenses.description') }}</p>
                            </article>

                            <article class="preview-card">
                                <strong>{{ __('welcome.dashboard.savings.eyebrow') }}</strong>
                                <h3>{{ __('welcome.dashboard.savings.title') }}</h3>
                                <div class="preview-big-value">{{ __('welcome.dashboard.savings.value') }}</div>
                                <p>{{ __('welcome.dashboard.savings.description') }}</p>
                            </article>

                            <article class="preview-card">
                                <strong>{{ __('welcome.dashboard.goal.eyebrow') }}</strong>
                                <h3>{{ __('welcome.dashboard.goal.title') }}</h3>
                                <div class="preview-big-value">{{ __('welcome.dashboard.goal.value') }}</div>
                                <p>{{ __('welcome.dashboard.goal.description') }}</p>
                                <div class="progress-shell">
                                    <div class="progress-bar"><span></span></div>
                                    <div class="progress-meta">
                                        <span>{{ __('welcome.dashboard.goal.progress_label') }}</span>
                                        <span>{{ __('welcome.dashboard.goal.progress_value') }}</span>
                                    </div>
                                </div>
                            </article>

                            <article class="preview-card">
                                <strong>{{ __('welcome.dashboard.currencies.eyebrow') }}</strong>
                                <h3>{{ __('welcome.dashboard.currencies.title') }}</h3>
                                <div class="preview-big-value">{{ __('welcome.dashboard.currencies.value') }}</div>
                                <p>{{ __('welcome.dashboard.currencies.description') }}</p>
                            </article>

                            <article class="preview-card preview-card--full">
                                <strong>{{ __('welcome.dashboard.ai.eyebrow') }}</strong>
                                <h3>{{ __('welcome.dashboard.ai.title') }}</h3>
                                <p>{{ __('welcome.dashboard.ai.description') }}</p>
                                <span class="feature-tag">{{ __('welcome.dashboard.ai.chip') }}</span>
                            </article>
                        </div>
                    </div>
                </section>

                <section id="how-it-works" class="panel section-block" data-reveal aria-label="{{ __('welcome.meta.process_aria') }}">
                    <div class="section-heading">
                        <span class="section-eyebrow">{{ __('welcome.sections.process.eyebrow') }}</span>
                        <h2>{{ __('welcome.sections.process.title') }}</h2>
                        <p>{{ __('welcome.sections.process.description') }}</p>
                    </div>

                    <div class="process-grid">
                        @foreach ($processKeys as $processKey)
                            <article class="process-card">
                                <span class="process-step">{{ __('welcome.process.items.' . $processKey . '.step') }}</span>
                                <h3>{{ __('welcome.process.items.' . $processKey . '.title') }}</h3>
                                <p>{{ __('welcome.process.items.' . $processKey . '.description') }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="security" class="panel section-block" data-reveal aria-label="{{ __('welcome.meta.security_aria') }}">
                    <div class="section-heading">
                        <span class="section-eyebrow">{{ __('welcome.sections.security.eyebrow') }}</span>
                        <h2>{{ __('welcome.sections.security.title') }}</h2>
                        <p>{{ __('welcome.sections.security.description') }}</p>
                    </div>

                    <div class="security-layout">
                        <div class="security-intro">
                            <span class="security-badge">{{ __('welcome.security.intro.eyebrow') }}</span>
                            <h3>{{ __('welcome.security.intro.title') }}</h3>
                            <p>{{ __('welcome.security.intro.description') }}</p>
                        </div>

                        <div class="security-grid">
                            @foreach ($securityKeys as $securityKey)
                                <article class="security-card">
                                    <strong>{{ __('welcome.security.cards.' . $securityKey . '.eyebrow') }}</strong>
                                    <h3>{{ __('welcome.security.cards.' . $securityKey . '.title') }}</h3>
                                    <p>{{ __('welcome.security.cards.' . $securityKey . '.description') }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="panel bottom-brand" data-reveal aria-labelledby="brand-future">
                    <div class="bottom-grid">
                        <div class="bottom-copy">
                            <span class="section-eyebrow">{{ __('welcome.bottom.eyebrow') }}</span>
                            <h3 id="brand-future">{{ __('welcome.bottom.title') }}</h3>
                            <p>{{ __('welcome.bottom.description') }}</p>
                        </div>

                        <div class="bottom-logo-wrap">
                            <img src="{{ asset('assets/logo/lunex.svg') }}" alt="{{ __('welcome.bottom.logo_alt') }}">
                        </div>
                    </div>
                </section>
            </div>

            <footer class="page-footer">
                <span><strong>{{ __('welcome.footer.brand') }}</strong> {{ __('welcome.footer.tagline') }}</span>
                <span>{{ __('welcome.footer.laravel') }} v{{ Illuminate\Foundation\Application::VERSION }} / {{ __('welcome.footer.php') }} v{{ PHP_VERSION }}</span>
            </footer>
        </main>
    </div>

    <script>
        (() => {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const revealItems = Array.from(document.querySelectorAll('[data-reveal]'));
            const parallaxItems = Array.from(document.querySelectorAll('[data-parallax]'));

            const showAll = () => {
                revealItems.forEach((item) => item.classList.add('is-visible'));
                parallaxItems.forEach((item) => item.style.setProperty('--parallax-offset', '0px'));
            };

            if (reduceMotion.matches) {
                showAll();
                return;
            }

            if ('IntersectionObserver' in window) {
                const revealObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (! entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, {
                    threshold: 0.18,
                    rootMargin: '0px 0px -8% 0px',
                });

                revealItems.forEach((item) => revealObserver.observe(item));
            } else {
                showAll();
            }

            if (! parallaxItems.length) {
                return;
            }

            let rafId = null;

            const updateParallax = () => {
                const viewportHeight = window.innerHeight || 1;

                parallaxItems.forEach((item) => {
                    const rect = item.getBoundingClientRect();
                    const speed = Number.parseFloat(item.dataset.parallaxSpeed || '0');
                    const offset = (rect.top + (rect.height / 2) - (viewportHeight / 2)) * speed * -0.12;

                    item.style.setProperty('--parallax-offset', `${offset.toFixed(2)}px`);
                });

                rafId = null;
            };

            const requestParallaxFrame = () => {
                if (rafId !== null) {
                    return;
                }

                rafId = window.requestAnimationFrame(updateParallax);
            };

            updateParallax();
            window.addEventListener('scroll', requestParallaxFrame, { passive: true });
            window.addEventListener('resize', requestParallaxFrame);
        })();
    </script>
</body>
</html>
