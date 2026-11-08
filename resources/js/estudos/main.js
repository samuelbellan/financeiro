// PWA de horas de estudo estilo YPT (offline-first).
// Toda a lógica roda no aparelho; o servidor só guarda e sincroniza (ver sync.js).

import {
    ArcElement, BarController, BarElement, CategoryScale, Chart, DoughnutController, Filler, Legend, LinearScale,
    LineController, LineElement, PointElement, Tooltip,
} from 'chart.js';
import { playBossHitSound, playFocusEndSound, playLevelUpSound, playRestDueSound, playRestSoonSound } from './audio.js';
import { AVATARS, AVATAR_LIMITES_MIN, avatarStage, avatarSvg } from './avatars.js';
import { BOSSES, bossSvg } from './bosses.js';
import { ensureUser, getMeta, loadAll, saveLocal, saveLocalMany, setMeta, uuid } from './db.js';
import { planImport } from './importer.js';
import { BOSS_HP, cycleMark, JORNADA_PADRAO, jornadaState, normalizeJornada, objectiveProgressMs } from './jornada.js';
import {
    averagePace, manualPace, paceWeeklyMs, projectDate, reachedOn, requiredPerDay, totalMs as historyTotalMs, weekdayPace,
} from './projection.js';
import { createRoomsApi, memberTodayMs } from './rooms.js';
import { createSync } from './sync.js';
import { createWidget, widgetSupported } from './widget.js';
import {
    addDays, allTimeStats, buildDaily, compareWithPrevious, comparisonSeries, dayBounds, dayGaps, dayStats, daysUntil, findOverlaps,
    formatClock, formatDuration, heatLevel, monthsBetween, hourProfile, isSuspicious, lastStopped, lastTypeFor, localInputToMs, customTypes, CUSTOM_TYPE_MAX, typeIdFromName, NO_TYPE, STUDY_TYPES, studyTypeName, monthRange, msToLocalInput,
    quarterWeeks, rangeStats, restAlertStage, restMaxGapMs, streak, studyDayOf, studyVsOther, timelineBlocks, toMs, trendBuckets, weekdayAverages,
    weekRange, yearMonths,
} from './metrics.js';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler, Legend, LinearScale,
    LineController, LineElement, PointElement, Tooltip,
);

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
    timelineSel: new Set(), // blocos marcados na linha do tempo, para somar; limpa ao trocar de dia
    statsMode: 'week',
    statsAnchor: null,
    trendSubject: null,
    // Calculadora de projeção: simulação avulsa (não é salva; só o alvo vai para os settings).
    proj: { mode: 'quando', ritmo: 'semana', janela: 30, manualHoras: 4, folgas: [], inicioHoras: null, alvoHoras: null, dataAlvo: null },
    focusOpen: false,
    focusView: 'classico', // 'classico' ou 'sala' (sala de estudo com os colegas); lembrado neste aparelho
    roomId: null, // sala mostrada na tela "sala" (lembrada neste aparelho)
    avatarStage: null,
    sync: { state: 'idle', pending: 0 },
    lastExportAt: null,
    widgetAuto: true,
    widgetHintSeen: false,
    widgetPending: false,
    jornadaMet: null, // objetivo de hoje já cumprido (para detectar o golpe ao vivo)
    jornadaFx: null, // 'hit' ou 'ko': animação a tocar no próximo render
};

let charts = [];
let sync;

// Widget flutuante (Chrome/Edge no PC): abre pelo ⧉ do cabeçalho ou ao sair da tela cheia do modo foco.
// O Chrome aceita só uma janela especial por clique (tela cheia OU widget), por isso o ▶ abre a tela
// cheia e o widget vem ao fechar o foco (✕) ou, se a tela cheia foi fechada com Esc, no próximo clique.
const widget = createWidget({
    getState: () => state,
    onToggle: (subjectId) => toggleSubject(subjectId),
    onClose: () => { sync?.setFast(false); renderWidgetButton(); },
    onTick: () => checkRestAlert(),
    onInteract: () => dismissRestAlert(),
});

// Precisa rodar dentro de um clique: o Chrome só abre a janela flutuante com gesto do usuário.
async function openWidget() {
    const opened = await widget.open({ hint: !state.widgetHintSeen });
    if (!opened) return;
    sync?.setFast(true);
    renderWidgetButton();
    if (!state.widgetHintSeen) {
        state.widgetHintSeen = true;
        await setMeta('widget_hint_seen', true);
    }
}

const shouldAutoWidget = () => state.widgetAuto && widgetSupported() && !widget.isOpen() && Boolean(runningSession() || restInfo());

// ── Utilidades ───────────────────────────────────────────────────────────────

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const nowMs = () => Date.now();
const iso = (ms) => new Date(ms).toISOString();
const today = () => studyDayOf(nowMs(), state.settings);
const subjectById = (id) => state.subjects.find((s) => s.id === id);
const activeSubjects = () => state.subjects.filter((s) => !s.deleted_at && !s.arquivada_em).sort((a, b) => a.ordem - b.ordem);
const liveSessions = () => state.sessions.filter((s) => !s.deleted_at);
const runningSession = () => liveSessions().find((s) => !s.fim);

/** Texto legível sobre a cor da matéria: escuro em cores claras, branco nas demais. */
function inkOn(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(String(hex ?? '').trim());
    if (!m) return '#fff';
    const lin = (i) => {
        const c = parseInt(m[1].slice(i, i + 2), 16) / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    };
    const lum = 0.2126 * lin(0) + 0.7152 * lin(2) + 0.0722 * lin(4);
    return lum > 0.4 ? '#14121c' : '#fff';
}

/** Variáveis de cor de uma matéria para style="": --c (fundo) e --on-c (texto). */
const colorVars = (cor) => `--c:${esc(cor ?? '#888')};--on-c:${inkOn(cor)}`;

function formatDay(day,opts = { weekday: 'short', day: '2-digit', month: '2-digit' }) {
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
    if (state.settings?.retro_sound) {
        playFocusEndSound();
    }
}

/** Tipos padrão + personalizados já usados (e o selecionado, se for um personalizado ainda sem sessão). */
function allTypes(selected = null) {
    const custom = customTypes(state.sessions);
    if (selected?.startsWith('x:') && !custom.includes(selected)) custom.push(selected);
    return [...STUDY_TYPES.map((t) => ({ id: t.id, nome: t.nome })), ...custom.map((id) => ({ id, nome: studyTypeName(id) }))];
}

// Chips dos tipos + campo para criar um tipo novo na hora (o nome digitado vale mais que o chip marcado).
function typePicker(selected) {
    return `<fieldset class="type-picker"><legend>Tipo de estudo</legend>
        ${allTypes(selected).map((t) => `<label><input type="radio" name="tipo" value="${esc(t.id)}" ${t.id === selected ? 'checked' : ''}><span>${esc(t.nome)}</span></label>`).join('')}
        <input class="type-new" name="tipo_novo" maxlength="${CUSTOM_TYPE_MAX}" placeholder="+ Outro tipo (ex.: Flashcards)" autocomplete="off" aria-label="Novo tipo de estudo">
    </fieldset>`;
}

/** Tipo escolhido no formulário: o novo digitado tem prioridade sobre o chip/seleção. */
function typeFromForm(data) {
    const typed = typeIdFromName(data?.get('tipo_novo'), customTypes(state.sessions));
    return typed ?? (String(data?.get('tipo') ?? '') || null);
}

// Ao parar: registra o tipo de estudo (sugere o último usado na matéria) e uma anotação opcional.
function openSessionNoteDialog(session) {
    const subject = subjectById(session.subject_id);
    const others = state.sessions.filter((s) => s.id !== session.id);
    const suggested = session.tipo ?? lastTypeFor(others, session.subject_id);
    openDialog(`
        <h3>Sessão de ${esc(subject?.nome ?? 'Estudo')} · ${formatDuration(toMs(session.fim) - toMs(session.inicio))}</h3>
        ${typePicker(suggested)}
        <label>Anotações (opcional)
            <textarea name="observacao" rows="3" placeholder="Ex.: Questões do cap. 3, revisão de depreciação, arts. 1º a 20...">${esc(session.observacao ?? '')}</textarea>
        </label>
        <div class="actions">
            <button class="btn" value="save">Salvar</button>
            <button class="btn ghost" value="cancel" formnovalidate>Pular</button>
        </div>`,
    async (action, data) => {
        if (action !== 'save') return null;
        const current = state.sessions.find((s) => s.id === session.id);
        if (!current) return null;
        const obs = String(data?.get('observacao') ?? '').trim();
        await persist('sessions', { ...current, tipo: typeFromForm(data), observacao: obs || null });
        return null;
    });
}

async function toggleSubject(subjectId) {
    const at = nowMs();
    const running = runningSession();
    if (running) {
        await stopSession(running, at);
        const stopped = { ...running, fim: iso(Math.max(at, toMs(running.inicio))) };
        openSessionNoteDialog(stopped);
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
    openFocus();
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

const THEMES = [
    ['auto', 'Automático (segue o sistema)'],
    ['claro', 'Claro'],
    ['escuro', 'Escuro'],
    ['floresta', 'Floresta (escuro verde)'],
    ['lavanda', 'Lavanda (escuro roxo)'],
    ['areia', 'Areia (claro quente)'],
    ['contraste', 'Alto contraste'],
];

// O tema também fica no localStorage para o <head> aplicá-lo antes da primeira pintura (sem piscar).
function applyTheme(theme = state.settings?.tema ?? 'auto') {
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('estudos_tema', theme); } catch { /* sem armazenamento: só não pré-aplica */ }
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', cssVar('--bg') || '#4F86F7');
}

function render() {
    applyTheme();
    charts.forEach((c) => c.destroy());
    charts = [];
    const views = { timer: renderTimer, sala: renderRoomTab, jornada: renderJornada, timeline: renderTimeline, stats: renderStats, settings: renderSettings };
    root.querySelector('[data-view]').innerHTML = views[state.tab]();
    root.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('active', b.dataset.tab === state.tab));
    renderSyncBadge();
    renderCurrent();
    renderFocus();
    renderWidgetButton();
    widget.refresh();
    if (!state.focusOpen) state.jornadaFx = null; // golpe durante o foco: anima ao fechar
    if (state.tab === 'stats') mountStatsCharts();
    tick();
}

function renderWidgetButton() {
    const button = root.querySelector('[data-widget-open]');
    button.hidden = !widgetSupported();
    button.classList.toggle('active', widget.isOpen());
}

// ── Modo foco, descanso e matéria atual ─────────────────────────────────────

let wakeLock = null;

async function keepScreenOn(on) {
    try {
        if (on && !wakeLock && 'wakeLock' in navigator && document.visibilityState === 'visible') {
            wakeLock = await navigator.wakeLock.request('screen');
            wakeLock.addEventListener('release', () => { wakeLock = null; });
        } else if (!on && wakeLock) {
            await wakeLock.release();
            wakeLock = null;
        }
    } catch {
        wakeLock = null; // sem suporte ou bateria fraca: segue sem manter a tela acesa
    }
}

function openFocus() {
    state.focusOpen = true;
    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.().catch(() => {});
    keepScreenOn(true);
    render();
}

function closeFocus() {
    state.focusOpen = false;
    if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {});
    keepScreenOn(false);
    render();
}

const restInfo = () => lastStopped(state.sessions, nowMs(), restMaxGapMs(state.settings));

// ── Aviso de fim do descanso ────────────────────────────────────────────────
// Bip + tela piscando 1 min antes e ao atingir o tempo de descanso configurado. O alerta de fim
// pisca por 30 s (ou até um clique no app ou no widget) e para sozinho; 3 min depois do tempo a
// pausa deixa de contar como descanso (restMaxGapMs).
// Roda pelo tick do app e pelo relógio do widget (que não desacelera com o app minimizado).
const restAlert = { sessionId: null, stage: 'none', notified: null, dismissed: false };

