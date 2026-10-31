// PWA de horas de estudo estilo YPT (offline-first).
// Toda a lógica roda no aparelho; o servidor só guarda e sincroniza (ver sync.js).

import {
    ArcElement, BarController, BarElement, CategoryScale, Chart, DoughnutController, Legend, LinearScale, Tooltip,
} from 'chart.js';
import { ensureUser, getMeta, loadAll, saveLocal, setMeta, uuid } from './db.js';
import { createSync } from './sync.js';
import {
    addDays, buildDaily, dayBounds, dayStats, daysUntil, findOverlaps, formatClock, formatDuration, heatLevel,
    isSuspicious, localInputToMs, monthRange, msToLocalInput, rangeStats, streak, studyDayOf, timelineSlots,
    toMs, weekRange,
} from './metrics.js';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend, LinearScale, Tooltip);

const PALETTE = ['#4F86F7', '#F25F5C', '#43AA8B', '#F9A03F', '#9B5DE5', '#00BBF9', '#F15BB5', '#7D8597', '#90BE6D', '#E07A5F'];
const MAX_SESSION_MS = 24 * 3_600_000;
const root = document.getElementById('estudos-app');
const config = root.dataset;

const state = {
    subjects: [],
    sessions: [],
    ddays: [],
    settings: null,
    tab: 'timer',
    timelineDay: null,
    statsMode: 'week',
    statsAnchor: null,
    sync: { state: 'idle', pending: 0 },
};

let charts = [];
let sync;

// ── Utilidades ───────────────────────────────────────────────────────────────

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const nowMs = () => Date.now();
const iso = (ms) => new Date(ms).toISOString();
const today = () => studyDayOf(nowMs(), state.settings);
const subjectById = (id) => state.subjects.find((s) => s.id === id);
const activeSubjects = () => state.subjects.filter((s) => !s.deleted_at && !s.arquivada_em).sort((a, b) => a.ordem - b.ordem);
const liveSessions = () => state.sessions.filter((s) => !s.deleted_at);
const runningSession = () => liveSessions().find((s) => !s.fim);

function formatDay(day, opts = { weekday: 'short', day: '2-digit', month: '2-digit' }) {
    const text = new Date(day + 'T12:00:00Z').toLocaleDateString('pt-BR', { timeZone: 'UTC', ...opts });
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function daily() {
    return buildDaily(state.sessions, state.settings, nowMs());
}

async function persist(type, record) {
    record.updated_at = iso(nowMs());
    const list = type === 'settings' ? null : state[type];
    if (list) {
        const index = list.findIndex((r) => r.id === record.id);
        if (index >= 0) list[index] = record; else list.push(record);
    } else {
        state.settings = record;
    }
    await saveLocal(type, record);
    sync?.schedule();
}

async function markSeen() {
    await setMeta('last_seen', nowMs());
}

// ── Ações do cronômetro ──────────────────────────────────────────────────────

async function stopSession(session, atMs) {
    await persist('sessions', { ...session, fim: iso(Math.max(atMs, toMs(session.inicio))) });
}

async function toggleSubject(subjectId) {
    const at = nowMs();
    const running = runningSession();
    if (running) {
        await stopSession(running, at);
        if (running.subject_id === subjectId) return render();
    }
    await persist('sessions', {
        id: uuid(),
        subject_id: subjectId,
        inicio: iso(at),
        fim: null,
        origem: 'timer',
        status: 'ok',
        device_id: await getMeta('device_id'),
        deleted_at: null,
        created_at: iso(at),
    });
    render();
}

async function checkSuspicious() {
    const running = runningSession();
    if (!running || !isSuspicious(running, state.settings, nowMs())) return;
    if ((await getMeta('suspicion_ack')) === running.id) return;
    if (running.status !== 'suspeita') await persist('sessions', { ...running, status: 'suspeita' });
    render();
    openSuspiciousDialog(runningSession());
}

// ── Renderização ─────────────────────────────────────────────────────────────

function render() {
    charts.forEach((c) => c.destroy());
    charts = [];
    const views = { timer: renderTimer, timeline: renderTimeline, stats: renderStats, settings: renderSettings };
    root.querySelector('[data-view]').innerHTML = views[state.tab]();
    root.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('active', b.dataset.tab === state.tab));
    renderSyncBadge();
    if (state.tab === 'stats') mountStatsCharts();
    tick();
}

