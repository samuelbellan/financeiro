// "O que revisar": só os erros, agrupados pelo tópico (norma + assunto), com o texto da lei a reler.
// Inclua depois de carregar.js e coloque <section id="revisar"></section> na página.
//
// Simulados: o tópico vem do campo "tema" da questão (4º item de PAINEL.simulados.q); o texto da lei de cada
// questão, de dados/revisar.json (gerado por tools/gerar_dados.py a partir de leis/*.md). Sem lei
// (Português, Informática, Contabilidade...), mostra a regra do comentário. Caderno: um tópico
// por seção, com o texto dos artigos das questões erradas.
(async function(){
const box=document.getElementById('revisar');
if(!box)return;
const PAINEL=await carregarJson('dados/painel.json');
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const read=key=>{try{const v=JSON.parse(localStorage.getItem(key)||'{}');return v&&typeof v==='object'&&!Array.isArray(v)?v:{}}catch(_){return {}}};

document.head.insertAdjacentHTML('beforeend',`<style>
#revisar{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:.75rem 1.125rem .875rem}
#revisar .rv-head,.rv-dlg .rv-dh{display:flex;align-items:baseline;gap:.5rem 1rem;flex-wrap:wrap}
#revisar h2{font-size:1rem;margin:0}
#revisar .rv-tot{color:var(--mut);font-size:.8125rem;flex:1}
#revisar .rv-tot b{color:#c0392b}
#revisar .rv-all{border:0;background:none;padding:2px 0;font:inherit;font-size:.8125rem;font-weight:600;color:var(--pri);cursor:pointer}
#revisar .rv-all:hover{text-decoration:underline}
#revisar a:focus-visible,#revisar .rv-all:focus-visible,.rv-dlg a:focus-visible,.rv-dlg button:focus-visible{outline:3px solid var(--pri);outline-offset:2px;border-radius:4px}
.rv-empty{color:var(--mut);font-size:.8125rem;margin:.375rem 0 0}
/* Uma linha por tópico: erros · tópico · questões. */
.rv-grid{display:grid;grid-template-columns:1fr 1fr;grid-auto-flow:column;column-gap:2rem;margin:.5rem 0 0;padding:0;list-style:none}
.rv-t{display:grid;grid-template-columns:minmax(0,12rem) minmax(4rem,1fr) 2rem;align-items:baseline;column-gap:.5rem;min-height:1.625rem;width:100%;border:0;background:none;padding:0 .25rem;border-radius:5px;font:inherit;font-size:.8125rem;color:inherit;text-align:left;cursor:pointer}
.rv-t:hover{background:var(--pri-l,#eee)}
.rv-t:focus-visible{outline:3px solid var(--pri);outline-offset:1px}
.rv-n{font-weight:700;color:#c0392b;text-align:right;font-variant-numeric:tabular-nums}
.rv-name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rv-bar{height:.625rem;align-self:center}
.rv-bar i{display:block;height:100%;min-width:3px;border-radius:3px;background:#c0392b}
.rv-t:hover .rv-bar i{filter:brightness(.9)}
.rv-more{margin:.375rem 0 0;font-size:.75rem;color:var(--mut)}

.rv-name b{font-weight:700}
/* Janela com tudo, agrupado por norma. */
dialog.rv-dlg{border:0;border-radius:12px;padding:0;width:min(820px,calc(100vw - 32px));max-height:min(86vh,860px);box-shadow:0 14px 44px #0005;color:var(--tx);font:15px/1.4 Inter,"Segoe UI",system-ui,sans-serif}
dialog.rv-dlg::backdrop{background:#17132a99}
.rv-dlg .rv-dh{position:sticky;top:0;z-index:1;background:var(--card);border-bottom:1px solid var(--line);padding:.875rem 1.25rem .75rem}
.rv-dlg .rv-dh h2{margin:0;font-size:1.0625rem;flex:1}
.rv-dlg .rv-x{border:0;background:var(--bg);border-radius:7px;width:2rem;height:2rem;font-size:1.25rem;line-height:1;cursor:pointer;flex:none}
.rv-list{margin:0;padding:.25rem 1.25rem 1rem}
.rv-list h3{font-size:.875rem;margin:0;display:flex;gap:.5rem;align-items:baseline}
.rv-list h3 span{color:#c0392b;font-size:.8125rem}
.rv-list ul{margin:0;padding:0;list-style:none}
.rv-list li{padding:.375rem 0;border-top:1px solid var(--line);font-size:.8125rem;scroll-margin-top:4.5rem}
.rv-list summary{cursor:pointer;list-style:none;display:flex;align-items:baseline;gap:.5rem}
.rv-list summary::-webkit-details-marker{display:none}
.rv-list summary::before{content:'▸';color:var(--mut);font-size:.75rem;width:.75rem;flex:none;transition:transform .15s}
.rv-list details[open]>summary::before{transform:rotate(90deg)}
.rv-list summary:hover h3,.rv-list summary:hover h4{color:var(--pri)}
.rv-list summary:focus-visible{outline:3px solid var(--pri);outline-offset:2px;border-radius:4px}
.rv-area{border-top:1px solid var(--line);scroll-margin-top:4.5rem}
.rv-area:first-child{border-top:0}
.rv-area>summary{padding:.5rem 0}
.rv-area>ul{margin:0 0 .5rem 1.25rem}
.rv-top .rv-n{width:1.75rem;flex:none}
.rv-body{margin-left:2.5rem}
.rv-dh .rv-tg{border:0;background:none;padding:2px 0;font:inherit;font-size:.8125rem;font-weight:600;color:var(--pri);cursor:pointer}
.rv-dh .rv-tg:hover{text-decoration:underline}
@media(prefers-reduced-motion:reduce){.rv-list summary::before{transition:none}}
.rv-list h4{margin:0;font-size:.8125rem}
.rv-lei{margin:.25rem 0 .5rem;padding:.375rem .625rem;border-left:3px solid var(--pri);background:var(--bg);border-radius:0 6px 6px 0}
.rv-lei b{display:block;font-size:.75rem;color:var(--pri);margin-bottom:.125rem}
.rv-lei p{margin:.125rem 0;line-height:1.45}
.rv-lei p+p{padding-left:.75rem}
.rv-its{margin:0 0 .5rem;padding:0;list-style:none}
.rv-its li{padding:.125rem 0;line-height:1.45}
.rv-its a{font-weight:600;color:var(--pri)}
.rv-alt{margin:.125rem 0 0 .75rem;padding-left:.5rem;border-left:3px solid var(--line)}
.rv-x1{border-color:#c0392b}
.rv-ok{border-color:#2e8b57}
.rv-m{color:var(--mut);font-size:.75rem;margin-left:.375rem}
.rv-regra{margin:.25rem 0;line-height:1.45}
.rv-list li:first-child{border-top:0}
@media(max-width:1180px){.rv-grid{grid-template-columns:1fr;grid-auto-flow:row}}
@media(max-width:760px){.rv-t{grid-template-columns:minmax(0,1fr) 4.5rem 1.75rem}}
</style>`);

// Tópico = "Norma — assunto (artigos)". Agrupa e conta só as erradas.
const topics=new Map();
function add(key,norma,assunto,blocos,regra,item){
 const t=topics.get(key)||{norma,assunto,n:0,blocos:new Set(),regras:new Set(),itens:[]};
 t.n++;if(item)t.itens.push(item);for(const b of blocos)t.blocos.add(b);if(!blocos.length&&regra)t.regras.add(regra);
 topics.set(key,t);
}
function split(tema){const i=tema.indexOf(' — ');return i<0?[tema,'']:[tema.slice(0,i),tema.slice(i+3)]}

// ---------- simulados ----------
const refeito=read('refazer-v1'); // questões já dominadas em "Refazer erros" (3 acertos seguidos) saem da lista
const dominada=k=>{const e=refeito[k];return !!e&&e.box>=3};
const simAns=read('simulados-respostas-v1');
const sim=PAINEL.simulados,L='ABCDE',temas=sim.temas||[];
const lomAns=read('lom_st'),lom=PAINEL.lom,lomArts=lom.arts||{};
// O texto das leis (dados/revisar.json, ~575 KB) só é baixado se houver algum erro a revisar.
const temErro=Object.entries(simAns).some(([id,a])=>a&&sim.q[id]&&!(a.ok===true||L[a.c]===a.checked))||Object.entries(lomAns).some(([n,a])=>a&&n in lom.q&&!a.ok);
const R=temErro?await carregarJson('dados/revisar.json'):{blocos:[],q:{},lom:{}};
for(const [id,a] of Object.entries(simAns)){
 const m=sim.q[id];if(!m||!a)continue;
 if(a.ok===true||L[a.c]===a.checked||dominada('s:'+id))continue;
 const tema=temas[m[3]]||sim.areas[m[1]];
 const [norma,assunto]=split(tema);
 const rq=R.q[id]||{b:[],g:''};
 const fonte=(sim.fontes[m[0]]||'').split(' · ')[0],num=id.replace(/^\D*\d*-?/,'')||id;
 add('s:'+tema,norma,assunto,rq.b,rq.g,{href:'simulados.html#q='+id,rot:`${fonte} · questão ${+num||num}`,
  onde:rq.b.map(b=>(R.blocos[b]||[''])[0]).filter(Boolean),marcou:L[a.c],certa:a.checked,txtM:(rq.o||[])[a.c],txtC:(rq.o||[])[L.indexOf(a.checked)],g:rq.g,dia:a.dia});
}

// ---------- caderno (lei de conteudo/caderno.json) ----------
const CAD_SIGLA=(lom.info&&lom.info.sigla)||'Caderno',CAD_NOME=(lom.info&&lom.info.titulo)||'Caderno';
const bySec=new Map();
for(const [n,a] of Object.entries(lomAns)){
 if(!(n in lom.q)||!a||a.ok||dominada('c:'+n))continue;
 const s=lom.q[n],t=bySec.get(s)||{nums:[],arts:new Map()};
 t.nums.push(+n);for(const art of lomArts[n]||[])t.arts.set(art,(t.arts.get(art)||0)+1);
 bySec.set(s,t);
}
const artKey=a=>{const m=a.match(/(\d+)/);return (a.startsWith('ADCT')?1000:0)+(m?+m[1]:0)};
for(const [s,t] of bySec){
 t.nums.sort((a,b)=>a-b);
 const arts=[...t.arts.keys()].sort((a,b)=>artKey(a)-artKey(b));
 const assunto=lom.secoes[s]+(arts.length?` (${arts.length>1?'arts.':'art.'} ${arts.join(', ')})`:'');
 const bl=arts.map(a=>R.lom[a]).filter(b=>b!==undefined);
 for(const n of t.nums){const a=lomAns[n];add('c:'+s,CAD_SIGLA,assunto,bl,'',{href:'caderno.html#'+n,rot:'Caderno ('+CAD_NOME+') · questão '+n,
  onde:(lomArts[n]||[]).map(x=>CAD_SIGLA+', '+(/^\d/.test(x)?'art. ':'')+x),marcou:'ABCDE'[a.c]||'',certa:'',dia:a.dia})}
}

// Ordem: mais erros no tópico; empate → norma com mais erros no total.
const porN=new Map();for(const t of topics.values())porN.set(t.norma,(porN.get(t.norma)||0)+t.n);
const list=[...topics.values()].sort((a,b)=>b.n-a.n||porN.get(b.norma)-porN.get(a.norma)||a.norma.localeCompare(b.norma,'pt-BR')||a.assunto.localeCompare(b.assunto,'pt-BR'));
const total=list.reduce((s,t)=>s+t.n,0);
const plural=(n,um,varios)=>`${n} ${n===1?um:varios}`;
// Texto de lei: rótulo em negrito e cada dispositivo (caput, parágrafo, inciso, alínea) em linha própria.
const lei=b=>{const [rot,txt]=R.blocos[b]||['',''];return `<div class="rv-lei"><b>${esc(rot)}</b>${txt.split('\n').map(l=>`<p>${esc(l)}</p>`).join('')}</div>`};
const dataBr=d=>/^\d{4}-\d\d-\d\d$/.test(d||'')?d.split('-').reverse().join('/'):'';
const itens=t=>`<ul class="rv-its">`+t.itens.map(i=>`<li><a href="${esc(i.href)}">${esc(i.rot)}</a>`+
 (i.onde.length?` — <b>Reler:</b> ${i.onde.map(esc).join('; ')}`:' — <b>Reler:</b> '+esc(t.assunto||t.norma))+
 (i.marcou?` <span class="rv-m">marcou ${esc(i.marcou)}${i.certa?', certa '+esc(i.certa):''}</span>`:'')+
 (dataBr(i.dia)?` <span class="rv-m">errou em ${dataBr(i.dia)}</span>`:'')+
 (i.txtM?`<div class="rv-alt rv-x1"><b>Você marcou ${esc(i.marcou)}:</b> ${esc(i.txtM)}</div>`:'')+
 (i.txtC?`<div class="rv-alt rv-ok"><b>Certa ${esc(i.certa)}:</b> ${esc(i.txtC)}</div>`:'')+
 (i.g?`<div class="rv-alt"><b>Por quê:</b> ${esc(i.g)}</div>`:'')+`</li>`).join('')+`</ul>`;
const corpo=t=>itens(t)+[...t.blocos].map(lei).join('')+[...t.regras].map(g=>`<p class="rv-regra">${esc(g)}</p>`).join('');
const name=t=>`<b>${esc(t.norma)}</b>${t.assunto?' — '+esc(t.assunto):''}`;

if(!list.length){
 box.innerHTML=`<div class="rv-head"><h2>O que revisar</h2></div><p class="rv-empty">Nenhum erro.</p>`;
 return;
}
// Quadro: gráfico de barras por área (norma), só com os erros; a barra mais longa é a área com mais erros.
const porNorma=new Map();
for(const t of list){const g=porNorma.get(t.norma)||[];g.push(t);porNorma.set(t.norma,g)}
const grupos=[...porNorma].map(([n,ts])=>({n,ts,err:ts.reduce((s,t)=>s+t.n,0)})).sort((a,b)=>b.err-a.err||a.n.localeCompare(b.n,'pt-BR'));
const MAX=10,shown=grupos.slice(0,MAX),topo=grupos[0].err;
box.setAttribute('aria-labelledby','rvTitle');
box.innerHTML=`<div class="rv-head"><h2 id="rvTitle">O que revisar</h2>`+
 `<span class="rv-tot"><b>${plural(total,'erro','erros')}</b></span>`+
 `<a class="rv-all" href="refazer.html">Refazer estes erros</a><button type="button" class="rv-all" id="rvAll" aria-haspopup="dialog">Ver o texto da lei</button></div>`+
 `<ul class="rv-grid" style="grid-template-rows:repeat(${Math.ceil(shown.length/2)},auto)">`+
 shown.map((g,i)=>`<li><button type="button" class="rv-t" data-g="${i}" title="${esc(g.n)}: ${plural(g.err,'erro','erros')} em ${plural(g.ts.length,'tópico','tópicos')}">`+
  `<span class="rv-name"><b>${esc(g.n)}</b></span><span class="rv-bar" aria-hidden="true"><i style="width:${g.err/topo*100}%"></i></span>`+
  `<span class="rv-n">${g.err}</span></button></li>`).join('')+`</ul>`+
 (grupos.length>MAX?`<p class="rv-more">+ ${grupos.length-MAX} áreas em "Ver o texto da lei"</p>`:'');

// Janela: todas as áreas, cada tópico com o texto da lei.
const dlg=document.createElement('dialog');
dlg.className='rv-dlg';dlg.setAttribute('aria-labelledby','rvDlgTitle');
// Áreas e tópicos são recolhíveis (<details>): a área clicada no gráfico abre já expandida.
dlg.innerHTML=`<div class="rv-dh"><h2 id="rvDlgTitle">O que revisar — ${plural(total,'erro','erros')}</h2>`+
 `<button type="button" class="rv-tg" data-v="1">Expandir tudo</button><button type="button" class="rv-tg" data-v="0">Recolher tudo</button>`+
 `<button type="button" class="rv-x" aria-label="Fechar">×</button></div>`+
 `<div class="rv-list">`+grupos.map((g,gi)=>`<details class="rv-area" id="rvG${gi}"><summary><h3>${esc(g.n)} <span>${plural(g.err,'erro','erros')}</span></h3></summary><ul>`+
  g.ts.map(t=>`<li id="rvT${list.indexOf(t)}"><details class="rv-top" open><summary><span class="rv-n">${t.n}×</span><h4>${esc(t.assunto||t.norma)}</h4></summary><div class="rv-body">${corpo(t)}</div></details></li>`).join('')+`</ul></details>`).join('')+`</div>`;
document.body.appendChild(dlg);
dlg.querySelector('.rv-x').onclick=()=>dlg.close();
// Fecha só com clique fora da janela (no fundo escuro). A barra de rolagem também tem a janela como alvo
// do clique, por isso a conferência é pela posição, não pelo e.target.
dlg.addEventListener('click',e=>{const r=dlg.getBoundingClientRect();
 if(e.target===dlg&&(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom))dlg.close()});
const areas=[...dlg.querySelectorAll('.rv-area')];
dlg.querySelectorAll('.rv-tg').forEach(b=>b.onclick=()=>{const v=b.dataset.v==='1';dlg.querySelectorAll('details').forEach(d=>d.open=v)});
document.getElementById('rvAll').onclick=()=>{areas.forEach(d=>d.open=false);dlg.showModal();dlg.scrollTop=0};
box.querySelectorAll('.rv-t').forEach(b=>b.onclick=()=>{const el=document.getElementById('rvG'+b.dataset.g);areas.forEach(d=>d.open=d===el);dlg.showModal();if(el)el.scrollIntoView({block:'start'})});
})().catch(e=>console.error("revisar.js:",e));