function checkRestAlert() {
    const rest = runningSession() ? null : restInfo();
    const id = rest?.session.id ?? null;
    const stage = rest ? restAlertStage(rest.sinceMs, state.settings?.descanso_min) : 'none';
    if (id !== restAlert.sessionId) Object.assign(restAlert, { sessionId: id, stage: 'none', dismissed: false });
    if (stage !== restAlert.stage) {
        restAlert.stage = stage;
        const key = `${id}:${stage}`;
        if ((stage === 'soon' || stage === 'due') && restAlert.notified !== key) {
            restAlert.notified = key;
            setMeta('rest_alert', key);
            if (stage === 'due') playRestDueSound(); else playRestSoonSound();
        } else if (stage === 'due') {
            restAlert.dismissed = true; // já avisado antes de a página recarregar
        }
    }
    const classes = {
        'rest-soon': restAlert.stage === 'soon',
        'rest-due': restAlert.stage === 'due' && !restAlert.dismissed,
        'rest-over': restAlert.stage === 'due' || restAlert.stage === 'over',
    };
    for (const [name, on] of Object.entries(classes)) document.body.classList.toggle(name, on);
    widget.setClasses(classes);
}

function dismissRestAlert() {
    if (restAlert.stage !== 'due' || restAlert.dismissed) return;
    restAlert.dismissed = true;
    checkRestAlert();
}

const restLimitText = () => (state.settings?.descanso_min ? ` / ${state.settings.descanso_min} min` : '');

function nextStageText(nextInMs) {
    return nextInMs === null ? 'Estágio máximo do dia!' : `Próximo estágio em ${formatDuration(nextInMs)}`;
}

function avatarBlock(totalMs, size, withHint = true) {
    const { stage, nextInMs } = avatarStage(totalMs);
    state.avatarStage = stage;
    const isRunning = Boolean(runningSession());
    return `<div class="avatar ${isRunning ? 'is-running' : 'is-idle'}" data-avatar data-size="${size}">${avatarSvg(state.settings.avatar, stage, { size })}</div>`
        + (withHint ? `<small class="avatar-hint" data-live="avatar-next">${nextStageText(nextInMs)}</small>` : '');
}

// Atualiza os avatares visíveis; ao subir de estágio, toca a animação e o som.
function updateAvatars(totalMs) {
    const { stage, nextInMs } = avatarStage(totalMs);
    const isRunning = Boolean(runningSession());
    root.querySelectorAll('[data-live="avatar-next"]').forEach((el) => { el.textContent = nextStageText(nextInMs); });
    root.querySelectorAll('[data-avatar]').forEach((el) => {
        el.classList.toggle('is-running', isRunning);
        el.classList.toggle('is-idle', !isRunning);
    });
    if (stage === state.avatarStage) return;
    const leveledUp = state.avatarStage !== null && stage > state.avatarStage;
    state.avatarStage = stage;
    root.querySelectorAll('[data-avatar]').forEach((el) => {
        el.innerHTML = avatarSvg(state.settings.avatar, stage, { size: Number(el.dataset.size) });
        if (leveledUp) {
            el.classList.remove('level-up');
            void el.offsetWidth;
            el.classList.add('level-up');
        }
    });
    if (leveledUp && state.settings?.retro_sound) {
        playLevelUpSound();
    }
}

function todayTotals() {
    return dayStats(buildDaily(recentSessions(), state.settings, nowMs()), today());
}

// Só as sessões que podem tocar o dia de hoje (o tick roda a cada segundo).
function recentSessions() {
    const [dayStart] = dayBounds(today(), state.settings);
    return state.sessions.filter((s) => !s.fim || toMs(s.fim) > dayStart);
}

function renderCurrent() {
    const chip = root.querySelector('[data-current]');
    const running = runningSession();
    const rest = running ? null : restInfo();
    chip.hidden = !running && !rest;
    if (running) {
        const subject = subjectById(running.subject_id);
        chip.style.setProperty('--c', subject?.cor ?? '#888');
        chip.innerHTML = `<span class="dot"></span><span class="name">${esc(subject?.nome ?? 'Matéria')}</span> <b data-live="current-subject">--</b>`;
    } else if (rest) {
        chip.style.setProperty('--c', 'var(--muted)');
        chip.innerHTML = `<span class="name">Descanso</span> <b data-live="rest">--</b>`;
    }
}

function renderFocus() {
    const panel = root.querySelector('[data-focus]');
    const running = runningSession();
    const rest = running ? null : restInfo();
    if (state.focusOpen && !running && !rest) state.focusOpen = false;
    panel.hidden = !state.focusOpen;
    document.body.classList.toggle('focus-on', state.focusOpen);
    if (!state.focusOpen) return;

    const subject = subjectById((running ?? rest.session).subject_id);
    panel.style.setProperty('--c', subject?.cor ?? '#888');
    panel.style.setProperty('--on-c', inkOn(subject?.cor));
    panel.classList.toggle('sala', state.focusView === 'sala');
    const switcher = `<button type="button" class="focus-switch" data-action="focus-view"
        aria-label="${state.focusView === 'sala' ? 'Ver o cronômetro grande' : 'Ver a sala de estudo'}" title="Alternar tela">⇆</button>`;
    if (state.focusView === 'sala') {
        panel.innerHTML = renderStudyRoom(running, rest, subject) + switcher;
        return;
    }
    panel.innerHTML = switcher + (running ? `
        <button type="button" class="focus-close" data-action="focus-close" aria-label="Sair do modo foco">✕</button>
        ${avatarBlock(todayTotals().totalMs, 150, false)}
        <p class="focus-label">Focando</p>
        <p class="focus-time" data-live="session">--</p>
        <div class="focus-sub">
            <div><small><span class="dot"></span>${esc(subject?.nome ?? 'Matéria')}</small><b data-live="current-subject">--</b></div>
            <div><small>Hoje</small><b data-live="total">--</b></div>
        </div>
        <button type="button" class="focus-btn" data-action="toggle" data-id="${running.subject_id}" aria-label="Pausar">❚❚</button>`
        : `
        <button type="button" class="focus-close" data-action="focus-close" aria-label="Sair do modo foco">✕</button>
        ${avatarBlock(todayTotals().totalMs, 150, false)}
        <p class="focus-label">Descanso${esc(restLimitText())}</p>
        <p class="focus-time rest" data-live="rest">--</p>
        <div class="focus-sub">
            <div><small><span class="dot"></span>${esc(subject?.nome ?? 'Matéria')}</small><b data-live="current-subject">--</b></div>
            <div><small>Hoje</small><b data-live="total">--</b></div>
        </div>
        <button type="button" class="focus-btn" data-action="toggle" data-id="${rest.session.subject_id}" aria-label="Retomar ${esc(subject?.nome ?? '')}">▶</button>
        <p class="focus-hint">Toque em ▶ para retomar ${esc(subject?.nome ?? 'a matéria')}</p>
        <button type="button" class="btn ghost focus-other" data-action="pick-subject">Começar outra matéria</button>`);
}

// Mesa vazia (com relógio quando a pessoa já estudou hoje), como na sala de estudo do YPT.
const DESK_SVG = (studied) => `<svg class="desk" viewBox="0 0 64 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round">
    ${studied ? '<circle cx="16" cy="12" r="9"/><path d="M16 7v5l3.5 2"/>' : ''}
    <path d="M42 4h10l3 9H39z"/><path d="M47 13v15"/><path d="M4 28h56M4 34h56M8 34v12M56 34v12"/></svg>`;

// ── Salas de estudo ─────────────────────────────────────────────────────────
// A tela "sala" do modo foco mostra os membros da sala: quem está estudando agora e quanto cada um
// estudou hoje. Busca o servidor a cada 20 s enquanto a tela está aberta; sem rede, mostra o último estado.
const ROOMS_REFRESH_MS = 20_000;
const roomsApi = createRoomsApi({ baseUrl: config.roomsUrl, tokenUrl: config.tokenUrl, initialToken: config.csrf });
const rooms = { data: null, fetchedAt: 0, lastTry: 0, loading: false, error: '' };

/** Busca as salas (ou roda `action`, que devolve a mesma visão). Erro de `action` sobe para quem chamou. */
async function refreshRooms(action = null) {
    rooms.lastTry = nowMs();
    rooms.loading = true;
    try {
        rooms.data = await (action ?? roomsApi.list)();
        rooms.fetchedAt = nowMs();
        rooms.error = '';
    } catch (error) {
        if (action) throw error;
        rooms.error = navigator.onLine === false ? 'Sem conexão' : error.message;
    } finally {
        rooms.loading = false;
        if (state.focusOpen && state.focusView === 'sala') renderFocus();
        if (state.tab === 'sala') root.querySelector('[data-view]').innerHTML = renderRoomTab();
    }
}

/** Aba "Sala": a mesma visão dos colegas do modo foco, sem precisar estar estudando. */
function renderRoomTab() {
    return roomSection(todayTotals(), runningSession(), 'tab');
}

const currentRoom = () => rooms.data?.rooms.find((r) => r.id === state.roomId) ?? rooms.data?.rooms[0] ?? null;

function roomSection(stats, running, extraClass = '') {
    if (!rooms.data) {
        return `<section class="room ${extraClass}"><h4>Sala de estudo</h4><p>${rooms.error ? `⚠ ${esc(rooms.error)}` : 'Carregando…'}</p></section>`;
    }
    const room = currentRoom();
    const manager = rooms.data.pode_gerenciar; // criar e gerenciar salas é só para gestores
    if (!room) {
        return `<section class="room ${extraClass}"><h4>Sala de estudo</h4>
            <p>Você ainda não está em nenhuma sala. ${manager
                ? 'Crie uma e mande o código para os colegas, ou entre com o código de alguém.'
                : 'Peça o código da sala a um gestor e entre com ele.'}</p>
            <div class="room-actions">
                ${manager ? '<button type="button" class="btn" data-action="room-create">Criar sala</button>' : ''}
                <button type="button" class="btn ${manager ? 'ghost' : ''}" data-action="room-join">Entrar com código</button>
            </div></section>`;
    }
    const picker = rooms.data.rooms.length > 1
        ? `<div class="chips">${rooms.data.rooms.map((r) => `<button type="button" data-action="room-pick" data-id="${r.id}" class="${r.id === room.id ? 'active' : ''}">${esc(r.nome)}</button>`).join('')}</div>`
        : '';
    const studying = room.membros.filter((m) => (m.eu ? running : m.estudando_desde)).length;
    const seats = room.membros.map((m) => {
        const name = `<small>${esc(m.nome)}</small>`;
        if (m.eu) {
            const icon = running ? avatarBlock(stats.totalMs, 52, false) : DESK_SVG(stats.totalMs > 0);
            return `<div class="seat me ${running ? 'studying' : ''}">${icon}${name}<b data-live="total">${formatDuration(stats.totalMs, { seconds: true })}</b></div>`;
        }
        const today = memberTodayMs(m, rooms.fetchedAt, nowMs());
        const icon = m.estudando_desde
            ? `<div class="avatar is-running">${avatarSvg(m.avatar, avatarStage(today).stage, { size: 52 })}</div>`
            : DESK_SVG(m.hoje_ms > 0);
        return `<div class="seat ${m.estudando_desde ? 'studying' : ''} ${m.hoje_ms > 0 ? 'studied' : ''}">${icon}${name}
            <b data-live="member" data-ms="${m.hoje_ms}" data-studying="${m.estudando_desde ? 1 : ''}">${formatDuration(today, { seconds: true })}</b></div>`;
    }).join('');
    return `<section class="room ${extraClass}">
        ${picker}
        <h4>${esc(room.nome)}</h4>
        <p><span>${studying} ${studying === 1 ? 'membro' : 'membros'}</span> estudando${rooms.error ? ` · ⚠ ${esc(rooms.error)}: mostrando o último estado` : ''}</p>
        <div class="seats">${seats}</div>
        <p class="room-code">Código <b>${esc(room.codigo)}</b>
            <button type="button" data-action="room-copy">Copiar convite</button>
            <button type="button" data-action="room-join">Entrar em outra</button>
            ${manager ? `<button type="button" data-action="room-create">Nova sala</button>
            <button type="button" data-action="room-manage">Gerenciar sala</button>` : ''}
            <button type="button" data-action="room-leave" data-id="${room.id}">Sair da sala</button></p>
    </section>`;
}

