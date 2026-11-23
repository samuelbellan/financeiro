// Página inicial (inicio.html): contagem regressiva e desempenho, tarefas de hoje (flashcards, refazer erros,
// simulados, caderno), caderno, simulados, edital e áreas, a partir das respostas salvas.
// Dados: dados/painel.json e dados/flashcards_indice.json (gerados por tools/gerar_dados.py).
const [PAINEL, FLASH_INDICE] = await Promise.all([carregarJson('dados/painel.json'), carregarJson('dados/flashcards_indice.json')]);
const $=id=>document.getElementById(id);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const read=key=>{try{const v=JSON.parse(localStorage.getItem(key)||'{}');return v&&typeof v==='object'&&!Array.isArray(v)?v:{}}catch(_){return {}}};
const pct=(ok,done)=>done?Math.round(ok/done*100):null;
const grade=p=>p===null?'':p>=70?'ok':p>=50?'mid':'no';
const plural=(n,um,varios)=>`${n} ${n===1?um:varios}`;
const num=n=>n.toLocaleString('pt-BR');
// Data da prova (js/config.js; a mesma usada em js/refazer.js) e o dia de hoje, local.
const PROVA=(window.CONFIG&&CONFIG.dataProva)||'';
const HOJE=(d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`)(new Date());
// Uma tarefa da faixa "Para hoje": o número do dia, o que é e o progresso.
function tarefa(id,{n,o,d,feito}){const el=$(id);el.classList.toggle('feito',!!feito);el.setAttribute('aria-label',`${n} ${o}. ${d}`);
 el.innerHTML=`<span class="t-n">${typeof n==='number'?num(n):n}</span><span class="t-o">${esc(o)}</span><span class="t-d">${esc(d)}</span>`}

// Nome "Simulado 1 · Exemplo…": a primeira parte em destaque.
function label(name){const i=name.indexOf(' · ');return i<0?esc(name):`<span class="lead">${esc(name.slice(0,i))}</span> · ${esc(name.slice(i+3))}`}
function row({name,done,total,ok,sub=''}){
 const p=pct(ok,done),cov=total?done/total*100:0;
 const aria=`${name}: ${done} de ${total} respondidas`+(p===null?'':`, ${p}% de acerto`);
 return `<div class="r${done?'':' idle'}" role="listitem" aria-label="${esc(aria)}">`+
  `<span class="r-name" title="${esc(name)}">${label(name)}</span>`+
  `<span class="r-bar" aria-hidden="true"><i style="width:${cov}%"></i></span>`+
  `<span class="r-n" aria-hidden="true">${done}/${total}</span>`+
  `<span class="r-s ${grade(p)}" aria-hidden="true">${p===null?'—':p+'%'}</span>`+
  (sub?`<span class="r-sub" aria-hidden="true">${esc(sub)}</span>`:'')+`</div>`;
}
const list=(el,html)=>{el.setAttribute('role','list');el.innerHTML=html};

// Caderno de questões (respostas em lom_st). O nome do caderno vem do build (PAINEL.lom.info).
const CAD=(PAINEL.lom.info&&PAINEL.lom.info.titulo)?'Caderno ('+PAINEL.lom.info.titulo+')':'Caderno';
const lomAns=read('lom_st'), lomMap=PAINEL.lom.q, lomTotal=Object.keys(lomMap).length;
const lomSec=PAINEL.lom.secoes.map(name=>({name,total:0,done:0,ok:0}));
for(const n of Object.keys(lomMap))lomSec[lomMap[n]].total++;
let lomDone=0,lomOk=0;
for(const [n,a] of Object.entries(lomAns)){if(!(n in lomMap)||!a)continue;lomDone++;if(a.ok)lomOk++;const s=lomSec[lomMap[n]];s.done++;if(a.ok)s.ok++}

// Simulados: página atual e as duas páginas antigas.
const simAns=read('simulados-respostas-v1');
const L='ABCDE', simMap=PAINEL.simulados.q, simTotal=Object.keys(simMap).length;
const fontes=PAINEL.simulados.fontes.map(name=>({name,total:0,done:0,ok:0})), areas=PAINEL.simulados.areas.map(name=>({name,total:0,done:0,ok:0}));
for(const [f,a] of Object.values(simMap)){fontes[f].total++;areas[a].total++}
let simDone=0,simOk=0;
for(const [id,a] of Object.entries(simAns)){const m=simMap[id];if(!m||!a)continue;const ok=a.ok===true||L[a.c]===a.checked;simDone++;if(ok)simOk++;for(const g of [fontes[m[0]],areas[m[1]]]){g.done++;if(ok)g.ok++}}

// Flashcards: mesma agenda de flashcards.js (lom_fc) e mesmo limite de cards novos por dia (lom_fc_cfg),
// e de revisões por dia, que valem para cada lei. FLASH_INDICE.cards: [id, índice da seção, índice da lei].
(function(){
 const fc=read('lom_fc'), cfg=Object.assign({novos:20,revs:100},read('lom_fc_cfg'));
 // Suspensos ("susp") e adiados (até a data) não entram no que há para hoje.
 const foraFc=read('lom_fc_fora');
 const d=new Date(), hoje=`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
 const cards=FLASH_INDICE.cards, total=cards.length;
 const leis=FLASH_INDICE.leis.map(()=>({total:0,vistos:0,novosHoje:0,due:0,revHoje:0,foraNovos:0}));
 // Revisado hoje: vencimento menos intervalo é hoje (como em flashcards.js).
 const menos=(s,n)=>{const [y,m,dd]=s.split('-').map(Number),x=new Date(y,m-1,dd-n);return `${x.getFullYear()}-${String(x.getMonth()+1).padStart(2,'0')}-${String(x.getDate()).padStart(2,'0')}`};
 let vistos=0;
 for(const [id,,li] of cards){const l=leis[li],st=fc[id],fo=foraFc[id],out=fo==='susp'||(fo&&fo>hoje);l.total++;
  if(!st){if(out)l.foraNovos++;continue}vistos++;l.vistos++;
  if(st.first===hoje)l.novosHoje++;else if(menos(st.due,st.ivl||0)===hoje)l.revHoje++;
  if(st.due<=hoje&&!out)l.due++}
 const due=leis.reduce((n,l)=>n+Math.min(l.due,Math.max(0,(+cfg.revs||0)-l.revHoje)),0);
 const novos=leis.reduce((n,l)=>n+Math.min(l.total-l.vistos-l.foraNovos,Math.max(0,(+cfg.novos||0)-l.novosHoje)),0), hojeTotal=due+novos;
 tarefa('tFc',!vistos?{n:hojeTotal,o:'flashcards novos para começar',d:'20 cards novos por dia em cada lei'}
  :hojeTotal?{n:hojeTotal,o:hojeTotal===1?'flashcard para hoje':'flashcards para hoje',d:`${num(due)} revisões e ${num(novos)} novos; ${num(vistos)} de ${num(total)} já vistos`}
  :{n:'✓',o:'Flashcards em dia',d:`${num(vistos)} de ${num(total)} cards já vistos`,feito:true});
})();

