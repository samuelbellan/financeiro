// Caderno de questões (caderno.html): questões de uma lei só, com respostas, cortes e áreas.
// Questões em dados/caderno/atual.json (gerado por tools/gerar_dados.py a partir de conteudo/caderno.json);
// respostas no localStorage (lom_st, lom_cuts, lom_areas).
let Q=[];
const INFO=(await carregarJson('dados/painel.json')).lom.info||{};
const LEI_MD=INFO.arquivo,LEI_NOME=INFO.titulo||'Caderno',LEI_SIGLA=INFO.sigla||'';
// Balão da lei (js/texto-lei.js) com os artigos da questão, no lugar do texto do dispositivo copiado na página.
function lawArticleLabel(b){return 'Art. '+b.k}
function lawModalButton(q){if(!LEI_MD||!q.law.length)return '';return `<div class="law-buttons"><button type="button" class="fund" data-lom="1" title="Abrir o texto da lei em uma janela móvel">Ver na lei: ${esc(q.law.map(lawArticleLabel).join(', '))}</button></div>`}
function openLawModal(q,btn){if(LEI_MD)window.abrirTextoLei(LEI_MD,LEI_NOME,'',{title:LEI_NOME,article:q.ref})}
const L='ABCDE';
const $=id=>document.getElementById(id);
let st={};try{const saved=JSON.parse(localStorage.getItem('lom_st')||'{}');if(saved&&typeof saved==='object'&&!Array.isArray(saved))st=saved}catch(e){}
let cuts={};try{const saved=JSON.parse(localStorage.getItem('lom_cuts')||'{}');if(saved&&typeof saved==='object'&&!Array.isArray(saved))cuts=saved}catch(e){}
Q=await carregarJson('dados/caderno/atual.json');
const save=()=>{try{localStorage.setItem('lom_st',JSON.stringify(st))}catch(e){}};
const saveCuts=()=>{try{localStorage.setItem('lom_cuts',JSON.stringify(cuts))}catch(e){}};
let list=Q.map((_,i)=>i),pos=0,sel=null,showSol=false;
const esc=s=>s.replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
function secOf(q){return q.secao||''}
// Revisão sugerida pelo painel do início: ?revisar=1,5,9 mostra só essas questões.
const REV=new Set((new URLSearchParams(location.search).get('revisar')||'').split(',').filter(Boolean));
// Áreas escolhidas na janela "Desempenho por área" (vazio = todas). Somam com o filtro "Organizar".
let AREAS=new Set();try{const a=JSON.parse(localStorage.getItem('lom_areas')||'[]');if(Array.isArray(a))AREAS=new Set(a)}catch(e){}
let areaDraft=new Set(AREAS);
const areaOf=q=>secOf(q)||'Outros';
function saveAreas(){try{localStorage.setItem('lom_areas',JSON.stringify([...AREAS]))}catch(e){}}
function paintAreaBtn(){const b=$('areaBtn');if(!b)return;b.textContent=AREAS.size?(AREAS.size===1?[...AREAS][0]:AREAS.size+' áreas'):'por área';b.classList.toggle('area-on',AREAS.size>0);b.title=AREAS.size?'Estudando só: '+[...AREAS].join(', ')+'. Clique para mudar.':'Desempenho por área e escolha de áreas para estudar'}
function applyFilter(keep){const f=$('flt').value;list=Q.map((_,i)=>i).filter(i=>(f==='all'||(f==='rev'?REV.has(String(Q[i].n)):f==='todo'?!st[Q[i].n]:(st[Q[i].n]&&!st[Q[i].n].ok)))&&(!AREAS.size||AREAS.has(areaOf(Q[i]))));paintAreaBtn();if(!list.length){pos=0;renderEmpty();return}pos=Math.max(0,list.indexOf(keep));render()}
function renderEmpty(){sel=null;showSol=false;const f=$('flt').value,msg=(f==='err'?'Nenhuma questão errada neste filtro.':'Todas as questões deste filtro já foram respondidas.')+(AREAS.size?' Áreas selecionadas: '+[...AREAS].join(', ')+'.':'');$('qn').textContent='Nenhum resultado';$('pills').innerHTML='<span class="pill">Filtro ativo</span>';$('hist').textContent='Altere o filtro para continuar';$('qid').textContent='';$('stem').textContent=msg;$('studyLaw').innerHTML='';$('opts').innerHTML='';$('sol').innerHTML='';$('sol').classList.remove('show');$('tag').className='tag hide';$('ans').disabled=true;$('sbtn').disabled=true;$('sbtn').classList.remove('open');$('sbtn').textContent='Ver solução e comentários';$('pg').value=0;$('tot').textContent=0;['pp','np','pv','nx','b1','b2','b3','b4','b5','b6'].forEach(id=>$(id).disabled=true);if(globalThis.history)history.replaceState(null,'',location.pathname+location.search);stats();$('opts').parentNode.scrollTop=0}
function areaStrip(r){const e=r.answered-r.correct,f=r.total-r.answered;return `<div class="area-strip" role="img" aria-label="${r.correct} acertos, ${e} erros e ${f} não respondidas de ${r.total} questões">`+'<i class="hit"></i>'.repeat(r.correct)+'<i class="miss"></i>'.repeat(e)+'<i></i>'.repeat(f)+'</div>'}
function areaStats(){const rows=new Map();Q.forEach(q=>{const area=areaOf(q),r=rows.get(area)||{total:0,answered:0,correct:0};r.total++;const a=st[q.n];if(a){r.answered++;if(a.ok)r.correct++}rows.set(area,r)});$('areaSummary').innerHTML=[...rows].map(([area,r])=>{const errors=r.answered-r.correct,hit=r.answered?Math.round(r.correct/r.answered*100):0,miss=r.answered?100-hit:0;return `<div class="area-row${areaDraft.has(area)?' picked':''}"><div class="area-row-top"><label class="area-pick"><input type="checkbox" data-area="${esc(area)}"${areaDraft.has(area)?' checked':''}><span class="area-name">${esc(area)}</span></label><span class="area-count">${r.answered} de ${r.total} respondidas</span></div>${r.answered?`<div class="area-metrics"><span class="hit">${hit}% de acertos (${r.correct})</span><span class="miss">${miss}% de erros (${errors})</span></div>`:'<div class="area-empty">Nenhuma questão respondida nesta área.</div>'}${areaStrip(r)}</div>`}).join('');paintAreaFoot()}
function paintAreaFoot(){const n=areaDraft.size,c=Q.filter(q=>!n||areaDraft.has(areaOf(q))).length;$('areaPicked').textContent=n?`${n===1?'1 área selecionada':n+' áreas selecionadas'} · ${c} questões`:'Nenhuma área selecionada: todas as questões';$('areaClear').disabled=!n}
function stats(){const ids=new Set(Q.map(q=>q.n)),v=Object.entries(st).filter(([n])=>ids.has(+n)).map(([,x])=>x),c=v.filter(x=>x.ok).length;$('sA').textContent=v.length;$('sC').textContent=c;$('sE').textContent=v.length-c;$('sPct').textContent=(v.length?Math.round(c/v.length*100):0)+'% de acertos';areaStats()}
function render(){
 ['pp','np','pv','nx','b1','b2','b3','b4','b5','b6'].forEach(id=>$(id).disabled=false);$('sbtn').disabled=false;
 const q=Q[list[pos]],done=st[q.n];sel=done?done.c:null;showSol=false;
 $('qn').textContent='Questão '+q.n;
 $('pills').innerHTML=[LEI_SIGLA||LEI_NOME,q.level?q.level+(q.format?' · '+q.format:''):'',secOf(q)].filter(Boolean).map(t=>`<span class="pill">${esc(t)}</span>`).join('');
 $('hist').textContent=done?'Respondida '+(done.ok?'· acertou':'· errou'):'Ainda não respondida';
 $('qid').textContent=(LEI_SIGLA||'Q').replace(/\W/g,'')+String(q.n).padStart(4,'0');
 $('stem').textContent=q.stem;
 $('studyLaw').innerHTML=q.model?`<details class="study-law"><summary>Consultar dispositivos aplicáveis</summary><div class="study-law-content">${lawModalButton(q)}</div></details>`:'';
 $('opts').innerHTML=q.opts.map((o,i)=>`<div class="opt" data-i="${i}"><button type="button" class="cut" data-i="${i}" aria-label="Riscar alternativa ${L[i]}" aria-pressed="false" title="Riscar ou restaurar esta alternativa"><span aria-hidden="true">✂</span></button><div class="letter">${L[i]}</div><div class="otext">${esc(o)}</div></div>`).join('');
 document.querySelectorAll('.cut').forEach(btn=>btn.onclick=e=>{e.stopPropagation();if(st[q.n])return;const i=+btn.dataset.i,set=new Set(cuts[q.n]||[]);if(set.has(i))set.delete(i);else set.add(i);if(set.size)cuts[q.n]=[...set].sort();else delete cuts[q.n];if(set.has(i)&&sel===i)sel=null;saveCuts();paint()});
 document.querySelectorAll('.opt').forEach(el=>el.onclick=e=>{if(st[q.n]||e.target.closest('.cut'))return;const i=+el.dataset.i;if((cuts[q.n]||[]).includes(i))return;sel=i;paint();$('ans').disabled=false});
 $('ans').disabled=true;$('sol').classList.remove('show');$('sbtn').classList.remove('open');$('sbtn').textContent='Ver solução e comentários';
 paint();$('pg').value=pos+1;$('tot').textContent=list.length;
 $('b1').disabled=$('b2').disabled=pos===0;$('b4').disabled=$('b5').disabled=pos===list.length-1;
 stats();const hash='#'+q.n;if(location.hash!==hash&&globalThis.history)history.replaceState(null,'',hash);$('opts').parentNode.scrollTop=0}