/** Gestores: remover membros ou excluir a sala. */
function openRoomManageDialog() {
    const room = currentRoom();
    if (!room) return;
    const others = room.membros.filter((m) => !m.eu);
    openDialog(`
        <h3>Gerenciar "${esc(room.nome)}"</h3>
        <ul class="room-members">${others.map((m) => `<li><span>${esc(m.nome)}</span>
            <button class="btn ghost" value="remove:${m.user_id}" formnovalidate>Remover</button></li>`).join('')
            || '<li class="muted">Ninguém além de você ainda.</li>'}</ul>
        <p class="form-error"></p>
        <div class="actions">
            <button class="btn ghost" value="cancel" formnovalidate>Fechar</button>
            <button class="btn danger" value="delete" formnovalidate>Excluir sala</button>
        </div>`,
    async (action) => {
        let request;
        if (action === 'delete') {
            if (!confirm(`Excluir a sala "${room.nome}" para todos os membros?`)) return 'Exclusão cancelada.';
            request = () => roomsApi.destroy(room.id);
        } else if (action?.startsWith('remove:')) {
            const member = others.find((m) => String(m.user_id) === action.slice(7));
            if (!confirm(`Remover ${member?.nome ?? 'este membro'} da sala?`)) return 'Remoção cancelada.';
            request = () => roomsApi.remove(room.id, action.slice(7));
        } else {
            return null;
        }
        try {
            await refreshRooms(request);
        } catch (error) {
            return error.message;
        }
        return null;
    });
}

function openRoomDialog(kind) {
    const create = kind === 'create';
    openDialog(`
        <h3>${create ? 'Nova sala de estudo' : 'Entrar numa sala'}</h3>
        ${create
            ? '<label>Nome da sala<input name="nome" maxlength="60" required autofocus placeholder="Concursos e afins"></label>'
            : '<label>Código da sala<input name="codigo" maxlength="12" required autofocus autocapitalize="characters" placeholder="ABC234"></label>'}
        <p class="form-error"></p>
        <div class="actions">
            <button class="btn" value="save">${create ? 'Criar' : 'Entrar'}</button>
            <button class="btn ghost" value="cancel" formnovalidate>Cancelar</button>
        </div>
        <small class="muted">Na sala, os colegas veem só o seu nome, o tempo estudado hoje e se você está estudando agora.</small>`,
    async (action, data) => {
        const before = new Set(rooms.data?.rooms.map((r) => r.id) ?? []);
        try {
            await refreshRooms(() => (create
                ? roomsApi.create(String(data.get('nome')).trim())
                : roomsApi.join(String(data.get('codigo')).trim())));
        } catch (error) {
            return error.message;
        }
        const joined = rooms.data.rooms.find((r) => !before.has(r.id))
            ?? rooms.data.rooms.find((r) => r.codigo === String(data.get('codigo') ?? '').trim().toUpperCase());
        if (joined) {
            state.roomId = joined.id;
            await setMeta('room_id', joined.id);
        }
        return null;
    });
}

/** Tela alternativa do modo foco: cartão do tempo atual e a sala de estudo com os colegas. */
function renderStudyRoom(running, rest, subject) {
    const stats = todayTotals();
    const current = running ?? rest.session;
    const live = running ? 'session' : 'rest';
    return `
        <header class="room-head">
            <button type="button" class="room-close" data-action="focus-close" aria-label="Sair do modo foco">✕</button>
            <b><span class="dot"></span>${esc(subject?.nome ?? 'Matéria')}</b>
            <span data-live="${live}">--</span>
        </header>
        <section class="room-card">
            <div>
                <small>${running ? 'Tempo de foco atual' : `Descanso${esc(restLimitText())}`}</small>
                <p class="room-time ${running ? '' : 'rest'}"><span data-live="${live}">--</span>
                    <button type="button" class="room-btn" data-action="toggle" data-id="${current.subject_id}"
                        aria-label="${running ? 'Pausar' : `Retomar ${esc(subject?.nome ?? '')}`}">${running ? '❚❚' : '▶'}</button></p>
            </div>
            <div class="room-me">${avatarBlock(stats.totalMs, 56, false)}<b data-live="total">--</b></div>
        </section>
        ${roomSection(stats, running)}`;
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
    const hasSubjects = activeSubjects().length > 0;

    return `
        <section class="today">
            <div class="ddays">${ddayChips()}</div>
            ${avatarBlock(stats.totalMs, 96)}
            <p class="day-label">${formatDay(day, { weekday: 'long', day: '2-digit', month: 'long' })}</p>
            <p class="total" data-live="total">${formatDuration(stats.totalMs, { seconds: true })}</p>
            ${metaBar(stats)}
            ${bossCard()}
            <div class="mini-stats">
                <div><small>Máx. foco</small><b data-live="maxfocus">${formatDuration(stats.maxFocusMs)}</b></div>
                <div><small>Início</small><b>${stats.startMs ? formatClock(stats.startMs, day, state.settings) : '--:--'}</b></div>
                <div><small>Término</small><b data-live="end">${stats.endMs ? formatClock(stats.endMs, day, state.settings) : '--:--'}</b></div>
            </div>
        </section>
        ${running ? `<section class="now-running" style="${colorVars(subjectById(running.subject_id)?.cor)}">
            <span>${esc(subjectById(running.subject_id)?.nome ?? 'Matéria')}</span>
            <b data-live="session">${formatDuration(nowMs() - toMs(running.inicio), { seconds: true })}</b>
            <button type="button" data-action="focus-open" aria-label="Abrir modo foco">⛶</button>
            <button type="button" data-action="toggle" data-id="${running.subject_id}">Parar</button>
        </section>` : renderRestBanner()}
        ${hasSubjects
            ? `<button type="button" class="btn start-study" data-action="pick-subject">${running ? '⇆ Trocar matéria' : '▶ Iniciar estudo'}</button>`
            : '<ul class="subjects"><li class="empty">Crie sua primeira matéria para começar.</li></ul>'}
        <button type="button" class="btn ghost" data-action="new-subject">+ Matéria</button>`;
}

function renderRestBanner() {
    const rest = restInfo();
    if (!rest) return '';
    const subject = subjectById(rest.session.subject_id);
    return `<section class="rest-banner" style="${colorVars(subject?.cor)}">
        <span>Descanso <b data-live="rest">${formatDuration(rest.sinceMs, { seconds: true })}</b>${esc(restLimitText())}</span>
        <button type="button" data-action="toggle" data-id="${rest.session.subject_id}">▶ Retomar ${esc(subject?.nome ?? '')}</button>
    </section>`;
}

// Linha do tempo vertical (estilo YPT): uma barra por bloco de estudo, com as pausas internas mais
// escuras; blocos curtos viram um ponto. 1 px por minuto.
const VTL_PX_PER_MIN = 1;
const VTL_DOT_MS = 12 * 60_000;
const VTL_LANE_PX = 22;
const VTL_LABEL_PX = 40;

/** "1h 05m" ou "59m 17s", como no YPT. */
function vtlDuration(ms) {
    const total = Math.floor(ms / 1000);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    return h ? `${h}h ${String(m).padStart(2, '0')}m` : `${m}m ${String(total % 60).padStart(2, '0')}s`;
}

function renderVerticalTimeline(stats, day) {
    const { blocks, fromMs, toMs: endMs } = timelineBlocks(stats, state.settings);
    if (!blocks.length) return '<p class="vtl-empty muted center">Nenhum estudo neste dia.</p>';
    const y = (ms) => ((ms - fromMs) / 60_000) * VTL_PX_PER_MIN;
    const clock = (ms) => formatClock(ms, day, state.settings);
    let hours = '';
    for (let t = fromMs; t <= endMs; t += 3_600_000) {
        hours += `<div class="vtl-hour" style="top:${y(t)}px"><span>${clock(t)}</span></div>`;
    }
    const lanes = Math.max(...blocks.map((b) => b.lane)) + 1;
    const keys = new Set(blocks.map(blockKey));
    for (const key of state.timelineSel) if (!keys.has(key)) state.timelineSel.delete(key);
    let labelBottom = -Infinity;
    const items = blocks.map((block) => {
        const subject = subjectById(block.subjectId);
        const name = esc(subject?.nome ?? 'Matéria removida');
        const type = block.type === NO_TYPE ? '' : esc(studyTypeName(block.type));
        const key = blockKey(block);
        const selected = state.timelineSel.has(key);
        const dot = block.end - block.start < VTL_DOT_MS;
        const top = y(block.start);
        const gaps = block.gaps.map((g) => `<i style="top:${y(g.start) - top}px;height:${y(g.end) - y(g.start)}px"></i>`).join('');
        const classes = ['vtl-pill', dot && 'dot', block.manual && 'manual', block.running && 'running', selected && 'selected'].filter(Boolean).join(' ');
        const pick = `data-action="vtl-pick" data-key="${key}" aria-pressed="${selected}"`;
        const pill = `<button type="button" class="${classes}" ${pick} style="--c:${esc(subject?.cor ?? '#888')};--lane:${block.lane};top:${top}px;${dot ? '' : `height:${y(block.end) - top}px`}"
            title="${name}${type ? ` (${type})` : ''} · ${clock(block.start)}–${clock(block.end)} · ${formatDuration(block.studiedMs)}">${gaps}</button>`;
        if (dot) return pill;
        const labelTop = Math.max(top, labelBottom);
        labelBottom = labelTop + VTL_LABEL_PX;
        return pill + `<button type="button" class="vtl-label${selected ? ' selected' : ''}" ${pick} tabindex="-1" style="top:${labelTop}px">
            <b>${name}</b><small>${type ? `${type} · ` : ''}${vtlDuration(block.studiedMs)}</small></button>`;
    }).join('');
    return `<div class="vtl" style="--lanes:${lanes};--lane-w:${VTL_LANE_PX}px;height:${Math.max(y(endMs), labelBottom) + 12}px">${hours}${items}</div>
        ${renderTimelineSum(blocks)}`;
}

const blockKey = (block) => `${block.subjectId}|${block.type}|${block.start}`;

/** Somatório dos blocos marcados na linha do tempo (some quando nada está marcado). */
function renderTimelineSum(blocks) {
    const picked = blocks.filter((b) => state.timelineSel.has(blockKey(b)));
    if (!picked.length) return '<p class="vtl-hint muted center">Toque nos blocos para somar o tempo.</p>';
    const byType = {};
    for (const b of picked) byType[b.type] = (byType[b.type] ?? 0) + b.studiedMs;
    const parts = Object.keys(byType).length > 1
        ? `<small>${Object.entries(byType).sort((a, b) => b[1] - a[1])
            .map(([t, ms]) => `${esc(studyTypeName(t))} ${formatDuration(ms)}`).join(' · ')}</small>`
        : '';
    const total = picked.reduce((sum, b) => sum + b.studiedMs, 0);
    return `<div class="vtl-sum">
        <span>${picked.length} ${picked.length === 1 ? 'selecionado' : 'selecionados'} · <b>${vtlDuration(total)}</b>${parts}</span>
        <button type="button" class="btn ghost" data-action="vtl-clear">Limpar</button>
    </div>`;
}

function renderTimeline() {
    const day = state.timelineDay ?? today();
    const stats = dayStats(daily(), day);

    const [dayStart, dayEnd] = dayBounds(day, state.settings);
    const sessions = liveSessions()
        .filter((s) => toMs(s.inicio) < dayEnd && (s.fim ? toMs(s.fim) : nowMs()) > dayStart)
        .sort((a, b) => toMs(a.inicio) - toMs(b.inicio));

    const gapRows = dayGaps(stats).map((g) => ({
        at: g.start,
        html: `<li class="gap">
            <span class="range">${formatClock(g.start, day, state.settings)} – ${formatClock(g.end, day, state.settings)}</span>
            <span class="dur">${formatDuration(g.end - g.start)}</span>
            <button type="button" data-action="gap-session" data-start="${g.start}" data-end="${g.end}" aria-label="Registrar sessão neste intervalo">+</button>
        </li>`,
    }));

    const sessionRows = sessions.map((s) => {
        const subject = subjectById(s.subject_id);
        const end = s.fim ? toMs(s.fim) : nowMs();
        const overlap = findOverlaps(s, liveSessions(), nowMs()).length > 0;
        const html = `<li style="--c:${esc(subject?.cor ?? '#888')}">
            <span class="dot"></span>
            <div class="session-main">
                <span class="name">${esc(subject?.nome ?? 'Matéria removida')}
                    ${s.tipo ? `<em class="tipo">${esc(studyTypeName(s.tipo))}</em>` : ''}
                    ${s.origem === 'manual' ? '<em>manual</em>' : ''}
                    ${s.origem === 'importado' ? '<em>importado</em>' : ''}
                    ${s.status === 'suspeita' ? '<em class="warn">a confirmar</em>' : ''}
                    ${overlap ? '<em class="warn">sobreposta</em>' : ''}</span>
                ${s.observacao ? `<p class="session-obs">📝 ${esc(s.observacao)}</p>` : ''}
            </div>
            <span class="range">${formatClock(toMs(s.inicio), day, state.settings)} – ${s.fim ? formatClock(end, day, state.settings) : 'agora'}</span>
            <span class="dur">${formatDuration(end - toMs(s.inicio))}</span>
            ${s.fim ? `<button type="button" data-action="edit-session" data-id="${s.id}" aria-label="Editar">✎</button>` : ''}
        </li>`;
        return { at: toMs(s.inicio) + 0.5, html };
    });
    const list = [...sessionRows, ...gapRows].sort((a, b) => a.at - b.at).map((row) => row.html).join('');

    return `
        <nav class="day-nav">
            <button type="button" data-action="day-prev" aria-label="Dia anterior">‹</button>
            <b>${formatDay(day, { weekday: 'long', day: '2-digit', month: '2-digit' })}</b>
            <button type="button" data-action="day-next" aria-label="Próximo dia" ${day >= today() ? 'disabled' : ''}>›</button>
        </nav>
        <p class="center muted">Total ${formatDuration(stats.totalMs)} · Máx. foco ${formatDuration(stats.maxFocusMs)}</p>
        ${renderVerticalTimeline(stats, day)}
        <h3>Sessões</h3>
        <ul class="session-list">${list || '<li class="empty">Nenhuma sessão neste dia.</li>'}</ul>
        <button type="button" class="btn ghost" data-action="new-session" data-day="${day}">+ Sessão manual</button>`;
}