// Topo: quantos dias faltam para a prova e o desempenho geral numa frase.
const done=lomDone+simDone, ok=lomOk+simOk, total=lomTotal+simTotal, p=pct(ok,done);
(function(){
 const fim=done?`Você acertou <b class="ok">${p}%</b> das <b>${num(done)}</b> questões que respondeu; <b class="no">${num(done-ok)}</b> ${done-ok===1?'está errada':'estão erradas'}.`
  :`Você ainda não respondeu nenhuma questão. Comece pelo <a href="caderno.html">${esc(CAD)}</a> ou pelos <a href="simulados.html">Simulados</a>.`;
 if(!PROVA){$('headline').textContent='Seu desempenho';$('desempenho').innerHTML=fim;return}
 const dp=new Date(PROVA+'T12:00'),dias=Math.round((dp-new Date(HOJE+'T12:00'))/864e5);
 $('headline').textContent=dias>1?`Faltam ${dias} dias para a prova.`:dias===1?'A prova é amanhã.':dias===0?'A prova é hoje.':`A prova foi em ${PROVA.split('-').reverse().join('/')}.`;
 const quando=dp.toLocaleDateString('pt-BR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
 $('desempenho').innerHTML=esc(quando[0].toUpperCase()+quando.slice(1))+'. '+fim;
})();
$('cover').innerHTML=`<p><span><b>${num(done)}</b> de ${num(total)} questões respondidas</span>`+
 (done?`<span><b class="ok">${num(ok)}</b> ${ok===1?'acerto':'acertos'}, <b class="no">${num(done-ok)}</b> ${done-ok===1?'erro':'erros'}</span>`:'')+`</p>`+
 `<div class="track" role="progressbar" aria-label="Questões respondidas" aria-valuemin="0" aria-valuemax="${total}" aria-valuenow="${done}"><i style="width:${total?done/total*100:0}%"></i></div>`;

// Para hoje: Refazer erros (contado como em js/refazer.js: cada erro do Caderno e dos Simulados entra na caixa 0,
// vencendo hoje, até ser refeito), o próximo simulado a terminar e o Caderno.
(function(){
 const rf=read('refazer-v1'),chaves=new Set(Object.keys(rf));
 for(const [id,a] of Object.entries(simAns))if(a&&simMap[id]&&!(a.ok===true||L[a.c]===a.checked))chaves.add('s:'+id);
 for(const [n,a] of Object.entries(lomAns))if(a&&n in lomMap&&!a.ok)chaves.add('c:'+n);
 let erros=0,vencem=0,dominadas=0;
 for(const k of chaves){
  const id=k.slice(2);if(k[0]==='s'?!simMap[id]:!(id in lomMap))continue;
  erros++;const e=rf[k];
  if(!e){vencem++;continue}
  if(e.box>=3)dominadas++;
  if(e.due<=HOJE)vencem++;
 }
 tarefa('tRf',!erros?{n:'✓',o:'Nenhum erro para refazer',d:'Os erros do Caderno e dos Simulados aparecem lá',feito:true}
  :vencem?{n:vencem,o:vencem===1?'questão para refazer':'questões para refazer',d:`${num(dominadas)} de ${num(erros)} erros já dominados`}
  :{n:'✓',o:'Refazer erros em dia',d:`${num(dominadas)} de ${num(erros)} erros já dominados`,feito:true});
 // Próximo simulado: o primeiro começado e não terminado; senão, o primeiro não começado.
 const prox=fontes.find(f=>f.done&&f.done<f.total)||fontes.find(f=>!f.done);
 const curto=f=>f.name.split(' · ')[0];
 tarefa('tSim',prox?{n:prox.total-prox.done,o:`questões para terminar o ${curto(prox)}`,d:prox.done?`${prox.done} de ${prox.total} feitas, ${pct(prox.ok,prox.done)}% de acerto`:`Ainda não começado, ${prox.total} questões`}
  :{n:'✓',o:'Todos os simulados feitos',d:`${num(simDone)} questões, ${pct(simOk,simDone)}% de acerto`,feito:true});
 const faltam=lomTotal-lomDone;
 tarefa('tLom',faltam?{n:faltam,o:'questões no '+CAD,d:lomDone?`${num(lomDone)} de ${num(lomTotal)} feitas, ${pct(lomOk,lomDone)}% de acerto`:'Ainda não começado'}
  :{n:'✓',o:CAD+' feito',d:`${pct(lomOk,lomDone)}% de acerto`,feito:true});
})();

// Por dia (historico.js): questões do Caderno, dos Simulados e do Refazer erros (acertos e erros) e flashcards revisados.
// Respostas antigas do Caderno e dos Simulados não têm data; o aluno pode contá-las como de hoje (uma vez).
(function(){
 const box=$('daily');if(!window.Historico){box.hidden=true;return}
 const dias=Historico.ultimos(14),hoje=dias[dias.length-1],max=Math.max(1,...dias.map(d=>Math.max(d.a,d.e,d.f)));
 const fmt=d=>d.data.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'2-digit'});
 const reg=Historico.total(),antes=Math.max(0,done-reg.r),antesOk=Math.max(0,Math.min(antes,ok-reg.a)),ini=Historico.inicio();
 const cols=dias.map((d,i)=>{const partes=[];if(d.r)partes.push(`${plural(d.r,'questão','questões')} (${plural(d.a,'acerto','acertos')}, ${plural(d.e,'erro','erros')}${d.rf?`; ${d.rf} no Refazer erros`:''})`);if(d.f)partes.push(plural(d.f,'flashcard','flashcards'));
  const tip=`${fmt(d)}: ${partes.length?partes.join(' e '):'nada registrado'}`;
  return `<span class="col${d.r||d.f?'':' vazio'}${i===dias.length-1?' hoje':''}" title="${esc(tip)}" role="listitem" aria-label="${esc(tip)}">`+
   (d.r||d.f?[['a',d.a],['e',d.e],['f',d.f]].map(([c,n])=>`<i class="${c}" style="height:${n/max*100}%"></i>`).join(''):'')+`</span>`}).join('');
 const note=ini?`Registro desde ${ini.split('-').reverse().slice(0,2).join('/')}`:'O registro por dia começa com a próxima questão ou flashcard.';
 const extra=antes?`<button type="button" class="importar" id="importarSemData" title="${esc(`${plural(antes,'resposta','respostas')} do Caderno e dos Simulados foram dadas antes de existir o registro por dia e não têm data.`)}">Contar ${antes} sem data como hoje</button>`
  :`<span title="${esc(note)}">${esc(note.length>40?note.slice(0,38)+'…':note)}</span>`;
 box.innerHTML=`<p class="daily-top"><span id="dailyTitle"><span class="nw">Hoje: <b>${hoje.r}</b> ${hoje.r===1?'questão':'questões'} · <b class="ok">${hoje.a}</b> ${hoje.a===1?'acerto':'acertos'} · <b class="no">${hoje.e}</b> ${hoje.e===1?'erro':'erros'}</span>${hoje.rf?` <span class="nw">(${hoje.rf} no <a href="refazer.html">Refazer erros</a>)</span>`:''} <span class="nw">· <b class="fl">${hoje.f}</b> ${hoje.f===1?'flashcard':'flashcards'}</span></span></p>`+
  `<div class="cols" role="list" aria-label="Questões e flashcards por dia, últimos 14 dias">${cols}</div>`+
  `<div class="cols-x"><span>${dias[0].data.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'})}</span>`+
  `<span class="daily-key" aria-hidden="true"><span><i></i>Acertos</span><span><i class="e"></i>Erros</span><span><i class="f"></i>Flashcards</span></span><span>hoje</span></div>`+
  `<p class="daily-more">${extra}</p>`;
 const b=$('importarSemData');
 if(b)b.onclick=()=>{
  if(!confirm(`Contar como de hoje ${plural(antes,'resposta','respostas')} do Caderno e dos Simulados que não têm data (${plural(antesOk,'acerto','acertos')}, ${plural(antes-antesOk,'erro','erros')})?\n\nUse só se você respondeu essas questões hoje. Não dá para desfazer.`))return;
  Historico.importarSemData(antes,antesOk);location.reload()};
})();

