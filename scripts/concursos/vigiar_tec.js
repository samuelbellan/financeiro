/*
 * Vigia dos cadernos do TEC: lista a pasta inteira (uma chamada só, sem expandir subpastas), vê no
 * receptor local quais cadernos já estão salvos e baixa só os novos, cada um na subpasta da matéria.
 * Não usa IA nem gasta tokens: roda no seu Chrome, logado no TEC.
 *
 * Como usar (resumo; detalhes em scripts/concursos/PROMPT-antigravity-cadernos-novos.md):
 *  1. python -I scripts/concursos/receptor_tec.py --raiz storage/concursos/tec --importar
 *  2. Abra http://127.0.0.1:8765/ e arraste o favorito "TEC: cadernos novos" para a barra.
 *  3. No TEC, clique no favorito. Ele abre uma aba de dados e roda este arquivo lá.
 *     (Também dá para colar este arquivo no Console de uma página /api/ do TEC.)
 *
 * Só entram cadernos finalizados, com nome "Cnn. SIGLA ..." de uma matéria conhecida (tabela abaixo);
 * os "Erradas:" (refação) ficam de fora. Se o TEC responder 405/429 o script espera e continua.
 */
(async () => {
  const PASTA_TEC = 5145567;
  const RECEPTOR = 'http://127.0.0.1:8765';
  const PAUSA = 300;              // ms entre pedidos
  const ESPERA_BLOQUEIO = 60000;  // ms entre tentativas quando o TEC responde 405/429
  const TENTATIVAS = 30;
  // sigla no nome do caderno → subpasta em storage/concursos/tec (a mesma de config/concursos.php → tec.subpastas)
  const MATERIAS = {
    AUD: 'aud', CTBGA: 'ctbga', DADM: 'dadm', DCIV: 'dciv', DCON: 'dcon', DEMP: 'demp', DPEN: 'dpen',
    DTRIB: n => (/Expert/i.test(n) ? 'dtrib-expert' : 'dtrib'), MATFI: 'mfin', PORT: 'port', RLOG: 'rlog',
  };

  if (window.__tecVigia) return;
  window.__tecVigia = true;

  // Painel de progresso por cima da página de dados.
  const painel = document.createElement('div');
  painel.style.cssText = 'position:fixed;inset:0;z-index:99999;background:#fff;color:#222;font:14px/1.5 system-ui;padding:24px;overflow:auto;white-space:pre-wrap';
  document.body.appendChild(painel);
  document.title = 'TEC: vigia';
  const log = (...a) => {
    const t = new Date().toLocaleTimeString() + '  ' + a.join(' ');
    painel.textContent += t + '\n';
    painel.scrollTop = painel.scrollHeight;
    (window.__tecLog = window.__tecLog || []).push(t);
  };
  const esp = ms => new Promise(r => setTimeout(r, ms));
  const limpo = n => n.trim().replace(/\s+-\s+(Aprendiz\s+-\s+)?Guruja$/, '');
  const subpasta = n => {
    const m = n.match(/^C\d{2}\.\s*([A-Z]+)/);
    const v = m && MATERIAS[m[1]];
    return typeof v === 'function' ? v(n) : v || null;
  };

  const get = async url => {
    for (let t = 0; t < TENTATIVAS; t++) {
      const r = await fetch(url, { headers: { Accept: 'application/json' } });
      if (r.ok) return r.json();
      if (r.status === 404 || r.status === 400) return null;
      if (r.status === 401 || r.status === 403) throw new Error('deslogado (' + r.status + '): faça login no TEC e clique de novo');
      log(`TEC respondeu ${r.status}; esperando ${ESPERA_BLOQUEIO / 1000} s (${t + 1}/${TENTATIVAS}). Se pedir "não sou um robô", resolva em outra aba.`);
      await esp(ESPERA_BLOQUEIO);
    }
    throw new Error('bloqueado pelo TEC por muito tempo; tente mais tarde');
  };

  try {
    log('Lendo a pasta do TEC…');
    const lista = await get(`/api/pastas-cadernos/${PASTA_TEC}/subpastas/itens`);
    const todos = (lista && lista.itens || []).filter(i => !i.subpasta);
    let feitos;
    try {
      feitos = new Set(await (await fetch(RECEPTOR + '/feitos')).json());
    } catch (e) {
      throw new Error('o receptor não respondeu em ' + RECEPTOR + '. Ele está aberto?');
    }
    const semMateria = new Set();
    const novos = [];
    for (const i of todos) {
      const n = limpo(i.nome);
      if (!i.finalizado || /^Erradas/i.test(n) || feitos.has(String(i.id))) continue;
      const p = subpasta(n);
      if (!p) { semMateria.add(n.replace(/^(C\d{2}\.\s*\S+(\s\S+)?).*/, '$1')); continue; }
      novos.push({ id: String(i.id), n, pasta: p });
    }
    log(`${todos.length} cadernos na pasta, ${novos.length} novos para baixar.`);
    if (semMateria.size) log('Ignorados (sem matéria no app): ' + [...semMateria].slice(0, 15).join(', '));
    if (window.TEC_SO_LISTAR) {  // teste: window.TEC_SO_LISTAR = true antes de rodar
      novos.forEach(c => log(`  ${c.pasta}: ${c.n} (${c.id})`));
      return;
    }

    let salvos = 0;
    for (const cad of novos) {
      const out = { caderno: cad.n, cadernoId: cad.id, extraidoEm: new Date().toISOString(), questoes: [] };
      const ids = new Set();
      for (let i = 1; i <= 300; i++) {
        const j = await get(`/api/cadernos/${cad.id}/questoes/${i}?atualizarCronometro=false`);
        if (!j || !j.questao) break;
        const q = j.questao;
        if (ids.has(q.idQuestao)) break;
        ids.add(q.idQuestao);
        const resolvida = q.alternativaSelecionada != null;
        const errou = resolvida && q.alternativaSelecionada !== q.numeroAlternativaCorreta;
        const item = {
          pos: i, id: q.idQuestao, tipo: q.tipoQuestao, banca: q.bancaSigla, orgao: q.orgaoSigla,
          orgaoNome: q.orgaoNome, cargo: q.cargoSigla, ano: q.concursoAno, materia: q.nomeMateria,
          assunto: q.nomeAssunto, anulada: q.anulada, desatualizada: q.desatualizada,
          enunciado: q.enunciado, alternativas: q.alternativas, marcada: q.alternativaSelecionada,
          correta: q.numeroAlternativaCorreta, resolvida, errou,
        };
        if (errou && q.possuiComentario) {
          await esp(PAUSA);
          const c = await get(`/api/questoes/${q.idQuestao}/comentario?tokenPreVisualizacao=`);
          if (c && c.comentario) {
            item.comentario = { texto: c.comentario.textoComentario, professor: c.comentario.nomeProfessor, data: c.comentario.dataPublicacaoComentario };
          }
        }
        out.questoes.push(item);
        await esp(PAUSA);
      }
      if (!out.questoes.length) { log(`${cad.n}: nenhuma questão (pulado)`); continue; }
      const nome = cad.id + '-' + cad.n.replace(/[^A-Za-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 80);
      const r = await fetch(`${RECEPTOR}/salvar?pasta=${cad.pasta}&nome=${encodeURIComponent(nome)}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(out),
      });
      if (!r.ok) throw new Error(`o receptor recusou ${cad.n} (${r.status}: ${await r.text()}). Ele está com --raiz?`);
      salvos++;
      log(`${salvos}/${novos.length} ${cad.pasta}: ${cad.n} — ${out.questoes.length} questões, ${out.questoes.filter(x => x.errou).length} erradas`);
    }

    if (salvos) {
      log('Importando no app…');
      const r = await fetch(RECEPTOR + '/fim', { method: 'POST' });
      log(await r.text());
    }
    log(salvos ? 'Pronto. Peça ao Antigravity para seguir o PENDENTES.md.' : 'Nada novo. Pode fechar esta aba.');
  } catch (e) {
    log('PAROU: ' + e.message + ' (os cadernos já salvos ficam; clique no favorito de novo para continuar)');
  } finally {
    window.__tecVigia = false;
  }
})();
