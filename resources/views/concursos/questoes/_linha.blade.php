<div class="row {{ $sub ? 'sub' : '' }}">
    <div class="nome">{{ $a->nome }}</div>
    <div class="acoes">
        @if ($s['respondidas'] < $s['total'])
            <a href="{{ route('concursos.questoes.resolver', ['assunto' => $a->id, 'filtro' => 'nao']) }}">Não resp.</a>
        @endif
        @if ($s['respondidas'] > $s['acertos'])
            <a href="{{ route('concursos.questoes.resolver', ['assunto' => $a->id, 'filtro' => 'erradas']) }}">Erradas</a>
        @endif
        <a href="{{ route('concursos.questoes.resolver', ['assunto' => $a->id, 'filtro' => 'todas']) }}">Todas</a>
    </div>
    <div class="num">
        <span>{{ $s['respondidas'] }}/{{ $s['total'] }} feitas</span>
        @if ($s['respondidas'])
            <span class="bar" title="acerto"><span style="width: {{ round(100 * $s['acertos'] / $s['respondidas']) }}%"></span></span>
            <span>{{ round(100 * $s['acertos'] / $s['respondidas']) }}% de acerto</span>
        @endif
    </div>
</div>
