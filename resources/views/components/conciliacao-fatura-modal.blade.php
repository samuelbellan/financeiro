<!-- Modal de Conciliação de Fatura Fechada -->
<div id="conciliacaoFaturaModal" class="conciliacao-modal-backdrop" style="display: none;">
    <div class="conciliacao-modal-card">
        <!-- Cabeçalho -->
        <div class="conciliacao-modal-header">
            <div class="conciliacao-header-info">
                <div class="conciliacao-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                        <path d="M16 14h.01"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="conciliacao-title">Conciliação de Fatura Fechada</h3>
                    <p class="conciliacao-subtitle">Cruze a fatura do banco com os lançamentos do sistema para auditar e identificar divergências.</p>
                </div>
            </div>
            <button type="button" class="conciliacao-close-btn" onclick="closeConciliacaoModal()" title="Fechar">&times;</button>
        </div>

        <div class="conciliacao-modal-body">
            <!-- ETAPA 1: Formulário de Envio -->
            <div id="conciliacaoUploadStep">
                <form id="conciliacaoForm" onsubmit="handleConciliacaoSubmit(event)" enctype="multipart/form-data">
                    @csrf
                    <div class="conciliacao-form-grid">
                        <div class="conciliacao-form-group">
                            <label for="conciliaCartaoSelect">Cartão de Crédito</label>
                            <select id="conciliaCartaoSelect" name="cartao_id" class="conciliacao-select" required>
                                @foreach($cartoes as $c)
                                    <option value="{{ $c->id }}" data-nome="{{ $c->nome }}">
                                        {{ $c->nome }} (Vencimento dia {{ $c->dia_vencimento ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conciliacao-form-group">
                            <label for="conciliaMesSelect">Mês de Referência</label>
                            <select id="conciliaMesSelect" name="mes" class="conciliacao-select" required>
                                @php
                                    $mesesNomes = [
                                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                    ];
                                    $selectedMes = $currentMonth ?? now()->month;
                                    $selectedAno = $currentYear ?? now()->year;
                                @endphp
                                @foreach($mesesNomes as $num => $nome)
                                    <option value="{{ $num }}" {{ $num == $selectedMes ? 'selected' : '' }}>
                                        {{ $nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conciliacao-form-group">
                            <label for="conciliaAnoSelect">Ano</label>
                            <select id="conciliaAnoSelect" name="ano" class="conciliacao-select" required>
                                @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == $selectedAno ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Dropzone de Arquivo -->
                    <div class="conciliacao-dropzone" id="conciliacaoDropzone" onclick="document.getElementById('conciliaArquivoInput').click()">
                        <input type="file" id="conciliaArquivoInput" name="arquivo" accept=".pdf,.xlsx,.csv,.txt,.ofx" style="display: none;" onchange="handleConciliaFileSelected(this)" required>
                        <div class="conciliacao-dropzone-content">
                            <div class="conciliacao-dropzone-icon">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <p class="conciliacao-dropzone-text" id="conciliaDropzoneText">
                                <strong>Clique para escolher</strong> ou arraste o arquivo da sua fatura aqui
                            </p>
                            <p class="conciliacao-dropzone-hint">Formatos suportados: <strong>PDF</strong>, <strong>Excel (.xlsx)</strong>, <strong>CSV</strong> ou <strong>OFX</strong></p>
                            <div id="conciliaFileInfo" class="concilia-file-selected" style="display: none;">
                                <span class="file-name" id="conciliaSelectedFileName"></span>
                                <span class="file-size" id="conciliaSelectedFileSize"></span>
                            </div>
                        </div>
                    </div>

                    <div id="conciliaErrorAlert" class="conciliacao-alert conciliacao-alert-danger" style="display: none;"></div>

                    <div class="conciliacao-modal-footer">
                        <button type="button" class="conciliacao-btn-secondary" onclick="closeConciliacaoModal()">Cancelar</button>
                        <button type="submit" id="conciliaSubmitBtn" class="conciliacao-btn-primary">
                            <span>Processar e Auditar Fatura</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 2: Loading State -->
            <div id="conciliacaoLoadingStep" style="display: none; text-align: center; padding: 3rem 1.5rem;">
                <div class="conciliacao-spinner"></div>
                <h4 style="margin: 1rem 0 0.5rem 0; font-size: 1.15rem; color: #1e293b;">Cruzando Dados da Fatura...</h4>
                <p style="color: #64748b; font-size: 0.9rem; max-width: 420px; margin: 0 auto;">
                    Analisando compras, parcelas, datas de vencimento e calculando divergências financeiras. Aguarde alguns instantes.
                </p>
            </div>

            <!-- ETAPA 3: Painel Comparativo e Relatório de Divergências -->
            <div id="conciliacaoResultsStep" style="display: none;">
                <!-- Cabeçalho de Ações e Resumo -->
                <div class="conciliacao-results-bar">
                    <div>
                        <span class="badge-cartao" id="resCartaoBadge">VUON</span>
                        <span class="badge-periodo" id="resPeriodoBadge">Outubro / 2026</span>
                    </div>
                    <div style="display: flex; gap: 0.6rem;">
                        <a id="btnDownloadConciliaPdf" href="{{ route('cartoes.conciliar.pdf') }}" target="_blank" class="conciliacao-btn-export">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span>Baixar Relatório em PDF</span>
                        </a>
                        <button type="button" class="conciliacao-btn-secondary" onclick="resetConciliacaoModal()">
                            <span>Nova Conciliação</span>
                        </button>
                    </div>
                </div>

                <div id="resAviso" style="display: none; margin: 0 0 1rem; padding: 0.8rem 1rem; border-radius: 10px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.45); color: #f59e0b; font-weight: 600;"></div>

                <!-- Grid de Métricas Totais -->
                <div class="conciliacao-metrics-grid">
                    <div class="conciliacao-metric-card">
                        <div class="metric-label">Total Fatura (Banco)</div>
                        <div class="metric-value metric-primary" id="resTotalFatura">R$ 0,00</div>
                        <div class="metric-sub" id="resQtdFatura">0 compras lidas</div>
                    </div>

                    <div class="conciliacao-metric-card">
                        <div class="metric-label">Total Lançado (Sistema)</div>
                        <div class="metric-value" id="resTotalSistema">R$ 0,00</div>
                        <div class="metric-sub" id="resQtdSistema">0 parcelas cadastradas</div>
                    </div>

                    <div class="conciliacao-metric-card">
                        <div class="metric-label">Diferença Total</div>
                        <div class="metric-value" id="resDiferenca">R$ 0,00</div>
                        <div class="metric-sub" id="resStatusBadge">Status</div>
                    </div>

                    <div class="conciliacao-metric-card">
                        <div class="metric-label">Auditoria de Itens</div>
                        <div class="metric-value metric-success" id="resItensOk">0 OK</div>
                        <div class="metric-sub" id="resItensDivergentes">0 divergências</div>
                    </div>
                </div>

                <!-- Abas de Navegação -->
                <div class="conciliacao-tabs-nav">
                    <button type="button" class="conciliacao-tab-btn active" data-tab="tabFaltando" onclick="switchConciliaTab('tabFaltando', this)">
                        🔴 Faltando no Sistema (<span id="badgeCountFaltando">0</span>)
                    </button>
                    <button type="button" class="conciliacao-tab-btn" data-tab="tabSobrando" onclick="switchConciliaTab('tabSobrando', this)">
                        🟡 Sobrando no Sistema (<span id="badgeCountSobrando">0</span>)
                    </button>
                    <button type="button" class="conciliacao-tab-btn" data-tab="tabDivergencias" onclick="switchConciliaTab('tabDivergencias', this)">
                        🟠 Diferença de Valor (<span id="badgeCountDivergencias">0</span>)
                    </button>
                    <button type="button" class="conciliacao-tab-btn" data-tab="tabConciliados" onclick="switchConciliaTab('tabConciliados', this)">
                        🟢 Conciliados (<span id="badgeCountConciliados">0</span>)
                    </button>
                    <button type="button" class="conciliacao-tab-btn" data-tab="tabPagamentos" onclick="switchConciliaTab('tabPagamentos', this)">
                        ℹ️ Pagamentos & Estornos (<span id="badgeCountPagamentos">0</span>)
                    </button>
                </div>

                <!-- Conteúdo das Abas -->
                <div class="conciliacao-tabs-container">
                    <!-- ABA 1: FALTANDO NO SISTEMA -->
                    <div id="tabFaltando" class="conciliacao-tab-content active">
                        <div class="tab-hint hint-danger">
                            Compras cobradas na fatura do banco que você <strong>não cadastrou no sistema</strong>.
                        </div>
                        <div class="table-responsive">
                            <table class="concilia-table">
                                <thead>
                                    <tr>
                                        <th style="width: 95px;">Data</th>
                                        <th>Estabelecimento na Fatura</th>
                                        <th style="width: 80px;" class="text-center">Parcela</th>
                                        <th style="width: 110px;" class="text-right">Valor Fatura</th>
                                        <th style="width: 100px;" class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyFaltando"></tbody>
                            </table>
                        </div>
                        <div id="emptyFaltando" class="empty-state" style="display: none;">
                            🎉 Nenhuma compra faltando! Todas as compras da fatura constam no sistema.
                        </div>
                    </div>

                    <!-- ABA 2: SOBRANDO NO SISTEMA -->
                    <div id="tabSobrando" class="conciliacao-tab-content">
                        <div class="tab-hint hint-warning">
                            Lançamentos cadastrados no sistema para este mês que <strong>não vieram cobrados na fatura</strong> (podem ter caído para o mês seguinte ou cancelados).
                        </div>
                        <div class="table-responsive">
                            <table class="concilia-table">
                                <thead>
                                    <tr>
                                        <th style="width: 95px;">Data Compra</th>
                                        <th style="width: 95px;">Vencimento</th>
                                        <th>Descrição no Sistema</th>
                                        <th>Categoria</th>
                                        <th style="width: 80px;" class="text-center">Parcela</th>
                                        <th style="width: 110px;" class="text-right">Valor Sistema</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodySobrando"></tbody>
                            </table>
                        </div>
                        <div id="emptySobrando" class="empty-state" style="display: none;">
                            🎉 Nenhum lançamento sobrando no sistema para este mês.
                        </div>
                    </div>

                    <!-- ABA 3: DIVERGÊNCIAS DE VALOR -->
                    <div id="tabDivergencias" class="conciliacao-tab-content">
                        <div class="tab-hint hint-orange">
                            Estabelecimentos correspondentes que possuem <strong>diferença no valor cobrado</strong>.
                        </div>
                        <div class="table-responsive">
                            <table class="concilia-table">
                                <thead>
                                    <tr>
                                        <th style="width: 95px;">Data</th>
                                        <th>Estabelecimento</th>
                                        <th style="width: 110px;" class="text-right">Valor Fatura</th>
                                        <th style="width: 110px;" class="text-right">Valor Sistema</th>
                                        <th style="width: 110px;" class="text-right">Diferença</th>
                                        <th style="width: 130px;" class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDivergencias"></tbody>
                            </table>
                        </div>
                        <div id="emptyDivergencias" class="empty-state" style="display: none;">
                            🎉 Nenhuma divergência de valor encontrada.
                        </div>
                    </div>

                    <!-- ABA 4: CONCILIADOS 100% -->
                    <div id="tabConciliados" class="conciliacao-tab-content">
                        <div class="tab-hint hint-success">
                            Lançamentos que <strong>conferem perfeitamente</strong> em valor e período.
                        </div>
                        <div class="table-responsive">
                            <table class="concilia-table">
                                <thead>
                                    <tr>
                                        <th style="width: 95px;">Data Compra</th>
                                        <th>Fatura (Banco)</th>
                                        <th>Sistema</th>
                                        <th style="width: 80px;" class="text-center">Parcela</th>
                                        <th style="width: 110px;" class="text-right">Valor</th>
                                        <th style="width: 80px;" class="text-center">Certeza</th>
                                        <th style="width: 130px;" class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyConciliados"></tbody>
                            </table>
                        </div>
                        <div id="emptyConciliados" class="empty-state" style="display: none;">
                            Nenhum item conciliado diretamente.
                        </div>
                    </div>

                    <!-- ABA 5: PAGAMENTOS E ESTORNOS -->
                    <div id="tabPagamentos" class="conciliacao-tab-content">
                        <div class="tab-hint hint-info">
                            Linhas informativas de pagamento de fatura anterior, créditos ou estornos.
                        </div>
                        <div class="table-responsive">
                            <table class="concilia-table">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Data</th>
                                        <th>Descrição</th>
                                        <th style="width: 120px;" class="text-right">Valor Creditado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPagamentos"></tbody>
                            </table>
                        </div>
                        <div id="emptyPagamentos" class="empty-state" style="display: none;">
                            Nenhum pagamento ou estorno identificado na fatura.
                        </div>
                    </div>
                </div>

                <div class="conciliacao-modal-footer" style="margin-top: 1.5rem; justify-content: flex-end;">
                    <button type="button" class="conciliacao-btn-secondary" onclick="closeConciliacaoModal()">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mini-Modal: Lançar Compra Faltante -->
<div id="quickLaunchModal" class="concilia-mini-backdrop" style="display: none;">
    <div class="concilia-mini-card">
        <div class="concilia-mini-header">
            <h4>+ Lançar Compra no Cartão</h4>
            <button type="button" class="concilia-close-mini" onclick="closeQuickLaunchModal()">&times;</button>
        </div>
        <form id="quickLaunchForm" onsubmit="submitQuickLaunch(event)">
            <input type="hidden" id="qlCartaoId" name="cartao_id">
            <input type="hidden" id="qlItemIndex" name="item_index">

            <div class="concilia-mini-body">
                <div class="concilia-mini-field">
                    <label>Cartão</label>
                    <input type="text" id="qlCartaoNome" class="concilia-mini-input" readonly style="background: #f1f5f9; cursor: not-allowed;">
                </div>
                <div class="concilia-mini-field">
                    <label>Estabelecimento / Descrição</label>
                    <input type="text" id="qlDescricao" name="descricao" class="concilia-mini-input" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="concilia-mini-field">
                        <label>Data da Compra</label>
                        <input type="date" id="qlData" name="data_compra" class="concilia-mini-input" required>
                    </div>
                    <div class="concilia-mini-field">
                        <label>Valor (R$)</label>
                        <input type="number" step="0.01" id="qlValor" name="valor_total" class="concilia-mini-input" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.75rem;">
                    <div class="concilia-mini-field">
                        <label>Categoria</label>
                        <select id="qlCategoria" name="categoria" class="concilia-mini-input">
                            <option value="Geral">Geral</option>
                            @if(isset($userCategorias))
                                @foreach($userCategorias as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="concilia-mini-field">
                        <label>Parcelas</label>
                        <div style="display: flex; gap: 0.4rem;">
                            <select id="qlTipo" name="tipo" class="concilia-mini-input" onchange="toggleQlParcelas(this.value)">
                                <option value="avista">À vista (1x)</option>
                                <option value="parcelada">Parcelado</option>
                            </select>
                            <input type="number" id="qlParcelas" name="numero_parcelas" class="concilia-mini-input" value="1" min="1" max="72" style="width: 60px; display: none;">
                        </div>
                    </div>
                </div>
                <div id="qlAlert" class="conciliacao-alert conciliacao-alert-danger" style="display: none; margin-top: 0.75rem;"></div>
            </div>
            <div class="concilia-mini-footer">
                <button type="button" class="conciliacao-btn-secondary" onclick="closeQuickLaunchModal()">Cancelar</button>
                <button type="submit" id="qlSubmitBtn" class="conciliacao-btn-primary">
                    <span id="qlBtnText">Confirmar e Lançar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mini-Modal: Resolver Diferença de Valor / Centavos -->
<div id="quickDiffModal" class="concilia-mini-backdrop" style="display: none;">
    <div class="concilia-mini-card">
        <div class="concilia-mini-header">
            <h4>Ajustar Diferença de Valor</h4>
            <button type="button" class="concilia-close-mini" onclick="closeQuickDiffModal()">&times;</button>
        </div>
        <div class="concilia-mini-body">
            <div class="diff-summary-box">
                <div class="diff-item">
                    <span class="diff-lbl">Fatura:</span>
                    <strong id="qdValFatura" class="diff-val">R$ 0,00</strong>
                </div>
                <div class="diff-item">
                    <span class="diff-lbl">Sistema:</span>
                    <strong id="qdValSistema" class="diff-val">R$ 0,00</strong>
                </div>
                <div class="diff-item diff-item-highlight">
                    <span class="diff-lbl">Diferença:</span>
                    <strong id="qdValDiferenca" class="diff-val">R$ 0,00</strong>
                </div>
            </div>

            <p style="font-size: 0.85rem; color: #475569; margin: 0.85rem 0 0.65rem;">Como deseja resolver essa diferença?</p>

            <div class="diff-choice-card" onclick="executarAjusteParcela()">
                <div class="diff-choice-icon" style="background: #e0f2fe; color: #0284c7;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 0.9rem;">Ajustar Parcela no Sistema</strong>
                    <p style="margin: 0.2rem 0 0; font-size: 0.78rem; color: #64748b;">
                        Atualiza o valor da parcela no sistema para igualar exatamente à fatura (elimina a diferença na compra original).
                    </p>
                </div>
            </div>

            <div class="diff-choice-card" onclick="executarLancamentoDiferenca()" style="margin-top: 0.65rem;">
                <div class="diff-choice-icon" style="background: #fef3c7; color: #d97706;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 0.9rem;">Criar Lançamento Avulso da Diferença</strong>
                    <p style="margin: 0.2rem 0 0; font-size: 0.78rem; color: #64748b;">
                        Cria um novo lançamento com o valor da diferença (ex: ajuste de IOF, encargos ou centavos).
                    </p>
                </div>
            </div>

            <div id="qdAlert" class="conciliacao-alert conciliacao-alert-danger" style="display: none; margin-top: 0.85rem;"></div>
        </div>
        <div class="concilia-mini-footer">
            <button type="button" class="conciliacao-btn-secondary" onclick="closeQuickDiffModal()">Cancelar</button>
        </div>
    </div>
</div>

<style>
/* Estilos do Modal de Conciliação */
.conciliacao-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeInModal 0.2s ease-out;
}

@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}

.conciliacao-modal-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 960px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.conciliacao-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}

.conciliacao-header-info {
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.conciliacao-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.conciliacao-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
}

.conciliacao-subtitle {
    margin: 0.2rem 0 0 0;
    font-size: 0.85rem;
    color: #64748b;
}

.conciliacao-close-btn {
    background: transparent;
    border: none;
    font-size: 1.8rem;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 0 0.5rem;
    transition: color 0.15s;
}

.conciliacao-close-btn:hover {
    color: #0f172a;
}

.conciliacao-modal-body {
    padding: 1.75rem;
    overflow-y: auto;
    flex: 1;
}

/* Grid de Form */
.conciliacao-form-grid {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1fr;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.conciliacao-form-group label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.4rem;
}

.conciliacao-select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #1e293b;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s;
}

.conciliacao-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Dropzone */
.conciliacao-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    padding: 2.25rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 1.5rem;
}

.conciliacao-dropzone:hover, .conciliacao-dropzone.dragover {
    border-color: #6366f1;
    background: #f5f3ff;
}

.conciliacao-dropzone-icon {
    margin-bottom: 0.6rem;
}

.conciliacao-dropzone-text {
    margin: 0;
    font-size: 0.95rem;
    color: #334155;
}

.conciliacao-dropzone-hint {
    margin: 0.4rem 0 0 0;
    font-size: 0.8rem;
    color: #64748b;
}

.concilia-file-selected {
    margin-top: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e0e7ff;
    color: #3730a3;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Spinner */
.conciliacao-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #6366f1;
    border-radius: 50%;
    margin: 0 auto;
    animation: spinModal 0.8s linear infinite;
}

@keyframes spinModal {
    to { transform: rotate(360deg); }
}

/* Resultados Bar */
.conciliacao-results-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.badge-cartao {
    background: #1e293b;
    color: #ffffff;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 700;
    margin-right: 0.4rem;
}

.badge-periodo {
    background: #e2e8f0;
    color: #334155;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Grid de Métricas */
.conciliacao-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.85rem;
    margin-bottom: 1.5rem;
}

.conciliacao-metric-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.85rem 1rem;
    text-align: center;
}