function paint(){
 const q=Q[list[pos]],done=st[q.n];
 document.querySelectorAll('.opt').forEach(el=>{const i=+el.dataset.i,cut=(cuts[q.n]||[]).includes(i),btn=el.querySelector('.cut');el.className='opt';btn.disabled=!!done;btn.setAttribute('aria-pressed',String(!done&&cut));
  if(!done&&cut)el.classList.add('struck');
  if(done){el.classList.add('locked');if(i===q.a)el.classList.add('right');else if(i===done.c)el.classList.add('wrong')}
  else if(i===sel)el.classList.add('sel')});
 const t=$('tag');t.className='tag'+(done?(done.ok?'':' no'):' hide');t.textContent=done?(done.ok?'CERTA':'ERRADA'):'';
 $('ans').disabled=!!done||sel===null;
 if(done)$('hist').textContent='Respondida · '+(done.ok?'acertou':'errou')}
function answer(){const q=Q[list[pos]];if(sel===null||st[q.n])return;st[q.n]={c:sel,ok:sel===q.a,model:q.model||'original',dia:window.Historico?Historico.hoje():undefined};delete cuts[q.n];save();if(window.Historico)Historico.registrar(st[q.n].ok,'caderno');saveCuts();paint();stats();if(showSol)buildSol()}
function statementTexts(q){const out={},re=/(?:^|\n\n)(I|II|III|IV|V)\.\s+([\s\S]*?)(?=\n\n(?:I|II|III|IV|V)\.\s|\n\nTodas as conclusões corretas estão em:|$)/g;for(const m of q.stem.matchAll(re))out[m[1]]=m[2].trim();if(Object.keys(out).length)return out;const legacy=/(?:^|\n\n)(?:Caso|Situação|Órgão|Seção) (I|II)(?: do parecer)? —\s*([\s\S]*?)(?=\n\n(?:Caso|Situação|Órgão|Seção) (?:I|II)(?: do parecer)? —|\n\n(?:Qual|Quais|Classifique)|$)/g;for(const m of q.stem.matchAll(legacy))out[m[1]]=m[2].trim();return out}
// Negrito na diferença entre a afirmação errada e o texto da lei ("O texto da lei é outro: …").
// Compara palavra a palavra (sem acentos, maiúsculas e pontuação) pela maior subsequência comum:
// o que só existe na afirmação fica em negrito nela; o que só existe na lei fica em negrito na justificativa.
const DIF_LEI='O texto da lei é outro:';
function difTokens(t){return [...t.matchAll(/[\p{L}\p{N}ºª§%°]+(?:[\/,.-][\p{L}\p{N}]+)*/gu)].map(m=>({i:m.index,j:m.index+m[0].length,k:m[0].normalize('NFD').replace(/\p{M}/gu,'').toLowerCase()}))}
function difMark(text,toks,keep){let h='',at=0,run=null;const flush=()=>{if(run){h+=esc(text.slice(at,run[0]))+'<b class="dif">'+esc(text.slice(run[0],run[1]))+'</b>';at=run[1];run=null}};
 toks.forEach((t,n)=>{if(keep[n])flush();else run=run?[run[0],t.j]:[t.i,t.j]});flush();return h+esc(text.slice(at))}
