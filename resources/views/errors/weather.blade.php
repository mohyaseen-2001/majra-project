<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-en="{{ $enTitle }} | Majra">{{ $arTitle }} | مجرى</title>
    @include('layouts.appearance')
    <script>
        try {
            if (localStorage.getItem('majra-language') === 'en') {
                document.documentElement.lang = 'en';
                document.documentElement.dir = 'ltr';
                document.title = document.querySelector('title').dataset.en;
            }
        } catch {}
    </script>
    <style>
        :root {
            color-scheme: dark;
            --sky: #0b1726;
            --horizon: #1a3450;
            --text: #f0f7ff;
            --muted: #b8cadf;
            --rain: #7bc9f8;
            --button: #a9dcfa;
            --button-ink: #132940;
        }
        :root[data-theme="light"] {
            color-scheme: light;
            --sky: #f4f9fe;
            --horizon: #cee3f4;
            --text: #142c43;
            --muted: #405a72;
            --rain: #2573a4;
            --button: #164e75;
            --button-ink: #fff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 2rem 1.25rem;
            background: radial-gradient(ellipse at 50% 18%, var(--horizon), transparent 72%), var(--sky);
            color: var(--text);
            font: 1rem/1.65 system-ui, sans-serif;
        }
        main { width: min(100%, 29rem); text-align: center; }
        [lang="en"] .ar, :root:not([lang="en"]) .en { display: none; }
        .weather {
            position: relative;
            width: 11rem;
            height: 9.25rem;
            margin-inline: auto;
        }
        .cloud {
            position: absolute;
            z-index: 1;
            inset: 3.1rem .4rem auto;
            height: 3.5rem;
            border-radius: 3rem;
            background: #acbfd2;
            box-shadow: 0 .6rem 1.5rem #0a243a35, inset 0 -.45rem #899fb63d;
        }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
        }
        .cloud::before { width: 4.65rem; height: 4.65rem; top: -2.3rem; left: 1.65rem; }
        .cloud::after { width: 3.9rem; height: 3.9rem; top: -1.55rem; right: 1.1rem; }
        .eye, .mouth { position: absolute; z-index: 2; }
        .eye { top: 1rem; width: .9rem; height: .4rem; border-bottom: .18rem solid #273f58; border-radius: 50%; }
        .eye:first-child { left: 3.2rem; transform: rotate(-14deg); }
        .eye:nth-child(2) { right: 3.2rem; transform: rotate(14deg); }
        .mouth { top: 1.9rem; left: calc(50% - .5rem); width: 1rem; height: .55rem; border-top: .16rem solid #273f58; border-radius: 50%; }
        .drop {
            position: absolute;
            top: 6.85rem;
            width: .17rem;
            height: .8rem;
            border-radius: 1rem;
            background: var(--rain);
            opacity: .75;
            transform: rotate(16deg);
            animation: rain 1.25s linear infinite;
        }
        .drop:nth-of-type(1) { left: 21%; animation-delay: -.7s; }
        .drop:nth-of-type(2) { left: 34%; animation-delay: -.25s; }
        .drop:nth-of-type(3) { left: 48%; animation-delay: -1.1s; }
        .drop:nth-of-type(4) { left: 62%; animation-delay: -.45s; }
        .drop:nth-of-type(5) { left: 76%; animation-delay: -.9s; }
        @keyframes rain {
            from { transform: translateY(-.4rem) rotate(16deg); opacity: .2; }
            35% { opacity: 1; }
            to { transform: translate(-.4rem, 1.05rem) rotate(16deg); opacity: 0; }
        }
        [data-weather-motion="paused"] .drop { animation: none; }
        @media (prefers-reduced-motion: reduce) { .drop { animation: none; } }
        .code { display: block; color: var(--rain); font-size: clamp(3.5rem, 12vw, 6rem); line-height: 1; font-weight: 800; }
        h1 { margin: .6rem 0 .5rem; line-height: 1.3; font-size: clamp(1.55rem, 5vw, 2.2rem); }
        p { margin: 0 auto 1.5rem; color: var(--muted); max-width: 26rem; }
        a {
            display: inline-flex;
            align-items: center;
            min-height: 2.75rem;
            padding: .55rem 1.15rem;
            border-radius: .65rem;
            background: var(--button);
            color: var(--button-ink);
            font-weight: 700;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
        a:focus-visible { outline: .2rem solid var(--rain); outline-offset: .25rem; }
    </style>
</head>
<body>
    <main>
        <div class="weather" aria-hidden="true">
            <div class="cloud"><i class="eye"></i><i class="eye"></i><i class="mouth"></i></div>
            <span class="drop"></span><span class="drop"></span><span class="drop"></span>
            <span class="drop"></span><span class="drop"></span>
        </div>
        <strong class="code" dir="ltr">{{ $status }}</strong>
        <h1><span class="ar">{{ $arTitle }}</span><span class="en">{{ $enTitle }}</span></h1>
        <p><span class="ar">{{ $arMessage }}</span><span class="en">{{ $enMessage }}</span></p>
        <a href="{{ url('/') }}"><span class="ar">الرجوع للرئيسية</span><span class="en">Back to home</span></a>
    </main>
</body>
</html>
