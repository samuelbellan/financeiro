// Efeitos sonoros retrô 8-bit sintetizados via Web Audio API (zero dependências externas e 100% offline).

let audioCtx = null;

function getAudioContext() {
    if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        audioCtx = new AudioContextClass();
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
    }
    return audioCtx;
}

/** Toca uma nota 8-bit estilo square wave com envelope rápido */
function playNote(ctx, freq, startTime, duration, gainValue = 0.08) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'square';
    osc.frequency.setValueAtTime(freq, startTime);

    gain.gain.setValueAtTime(gainValue, startTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start(startTime);
    osc.stop(startTime + duration);
}

/** Arpeggio ascendente de vitória retrô ao subir de estágio */
export function playLevelUpSound() {
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;
        const notes = [
            { f: 523.25, d: 0.08 }, // C5
            { f: 659.25, d: 0.08 }, // E5
            { f: 783.99, d: 0.08 }, // G5
            { f: 1046.50, d: 0.20 }, // C6
        ];
        let offset = 0;
        for (const note of notes) {
            playNote(ctx, note.f, now + offset, note.d, 0.08);
            offset += 0.07;
        }
    } catch {
        // Silêncio em caso de bloqueio de áudio pelo navegador
    }
}

/** Sino retrô suave ao pausar ou concluir o foco */
export function playFocusEndSound() {
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;
        playNote(ctx, 659.25, now, 0.12, 0.05); // E5
        playNote(ctx, 880.00, now + 0.10, 0.25, 0.06); // A5
    } catch {
        // Silêncio em caso de bloqueio
    }
}
