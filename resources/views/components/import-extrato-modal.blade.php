<div id="importExtratoModalBackdrop" class="extrato-modal-backdrop" style="display: none;">
    <div class="extrato-modal-card" role="dialog" aria-modal="true" aria-labelledby="importExtratoModalTitle">
        <!-- Header -->
        <div class="extrato-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="extrato-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <div>
                    <h3 id="importExtratoModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff;">Importar Extrato Bancário</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;">Suporta PDF, Excel (.xlsx), CSV e OFX</p>
                </div>
            </div>
            <button type="button" class="extrato-modal-close" onclick="closeImportExtratoModal()">&times;</button>
        </div>

        <!-- Body -->
        <div class="extrato-modal-body">
            <form id="formImportExtrato" action="{{ route('financas.importar-extrato') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Formatos Suportados Badges -->
                <div class="extrato-badges-row">
                    <span class="extrato-format-badge badge-pdf">📄 PDF</span>
                    <span class="extrato-format-badge badge-excel">📊 Excel (.xlsx)</span>
                    <span class="extrato-format-badge badge-csv">📑 CSV</span>
                    <span class="extrato-format-badge badge-ofx">💳 OFX</span>
                </div>

                <!-- Dropzone de Arquivo -->
                <div class="extrato-dropzone" id="extratoDropzone" onclick="document.getElementById('inputExtratoFile').click()">
                    <input type="file" id="inputExtratoFile" name="extrato" accept=".pdf,.xlsx,.csv,.txt,.ofx" style="display: none;" onchange="handleExtratoFileSelect(this)">
                    
                    <div id="dropzoneEmpty">
                        <div class="extrato-upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/>
                                <path d="M12 12v9"/>
                                <path d="m8 16 4-4 4 4"/>
                            </svg>
                        </div>
                        <p class="dropzone-title">Arraste seu extrato bancário aqui</p>
                        <p class="dropzone-sub">ou clique para escolher no computador</p>
                    </div>

                    <div id="dropzoneSelected" style="display: none;">
                        <div class="selected-file-card">
                            <span id="selectedFileIcon" style="font-size: 1.5rem;">📄</span>
                            <div style="flex: 1; text-align: left; overflow: hidden;">
                                <div id="selectedFileName" style="font-weight: 600; color: #fff; font-size: 0.9rem; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"></div>
                                <div id="selectedFileSize" style="font-size: 0.75rem; color: #94a3b8;"></div>
                            </div>
                            <button type="button" class="btn-clear-file" onclick="event.stopPropagation(); clearExtratoFile();" title="Trocar arquivo">&times;</button>
                        </div>
                    </div>
                </div>

                <!-- Seleção de Conta -->
                <div class="extrato-form-group">
                    <label for="selectContaDestino" class="extrato-label">
                        <span>Vincular à Conta:</span>
                        <small style="color: #64748b; font-weight: normal;">(opcional)</small>
                    </label>
                    <select id="selectContaDestino" name="conta_id" class="extrato-select">
                        <option value="">🔍 Detectar / Criar Conta Automaticamente</option>
                        @if(isset($contas) && count($contas) > 0)
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">🏦 {{ $conta->nome }} ({{ ucfirst($conta->tipo) }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Dica Rápida -->
                <div class="extrato-info-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; color: #38bdf8; margin-top: 1px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span><strong>Prevenção de Duplicidades:</strong> Transações repetidas ou já cadastradas são identificadas e ignoradas automaticamente.</span>
                </div>

                <!-- Alerta de Status / Progresso -->
                <div id="extratoProgressArea" style="display: none;" class="extrato-progress-box">
                    <div class="extrato-spinner"></div>
                    <p id="extratoProgressText" style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #cbd5e1;">Processando e interpretando lançamentos do extrato...</p>
                </div>

                <!-- Resultado da Importação -->
                <div id="extratoResultArea" style="display: none;" class="extrato-result-box">
                    <div id="extratoResultHeader" class="result-header"></div>
                    <div class="result-stats-grid">
                        <div class="stat-pill">
                            <span class="stat-label">Lidos</span>
                            <span id="statTotalLidos" class="stat-val">0</span>
                        </div>
                        <div class="stat-pill success">
                            <span class="stat-label">Inseridos</span>
                            <span id="statInseridos" class="stat-val">0</span>
                        </div>
                        <div class="stat-pill warning">
                            <span class="stat-label">Duplicados</span>
                            <span id="statDuplicados" class="stat-val">0</span>
                        </div>
                        <div class="stat-pill">
                            <span class="stat-label">Entradas</span>
                            <span id="statEntradas" class="stat-val" style="color: #10b981;">R$ 0,00</span>
                        </div>
                        <div class="stat-pill">
                            <span class="stat-label">Saídas</span>
                            <span id="statSaidas" class="stat-val" style="color: #ef4444;">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <!-- Footer do Modal -->
                <div class="extrato-modal-footer">
                    <button type="button" class="extrato-btn-cancel" onclick="closeImportExtratoModal()">Cancelar</button>
                    <button type="button" id="btnSubmitExtrato" class="extrato-btn-submit" onclick="submitImportExtrato()">
                        <span>Importar Lançamentos</span>
                    </button>
                    <button type="button" id="btnRecarregarTela" class="extrato-btn-reload" style="display: none;" onclick="window.location.reload()">
                        <span>Atualizar Tela ↻</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Extrato Modal Styles */