function renderSyncBadge() {
    const s = state.sync;
    const labels = {
        ok: ['ok', '✓ Sincronizado'],
        idle: ['pending', '↻ Aguardando'],
        syncing: ['pending', '↻ Sincronizando…'],
        pending: ['pending', `↻ Pendentes (${s.pending})`],
        offline: ['offline', `⌁ Offline${s.pending ? ` · ${s.pending} pendentes` : ''}`],
        error: ['error', `⚠ ${s.message || 'Erro de sincronização'}`],
        login: ['error', '⚠ Entre novamente para sincronizar'],
    };
    const [kind, text] = labels[s.state] ?? labels.idle;
    const badge = root.querySelector('[data-sync-badge]');
    badge.className = `sync-badge ${kind}`;
    badge.innerHTML = s.state === 'login' ? `<a href="${esc(config.loginUrl)}">${esc(text)}</a>` : esc(text);

    const banner = root.querySelector('[data-conflicts]');
    const conflicts = (s.conflicts ?? []).filter((c) => state.sessions.some((x) => x.id === c.session_id && !x.deleted_at));
    banner.hidden = conflicts.length === 0;
    if (conflicts.length) {
        const session = state.sessions.find((x) => x.id === conflicts[0].session_id);
        const day = studyDayOf(toMs(session.inicio), state.settings);
        banner.innerHTML = `⚠ ${conflicts.length} sessão(ões) sobreposta(s) vindas de outro aparelho.
            <button type="button" data-action="goto-day" data-day="${day}">Revisar ${formatDay(day)}</button>`;
    }
}

function metaBar(stats) {
    const meta = state.settings.meta_diaria_min;
    if (!meta) return '';
    const pct = Math.min(100, (stats.totalMs / (meta * 60_000)) * 100);
    return `<div class="goal"><div class="goal-bar"><span data-live="goal-bar" style="width:${pct}%"></span></div>
        <small>Meta do dia: ${formatDuration(meta * 60_000)} · <span data-live="goal-pct">${Math.floor(pct)}</span>%</small></div>`;
}

function ddayChips() {
    const t = today();
    return state.ddays
        .filter((d) => !d.deleted_at && daysUntil(d.data, t) >= 0)
        .sort((a, b) => a.data.localeCompare(b.data))
        .map((d) => {
            const n = daysUntil(d.data, t);
            return `<span class="dday">${esc(d.titulo)} <b>${n === 0 ? 'D-DAY' : `D-${n}`}</b></span>`;
        })
        .join('');
}

function renderTimer() {
    const day = today();
    const stats = dayStats(daily(), day);
    const running = runningSession();
    const subjects = activeSubjects();

    const rows = subjects.map((s) => {
        const isRunning = running?.subject_id === s.id;
        return `<li class="subject ${isRunning ? 'running' : ''}" style="--c:${esc(s.cor)}">
            <button type="button" class="play" data-action="toggle" data-id="${s.id}" aria-label="${isRunning ? 'Parar' : 'Iniciar'} ${esc(s.nome)}">
                ${isRunning ? '❚❚' : '▶'}
            </button>
            <span class="name">${esc(s.nome)}</span>
            <span class="time" data-live="subject" data-id="${s.id}">${formatDuration(stats.bySubject[s.id] ?? 0, { seconds: true })}</span>
        </li>`;
    }).join('');

    return `
        <section class="today">
            <div class="ddays">${ddayChips()}</div>
            <p class="day-label">${formatDay(day, { weekday: 'long', day: '2-digit', month: 'long' })}</p>
            <p class="total" data-live="total">${formatDuration(stats.totalMs, { seconds: true })}</p>
            ${metaBar(stats)}
            <div class="mini-stats">
                <div><small>Máx. foco</small><b data-live="maxfocus">${formatDuration(stats.maxFocusMs)}</b></div>
                <div><small>Início</small><b>${stats.startMs ? formatClock(stats.startMs, day, state.settings) : '--:--'}</b></div>
                <div><small>Término</small><b data-live="end">${stats.endMs ? formatClock(stats.endMs, day, state.settings) : '--:--'}</b></div>
            </div>
        </section>
        ${running ? `<section class="now-running" style="--c:${esc(subjectById(running.subject_id)?.cor ?? '#888')}">
            <span>${esc(subjectById(running.subject_id)?.nome ?? 'Matéria')}</span>
            <b data-live="session">${formatDuration(nowMs() - toMs(running.inicio), { seconds: true })}</b>
            <button type="button" data-action="toggle" data-id="${running.subject_id}">Parar</button>
        </section>` : ''}
        <ul class="subjects">${rows || '<li class="empty">Crie sua primeira matéria para começar.</li>'}</ul>
        <button type="button" class="btn ghost" data-action="new-subject">+ Matéria</button>`;
}

