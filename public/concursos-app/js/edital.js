// Edital verticalizado — dados, progresso e janela únicos, usados pela página inicial e pelo caderno.
// As marcações ficam em localStorage 'lom_edital' ({ '<disciplina>-<tópico>': 1 }) e são
// sincronizadas entre abas abertas pelo evento 'storage'.
(function(){
const KEY='lom_edital';
const CADERNO='caderno.html';
// O edital vem de js/config.js (CONFIG.edital): grupos → disciplinas → tópicos.
const EDITAL=((window.CONFIG&&CONFIG.edital)||[]).map(g=>({group:g.grupo,disciplines:(g.disciplinas||[]).map(d=>({id:d.id,title:d.titulo,topics:d.topicos||[],bank:d.banco||'',sources:d.fontes||[],localQuestions:!!d.caderno}))}));

const DISC=EDITAL.flatMap(g=>g.disciplines.map(d=>({...d,group:g.group})));
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const norm=s=>s.normalize('NFD').replace(/[̀-ͯ]/g,'').toLocaleLowerCase('pt-BR');
const readObj=k=>{try{const v=JSON.parse(localStorage.getItem(k)||'{}');return v&&typeof v==='object'&&!Array.isArray(v)?v:{}}catch(_){return {}}};
const write=(k,v)=>{try{localStorage.setItem(k,typeof v==='string'?v:JSON.stringify(v))}catch(_){}};

let state=readObj(KEY);


const listeners=new Set();
const notify=()=>listeners.forEach(fn=>{try{fn()}catch(_){}});
const key=(d,i)=>d.id+'-'+i;
function counts(d){const done=d.topics.reduce((n,_,i)=>n+(state[key(d,i)]?1:0),0);return {done,total:d.topics.length,pct:d.topics.length?Math.round(done/d.topics.length*100):0}}
function overall(){let done=0,total=0;DISC.forEach(d=>{const c=counts(d);done+=c.done;total+=c.total});return {done,total,pct:total?Math.round(done/total*100):0}}
function setTopic(k,on){if(on)state[k]=1;else delete state[k];write(KEY,state);refresh();notify()}
// Uma célula por tópico: mostra o tamanho da disciplina e o que falta.
const strip=d=>`<span class="ev-strip" aria-hidden="true">${d.topics.map((_,i)=>`<i${state[key(d,i)]?' class="on"':''}></i>`).join('')}</span>`;

let dlg=null,ui={term:'',filter:'all'},opener=null;
function build(){
 dlg=document.createElement('dialog');dlg.className='ev';dlg.setAttribute('aria-labelledby','evTitle');
 dlg.innerHTML=`<header class="ev-head">
  <div class="ev-heading"><h2 id="evTitle">Edital verticalizado</h2><p id="evSum"></p></div>
  <div class="ev-meter" aria-hidden="true"><b id="evPct">0%</b><span class="ev-bar"><span id="evBar"></span></span></div>
  <button class="ev-x" type="button" aria-label="Fechar o edital">×</button></header>
 <div class="ev-tools">
  <input class="ev-search" id="evSearch" type="search" placeholder="Buscar disciplina ou tópico" aria-label="Buscar no edital" autocomplete="off">
  <div class="ev-seg" role="radiogroup" aria-label="Mostrar">
   <label><input type="radio" name="evFilter" value="all" checked><span>Todos</span></label>
   <label><input type="radio" name="evFilter" value="todo"><span>A estudar</span></label>
   <label><input type="radio" name="evFilter" value="done"><span>Estudados</span></label>
  </div></div>
 <div class="ev-main"><nav class="ev-index" id="evIndex" aria-label="Disciplinas"></nav><div class="ev-list" id="evList" tabindex="-1"></div></div>
 <footer class="ev-foot"><span>As marcações ficam salvas neste navegador e aparecem igual no caderno e na página inicial.</span><button type="button" class="ev-clear" id="evClear">Desmarcar tudo</button></footer>`;
 document.body.appendChild(dlg);
 const $=s=>dlg.querySelector(s);
 $('.ev-x').onclick=close;
 dlg.addEventListener('click',e=>{if(e.target===dlg)close()});
 dlg.addEventListener('close',()=>{document.documentElement.classList.remove('ev-lock');if(opener?.isConnected)opener.focus()});
 $('#evSearch').oninput=e=>{ui.term=norm(e.target.value.trim());refresh()};
 dlg.querySelectorAll('input[name=evFilter]').forEach(r=>r.onchange=()=>{ui.filter=r.value;refresh()});
 $('#evClear').onclick=()=>{if(confirm('Desmarcar todos os tópicos do edital?')){state={};write(KEY,state);refresh();notify()}};
 $('#evList').addEventListener('change',e=>{const c=e.target.closest('input[data-k]');if(c)setTopic(c.dataset.k,c.checked)});
 $('#evIndex').addEventListener('click',e=>{const b=e.target.closest('button[data-d]');if(b)jump(b.dataset.d)});
 $('#evList').addEventListener('click',e=>{const a=e.target.closest('[data-local]');if(!a||!api.onLocalQuestions)return;e.preventDefault();close();api.onLocalQuestions()});
}
function refresh(){
 if(!dlg)return;
 const o=overall(),$=s=>dlg.querySelector(s);
 $('#evSum').textContent=`${o.done} de ${o.total} tópicos estudados · faltam ${o.total-o.done}`;
 $('#evPct').textContent=o.pct+'%';$('#evBar').style.width=o.pct+'%';
 let idx='',list='',shown=0,lastGroup='';
 DISC.forEach(d=>{
  const c=counts(d),titleHit=!ui.term||norm(d.title).includes(ui.term);
  const topics=d.topics.map((t,i)=>({t,i,k:key(d,i),on:!!state[key(d,i)]})).filter(x=>(titleHit||norm(x.t).includes(ui.term))&&(ui.filter==='all'||(ui.filter==='done')===x.on));
  if(d.group!==lastGroup){idx+=`<p class="ev-group">${esc(d.group)}</p>`;lastGroup=d.group}
  idx+=`<button type="button" data-d="${d.id}"${topics.length?'':' disabled'}><span class="ev-name">${esc(d.title)}</span><span class="ev-count">${c.done}/${c.total}</span>${strip(d)}</button>`;
  if(!topics.length)return;shown+=topics.length;
  const links=[d.localQuestions?`<a href="${CADERNO}" data-local>Praticar no caderno</a>`:'',d.bank?`<a href="${d.bank}" target="_blank" rel="noopener noreferrer">Banco de questões ↗</a>`:'',...(d.sources||[]).map(s=>`<a href="${s[1]}" target="_blank" rel="noopener noreferrer">${esc(s[0])} ↗</a>`)].filter(Boolean).join('');
  list+=`<section class="ev-disc" id="ev-${d.id}" aria-labelledby="ev-h-${d.id}"><div class="ev-disc-head"><h3 id="ev-h-${d.id}">${esc(d.title)}</h3><span class="ev-count">${c.done} de ${c.total}${c.done===c.total?' · concluída':''}</span></div><div class="ev-links">${links}</div><ul class="ev-topics">${topics.map(x=>`<li><label class="ev-topic${x.on?' on':''}"><input type="checkbox" data-k="${x.k}"${x.on?' checked':''}><span>${esc(x.t)}</span></label></li>`).join('')}</ul></section>`;
 });
 $('#evIndex').innerHTML=idx;
 $('#evList').innerHTML=list||`<div class="ev-empty"><b>Nenhum tópico encontrado.</b><span>${ui.filter==='done'?'Você ainda não marcou tópicos que correspondam à busca.':'Mude a busca ou escolha “Todos”.'}</span></div>`;
}
function jump(id){const s=dlg.querySelector('#ev-'+id);if(!s)return;s.scrollIntoView({block:'start',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});const first=s.querySelector('input');if(first)first.focus({preventScroll:true})}
function open(id){
 if(!dlg)build();
 opener=document.activeElement;refresh();
 document.documentElement.classList.add('ev-lock');
 if(!dlg.open)dlg.showModal();
 if(id){const s=dlg.querySelector('#ev-'+id);if(s){dlg.querySelector('#evList').scrollTop=s.offsetTop-dlg.querySelector('#evList').offsetTop;s.querySelector('input')?.focus({preventScroll:true});return}}
 dlg.querySelector('#evSearch').focus();
}
function close(){if(dlg?.open)dlg.close()}

addEventListener('storage',e=>{if(e.key!==KEY&&e.key!==null)return;state=readObj(KEY);refresh();notify()});

const api={
 open,close,isOpen:()=>!!dlg?.open,
 overall,
 strip:d=>strip(DISC.find(x=>x.id===(d.id||d))),
 disciplines:()=>DISC.map(d=>({id:d.id,title:d.title,group:d.group,...counts(d)})),
 subscribe(fn){listeners.add(fn);return ()=>listeners.delete(fn)},
 onLocalQuestions:null
};
window.Edital=api;
})();
