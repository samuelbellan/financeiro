// Widget flutuante do cronômetro (Document Picture-in-Picture, Chrome/Edge no PC).
// widgetView() é lógica pura (testada em tests/js/estudos/widget.test.js); createWidget() cuida da janela.
// O relógio roda dentro da janela do widget: ela fica visível, então o Chrome não desacelera o timer
// mesmo com o app minimizado.

import { buildDaily, dayBounds, dayStats, formatDuration, lastStopped, studyDayOf, toMs } from './metrics.js';

// Altura útil do conteúdo; o Chrome desconta ~56 px da altura pedida para a própria barra da janela.
const SIZE = { width: 260, height: 100 };
const WINDOW_CHROME_HEIGHT = 56;
const HINT_EXTRA_HEIGHT = 34;

export const widgetSupported = () => typeof window !== 'undefined' && 'documentPictureInPicture' in window;

/**
 * O que o widget mostra no instante nowMs.
 * mode: 'running' (sessão aberta), 'rest' (até 6h depois da última sessão do cronômetro) ou 'idle'.
 * resumeId: matéria que o "Retomar" inicia; null se a última foi arquivada/excluída (o botão vira "Abrir app").
 */
export function widgetView({ sessions, subjects, settings }, nowMs) {
    const live = sessions.filter((s) => !s.deleted_at);
    const day = studyDayOf(nowMs, settings);
    const [dayStart] = dayBounds(day, settings);
    const todayMs = dayStats(buildDaily(live.filter((s) => !s.fim || toMs(s.fim) > dayStart), settings, nowMs), day).totalMs;
    const subjectOf = (id) => subjects.find((x) => x.id === id) ?? null;

    const running = live.find((s) => !s.fim);
    if (running) {
        return { mode: 'running', subject: subjectOf(running.subject_id), subjectId: running.subject_id, sessionMs: nowMs - toMs(running.inicio), todayMs };
    }

    let last = null;
    for (const s of live) {
        if (s.origem === 'timer' && (!last || toMs(s.fim) > toMs(last.fim))) last = s;
    }
    const subject = last ? subjectOf(last.subject_id) : null;
    const resumeId = subject && !subject.deleted_at && !subject.arquivada_em ? subject.id : null;
    const rest = lastStopped(live, nowMs);
    return { mode: rest ? 'rest' : 'idle', subject, subjectId: last?.subject_id ?? null, restMs: rest?.sinceMs ?? null, todayMs, resumeId };
}

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/**
 * @param {{ getState: () => object, onToggle: (subjectId: string) => void, onClose?: () => void }} options
 */
export function createWidget({ getState, onToggle, onClose }) {
    let win = null;
    let shapeKey = null;

    function shape(view, hint) {
        const color = view.mode === 'running' ? (view.subject?.cor ?? '#888') : 'var(--muted)';
        const name = view.mode === 'running'
            ? (view.subject?.nome ?? 'Matéria')
            : (view.mode === 'rest' ? 'Descanso' : 'Pronto para estudar');
        let button;
        if (view.mode === 'running') {
            button = `<button type="button" class="w-btn" data-w="toggle" data-id="${esc(view.subjectId)}">⏸ Parar</button>`;
        } else if (view.resumeId) {
            button = `<button type="button" class="w-btn" data-w="toggle" data-id="${esc(view.resumeId)}" title="Retomar ${esc(view.subject.nome)}">▶ Retomar</button>`;
        } else {
            button = '<button type="button" class="w-btn" data-w="app">Abrir app</button>';
        }
        return `<div class="w" style="--c:${esc(color)}">
            <div class="w-top"><span class="w-name">${esc(name)}</span>
                <button type="button" class="w-app" data-w="app" title="Voltar ao app" aria-label="Voltar ao app">↗</button></div>
            <div class="w-time ${view.mode === 'running' ? '' : 'rest'}" data-w-time>--</div>
            <div class="w-bottom"><span class="w-today">Hoje <b data-w-today>--</b></span>${button}</div>
            ${hint ? '<p class="w-hint">Minimize o app em vez de fechar: fechar o app fecha o widget.</p>' : ''}
        </div>`;
    }

    function refresh() {
        if (!win) return;
        const view = widgetView(getState(), Date.now());
        const key = [view.mode, view.subjectId, view.resumeId, view.subject?.nome, view.subject?.cor].join('|');
        if (key !== shapeKey) {
            shapeKey = key;
            win.document.body.innerHTML = shape(view, win.showHint);
        }
        const time = view.mode === 'running' ? view.sessionMs : view.restMs;
        win.document.querySelector('[data-w-time]').textContent = time === null ? '--:--:--' : formatDuration(time, { seconds: true });
        win.document.querySelector('[data-w-today]').textContent = formatDuration(view.todayMs);
    }

    function copyStyles(target) {
        for (const sheet of document.styleSheets) {
            try {
                const style = target.document.createElement('style');
                style.textContent = [...sheet.cssRules].map((rule) => rule.cssText).join('\n');
                target.document.head.append(style);
            } catch {
                if (!sheet.href) continue;
                const link = target.document.createElement('link');
                link.rel = 'stylesheet';
                link.href = sheet.href;
                target.document.head.append(link);
            }
        }
    }

    /** Precisa ser chamada dentro de um clique (exigência do Chrome). */
    async function open({ hint = false } = {}) {
        if (!widgetSupported()) return false;
        if (win) return true;
        try {
            win = await window.documentPictureInPicture.requestWindow({
                width: SIZE.width,
                height: SIZE.height + WINDOW_CHROME_HEIGHT + (hint ? HINT_EXTRA_HEIGHT : 0),
            });
        } catch (error) {
            console.warn('Widget:', error);
            win = null;
            return false;
        }
        win.showHint = hint;
        win.document.title = 'Estudos';
        win.document.body.classList.add('widget-body');
        copyStyles(win);
        win.document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-w]');
            if (!button) return;
            if (button.dataset.w === 'toggle') onToggle(button.dataset.id);
            if (button.dataset.w === 'app') window.focus();
        });
        win.addEventListener('pagehide', () => {
            win = null;
            shapeKey = null;
            onClose?.();
        });
        shapeKey = null;
        refresh();
        win.setInterval(refresh, 1000);
        return true;
    }

    return { open, refresh, isOpen: () => win !== null };
}