function renderTimeline() {
    const day = state.timelineDay ?? today();
    const stats = dayStats(daily(), day);
    const slots = timelineSlots(stats, state.settings);
    const virada = state.settings.virada_hora;

    let grid = '';
    for (let h = 0; h < 24; h++) {
        const cells = slots.slice(h * 6, h * 6 + 6).map((slot) => {
            if (!slot) return '<i></i>';
            const color = subjectById(slot.subjectId)?.cor ?? '#888';
            return `<i class="${slot.manual ? 'manual' : ''}" style="--c:${esc(color)}"></i>`;
        }).join('');
        grid += `<div class="hour"><span>${String((virada + h) % 24).padStart(2, '0')}</span>${cells}</div>`;
    }

    const [dayStart, dayEnd] = dayBounds(day, state.settings);
    const sessions = liveSessions()
        .filter((s) => toMs(s.inicio) < dayEnd && (s.fim ? toMs(s.fim) : nowMs()) > dayStart)
        .sort((a, b) => toMs(a.inicio) - toMs(b.inicio));

    const list = sessions.map((s) => {
        const subject = subjectById(s.subject_id);
        const end = s.fim ? toMs(s.fim) : nowMs();
        const overlap = findOverlaps(s, liveSessions(), nowMs()).length > 0;
        return `<li style="--c:${esc(subject?.cor ?? '#888')}">
            <span class="dot"></span>
            <span class="name">${esc(subject?.nome ?? 'Matéria removida')}
                ${s.origem === 'manual' ? '<em>manual</em>' : ''}
                ${s.status === 'suspeita' ? '<em class="warn">a confirmar</em>' : ''}
                ${overlap ? '<em class="warn">sobreposta</em>' : ''}</span>
            <span class="range">${formatClock(toMs(s.inicio), day, state.settings)} – ${s.fim ? formatClock(end, day, state.settings) : 'agora'}</span>
            <span class="dur">${formatDuration(end - toMs(s.inicio))}</span>
            ${s.fim ? `<button type="button" data-action="edit-session" data-id="${s.id}" aria-label="Editar">✎</button>` : ''}
        </li>`;
    }).join('');

    return `
        <nav class="day-nav">
            <button type="button" data-action="day-prev" aria-label="Dia anterior">‹</button>
            <b>${formatDay(day, { weekday: 'long', day: '2-digit', month: '2-digit' })}</b>
            <button type="button" data-action="day-next" aria-label="Próximo dia" ${day >= today() ? 'disabled' : ''}>›</button>
        </nav>
        <p class="center muted">Total ${formatDuration(stats.totalMs)} · Máx. foco ${formatDuration(stats.maxFocusMs)}</p>
        <div class="planner">${grid}</div>
        <h3>Sessões</h3>
        <ul class="session-list">${list || '<li class="empty">Nenhuma sessão neste dia.</li>'}</ul>
        <button type="button" class="btn ghost" data-action="new-session" data-day="${day}">+ Sessão manual</button>`;
}

function statsRange() {
    const anchor = state.statsAnchor ?? today();
    if (state.statsMode === 'day') return [anchor, anchor];
    if (state.statsMode === 'week') return weekRange(anchor);
    return monthRange(anchor);
}

