@extends('concursos.layout')

@section('title', $lei['sigla'])

@php
    $rotulos = \App\Models\Concursos\MaterialPonto::STATUS;
@endphp

@section('css')
    .cab h1 { font-size: 22px; margin: 0 0 4px; }
    .cab .ementa { font-size: 14px; color: var(--muted); margin: 0 0 10px; }
    .ferr { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; font-size: 14px; }
    .ferr input[type=text] { width: 120px; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 10px; font: inherit; }
    .ferr label.tg { display: inline-flex; gap: 6px; align-items: center; cursor: pointer; }
    .texto { font-size: 16px; line-height: 1.65; }
    .texto h1, .texto h2, .texto h3, .texto h4, .texto h5, .texto h6 { font-size: 15px; text-transform: none; text-align: center; margin: 28px 0 8px; color: var(--muted); letter-spacing: .02em; }
    .art { scroll-margin-top: 16px; padding: 2px 0; }
    .art p { margin: 0 0 8px; }
    .art:target { background: var(--warn-soft); border-radius: 8px; box-shadow: 0 0 0 8px var(--warn-soft); }
    .art.citado { border-left: 3px solid var(--accent); padding-left: 12px; margin-left: -15px; }
    .notas { margin: 4px 0 12px; display: flex; flex-direction: column; gap: 4px; }
    .nota { font-size: 13px; background: var(--accent-soft); border-radius: 8px; padding: 6px 10px; text-decoration: none; color: var(--ink); }
    .nota b { color: var(--accent); }
    .nota .st { font-size: 12px; color: var(--muted); }
    .so-citados .texto > :not(.citado) { display: none; }
    .so-citados .texto > .citado { margin-bottom: 20px; }
    .fonte { font-size: 13px; color: var(--muted); }
@endsection

@section('content')
<header class="card cab">
    <h1>{{ $lei['nome'] }}</h1>
    @if (!empty($meta['ementa']))<p class="ementa">{{ $meta['ementa'] }}</p>@endif
    <div class="ferr">
        <form id="ir" class="ferr" onsubmit="return false">
            <input type="text" inputmode="text" placeholder="Ir ao art." aria-label="Ir ao artigo">
            <button class="btn" type="submit">Ir</button>
        </form>
        @if ($citados->isNotEmpty())
            <label class="tg"><input type="checkbox" id="so-citados"> Só os {{ $citados->count() }} artigos do meu resumo</label>
        @endif
    </div>
</header>

<div class="card">
    <div class="texto">
        @foreach ($blocos as $b)
            @if ($b['tipo'] === 'titulo')
                {!! $acervo->mdParaHtml($b['md']) !!}
            @elseif ($b['tipo'] === 'artigo')
                @php $notas = $citados[$b['ancora']] ?? []; @endphp
                <section class="art @if ($notas) citado @endif" id="{{ $b['ancora'] }}">
                    {!! $acervo->mdParaHtml($b['md']) !!}
                    @if ($notas)
                        <div class="notas">
                            @foreach ($notas as $n)
                                <a class="nota" href="{{ $n['url'] }}"><b>No resumo:</b> {{ $n['titulo'] }}@if ($n['status']) <span class="st">· {{ $rotulos[$n['status']] ?? $n['status'] }}</span>@endif</a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @else
                <div>{!! $acervo->mdParaHtml($b['md']) !!}</div>
            @endif
        @endforeach
    </div>
</div>
<p class="fonte">Texto de <a href="{{ $fonte }}" target="_blank" rel="noopener">ctresb/leis</a>@if (!empty($meta['fonte'])), convertido de <a href="{{ $meta['fonte'] }}" target="_blank" rel="noopener">{{ parse_url($meta['fonte'], PHP_URL_HOST) }}</a>@endif. Conversão automática: confira a fonte oficial antes de citar.</p>
@endsection

@section('js')
<script>
(() => {
    const prefixo = @json($lei['prefixo'] ?? '');
    document.getElementById('ir').addEventListener('submit', () => {
        const v = document.querySelector('#ir input').value.trim().toLowerCase().replace(/[^0-9a-z-]/g, '');
        const el = v && document.getElementById(`${prefixo}art-${v}`);
        if (el) { location.hash = el.id; } else { alert('Artigo não encontrado nesta lei.'); }
    });
    const so = document.getElementById('so-citados');
    if (so) so.addEventListener('change', () => document.body.classList.toggle('so-citados', so.checked));
})();
</script>
@endsection
