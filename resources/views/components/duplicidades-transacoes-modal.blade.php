<!-- Modal de Verificação de Duplicidades na Conta Corrente -->
<div id="duplicidadesModalBackdrop" class="duplicidades-modal-backdrop" style="display: none;">
    <div class="duplicidades-modal-card" role="dialog" aria-modal="true" aria-labelledby="duplicidadesModalTitle">
        <!-- Header -->
        <div class="duplicidades-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="duplicidades-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="duplicidadesModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff;">
                        Verificação de Duplicidades na Conta Corrente
                    </h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;">
                        Concilie lançamentos manuais com lançamentos importados de extrato bancário
                    </p>
                </div>
            </div>
            <button type="button" class="duplicidades-modal-close" onclick="closeDuplicidadesModal()" title="Fechar">&times;</button>
        </div>

        <!-- Body -->
        <div class="duplicidades-modal-body">
            <!-- Barra Superior de Resumo e Ação em Lote -->
            <div class="duplicidades-summary-bar">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span id="dupCountBadge" class="dup-summary-pill">
                        {{ count($duplicidades ?? []) }} {{ count($duplicidades ?? []) === 1 ? 'par suspeito' : 'pares suspeitos' }}
                    </span>
                    <span style="font-size: 0.85rem; color: #cbd5e1;">
                        Mês de Referência: <strong>{{ sprintf('%02d/%04d', $mes ?? now()->month, $ano ?? now()->year) }}</strong>
                    </span>
                </div>
                @if(!empty($duplicidades) && count($duplicidades) > 0)
                    <button type="button" id="btnMesclarTodas" onclick="mesclarTodasDuplicidades(this)" class="btn-dup-batch">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Mesclar Todas as Sugeridas ({{ count($duplicidades) }})</span>
                    </button>
                @endif
            </div>

            <!-- Lista de Pares de Duplicidades -->
            <div id="duplicidadesListContainer" class="duplicidades-list">
                @if(empty($duplicidades) || count($duplicidades) === 0)
                    <div class="dup-empty-state">
                        <div class="dup-empty-icon">✓</div>
                        <h4 style="margin: 0.5rem 0 0.25rem; color: #f8fafc; font-size: 1.05rem;">Nenhuma duplicidade detectada!</h4>
                        <p style="margin: 0; color: #94a3b8; font-size: 0.85rem;">
                            Todos os lançamentos do período parecem únicos e devidamente conciliados.
                        </p>
                    </div>
                @else
                    @foreach($duplicidades as $dup)
                        <div class="dup-pair-card" id="card-dup-{{ $dup['par_key'] }}">
                            <!-- Topo do Card: Motivo e Score -->
                            <div class="dup-pair-header">
                                <div class="dup-reason-tag">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                    <span>{{ $dup['motivo'] }}</span>
                                </div>
                                <div class="dup-score-tag {{ $dup['score'] >= 90 ? 'score-high' : 'score-med' }}">
                                    {{ $dup['score'] }}% afinidade
                                </div>
                            </div>

                            <!-- Comparação dos Dois Lançamentos -->
                            <div class="dup-comparison-grid">
                                <!-- Lançamento 1 -->
                                <div class="dup-item-box {{ $dup['sugestao']['manter_id'] == $dup['t1']['id'] ? 'box-suggested' : '' }}">
                                    <div class="dup-item-badge-row">
                                        <span class="dup-origin-badge {{ $dup['t1']['origem'] === 'Manual' ? 'badge-manual' : 'badge-extrato' }}">
                                            {{ $dup['t1']['origem'] }}
                                        </span>
                                        <span class="dup-item-date">{{ $dup['t1']['data'] }}</span>
                                    </div>
                                    <div class="dup-item-desc" title="{{ $dup['t1']['descricao'] }}">{{ $dup['t1']['descricao'] }}</div>
                                    <div class="dup-item-cat">
                                        <span>Categoria: <strong>{{ $dup['t1']['categoria'] }}</strong></span>
                                        @if($dup['t1']['subcategoria'])
                                            <span style="color: #94a3b8;">/ {{ $dup['t1']['subcategoria'] }}</span>
                                        @endif
                                    </div>
                                    <div class="dup-item-amount {{ $dup['t1']['tipo'] === 'receita' ? 'amount-credit' : 'amount-debit' }}">
                                        {{ $dup['t1']['tipo'] === 'receita' ? '+' : '-' }} R$ {{ number_format($dup['t1']['valor'], 2, ',', '.') }}
                                    </div>
                                    @if($dup['sugestao']['manter_id'] == $dup['t1']['id'])
                                        <div class="dup-keep-flag">⭐ Sugerido para Manter (Dados Enriquecidos)</div>
                                    @endif
                                </div>

                                <!-- Divisor com Ícone VS -->
                                <div class="dup-vs-divider">
                                    <div class="dup-vs-circle">VS</div>
                                    @if($dup['dias_diferenca'] > 0)
                                        <span class="dup-diff-text">±{{ $dup['dias_diferenca'] }} {{ $dup['dias_diferenca'] === 1 ? 'dia' : 'dias' }}</span>
                                    @else
                                        <span class="dup-diff-text">Mesmo dia</span>
                                    @endif
                                </div>

                                <!-- Lançamento 2 -->
                                <div class="dup-item-box {{ $dup['sugestao']['manter_id'] == $dup['t2']['id'] ? 'box-suggested' : '' }}">
                                    <div class="dup-item-badge-row">
                                        <span class="dup-origin-badge {{ $dup['t2']['origem'] === 'Manual' ? 'badge-manual' : 'badge-extrato' }}">
                                            {{ $dup['t2']['origem'] }}
                                        </span>
                                        <span class="dup-item-date">{{ $dup['t2']['data'] }}</span>
                                    </div>
                                    <div class="dup-item-desc" title="{{ $dup['t2']['descricao'] }}">{{ $dup['t2']['descricao'] }}</div>
                                    <div class="dup-item-cat">
                                        <span>Categoria: <strong>{{ $dup['t2']['categoria'] }}</strong></span>
                                        @if($dup['t2']['subcategoria'])
                                            <span style="color: #94a3b8;">/ {{ $dup['t2']['subcategoria'] }}</span>
                                        @endif
                                    </div>
                                    <div class="dup-item-amount {{ $dup['t2']['tipo'] === 'receita' ? 'amount-credit' : 'amount-debit' }}">
                                        {{ $dup['t2']['tipo'] === 'receita' ? '+' : '-' }} R$ {{ number_format($dup['t2']['valor'], 2, ',', '.') }}
                                    </div>
                                    @if($dup['sugestao']['manter_id'] == $dup['t2']['id'])
                                        <div class="dup-keep-flag">⭐ Sugerido para Manter (Dados Enriquecidos)</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Rodapé do Card: Sugestão e Ações -->
                            <div class="dup-pair-footer">
                                <div class="dup-footer-hint">
                                    💡 <strong>{{ $dup['sugestao']['resumo'] }}</strong> e vincular metadados do extrato bancário.
                                </div>
                                <div class="dup-footer-actions">
                                    <button type="button" 
                                            onclick="mesclarDuplicidade({{ $dup['sugestao']['manter_id'] }}, {{ $dup['sugestao']['excluir_id'] }}, '{{ $dup['par_key'] }}', this)" 
                                            class="btn-dup-merge">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                        <span>Mesclar Lançamentos</span>
                                    </button>

                                    <button type="button" 
                                            onclick="excluirLancamentoDuplicado({{ $dup['sugestao']['excluir_id'] }}, '{{ $dup['par_key'] }}', this)" 
                                            class="btn-dup-delete" 
                                            title="Excluir o lançamento duplicado diretamente">
                                        Excluir Duplicata
                                    </button>

                                    <button type="button" 
                                            onclick="ignorarDuplicidade({{ $dup['t1']['id'] }}, {{ $dup['t2']['id'] }}, '{{ $dup['par_key'] }}', this)" 
                                            class="btn-dup-ignore" 
                                            title="Não é duplicata; manter ambos separados">
                                        Não é duplicado
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos do Modal de Duplicidades */
.duplicidades-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    animation: dupFadeIn 0.2s ease-out;
}