const STATS_MODES = { day: 'Dia', week: 'Semana', month: 'Mês', trend: 'Tendência', total: 'Total', proj: 'Projeção' };
const WEEKDAYS = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

function statsRange() {
    const anchor = state.statsAnchor ?? today();
    if (state.statsMode === 'day') return [anchor, anchor];
    if (state.statsMode === 'week') return weekRange(anchor);
    return monthRange(anchor);
}

/** "3:29" (horas:minutos), como no calendário do YPT. */
function formatHM(ms) {
    const minutes = Math.floor(ms / 60_000);
    return `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`;
}

function formatSigned(ms) {
    return `${ms < 0 ? '−' : '+'}${formatDuration(Math.abs(ms), { seconds: true })}`;
}

function cards(items) {
    return `<div class="cards">${items.map(([label, value]) => `<div class="card"><small>${label}</small><b>${value}</b></div>`).join('')}</div>`;
}

function segmentedModes() {
    return `<div class="segmented six">
        ${Object.entries(STATS_MODES).map(([m, label]) => `<button type="button" data-action="stats-mode" data-mode="${m}" class="${m === state.statsMode ? 'active' : ''}">${label}</button>`).join('')}
    </div>`;
}

function renderStats() {
    if (state.statsMode === 'trend') return segmentedModes() + renderTrend();
    if (state.statsMode === 'total') return segmentedModes() + renderAllTime();
    if (state.statsMode === 'proj') return segmentedModes() + renderProjection();

    const d = daily();
    const t = today();
    const [from, to] = statsRange();
    const range = rangeStats(d, from, to, t);
    const mode = state.statsMode;
    const isCurrent = from <= t && t <= to;
    const single = mode === 'day' ? dayStats(d, from) : null;
    const title = mode === 'day'
        ? formatDay(from, { weekday: 'long', day: '2-digit', month: '2-digit' })
        : mode === 'week' ? `${formatDay(from, { day: '2-digit', month: '2-digit' })} – ${formatDay(to, { day: '2-digit', month: '2-digit' })}`
            : formatDay(from, { month: 'long', year: 'numeric' });

    const summary = mode === 'day'
        ? cards([
            ['Total', formatDuration(range.totalMs)],
            ['Máx. foco', formatDuration(single.maxFocusMs)],
            ['Início / término', single.startMs ? `${formatClock(single.startMs, from, state.settings)} – ${formatClock(single.endMs, from, state.settings)}` : '--'],
            ['Sequência', `${streak(d, t)} dias`],
        ])
        : cards([
            ['Total', formatDuration(range.totalMs)],
            ['Média diária', formatDuration(range.avgPerDayMs)],
            ['Dias estudados', `${range.daysStudied} / ${range.days.length}`],
            ['Máx. foco', formatDuration(range.maxFocusMs)],
        ]);

    return `
        ${segmentedModes()}
        ${mode === 'week' ? renderQuarterGrid(d, from) : ''}
        ${mode === 'month' ? renderYearGrid(d, from) : ''}
        <nav class="day-nav">
            <button type="button" data-action="stats-prev" aria-label="Anterior">‹</button>
            <b>${title}</b>
            <button type="button" data-action="stats-next" aria-label="Próximo" ${to >= t ? 'disabled' : ''}>›</button>
        </nav>
        ${summary}
        ${isCurrent ? renderComparison(d, mode, t) : ''}
        ${renderStudyVsOther(d, from, to)}
        ${mode !== 'day' ? '<div class="chart-box"><canvas data-chart="bars"></canvas></div>' : ''}
        ${mode === 'month' ? renderMonthCalendar(range, from) : ''}
        ${mode === 'month' ? renderWeekdayAndHours(d, from, to, t) : ''}
        ${renderSubjectShare(range.bySubject, range.totalMs, range.bySubjectType)}
        ${renderTypeShare(range.byType, range.totalMs)}`;
}

function renderAllTime() {
    const d = daily();
    const t = today();
    const all = allTimeStats(d, t);
    if (!all) return '<p class="center muted">Nenhum estudo registrado ainda.</p>';
    const dm = { day: '2-digit', month: '2-digit' };
    const dmy = { day: '2-digit', month: '2-digit', year: 'numeric' };
    const goto = (mode, day, label, value) => `<button type="button" class="card link" data-action="stats-open" data-mode="${mode}" data-day="${day}">
        <small>${label}</small><b>${value}</b></button>`;
    return `
        <p class="center muted">Desde ${formatDay(all.firstDay, dmy)} · ${all.elapsedDays} ${all.elapsedDays === 1 ? 'dia' : 'dias'}</p>
        ${cards([
            ['Total estudado', formatDuration(all.totalMs)],
            ['Dias estudados', `${all.daysStudied} (${Math.round((all.daysStudied / all.elapsedDays) * 100)}%)`],
            ['Média diária', formatDuration(all.avgPerDayMs)],
            ['Média por dia estudado', formatDuration(all.avgPerStudiedDayMs)],
            ['Sequência atual', `${streak(d, t)} dias`],
            ['Maior sequência', `${all.longestStreak} dias`],
            ['Sessões', String(all.sessions)],
            ['Máx. foco', formatDuration(all.maxFocusMs)],
        ])}
        <h3>Recordes</h3>
        <div class="cards three">
            ${goto('day', all.bestDay.day, `Melhor dia · ${formatDay(all.bestDay.day, dm)}`, formatDuration(all.bestDay.totalMs))}
            ${goto('week', all.bestWeek.from, `Melhor semana · ${formatDay(all.bestWeek.from, dm)}`, formatDuration(all.bestWeek.totalMs))}
            ${goto('month', all.bestMonth.from, `Melhor mês · ${formatDay(all.bestMonth.from, { month: 'short', year: '2-digit' }).replace('.', '')}`, formatDuration(all.bestMonth.totalMs))}
        </div>
        <h3>Por mês</h3>
        <div class="chart-box"><canvas data-chart="total-months" data-from="${all.firstDay}"></canvas></div>
        ${renderStudyVsOther(d, all.firstDay, t)}
        <h3>Média por dia da semana</h3>
        <div class="chart-box small"><canvas data-chart="weekday" data-from="${all.firstDay}" data-to="${t}"></canvas></div>
        <h3>Estudo por hora do dia (média, só cronômetro)</h3>
        <div class="chart-box small"><canvas data-chart="hours" data-from="${all.firstDay}" data-to="${t}"></canvas></div>
        ${renderSubjectShare(all.bySubject, all.totalMs, all.bySubjectType)}
        ${renderTypeShare(all.byType, all.totalMs)}`;
}

function renderComparison(d, mode, t) {
    const { diffMs } = compareWithPrevious(d, mode, t, state.settings, nowMs());
    const weekdayName = formatDay(t, { weekday: 'long' }).toLowerCase();
    const label = mode === 'day' ? 'Vs ontem, mesmo horário'
        : mode === 'week' ? `Até ${weekdayName}, vs semana passada`
            : `Até o dia ${Number(t.slice(8))}, vs mês passado`;
    const previousLabel = { day: 'Ontem', week: 'Semana passada', month: 'Mês passado' }[mode];
    return `<div class="compare">
        <div><small>${label}</small><b class="${diffMs < 0 ? 'neg' : 'pos'}">${formatSigned(diffMs)}</b>
            <small class="legend"><i class="now"></i>Agora <i class="prev"></i>${previousLabel}</small></div>
        <div class="chart-box mini"><canvas data-chart="compare"></canvas></div>
    </div>`;
}

function renderStudyVsOther(d, from, to) {
    const { studyMs, otherMs, ratio } = studyVsOther(d, from, to);
    if (!studyMs && !otherMs) return '';
    return `<div class="ratio">
        <small>Estudo × outros, entre o início e o término de cada dia (só cronômetro)</small>
        <div class="ratio-bar"><span style="width:${(ratio * 100).toFixed(1)}%"></span></div>
        <small><b>Estudo ${formatHM(studyMs)} · ${Math.round(ratio * 100)}%</b> &nbsp; Outros ${formatHM(otherMs)} · ${100 - Math.round(ratio * 100)}%</small>
    </div>`;
}

function renderQuarterGrid(d, from) {
    const weeks = quarterWeeks(d, from);
    const t = today();
    return `<div class="period-grid">${weeks.map((w) => `<button type="button" data-action="stats-goto" data-day="${w.from}"
        class="lv${heatLevel(w.totalMs / 7)} ${w.from === from ? 'selected' : ''}" ${w.from > t ? 'disabled' : ''}>
        <small>${formatDay(w.from, { day: '2-digit', month: '2-digit' })}</small><b>${w.totalMs ? formatHM(w.totalMs) : '—'}</b></button>`).join('')}</div>`;
}

function renderYearGrid(d, from) {
    const months = yearMonths(d, from);
    const t = today();
    return `<div class="period-grid months">${months.map((m) => `<button type="button" data-action="stats-goto" data-day="${m.from}"
        class="lv${heatLevel(m.totalMs / 30)} ${m.from === from ? 'selected' : ''}" ${m.from > t ? 'disabled' : ''}>
        <small>${formatDay(m.from, { month: 'short' }).replace('.', '')}</small><b>${m.totalMs ? formatHM(m.totalMs) : '—'}</b></button>`).join('')}</div>`;
}

function renderMonthCalendar(range, from) {
    const offset = (new Date(from + 'T00:00:00Z').getUTCDay() + 6) % 7;
    const blanks = '<i class="blank"></i>'.repeat(offset);
    const t = today();
    const cells = range.days.map((s) => `<i class="lv${heatLevel(s.totalMs)} ${s.day === t ? 'today' : ''}">
        <b>${Number(s.day.slice(8))}</b><small>${s.totalMs ? formatHM(s.totalMs) : ''}</small></i>`).join('');
    return `<div class="calendar">${WEEKDAYS.map((w) => `<span>${w}</span>`).join('')}${blanks}${cells}</div>
        <p class="heat-legend"><i class="lv1">0+</i><i class="lv2">4+</i><i class="lv3">7+</i><i class="lv4">10+</i><i class="lv5">12+</i></p>`;
}

function renderWeekdayAndHours(d, from, to, t) {
    return `<h3>Média por dia da semana</h3>
        <div class="chart-box small"><canvas data-chart="weekday" data-from="${from}" data-to="${to}"></canvas></div>
        <h3>Estudo por hora do dia (média, só cronômetro)</h3>
        <div class="chart-box small"><canvas data-chart="hours" data-from="${from}" data-to="${to}"></canvas></div>`;
}

function typeSplit(types = {}) {
    const typed = Object.entries(types).filter(([id]) => id !== NO_TYPE).sort((a, b) => b[1] - a[1]);
    if (!typed.length) return '';
    return `<small class="type-split">${typed.map(([id, ms]) => `${esc(studyTypeName(id))} ${formatDuration(ms)}`).join(' · ')}</small>`;
}