function difBold(errada,certa){
 const a=difTokens(errada),b=difTokens(certa),n=a.length,m=b.length;
 if(!n||!m)return null;
 const L=Array.from({length:n+1},()=>new Uint16Array(m+1));
 for(let i=n-1;i>=0;i--)for(let j=m-1;j>=0;j--)L[i][j]=a[i].k===b[j].k?L[i+1][j+1]+1:Math.max(L[i+1][j],L[i][j+1]);
 const ka=new Array(n).fill(false),kb=new Array(m).fill(false);
 for(let i=0,j=0;i<n&&j<m;){if(a[i].k===b[j].k){ka[i]=kb[j]=true;i++;j++}else if(L[i+1][j]>=L[i][j+1])i++;else j++}
 // Paráfrase (a ordem das palavras mudou): compara só quais palavras existem em cada frase.
 if(L[0][0]<Math.min(n,m)*.2||L[0][0]<m*.2)return difPalavras(errada,certa,a,b);
 return {errada:difMark(errada,a,ka),certa:difMark(certa,b,kb)}}
// Sem olhar a ordem: fica em negrito a palavra que não aparece na outra frase. Singular e plural contam como
// iguais, e palavras de ligação nunca ficam em negrito sozinhas (só dentro de um trecho em negrito).
const DIF_LIGA=new Set('a o as os ao aos de do da dos das em no na nos nas e ou que se por pelo pela pelos pelas para com um uma uns umas sem sob ate entre'.split(' '));
function difPalavras(errada,certa,a,b){
 const raiz=k=>k.length>3?k.replace(/s$/,''):k,ra=new Set(a.map(t=>raiz(t.k))),rb=new Set(b.map(t=>raiz(t.k)));
 const manter=(toks,outra)=>{const k=toks.map(t=>DIF_LIGA.has(t.k)||outra.has(raiz(t.k)));
  // Palavra de ligação entre duas em negrito entra no trecho ("convocado com prioridade" fica inteiro).
  for(let i=1;i<k.length-1;i++)if(DIF_LIGA.has(toks[i].k)&&!k[i-1]&&!k[i+1])k[i]=false;return k};
 const ka=manter(a,rb),kb=manter(b,ra);
 const novas=kb.filter(x=>!x).length;
 if(!novas||novas>b.length*.85)return null; // nada a destacar, ou quase tudo: o negrito não ajudaria
 return {errada:difMark(errada,a,ka),certa:difMark(certa,b,kb)}}