const hint=(d,t,o)=>`${d} de ${t} respondidas`+(d?` · ${pct(o,d)}% de acerto`:'');
$('hLom').textContent=CAD;
$('lomHint').textContent=hint(lomDone,lomTotal,lomOk);
list($('lomRows'),lomSec.filter(s=>s.total).sort((a,b)=>a.name.localeCompare(b.name,'pt-BR')).map(row).join(''));

$('simHint').textContent=hint(simDone,simTotal,simOk);
list($('simRows'),fontes.map(s=>row({...s,sub:s.done?`${plural(s.ok,'acerto','acertos')} · ${plural(s.done-s.ok,'erro','erros')} · faltam ${s.total-s.done}`:'Ainda não iniciado'})).join(''));

const tried=areas.filter(a=>a.done).sort((a,b)=>pct(a.ok,a.done)-pct(b.ok,b.done)||b.done-a.done);
const later=areas.filter(a=>!a.done).sort((a,b)=>a.name.localeCompare(b.name,'pt-BR'));
list($('areaRows'),[...tried,...later].map(row).join(''));

// Edital verticalizado: a mesma janela e as mesmas marcações do caderno.
function renderEdital(){const o=Edital.overall();
 $('edHint').textContent=`${o.done} de ${o.total} tópicos estudados · ${o.pct}%`;
 $('edRows').innerHTML=Edital.disciplines().map(d=>{const full=d.total&&d.done===d.total;
  return `<button type="button" class="r${d.done?'':' idle'}${full?' full':''}" data-d="${d.id}" aria-label="${esc(d.title)}: ${d.done} de ${d.total} tópicos estudados">`+
  `<span class="r-name">${esc(d.title)}</span><span class="r-bar" aria-hidden="true"><i style="width:${d.total?d.done/d.total*100:0}%"></i></span>`+
  `<span class="r-n" aria-hidden="true">${d.done}/${d.total}</span><span class="r-s${full?' ok':''}" aria-hidden="true">${full?'✓':''}</span></button>`}).join('')}
