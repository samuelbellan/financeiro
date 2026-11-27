<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Questões') · Concursos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
    // Tema antes de pintar: 'claro' | 'escuro' | 'auto' (padrão, segue o sistema).
    (function () {
        var mq = matchMedia('(prefers-color-scheme: dark)');
        function pref() { try { return localStorage.getItem('concursos.tema') || 'auto'; } catch (e) { return 'auto'; } }
        function aplicar() {
            var t = pref(), escuro = t === 'escuro' || (t === 'auto' && mq.matches);
            document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
            document.documentElement.dataset.tema = t;
        }
        aplicar();
        mq.addEventListener && mq.addEventListener('change', aplicar);
        window.trocarTema = function (ev) {
            var ordem = ['auto', 'claro', 'escuro'], t = ordem[(ordem.indexOf(pref()) + 1) % 3];
            try { localStorage.setItem('concursos.tema', t); } catch (e) {}
            var calmo = matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!document.startViewTransition || calmo) { aplicar(); return; }
            // Revelação circular a partir do botão (o tema novo "se espalha" pela tela).
            var r = ev && ev.currentTarget ? ev.currentTarget.getBoundingClientRect() : { left: innerWidth, top: 0, width: 0, height: 0 };
            var x = r.left + r.width / 2, y = r.top + r.height / 2, raio = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
            var vt = document.startViewTransition(aplicar);
            vt.ready.then(function () {
                document.documentElement.animate({ clipPath: ['circle(0 at ' + x + 'px ' + y + 'px)', 'circle(' + raio + 'px at ' + x + 'px ' + y + 'px)'] },
                    { duration: 480, easing: 'cubic-bezier(.4,0,.2,1)', pseudoElement: '::view-transition-new(root)' });
            });
        };
    })();
    </script>
    @yield('head')
    <style>
        :root {
            --bg: #f4f5f8; --card: #ffffff; --ink: #1d2433; --muted: #667085; --line: #e3e6ec;
            --accent: #3b6fe0; --accent-ink: #ffffff; --accent-soft: #e8efff;
            --ok: #12805c; --ok-soft: #e3f6ee; --no: #c0362c; --no-soft: #fdecea; --warn: #9a6400; --warn-soft: #fff4dc;
        }
        /* Tema: data-theme é resolvido no <head> (auto segue o sistema; claro/escuro ficam salvos no aparelho). */
        :root[data-theme=dark] {
            color-scheme: dark;
            --bg: #12151c; --card: #1b1f29; --ink: #e6e9f0; --muted: #98a2b3; --line: #2c3241;
            --accent: #6f9bff; --accent-ink: #0d1117; --accent-soft: #1f2a44;
            --ok: #4cc79a; --ok-soft: #15322a; --no: #ff8076; --no-soft: #3a1d1b; --warn: #f0b44c; --warn-soft: #3a2e15;
        }
        * { box-sizing: border-box; }
        /* ---- movimento: transição entre páginas (a aba ativa desliza) e microinterações ---- */
        @view-transition { navigation: auto; }
        ::view-transition-old(root), ::view-transition-new(root) { animation-duration: .22s; }
        .menu a[aria-current=page] { view-transition-name: aba-ativa; }
        ::view-transition-group(aba-ativa) { animation-duration: .32s; animation-timing-function: cubic-bezier(.3,.7,.2,1); }
        :root { --mola: cubic-bezier(.3,.7,.2,1.15); }
        @keyframes surgir { from { opacity: 0; transform: translateY(8px); } }
        @keyframes brilho { from { background-position: 150% 0; } to { background-position: -50% 0; } }
        .esqueleto { border-radius: 8px; background: linear-gradient(90deg, var(--line) 30%, color-mix(in srgb, var(--line) 40%, var(--card)) 50%, var(--line) 70%) 0 0 / 300% 100%; animation: brilho 1.3s linear infinite; }
        body { margin: 0; background: var(--bg); color: var(--ink); font: 16px/1.55 Inter, system-ui, sans-serif; }
        a { color: var(--accent); }
        .wrap { max-width: 880px; margin: 0 auto; padding: 16px 16px 64px; }
        .top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .top h1 { font-size: 20px; margin: 0; flex: 1; min-width: 200px; }
        .muted { color: var(--muted); }
        .small { font-size: 14px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--line); background: var(--card); color: var(--ink);
               border-radius: 10px; padding: 10px 16px; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none;
               transition: background-color .15s, border-color .15s, color .15s, box-shadow .15s, transform .12s var(--mola); }
        .btn:hover:not(:disabled) { border-color: color-mix(in srgb, var(--accent) 40%, var(--line)); }
        .btn.main:hover:not(:disabled) { box-shadow: 0 4px 14px color-mix(in srgb, var(--accent) 35%, transparent); }
        .btn:active:not(:disabled) { transform: scale(.96); }
        input, select, textarea { transition: border-color .15s, box-shadow .15s; }
        input:focus-visible, select:focus-visible, textarea:focus-visible { outline: none; border-color: var(--accent) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 22%, transparent); }
        .btn.main { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
        .btn:disabled { opacity: .45; cursor: not-allowed; }
        .btn:focus-visible, .opt:focus-within, .chip:focus-within { outline: 2px solid var(--accent); outline-offset: 2px; }
        .flash { background: var(--accent-soft); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; }
        .alerta { background: var(--warn-soft); color: var(--warn); border-radius: 10px; padding: 10px 14px; margin: 12px 0; font-size: 14px; }
        .alerta a { color: inherit; font-weight: 600; }
        .bar { height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; }
        .bar > span { display: block; height: 100%; background: var(--ok); }
        .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        .menu { display: flex; gap: 4px; overflow-x: auto; margin: -4px 0 16px; padding-bottom: 4px; scrollbar-width: none; }
        .menu a { flex: none; padding: 7px 12px; border-radius: 99px; text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 600; transition: color .15s, background-color .15s; }
        .menu a:hover { color: var(--ink); background: var(--card); }
        .menu a[aria-current=page] { color: var(--accent-ink); background: var(--accent); }
        .menu .tema { flex: none; margin-left: auto; border: 1px solid var(--line); background: var(--card); color: var(--muted); border-radius: 99px; padding: 6px 12px; font: 600 13px/1 Inter, system-ui, sans-serif; cursor: pointer; white-space: nowrap; }
        .menu .tema:hover { color: var(--ink); }
        .menu .tema:active { transform: scale(.94); }
        @media (prefers-reduced-motion: reduce) {
            @view-transition { navigation: none; }
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
        .menu .tema::after { content: 'Tema: auto'; }
        :root[data-tema=claro] .menu .tema::after { content: '☀ Claro'; }
        :root[data-tema=escuro] .menu .tema::after { content: '☾ Escuro'; }
        @yield('css')
    </style>
</head>
<body>
<div class="wrap">
    @unless (View::hasSection('semmenu'))
        @php
            // rota => [rótulo, prefixo das rotas que acendem o item]
            $itensMenu = [
                'concursos.meta' => ['Meta do dia', 'concursos.meta'],
                'concursos.questoes.index' => ['Questões', 'concursos.questoes.'],
                'concursos.painel' => ['Desempenho', 'concursos.painel'],
                'concursos.simulados' => ['Simulados', 'concursos.simulado'],
                'concursos.material.index' => ['Resumos', 'concursos.material.'],
                'concursos.revisoes' => ['Revisões', 'concursos.revisoes'],
                'concursos.leis.index' => ['Leis', 'concursos.leis.'],
                'concursos.perfil' => ['Perfil', 'concursos.perfil'],
            ];
            if (!auth()->user()?->temPlanoConcursos()) {
                // Sem o planejamento dirigido, o módulo é só material de resumo e lei seca.
                $itensMenu = array_intersect_key($itensMenu, array_flip(['concursos.material.index', 'concursos.revisoes', 'concursos.leis.index']));
            }
            if (auth()->user()?->is_admin || auth()->user()?->is_owner) {
                $itensMenu['concursos.curadoria'] = ['Curadoria', 'concursos.curadoria'];
                $itensMenu['concursos.uso'] = ['Uso', 'concursos.uso'];
            }
            $rotaAtual = request()->route()?->getName() ?? '';
            // Refazer questões de uma matéria pertence a Revisões, não a Resumos.
            if (str_starts_with($rotaAtual, 'concursos.material.refazer')) { $rotaAtual = 'concursos.revisoes'; }
        @endphp
        <nav class="menu" aria-label="Módulo de concursos">
            @foreach ($itensMenu as $rota => [$nome, $prefixo])
                @if (Route::has($rota))
                    <a href="{{ route($rota) }}" @if (str_starts_with($rotaAtual, $prefixo)) aria-current="page" @endif>{{ $nome }}</a>
                @endif
            @endforeach
            <button type="button" class="tema" onclick="trocarTema(event)" title="Trocar tema (automático, claro, escuro)" aria-label="Trocar tema"></button>
        </nav>
    @endunless
    @if (session('status'))
        <div class="flash" role="status">{{ session('status') }}</div>
    @endif
    @yield('content')
</div>
@yield('js')
@if ($usoEvento = request()->attributes->get('uso_evento'))
<script>
// Telemetria: quanto tempo esta tela ficou à vista (acumulado), enviado ao esconder ou sair.
(function () {
    var url = @json(route('concursos.uso.tempo')), token = @json(csrf_token()), id = @json($usoEvento);
    var total = 0, desde = document.visibilityState === 'visible' ? Date.now() : null, enviado = -1;
    function parar() { if (desde) { total += Date.now() - desde; desde = null; } }
    function enviar() {
        var s = Math.round(total / 1000);
        if (s === enviado || !navigator.sendBeacon) return;
        var f = new FormData(); f.append('_token', token); f.append('id', id); f.append('s', s);
        if (navigator.sendBeacon(url, f)) enviado = s;
    }
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { parar(); enviar(); } else { desde = Date.now(); }
    });
    addEventListener('pagehide', function () { parar(); enviar(); });
})();
</script>
@endif
</body>
</html>