.metric-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
}

.metric-primary { color: #4f46e5; }
.metric-success { color: #10b981; }
.metric-danger { color: #ef4444; }
.metric-warning { color: #f59e0b; }

.metric-sub {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Abas */
.conciliacao-tabs-nav {
    display: flex;
    gap: 0.4rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1rem;
    overflow-x: auto;
}

.conciliacao-tab-btn {
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.65rem 0.95rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    margin-bottom: -2px;
    transition: all 0.15s;
}

.conciliacao-tab-btn:hover {
    color: #1e293b;
}

.conciliacao-tab-btn.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}

.conciliacao-tab-content {
    display: none;
}

.conciliacao-tab-content.active {
    display: block;
}

.tab-hint {
    padding: 0.65rem 1rem;
    border-radius: 8px;
    font-size: 0.83rem;
    margin-bottom: 1rem;
}

.hint-danger { background: #fee2e2; color: #991b1b; }
.hint-warning { background: #fef3c7; color: #92400e; }
.hint-orange { background: #ffedd5; color: #9a3412; }
.hint-success { background: #dcfce7; color: #166534; }
.hint-info { background: #e0e7ff; color: #3730a3; }

/* Tabelas de Resultados */
.table-responsive {
    overflow-x: auto;
}

.concilia-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.concilia-table th {
    background: #f1f5f9;
    color: #475569;
    padding: 0.6rem 0.75rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    text-align: left;
    border-bottom: 1px solid #cbd5e1;
}

.concilia-table td {
    padding: 0.65rem 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}

.concilia-table tr:hover td {
    background: #f8fafc;
}

.empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #10b981;
    font-weight: 600;
    font-size: 0.95rem;
}

/* Botões */
.conciliacao-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
}

.conciliacao-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0.7rem 1.4rem;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.15s;
}

.conciliacao-btn-primary:hover {
    background: #4338ca;
}

.conciliacao-btn-secondary {
    background: #e2e8f0;
    color: #334155;
    border: none;
    border-radius: 8px;
    padding: 0.7rem 1.25rem;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.15s;
}

.conciliacao-btn-secondary:hover {
    background: #cbd5e1;
}

.conciliacao-btn-export {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: #10b981;
    color: #ffffff;
    text-decoration: none;
    border-radius: 8px;
    padding: 0.65rem 1.15rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: background 0.15s;
}

.conciliacao-btn-export:hover {
    background: #059669;
}

.conciliacao-alert {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.conciliacao-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

/* Estilos dos Mini-Modals de Lançamento e Diferença */
.concilia-mini-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(3px);
    z-index: 10050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeInModal 0.15s ease-out;
}
.concilia-mini-card {
    background: #ffffff;
    border-radius: 14px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.concilia-mini-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.concilia-mini-header h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
}
.concilia-close-mini {
    background: transparent;
    border: none;
    font-size: 1.6rem;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 0 0.3rem;
    transition: color 0.15s;
}
.concilia-close-mini:hover {
    color: #0f172a;
}
.concilia-mini-body {
    padding: 1.25rem;
}
.concilia-mini-field {
    margin-bottom: 0.85rem;
}
.concilia-mini-field label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.35rem;
}
.concilia-mini-input {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.88rem;
    color: #1e293b;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s;
}
.concilia-mini-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.concilia-mini-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    padding: 0.85rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}
.diff-summary-box {
    display: flex;
    justify-content: space-between;
    background: #f1f5f9;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
}
.diff-item {
    display: flex;
    flex-direction: column;
}
.diff-lbl {
    font-size: 0.72rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
}
.diff-val {
    font-size: 0.95rem;
    color: #1e293b;
    margin-top: 0.15rem;
}
.diff-item-highlight .diff-val {
    color: #ef4444;
}
.diff-choice-card {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 0.85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.diff-choice-card:hover {
    border-color: #4f46e5;
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.diff-choice-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.btn-concilia-row-action {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.btn-concilia-row-action.btn-launch {
    background: #4f46e5;
    color: #ffffff;
}
.btn-concilia-row-action.btn-launch:hover {
    background: #4338ca;
}
.btn-concilia-row-action.btn-diff {
    background: #f59e0b;
    color: #ffffff;
}
.btn-concilia-row-action.btn-diff:hover {
    background: #d97706;
}
.btn-concilia-row-action.btn-cents {
    background: #0284c7;
    color: #ffffff;
}
.btn-concilia-row-action.btn-cents:hover {
    background: #0369a1;
}
.badge-resolved {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #dcfce7;
    color: #15803d;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 700;
}

@media (max-width: 768px) {
    .conciliacao-form-grid {
        grid-template-columns: 1fr;
    }
    .conciliacao-metrics-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<script>
let lastConciliacaoResult = null;

function openConciliacaoModal(cartaoId = null) {
    const modal = document.getElementById('conciliacaoFaturaModal');
    if (!modal) return;

    if (cartaoId) {
        const sel = document.getElementById('conciliaCartaoSelect');
        if (sel) sel.value = cartaoId;
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeConciliacaoModal() {
    const modal = document.getElementById('conciliacaoFaturaModal');
    if (!modal) return;

    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function resetConciliacaoModal() {
    document.getElementById('conciliacaoUploadStep').style.display = 'block';
    document.getElementById('conciliacaoLoadingStep').style.display = 'none';
    document.getElementById('conciliacaoResultsStep').style.display = 'none';
    document.getElementById('conciliaErrorAlert').style.display = 'none';
    document.getElementById('conciliaFileInfo').style.display = 'none';
    document.getElementById('conciliaArquivoInput').value = '';
    document.getElementById('conciliaDropzoneText').innerHTML = '<strong>Clique para escolher</strong> ou arraste o arquivo da sua fatura aqui';
}

function handleConciliaFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('conciliaSelectedFileName').textContent = file.name;
        document.getElementById('conciliaSelectedFileSize').textContent = '(' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('conciliaFileInfo').style.display = 'inline-flex';
        document.getElementById('conciliaErrorAlert').style.display = 'none';
    }
}

// Drag & drop dropzone
const dropzone = document.getElementById('conciliacaoDropzone');
if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt.files && dt.files.length) {
            const input = document.getElementById('conciliaArquivoInput');
            input.files = dt.files;
            handleConciliaFileSelected(input);
        }
    });
}

async function handleConciliacaoSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('conciliacaoForm');
    const formData = new FormData(form);
    const errAlert = document.getElementById('conciliaErrorAlert');

    errAlert.style.display = 'none';

    document.getElementById('conciliacaoUploadStep').style.display = 'none';
    document.getElementById('conciliacaoLoadingStep').style.display = 'block';

    try {
        const response = await fetch("{{ route('cartoes.conciliar.fatura') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        let data;
        try {
            data = await response.json();
        } catch (e) {
            throw new Error('O servidor retornou uma resposta inesperada (Status ' + response.status + ').');
        }

        if (!response.ok || !data.sucesso) {
            let msg = data.mensagem;
            if (!msg && data.errors) {
                msg = Object.values(data.errors).flat().join('<br>');
            } else if (!msg && data.erros) {
                msg = Array.isArray(data.erros) ? data.erros.join('<br>') : data.erros;
            }
            throw new Error(msg || 'Falha ao processar conciliação da fatura.');
        }

        lastConciliacaoResult = data;
        renderConciliacaoResults(data);

        document.getElementById('conciliacaoLoadingStep').style.display = 'none';
        document.getElementById('conciliacaoResultsStep').style.display = 'block';

    } catch (err) {
        document.getElementById('conciliacaoLoadingStep').style.display = 'none';
        document.getElementById('conciliacaoUploadStep').style.display = 'block';
        errAlert.innerHTML = err.message || 'Ocorreu um erro ao conciliar a fatura.';
        errAlert.style.display = 'block';
    }
}

function formatBRL(val) {
    return 'R$ ' + Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateBR(dtStr) {
    if (!dtStr) return '-';
    const parts = dtStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dtStr;
}

function renderConciliacaoResults(data) {
    // Badges topo
    document.getElementById('resCartaoBadge').textContent = data.cartao.nome;
    document.getElementById('resPeriodoBadge').textContent = data.periodo.nome_mes;

    const avisoEl = document.getElementById('resAviso');
    avisoEl.textContent = data.aviso ? '⚠ ' + data.aviso : '';
    avisoEl.style.display = data.aviso ? 'block' : 'none';

    // Métricas
    document.getElementById('resTotalFatura').textContent = formatBRL(data.totais.fatura_despesas);
    document.getElementById('resQtdFatura').textContent = data.contadores.total_fatura + ' compras lidas';

    document.getElementById('resTotalSistema').textContent = formatBRL(data.totais.sistema);
    document.getElementById('resQtdSistema').textContent = data.contadores.total_sistema + ' parcelas cadastradas';

    const difEl = document.getElementById('resDiferenca');
    const statusEl = document.getElementById('resStatusBadge');
    difEl.textContent = formatBRL(data.totais.diferenca);

    if (Math.abs(data.totais.diferenca) < 0.01) {
        difEl.className = 'metric-value metric-success';
        statusEl.innerHTML = '<span style="color: #10b981; font-weight: bold;">✔ 100% Alinhado</span>';
    } else {
        difEl.className = 'metric-value metric-danger';
        statusEl.innerHTML = '<span style="color: #ef4444; font-weight: bold;">⚠ Divergência detectada</span>';
    }

    document.getElementById('resItensOk').textContent = data.contadores.conciliados + ' OK';
    document.getElementById('resItensDivergentes').textContent = (data.contadores.faltando_no_sistema + data.contadores.sobrando_no_sistema + data.contadores.divergencias_valor) + ' divergências';

    // Contadores Abas
    document.getElementById('badgeCountFaltando').textContent = data.contadores.faltando_no_sistema;
    document.getElementById('badgeCountSobrando').textContent = data.contadores.sobrando_no_sistema;
    document.getElementById('badgeCountDivergencias').textContent = data.contadores.divergencias_valor;
    document.getElementById('badgeCountConciliados').textContent = data.contadores.conciliados;
    document.getElementById('badgeCountPagamentos').textContent = data.contadores.pagamentos_estornos;

    // 1. Faltando no Sistema
    const tbodyFaltando = document.getElementById('tbodyFaltando');
    tbodyFaltando.innerHTML = '';
    const faltando = data.grupos.faltando_no_sistema || [];
    if (faltando.length === 0) {
        document.getElementById('emptyFaltando').style.display = 'block';
    } else {
        document.getElementById('emptyFaltando').style.display = 'none';
        faltando.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.id = `row-faltando-${idx}`;
            tr.innerHTML = `
                <td>${formatDateBR(item.data)}</td>
                <td><strong>${escapeHtml(item.descricao)}</strong></td>
                <td class="text-center">${item.parcela_info || '1/1'}</td>
                <td class="text-right" style="color: #ef4444; font-weight: bold;">${formatBRL(item.valor)}</td>
                <td class="text-center" id="action-faltando-${idx}">
                    <button type="button" class="btn-concilia-row-action btn-launch" onclick="openQuickLaunchModal(${idx})" title="Lançar compra no sistema">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Lançar</span>
                    </button>
                </td>
            `;
            tbodyFaltando.appendChild(tr);
        });
    }

    // 2. Sobrando no Sistema
    const tbodySobrando = document.getElementById('tbodySobrando');
    tbodySobrando.innerHTML = '';
    const sobrando = data.grupos.sobrando_no_sistema || [];
    if (sobrando.length === 0) {
        document.getElementById('emptySobrando').style.display = 'block';
    } else {
        document.getElementById('emptySobrando').style.display = 'none';
        sobrando.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${formatDateBR(p.data_compra)}</td>
                <td>${formatDateBR(p.data_vencimento)}</td>
                <td><strong>${escapeHtml(p.descricao)}</strong></td>
                <td>${escapeHtml(p.categoria || 'Geral')}</td>
                <td class="text-center">${p.parcela_texto}</td>
                <td class="text-right" style="color: #f59e0b; font-weight: bold;">${formatBRL(p.valor_parcela)}</td>
            `;
            tbodySobrando.appendChild(tr);
        });
    }

    // 3. Divergências de Valor
    const tbodyDivergencias = document.getElementById('tbodyDivergencias');
    tbodyDivergencias.innerHTML = '';
    const divergencias = data.grupos.divergencias_valor || [];
    if (divergencias.length === 0) {
        document.getElementById('emptyDivergencias').style.display = 'block';
    } else {
        document.getElementById('emptyDivergencias').style.display = 'none';
        divergencias.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.id = `row-divergencia-${idx}`;
            const difClass = d.diferenca > 0 ? 'color: #ef4444;' : 'color: #f59e0b;';
            tr.innerHTML = `
                <td>${formatDateBR(d.fatura_item.data)}</td>
                <td>
                    <strong>${escapeHtml(d.fatura_item.descricao)}</strong>
                    <div style="font-size: 0.75rem; color: #64748b;">Sistema: ${escapeHtml(d.sistema_item.descricao)}</div>
                </td>
                <td class="text-right">${formatBRL(d.valor_fatura)}</td>
                <td class="text-right">${formatBRL(d.valor_sistema)}</td>
                <td class="text-right" style="${difClass} font-weight: bold;">${d.diferenca > 0 ? '+' : ''}${formatBRL(d.diferenca)}</td>
                <td class="text-center" id="action-divergencia-${idx}">
                    <button type="button" class="btn-concilia-row-action btn-diff" onclick="openQuickDiffModal('divergencia', ${idx})" title="Resolver diferença de valor">
                        <span>Resolver</span>
                    </button>
                </td>
            `;
            tbodyDivergencias.appendChild(tr);
        });
    }

    // 4. Conciliados
    const tbodyConciliados = document.getElementById('tbodyConciliados');
    tbodyConciliados.innerHTML = '';
    const conciliados = data.grupos.conciliados || [];
    if (conciliados.length === 0) {
        document.getElementById('emptyConciliados').style.display = 'block';
    } else {
        document.getElementById('emptyConciliados').style.display = 'none';
        conciliados.forEach((c, idx) => {
            const tr = document.createElement('tr');
            tr.id = `row-conciliado-${idx}`;
            const badgeCerteza = c.status === 'certeiro'
                ? '<span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">100%</span>'
                : `<span style="background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">${c.confianca || 80}%</span>`;
            const difCentavos = c.status === 'tolerancia' && c.diferenca_valor
                ? `<div style="font-size: 0.75rem; font-weight: normal; color: #b45309;" title="Valor no sistema: ${formatBRL(c.sistema_item.valor_parcela)}">${c.diferenca_valor > 0 ? '+' : '−'}${formatBRL(Math.abs(c.diferenca_valor))} vs sistema</div>`
                : '';

            let acaoHtml = '<span style="color:#94a3b8; font-size:0.8rem;">—</span>';
            if (c.status === 'tolerancia' && c.diferenca_valor && Math.abs(c.diferenca_valor) > 0.001) {
                acaoHtml = `
                    <button type="button" class="btn-concilia-row-action btn-cents" onclick="openQuickDiffModal('tolerancia', ${idx})" title="Ajustar diferença de centavos">
                        <span>Ajustar Centavos</span>
                    </button>
                `;
            }

            tr.innerHTML = `
                <td>${formatDateBR(c.fatura_item.data)}</td>
                <td><strong>${escapeHtml(c.fatura_item.descricao)}</strong></td>
                <td style="color: #475569;">${escapeHtml(c.sistema_item.descricao)}</td>
                <td class="text-center">${c.sistema_item.parcela_texto}</td>
                <td class="text-right" style="font-weight: bold;">${formatBRL(c.fatura_item.valor)}${difCentavos}</td>
                <td class="text-center">${badgeCerteza}</td>
                <td class="text-center" id="action-conciliado-${idx}">${acaoHtml}</td>
            `;
            tbodyConciliados.appendChild(tr);
        });
    }

    // 5. Pagamentos e Estornos
    const tbodyPagamentos = document.getElementById('tbodyPagamentos');
    tbodyPagamentos.innerHTML = '';
    const pagamentos = data.grupos.pagamentos_estornos || [];
    if (pagamentos.length === 0) {
        document.getElementById('emptyPagamentos').style.display = 'block';
    } else {
        document.getElementById('emptyPagamentos').style.display = 'none';
        pagamentos.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${formatDateBR(p.data)}</td>
                <td>${escapeHtml(p.descricao)}</td>
                <td class="text-right" style="color: #4f46e5; font-weight: bold;">${formatBRL(p.valor)}</td>
            `;
            tbodyPagamentos.appendChild(tr);
        });
    }

    // Abrir a primeira aba com divergência por padrão (ou conciliação se tudo estiver ok)
    if (faltando.length > 0) {
        document.querySelector('.conciliacao-tab-btn[data-tab="tabFaltando"]').click();
    } else if (sobrando.length > 0) {
        document.querySelector('.conciliacao-tab-btn[data-tab="tabSobrando"]').click();
    } else if (divergencias.length > 0) {
        document.querySelector('.conciliacao-tab-btn[data-tab="tabDivergencias"]').click();
    } else {
        document.querySelector('.conciliacao-tab-btn[data-tab="tabConciliados"]').click();
    }
}

function switchConciliaTab(tabId, btn) {
    document.querySelectorAll('.conciliacao-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.conciliacao-tab-content').forEach(c => c.classList.remove('active'));

    btn.classList.add('active');
    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// ── MINI-MODAL: Lançar Compra Faltante ──
function openQuickLaunchModal(idx) {
    if (!lastConciliacaoResult) return;
    const item = (lastConciliacaoResult.grupos.faltando_no_sistema || [])[idx];
    if (!item) return;

    document.getElementById('qlCartaoId').value = lastConciliacaoResult.cartao.id;
    document.getElementById('qlCartaoNome').value = lastConciliacaoResult.cartao.nome;
    document.getElementById('qlItemIndex').value = idx;
    document.getElementById('qlDescricao').value = item.descricao || '';
    document.getElementById('qlData').value = item.data || new Date().toISOString().split('T')[0];
    document.getElementById('qlValor').value = parseFloat(item.valor) || 0;
    document.getElementById('qlTipo').value = 'avista';
    document.getElementById('qlParcelas').value = '1';
    document.getElementById('qlParcelas').style.display = 'none';

    // Parse de parcelas da fatura se houver (ex: 1/3)
    if (item.parcela_info && item.parcela_info.includes('/')) {
        const parts = item.parcela_info.split('/');
        const totalP = parseInt(parts[1], 10);
        if (totalP > 1) {
            document.getElementById('qlTipo').value = 'parcelada';
            document.getElementById('qlParcelas').value = totalP;
            document.getElementById('qlParcelas').style.display = 'block';
        }
    }

    document.getElementById('qlAlert').style.display = 'none';
    document.getElementById('quickLaunchModal').style.display = 'flex';
}

function closeQuickLaunchModal() {
    document.getElementById('quickLaunchModal').style.display = 'none';
}

function toggleQlParcelas(val) {
    const pInput = document.getElementById('qlParcelas');
    if (val === 'parcelada') {
        pInput.style.display = 'block';
        if (parseInt(pInput.value, 10) <= 1) pInput.value = '2';
    } else {
        pInput.style.display = 'none';
        pInput.value = '1';
    }
}

async function submitQuickLaunch(event) {
    event.preventDefault();
    const btn = document.getElementById('qlSubmitBtn');
    const alertEl = document.getElementById('qlAlert');
    alertEl.style.display = 'none';

    btn.disabled = true;
    document.getElementById('qlBtnText').textContent = 'Lançando...';

    const itemIdx = document.getElementById('qlItemIndex').value;
    const faturaId = lastConciliacaoResult ? lastConciliacaoResult.fatura_id : null;

    const payload = {
        cartao_id: document.getElementById('qlCartaoId').value,
        descricao: document.getElementById('qlDescricao').value,
        valor_total: document.getElementById('qlValor').value,
        data_compra: document.getElementById('qlData').value,
        categoria: document.getElementById('qlCategoria').value,
        tipo: document.getElementById('qlTipo').value,
        numero_parcelas: document.getElementById('qlParcelas').value,
        fatura_id: faturaId,
    };

    try {
        const res = await fetch("{{ route('cartoes.conciliar.lancar-item') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.sucesso) {
            throw new Error(data.message || data.mensagem || 'Falha ao registrar compra.');
        }

        const actionTd = document.getElementById(`action-faltando-${itemIdx}`);
        if (actionTd) {
            actionTd.innerHTML = '<span class="badge-resolved">✔ Lançado</span>';
        }

        const lancadoValor = parseFloat(payload.valor_total) || 0;
        lastConciliacaoResult.totais.sistema += lancadoValor;
        lastConciliacaoResult.totais.diferenca = Math.round((lastConciliacaoResult.totais.fatura_despesas - lastConciliacaoResult.totais.sistema) * 100) / 100;
        lastConciliacaoResult.contadores.faltando_no_sistema = Math.max(0, lastConciliacaoResult.contadores.faltando_no_sistema - 1);
        lastConciliacaoResult.contadores.total_sistema += 1;
        lastConciliacaoResult.contadores.conciliados += 1;

        atualizarMetricasDinamicas();
        if (data.fatura_atualizada) {
            atualizarLinhaFaturaSalva(data.fatura_atualizada);
        }

        closeQuickLaunchModal();
    } catch (err) {
        alertEl.textContent = err.message;
        alertEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        document.getElementById('qlBtnText').textContent = 'Confirmar e Lançar';
    }
}

// ── MINI-MODAL: Resolver Diferença / Centavos ──
let currentDiffContext = null;

function openQuickDiffModal(type, idx) {
    if (!lastConciliacaoResult) return;
    currentDiffContext = { type, idx };
    let itemData = null;
    if (type === 'divergencia') {
        itemData = (lastConciliacaoResult.grupos.divergencias_valor || [])[idx];
    } else if (type === 'tolerancia') {
        itemData = (lastConciliacaoResult.grupos.conciliados || [])[idx];
    }

    if (!itemData) return;

    const valFatura = type === 'divergencia' ? itemData.valor_fatura : itemData.fatura_item.valor;
    const valSistema = type === 'divergencia' ? itemData.valor_sistema : itemData.sistema_item.valor_parcela;
    const diferenca = type === 'divergencia' ? itemData.diferenca : itemData.diferenca_valor;

    document.getElementById('qdValFatura').textContent = formatBRL(valFatura);
    document.getElementById('qdValSistema').textContent = formatBRL(valSistema);
    document.getElementById('qdValDiferenca').textContent = (diferenca > 0 ? '+' : '') + formatBRL(diferenca);

    document.getElementById('qdAlert').style.display = 'none';
    document.getElementById('quickDiffModal').style.display = 'flex';
}

function closeQuickDiffModal() {
    document.getElementById('quickDiffModal').style.display = 'none';
}

async function executarAjusteParcela() {
    if (!currentDiffContext || !lastConciliacaoResult) return;
    const { type, idx } = currentDiffContext;
    const itemData = type === 'divergencia' 
        ? lastConciliacaoResult.grupos.divergencias_valor[idx] 
        : lastConciliacaoResult.grupos.conciliados[idx];

    const valFatura = type === 'divergencia' ? itemData.valor_fatura : itemData.fatura_item.valor;
    const parcelaId = itemData.sistema_item.id;
    const diferenca = type === 'divergencia' ? itemData.diferenca : itemData.diferenca_valor;

    const alertEl = document.getElementById('qdAlert');
    alertEl.style.display = 'none';

    try {
        const res = await fetch("{{ route('cartoes.conciliar.ajustar-centavos') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                tipo_ajuste: 'ajustar_parcela',
                parcela_id: parcelaId,
                novo_valor: valFatura,
                fatura_id: lastConciliacaoResult.fatura_id || null,
            })
        });

        const data = await res.json();
        if (!res.ok || !data.sucesso) {
            throw new Error(data.message || data.mensagem || 'Falha ao ajustar parcela.');
        }

        const actionTd = type === 'divergencia' 
            ? document.getElementById(`action-divergencia-${idx}`)
            : document.getElementById(`action-conciliado-${idx}`);
        if (actionTd) {
            actionTd.innerHTML = '<span class="badge-resolved">✔ Ajustado</span>';
        }

        lastConciliacaoResult.totais.sistema += diferenca;
        lastConciliacaoResult.totais.diferenca = Math.round((lastConciliacaoResult.totais.fatura_despesas - lastConciliacaoResult.totais.sistema) * 100) / 100;
        if (type === 'divergencia') {
            lastConciliacaoResult.contadores.divergencias_valor = Math.max(0, lastConciliacaoResult.contadores.divergencias_valor - 1);
            lastConciliacaoResult.contadores.conciliados += 1;
        }

        atualizarMetricasDinamicas();
        if (data.fatura_atualizada) {
            atualizarLinhaFaturaSalva(data.fatura_atualizada);
        }

        closeQuickDiffModal();
    } catch (err) {
        alertEl.textContent = err.message;
        alertEl.style.display = 'block';
    }
}

