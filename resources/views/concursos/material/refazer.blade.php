@extends('concursos.layout')

@section('title', 'Questões · ' . $material->titulo)

{{-- ?embed=1: dentro do modal do resumo, sem menu nem cabeçalho (o modal já mostra a seção). --}}
@if (request()->boolean('embed'))
    @section('semmenu', '1')
@endif

@section('css')
    .cab { display: flex; gap: 12px; align-items: baseline; flex-wrap: wrap; margin-bottom: 12px; }
    .cab h1 { font-size: 22px; margin: 0; flex: 1; }
    table.cad { width: 100%; border-collapse: collapse; font-size: 14px; }
    table.cad th, table.cad td { text-align: left; padding: 7px 6px; border-top: 1px solid var(--line); }
    table.cad th { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
    table.cad td.n, table.cad th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    table.cad .err { color: var(--no); font-weight: 700; }
    .rolar { overflow-x: auto; }
    @media (max-width: 560px) { table.cad .opc { display: none; } }
    .filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; align-items: end; }
    .filtros label { display: grid; gap: 4px; font-size: 13px; font-weight: 600; color: var(--muted); }
    .filtros select, .filtros input { border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 9px 10px; font: inherit; font-size: 15px; min-width: 0; }
    .barra { display: flex; justify-content: space-between; gap: 8px; font-size: 14px; color: var(--muted); margin-bottom: 8px; }
    .meta { font-size: 13px; color: var(--muted); margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 4px 12px; }
    .meta b { color: var(--ink); font-weight: 600; }
    .enun { white-space: pre-line; font-size: 16px; line-height: 1.6; margin: 0 0 14px; }
    .opts { display: grid; gap: 8px; }
    .opt { display: flex; gap: 10px; align-items: flex-start; text-align: left; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 10px; padding: 10px 12px; font: inherit; font-size: 15px; cursor: pointer; white-space: pre-line; }
    .opt:hover:not(:disabled) { border-color: var(--accent); }
    .opt .l { font-weight: 700; min-width: 20px; }
    .opt:disabled { cursor: default; }
    .opt { transition: border-color .15s, background-color .2s, transform .12s var(--mola), opacity .2s; }
    .opt:active:not(:disabled) { transform: scale(.985); }
    .opt.certa { border-color: var(--ok); background: var(--ok-soft); animation: pulso-ok .5s var(--mola); }
    .opt.marcada-errada { border-color: var(--no); background: var(--no-soft); animation: tremer .38s; }
    .opt:disabled:not(.certa):not(.marcada-errada) { opacity: .6; }
    @keyframes pulso-ok { 40% { transform: scale(1.015); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ok) 25%, transparent); } }
    @keyframes tremer { 20%, 60% { transform: translateX(-4px); } 40%, 80% { transform: translateX(4px); } }
    .res { border-radius: 10px; padding: 10px 14px; margin: 14px 0 10px; font-weight: 700; animation: surgir .3s var(--mola); }
    .res.ok::before { content: '✓'; display: inline-grid; place-items: center; width: 20px; height: 20px; margin-right: 8px; border-radius: 50%; background: var(--ok); color: #fff; font-size: 12px; animation: carimbo .45s var(--mola); }
    @keyframes carimbo { from { transform: scale(0) rotate(-30deg); } }
    .entrando { animation: surgir .3s var(--mola); }
    .res.ok { background: var(--ok-soft); color: var(--ok); }
    .res.no { background: var(--no-soft); color: var(--no); }
    .bloco { display: grid; grid-template-columns: 104px 1fr; gap: 4px 12px; margin: 6px 0; font-size: 15px; }
    .bloco > span { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); padding-top: 3px; }
    .bloco > div > :first-child { margin-top: 0; } .bloco > div > :last-child { margin-bottom: 0; }
    .bloco.armadilha > span { color: var(--no); } .bloco.regra > span { color: var(--ok); }
    details.com { margin-top: 10px; font-size: 14px; }
    details.com summary { cursor: pointer; font-weight: 600; color: var(--accent); }
    details.com pre { white-space: pre-wrap; font: inherit; font-size: 14px; color: var(--ink); background: var(--bg); border-radius: 8px; padding: 10px 12px; max-height: 420px; overflow: auto; }
    .acoes { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
    a.lei { font-weight: 600; text-decoration: underline dotted; text-underline-offset: 3px; }
    .fim h2 { font-size: 20px; margin: 0 0 8px; }
    .fim ul { padding-left: 20px; font-size: 14px; }
    dialog.art { border: 1px solid var(--line); border-radius: 14px; background: var(--card); color: var(--ink); max-width: 680px; width: calc(100% - 32px); max-height: 80vh; padding: 0; }
    dialog.art::backdrop { background: rgba(0,0,0,.45); }
    dialog.art .topo { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); position: sticky; top: 0; background: var(--card); }
    dialog.art .topo b { flex: 1; }
    dialog.art .corpo { padding: 4px 16px 16px; font-size: 15px; }
    @media (max-width: 560px) { .bloco { grid-template-columns: 1fr; } }
    .abas { display: flex; gap: 6px; margin-bottom: 14px; border-bottom: 1px solid var(--line); }
    .abas a { padding: 8px 14px; font-weight: 600; color: var(--muted); border-bottom: 2px solid transparent; margin-bottom: -1px; text-decoration: none; }
    .abas a.on { color: var(--ink); border-color: var(--accent); }
    .hoje { display: flex; gap: 16px 24px; align-items: center; flex-wrap: wrap; }
    .hoje .big { font-size: 40px; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
    .hoje .info { flex: 1; min-width: 200px; display: grid; gap: 4px; font-size: 14px; color: var(--muted); }
    .hoje .info b { color: var(--ink); }
    .chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
    .chip { font-size: 13px; font-weight: 700; padding: 3px 10px; border-radius: 999px; text-decoration: none; }
    .chip.errando { background: var(--no-soft); color: var(--no); }
    .chip.revisando { background: color-mix(in srgb, #ca8a04 18%, transparent); color: #b7791f; }
    .chip.dominado { background: var(--ok-soft); color: var(--ok); }
    .chip.neutro { background: var(--bg); color: var(--muted); }
    table.cad .rev { color: #b7791f; } table.cad .dom { color: var(--ok); }
    .certeza { display: flex; gap: 6px; align-items: center; margin: 0 0 10px; font-size: 13px; color: var(--muted); flex-wrap: wrap; }
    .certeza button { border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 999px; padding: 4px 12px; font: inherit; font-size: 13px; cursor: pointer; }
    .certeza button.on { border-color: var(--accent); background: var(--accent); color: #fff; }
    .caixa-info { font-size: 14px; color: var(--muted); margin: -4px 0 10px; }
    .etq { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); border: 1px solid var(--line); border-radius: 6px; padding: 0 6px; margin-left: 6px; }
    .ponto-card + .ponto-card { border-top: 1px dashed var(--line); margin-top: 10px; padding-top: 10px; }
@endsection

@section('content')
@if (request()->boolean('embed'))
    <style>.wrap { padding-top: 12px; } .cab, nav.abas { display: none; }</style>
    <script>
        // Links que saem das questões (ex.: "ver no livro") abrem na página de fora, não dentro do modal.
        document.addEventListener('click', e => { const a = e.target.closest('a[href]'); if (a && !a.pathname.endsWith('/refazer')) a.target = '_top'; });
    </script>
@endif
<div class="cab">
    <h1>{{ $material->titulo }}</h1>
    <a href="{{ route('concursos.revisoes') }}">← Revisões</a>
</div>
<nav class="abas">
    <a href="{{ route('concursos.material.show', $material) }}">Resumo</a>
    <a class="on" href="{{ route('concursos.material.refazer', $material) }}">Questões</a>
</nav>

@if (!$fila)
    @if ($resumo->isEmpty())
        <div class="card muted">Este material ainda não tem questões. Importe com <code>php artisan concursos:importar-todas …</code>.</div>
    @else
        @php($p = $painel)
        <div class="card">
            <div class="hoje">
                <div><div class="big">{{ $p['hoje'] }}</div><div class="muted small">para revisar hoje</div></div>
                <div class="info">
                    <span>Caixa 1 (erros recentes): <b>{{ $p['porCaixa'][1] }}</b> · caixa 2 (7 dias): <b>{{ $p['porCaixa'][2] }}</b> · caixa 3 (30 dias): <b>{{ $p['porCaixa'][3] }}</b></span>
                    <span>Amanhã: <b>{{ $p['amanha'] }}</b> · próximos 7 dias: <b>{{ $p['semana'] }}</b></span>
                </div>
                @if ($p['hoje'])
                    <a class="btn main" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'fila', 'iniciar' => 1]) }}">Revisar agora ({{ min(20, $p['hoje']) }})</a>
                @else
                    <span class="muted">Nada vencido hoje.</span>
                @endif
            </div>
            <div class="chips">
                <a class="chip errando" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'errando', 'iniciar' => 1]) }}">{{ $p['estados']['errando'] }} ainda erro</a>
                <a class="chip revisando" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'revisando', 'iniciar' => 1]) }}">{{ $p['estados']['revisando'] }} revisando</a>
                <a class="chip dominado" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'dominado', 'iniciar' => 1]) }}">{{ $p['estados']['dominado'] }} dominadas</a>
                <span class="chip neutro">{{ $p['total'] }} questões</span>
            </div>
        </div>

        <h2 style="font-size:17px;margin:18px 0 8px">Explorar</h2>
        <form class="card" method="GET" action="{{ route('concursos.material.refazer', $material) }}">
            <input type="hidden" name="iniciar" value="1">
            <div class="filtros">
                <label>Caderno
                    <select name="secao">
                        <option value="">Todos</option>
                        @foreach ($resumo as $r)<option value="{{ $r['id'] }}" @selected(($f['secao'] ?? null) == $r['id'])>{{ $r['titulo'] }}</option>@endforeach
                    </select>
                </label>
                @if ($capitulos->isNotEmpty())
                <label>Capítulo do livro
                    <select name="capitulo">
                        <option value="">Todos</option>
                        @foreach ($capitulos as $c)<option value="{{ $c['id'] }}" @selected(($f['capitulo'] ?? null) == $c['id'])>{{ $c['titulo'] }} ({{ $c['n'] }})</option>@endforeach
                    </select>
                </label>
                @endif
                <label>Ponto de erro
                    <select name="ponto">
                        <option value="">Todos</option>
                        @foreach ($assuntos as $a)<option value="{{ $a['id'] }}" @selected(($f['ponto'] ?? null) == $a['id'])>{{ $a['titulo'] }} ({{ $a['n'] }})</option>@endforeach
                    </select>
                </label>
                @if ($bancas->count() > 1)
                <label>Banca
                    <select name="banca">
                        <option value="">Todas</option>
                        @foreach ($bancas as $b => $n)<option value="{{ $b }}" @selected(($f['banca'] ?? null) === $b)>{{ $b }} ({{ $n }})</option>@endforeach
                    </select>
                </label>
                @endif
                <label>Situação
                    <select name="situacao">
                        @foreach (\App\Http\Controllers\Concursos\RefazerController::SITUACOES as $k => $nome)<option value="{{ $k }}" @selected($f['situacao'] === $k)>{{ $nome }}</option>@endforeach
                    </select>
                </label>
                <label>Ordem
                    <select name="ordem">
                        <option value="caderno" @selected($f['ordem'] === 'caderno')>Do caderno</option>
                        <option value="aleatoria" @selected($f['ordem'] === 'aleatoria')>Aleatória</option>
                    </select>
                </label>
                <label>Quantas
                    <input type="number" name="qtd" min="1" max="200" value="{{ $f['qtd'] }}">
                </label>
                <button class="btn main" type="submit">Começar</button>
            </div>
        </form>
        @if (request()->boolean('iniciar'))
            <div class="card muted">Nenhuma questão com esses filtros. Tente “Todas” em Situação.</div>
        @endif

        <div class="card rolar">
            <table class="cad">
                <thead><tr><th>Caderno</th><th class="n">Total</th><th class="n">Ainda erro</th><th class="n opc">Revisando</th><th class="n opc">Dominadas</th><th></th></tr></thead>
                <tbody>
                    @foreach ($resumo as $r)
                        <tr>
                            <td>{{ $r['titulo'] }}</td>
                            <td class="n">{{ $r['total'] }}</td>
                            <td class="n {{ $r['errando'] ? 'err' : '' }}">{{ $r['errando'] }}</td>
                            <td class="n opc rev">{{ $r['revisando'] }}</td>
                            <td class="n opc dom">{{ $r['dominado'] }}</td>
                            <td class="n"><a href="{{ route('concursos.material.refazer', [$material, 'secao' => $r['id'], 'situacao' => 'errando', 'iniciar' => 1]) }}">Refazer erradas</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@else
    <div class="card" id="quiz">
        <div class="barra"><span data-pos></span><span data-placar></span></div>
        <div class="meta" data-meta></div>
        <p class="enun" data-enun></p>
        <div class="certeza" data-certeza>Certeza:
            <button type="button" data-c="sei">1 · Sei</button>
            <button type="button" data-c="acho">2 · Acho</button>
            <button type="button" data-c="chute">3 · Chute</button>
        </div>
        <div class="opts" data-opts></div>
        <div data-retorno hidden>
            <div class="res" data-res></div>
            <div class="caixa-info" data-caixa></div>
            <div data-ponto></div>
            <details class="com" data-com-box><summary>Comentário do caderno</summary><pre data-com></pre></details>
            <div class="acoes">
                <button class="btn main" type="button" data-prox>Próxima →</button>
                <a class="btn" data-tec target="_blank" rel="noopener">Ver no TEC</a>
            </div>
        </div>
    </div>
    <div class="card fim" data-fim hidden>
        <h2 data-fim-titulo></h2>
        <ul data-fim-lista></ul>
        <div class="acoes">
            <a class="btn main" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'fila', 'iniciar' => 1]) }}">Continuar a fila de hoje</a>
            <a class="btn" href="{{ route('concursos.material.refazer', $material) }}">Voltar às Questões</a>
            <a class="btn" href="{{ route('concursos.material.show', $material) }}">Ir ao resumo</a>
        </div>
    </div>
    <p class="muted small">Teclas: 1/2/3 para a certeza, letra da alternativa (ou C/E) para responder, Enter para a próxima. Acerto no “chute” não sobe de caixa.</p>
    @csrf
