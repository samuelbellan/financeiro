/*
 * Extrai os cadernos resolvidos de uma pasta do TEC Concursos e envia cada um ao receptor local
 * (receptor_tec.py), que grava um JSON por caderno. Os JSON alimentam o converter_tec.py.
 *
 * Roda em duas passadas, porque a página normal do TEC se recarrega sozinha quando o site
 * responde 405/429 (bloqueio por volume) e a recarga mata o script:
 *
 *  1. Rode o receptor:  python -I receptor_tec.py --saida "<pasta no Google Drive>"
 *  2. No Chrome, logado no TEC, abra a pasta (https://www.tecconcursos.com.br/questoes/pastas/5145567)
 *     e EXPANDA as subpastas da disciplina, para os cadernos aparecerem na página.
 *  3. F12 > Console, ajuste FILTRO abaixo, cole este arquivo inteiro e Enter. (Na primeira vez o
 *     Chrome pede para digitar "allow pasting" / "permitir colar".)
 *     -> Ele guarda a lista dos cadernos e abre, na mesma aba, uma página só de dados do TEC.
 *  4. Nessa página de dados (texto JSON), abra o Console de novo, cole o arquivo de novo e Enter.
 *     -> Aqui a página não se recarrega: nos bloqueios o script só espera e continua.
 *
 * Pula os cadernos que o receptor já tem: se algo parar, cole de novo na página de dados.
 * Se o TEC pedir a verificação "não sou um robô", resolva numa OUTRA aba do TEC e deixe esta
 * rodando: ela retoma quando o acesso voltar.
 */
(async () => {
  const FILTRO = /DEMP/;       // so cadernos cujo nome casa com isto (use /./ para todos)
  const PULAR = /^Erradas/;    // cadernos de refacao das erradas: ficam de fora
  const PAUSA = 300;           // ms entre pedidos (o bloqueio vem do volume, nao da velocidade)
  const ESPERA_BLOQUEIO = 60000; // ms entre tentativas quando o TEC responde 405/429
  const TENTATIVAS = 30;       // ~30 min de espera maxima por bloqueio
  const RECEPTOR = 'http://127.0.0.1:8765';
  const CHAVE = 'tecFila';

  const esp = ms => new Promise(r => setTimeout(r, ms));
  const log = (...a) => console.log('%c[TEC]', 'color:#0a7;font-weight:bold', new Date().toLocaleTimeString(), ...a);

  // Passada 1: na pagina da pasta, guarda a lista e vai para a pagina de dados.
  if (!location.pathname.startsWith('/api/')) {
    const vistos = new Set();
    const cadernos = [...document.querySelectorAll('a')]
      .filter(a => /questoes\/cadernos\/\d+$/.test(a.href))
      .map(a => ({ id: a.href.split('/').pop(), n: a.textContent.trim().replace(/\s+-\s+(Aprendiz\s+-\s+)?Guruja$/, '') }))
      .filter(c => c.n && FILTRO.test(c.n) && !PULAR.test(c.n) && !vistos.has(c.id) && vistos.add(c.id));
    if (!cadernos.length) {
      log('Nenhum caderno encontrado. Expandiu as subpastas? O FILTRO esta certo?');
      return;
    }
    localStorage.setItem(CHAVE, JSON.stringify(cadernos));
    log(`${cadernos.length} cadernos guardados. Abrindo a pagina de dados: quando carregar, abra o Console e cole este script de novo.`);
    location.href = `/api/cadernos/${cadernos[0].id}/questoes/1?atualizarCronometro=false`;
    return;
  }

  // Passada 2: na pagina de dados, extrai.
  if (window.__tecRodando) {
    log('Ja esta rodando nesta aba.');
    return;
  }
  window.__tecRodando = true;
  const cadernos = JSON.parse(localStorage.getItem(CHAVE) || '[]');
  if (!cadernos.length) {
    log('Lista vazia: rode primeiro na pagina da pasta (passo 3).');
    window.__tecRodando = false;
    return;
  }

  let feitos;
  try {
    feitos = new Set(await (await fetch(RECEPTOR + '/feitos')).json());
  } catch (e) {
    console.error('[TEC] Receptor nao respondeu em ' + RECEPTOR + '. Ele esta rodando?');
    window.__tecRodando = false;
    return;
  }
  const fila = cadernos.filter(c => !feitos.has(c.id));
  log(`${cadernos.length} cadernos na lista, ${cadernos.length - fila.length} ja salvos, ${fila.length} faltando.`);

  const get = async url => {
    for (let t = 0; t < TENTATIVAS; t++) {
      const r = await fetch(url, { headers: { Accept: 'application/json' } });
      if (r.ok) return r.json();
      if (r.status === 404 || r.status === 400) return null;
      if (r.status === 401 || r.status === 403) throw new Error('deslogado (' + r.status + '): faca login em outra aba e cole de novo');
      log(`TEC respondeu ${r.status}; esperando ${ESPERA_BLOQUEIO / 1000} s (tentativa ${t + 1}/${TENTATIVAS}). Se pedir "nao sou um robo", resolva em outra aba.`);
      await esp(ESPERA_BLOQUEIO);
    }
    throw new Error('bloqueado pelo TEC por muito tempo; tente mais tarde');
  };

  let salvos = 0;
  for (const cad of fila) {
    const out = { caderno: cad.n, cadernoId: cad.id, extraidoEm: new Date().toISOString(), questoes: [] };
    const ids = new Set();
    try {
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
        window.__tecEstado = cad.n + ' questao ' + i;
        await esp(PAUSA);
      }
    } catch (e) {
      console.error('[TEC] Parou em ' + cad.n + ': ' + e.message + '. Esse caderno NAO foi salvo.');
      break;
    }
    if (!out.questoes.length) {
      log(`${cad.n}: nenhuma questao (pulado)`);
      continue;
    }
    const nome = cad.id + '-' + cad.n.replace(/[^A-Za-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 80);
    const r = await fetch(RECEPTOR + '/salvar?nome=' + encodeURIComponent(nome), {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(out),
    });
    salvos++;
    log(`${salvos}/${fila.length} ${cad.n}: ${out.questoes.length} questoes, ${out.questoes.filter(x => x.errou).length} erradas, receptor=${r.status}`);
  }
  log(`Fim: ${salvos} salvos, faltam ${fila.length - salvos}.`);
  window.__tecEstado = 'fim';
  window.__tecRodando = false;
})();