function renderStats() {
    const d = daily();
    const t = today();
    const [from, to] = statsRange();
    const range = rangeStats(d, from, to, t);
    const mode = state.statsMode;
    const single = mode === 'day' ? dayStats(d, from) : null;
    const title = mode === 'day'
        ? formatDay(from, { weekday: 'long', day: '2-digit', month: '2-digit' })
        : mode === 'week' ? `${formatDay(from, { day: '2-digit', month: '2-digit' })} – ${formatDay(to, { day: '2-digit', month: '2-digit' })}`
            : formatDay(from, { month: 'long', year: 'numeric' });

    const cards = [
        ['Total', formatDuration(range.totalMs)],
        mode === 'day' ? ['Máx. foco', formatDuration(single.maxFocusMs)] : ['Média diária', formatDuration(range.avgPerDayMs)],
        mode === 'day'
            ? ['Início / término', single.startMs ? `${formatClock(single.startMs, from, state.settings)} – ${formatClock(single.endMs, from, state.settings)}` : '--']
            : ['Dias estudados', `${range.daysStudied} / ${range.days.length}`],
        mode === 'day' ? ['Sequência', `${streak(d, t)} dias`] : ['Máx. foco', formatDuration(range.maxFocusMs)],
    ].map(([label, value]) => `<div class="card"><small>${label}</small><b>${value}</b></div>`).join('');

    const subjects = Object.entries(range.bySubject).sort((a, b) => b[1] - a[1]);
    const subjectRows = subjects.map(([id, ms]) => {
        const s = subjectById(id);
        const pct = range.totalMs ? Math.round((ms / range.totalMs) * 100) : 0;
        return `<li style="--c:${esc(s?.cor ?? '#888')}"><span class="dot"></span><span class="name">${esc(s?.nome ?? 'Removida')}</span>
            <span>${formatDuration(ms)}</span><span class="muted">${pct}%</span></li>`;
    }).join('');

    return `
        <div class="segmented">
            ${['day', 'week', 'month'].map((m) => `<button type="button" data-action="stats-mode" data-mode="${m}" class="${m === mode ? 'active' : ''}">${{ day: 'Dia', week: 'Semana', month: 'Mês' }[m]}</button>`).join('')}
        </div>
        <nav class="day-nav">
            <button type="button" data-action="stats-prev" aria-label="Anterior">‹</button>
            <b>${title}</b>
            <button type="button" data-action="stats-next" aria-label="Próximo" ${to >= t ? 'disabled' : ''}>›</button>
        </nav>
        <div class="cards">${cards}</div>
        ${mode !== 'day' ? '<div class="chart-box"><canvas data-chart="bars"></canvas></div>' : ''}
        ${mode === 'month' ? renderMonthCalendar(range, from) : ''}
        <h3>Por matéria</h3>
        ${subjects.length ? `<div class="chart-box small"><canvas data-chart="donut"></canvas></div>` : ''}
        <ul class="subject-share">${subjectRows || '<li class="empty">Sem estudo no período.</li>'}</ul>
        <h3>Últimas 15 semanas</h3>
        ${renderHeatmap(d, t)}
        <p class="center muted">Sequência atual: <b>${streak(d, t)} dias</b></p>`;
}

function renderMonthCalendar(range, from) {
    const offset = (new Date(from + 'T00:00:00Z').getUTCDay() + 6) % 7;
    const blanks = '<i class="blank"></i>'.repeat(offset);
    const cells = range.days.map((s) => `<i class="lv${heatLevel(s.totalMs)}" title="${formatDay(s.day)}: ${formatDuration(s.totalMs)}">${Number(s.day.slice(8))}</i>`).join('');
    return `<div class="calendar"><b>S</b><b>T</b><b>Q</b><b>Q</b><b>S</b><b>S</b><b>D</b>${blanks}${cells}</div>`;
}

function renderHeatmap(d, t) {
    const [weekStart] = weekRange(t);
    const start = addDays(weekStart, -14 * 7);
    let cols = '';
    for (let w = 0; w < 15; w++) {
        let col = '';
        for (let i = 0; i < 7; i++) {
            const day = addDays(start, w * 7 + i);
            const ms = d.get(day)?.totalMs ?? 0;
            col += day > t ? '<i class="blank"></i>' : `<i class="lv${heatLevel(ms)}" title="${formatDay(day)}: ${formatDuration(ms)}"></i>`;
        }
        cols += `<div>${col}</div>`;
    }
    return `<div class="heatmap">${cols}</div>`;
}

function mountStatsCharts() {
    const [from, to] = statsRange();
    const range = rangeStats(daily(), from, to, today());
    const subjectIds = Object.keys(range.bySubject);
    const color = (id) => subjectById(id)?.cor ?? '#888';
    const name = (id) => subjectById(id)?.nome ?? 'Removida';
    const hours = (ms) => +(ms / 3_600_000).toFixed(2);
    const tooltip = { callbacks: { label: (ctx) => `${ctx.dataset.label ?? ctx.label}: ${formatDuration(ctx.raw * 3_600_000)}` } };

    const bars = root.querySelector('[data-chart="bars"]');
    if (bars) {
        charts.push(new Chart(bars, {
            type: 'bar',
            data: {
                labels: range.days.map((s) => (state.statsMode === 'week' ? formatDay(s.day, { weekday: 'short' }) : Number(s.day.slice(8)))),
                datasets: subjectIds.map((id) => ({
                    label: name(id),
                    data: range.days.map((s) => hours(s.bySubject[id] ?? 0)),
                    backgroundColor: color(id),
                    borderRadius: 3,
                })),
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, ticks: { callback: (v) => `${v}h` } } },
            },
        }));
    }

    const donut = root.querySelector('[data-chart="donut"]');
    if (donut) {
        charts.push(new Chart(donut, {
            type: 'doughnut',
            data: {
                labels: subjectIds.map(name),
                datasets: [{ data: subjectIds.map((id) => hours(range.bySubject[id])), backgroundColor: subjectIds.map(color), borderWidth: 0 }],
            },
            options: { maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false }, tooltip } },
        }));
    }
}