async function executarLancamentoDiferenca() {
    if (!currentDiffContext || !lastConciliacaoResult) return;
    const { type, idx } = currentDiffContext;
    const itemData = type === 'divergencia' 
        ? lastConciliacaoResult.grupos.divergencias_valor[idx] 
        : lastConciliacaoResult.grupos.conciliados[idx];

    const desc = type === 'divergencia' ? itemData.fatura_item.descricao : itemData.fatura_item.descricao;
    const dataCompra = type === 'divergencia' ? itemData.fatura_item.data : itemData.fatura_item.data;
    const diferenca = type === 'divergencia' ? itemData.diferenca : itemData.diferenca_valor;

    const alertEl = document.getElementById('qdAlert');
    alertEl.style.display = 'none';

    try {
        const res = await fetch("{{ route('cartoes.conciliar.ajustar-centavos') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                tipo_ajuste: 'novo_lancamento',
                cartao_id: lastConciliacaoResult.cartao.id,
                descricao: `Ajuste Diferença - ${desc}`,
                valor: Math.abs(diferenca),
                data_compra: dataCompra || new Date().toISOString().split('T')[0],
                categoria: 'Ajuste / Tarifas',
                fatura_id: lastConciliacaoResult.fatura_id || null,
            })
        });

        const data = await res.json();
        if (!res.ok || !data.sucesso) {
            throw new Error(data.message || data.mensagem || 'Falha ao lançar diferença.');
        }

        const actionTd = type === 'divergencia' 
            ? document.getElementById(`action-divergencia-${idx}`)
            : document.getElementById(`action-conciliado-${idx}`);
        if (actionTd) {
            actionTd.innerHTML = '<span class="badge-resolved">✔ Lançado</span>';
        }

        lastConciliacaoResult.totais.sistema += diferenca;
        lastConciliacaoResult.totais.diferenca = Math.round((lastConciliacaoResult.totais.fatura_despesas - lastConciliacaoResult.totais.sistema) * 100) / 100;
        if (type === 'divergencia') {
            lastConciliacaoResult.contadores.divergencias_valor = Math.max(0, lastConciliacaoResult.contadores.divergencias_valor - 1);
            lastConciliacaoResult.contadores.conciliados += 1;
        }

        atualizarMetricasDinamicas();
        if (data.fatura_atualizada) {
            atualizarLinhaFaturaSalva(data.fatura_atualizada);
        }

        closeQuickDiffModal();
    } catch (err) {
        alertEl.textContent = err.message;
        alertEl.style.display = 'block';
    }
}

