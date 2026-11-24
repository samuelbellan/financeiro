/*
 * Extrai os cadernos resolvidos de uma pasta do TEC Concursos e envia cada um ao receptor local
 * (receptor_tec.py), que grava um JSON por caderno. Os JSON alimentam o converter_tec.py.
 *
 * Como usar:
 *  1. Rode o receptor:  python -I receptor_tec.py --saida "<pasta no Google Drive>"
 *  2. No Chrome, logado no TEC, abra a pasta: https://www.tecconcursos.com.br/questoes/pastas/5145567
 *  3. F12 > Console, cole este arquivo inteiro e Enter. (Na primeira vez o Chrome pede para
 *     digitar "allow pasting" ou "permitir colar".)
 *  4. Ajuste FILTRO para pegar só a disciplina desejada (texto que aparece no nome do caderno).
 *
 * Pula os cadernos que o receptor já tem: se o TEC recarregar a página (validação de robô) ou
 * deslogar, é só fazer login, voltar à pasta e colar de novo que ele continua de onde parou.
 * Para de propósito depois de LIMITE cadernos: o TEC bloqueia por volta de 15-18 seguidos.
 */
(async () => {
  const FILTRO = /CTBGA/;      // so cadernos cujo nome casa com isto (use /./ para todos)
  const LIMITE = 12;           // cadernos por rodada; cole de novo mais tarde para continuar
  const PAUSA = 2000;          // ms entre pedidos ao TEC
  const RECEPTOR = 'http://127.0.0.1:8765';

  const esp = ms => new Promise(r => setTimeout(r, ms));
  const log = (...a) => console.log('%c[TEC]', 'color:#0a7;font-weight:bold', ...a);

  let feitos;
  try {
    feitos = new Set(await (await fetch(RECEPTOR + '/feitos')).json());
  } catch (e) {
    console.error('[TEC] Receptor nao respondeu em ' + RECEPTOR + '. Ele esta rodando?');
    return;
  }

  const vistos = new Set();
  const cadernos = [...document.querySelectorAll('a')]
    .filter(a => /questoes\/cadernos\/\d+$/.test(a.href))
    .map(a => ({ id: a.href.split('/').pop(), n: a.textContent.trim().replace(/\s+-\s+Aprendiz\s+-\s+Guruja$/, '') }))
    .filter(c => c.n && FILTRO.test(c.n) && !vistos.has(c.id) && vistos.add(c.id));
  const fila = cadernos.filter(c => !feitos.has(c.id));
  log(`${cadernos.length} cadernos na pasta com o filtro, ${cadernos.length - fila.length} ja salvos, ${fila.length} faltando. Esta rodada: ate ${LIMITE}.`);
  if (!cadernos.length) log('Nenhum caderno encontrado: a pagina da pasta terminou de carregar? O FILTRO esta certo?');

  const get = async url => {
    for (let t = 0; t < 10; t++) {
      const r = await fetch(url, { headers: { Accept: 'application/json' } });
      if (r.ok) return r.json();
      if (r.status === 404 || r.status === 400) return null;
      if (r.status === 401 || r.status === 403) throw new Error('deslogado (' + r.status + '): faca login e cole de novo');
      log(`TEC respondeu ${r.status}; esperando 2 min (tentativa ${t + 1}/10)...`);
      await esp(120000);
    }
    throw new Error('bloqueado pelo TEC; tente mais tarde');
  };

  let feitosAgora = 0;
  for (const cad of fila.slice(0, LIMITE)) {
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
    feitosAgora++;
    log(`${feitosAgora}/${Math.min(LIMITE, fila.length)} ${cad.n}: ${out.questoes.length} questoes, ${out.questoes.filter(x => x.errou).length} erradas, receptor=${r.status}`);
  }
  log(`Fim da rodada: ${feitosAgora} salvos, faltam ${fila.length - feitosAgora}.`);
})();