function renderSettings() {
    const s = state.settings;
    const all = state.subjects.filter((x) => !x.deleted_at).sort((a, b) => a.ordem - b.ordem);
    const subjectRows = all.map((x, i) => `<li style="--c:${esc(x.cor)}" class="${x.arquivada_em ? 'archived' : ''}">
        <span class="dot"></span><span class="name">${esc(x.nome)}${x.arquivada_em ? ' <em>arquivada</em>' : ''}</span>
        <button type="button" data-action="move-subject" data-id="${x.id}" data-dir="-1" ${i === 0 ? 'disabled' : ''} aria-label="Subir">↑</button>
        <button type="button" data-action="move-subject" data-id="${x.id}" data-dir="1" ${i === all.length - 1 ? 'disabled' : ''} aria-label="Descer">↓</button>
        <button type="button" data-action="edit-subject" data-id="${x.id}" aria-label="Editar">✎</button>
    </li>`).join('');

    const ddays = state.ddays.filter((d) => !d.deleted_at).sort((a, b) => a.data.localeCompare(b.data)).map((d) => `<li>
        <span class="name">${esc(d.titulo)}</span><span class="muted">${formatDay(d.data, { day: '2-digit', month: '2-digit', year: 'numeric' })}</span>
        <button type="button" data-action="delete-dday" data-id="${d.id}" aria-label="Excluir">✕</button>
    </li>`).join('');

    const st = state.sync;
    return `
        <h3>Matérias</h3>
        <ul class="manage">${subjectRows || '<li class="empty">Nenhuma matéria.</li>'}</ul>
        <button type="button" class="btn ghost" data-action="new-subject">+ Matéria</button>

        <h3>Preferências</h3>
        <form class="form" data-form="settings">
            <label>Virada do dia (hora)
                <input type="number" name="virada_hora" min="0" max="12" value="${s.virada_hora}" required>
                <small>Estudo antes dessa hora conta para o dia anterior.</small></label>
            <label>Meta diária (minutos)
                <input type="number" name="meta_diaria_min" min="0" max="1440" step="5" value="${s.meta_diaria_min ?? ''}" placeholder="Sem meta"></label>
            <label>Confirmar sessão aberta após (minutos)
                <input type="number" name="limite_suspeita_min" min="30" max="1440" step="10" value="${s.limite_suspeita_min}" required></label>
            <label>Fuso horário
                <input name="timezone" value="${esc(s.timezone)}" required list="estudos-timezones">
                <datalist id="estudos-timezones">${['America/Sao_Paulo', 'America/Cuiaba', 'America/Manaus', 'America/Belem', 'America/Fortaleza', 'America/Recife', 'America/Rio_Branco', 'America/Noronha']
                    .map((tz) => `<option value="${tz}">`).join('')}</datalist>
                <small>Este aparelho: ${esc(Intl.DateTimeFormat().resolvedOptions().timeZone)}</small></label>
            <button class="btn" type="submit">Salvar preferências</button>
        </form>

        <h3>D-Day</h3>
        <ul class="manage">${ddays || '<li class="empty">Nenhuma contagem regressiva.</li>'}</ul>
        <form class="form inline" data-form="dday">
            <input name="titulo" maxlength="80" placeholder="Ex.: Prova TRF" required>
            <input type="date" name="data" required>
            <button class="btn" type="submit">Adicionar</button>
        </form>

        <h3>Sincronização</h3>
        <p class="muted">${st.lastSyncAt ? `Última: ${new Date(st.lastSyncAt).toLocaleString('pt-BR')}` : 'Ainda não sincronizado nesta sessão.'}
            ${st.pending ? ` · ${st.pending} alteração(ões) pendente(s)` : ''}</p>
        ${(st.rejected ?? []).length ? `<p class="warn">${st.rejected.length} item(ns) recusado(s) pelo servidor: ${esc(st.rejected.map((r) => r.motivo).join('; '))}</p>` : ''}
        <button type="button" class="btn ghost" data-action="sync-now">Sincronizar agora</button>
        <a class="btn ghost" href="${esc(config.homeUrl)}">← Voltar ao sistema</a>`;
}

