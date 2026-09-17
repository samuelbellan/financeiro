/**
 * Transaction Autocomplete Component
 * Sugere descrições e categorias baseado no histórico de lançamentos.
 */
(function(window) {
    'use strict';

    function normalizeStr(str) {
        if (!str) return '';
        return str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function highlightMatch(text, query) {
        if (!query) return text;
        const normText = normalizeStr(text);
        const normQuery = normalizeStr(query);
        const index = normText.indexOf(normQuery);
        if (index === -1) return text;

        const before = text.substring(0, index);
        const match = text.substring(index, index + query.length);
        const after = text.substring(index + query.length);

        return `${escapeHtml(before)}<strong style="color: #4f46e5; text-decoration: underline;">${escapeHtml(match)}</strong>${escapeHtml(after)}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initTransactionAutocomplete(config) {
        const input = typeof config.input === 'string' ? document.querySelector(config.input) : config.input;
        if (!input) return null;

        // Desativar autocomplete nativo do navegador para não sobrepor
        input.setAttribute('autocomplete', 'off');

        let suggestions = Array.isArray(config.initialSuggestions) ? [...config.initialSuggestions] : [];
        const onSelect = config.onSelect || function() {};
        const fetchUrl = config.fetchUrl || '/financas/sugestoes-descricao';

        // Garante que o elemento pai possua position: relative
        const parent = input.parentElement;
        if (parent && window.getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        // Dropdown container
        let dropdown = document.createElement('div');
        dropdown.className = 'tx-autocomplete-dropdown';
        dropdown.style.cssText = `
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
            z-index: 1050;
            padding: 4px 0;
        `;
        parent.appendChild(dropdown);

        let activeIndex = -1;
        let currentMatches = [];
        let debounceTimer = null;

        function renderDropdown(matches, query) {
            currentMatches = matches;
            dropdown.innerHTML = '';
            activeIndex = -1;

            if (matches.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            matches.forEach((item, idx) => {
                const row = document.createElement('div');
                row.className = 'tx-autocomplete-item';
                row.dataset.index = idx;
                row.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 8px 12px;
                    cursor: pointer;
                    font-size: 0.875rem;
                    color: #1e293b;
                    border-left: 3px solid transparent;
                    transition: background 0.12s, border-color 0.12s;
                `;

                const leftCol = document.createElement('div');
                leftCol.style.cssText = 'display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                
                // Ícone sutil
                const icon = document.createElement('span');
                icon.innerHTML = item.tipo === 'receita' ? '💰' : '🏷️';
                icon.style.fontSize = '0.85rem';
                leftCol.appendChild(icon);

                // Texto da descrição
                const textSpan = document.createElement('span');
                textSpan.innerHTML = highlightMatch(item.descricao, query);
                textSpan.style.overflow = 'hidden';
                textSpan.style.textOverflow = 'ellipsis';
                textSpan.style.whiteSpace = 'nowrap';
                leftCol.appendChild(textSpan);

                row.appendChild(leftCol);

                // Coluna da Categoria / Subcategoria
                if (item.categoria) {
                    const rightCol = document.createElement('div');
                    rightCol.style.cssText = 'display: flex; align-items: center; gap: 4px; margin-left: 8px; flex-shrink: 0;';

                    const catBadge = document.createElement('span');
                    catBadge.textContent = item.categoria;
                    catBadge.style.cssText = `
                        background: #eef2ff;
                        color: #4338ca;
                        font-size: 0.72rem;
                        font-weight: 600;
                        padding: 2px 7px;
                        border-radius: 9999px;
                        white-space: nowrap;
                    `;
                    rightCol.appendChild(catBadge);

                    if (item.subcategoria) {
                        const subSpan = document.createElement('span');
                        subSpan.textContent = `• ${item.subcategoria}`;
                        subSpan.style.cssText = 'font-size: 0.72rem; color: #64748b; white-space: nowrap;';
                        rightCol.appendChild(subSpan);
                    }

                    row.appendChild(rightCol);
                }

                // Eventos de clique e hover
                row.addEventListener('mouseenter', () => setActive(idx));
                row.addEventListener('mousedown', (e) => {
                    e.preventDefault(); // Evita que o input perca o foco antes de selecionar
                    selectItem(item);
                });

                dropdown.appendChild(row);
            });

            dropdown.style.display = 'block';
        }

        function setActive(index) {
            const rows = dropdown.querySelectorAll('.tx-autocomplete-item');
            rows.forEach((r, i) => {
                if (i === index) {
                    r.style.background = '#f1f5f9';
                    r.style.borderLeftColor = '#4f46e5';
                    r.scrollIntoView({ block: 'nearest' });
                } else {
                    r.style.background = 'transparent';
                    r.style.borderLeftColor = 'transparent';
                }
            });
            activeIndex = index;
        }

        function selectItem(item) {
            input.value = item.descricao;
            closeDropdown();
            onSelect(item);
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            activeIndex = -1;
            currentMatches = [];
        }

        function filterLocal(query) {
            const normQuery = normalizeStr(query);
            if (!normQuery) return [];

            const filtered = [];
            for (const item of suggestions) {
                const normDesc = normalizeStr(item.descricao);
                if (normDesc.includes(normQuery)) {
                    const isPrefix = normDesc.startsWith(normQuery);
                    filtered.push({ ...item, isPrefix });
                }
            }

            // Ordenar: prefixo primeiro, depois frequência
            filtered.sort((a, b) => {
                if (a.isPrefix !== b.isPrefix) return a.isPrefix ? -1 : 1;
                return (b.count || 0) - (a.count || 0);
            });

            return filtered.slice(0, 8);
        }

        function handleInput() {
            const query = input.value;
            if (!query || query.trim().length === 0) {
                closeDropdown();
                return;
            }

            const localMatches = filterLocal(query);
            renderDropdown(localMatches, query);

            // Se tiver poucas sugestões e o backend puder complementar termos raros
            if (localMatches.length < 3 && query.trim().length >= 2 && fetchUrl) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetch(`${fetchUrl}?q=${encodeURIComponent(query.trim())}&limit=8`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.ok ? res.json() : [])
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0 && input.value === query) {
                            // Mesclar com a base de sugestões
                            data.forEach(srvItem => {
                                const exists = suggestions.some(s => normalizeStr(s.descricao) === normalizeStr(srvItem.descricao));
                                if (!exists) {
                                    suggestions.push(srvItem);
                                }
                            });
                            renderDropdown(filterLocal(query), query);
                        }
                    })
                    .catch(() => {});
                }, 200);
            }
        }

        // Ouvir digitação
        input.addEventListener('input', handleInput);
        input.addEventListener('focus', () => {
            if (input.value && input.value.trim().length > 0) {
                handleInput();
            }
        });

        // Navegação por teclado
        input.addEventListener('keydown', (e) => {
            if (dropdown.style.display === 'none') {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = activeIndex < currentMatches.length - 1 ? activeIndex + 1 : 0;
                setActive(next);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = activeIndex > 0 ? activeIndex - 1 : currentMatches.length - 1;
                setActive(prev);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (activeIndex >= 0 && activeIndex < currentMatches.length) {
                    e.preventDefault();
                    selectItem(currentMatches[activeIndex]);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!parent.contains(e.target)) {
                closeDropdown();
            }
        });

        return {
            addSuggestion(newItem) {
                if (!newItem || !newItem.descricao) return;
                const normNew = normalizeStr(newItem.descricao);
                const existingIdx = suggestions.findIndex(s => normalizeStr(s.descricao) === normNew);
                if (existingIdx >= 0) {
                    suggestions[existingIdx] = { ...suggestions[existingIdx], ...newItem };
                } else {
                    suggestions.unshift({ ...newItem, count: 1 });
                }
            },
            setSuggestions(newSuggestions) {
                suggestions = Array.isArray(newSuggestions) ? [...newSuggestions] : [];
            },
            close() {
                closeDropdown();
            }
        };
    }

    window.initTransactionAutocomplete = initTransactionAutocomplete;

})(window);