renderEdital();Edital.subscribe(renderEdital);
$('openEdital2').onclick=()=>Edital.open();
$('edRows').onclick=e=>{const b=e.target.closest('[data-d]');if(b)Edital.open(b.dataset.d)};
if(location.hash==='#edital')Edital.open();
$('foot').textContent=`Mapa de questões atualizado em ${PAINEL.gerado.split('-').reverse().join('/')}. Os números vêm das respostas salvas neste navegador.`;
// Mantém o painel em dia: quando o Caderno, os Simulados ou os Flashcards (em outra aba) gravam
// respostas, recarrega o Início — na hora se ele estiver à vista, senão quando voltar a ficar.
// O edital se atualiza sozinho (Edital.subscribe); com uma janela aberta, espera ela fechar.
(function(){
 const CHAVES=['historico_diario','lom_st','simulados-respostas-v1','lom_fc','lom_fc_fora','lom_fc_cfg','refazer-v1'];
 let pendente=false;
 function atualizar(){
  if(document.hidden||document.querySelector('dialog[open]')){pendente=true;return}
  location.reload();
 }
 addEventListener('storage',e=>{if(e.key===null||CHAVES.includes(e.key))atualizar()});
 document.addEventListener('visibilitychange',()=>{if(!document.hidden&&pendente)atualizar()});
 document.addEventListener('close',()=>{if(pendente)atualizar()},true);
})();
