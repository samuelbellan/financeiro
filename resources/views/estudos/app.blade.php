@php
    $buildManifest = public_path('build/manifest.json');
    $swVersion = is_file($buildManifest) ? substr(md5_file($buildManifest), 0, 12) : 'dev';
    $useServiceWorker = !is_file(public_path('hot'));
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Horas de Estudo</title>
    <meta name="theme-color" content="#4F86F7">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Estudos">
    <link rel="manifest" href="{{ asset('estudos.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/estudos/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/estudos/icon-192.png') }}">
    @vite(['resources/css/estudos.css', 'resources/js/estudos/main.js'])
</head>
<body>
    <div id="estudos-app"
         data-user-id="{{ auth()->id() }}"
         data-csrf="{{ csrf_token() }}"
         data-sync-url="{{ route('estudos.sync', [], false) }}"
         data-token-url="{{ route('estudos.token', [], false) }}"
         data-login-url="{{ route('login', [], false) }}"
         data-home-url="{{ route('home', [], false) }}"
         data-sw-url="{{ $useServiceWorker ? '/estudos-sw.js?v=' . $swVersion : '' }}"
         data-sw-scope="/estudos">
        <header class="topbar">
            <a href="{{ route('home', [], false) }}" class="brand" aria-label="Voltar ao sistema">Estudos</a>
            <span class="sync-badge" data-sync-badge></span>
        </header>
        <div class="conflicts" data-conflicts hidden></div>
        <main data-view></main>
        <nav class="tabbar">
            <button type="button" data-tab="timer"><span>⏱</span>Cronômetro</button>
            <button type="button" data-tab="timeline"><span>▦</span>Linha do tempo</button>
            <button type="button" data-tab="stats"><span>▮</span>Estatísticas</button>
            <button type="button" data-tab="settings"><span>⚙</span>Ajustes</button>
        </nav>
    </div>
    <dialog id="estudos-dialog"></dialog>
    <noscript>Este módulo precisa de JavaScript.</noscript>
</body>
</html>