// Atualiza apenas os números vivos (evita re-renderizar a tela toda a cada segundo).
function tick() {
    const running = runningSession();
    if (state.tab !== 'timer') return;
    const day = today();
    const stats = dayStats(daily(), day);
    const set = (sel, text) => root.querySelectorAll(sel).forEach((el) => { el.textContent = text; });
    set('[data-live="total"]', formatDuration(stats.totalMs, { seconds: true }));
    set('[data-live="maxfocus"]', formatDuration(stats.maxFocusMs));
    if (stats.endMs) set('[data-live="end"]', formatClock(stats.endMs, day, state.settings));
    root.querySelectorAll('[data-live="subject"]').forEach((el) => {
        el.textContent = formatDuration(stats.bySubject[el.dataset.id] ?? 0, { seconds: true });
    });
    if (running) set('[data-live="session"]', formatDuration(nowMs() - toMs(running.inicio), { seconds: true }));
    const meta = state.settings.meta_diaria_min;
    if (meta) {
        const pct = Math.min(100, (stats.totalMs / (meta * 60_000)) * 100);
        root.querySelectorAll('[data-live="goal-bar"]').forEach((el) => { el.style.width = `${pct}%`; });
        set('[data-live="goal-pct"]', String(Math.floor(pct)));
    }
}

// ── Diálogos ─────────────────────────────────────────────────────────────────

const dialog = document.getElementById('estudos-dialog');

function openDialog(html, onSubmit) {
    dialog.innerHTML = `<form method="dialog" class="form">${html}</form>`;
    const form = dialog.querySelector('form');
    form.addEventListener('submit', async (event) => {
        const action = event.submitter?.value;
        if (action === 'cancel') return;
        event.preventDefault();
        const error = await onSubmit(action, new FormData(form));
        if (error) {
            form.querySelector('.form-error').textContent = error;
            return;
        }
        dialog.close();
        render();
    });
    dialog.showModal();
}

function colorPicker(current) {
    return `<div class="palette">${PALETTE.map((c) => `<label style="--c:${c}">
        <input type="radio" name="cor" value="${c}" ${c === current ? 'checked' : ''} required><span></span></label>`).join('')}</div>`;
}

function openSubjectDialog(subject = null) {
    const used = new Set(state.subjects.map((s) => s.cor));
    const defaultColor = subject?.cor ?? PALETTE.find((c) => !used.has(c)) ?? PALETTE[0];
    const hasSessions = subject && state.sessions.some((s) => s.subject_id === subject.id && !s.deleted_at);
    openDialog(`
        <h3>${subject ? 'Editar matéria' : 'Nova matéria'}</h3>
        <label>Nome<input name="nome" maxlength="60" required value="${esc(subject?.nome ?? '')}" autofocus></label>
        <fieldset><legend>Cor</legend>${colorPicker(defaultColor)}</fieldset>
        <p class="form-error"></p>
        <div class="actions">
            <button class="btn" value="save">Salvar</button>
            <button class="btn ghost" value="cancel" formnovalidate>Cancelar</button>
            ${subject ? `<button class="btn ghost" value="archive" formnovalidate>${subject.arquivada_em ? 'Desarquivar' : 'Arquivar'}</button>` : ''}
            ${subject && !hasSessions ? '<button class="btn danger" value="delete" formnovalidate>Excluir</button>' : ''}
        </div>
        ${subject && hasSessions ? '<small class="muted">Matérias com sessões não podem ser excluídas, só arquivadas: as horas continuam no histórico.</small>' : ''}`,
    async (action, data) => {
        if (action === 'archive') {
            await persist('subjects', { ...subject, arquivada_em: subject.arquivada_em ? null : iso(nowMs()) });
            return null;
        }
        if (action === 'delete') {
            if (!confirm(`Excluir a matéria "${subject.nome}"?`)) return 'Exclusão cancelada.';
            await persist('subjects', { ...subject, deleted_at: iso(nowMs()) });
            return null;
        }
        const nome = String(data.get('nome')).trim();
        if (!nome) return 'Informe o nome.';
        const ordem = subject?.ordem ?? Math.max(-1, ...state.subjects.map((s) => s.ordem)) + 1;
        await persist('subjects', {
            id: subject?.id ?? uuid(), nome, cor: data.get('cor'), ordem,
            arquivada_em: subject?.arquivada_em ?? null, deleted_at: null, created_at: subject?.created_at ?? iso(nowMs()),
        });
        return null;
    });
}

