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
    @endphp
    <style>
        :root {
            --bg: #f4f4f1;
            --bg-soft: rgba(255, 255, 255, 0.7);
            --panel: rgba(255, 255, 255, 0.76);
            --panel-strong: rgba(255, 255, 255, 0.92);
            --line: rgba(12, 12, 12, 0.08);
            --line-strong: rgba(12, 12, 12, 0.16);
            --text: #080808;
            --muted: #676767;
            --accent: #111111;
            --shadow: 0 30px 90px rgba(15, 15, 15, 0.10);
            --radius-xl: 32px;
            --radius-lg: 24px;
            --radius-md: 18px;
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
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), transparent 34%),
                radial-gradient(circle at right 20%, rgba(0, 0, 0, 0.05), transparent 28%),
                linear-gradient(135deg, #ffffff 0%, #f7f7f2 40%, #efefea 100%);
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(circle at center, black 45%, transparent 90%);
        }

        body::after {
            background:
                radial-gradient(circle at 20% 18%, rgba(255, 255, 255, 0.95), transparent 20%),
                radial-gradient(circle at 82% 24%, rgba(0, 0, 0, 0.10), transparent 16%),
                radial-gradient(circle at 72% 68%, rgba(255, 255, 255, 0.92), transparent 18%);
            opacity: 0.8;
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
            width: min(1240px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 48px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
            padding: 18px 22px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.66);
            backdrop-filter: blur(18px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.05);
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

        .topbar-end {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .nav-mark::before,
        .nav-tag::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 8px rgba(0, 0, 0, 0.05);
        }

        .locale-switcher {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.7);
        }

        .locale-label {
            padding-left: 8px;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .locale-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            min-height: 36px;
            padding: 0 12px;
            border-radius: var(--radius-sm);
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .locale-link:hover {
            transform: translateY(-1px);
            background: rgba(0, 0, 0, 0.04);
            color: var(--text);
        }

        .locale-link.is-active {
            background: #0a0a0a;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.14);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 24px;
            align-items: stretch;
        }

        .hero-copy,
        .hero-visual,
        .bottom-brand {
            position: relative;
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            background: linear-gradient(180deg, var(--panel-strong), var(--panel));
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero-copy {
            padding: 34px;
        }

        .hero-copy::before,
        .hero-visual::before,
        .bottom-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.65), transparent 42%, rgba(0, 0, 0, 0.03));
            pointer-events: none;
        }

        .eyebrow {
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

        .eyebrow::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #000;
        }

        .hero-copy h1 {
            margin: 22px 0 18px;
            max-width: 12ch;
            font-size: clamp(3rem, 7vw, 6.2rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .hero-copy p {
            max-width: 34rem;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
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
            font-size: 0.96rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            background: #0a0a0a;
            color: #ffffff;
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.18);
        }

        .button-secondary {
            border-color: var(--line-strong);
            background: rgba(255, 255, 255, 0.72);
            color: var(--text);
        }

        .button-ghost {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 26px;
            color: var(--muted);
            font-size: 0.92rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .button-ghost::after {
            content: "↗";
            font-size: 1rem;
            color: var(--text);
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 34px;
        }

        .meta-card {
            padding: 16px 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.68);
        }

        .meta-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 0.82rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .meta-card span {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .hero-visual {
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
        }

        .visual-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 26px;
        }

        .visual-title {
            font-size: 0.8rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .signal {
            display: inline-flex;
            gap: 6px;
        }

        .signal span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.12);
        }

        .signal span:first-child {
            background: #0f0f0f;
            box-shadow: 0 0 24px rgba(0, 0, 0, 0.24);
        }

        .brand-orb {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 290px;
            margin-bottom: 22px;
            border-radius: 28px;
            border: 1px solid var(--line);
            background:
                radial-gradient(circle at center, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.5) 44%, rgba(0, 0, 0, 0.05) 100%),
                linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(244, 244, 239, 0.8));
            overflow: hidden;
        }

        .brand-orb::before,
        .brand-orb::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .brand-orb::before {
            width: 260px;
            height: 260px;
            animation: pulse 8s infinite ease-in-out;
        }

        .brand-orb::after {
            width: 360px;
            height: 360px;
            animation: pulse 11s infinite ease-in-out reverse;
        }

        .brand-orb img {
            position: relative;
            z-index: 1;
            width: min(72%, 280px);
            filter: drop-shadow(0 16px 26px rgba(0, 0, 0, 0.1));
        }

        .panel-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .stat-card {
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.72);
        }

        .stat-card small,
        .mini-label {
            display: block;
            margin-bottom: 10px;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.4rem;
            letter-spacing: -0.04em;
        }

        .stat-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .tracker {
            grid-column: 1 / -1;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.78), rgba(247, 247, 243, 0.92));
        }

        .tracker-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .tracker-value {
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
            letter-spacing: -0.06em;
        }

        .tracker-bars {
            display: grid;
            gap: 12px;
        }

        .bar {
            display: grid;
            gap: 8px;
        }

        .bar-line {
            position: relative;
            height: 8px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .bar-line span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0e0e0e, #5d5d5d);
        }

        .bar-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .bottom-brand {
            margin-top: 24px;
            padding: 28px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 18px;
            align-items: center;
        }

        .bottom-copy strong {
            display: inline-block;
            margin-bottom: 12px;
            font-size: 0.8rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .bottom-copy p {
            margin: 0;
            max-width: 36rem;
            color: var(--muted);
            line-height: 1.75;
        }

        .bottom-logo-wrap {
            position: relative;
            min-height: 180px;
            display: grid;
            place-items: center;
            border-radius: 28px;
            border: 1px solid var(--line);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(245, 245, 240, 0.8)),
                linear-gradient(90deg, rgba(0, 0, 0, 0.04), transparent);
        }

        .bottom-logo-wrap::before {
            content: "";
            position: absolute;
            inset: 24px;
            border: 1px dashed rgba(0, 0, 0, 0.12);
            border-radius: 20px;
        }

        .bottom-logo-wrap img {
            position: relative;
            z-index: 1;
            width: min(72%, 420px);
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

        .page-footer strong {
            color: var(--text);
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(0.94);
                opacity: 0.44;
            }
            50% {
                transform: scale(1.04);
                opacity: 0.78;
            }
        }

        @media (max-width: 1024px) {
            .hero,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .hero-copy h1 {
                max-width: none;
            }

            .bottom-logo-wrap {
                min-height: 160px;
            }
        }

        @media (max-width: 720px) {
            .page-shell {
                width: min(100% - 20px, 1240px);
                padding-top: 10px;
                padding-bottom: 26px;
            }

            .topbar,
            .hero-copy,
            .hero-visual,
            .bottom-brand {
                border-radius: 24px;
            }

            .topbar,
            .hero-copy,
            .hero-visual,
            .bottom-brand {
                padding-left: 18px;
                padding-right: 18px;
            }

            .topbar {
                padding-top: 16px;
                padding-bottom: 16px;
            }

            .hero-copy {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .hero-copy h1 {
                font-size: clamp(2.6rem, 14vw, 4rem);
            }

            .hero-meta,
            .panel-grid {
                grid-template-columns: 1fr;
            }

            .page-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar-end {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
            }

            .locale-switcher {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <a href="{{ route('welcome') }}" class="nav-mark" aria-label="{{ __('welcome.meta.home_aria') }}">
                <strong>#lunex</strong>
            </a>
            <div class="topbar-end">
                <div class="nav-tag">
                    <strong>{{ __('welcome.nav.grow') }}</strong>
                </div>
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

        <main class="hero">
            <section class="hero-copy" aria-labelledby="hero-title">
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

                @if ($loginUrl || $dashboardUrl)
                    <a href="{{ $dashboardUrl ?? $loginUrl }}" class="button-ghost">
                        {{ __('welcome.hero.ghost_link') }}
                    </a>
                @endif

                <div class="hero-meta" aria-label="{{ __('welcome.meta.features_aria') }}">
                    <article class="meta-card">
                        <strong>{{ __('welcome.features.expenses.title') }}</strong>
                        <span>{{ __('welcome.features.expenses.description') }}</span>
                    </article>
                    <article class="meta-card">
                        <strong>{{ __('welcome.features.savings.title') }}</strong>
                        <span>{{ __('welcome.features.savings.description') }}</span>
                    </article>
                    <article class="meta-card">
                        <strong>{{ __('welcome.features.clarity.title') }}</strong>
                        <span>{{ __('welcome.features.clarity.description') }}</span>
                    </article>
                </div>
            </section>

            <aside class="hero-visual" aria-label="{{ __('welcome.meta.visual_aria') }}">
                <div class="visual-head">
                    <span class="visual-title">{{ __('welcome.visual.title') }}</span>
                    <div class="signal" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="brand-orb">
                    <img src="{{ asset('assets/logo/lunex.svg') }}" alt="{{ __('welcome.visual.logo_alt') }}">
                </div>

                <div class="panel-grid">
                    <article class="stat-card">
                        <small>{{ __('welcome.visual.expenses.label') }}</small>
                        <strong>{{ __('welcome.visual.expenses.value') }}</strong>
                        <p>{{ __('welcome.visual.expenses.description') }}</p>
                    </article>
                    <article class="stat-card">
                        <small>{{ __('welcome.visual.savings.label') }}</small>
                        <strong>{{ __('welcome.visual.savings.value') }}</strong>
                        <p>{{ __('welcome.visual.savings.description') }}</p>
                    </article>
                    <article class="stat-card">
                        <small>{{ __('welcome.visual.goals.label') }}</small>
                        <strong>{{ __('welcome.visual.goals.value') }}</strong>
                        <p>{{ __('welcome.visual.goals.description') }}</p>
                    </article>
                    <article class="stat-card">
                        <small>{{ __('welcome.visual.ai.label') }}</small>
                        <strong>{{ __('welcome.visual.ai.value') }}</strong>
                        <p>{{ __('welcome.visual.ai.description') }}</p>
                    </article>

                    <section class="tracker" aria-labelledby="tracker-title">
                        <div class="tracker-head">
                            <div>
                                <span class="mini-label" id="tracker-title">{{ __('welcome.visual.tracker.title') }}</span>
                                <div class="tracker-value">{{ __('welcome.visual.tracker.value') }}</div>
                            </div>
                            <span class="mini-label">{{ __('welcome.visual.tracker.subtitle') }}</span>
                        </div>

                        <div class="tracker-bars">
                            <div class="bar">
                                <div class="bar-meta">
                                    <span>{{ __('welcome.visual.tracker.reserve.label') }}</span>
                                    <span>{{ __('welcome.visual.tracker.reserve.value') }}</span>
                                </div>
                                <div class="bar-line"><span style="width: 82%;"></span></div>
                            </div>
                            <div class="bar">
                                <div class="bar-meta">
                                    <span>{{ __('welcome.visual.tracker.investment.label') }}</span>
                                    <span>{{ __('welcome.visual.tracker.investment.value') }}</span>
                                </div>
                                <div class="bar-line"><span style="width: 67%;"></span></div>
                            </div>
                            <div class="bar">
                                <div class="bar-meta">
                                    <span>{{ __('welcome.visual.tracker.completion.label') }}</span>
                                    <span>{{ __('welcome.visual.tracker.completion.value') }}</span>
                                </div>
                                <div class="bar-line"><span style="width: 79%;"></span></div>
                            </div>
                        </div>
                    </section>
                </div>
            </aside>
        </main>

        <section class="bottom-brand" aria-labelledby="brand-future">
            <div class="bottom-grid">
                <div class="bottom-copy">
                    <strong id="brand-future">{{ __('welcome.bottom.title') }}</strong>
                    <p>{{ __('welcome.bottom.description') }}</p>
                </div>

                <div class="bottom-logo-wrap">
                    <img src="{{ asset('assets/logo/lunex.svg') }}" alt="{{ __('welcome.bottom.logo_alt') }}">
                </div>
            </div>
        </section>

        <footer class="page-footer">
            <span><strong>Lunex</strong> {{ __('welcome.footer.tagline') }}</span>
            <span>{{ __('welcome.footer.laravel') }} v{{ Illuminate\Foundation\Application::VERSION }} / {{ __('welcome.footer.php') }} v{{ PHP_VERSION }}</span>
        </footer>
    </div>
</body>
</html>
