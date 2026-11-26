{{-- Itens do sumário de um nível (recursivo). $filhos: plano agrupado por parent_id; $pai: id do pai (0 = raiz). --}}
<ol>
    @foreach ($filhos->get($pai, collect()) as $c)
        <li data-cap="{{ $c['id'] }}" style="--nivel: {{ $c['nivel'] }}">
            <div class="cap-linha @if ($atual === $c['id']) atual @endif">
                <a href="{{ route('concursos.material.capitulo', [$material, $c['id']]) }}" @if ($atual === $c['id']) aria-current="page" @endif>
                    @php
                        $estadoCap = match (true) {
                            $c['errando'] > 0 => ['err', 'Ainda erro pontos deste capítulo'],
                            ($c['revisando'] ?? 0) > 0 => ['rev', 'Revisando'],
                            $c['pontos'] > 0 => ['dom', 'Tudo dominado'],
                            $c['questoes'] > 0 => ['q', 'Tem questões erradas ligadas'],
                            default => ['nada', 'Sem erros registrados'],
                        };
                    @endphp
                    <span class="cap-dot {{ $estadoCap[0] }}" title="{{ $estadoCap[1] }}" aria-hidden="true"></span>
                    <span class="cap-num">{{ $c['num'] }}</span>
                    <span class="cap-nome">{{ $c['titulo'] }}</span>
                    @if ($c['errando'])<span class="cap-err" title="{{ $c['errando'] }} {{ $c['errando'] === 1 ? 'ponto' : 'pontos' }} que ainda erro">{{ $c['errando'] }}</span>
                    @elseif ($c['questoes'])<span class="cap-q" title="{{ $c['questoes'] }} questões erradas ligadas">{{ $c['questoes'] }}</span>@endif
                    @if ($c['pontos'])<span class="cap-barra" title="{{ round(($c['dominado'] ?? 0) / $c['pontos'] * 100) }}% dominado" aria-hidden="true"><i style="width: {{ ($c['dominado'] ?? 0) / $c['pontos'] * 100 }}%"></i></span>@endif
                </a>
                <details class="cap-menu">
                    <summary aria-label="Opções do capítulo {{ $c['titulo'] }}">⋯</summary>
                    <div class="cap-menu-lista">
                        <button type="button" data-acao="renomear">Renomear</button>
                        <button type="button" data-acao="sub">Novo subcapítulo</button>
                        <button type="button" data-acao="subir">Mover para cima</button>
                        <button type="button" data-acao="descer">Mover para baixo</button>
                        <button type="button" data-acao="entrar">Virar subcapítulo do anterior</button>
                        <button type="button" data-acao="sair">Subir um nível</button>
                        <button type="button" data-acao="apagar" class="perigo">Apagar</button>
                    </div>
                </details>
            </div>
            @if ($filhos->has($c['id']))
                @include('concursos.material._sumario', ['pai' => $c['id']])
            @endif
        </li>
    @endforeach
</ol>
