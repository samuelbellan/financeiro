{{-- Peças visuais comuns da estante e da capa do livro (entra dentro do <style> do layout). --}}
    .etq { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; border-radius: 99px; padding: 2px 9px; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .etq.err { background: var(--no-soft); color: var(--no); }
    .etq.rev { background: var(--warn-soft); color: var(--warn); }
    .etq.dom { background: var(--ok-soft); color: var(--ok); }
    .etq.neutra { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }
    .seg { display: flex; height: 6px; border-radius: 99px; overflow: hidden; background: var(--line); }
    .seg > i { display: block; height: 100%; }
    .seg .err { background: var(--no); } .seg .rev { background: var(--warn); } .seg .dom { background: var(--ok); }
    .cabeca { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
    .cabeca h1 { font-size: 22px; margin: 0; flex: 1; min-width: 200px; letter-spacing: -.01em; }
    .secao-titulo { display: flex; align-items: baseline; gap: 4px 10px; flex-wrap: wrap; margin: 0 0 10px; }
    .secao-titulo h2 { font-size: 16px; margin: 0; flex: 1; }
    .elevar { transition: border-color .15s, transform .15s, box-shadow .15s; }
    .elevar:hover { border-color: color-mix(in srgb, var(--accent) 45%, var(--line)); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
    .btn.fantasma { background: transparent; }
    .btn.fantasma:hover { background: var(--card); }
    @media (prefers-reduced-motion: reduce) { .elevar, .elevar:hover { transition: none; transform: none; } }