function atualizarMetricasDinamicas() {
    if (!lastConciliacaoResult) return;
    document.getElementById('resTotalSistema').textContent = formatBRL(lastConciliacaoResult.totais.sistema);
    document.getElementById('resQtdSistema').textContent = lastConciliacaoResult.contadores.total_sistema + ' parcelas cadastradas';

    const difEl = document.getElementById('resDiferenca');
    const statusEl = document.getElementById('resStatusBadge');
    difEl.textContent = formatBRL(lastConciliacaoResult.totais.diferenca);

    if (Math.abs(lastConciliacaoResult.totais.diferenca) < 0.01) {
        difEl.className = 'metric-value metric-success';
        statusEl.innerHTML = '<span style="color: #10b981; font-weight: bold;">✔ 100% Alinhado</span>';
    } else {
        difEl.className = 'metric-value metric-danger';
        statusEl.innerHTML = '<span style="color: #ef4444; font-weight: bold;">⚠ Divergência detectada</span>';
    }

    document.getElementById('resItensOk').textContent = lastConciliacaoResult.contadores.conciliados + ' OK';
    const totalDivergentes = (lastConciliacaoResult.contadores.faltando_no_sistema || 0) + 
                            (lastConciliacaoResult.contadores.sobrando_no_sistema || 0) + 
                            (lastConciliacaoResult.contadores.divergencias_valor || 0);
    document.getElementById('resItensDivergentes').textContent = totalDivergentes + ' divergências';

    document.getElementById('badgeCountFaltando').textContent = lastConciliacaoResult.contadores.faltando_no_sistema;
    document.getElementById('badgeCountDivergencias').textContent = lastConciliacaoResult.contadores.divergencias_valor;
    document.getElementById('badgeCountConciliados').textContent = lastConciliacaoResult.contadores.conciliados;
}

