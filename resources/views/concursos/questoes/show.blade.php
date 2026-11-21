@extends('concursos.layout')

@section('title', "Questão {$q->id_externo}")

@php
    $respondida = (bool) $t;
    $res = $q->resolucao;
    $cls = $q->classificacao;
    $folha = $q->assuntos->first();
    $ctxQuery = $ctx;
    $pct = $progresso['respondidas'] ? round(100 * $progresso['acertos'] / $progresso['respondidas']) : null;
@endphp

@section('css')
    .ctx { display: flex; flex-wrap: wrap; gap: 8px 16px; align-items: center; font-size: 14px; }
    .ctx .bar { width: 120px; }
    .meta { display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: 13px; color: var(--muted); margin-bottom: 12px; }
    .meta b { color: var(--ink); font-weight: 600; }
    .stem { white-space: pre-line; overflow-wrap: anywhere; margin: 0 0 16px; }
    .opts { display: grid; gap: 8px; margin: 0; padding: 0; border: 0; }
    .opt { display: grid; grid-template-columns: 32px 1fr auto; gap: 12px; align-items: start; padding: 12px; border: 1px solid var(--line); border-radius: 12px; cursor: pointer; background: var(--card); }
    .opt input { position: absolute; opacity: 0; pointer-events: none; }
    .opt .l { width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid var(--line); display: grid; place-items: center; font-weight: 700; font-size: 14px; }
    .opt .tx { white-space: pre-line; overflow-wrap: anywhere; padding-top: 4px; }
    .opt:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
    .opt:has(input:checked) .l { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
    .opt.riscada .tx { text-decoration: line-through; color: var(--muted); }
    .opt .risca { border: 0; background: none; color: var(--muted); cursor: pointer; font-size: 18px; padding: 2px 6px; border-radius: 6px; }
    .opt.ok { border-color: var(--ok); background: var(--ok-soft); cursor: default; }
    .opt.ok .l { background: var(--ok); color: #fff; border-color: var(--ok); }
    .opt.no { border-color: var(--no); background: var(--no-soft); cursor: default; }
    .opt.no .l { background: var(--no); color: #fff; border-color: var(--no); }
    .opt.fixa { cursor: default; }
    .exp { grid-column: 2 / -1; font-size: 14px; color: var(--muted); border-top: 1px dashed var(--line); padding-top: 8px; margin-top: 2px; }
    .ok .exp, .no .exp { color: var(--ink); }
    .certeza { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 16px 0; border: 0; padding: 0; }
    .certeza legend { font-weight: 600; font-size: 14px; margin-bottom: 6px; padding: 0; }
    .chip { position: relative; }
    .chip input { position: absolute; opacity: 0; }
    .chip span { display: inline-block; padding: 8px 14px; border: 1px solid var(--line); border-radius: 99px; cursor: pointer; font-size: 14px; font-weight: 500; }
    .chip input:checked + span { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
    .acoes { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .acoes .fill { flex: 1; }
    .resultado { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 14px 16px; border-radius: 12px; font-weight: 600; margin-bottom: 16px; }
    .resultado.ok { background: var(--ok-soft); color: var(--ok); }
    .resultado.no { background: var(--no-soft); color: var(--no); }
    .resultado .fill { flex: 1; }
    .motivos { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .motivos button { border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 99px; padding: 8px 14px; font: inherit; font-size: 14px; cursor: pointer; }
    .motivos button:hover { border-color: var(--accent); }
    .motivos kbd, .dica kbd { font: 12px ui-monospace, monospace; border: 1px solid var(--line); border-radius: 4px; padding: 0 4px; color: var(--muted); }
    .sol h2 { font-size: 16px; margin: 0 0 8px; }
    .sol .ponto { background: var(--accent-soft); border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; font-size: 15px; }
    .sol details { margin-top: 12px; }
    .sol summary { cursor: pointer; font-weight: 600; }
    .sol .fund { white-space: pre-line; font-size: 15px; margin-top: 8px; }
    .hist { font-size: 13px; color: var(--muted); }
    .dica { font-size: 12px; color: var(--muted); margin-top: 12px; }
    @media (max-width: 560px) { .card { padding: 16px; } .dica { display: none; } }
@endsection

@section('content')
    <div class="top">
        <a class="btn" href="{{ $ctxAtividade ? route('concursos.meta') : route('concursos.questoes.index') }}" aria-label="Voltar">←</a>
        <div class="ctx" style="flex:1">
            @if ($ctxAtividade)
                <span><b>Meta de hoje</b> · {{ \App\Models\Concursos\MetaAtividade::TIPOS[$ctxAtividade->tipo] }}{{ $ctxAtividade->topico ? ': ' . $ctxAtividade->topico->ordem . ' ' . \Illuminate\Support\Str::limit($ctxAtividade->topico->texto, 60) : '' }}</span>
            @else
                <span><b>{{ $ctxAssunto?->nome ?? 'Todas as disciplinas' }}</b> · {{ $filtros[$ctx['filtro']] }}</span>
            @endif
            <span class="muted">{{ $progresso['respondidas'] }}/{{ $progresso['total'] }} feitas{{ $pct !== null ? " · {$pct}% de acerto" : '' }}</span>
            @if ($pct !== null)<span class="bar"><span style="width: {{ $pct }}%"></span></span>@endif
        </div>
    </div>

    @if ($respondida)
        <div class="resultado {{ $t->correta ? 'ok' : 'no' }}" role="status">
            <span class="fill">
                {{ $t->correta ? 'Você acertou.' : "Você errou. O gabarito é {$q->gabarito}." }}
                @if ($t->correta && $t->certeza === 'chute')
                    <span class="small" style="font-weight:500">Foi no chute: ela continua em "Que errei ou chutei".</span>
                @endif
            </span>
            @if ($t->correta || $t->motivo_erro)
                <a class="btn main" id="proxima" href="{{ route('concursos.questoes.resolver', ['depois' => $q->id] + $ctxQuery) }}">Próxima →</a>
            @endif
        </div>
        @if (!$t->correta && !$t->motivo_erro)
            <form class="card" method="POST" action="{{ route('concursos.questoes.motivo', ['tentativa' => $t->id]) }}">
                @csrf
                @foreach ($ctxQuery as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <b>Por que você errou?</b> <span class="muted small">Isso decide o que a revisão vai te cobrar.</span>
                <div class="motivos">
                    @foreach ($motivos as $chave => $nome)
                        <button type="submit" name="motivo" value="{{ $chave }}" data-tecla="{{ $loop->iteration }}"><kbd>{{ $loop->iteration }}</kbd> {{ $nome }}</button>
                    @endforeach
                </div>
            </form>
        @endif
    @endif

    <div class="card">
        <div class="meta">
            <span><b>Q{{ $q->id_externo }}</b></span>
            <span>{{ $q->banca_sigla }}</span>
            @if ($q->orgao)<span>{{ $q->orgao }}</span>@endif
            <span>{{ $q->ano }}</span>
            @if ($folha)<span>{{ $folha->nome }}</span>@endif
            @if ($cls)<span title="dificuldade estimada">{{ ['B' => 'fácil', 'M' => 'média', 'A' => 'difícil'][$cls->dificuldade] ?? '' }}</span>@endif
            @if ($q->urlTec())<a href="{{ $q->urlTec() }}" target="_blank" rel="noopener">ver no TEC ↗</a>@endif
        </div>

        @if ($cls?->temFlag('ilegivel'))
            <div class="alerta">Esta questão tem figura ou tabela que não veio no PDF. <a href="{{ $q->urlTec() }}" target="_blank" rel="noopener">Abra no TEC</a> para ver antes de responder.</div>
        @endif

        <p class="stem">{{ $q->enunciado }}</p>

        @if (!$respondida)
            <form method="POST" action="{{ route('concursos.questoes.responder', ['questao' => $q->id]) }}" id="form-resp">
                @csrf
                @foreach ($ctxQuery as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <input type="hidden" name="tempo" id="tempo" value="">
                <fieldset class="opts">
                    <legend class="muted small" style="padding:0;margin-bottom:6px">Alternativas</legend>
                    @foreach ($q->alternativas as $a)
                        <label class="opt" data-letra="{{ $a->letra }}">
                            <input type="radio" name="resposta" value="{{ $a->letra }}" @checked(old('resposta') === $a->letra)>
                            <span class="l">{{ $q->tipo === 'CE' ? mb_substr($a->letra, 0, 1) : $a->letra }}</span>
                            <span class="tx">{{ $a->texto }}</span>
                            @if ($q->tipo !== 'CE')<button type="button" class="risca" title="Riscar alternativa" aria-label="Riscar {{ $a->letra }}">✕</button>@endif
                        </label>
                    @endforeach
                </fieldset>

                <fieldset class="certeza">
                    <legend>Quanta certeza?</legend>
                    @foreach ($certezas as $chave => $nome)
                        <label class="chip"><input type="radio" name="certeza" value="{{ $chave }}" @checked(old('certeza') === $chave)><span>{{ $nome }}</span></label>
                    @endforeach
                </fieldset>

                @if ($errors->any())
                    <div class="alerta" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="acoes">
                    <button class="btn main" id="btn-resp" type="submit">Responder</button>
                    <span class="fill"></span>
                    <a class="btn" href="{{ route('concursos.questoes.resolver', ['depois' => $q->id] + $ctxQuery) }}">Pular</a>
                </div>
                <div class="dica">Atalhos: <kbd>{{ $q->tipo === 'CE' ? 'C/E' : 'A–E' }}</kbd> alternativa · <kbd>1</kbd> <kbd>2</kbd> <kbd>3</kbd> certeza · <kbd>Enter</kbd> responder</div>
            </form>
        @else
            <div class="opts">
                @foreach ($q->alternativas as $a)
                    @php($estado = $a->letra === $q->gabarito ? 'ok' : ($a->letra === $t->resposta ? 'no' : 'fixa'))
                    <div class="opt {{ $estado }}">
                        <span class="l">{{ $q->tipo === 'CE' ? mb_substr($a->letra, 0, 1) : $a->letra }}</span>
                        <span class="tx">{{ $a->texto }}@if ($a->letra === $t->resposta) <span class="small muted">(sua resposta)</span>@endif</span>
                        <span></span>
                        @if ($res && ($exp = $res->explicacao($a->letra)))
                            <div class="exp">{{ $exp }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="hist">Sua certeza: {{ $certezas[$t->certeza] }}@if ($t->tempo_segundos) · {{ intdiv($t->tempo_segundos, 60) }}min{{ str_pad($t->tempo_segundos % 60, 2, '0', STR_PAD_LEFT) }}s @endif
                @if ($historico->isNotEmpty())
                    · antes: @foreach ($historico as $h){{ $h->correta ? '✓' : '✗' }}{{ $h->resposta }} ({{ $h->created_at->format('d/m') }})@if (!$loop->last), @endif @endforeach
                @endif
            </p>
        @endif
    </div>

    @if ($respondida)
        <div class="card sol">
            @if ($cls)
                <div class="ponto"><b>O que a questão cobra:</b> {{ $cls->ponto_cobrado }}
                    @if ($cls->distrator_letra && $cls->distrator_letra === $t->resposta && $cls->distrator_mecanismo)
                        <br><b>A armadilha em que você caiu:</b> {{ $cls->distrator_mecanismo }}.
                    @endif
                </div>
            @endif
            @if ($res)
                <h2>Resolução</h2>
                <p style="margin:0">{{ $res->resumo }}</p>
                @if ($res->fundamentacao)
                    <details><summary>Fundamentação completa</summary><div class="fund">{{ $res->fundamentacao }}</div></details>
                @endif
                @if ($cls?->temFlag('ilegivel'))
                    <div class="alerta">A resolução foi escrita sem a figura: confira no TEC.</div>
                @endif
                <p class="small muted" style="margin:12px 0 0">Resolução gerada por IA a partir do gabarito oficial. Viu algum erro? <a href="{{ $q->urlTec() }}" target="_blank" rel="noopener">Compare com os comentários do TEC</a>.</p>
            @else
                <h2>Resolução</h2>
                <p class="muted" style="margin:0">Esta questão ainda não tem resolução comentada. <a href="{{ $q->urlTec() }}" target="_blank" rel="noopener">Veja os comentários no TEC ↗</a></p>
            @endif
        </div>
    @endif
@endsection

@section('js')
<script>
(() => {
    const form = document.getElementById('form-resp');
    if (form) {
        const inicio = Date.now();
        const btn = document.getElementById('btn-resp');
        const ok = () => form.querySelector('[name=resposta]:checked') && form.querySelector('[name=certeza]:checked');
        const atualizar = () => { btn.disabled = !ok(); };
        form.addEventListener('change', atualizar);
        atualizar();
        form.addEventListener('submit', (e) => {
            if (!ok()) { e.preventDefault(); return; }
            document.getElementById('tempo').value = Math.round((Date.now() - inicio) / 1000);
        });
        form.querySelectorAll('.risca').forEach((b) => b.addEventListener('click', (e) => {
            e.preventDefault();
            b.closest('.opt').classList.toggle('riscada');
        }));
        const marcar = (sel) => { const el = form.querySelector(sel); if (el) { el.checked = true; atualizar(); } };
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey || e.altKey || e.target.matches('input[type=text], textarea')) return;
            const k = e.key.toUpperCase();
            const ce = { C: 'Certo', E: 'Errado' };
            if (form.querySelector('[name=resposta][value=Certo]') && ce[k]) marcar(`[name=resposta][value=${ce[k]}]`);
            else if ('ABCDE'.includes(k) && k.length === 1) marcar(`[name=resposta][value=${k}]`);
            else if (['1', '2', '3'].includes(e.key)) {
                const c = form.querySelectorAll('[name=certeza]')[+e.key - 1];
                if (c) { c.checked = true; atualizar(); }
            }
            else if (e.key === 'Enter' && ok()) { e.preventDefault(); form.requestSubmit(); }
        });
        return;
    }
    const prox = document.getElementById('proxima');
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const m = document.querySelector(`.motivos [data-tecla="${e.key}"]`);
        if (m) { m.click(); return; }
        if (prox && (e.key === 'Enter' || e.key === 'ArrowRight')) prox.click();
    });
})();
</script>
@endsection