function openSessionDialog(session = null, day = today()) {
    const tz = state.settings.timezone;
    const subjects = state.subjects.filter((s) => !s.deleted_at && (!s.arquivada_em || s.id === session?.subject_id));
    if (!subjects.length) return alert('Crie uma matéria primeiro.');
    const [dayStart] = dayBounds(day, state.settings);
    const defaultStart = session ? toMs(session.inicio) : Math.min(dayStart + 6 * 3_600_000, nowMs() - 3_600_000);
    const defaultEnd = session?.fim ? toMs(session.fim) : defaultStart + 3_600_000;

    openDialog(`
        <h3>${session ? 'Editar sessão' : 'Sessão manual'}</h3>
        <label>Matéria<select name="subject_id" required>
            ${subjects.map((s) => `<option value="${s.id}" ${s.id === session?.subject_id ? 'selected' : ''}>${esc(s.nome)}</option>`).join('')}
        </select></label>
        <label>Início<input type="datetime-local" name="inicio" required value="${msToLocalInput(defaultStart, tz)}"></label>
        <label>Fim<input type="datetime-local" name="fim" required value="${msToLocalInput(defaultEnd, tz)}"></label>
        ${!session || session.origem === 'manual' ? '<small class="muted">Sessões manuais contam no total, mas não no máximo de foco nem no início/término.</small>' : ''}
        <p class="form-error"></p>
        <div class="actions">
            <button class="btn" value="save">Salvar</button>
            <button class="btn ghost" value="cancel" formnovalidate>Cancelar</button>
            ${session ? '<button class="btn danger" value="delete" formnovalidate>Excluir</button>' : ''}
        </div>`,
    async (action, data) => {
        if (action === 'delete') {
            if (!confirm('Excluir esta sessão?')) return 'Exclusão cancelada.';
            await persist('sessions', { ...session, deleted_at: iso(nowMs()) });
            return null;
        }
        const inicio = localInputToMs(String(data.get('inicio')), tz);
        const fim = localInputToMs(String(data.get('fim')), tz);
        if (!(fim > inicio)) return 'O fim precisa ser depois do início.';
        if (fim - inicio > MAX_SESSION_MS) return 'Uma sessão pode ter no máximo 24h.';
        if (fim > nowMs() + 60_000) return 'A sessão não pode terminar no futuro.';
        const candidate = {
            ...(session ?? { id: uuid(), origem: 'manual', device_id: await getMeta('device_id'), created_at: iso(nowMs()) }),
            subject_id: String(data.get('subject_id')), inicio: iso(inicio), fim: iso(fim), status: 'ok', deleted_at: null,
        };
        const overlaps = findOverlaps(candidate, liveSessions(), nowMs());
        if (overlaps.length) {
            const o = overlaps[0];
            const d = studyDayOf(toMs(o.inicio), state.settings);
            return `Conflito com ${subjectById(o.subject_id)?.nome ?? 'outra sessão'} (${formatClock(toMs(o.inicio), d, state.settings)} – ${o.fim ? formatClock(toMs(o.fim), d, state.settings) : 'agora'}).`;
        }
        await persist('sessions', candidate);
        return null;
    });
}

async function openSuspiciousDialog(session) {
    const tz = state.settings.timezone;
    const subject = subjectById(session.subject_id);
    const lastSeen = await getMeta('last_seen', nowMs());
    const suggestion = Math.max(toMs(session.inicio), Math.min(lastSeen, nowMs()));
    openDialog(`
        <h3>Ainda está estudando?</h3>
        <p>O cronômetro de <b>${esc(subject?.nome ?? 'Matéria')}</b> está ligado há
            <b>${formatDuration(nowMs() - toMs(session.inicio))}</b>. Enquanto você não confirmar, essa sessão não entra nas estatísticas.</p>
        <label>Parei em<input type="datetime-local" name="fim" value="${msToLocalInput(suggestion, tz)}"></label>
        <p class="form-error"></p>
        <div class="actions">
            <button class="btn" value="stop">Parei nesse horário</button>
            <button class="btn ghost" value="continue" formnovalidate>Continuar</button>
            <button class="btn danger" value="discard" formnovalidate>Descartar sessão</button>
        </div>`,
    async (action, data) => {
        const current = state.sessions.find((s) => s.id === session.id);
        if (action === 'discard') {
            await persist('sessions', { ...current, deleted_at: iso(nowMs()) });
        } else if (action === 'continue') {
            await setMeta('suspicion_ack', current.id);
            await persist('sessions', { ...current, status: 'ok' });
        } else {
            const fim = localInputToMs(String(data.get('fim')), tz);
            if (fim < toMs(current.inicio) || fim > nowMs()) return 'Escolha um horário entre o início da sessão e agora.';
            if (fim - toMs(current.inicio) > MAX_SESSION_MS) return 'Uma sessão pode ter no máximo 24h.';
            await persist('sessions', { ...current, fim: iso(fim), status: 'ok' });
        }
        return null;
    });
}