async function reabrirConciliacaoSalva(faturaId) {
    const modal = document.getElementById('conciliacaoFaturaModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    document.getElementById('conciliacaoUploadStep').style.display = 'none';
    document.getElementById('conciliacaoLoadingStep').style.display = 'block';
    document.getElementById('conciliacaoResultsStep').style.display = 'none';
    document.getElementById('conciliaErrorAlert').style.display = 'none';

    try {
        const response = await fetch(`/financas/cartoes/faturas/${faturaId}/reconciliar`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (!response.ok || !data.sucesso) {
            throw new Error(data.mensagem || 'Falha ao auditar fatura salva.');
        }

        lastConciliacaoResult = data;
        renderConciliacaoResults(data);

        document.getElementById('conciliacaoLoadingStep').style.display = 'none';
        document.getElementById('conciliacaoResultsStep').style.display = 'block';

        if (data.fatura_id) {
            atualizarLinhaFaturaSalva({
                id: data.fatura_id,
                status: data.totais.is_alinhado ? 'conciliada' : 'pendente',
                pendencias_count: (data.contadores.faltando_no_sistema + data.contadores.sobrando_no_sistema + data.contadores.divergencias_valor),
                diferenca_total: data.totais.diferenca
            });
        }
    } catch (err) {
        document.getElementById('conciliacaoLoadingStep').style.display = 'none';
        document.getElementById('conciliacaoUploadStep').style.display = 'block';
        const errAlert = document.getElementById('conciliaErrorAlert');
        errAlert.innerHTML = err.message || 'Erro ao reabrir fatura salva.';
        errAlert.style.display = 'block';
    }
}

function atualizarLinhaFaturaSalva(faturaData) {
    const badgeEl = document.getElementById(`fatura-status-badge-${faturaData.id}`);
    if (badgeEl) {
        if (faturaData.status === 'conciliada' || faturaData.pendencias_count === 0) {
            badgeEl.className = 'fatura-badge fatura-badge-success';
            badgeEl.innerHTML = '✔ 100% Conciliada';
        } else {
            badgeEl.className = 'fatura-badge fatura-badge-warning';
            badgeEl.innerHTML = `⚠ ${faturaData.pendencias_count} pendência(s)`;
        }
    }
}
</script>