// "…O texto da lei é outro: <lei> (Art. 27)." → texto antes, lei, referência final.
function difSplit(why){const at=why.indexOf(DIF_LEI);if(at<0)return null;const pre=why.slice(0,at+DIF_LEI.length),rest=why.slice(at+DIF_LEI.length),ref=rest.match(/\s*\((?:Art|ADCT)[^()]*\)\.?\s*$/);return {pre,lei:ref?rest.slice(0,ref.index):rest,ref:ref?ref[0]:''}}
function difPair(errada,why){const p=difSplit(why),d=p&&errada?difBold(errada,p.lei):null;
 return d?{errada:d.errada,why:esc(p.pre)+d.certa+esc(p.ref)}:{errada:esc(errada||''),why:esc(why)}}
function itemBreakdown(q){const source=q.x[q.a]||'',items=statementTexts(q),rows=[],re=/(I|II|III|IV|V) — (correta|incorreta)\.\s*([\s\S]*?)(?=\s+(?:I|II|III|IV|V) — (?:correta|incorreta)\.|$)/g;for(const m of source.matchAll(re))rows.push({id:m[1],ok:m[2]==='correta',why:m[3].trim()});if(rows.length<2){rows.length=0;const paired=/Caso (I|II) — a conclusão está (correta|incorreta)\.\s*([\s\S]*?)(?=\n\nCaso (?:I|II) —|$)/g;for(const m of source.matchAll(paired))rows.push({id:m[1],ok:m[2]==='correta',why:m[3].trim()})}if(rows.length<2){rows.length=0;const first=source.match(/A primeira (?:conclusão|orientação) (é válida|precisa ser revista):\s*([\s\S]*?)(?=\s+A segunda (?:conclusão|orientação) )/),second=source.match(/A segunda (?:conclusão|orientação) (é válida|precisa ser revista):\s*([\s\S]*)$/);if(first&&second){rows.push({id:'I',ok:first[1]==='é válida',why:first[2].trim()},{id:'II',ok:second[1]==='é válida',why:second[2].trim()})}}if(rows.length<2)return'';return '<div class="item-review">'+rows.map(r=>{const d=r.ok?{errada:esc(items[r.id]||''),why:esc(r.why)}:difPair(items[r.id],r.why);return `<div class="item-card ${r.ok?'ok':'no'}"><div class="item-head"><span>Item ${r.id} —</span><span class="item-status">${r.ok?'CORRETO':'ERRADO'}.</span></div>${items[r.id]?`<div class="item-statement">${d.errada}</div>`:''}<div class="item-why"><strong>Justificativa:</strong> ${d.why}</div></div>`}).join('')+'</div>'}
function shortAlternativeComment(q,i,hasItems){if(!hasItems)return q.x[i];const text=q.x[i],markers=[/\sI — (?:correta|incorreta)\./,/\n\nCaso I — a conclusão está (?:correta|incorreta)\./,/\sA primeira (?:conclusão|orientação) (?:é válida|precisa ser revista):/];for(const re of markers){const at=text.search(re);if(at>0)return text.slice(0,at).trim();if(at===0)return'Avalie esta combinação conforme a análise item a item apresentada acima.'}return text.trim()}
// Questões de assertivas: em vez de repetir "não confere com a análise" em cada alternativa, explica
// cada combinação a partir do resultado item a item (quais itens ela inclui ou deixa de fora).
// Devolve '' quando não consegue ler as combinações; aí fica a análise de sempre.
function comboReview(q,itemHtml,done){
 const certo={};for(const m of itemHtml.matchAll(/item-card (ok|no)"><div class="item-head"><span>Item (I|II|III|IV|V) —/g))certo[m[2]]=m[1]==='ok';
 const todos=Object.keys(certo);if(todos.length<2)return'';
 const ROM=['I','II','III','IV','V'],ordem=ids=>ROM.filter(r=>ids.includes(r));
 const lista=ids=>{const o=ordem(ids);return o.length>1?o.slice(0,-1).join(', ')+' e '+o[o.length-1]:o[0]};
 const ler=t=>{if(/\btodas\b/i.test(t))return todos.slice();if(/\bnenhuma\b/i.test(t))return [];const ids=[...t.matchAll(/\b(IV|V|III|II|I)\b/g)].map(m=>m[1]);return ids.length?[...new Set(ids)]:null};
 const combos=q.opts.map(ler);if(combos.some(c=>!c||c.some(id=>!(id in certo))))return'';
 const corretos=todos.filter(id=>certo[id]),igual=c=>c.length===corretos.length&&c.every(id=>certo[id]);
 if(!igual(combos[q.a]))return''; // gabarito e análise item a item não batem: melhor não inventar
 const linhas=q.opts.map((o,i)=>{const c=combos[i],ok=i===q.a,mine=done&&done.c===i;let why;
  if(ok)why=corretos.length===todos.length?'todas as assertivas estão corretas':corretos.length===1?`${corretos[0]} é a única correta`:corretos.length?`só ${lista(corretos)} estão corretas`:'nenhuma assertiva está correta';
  else{const extra=c.filter(id=>!certo[id]),falta=corretos.filter(id=>!c.includes(id)),p=[];
   if(extra.length)p.push(`inclui ${lista(extra)}, que ${extra.length>1?'estão erradas':'está errada'}`);
   if(falta.length)p.push(`deixa de fora ${lista(falta)}, que ${falta.length>1?'estão corretas':'está correta'}`);
   why=p.join(', e ')||'combinação diferente do gabarito'}
  return `<li class="cb ${ok?'ok':'no'}${mine?' mine':''}"><span class="cb-l">${L[i]}</span><span class="cb-t">${esc(o)}</span><span class="cb-w">${ok?'Gabarito — ':''}${esc(why)}${mine?' · sua resposta':''}</span></li>`}).join('');
 return `<h3>Alternativas</h3><ul class="cb-list">${linhas}</ul>`;
}
function buildSol(){
 const q=Q[list[pos]],done=st[q.n];
 const ref=LEI_NOME+(q.ref?' — '+q.ref:'');
 let h=`<div class="verdict"><span class="ans">Gabarito: ${L[q.a]}</span>${done?`<span class="res ${done.ok?'y':'n'}">${done.ok?'Você acertou!':'Você marcou '+L[done.c]+' — resposta incorreta'}</span>`:''}</div>
 <h3>Fundamento legal</h3><div class="small">${esc(ref)}</div>${lawModalButton(q)}`;
 const itemHtml=itemBreakdown(q);if(itemHtml)h+='<h3>Análise item a item</h3>'+itemHtml;
 const combo=itemHtml?comboReview(q,itemHtml,done):'';
 if(combo){h+=combo;$('sol').innerHTML=h;return}
 h+='<h3>Análise das alternativas</h3><div class="an">'+q.opts.map((o,i)=>{const ok=i===q.a,mine=done&&done.c===i,comment=shortAlternativeComment(q,i,!!itemHtml);return `<div class="ai ${ok?'ok':'no'}"><div class="ah"><span class="al">${L[i]}</span><span class="as">${ok?'Correta':'Incorreta'}${mine?' · sua resposta':''}</span></div>${(d=>`<div class="ao">${d.errada}</div><div class="ax">${d.why}</div>`)(comment.includes(DIF_LEI)?difPair(o,comment):{errada:esc(o),why:esc(comment)})}</div>`}).join('')+'</div>';
 $('sol').innerHTML=h}
document.addEventListener('click',e=>{const b=e.target.closest('[data-lom]');if(!b)return;const q=Q[list[pos]];if(q)openLawModal(q,b)});
function toggleSol(){showSol=!showSol;$('sol').classList.toggle('show',showSol);$('sbtn').classList.toggle('open',showSol);$('sbtn').textContent=showSol?'Ocultar solução e comentários':'Ver solução e comentários';if(showSol){buildSol();$('sol').scrollTop=0}}
const go=p=>{if(!list.length)return;pos=Math.min(list.length-1,Math.max(0,p));render();const hash='#'+Q[list[pos]].n;if(location.hash!==hash&&globalThis.history)history.replaceState(null,'',hash)};
$('ans').onclick=answer;$('sbtn').onclick=toggleSol;
$('pv').onclick=$('b2').onclick=$('pp').onclick=()=>go(pos-1);
$('nx').onclick=$('b4').onclick=$('np').onclick=()=>go(pos+1);
$('b6').onclick=()=>{const n=list.length;if(!n){alert('Não há questões neste filtro.');return}for(let k=1;k<=n;k++){const p=(pos+k)%n;if(!st[Q[list[p]].n]){go(p);return}}alert('Todas as questões já foram respondidas.')};
$('b1').onclick=()=>go(0);$('b5').onclick=()=>go(list.length-1);
$('b3').onclick=()=>{if(!list.length)return;const un=list.map((_,i)=>i).filter(i=>!st[Q[list[i]].n]&&i!==pos);const pool=un.length?un:list.map((_,i)=>i).filter(i=>i!==pos||list.length===1);go(pool[Math.floor(Math.random()*pool.length)])};
$('pg').onchange=e=>go((+e.target.value||1)-1);
$('flt').onchange=()=>applyFilter(list[pos]);
function useAreas(set){AREAS=new Set(set);saveAreas();areaDraft=new Set(AREAS);closeArea();applyFilter(list[pos])}
$('areaSummary').addEventListener('change',e=>{const c=e.target.closest('input[data-area]');if(!c)return;c.checked?areaDraft.add(c.dataset.area):areaDraft.delete(c.dataset.area);c.closest('.area-row').classList.toggle('picked',c.checked);paintAreaFoot()});
$('areaClear').onclick=()=>{areaDraft.clear();areaStats()};
$('areaGo').onclick=()=>useAreas(areaDraft);
const openArea=()=>{areaDraft=new Set(AREAS);areaStats();$('areaModal').hidden=false;$('areaClose').focus()},closeArea=()=>{$('areaModal').hidden=true;$('areaBtn').focus()};$('areaBtn').onclick=openArea;$('areaClose').onclick=closeArea;$('areaModal').onclick=e=>{if(e.target===e.currentTarget)closeArea()};
Edital.onLocalQuestions=()=>{$('flt').value='all';applyFilter(0)};
$('reset').onclick=()=>{if(confirm('Zerar todas as respostas?')){st={};cuts={};save();saveCuts();applyFilter(list[pos])}};
document.addEventListener('keydown',e=>{if(Edital.isOpen())return;if(!$('areaModal').hidden){if(e.key==='Escape'){e.preventDefault();closeArea()}return}if(!list.length||['INPUT','SELECT','BUTTON','SUMMARY','TEXTAREA','A'].includes(e.target.tagName)||e.target.isContentEditable)return;
 if(e.key==='ArrowRight')go(pos+1);else if(e.key==='ArrowLeft')go(pos-1);
 else if(/^[a-eA-E]$/.test(e.key)&&!st[Q[list[pos]].n]){const i=L.indexOf(e.key.toUpperCase());if(!(cuts[Q[list[pos]].n]||[]).includes(i)){sel=i;paint()}}
 else if(e.key==='Enter')answer()});
const openHash=()=>{const h=+(location.hash.slice(1)),i=Q.findIndex(q=>q.n===h);if(i>=0){$('flt').value='all';applyFilter(i)}};window.addEventListener('hashchange',openHash);if(REV.size){$('flt').querySelector('[value=rev]').hidden=false;$('flt').value='rev'}const h=+(location.hash.slice(1));applyFilter(Math.max(0,Q.findIndex(q=>q.n===h)));