// ── Eventos ──────────────────────────────────────────────────────────────────

function shiftStats(direction) {
    const anchor = state.statsAnchor ?? today();
    const [from, to] = statsRange();
    if (state.statsMode === 'day') state.statsAnchor = addDays(anchor, direction);
    else if (state.statsMode === 'week') state.statsAnchor = addDays(from, direction * 7);
    else state.statsAnchor = direction < 0 ? addDays(from, -1) : addDays(to, 1);
}

root.addEventListener('click', async (event) => {
    markSeen();
    const tab = event.target.closest('[data-tab]');
    if (tab) {
        state.tab = tab.dataset.tab;
        return render();
    }
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const { action, id } = button.dataset;
    const handlers = {
        toggle: () => toggleSubject(id),
        'new-subject': () => openSubjectDialog(),
        'edit-subject': () => openSubjectDialog(subjectById(id)),
        'move-subject': async () => {
            const list = state.subjects.filter((x) => !x.deleted_at).sort((a, b) => a.ordem - b.ordem);
            const i = list.findIndex((x) => x.id === id);
            const j = i + Number(button.dataset.dir);
            if (j < 0 || j >= list.length) return;
            [list[i], list[j]] = [list[j], list[i]];
            for (const [ordem, subject] of list.entries()) {
                if (subject.ordem !== ordem) await persist('subjects', { ...subject, ordem });
            }
            render();
        },
        'new-session': () => openSessionDialog(null, button.dataset.day),
        'edit-session': () => openSessionDialog(state.sessions.find((s) => s.id === id), state.timelineDay ?? today()),
        'day-prev': () => { state.timelineDay = addDays(state.timelineDay ?? today(), -1); render(); },
        'day-next': () => { state.timelineDay = addDays(state.timelineDay ?? today(), 1); render(); },
        'goto-day': () => { state.tab = 'timeline'; state.timelineDay = button.dataset.day; render(); },
        'stats-mode': () => { state.statsMode = button.dataset.mode; render(); },
        'stats-prev': () => { shiftStats(-1); render(); },
        'stats-next': () => { shiftStats(1); render(); },
        'delete-dday': async () => {
            const dday = state.ddays.find((d) => d.id === id);
            if (confirm(`Excluir "${dday.titulo}"?`)) { await persist('ddays', { ...dday, deleted_at: iso(nowMs()) }); render(); }
        },
        'sync-now': () => sync.syncNow(),
    };
    await handlers[action]?.();
});

root.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-form]');
    if (!form) return;
    event.preventDefault();
    const data = new FormData(form);
    if (form.dataset.form === 'settings') {
        const meta = String(data.get('meta_diaria_min') ?? '').trim();
        const timezone = String(data.get('timezone')).trim();
        try {
            new Intl.DateTimeFormat('pt-BR', { timeZone: timezone });
        } catch {
            return alert(`Fuso horário inválido: ${timezone}`);
        }
        await persist('settings', {
            ...state.settings,
            timezone,
            virada_hora: Number(data.get('virada_hora')),
            limite_suspeita_min: Number(data.get('limite_suspeita_min')),
            meta_diaria_min: meta === '' ? null : Number(meta),
        });
    } else if (form.dataset.form === 'dday') {
        await persist('ddays', { id: uuid(), titulo: String(data.get('titulo')).trim(), data: String(data.get('data')), deleted_at: null, created_at: iso(nowMs()) });
    }
    render();
});

document.addEventListener('visibilitychange', async () => {
    if (document.visibilityState === 'hidden') {
        await markSeen();
    } else {
        render();
        checkSuspicious();
    }
});

// ── Inicialização ────────────────────────────────────────────────────────────

async function reload() {
    Object.assign(state, await loadAll());
}

async function boot() {
    await ensureUser(Number(config.userId));
    await reload();

    sync = createSync({
        syncUrl: config.syncUrl,
        tokenUrl: config.tokenUrl,
        initialToken: config.csrf,
        onStatus: (status) => { state.sync = status; renderSyncBadge(); },
        onApplied: async () => {
            await reload();
            if (!dialog.open) render();
            checkSuspicious();
        },
    });

    render();
    setInterval(tick, 1000);
    await checkSuspicious();
    sync.start();

    if ('serviceWorker' in navigator && config.swUrl) {
        navigator.serviceWorker.register(config.swUrl, { scope: config.swScope }).catch((error) => console.warn('SW:', error));
    }
}

boot();