@keyframes dupFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.duplicidades-modal-card {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 16px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
    overflow: hidden;
}

.duplicidades-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #1e293b;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #1e293b;
}

.duplicidades-icon-circle {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
}

.duplicidades-modal-close {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1.75rem;
    cursor: pointer;
    line-height: 1;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}

.duplicidades-modal-close:hover {
    color: #fff;
    background: #334155;
}

.duplicidades-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    background: #0b1120;
}

.duplicidades-summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 0.85rem 1.25rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
}

.dup-summary-pill {
    background: #d97706;
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
}

.btn-dup-batch {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.45rem 0.95rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
    transition: filter 0.15s;
}
.btn-dup-batch:hover {
    filter: brightness(1.1);
}

.duplicidades-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dup-pair-card {
    background: #162032;
    border: 1px solid #334155;
    border-radius: 12px;
    overflow: hidden;
    transition: border-color 0.15s;
}

.dup-pair-card:hover {
    border-color: #475569;
}

.dup-pair-header {
    padding: 0.65rem 1rem;
    background: #1e293b;
    border-bottom: 1px solid #2d3b50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.dup-reason-tag {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #fde68a;
    font-size: 0.8rem;
    font-weight: 600;
}

.dup-score-tag {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
}
.score-high {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.score-med {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.dup-comparison-grid {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0.75rem;
    padding: 1rem;
    align-items: center;
}

.dup-item-box {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 10px;
    padding: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    position: relative;
}

.dup-item-box.box-suggested {
    border-color: #059669;
    background: #06191b;
}

.dup-item-badge-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dup-origin-badge {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
    text-transform: uppercase;
}
.badge-manual {
    background: #312e81;
    color: #c7d2fe;
}
.badge-extrato {
    background: #064e3b;
    color: #a7f3d0;
}

.dup-item-date {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 600;
}

.dup-item-desc {
    font-size: 0.95rem;
    font-weight: 700;
    color: #f8fafc;
    word-break: break-word;
}

.dup-item-cat {
    font-size: 0.75rem;
    color: #cbd5e1;
}

.dup-item-amount {
    font-size: 1.05rem;
    font-weight: 800;
    margin-top: 0.25rem;
}
.amount-debit { color: #f87171; }
.amount-credit { color: #34d399; }

.dup-keep-flag {
    font-size: 0.7rem;
    color: #10b981;
    font-weight: 700;
    margin-top: 0.25rem;
}

.dup-vs-divider {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.dup-vs-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #334155;
    color: #e2e8f0;
    font-size: 0.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dup-diff-text {
    font-size: 0.7rem;
    color: #94a3b8;
    white-space: nowrap;
}

.dup-pair-footer {
    padding: 0.75rem 1rem;
    background: #141d2e;
    border-top: 1px solid #2d3b50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.dup-footer-hint {
    font-size: 0.8rem;
    color: #cbd5e1;
    flex: 1;
    min-width: 240px;
}

.dup-footer-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-dup-merge {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white;
    border: none;
    padding: 0.4rem 0.85rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
    transition: filter 0.15s;
}
.btn-dup-merge:hover { filter: brightness(1.1); }

.btn-dup-delete {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-dup-delete:hover { background: #dc2626; }

.btn-dup-ignore {
    background: transparent;
    color: #94a3b8;
    border: 1px solid #475569;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}
.btn-dup-ignore:hover {
    color: #f8fafc;
    border-color: #94a3b8;
}

.dup-empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #162032;
    border-radius: 12px;
    border: 1px dashed #334155;
}
.dup-empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    font-size: 1.5rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .dup-comparison-grid {
        grid-template-columns: 1fr;
    }
    .dup-vs-divider {
        flex-direction: row;
        margin: 0.5rem 0;
    }
    .dup-pair-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .dup-footer-actions {
        justify-content: stretch;
    }
    .btn-dup-merge, .btn-dup-delete, .btn-dup-ignore {
        flex: 1;
        justify-content: center;
    }
}
</style>

<script>
function openDuplicidadesModal(focusId) {
    const modal = document.getElementById('duplicidadesModalBackdrop');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        if (focusId) {
            setTimeout(() => {
                const targetCard = document.querySelector(`[id*="${focusId}"]`);
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetCard.style.outline = '2px solid #f59e0b';
                    setTimeout(() => targetCard.style.outline = 'none', 3000);
                }
            }, 100);
        }
    }
}

function closeDuplicidadesModal() {
    const modal = document.getElementById('duplicidadesModalBackdrop');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('duplicidadesModalBackdrop');
        if (modal && modal.style.display === 'flex') {
            closeDuplicidadesModal();
        }
    }
});

async function mesclarDuplicidade(manterId, excluirId, parKey, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>Mesclando...</span>';
    }

    try {
        const response = await fetch("{{ route('financas.duplicidades.mesclar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ manter_id: manterId, excluir_id: excluirId })
        });

        const data = await response.json();

        if (response.ok && data.sucesso) {
            const card = document.getElementById(`card-dup-${parKey}`);
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    updateDupCountAfterAction();
                }, 300);
            }
        } else {
            alert(data.mensagem || 'Erro ao mesclar lançamentos.');
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Erro ao conectar com o servidor.');
        if (btn) btn.disabled = false;
    }
}