@endif

<dialog class="art" aria-labelledby="art-titulo">
    <div class="topo"><b id="art-titulo">Texto</b><a href="#" data-abrir class="btn">Abrir</a><button type="button" class="btn" data-fechar>Fechar</button></div>
    <div class="corpo" data-corpo></div>
</dialog>
@endsection

@section('js')
<script>
(() => {
    const fila = @json($fila);
    const token = document.querySelector('input[name=_token]')?.value;
    const $ = s => document.querySelector(s);

    // Diálogo com o texto da lei / súmula (links do resumo).
    const dlg = $('dialog.art');
    dlg.querySelector('[data-fechar]').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    document.addEventListener('click', async e => {
        const a = e.target.closest('a.lei');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        const corpo = dlg.querySelector('[data-corpo]');
        dlg.querySelector('#art-titulo').textContent = a.textContent;
        dlg.querySelector('[data-abrir]').href = a.href;
        corpo.textContent = 'Carregando…';
        dlg.showModal();
        try {
            const resp = await fetch(a.dataset.json, { headers: { 'Accept': 'application/json' } });
            const r = await resp.json().catch(() => ({}));
            if (!resp.ok) { corpo.textContent = r.erro ?? 'Texto não encontrado no acervo.'; return; }
            dlg.querySelector('#art-titulo').textContent = r.titulo;
            corpo.innerHTML = r.html;
        } catch (err) { corpo.textContent = 'Não consegui carregar.'; }
    });

    if (!fila.length) return;
    let i = 0, acertos = 0, respondida = false, certeza = 'sei';
    const erros = [];
    const certezaBox = $('[data-certeza]');
    function marcarCerteza(c) {
        certeza = c;
        certezaBox.querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.c === c));
    }
    certezaBox.addEventListener('click', e => { const b = e.target.closest('button[data-c]'); if (b && !respondida) marcarCerteza(b.dataset.c); });

    function mostrar() {
        const q = fila[i];
        respondida = false;
        $('[data-pos]').textContent = `Questão ${i + 1} de ${fila.length}`;
        $('[data-placar]').textContent = i ? `${acertos} de ${i} certas` : '';
        const meta = $('[data-meta]');
        meta.replaceChildren();
        marcarCerteza('sei');
        [['', q.caderno], ['Assunto: ', q.assunto], ['', q.origem], ['Antes: ', q.antes], ['Caixa ', q.caixa ? String(q.caixa) : null]].forEach(([r, v]) => {
            if (!v) return;
            const s = document.createElement('span');
            if (r) { const b = document.createElement('b'); b.textContent = r; s.append(b); }
            s.append(v);
            meta.append(s);
        });
        $('[data-enun]').textContent = q.enunciado;
        const quiz = $('#quiz'); quiz?.classList.remove('entrando'); void quiz?.offsetWidth; quiz?.classList.add('entrando');
        const opts = $('[data-opts]');
        opts.replaceChildren(...q.alternativas.map(a => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'opt'; b.dataset.letra = a.letra;
            const l = document.createElement('span'); l.className = 'l'; l.textContent = q.tipo === 'CE' ? '' : a.letra + ')';
            const t = document.createElement('span'); t.textContent = a.texto;
            b.append(l, t);
            b.addEventListener('click', () => responder(a.letra));
            return b;
        }));
        $('[data-retorno]').hidden = true;
        $('#quiz').scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    async function responder(letra) {
        if (respondida) return;
        respondida = true;
        const q = fila[i];
        const corpo = new FormData(); corpo.append('resposta', letra); corpo.append('certeza', certeza); corpo.append('_token', token);
        const resp = await fetch(q.url, { method: 'POST', body: corpo, headers: { 'Accept': 'application/json' } });
        if (!resp.ok) { respondida = false; alert('Não consegui registrar a resposta. Recarregue a página.'); return; }
        const r = await resp.json();
        if (r.acertou) acertos++; else erros.push({ q, r });
        document.querySelectorAll('.opt').forEach(b => {
            b.disabled = true;
            if (b.dataset.letra === r.gabarito) b.classList.add('certa');
            else if (b.dataset.letra === letra) b.classList.add('marcada-errada');
        });
        const res = $('[data-res]');
        const nome = x => q.tipo === 'CE' ? (x === 'C' ? 'Certo' : 'Errado') : x;
        res.className = 'res';
        void res.offsetWidth; // reinicia a animação de entrada
        res.className = 'res ' + (r.acertou ? 'ok' : 'no');
        res.textContent = r.acertou ? `Acertou! Gabarito: ${nome(r.gabarito)}` : `Errou. Você marcou ${nome(letra)}; o gabarito é ${nome(r.gabarito)}.`;
        $('[data-caixa]').textContent = r.caixa ?? '';
        $('[data-caixa]').hidden = !r.caixa;
        const ponto = $('[data-ponto]');
        ponto.innerHTML = '';
        r.ponto = r.pontos?.[0] ?? null;
        if (r.pontos?.length) {
            // HTML gerado no servidor a partir do resumo (Markdown escapado).
            ponto.innerHTML = r.pontos.map(p => `<div class="ponto-card"><div class="bloco"><span>Ponto</span><div><a href="${p.url}">${escapar(p.titulo)}</a>${p.etiqueta ? `<span class="etq">${escapar(p.etiqueta)}</span>` : ''}</div></div>`
                + (p.armadilha ? `<div class="bloco armadilha"><span>Onde caio</span><div>${p.armadilha}</div></div>` : '')
                + (p.regra ? `<div class="bloco regra"><span>Regra certa</span><div>${p.regra}</div></div>` : '')
                + (p.macete ? `<div class="bloco"><span>Macete</span><div>${p.macete}</div></div>` : '')
                + (p.base_legal ? `<div class="bloco"><span>Base legal</span><div>${p.base_legal}</div></div>` : '') + '</div>').join('');
        }
        if (r.livro) {
            ponto.insertAdjacentHTML('beforeend', `<div class="bloco"><span>No resumo</span><div><a href="${r.livro}">Ver o capítulo deste assunto</a></div></div>`);
        }
        $('[data-com-box]').hidden = !r.comentario;
        $('[data-com-box]').open = !r.ponto;
        $('[data-com]').textContent = r.comentario ?? '';
        const tec = $('[data-tec]');
        tec.hidden = !r.tec; if (r.tec) tec.href = r.tec;
        $('[data-placar]').textContent = `${acertos} de ${i + 1} certas`;
        $('[data-retorno]').hidden = false;
        $('[data-prox]').textContent = i + 1 < fila.length ? 'Próxima →' : 'Ver resultado';
        $('[data-prox]').focus({ preventScroll: true });
    }

    function escapar(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function proxima() {
        if (!respondida) return;
        if (++i < fila.length) return mostrar();
        $('#quiz').hidden = true;
        $('[data-fim]').hidden = false;
        $('[data-fim-titulo]').textContent = `${acertos} de ${fila.length} certas (${Math.round(100 * acertos / fila.length)}%)`;
        $('[data-fim-lista]').replaceChildren(...erros.map(({ q, r }) => {
            const li = document.createElement('li');
            const txt = `${q.caderno}: ${q.assunto ?? q.enunciado.slice(0, 80) + '…'}`;
            if (r.ponto) { const a = document.createElement('a'); a.href = r.ponto.url; a.textContent = txt; li.append(a); } else li.textContent = txt;
            return li;
        }));
        if (!erros.length) $('[data-fim-lista]').replaceChildren(Object.assign(document.createElement('li'), { textContent: 'Nenhum erro nesta rodada.' }));
    }

    $('[data-prox]').addEventListener('click', proxima);
    document.addEventListener('keydown', e => {
        if (dlg.open || e.target.closest('input, select, textarea')) return;
        if (e.key === 'Enter' && respondida) { e.preventDefault(); return proxima(); }
        const k = e.key.toUpperCase();
        if (!respondida && ['1', '2', '3'].includes(k)) return marcarCerteza(['sei', 'acho', 'chute'][+k - 1]);
        if (!respondida && fila[i].alternativas.some(a => a.letra === k)) responder(k);
    });
    mostrar();
})();
</script>
@endsection