function renderSubjectShare(bySubject, totalMs, bySubjectType = {}) {
    const subjects = Object.entries(bySubject).sort((a, b) => b[1] - a[1]);
    const rows = subjects.map(([id, ms]) => {
        const s = subjectById(id);
        const pct = totalMs ? Math.round((ms / totalMs) * 100) : 0;
        return `<li style="--c:${esc(s?.cor ?? '#888')}"><span class="dot"></span>
            <div class="share-main"><span class="name">${esc(s?.nome ?? 'Removida')}</span>${typeSplit(bySubjectType[id])}</div>
            <span>${formatDuration(ms)}</span><span class="muted">${pct}%</span></li>`;
    }).join('');
    return `<h3>Por matéria</h3>
        ${subjects.length ? '<div class="chart-box small"><canvas data-chart="donut"></canvas></div>' : ''}
        <ul class="subject-share">${rows || '<li class="empty">Sem estudo no período.</li>'}</ul>`;
}

function renderTypeShare(byType, totalMs) {
    const entries = Object.entries(byType).sort((a, b) => b[1] - a[1]);
    if (!entries.length) return '';
    const rows = entries.map(([id, ms]) => {
        const pct = totalMs ? Math.round((ms / totalMs) * 100) : 0;
        return `<li class="${id === NO_TYPE ? 'untyped' : ''}"><span class="name">${esc(studyTypeName(id === NO_TYPE ? null : id))}</span>
            <span>${formatDuration(ms)}</span><span class="muted">${pct}%</span></li>`;
    }).join('');
    return `<h3>Por tipo de estudo</h3><ul class="subject-share type-share">${rows}</ul>`;
}

function trendSessions() {
    return state.trendSubject ? state.sessions.filter((s) => s.subject_id === state.trendSubject) : state.sessions;
}

function renderTrend() {
    const d = buildDaily(trendSessions(), state.settings, nowMs());
    const t = today();
    const withSessions = new Set(liveSessions().map((s) => s.subject_id));
    const chips = state.subjects
        .filter((s) => !s.deleted_at && withSessions.has(s.id))
        .sort((a, b) => a.ordem - b.ordem)
        .map((s) => `<button type="button" data-action="trend-subject" data-id="${s.id}" style="--c:${esc(s.cor)}"
            class="${state.trendSubject === s.id ? 'active' : ''}">${esc(s.nome)}</button>`).join('');
    const maxOf = (unit, count) => formatDuration(Math.max(0, ...trendBuckets(d, unit, count, t).map((b) => b.totalMs)));
    const weeksFrom = addDays(weekRange(t)[0], -11 * 7);
    return `
        <div class="chips"><button type="button" data-action="trend-subject" data-id="" class="${state.trendSubject ? '' : 'active'}">Todos</button>${chips}</div>
        <h3>Por dia · máximo ${maxOf('day', 14)}</h3>
        <div class="chart-box"><canvas data-chart="trend-day"></canvas></div>
        <h3>Por semana · máximo ${maxOf('week', 12)}</h3>
        <div class="chart-box"><canvas data-chart="trend-week"></canvas></div>
        <h3>Por mês · máximo ${maxOf('month', 12)}</h3>
        <div class="chart-box"><canvas data-chart="trend-month"></canvas></div>
        <h3>Média por dia da semana (12 semanas)</h3>
        <div class="chart-box small"><canvas data-chart="weekday" data-from="${weeksFrom}" data-to="${t}"></canvas></div>
        <h3>Estudo por hora do dia (28 dias, só cronômetro)</h3>
        <div class="chart-box small"><canvas data-chart="hours" data-from="${addDays(t, -27)}" data-to="${t}"></canvas></div>
        <h3>Últimas 15 semanas</h3>
        ${renderHeatmap(d, t)}
        <p class="center muted">Sequência atual: <b>${streak(d, t)} dias</b></p>`;
}

// ── Calculadora de projeção ──────────────────────────────────────────────────

const HOUR_MS = 3_600_000;
const PROJ_RITMOS = { semana: 'Por dia da semana', media: 'Média simples', manual: 'Manual' };

function formatBigHours(ms) {
    return `${Math.floor(ms / HOUR_MS).toLocaleString('pt-BR')}h`;
}

function longDay(day) {
    return formatDay(day, { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function projInputs() {
    const p = state.proj;
    const d = daily();
    const historyMs = historyTotalMs(d);
    const startMs = p.inicioHoras === null ? historyMs : p.inicioHoras * HOUR_MS;
    const alvo = p.alvoHoras ?? state.settings.alvo_horas ?? null;
    return { d, historyMs, startMs, targetMs: alvo ? alvo * HOUR_MS : null, alvo };
}

function folgaPicker() {
    return `<div class="chips folgas">${WEEKDAYS.map((w, i) => `<button type="button" data-action="proj-folga" data-day="${i}"
        class="${state.proj.folgas.includes(i) ? 'active' : ''}" aria-pressed="${state.proj.folgas.includes(i)}">${w}</button>`).join('')}</div>
        <small class="muted">Toque nos dias de folga.</small>`;
}

function renderProjection() {
    const p = state.proj;
    const t = today();
    const { d, historyMs, startMs, targetMs, alvo } = projInputs();
    const saved = state.settings.alvo_horas ?? null;
    const pct = saved ? Math.min(100, (historyMs / (saved * HOUR_MS)) * 100) : 0;
    const progress = saved ? `<div class="goal proj-goal"><div class="goal-bar"><span style="width:${pct}%"></span></div>
        <small>${formatBigHours(historyMs)} / ${saved.toLocaleString('pt-BR')}h · ${Math.floor(pct)}%</small></div>` : '';

    const form = `<form class="form proj-form" data-proj>
        <div class="proj-row">
            <label>Ponto de partida (h)
                <input type="number" name="inicioHoras" min="0" step="0.5" value="${p.inicioHoras ?? Math.floor(historyMs / HOUR_MS)}">
                <small>${p.inicioHoras === null ? 'Seu total histórico' : '<button type="button" class="link" data-action="proj-reset-start">usar meu total</button>'}</small></label>
            <label>Alvo (h)
                <input type="number" name="alvoHoras" min="1" max="100000" step="1" value="${alvo ?? ''}" placeholder="Ex.: 2000">
                <small>${alvo && alvo !== saved ? '<button type="button" class="link" data-action="proj-save-target">salvar como meu alvo</button>' : saved ? 'Seu alvo salvo' : '&nbsp;'}</small></label>
        </div>
    </form>`;

    const modes = `<div class="segmented two">${[['quando', 'Quando chego?'], ['quanto', 'Quanto preciso?']].map(([m, label]) =>
        `<button type="button" data-action="proj-mode" data-mode="${m}" class="${p.mode === m ? 'active' : ''}">${label}</button>`).join('')}</div>`;

    if (!targetMs) {
        return `${progress}${modes}${form}<p class="center muted">Informe um alvo em horas para simular.</p>`;
    }
    if (targetMs <= startMs) {
        const day = p.inicioHoras === null ? reachedOn(d, targetMs) : null;
        return `${progress}${modes}${form}<div class="proj-result main"><small>Alvo de ${alvo.toLocaleString('pt-BR')}h</small>
            <b>${day ? `Atingido em ${longDay(day)}` : 'Já atingido'}</b></div>`;
    }
    const body = p.mode === 'quando' ? renderProjWhen(d, t, startMs, targetMs) : renderProjHowMuch(d, t, startMs, targetMs);
    return `${progress}${modes}${form}<p class="center muted">Faltam <b>${formatDuration(targetMs - startMs)}</b> para ${alvo.toLocaleString('pt-BR')}h</p>${body}`;
}

function projPaces(d, t) {
    const p = state.proj;
    return {
        semana: weekdayPace(d, t, p.janela),
        media: averagePace(d, t, p.janela),
        manual: manualPace(p.manualHoras, p.folgas),
    };
}

function renderProjWhen(d, t, startMs, targetMs) {
    const p = state.proj;
    const todayDoneMs = dayStats(d, t).totalMs;
    const paces = projPaces(d, t);
    const rows = Object.entries(PROJ_RITMOS).map(([id, label]) => {
        const r = projectDate({ startMs, targetMs, pace: paces[id], today: t, todayDoneMs });
        const perDay = paceWeeklyMs(paces[id]) / 7;
        const when = r.status === 'never' ? 'Sem previsão com esse ritmo'
            : `${longDay(r.day)} <small>(${r.days === 0 ? 'hoje' : `em ${r.days.toLocaleString('pt-BR')} dias`})</small>`;
        const detail = id === 'manual'
            ? `${formatDuration(p.manualHoras * HOUR_MS)}/dia${p.folgas.length ? `, folga ${p.folgas.map((i) => WEEKDAYS[i]).join('/')}` : ''}`
            : `${formatDuration(perDay)}/dia em média · últimos ${p.janela} dias`;
        return `<button type="button" data-action="proj-ritmo" data-id="${id}" class="proj-result ${p.ritmo === id ? 'main' : ''}">
            <small>${label}</small><b>${when}</b><small>${detail}</small></button>`;
    }).join('');

    const controls = p.ritmo === 'manual'
        ? `<form class="form" data-proj><label>Horas por dia
            <input type="number" name="manualHoras" min="0" max="24" step="0.25" value="${p.manualHoras}"></label>${folgaPicker()}
            ${p.folgas.length === 7 ? '<p class="form-error">Todos os dias são folga: escolha ao menos um dia de estudo.</p>' : ''}</form>`
        : `<form class="form" data-proj><label>Janela do ritmo
            <select name="janela">${[7, 30, 90].map((n) => `<option value="${n}" ${p.janela === n ? 'selected' : ''}>Últimos ${n} dias</option>`).join('')}</select></label></form>`;
    return `<div class="proj-results">${rows}</div>${controls}`;
}

function renderProjHowMuch(d, t, startMs, targetMs) {
    const p = state.proj;
    const ddays = state.ddays.filter((x) => !x.deleted_at && x.data >= t).sort((a, b) => a.data.localeCompare(b.data));
    const chips = ddays.map((x) => `<button type="button" data-action="proj-dday" data-day="${esc(x.data)}"
        class="${p.dataAlvo === x.data ? 'active' : ''}">${esc(x.titulo)} · ${formatDay(x.data, { day: '2-digit', month: '2-digit' })}</button>`).join('');
    const form = `<form class="form" data-proj><label>Até quando?
        <input type="date" name="dataAlvo" min="${t}" value="${p.dataAlvo ?? ''}"></label>
        ${chips ? `<div class="chips">${chips}</div>` : ''}${folgaPicker()}</form>`;
    if (!p.dataAlvo) return `${form}<p class="center muted">Escolha uma data ou um D-Day.</p>`;

    const r = requiredPerDay({ startMs, targetMs, today: t, targetDay: p.dataAlvo, offWeekdays: p.folgas });
    if (r.status === 'invalid') return `${form}<p class="form-error center">Não há dias de estudo até essa data.</p>`;
    const currentPerDay = paceWeeklyMs(weekdayPace(d, t, p.janela)) / 7;
    const diff = r.perDayMs - currentPerDay;
    return `<div class="proj-result main"><small>Para chegar até ${longDay(p.dataAlvo)}</small>
        <b>${formatDuration(r.perDayMs)} por dia</b>
        <small>em ${r.studyDays.toLocaleString('pt-BR')} dias de estudo · seu ritmo (${p.janela} dias): ${formatDuration(currentPerDay)}/dia
        · <span class="${diff > 0 ? 'neg' : 'pos'}">${diff > 0 ? `+${formatDuration(diff)} acima` : 'você já está no ritmo'}</span></small></div>${form}`;
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

// ── Gráficos ─────────────────────────────────────────────────────────────────

const hoursOf = (ms) => +(ms / 3_600_000).toFixed(2);
const chartTooltip = { callbacks: { label: (ctx) => `${ctx.dataset.label ?? ctx.label}: ${formatDuration(ctx.raw * 3_600_000)}` } };
const colorOf = (id) => subjectById(id)?.cor ?? '#888';
const nameOf = (id) => subjectById(id)?.nome ?? 'Removida';

function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function stackedBars(canvas, labels, buckets) {
    const subjectIds = [...new Set(buckets.flatMap((b) => Object.keys(b.bySubject)))];
    charts.push(new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: subjectIds.map((id) => ({
                label: nameOf(id),
                data: buckets.map((b) => hoursOf(b.bySubject[id] ?? 0)),
                backgroundColor: colorOf(id),
                borderRadius: 3,
            })),
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: chartTooltip },
            scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, ticks: { callback: (v) => `${v}h` } } },
        },
    }));
}