.extrato-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    padding: 1rem;
    box-sizing: border-box;
    animation: extratoFadeIn 0.2s ease-out;
}

@keyframes extratoFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.extrato-modal-card {
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: extratoSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes extratoSlideUp {
    from { transform: translateY(20px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.extrato-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(30, 41, 59, 0.6);
}

.extrato-icon-circle {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.extrato-modal-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.15s ease;
}

.extrato-modal-close:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}

.extrato-modal-body {
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    overflow-y: auto;
}

.extrato-badges-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.extrato-format-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    letter-spacing: 0.3px;
}

.badge-pdf { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge-excel { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.badge-csv { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.badge-ofx { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }

.extrato-dropzone {
    border: 2px dashed rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 1.75rem 1rem;
    text-align: center;
    background: rgba(15, 23, 42, 0.5);
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 1.15rem;
}

.extrato-dropzone:hover, .extrato-dropzone.dragover {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.08);
}

.extrato-upload-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.15);
    color: #818cf8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.dropzone-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #f1f5f9;
}

.dropzone-sub {
    margin: 0.25rem 0 0 0;
    font-size: 0.8rem;
    color: #94a3b8;
}

.selected-file-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(30, 41, 59, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.btn-clear-file {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.4rem;
    cursor: pointer;
    line-height: 1;
    padding: 0 0.25rem;
}
.btn-clear-file:hover { color: #ef4444; }

.extrato-form-group {
    margin-bottom: 1rem;
}

.extrato-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 0.4rem;
}

.extrato-select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s ease;
}

.extrato-select:focus {
    border-color: #6366f1;
}

.extrato-info-box {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    background: rgba(56, 189, 248, 0.08);
    border: 1px solid rgba(56, 189, 248, 0.2);
    border-radius: 8px;
    padding: 0.65rem 0.85rem;
    font-size: 0.78rem;
    color: #bae6fd;
    line-height: 1.4;
    margin-bottom: 1rem;
}

.extrato-progress-box {
    text-align: center;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 10px;
    margin-bottom: 1rem;
}

.extrato-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(99, 102, 241, 0.2);
    border-top-color: #6366f1;
    border-radius: 50%;
    margin: 0 auto;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.extrato-result-box {
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.result-header {
    font-size: 0.9rem;
    font-weight: 700;
    color: #34d399;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(85px, 1fr));
    gap: 0.5rem;
}

.stat-pill {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 6px;
    padding: 0.5rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.stat-pill.success { border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.08); }
.stat-pill.warning { border-color: rgba(234, 179, 8, 0.3); background: rgba(234, 179, 8, 0.08); }

.stat-label { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; }
.stat-val { font-size: 0.85rem; font-weight: 700; color: #f1f5f9; }

.extrato-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.5rem;
}

.extrato-btn-cancel {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #cbd5e1;
    padding: 0.55rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.extrato-btn-cancel:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }

.extrato-btn-submit {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #fff;
    border: none;
    padding: 0.55rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transition: all 0.15s ease;
}
.extrato-btn-submit:hover { transform: translateY(-1px); filter: brightness(1.1); }
.extrato-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.extrato-btn-reload {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    border: none;
    padding: 0.55rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
</style>

<script>
function openImportExtratoModal() {
    var modal = document.getElementById('importExtratoModalBackdrop');
    if (!modal) return;
    modal.style.display = 'flex';
}

function closeImportExtratoModal() {
    var modal = document.getElementById('importExtratoModalBackdrop');
    if (modal) modal.style.display = 'none';
}

function handleExtratoFileSelect(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        document.getElementById('dropzoneEmpty').style.display = 'none';
        document.getElementById('dropzoneSelected').style.display = 'block';
        document.getElementById('selectedFileName').innerText = file.name;
        
        var sizeKb = (file.size / 1024).toFixed(1);
        document.getElementById('selectedFileSize').innerText = sizeKb > 1024 
            ? (sizeKb / 1024).toFixed(2) + ' MB' 
            : sizeKb + ' KB';

        var ext = file.name.split('.').pop().toLowerCase();
        var icon = '📄';
        if (ext === 'xlsx' || ext === 'xls') icon = '📊';
        else if (ext === 'csv' || ext === 'txt') icon = '📑';
        else if (ext === 'ofx') icon = '💳';
        document.getElementById('selectedFileIcon').innerText = icon;
    }
}

function clearExtratoFile() {
    var input = document.getElementById('inputExtratoFile');
    if (input) input.value = '';
    document.getElementById('dropzoneEmpty').style.display = 'block';
    document.getElementById('dropzoneSelected').style.display = 'none';
}

// Drag & Drop listeners
(function() {
    var dropzone = document.getElementById('extratoDropzone');
    if (!dropzone) return;

    ['dragenter', 'dragover'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            var input = document.getElementById('inputExtratoFile');
            input.files = dt.files;
            handleExtratoFileSelect(input);
        }
    });
})();