async function excluirLancamentoDuplicado(transacaoId, parKey, btn) {
    if (!confirm('Deseja realmente excluir este lançamento duplicado?')) {
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = 'Excluindo...';
    }

    try {
        const response = await fetch(`/financas/${transacaoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const card = document.getElementById(`card-dup-${parKey}`);
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    updateDupCountAfterAction();
                }, 300);
            }
        } else {
            alert('Não foi possível excluir o lançamento.');
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Erro ao excluir lançamento.');
        if (btn) btn.disabled = false;
    }
}

async function ignorarDuplicidade(idA, idB, parKey, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = 'Ignorando...';
    }

    try {
        const response = await fetch("{{ route('financas.duplicidades.ignorar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id_a: idA, id_b: idB })
        });

        const data = await response.json();

        if (response.ok && data.sucesso) {
            const card = document.getElementById(`card-dup-${parKey}`);
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    updateDupCountAfterAction();
                }, 300);
            }
        } else {
            alert(data.mensagem || 'Erro ao ignorar par.');
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Erro ao conectar.');
        if (btn) btn.disabled = false;
    }
}

async function mesclarTodasDuplicidades(btn) {
    if (!confirm('Deseja mesclar todas as duplicidades sugeridas automaticamente? Os dados enriquecidos serão mantidos e as duplicatas serão removidas.')) {
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>Mesclando todas...</span>';
    }

    try {
        const response = await fetch("{{ route('financas.duplicidades.mesclar-todas') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                mes: {{ $mes ?? now()->month }},
                ano: {{ $ano ?? now()->year }}
            })
        });

        const data = await response.json();

        if (response.ok && data.sucesso) {
            window.location.reload();
        } else {
            alert(data.mensagem || 'Erro ao mesclar duplicidades.');
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Erro ao conectar com o servidor.');
        if (btn) btn.disabled = false;
    }
}

function updateDupCountAfterAction() {
    const remainingCards = document.querySelectorAll('.dup-pair-card');
    const badge = document.getElementById('dupCountBadge');
    const headerBtnBadge = document.getElementById('headerDupCountBadge');
    const alertBox = document.getElementById('alertDuplicidadesBox');

    if (badge) {
        badge.textContent = `${remainingCards.length} ${remainingCards.length === 1 ? 'par suspeito' : 'pares suspeitos'}`;
    }

    if (headerBtnBadge) {
        if (remainingCards.length > 0) {
            headerBtnBadge.textContent = remainingCards.length;
            headerBtnBadge.style.display = 'inline-block';
        } else {
            headerBtnBadge.style.display = 'none';
        }
    }

    if (remainingCards.length === 0) {
        if (alertBox) alertBox.style.display = 'none';
        const container = document.getElementById('duplicidadesListContainer');
        if (container) {
            container.innerHTML = `
                <div class="dup-empty-state">
                    <div class="dup-empty-icon">✓</div>
                    <h4 style="margin: 0.5rem 0 0.25rem; color: #f8fafc; font-size: 1.05rem;">Todas as duplicidades foram resolvidas!</h4>
                    <p style="margin: 0; color: #94a3b8; font-size: 0.85rem;">
                        Recarregando a página para atualizar o saldo e o extrato...
                    </p>
                </div>
            `;
            setTimeout(() => window.location.reload(), 1200);
        }
    }
}
</script>
