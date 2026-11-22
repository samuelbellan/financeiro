@extends('concursos.layout')

@section('title', 'Simulado em andamento')
@section('semmenu', '1')

@php
    $respostas = $s->respostas ?? [];
    $n = 0;
@endphp

@section('css')
    .barra { position: sticky; top: 0; z-index: 5; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; background: var(--bg); padding: 10px 0; margin-bottom: 12px; border-bottom: 1px solid var(--line); }
    .barra .relogio { font-size: 22px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .barra .relogio.pouco { color: var(--no); }
    .barra .fill { flex: 1; }
    .area-titulo { font-size: 15px; color: var(--muted); margin: 24px 0 8px; text-transform: uppercase; letter-spacing: .04em; }
    .q .cab { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; font-size: 13px; color: var(--muted); margin-bottom: 8px; }
    .q .cab b { color: var(--ink); font-size: 15px; }
    .stem { white-space: pre-line; overflow-wrap: anywhere; margin: 0 0 12px; }
    .opt { display: grid; grid-template-columns: 30px 1fr; gap: 10px; align-items: start; padding: 10px; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; margin-bottom: 6px; }
    .opt input { position: absolute; opacity: 0; }
    .opt .l { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--line); display: grid; place-items: center; font-weight: 700; font-size: 13px; }
    .opt .tx { white-space: pre-line; overflow-wrap: anywhere; padding-top: 4px; }
    .opt:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
    .opt:has(input:checked) .l { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
    .opt:has(input:focus-visible) { outline: 2px solid var(--accent); outline-offset: 2px; }
    .chute { font-size: 14px; color: var(--muted); display: inline-flex; gap: 6px; align-items: center; margin-top: 4px; cursor: pointer; }
    .q.respondida { border-color: var(--accent); }
@endsection

@section('content')
    <form method="POST" action="{{ route('concursos.simulados.entregar', $s) }}" id="prova">
        @csrf
        <div class="barra">
            <span class="relogio" id="relogio" aria-live="off">--:--:--</span>
            <span class="muted small"><span id="feitas">{{ count($respostas) }}</span>/{{ count($s->questao_ids) }} respondidas · <span id="salvo">salvo</span></span>
            <span class="fill"></span>
            <button class="btn main" type="submit" id="entregar">Entregar</button>
        </div>

        @foreach ($s->composicao as $c)
            <h2 class="area-titulo">{{ $c['area'] }} · {{ count($c['questao_ids']) }} questão(ões){{ count($c['questao_ids']) < $c['previstas'] ? ' de ' . $c['previstas'] . ' previstas' : '' }}</h2>
            @if (!$c['questao_ids'])
                <div class="card muted small">Sem questões no banco para esta área ainda.</div>
            @endif
            @foreach ($c['questao_ids'] as $qid)
                @php($q = $questoes[$qid] ?? null)
                @continue(!$q)
                @php($r = $respostas[$qid] ?? null)
                <fieldset class="card q {{ $r ? 'respondida' : '' }}" data-q="{{ $qid }}" style="margin:0 0 12px">
                    <legend class="visually-hidden">Questão {{ ++$n }}</legend>
                    <div class="cab"><b>{{ $n }}</b><span>{{ $q->banca_sigla }} · {{ $q->ano }}</span></div>
                    @if ($q->classificacao?->temFlag('ilegivel'))
                        <div class="alerta">Tem figura que não veio no PDF: <a href="{{ $q->urlTec() }}" target="_blank" rel="noopener">veja no TEC</a>.</div>
                    @endif
                    <p class="stem">{{ $q->enunciado }}</p>
                    @foreach ($q->alternativas as $a)
                        <label class="opt">
                            <input type="radio" name="respostas[{{ $qid }}][r]" value="{{ $a->letra }}" @checked(($r['r'] ?? null) === $a->letra)>
                            <span class="l">{{ $q->tipo === 'CE' ? mb_substr($a->letra, 0, 1) : $a->letra }}</span>
                            <span class="tx">{{ $a->texto }}</span>
                        </label>
                    @endforeach
                    <label class="chute"><input type="checkbox" name="respostas[{{ $qid }}][chute]" value="1" @checked($r['chute'] ?? false)> chutei</label>
                </fieldset>
            @endforeach
        @endforeach

        <div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="btn main" type="submit">Entregar simulado</button></div>
    </form>
@endsection

@section('js')
<script>
(() => {
    const form = document.getElementById('prova');
    const prazo = new Date(@json($s->prazo_em->toIso8601String())).getTime();
    const urlSalvar = @json(route('concursos.simulados.salvar', $s));
    const csrf = form.querySelector('[name=_token]').value;
    const relogio = document.getElementById('relogio');
    const salvo = document.getElementById('salvo');
    const feitas = document.getElementById('feitas');
    const total = {{ count($s->questao_ids) }};
    let enviando = false;
    let timer = null;

    const coletar = () => {
        const r = {};
        form.querySelectorAll('fieldset[data-q]').forEach((f) => {
            const sel = f.querySelector('input[type=radio]:checked');
            r[f.dataset.q] = { r: sel ? sel.value : '', chute: f.querySelector('input[type=checkbox]').checked };
            f.classList.toggle('respondida', Boolean(sel));
        });
        return r;
    };
    const salvar = async () => {
        const r = coletar();
        feitas.textContent = Object.values(r).filter((x) => x.r).length;
        salvo.textContent = 'salvando…';
        try {
            const resp = await fetch(urlSalvar, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ respostas: r }) });
            salvo.textContent = resp.ok ? 'salvo' : 'não salvou (o envio final leva tudo)';
        } catch { salvo.textContent = 'sem conexão (o envio final leva tudo)'; }
    };
    form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(salvar, 600); });

    const entregar = () => { if (!enviando) { enviando = true; window.onbeforeunload = null; form.submit(); } };
    form.addEventListener('submit', (e) => {
        const r = coletar();
        const brancas = total - Object.values(r).filter((x) => x.r).length;
        if (!enviando && brancas > 0 && !confirm(`${brancas} questão(ões) em branco. Entregar mesmo assim?`)) { e.preventDefault(); return; }
        enviando = true;
        window.onbeforeunload = null;
    });
    window.onbeforeunload = () => (enviando ? undefined : true);

    const tick = () => {
        const s = Math.max(0, Math.round((prazo - Date.now()) / 1000));
        const p = (n) => String(n).padStart(2, '0');
        relogio.textContent = `${p(Math.floor(s / 3600))}:${p(Math.floor(s / 60) % 60)}:${p(s % 60)}`;
        relogio.classList.toggle('pouco', s <= 600);
        if (s === 0) entregar();
    };
    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