function submitImportExtrato() {
    var fileInput = document.getElementById('inputExtratoFile');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        alert('Por favor, selecione ou arraste um arquivo de extrato (PDF, Excel, CSV ou OFX).');
        return;
    }

    var form = document.getElementById('formImportExtrato');
    var formData = new FormData(form);

    var btnSubmit = document.getElementById('btnSubmitExtrato');
    var progressArea = document.getElementById('extratoProgressArea');
    var resultArea = document.getElementById('extratoResultArea');
    var btnReload = document.getElementById('btnRecarregarTela');

    btnSubmit.disabled = true;
    progressArea.style.display = 'block';
    resultArea.style.display = 'none';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function(res) {
        return res.json().then(function(data) {
            return { ok: res.ok, status: res.status, data: data };
        });
    })
    .then(function(response) {
        progressArea.style.display = 'none';
        btnSubmit.disabled = false;

        var data = response.data;
        if (response.ok && data.sucesso) {
            resultArea.style.display = 'block';
            document.getElementById('extratoResultHeader').innerHTML = '✔ ' + (data.mensagem || 'Importação realizada com sucesso!');
            document.getElementById('statTotalLidos').innerText = data.total_lidos || 0;
            document.getElementById('statInseridos').innerText = data.inseridos || 0;
            document.getElementById('statDuplicados').innerText = data.duplicados || 0;

            var formatMoney = function(v) {
                return 'R$ ' + (v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            document.getElementById('statEntradas').innerText = formatMoney(data.total_entradas);
            document.getElementById('statSaidas').innerText = formatMoney(data.total_saidas);

            btnSubmit.style.display = 'none';
            btnReload.style.display = 'inline-flex';
        } else {
            alert('Erro: ' + (data.mensagem || 'Falha ao processar arquivo. Verifique o formato.'));
        }
    })
    .catch(function(err) {
        progressArea.style.display = 'none';
        btnSubmit.disabled = false;
        alert('Erro de comunicação com o servidor ao enviar o arquivo.');
    });
}

// Fechar com ESC ou clique fora
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeImportExtratoModal();
});
document.addEventListener('click', function(e) {
    var backdrop = document.getElementById('importExtratoModalBackdrop');
    if (backdrop && e.target === backdrop) closeImportExtratoModal();
});
</script>