function simpleBars(canvas, labels, values, tooltipLabel) {
    charts.push(new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets: [{ label: tooltipLabel, data: values, backgroundColor: cssVar('--primary'), borderRadius: 3 }] },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true } },
        },
    }));
}

function mountDonut(canvas, bySubject) {
    const ids = Object.keys(bySubject);
    charts.push(new Chart(canvas, {
        type: 'doughnut',
        data: { labels: ids.map(nameOf), datasets: [{ data: ids.map((id) => hoursOf(bySubject[id])), backgroundColor: ids.map(colorOf), borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false }, tooltip: chartTooltip } },
    }));
}

function mountStatsCharts() {
    const t = today();
    const mode = state.statsMode;
    const d = mode === 'trend' ? buildDaily(trendSessions(), state.settings, nowMs()) : daily();

    if (mode === 'total') {
        const all = allTimeStats(d, t);
        const months = root.querySelector('[data-chart="total-months"]');
        if (all && months) {
            const buckets = trendBuckets(d, 'month', monthsBetween(all.firstDay, t), t);
            stackedBars(months, buckets.map((b) => formatDay(b.from, { month: 'short', year: '2-digit' }).replace('.', '')), buckets);
        }
        const donut = root.querySelector('[data-chart="donut"]');
        if (all && donut) mountDonut(donut, all.bySubject);
    } else if (mode !== 'trend') {
        const [from, to] = statsRange();
        const range = rangeStats(d, from, to, t);
        const bars = root.querySelector('[data-chart="bars"]');
        if (bars) {
            const labels = range.days.map((s) => (mode === 'week' ? formatDay(s.day, { weekday: 'short' }) : Number(s.day.slice(8))));
            stackedBars(bars, labels, range.days.map((s) => ({ bySubject: s.bySubject })));
        }
        const donut = root.querySelector('[data-chart="donut"]');
        if (donut) mountDonut(donut, range.bySubject);
        const compare = root.querySelector('[data-chart="compare"]');
        if (compare) {
            const series = comparisonSeries(d, mode, t, state.settings, nowMs());
            const labels = mode === 'day' ? series.labels : series.labels.map((l) => (l ? Number(l.slice(8)) : ''));
            charts.push(new Chart(compare, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Agora', data: series.current.map(hoursOf), borderColor: cssVar('--primary'), borderWidth: 2, pointRadius: 0, stepped: true },
                        { label: 'Anterior', data: series.previous.map(hoursOf), borderColor: 'transparent', backgroundColor: cssVar('--compare-prev'), fill: 'origin', pointRadius: 0, stepped: true },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: { legend: { display: false }, tooltip: chartTooltip },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 5 } }, y: { display: false, beginAtZero: true } },
                },
            }));
        }
    } else {
        const dayBuckets = trendBuckets(d, 'day', 14, t);
        stackedBars(root.querySelector('[data-chart="trend-day"]'), dayBuckets.map((b) => formatDay(b.from, { day: '2-digit', month: '2-digit' })), dayBuckets);
        const weekBuckets = trendBuckets(d, 'week', 12, t);
        stackedBars(root.querySelector('[data-chart="trend-week"]'), weekBuckets.map((b) => formatDay(b.from, { day: '2-digit', month: '2-digit' })), weekBuckets);
        const monthBuckets = trendBuckets(d, 'month', 12, t);
        stackedBars(root.querySelector('[data-chart="trend-month"]'), monthBuckets.map((b) => formatDay(b.from, { month: 'short' }).replace('.', '')), monthBuckets);
    }

    const weekday = root.querySelector('[data-chart="weekday"]');
    if (weekday) {
        const averages = weekdayAverages(d, weekday.dataset.from, weekday.dataset.to, t);
        charts.push(new Chart(weekday, {
            type: 'bar',
            data: { labels: WEEKDAYS, datasets: [{ label: 'Média', data: averages.map(hoursOf), backgroundColor: cssVar('--primary'), borderRadius: 3 }] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: chartTooltip },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: (v) => `${v}h` } } },
            },
        }));
    }
    const hoursChart = root.querySelector('[data-chart="hours"]');
    if (hoursChart) {
        const profile = hourProfile(d, hoursChart.dataset.from, hoursChart.dataset.to, t, state.settings);
        const order = Array.from({ length: 24 }, (_, i) => (state.settings.virada_hora + i) % 24);
        simpleBars(hoursChart, order.map((h) => String(h).padStart(2, '0')), order.map((h) => +profile[h].toFixed(1)), 'Minutos por dia');
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

        <h3>Avatar</h3>
        <div class="avatar-picker">${Object.entries(AVATARS).map(([id, a]) => `<button type="button" data-action="pick-avatar" data-id="${id}"
            class="${(s.avatar ?? 'bichinho') === id ? 'selected' : ''}" aria-pressed="${(s.avatar ?? 'bichinho') === id}">
            ${avatarSvg(id, AVATAR_LIMITES_MIN.length - 1, { size: 64 })}<span>${esc(a.nome)}</span></button>`).join('')}</div>
        <p class="muted small">Evolui com o total estudado no dia: 0 · 30 min · 1h · 2h · 3h · 4h · 5h. Recomeça na virada do dia.</p>

        <h3>Preferências</h3>
        <form class="form" data-form="settings">
            <label>Virada do dia (hora)
                <input type="number" name="virada_hora" min="0" max="12" value="${s.virada_hora}" required>
                <small>Estudo antes dessa hora conta para o dia anterior.</small></label>
            <label>Meta diária (minutos)
                <input type="number" name="meta_diaria_min" min="0" max="1440" step="5" value="${s.meta_diaria_min ?? ''}" placeholder="Sem meta"></label>
            <label>Tempo de descanso (minutos)
                <input type="number" name="descanso_min" min="1" max="240" value="${s.descanso_min ?? ''}" placeholder="Sem aviso">
                <small>Bip e tela piscando 1 min antes e por 30 s quando o descanso acabar; 3 min depois, a pausa deixa de contar como descanso.</small></label>
            <label>Confirmar sessão aberta após (minutos)
                <input type="number" name="limite_suspeita_min" min="30" max="1440" step="10" value="${s.limite_suspeita_min}" required></label>
            <label>Fuso horário
                <input name="timezone" value="${esc(s.timezone)}" required list="estudos-timezones">
                <datalist id="estudos-timezones">${['America/Sao_Paulo', 'America/Cuiaba', 'America/Manaus', 'America/Belem', 'America/Fortaleza', 'America/Recife', 'America/Rio_Branco', 'America/Noronha']
                    .map((tz) => `<option value="${tz}">`).join('')}</datalist>
                <small>Este aparelho: ${esc(Intl.DateTimeFormat().resolvedOptions().timeZone)}</small></label>
            <label>Tema
                <select name="tema" data-theme-picker>${THEMES.map(([value, label]) =>
                    `<option value="${value}" ${(s.tema ?? 'auto') === value ? 'selected' : ''}>${label}</option>`).join('')}</select>
                <small>Muda as cores na hora; salve para manter (vale em todos os aparelhos).</small></label>
            <label class="checkbox">
                <input type="checkbox" name="retro_sound" ${s.retro_sound ? 'checked' : ''}>
                Sons retrô 8-bit (ao subir de nível e pausar foco)
            </label>
            ${widgetSupported() ? `<label class="checkbox">
                <input type="checkbox" name="widget_auto" ${state.widgetAuto ? 'checked' : ''}>
                Abrir o widget flutuante ao sair da tela cheia do modo foco (neste computador)
            </label>` : ''}
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

        <h3>Backup</h3>
        <p class="muted">Baixa um arquivo JSON com todas as matérias, sessões, D-Days e preferências deste aparelho.
            ${state.lastExportAt ? `Último backup: ${new Date(state.lastExportAt).toLocaleString('pt-BR')}.` : 'Nenhum backup feito neste aparelho.'}</p>
        <button type="button" class="btn ghost" data-action="export-backup">⤓ Exportar backup</button>
        <label class="btn ghost file-btn">⤒ Importar arquivo (backup ou histórico)
            <input type="file" accept="application/json,.json" data-import hidden></label>
        <a class="btn ghost" href="${esc(config.homeUrl)}">← Voltar ao sistema</a>`;
}

// Atualiza apenas os números vivos (evita re-renderizar a tela toda a cada segundo).
function tick() {
    checkRestAlert();
    const roomsVisible = state.tab === 'sala' || (state.focusOpen && state.focusView === 'sala');
    if (roomsVisible && !rooms.loading && nowMs() - rooms.lastTry >= ROOMS_REFRESH_MS) refreshRooms();
    root.querySelectorAll('[data-live="member"]').forEach((el) => {
        const member = { hoje_ms: Number(el.dataset.ms), estudando_desde: el.dataset.studying ? 'sim' : null };
        el.textContent = formatDuration(memberTodayMs(member, rooms.fetchedAt, nowMs()), { seconds: true });
    });
    const running = runningSession();
    const day = today();
    const stats = todayTotals();
    const set = (sel, text) => root.querySelectorAll(sel).forEach((el) => { el.textContent = text; });
    updateAvatars(stats.totalMs);
    const current = running ?? restInfo()?.session;
    if (current) set('[data-live="current-subject"]', formatDuration(stats.bySubject[current.subject_id] ?? 0, { seconds: true }));
    const rest = running ? null : restInfo();
    if (rest) set('[data-live="rest"]', formatDuration(rest.sinceMs, { seconds: true }));
    // Descanso passou do limite ou outra aba/aparelho mudou o estado: atualiza cabeçalho, foco e faixa.
    if (!running && !rest && (state.focusOpen || !root.querySelector('[data-current]').hidden)) {
        renderCurrent();
        renderFocus();
        root.querySelector('.rest-banner')?.remove();
    }
    set('[data-live="total"]', formatDuration(stats.totalMs, { seconds: true }));
    set('[data-live="maxfocus"]', formatDuration(stats.maxFocusMs));
    if (stats.endMs) set('[data-live="end"]', formatClock(stats.endMs, day, state.settings));
    root.querySelectorAll('[data-live="subject"]').forEach((el) => {
        el.textContent = formatDuration(stats.bySubject[el.dataset.id] ?? 0, { seconds: true });
    });
    if (running) set('[data-live="session"]', formatDuration(nowMs() - toMs(running.inicio), { seconds: true }));
    tickJornada(stats);
    const meta = state.settings.meta_diaria_min;
    if (meta) {
        const pct = Math.min(100, (stats.totalMs / (meta * 60_000)) * 100);
        root.querySelectorAll('[data-live="goal-bar"]').forEach((el) => { el.style.width = `${pct}%`; });
        set('[data-live="goal-pct"]', String(Math.floor(pct)));
    }
}

// ── Jornada (bosses derrotados com dias de objetivo cumprido) ────────────────

function objectiveLabel(objetivo) {
    const sep = objetivo.indexOf(':');
    const [kind, id] = sep < 0 ? [objetivo, ''] : [objetivo.slice(0, sep), objetivo.slice(sep + 1)];
    if (kind === 'tipo') return studyTypeName(id);
    if (kind === 'materia') return subjectById(id)?.nome ?? 'Matéria removida';
    return 'Estudo total';
}

function objectiveOptions(selected) {
    const opt = (value, label) => `<option value="${esc(value)}" ${value === selected ? 'selected' : ''}>${esc(label)}</option>`;
    const subjects = state.subjects.filter((x) => !x.deleted_at && (!x.arquivada_em || `materia:${x.id}` === selected)).sort((a, b) => a.ordem - b.ordem);
    return opt('total', 'Estudo total do dia')
        + `<optgroup label="Tipo de estudo">${allTypes(selected.startsWith('tipo:') ? selected.slice(5) : null).map((t) => opt(`tipo:${t.id}`, t.nome)).join('')}</optgroup>`
        + (subjects.length ? `<optgroup label="Matéria">${subjects.map((x) => opt(`materia:${x.id}`, x.nome)).join('')}</optgroup>` : '');
}

/** Vida do boss: segmentos (um por golpe) até 20 de vida; acima disso, barra contínua. */
function hpBar(current) {
    const aria = `role="meter" aria-label="Vida do boss" aria-valuemin="0" aria-valuemax="${current.maxHp}" aria-valuenow="${current.hp}"`;
    if (current.maxHp > 20) {
        return `<div class="hp solid" ${aria}><span style="width:${(current.hp / current.maxHp) * 100}%"></span></div>`;
    }
    return `<div class="hp" style="--n:${current.maxHp}" ${aria}>${Array.from({ length: current.maxHp }, (_, i) => `<i class="${i < current.hp ? 'on' : ''}"></i>`).join('')}</div>`;
}

function chargeText(met, remainingMs) {
    return met ? '⚔ Golpe de hoje desferido!' : `Faltam ${formatDuration(remainingMs)} para o golpe de hoje`;
}

function chargeBar(j) {
    const { met, pct, goalMs, progressMs } = j.today;
    return `<div class="charge ${met ? 'full' : ''}" data-live="jornada-charge">
        <div class="charge-bar"><span data-live="jornada-bar" style="width:${pct}%"></span></div>
        <small data-live="jornada-text">${chargeText(met, goalMs - progressMs)}</small></div>`;
}

const bossLabel = (current) => `Boss ${current.index + 1}/${BOSSES.length}${cycleMark(current.cycle)}`;

function bossCard() {
    const j = jornadaState(daily(), state.settings.jornada, today());
    if (!j) {
        return `<button type="button" class="boss-card invite" data-action="goto-jornada">
            <span class="boss-mini">${bossSvg(BOSSES.length - 1, { size: 64, locked: true })}</span>
            <span class="boss-info"><b>⚔ Jornada dos Bosses</b>
            <small>Cumpra um objetivo por dia (ex.: 5h de estudo) e derrote ${BOSSES.length} chefões.</small></span></button>`;
    }
    state.jornadaMet = j.today.met;
    const { current } = j;
    const boss = BOSSES[current.index];
    const fx = state.jornadaFx ? `fx-${state.jornadaFx}` : '';
    return `<button type="button" class="boss-card ${fx}" data-action="goto-jornada" style="--boss:${boss.cor}">
        <span class="boss-mini">${bossSvg(current.index, { size: 72, hp: current.hp, maxHp: current.maxHp })}</span>
        <span class="boss-info">
            <small>${bossLabel(current)} · ${esc(objectiveLabel(j.jornada.objetivo))} ${formatDuration(j.today.goalMs)}/dia</small>
            <b>${esc(boss.nome)}</b>
            ${hpBar(current)}
            ${chargeBar(j)}
        </span></button>`;
}

function jornadaForm(j) {
    const minutos = j?.minutos ?? JORNADA_PADRAO.minutos;
    return `<form class="form" data-form="jornada">
        <label>O que conta para o golpe
            <select name="objetivo">${objectiveOptions(j?.objetivo ?? JORNADA_PADRAO.objetivo)}</select></label>
        <label>Meta por dia
            <span class="hm"><input type="number" name="horas" min="0" max="24" value="${Math.floor(minutos / 60)}" aria-label="Horas"> h
            <input type="number" name="minutos" min="0" max="59" step="5" value="${minutos % 60}" aria-label="Minutos"> min</span></label>
        <label>Contar a partir de
            <input type="date" name="inicio" value="${j?.inicio ?? today()}" max="${today()}" required>
            <small>Uma data passada faz os dias já estudados contarem como golpes. Mudar a data recomeça a jornada dali.</small></label>
        <button class="btn" type="submit">${j ? 'Salvar objetivo' : '⚔ Começar a jornada'}</button>
    </form>`;
}

function renderJornada() {
    const j = jornadaState(daily(), state.settings.jornada, today());
    if (!j) {
        return `<section class="arena intro" style="--boss:${BOSSES.at(-1).cor}">
            <h2>Jornada dos Bosses</h2>
            <p>Cada dia em que você cumpre o seu objetivo é um golpe no chefão da vez. Derrote os ${BOSSES.length} vilões do estudo,
                da Preguiça ao Dragão da Banca.</p>
            <div class="lineup">${BOSSES.map((_, i) => bossSvg(i, { size: 60, locked: i > 0 })).join('')}</div>
        </section>
        <h3>Escolha seu objetivo</h3>
        ${jornadaForm(null)}`;
    }
    state.jornadaMet = j.today.met;
    const { current, defeated } = j;
    const boss = BOSSES[current.index];
    const fx = state.jornadaFx;
    const lastKo = defeated.at(-1);
    const victory = lastKo?.day === today() ? `<section class="victory ${fx === 'ko' ? 'fx-ko' : ''}" style="--boss:${BOSSES[lastKo.index].cor}">
        <span class="victory-boss">${bossSvg(lastKo.index, { size: 88, defeated: true })}</span>
        <span><b>Vitória!</b> Você derrotou ${esc(BOSSES[lastKo.index].nome)} hoje, com ${lastKo.hits.length} dias de objetivo cumprido.</span>
    </section>` : '';

    const lastDefeat = (i) => defeated.filter((d) => d.index === i).at(-1);
    const map = BOSSES.map((b, i) => {
        const ko = lastDefeat(i);
        const isCurrent = i === current.index;
        const known = isCurrent || ko;
        const status = isCurrent ? `<b>${current.hp}/${current.maxHp} HP</b>` : ko ? `Derrotado ${formatDay(ko.day, { day: '2-digit', month: '2-digit' })}` : `${BOSS_HP[i]} HP`;
        return `<li class="${isCurrent ? 'current' : ko ? 'defeated' : 'locked'}" style="--boss:${b.cor}">
            ${bossSvg(i, { size: 64, defeated: !isCurrent && Boolean(ko), locked: !known, hp: isCurrent ? current.hp : null, maxHp: current.maxHp })}
            <span class="name">${known ? esc(b.nome) : '???'}</span><small>${status}</small></li>`;
    }).join('');

    return `${victory}
        <section class="arena ${fx === 'hit' ? 'fx-hit' : ''} ${fx === 'ko' ? 'fx-enter' : ''}" style="--boss:${boss.cor}">
            <p class="arena-tag">${bossLabel(current)}${current.cycle ? ` · ciclo ${current.cycle + 1}, bosses mais fortes` : ''}</p>
            <h2>${esc(boss.nome)}</h2>
            <p class="arena-title">${esc(boss.titulo)}</p>
            <div class="arena-stage">
                <p class="taunt">${esc(boss.fala)}</p>
                <div class="arena-boss">${bossSvg(current.index, { size: 220, hp: current.hp, maxHp: current.maxHp })}</div>
                ${fx === 'hit' ? '<span class="dmg" aria-hidden="true">−1</span>' : ''}
            </div>
            <div class="hp-row"><span>HP</span>${hpBar(current)}<b>${current.hp}/${current.maxHp}</b></div>
            ${chargeBar(j)}
            <p class="arena-goal">Objetivo: ${esc(objectiveLabel(j.jornada.objetivo))} ≥ ${formatDuration(j.today.goalMs)} por dia · cada dia cumprido é 1 golpe</p>
        </section>
        ${cards([
            ['Golpes desferidos', String(j.totalHits)],
            ['Bosses derrotados', String(defeated.length)],
            ['Combo atual', `${j.combo} ${j.combo === 1 ? 'dia' : 'dias'}`],
            ['Melhor combo', `${j.bestCombo} ${j.bestCombo === 1 ? 'dia' : 'dias'}`],
        ])}
        ${current.hits.length ? `<h3>Golpes em ${esc(boss.nome)}</h3><div class="chips hits">${current.hits
            .map((d) => `<button type="button" data-action="goto-day" data-day="${d}">⚔ ${formatDay(d)}</button>`).join('')}</div>` : ''}
        <h3>Mapa da jornada</h3>
        <ol class="boss-map">${map}</ol>
        <h3>Objetivo</h3>
        ${jornadaForm(j.jornada)}`;
}

// Atualiza a carga do golpe de hoje a cada segundo; ao cumprir o objetivo, toca o golpe (e o nocaute).
function tickJornada(stats) {
    const j = normalizeJornada(state.settings.jornada);
    if (!j || today() < j.inicio) return;
    const goalMs = j.minutos * 60_000;
    const progressMs = objectiveProgressMs(stats, j.objetivo);
    const met = progressMs >= goalMs;
    root.querySelectorAll('[data-live="jornada-bar"]').forEach((el) => { el.style.width = `${Math.min(100, (progressMs / goalMs) * 100)}%`; });
    root.querySelectorAll('[data-live="jornada-text"]').forEach((el) => { el.textContent = chargeText(met, goalMs - progressMs); });
    root.querySelectorAll('[data-live="jornada-charge"]').forEach((el) => el.classList.toggle('full', met));
    const justHit = state.jornadaMet === false && met;
    state.jornadaMet = met;
    if (!justHit) return;
    const after = jornadaState(daily(), state.settings.jornada, today());
    const ko = after.defeated.at(-1)?.day === today();
    state.jornadaFx = ko ? 'ko' : 'hit';
    if (state.settings.retro_sound) playBossHitSound(ko);
    // No modo foco a animação espera: toca no próximo render, ao fechar o foco.
    if (!state.focusOpen && (state.tab === 'timer' || state.tab === 'jornada') && !dialog.open) render();
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

// Escolha de matéria (tela inicial e descanso): lista rolável, em ordem alfabética, para não virar
// uma tela enorme com muitas matérias.
// Tempo de hoje da matéria separado por tipo, quando houver mais de um (inclui "Sem tipo", ex.: sessão rodando).
function pickTypeSplit(types = {}) {
    const entries = Object.entries(types).filter(([, ms]) => ms >= 1000).sort((a, b) => b[1] - a[1]);
    if (entries.length < 2) return '';
    return `<span class="pick-types">${entries.map(([id, ms]) => `<span><i>${esc(studyTypeName(id === NO_TYPE ? null : id))}</i> ${formatDuration(ms, { seconds: true })}</span>`).join('')}</span>`;
}

function openSubjectPicker() {
    const current = runningSession()?.subject_id ?? restInfo()?.session.subject_id;
    const subjects = activeSubjects().sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));
    if (!subjects.length) return alert('Crie uma matéria primeiro.');
    const stats = dayStats(daily(), today());
    openDialog(`
        <h3>Começar matéria</h3>
        <div class="subject-picker">
            ${subjects.map((s) => `<button class="subject-pick" value="${s.id}" style="--c:${esc(s.cor)}" formnovalidate>
                <span class="dot"></span><span class="pick-main"><span class="name">${esc(s.nome)}${s.id === current ? ' <small>(atual)</small>' : ''}</span>
                ${pickTypeSplit(stats.bySubjectType[s.id])}</span>
                <small class="time">${formatDuration(stats.bySubject[s.id] ?? 0, { seconds: true })}</small></button>`).join('')}
        </div>
        <p class="form-error"></p>
        <div class="actions"><button class="btn ghost" value="cancel" formnovalidate>Cancelar</button></div>`,
    async (subjectId) => {
        await toggleSubject(subjectId);
        return null;
    });
}

function openSessionDialog(session = null, day = today(), gap = null) {
    const tz = state.settings.timezone;
    const subjects = state.subjects
        .filter((s) => !s.deleted_at && (!s.arquivada_em || s.id === session?.subject_id))
        .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));
    if (!subjects.length) return alert('Crie uma matéria primeiro.');
    const [dayStart] = dayBounds(day, state.settings);
    const defaultStart = session ? toMs(session.inicio) : gap?.start ?? Math.min(dayStart + 6 * 3_600_000, nowMs() - 3_600_000);
    const defaultEnd = session?.fim ? toMs(session.fim) : gap?.end ?? defaultStart + 3_600_000;

    openDialog(`
        <h3>${session ? 'Editar sessão' : 'Sessão manual'}</h3>
        <label>Matéria<select name="subject_id" required>
            ${subjects.map((s) => `<option value="${s.id}" ${s.id === session?.subject_id ? 'selected' : ''}>${esc(s.nome)}</option>`).join('')}
        </select></label>
        <label>Início<input type="datetime-local" name="inicio" required value="${msToLocalInput(defaultStart, tz)}"></label>
        <label>Fim<input type="datetime-local" name="fim" required value="${msToLocalInput(defaultEnd, tz)}"></label>
        <label>Tipo de estudo<select name="tipo">
            <option value="">Sem tipo</option>
            ${allTypes(session?.tipo).map((t) => `<option value="${esc(t.id)}" ${t.id === session?.tipo ? 'selected' : ''}>${esc(t.nome)}</option>`).join('')}
        </select></label>
        <label>Ou crie um tipo novo<input name="tipo_novo" maxlength="${CUSTOM_TYPE_MAX}" placeholder="Ex.: Flashcards" autocomplete="off"></label>
        <label>Observação / Conteúdo estudado
            <textarea name="observacao" rows="2" placeholder="Ex.: Artigos lidos, exercícios, tópicos...">${esc(session?.observacao ?? '')}</textarea>
        </label>
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
        const observacao = String(data.get('observacao') ?? '').trim() || null;
        const candidate = {
            ...(session ?? { id: uuid(), origem: 'manual', device_id: await getMeta('device_id'), created_at: iso(nowMs()) }),
            subject_id: String(data.get('subject_id')), inicio: iso(inicio), fim: iso(fim), status: 'ok', deleted_at: null,
            observacao,
            tipo: typeFromForm(data),
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
    const startDay = studyDayOf(toMs(session.inicio), state.settings);
    const startedAt = formatClock(toMs(session.inicio), startDay, state.settings)
        + (startDay === today() ? '' : ` de ${formatDay(startDay)}`);
    openDialog(`
        <h3>Ainda está estudando?</h3>
        <p>O cronômetro de <b>${esc(subject?.nome ?? 'Matéria')}</b> está ligado há
            <b>${formatDuration(nowMs() - toMs(session.inicio))}</b> (começou às <b>${startedAt}</b>). Enquanto você não confirmar, essa sessão não entra nas estatísticas.</p>
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

// ── Backup ───────────────────────────────────────────────────────────────────

// Exporta tudo o que está no aparelho (inclusive excluídos, para permitir restauração fiel).
async function exportBackup() {
    const at = nowMs();
    const backup = {
        app: 'estudos',
        versao: 1,
        gerado_em: iso(at),
        pendentes_de_sincronizar: state.sync.pending ?? 0,
        settings: state.settings,
        subjects: state.subjects,
        sessions: state.sessions,
        ddays: state.ddays,
    };
    const stamp = msToLocalInput(at, state.settings.timezone).replace('T', '_').replace(':', '');
    const url = URL.createObjectURL(new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' }));
    const link = Object.assign(document.createElement('a'), { href: url, download: `estudos_backup_${stamp}.json` });
    document.body.append(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 10_000);

    state.lastExportAt = at;
    await setMeta('last_export_at', at);
    render();
}

async function importBackup(file) {
    let plan;
    try {
        plan = planImport(JSON.parse(await file.text()), state);
    } catch (error) {
        return alert(error instanceof SyntaxError ? 'O arquivo não é um JSON válido.' : error.message);
    }
    const total = plan.subjects.length + plan.sessions.length + plan.ddays.length + plan.removals.length;
    const day = (at) => new Date(at).toLocaleDateString('pt-BR');
    const details = `${plan.subjects.length} matéria(s), ${plan.sessions.length} sessão(ões), ${plan.ddays.length} D-Day(s)`
        + (plan.replace
            ? `\nSUBSTITUIÇÃO: ${plan.removals.length} sessão(ões) do aparelho entre ${day(plan.replace.desde)} e ${day(plan.replace.ate)} (YPT, cronômetro e manuais) serão excluídas e trocadas pelas do arquivo. Exporte um backup antes, se quiser poder voltar.`
            : (plan.removals.length ? `\n${plan.removals.length} sessão(ões) serão excluídas.` : ''))
        + (plan.skipped ? `\n${plan.skipped} já existiam e serão ignoradas.` : '')
        + (plan.invalid ? `\n${plan.invalid} registro(s) inválido(s) serão descartados.` : '');
    if (!total) return alert(`Nada novo para importar.\n${details}`);
    if (!confirm(`Importar ${details}?`)) return;

    // Tudo numa transação só: rápido e sem import pela metade (exclusões gravadas sem as sessões novas).
    const at = iso(nowMs());
    const entries = [
        ...plan.subjects.map((record) => ({ type: 'subjects', record })),
        ...plan.ddays.map((record) => ({ type: 'ddays', record })),
        ...plan.removals.map((session) => ({ type: 'sessions', record: { ...session, deleted_at: at } })),
        ...plan.sessions.map((record) => ({ type: 'sessions', record })),
    ].map(({ type, record }) => ({ type, record: { ...record, updated_at: at } }));
    try {
        await saveLocalMany(entries);
    } catch (error) {
        return alert(`A importação falhou e nada foi alterado: ${error.message}`);
    }
    for (const type of ['subjects', 'ddays', 'sessions']) {
        const byId = new Map(state[type].map((r) => [r.id, r]));
        for (const entry of entries) if (entry.type === type) byId.set(entry.record.id, entry.record);
        state[type] = [...byId.values()];
    }
    sync?.schedule();
    render();
    alert(`Importado: ${details}.\nO envio para o servidor acontece em segundo plano.`);
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
    dismissRestAlert();
    // Saiu da tela cheia com Esc (sem clique): o widget abre no primeiro clique seguinte.
    if (state.widgetPending && !event.target.closest('[data-action="focus-close"], [data-action="widget-open"]')) {
        state.widgetPending = false;
        if (shouldAutoWidget()) openWidget();
    }
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
        'focus-open': () => openFocus(),
        'room-create': () => openRoomDialog('create'),
        'room-join': () => openRoomDialog('join'),
        'room-manage': () => openRoomManageDialog(),
        'room-pick': async () => { state.roomId = id; await setMeta('room_id', id); renderFocus(); tick(); },
        'room-copy': async () => {
            const room = currentRoom();
            const text = `Entra na minha sala de estudo "${room.nome}" no Horas de Estudo: código ${room.codigo} (${location.origin}/estudos → modo foco → ⇆).`;
            try { await navigator.clipboard.writeText(text); alert('Convite copiado.'); } catch { prompt('Copie o convite:', text); }
        },
        'room-leave': async () => {
            const room = currentRoom();
            if (!room || !confirm(`Sair da sala "${room.nome}"?`)) return;
            try { await refreshRooms(() => roomsApi.leave(room.id)); } catch (error) { alert(error.message); }
        },
        'focus-view': async () => {
            state.focusView = state.focusView === 'sala' ? 'classico' : 'sala';
            await setMeta('focus_view', state.focusView);
            renderFocus();
            tick();
        },
        'widget-open': () => openWidget(),
        'pick-avatar': async () => { await persist('settings', { ...state.settings, avatar: id }); render(); },
        'focus-close': () => {
            const autoWidget = shouldAutoWidget();
            state.widgetPending = false;
            if (autoWidget) openWidget(); // antes do render: o clique ainda vale como gesto
            closeFocus();
        },
        'new-subject': () => openSubjectDialog(),
        'pick-subject': () => openSubjectPicker(),
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
        'day-prev': () => { state.timelineDay = addDays(state.timelineDay ?? today(), -1); state.timelineSel.clear(); render(); },
        'day-next': () => { state.timelineDay = addDays(state.timelineDay ?? today(), 1); state.timelineSel.clear(); render(); },
        'goto-day': () => { state.tab = 'timeline'; state.timelineDay = button.dataset.day; state.timelineSel.clear(); render(); },
        'vtl-pick': () => {
            const { key } = button.dataset;
            if (!state.timelineSel.delete(key)) state.timelineSel.add(key);
            render();
        },
        'vtl-clear': () => { state.timelineSel.clear(); render(); },
        'stats-mode': () => { state.statsMode = button.dataset.mode; render(); },
        'stats-prev': () => { shiftStats(-1); render(); },
        'stats-goto': () => { state.statsAnchor = button.dataset.day; render(); },
        'stats-open': () => { state.statsMode = button.dataset.mode; state.statsAnchor = button.dataset.day; render(); },
        'trend-subject': () => { state.trendSubject = id || null; render(); },
        'gap-session': () => openSessionDialog(null, state.timelineDay ?? today(), { start: Number(button.dataset.start), end: Number(button.dataset.end) }),
        'stats-next': () => { shiftStats(1); render(); },
        'proj-mode': () => { state.proj.mode = button.dataset.mode; render(); },
        'proj-ritmo': () => { state.proj.ritmo = id; render(); },
        'proj-dday': () => { state.proj.dataAlvo = button.dataset.day; render(); },
        'proj-folga': () => {
            const day = Number(button.dataset.day);
            const folgas = state.proj.folgas;
            state.proj.folgas = folgas.includes(day) ? folgas.filter((x) => x !== day) : [...folgas, day].sort();
            render();
        },
        'proj-reset-start': () => { state.proj.inicioHoras = null; render(); },
        'proj-save-target': async () => {
            const alvo = state.proj.alvoHoras;
            if (!alvo) return;
            await persist('settings', { ...state.settings, alvo_horas: alvo });
            state.proj.alvoHoras = null;
            render();
        },
        'delete-dday': async () => {
            const dday = state.ddays.find((d) => d.id === id);
            if (confirm(`Excluir "${dday.titulo}"?`)) { await persist('ddays', { ...dday, deleted_at: iso(nowMs()) }); render(); }
        },
        'goto-jornada': () => { state.tab = 'jornada'; render(); window.scrollTo(0, 0); },
        'sync-now': () => sync.syncNow(),
        'export-backup': () => exportBackup(),
    };
    await handlers[action]?.();
});

root.addEventListener('change', async (event) => {
    const projField = event.target.closest('[data-proj] [name]');
    if (projField) {
        const p = state.proj;
        const raw = projField.value.trim();
        const num = raw === '' ? null : Number(raw);
        if (projField.name === 'inicioHoras') p.inicioHoras = num !== null && num >= 0 ? num : null;
        else if (projField.name === 'alvoHoras') p.alvoHoras = num !== null && num >= 1 ? Math.round(num) : null;
        else if (projField.name === 'manualHoras') p.manualHoras = Math.min(24, Math.max(0, num ?? 0));
        else if (projField.name === 'janela') p.janela = Number(raw);
        else if (projField.name === 'dataAlvo') p.dataAlvo = raw || null;
        render();
        return;
    }
    if (event.target.matches('[data-theme-picker]')) {
        applyTheme(event.target.value); // prévia; o render volta ao tema salvo se não salvar
        return;
    }
    const input = event.target.closest('[data-import]');
    if (!input?.files?.length) return;
    await importBackup(input.files[0]);
    input.value = '';
});

root.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-form]');
    if (!form) return;
    event.preventDefault();
    const data = new FormData(form);
    if (form.dataset.form === 'settings') {
        const meta = String(data.get('meta_diaria_min') ?? '').trim();
        const descanso = String(data.get('descanso_min') ?? '').trim();
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
            descanso_min: descanso === '' ? null : Number(descanso),
            retro_sound: data.get('retro_sound') === 'on',
            tema: String(data.get('tema') ?? 'auto'),
        });
        if (widgetSupported()) {
            state.widgetAuto = data.get('widget_auto') === 'on';
            await setMeta('widget_auto', state.widgetAuto);
        }
    } else if (form.dataset.form === 'jornada') {
        const minutos = Math.round(Number(data.get('horas') || 0) * 60) + Number(data.get('minutos') || 0);
        if (minutos < 10 || minutos > 1440) return alert('O objetivo precisa ficar entre 10 minutos e 24 horas.');
        const inicio = String(data.get('inicio') || '') || state.settings.jornada?.inicio || today();
        await persist('settings', { ...state.settings, jornada: { inicio, objetivo: String(data.get('objetivo') || 'total'), minutos } });
        state.jornadaMet = null;
    } else if (form.dataset.form === 'dday') {
        await persist('ddays', { id: uuid(), titulo: String(data.get('titulo')).trim(), data: String(data.get('data')), deleted_at: null, created_at: iso(nowMs()) });
    }
    render();
});

document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement && state.focusOpen) state.widgetPending = true;
});

document.addEventListener('visibilitychange', async () => {
    if (document.visibilityState === 'hidden') {
        await markSeen();
    } else {
        render();
        checkSuspicious();
        if (state.focusOpen) keepScreenOn(true);
    }
});

// ── Inicialização ────────────────────────────────────────────────────────────

async function reload() {
    Object.assign(state, await loadAll());
}

async function boot() {
    await ensureUser(Number(config.userId));
    await reload();
    state.lastExportAt = await getMeta('last_export_at');
    state.widgetAuto = (await getMeta('widget_auto')) ?? true;
    state.widgetHintSeen = Boolean(await getMeta('widget_hint_seen'));
    state.focusView = (await getMeta('focus_view')) ?? 'classico';
    state.roomId = (await getMeta('room_id')) ?? null;
    restAlert.notified = (await getMeta('rest_alert')) ?? null;

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
