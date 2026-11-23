// Balão móvel com o texto local da lei (leis/*.md) citado no comentário da questão.
// Mostra só os artigos mencionados no comentário; "Lei inteira" carrega o texto todo.
(function(){
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const cache={};
let box;

const norm=n=>n.replace(/(\d)o\b/g,'$1').replace(/[.º°ª\s]/g,'').toLowerCase(); // "2o" (LC 116) = "2º"

// Artigos citados no comentário, com o recorte pedido ("Art. 50, IV e § 3º" → inciso IV do caput e § 3º).
// Ignora os de outra lei. Retorna {chave do artigo: seletores | null}; null = artigo inteiro.
const ROM={I:1,V:5,X:10,L:50};
const romInt=r=>[...r].reduce((t,c,i,a)=>t+(i+1<a.length&&ROM[c]<ROM[a[i+1]]?-ROM[c]:ROM[c]),0);
const intRom=n=>{let o='';for(const [v,c] of [[50,'L'],[40,'XL'],[10,'X'],[9,'IX'],[5,'V'],[4,'IV'],[1,'I']])while(n>=v){o+=c;n-=v}return o};
const parKey=x=>{const m=x.match(/(\d+)[º°]?(-[A-Z])?/);return m[1]+(m[2]||'').toLowerCase()};
const TOK=/\s*[,;]?\s*(?:\b(?:e|ou)\b\s*)?(?:(caput)|([Pp]ar[áa]grafo [úu]nico)|(§§?\s*\d+[º°]?(?:-[A-Z])?(?:\s*(?:,|\be\b|\ba\b)\s*\d+[º°]?(?:-[A-Z])?)*)|(?:incisos?\s+)?([IVXL]+(?:-[A-Z])?)(?:\s+a\s+([IVXL]+))?(?![\wá-ú])|(?:al[ií]neas?\s+)?[“"']([a-z])[”"']|al[ií]neas?\s+([a-z])\b|,\s*([a-z])(?=\s*(?:[,;]|$)))/y;

function recorte(spec,de=0){
  let par=['caput'],sels=[],ult=[],fim=de;
  TOK.lastIndex=de;
  for(let m;(m=TOK.exec(spec))&&m[0];){
    fim=TOK.lastIndex;
    if(m[1]){par=['caput'];sels.push({par:'caput',inteiro:true});ult=[]}
    else if(m[2]){par=['u'];sels.push({par:'u',inteiro:true});ult=[]}
    else if(m[3]){par=m[3].replace(/^§+/,'').split(/\s*(?:,|\be\b|\ba\b)\s*/).map(parKey);for(const p of par)sels.push({par:p,inteiro:true});ult=[]}
    else if(m[4]){
      const ini=m[4],lista=m[5]?Array.from({length:romInt(m[5])-romInt(ini)+1},(_,i)=>intRom(romInt(ini)+i)):[ini];
      sels=sels.filter(x=>!(x.inteiro&&par.includes(x.par)&&x.par!=='caput'));
      ult=[];for(const inc of lista)for(const p of par){const x={par:p,inc,ali:[]};sels.push(x);ult.push(x)}
    }else{const l=m[6]||m[7]||m[8];for(const x of ult)x.ali.push(l)}
  }
  return {sels:sels.length?sels:null,fim};
}

// "(inciso VIII)", "incisos I e III", "inciso II do § 2º" ditos depois do artigo, no restante do comentário.
function extras(regiao){
  const out=[],re=/\b[Ii]ncisos?\s+([IVXL]+(?:-[A-Z])?(?:\s*(?:,|\be\b|\ba\b)\s*[IVXL]+(?:-[A-Z])?)*)(?:\s+do\s+§\s*(\d+[º°]?(?:-[A-Z])?))?/g;
  for(let m;(m=re.exec(regiao));){
    const par=m[2]?parKey(m[2]):'caput',partes=m[1].split(/(\s*,\s*|\s+e\s+|\s+a\s+)/);
    for(let i=0;i<partes.length;i+=2){
      const ini=partes[i],fim=/^\s+a\s+$/.test(partes[i-1]||'')?ini:null;
      if(fim){const ant=partes[i-2];for(let n=romInt(ant)+1;n<=romInt(ini);n++)out.push({par,inc:intRom(n),ali:[]})}
      else out.push({par,inc:ini,ali:[]});
    }
  }
  return out;
}

// Como cada lei aparece nos comentários, para saber de qual lei é o "Art. N" citado.
const LEIS={
  cf:[/Constitui[çc]|\bCF\b/,'constituicao-federal'],
  ctn:[/\bCTN\b|5\.172|C[óo]digo Tribut/,'-ctn'],
  lrf:[/\bLRF\b|LC\s*(?:n[º°.]?\s*)?101|Complementar\s*(?:n[º°.]?\s*)?101|Responsabilidade Fiscal/,'-lrf'],
  lai:[/\bLAI\b|12\.527/,'-lai'],
  l8137:[/8\.137/,'-8137-'],
  l14133:[/14\.133|Nova Lei de Licita/,'-14133-'],
  lgpd:[/LGPD|13\.709/,'-13709-'],
  l14063:[/14\.063/,'-14063-'],
  l4320:[/4\.320/,'-4320-'],
  l9784:[/9\.784/,'-9784-'],
  decreto1171:[/1\.171/,'-1171-'],
  lc116:[/LC\s*(?:n[º°.]?\s*)?116|116\/2003|Complementar\s*(?:n[º°.]?\s*)?116/,'-116-']
};
const OUTRA=/8\.666|C[óo]digo (?:Civil|Penal|de Processo)|Lei\s+(?:Complementar\s+)?n[º°.]?\s*[\d.]+/;
const leiDaUrl=url=>Object.keys(LEIS).find(k=>url.includes(LEIS[k][1]));
// Lei nomeada logo depois do artigo ("art. 5º, II, da CF"): sua chave, 'outra' ou null se não nomeia nenhuma.
function quemCita(resto){
  resto=resto.split(/\.\s|;|:/)[0];
  let melhor=null,pos=1e9;
  for(const k in LEIS){const m=resto.match(LEIS[k][0]);if(m&&m.index<pos){pos=m.index;melhor=k}}
  return melhor||(OUTRA.test(resto)?'outra':null);
}

function artigosCitados(texto,lei,semFiltro){
  const out={},re=/\b[Aa]rts?\.\s*(\d[\d.]*[º°]?(?:-[A-Z])?)/g,prox=/\s*[,;]?\s*(?:\be\b\s*)?(\d[\d.]*[º°]?(?:-[A-Z])?)(?![\w§])/y;
  const todos=[...texto.matchAll(re)];
  const somar=(k,sels)=>{if(!(k in out))out[k]=sels;else if(out[k]&&sels)out[k]=out[k].concat(sels);else out[k]=null};
  for(const m of todos){
    const seguinte=todos.find(x=>x.index>m.index),regiao=texto.slice(m.index+m[0].length,seguinte?seguinte.index:texto.length);
    const resto=regiao.slice(0,110);
    if(!semFiltro){
      const dono=quemCita(resto);
      if(lei==='cf'?dono!=='cf':dono&&dono!==lei)continue;
    }
    // "arts. 29, III, 35 e 36": cada número seguinte é outro artigo, com o próprio recorte.
    const plural=/^[Aa]rts/.test(m[0]);let pos=0,k=norm(m[1]),r=recorte(regiao,0);
    for(;;){
      let seguintes=null;
      if(plural){prox.lastIndex=r.fim;const n=prox.exec(regiao);if(n){seguintes=n;}}
      const ultimo=!seguintes;
      let sels=r.sels;
      if(ultimo){const ex=extras(regiao);if(ex.length)sels=(sels||[]).concat(ex)}
      if(k)somar(k,sels);
      if(ultimo)break;
      k=norm(seguintes[1]);r=recorte(regiao,prox.lastIndex);
    }
  }
  return out;
}

function blocos(md){
  const out=[];let cur=null,adct=false;
  for(const linha of md.split('\n')){
    const m=linha.match(/^\*\*Art\.\s*([^*]+?)\*\*/);
    if(m){cur={k:norm(m[1]),linhas:[linha],adct};out.push(cur)}
    else if(/^#{1,6} /.test(linha)){cur=null;if(/^## /.test(linha))adct=/Transit[óo]rias/.test(linha)}
    else if(cur&&linha.trim())cur.linhas.push(linha);
  }
  return out;
}

// Classifica cada linha do artigo (caput, §, inciso, alínea) para poder recortar.
function estrutura(linhas){
  let par='caput',inc=null,ult=null;
  return linhas.map((t,i)=>{
    let m,tipo='txt';
    if(i===0)tipo='caput';
    else if((m=t.match(/^§\s*(\d+)[º°]?(-[A-Z])?/))){par=parKey(m[0].slice(1));inc=null;ult=null;tipo='par'}
    else if(/^Par[áa]grafo [Úú]nico/.test(t)){par='u';inc=null;ult=null;tipo='par'}
    else if((m=t.match(/^([IVXL]+(?:-[A-Z])?)\s*[-–.]/))){inc=m[1];ult=null;tipo='inc'}
    else if((m=t.match(/^([a-z])\)/))){tipo='ali';ult=m[1]}
    return {t,par,inc,tipo,ali:ult};
  });
}

function recortar(linhas,sels){
  const L=estrutura(linhas),alvo=new Set();
  for(const s of sels)L.forEach((l,i)=>{
    if(!i)return;
    if(s.inteiro){if(l.par===s.par)alvo.add(i)}
    else if(l.par===s.par){
      if(l.tipo==='par')alvo.add(i);
      else if(l.inc===s.inc&&(!s.ali.length||l.tipo==='inc'||s.ali.includes(l.ali)))alvo.add(i);
    }
  });
  if(!alvo.size)return null;
  return L.map((l,i)=>({...l,mostra:i===0||alvo.has(i),alvo:alvo.has(i)})).filter(l=>l.mostra);
}

const htmlArtigos=(achados,cit)=>achados.map(b=>{
  const r=cit[b.k]&&recortar(b.linhas,cit[b.k]);
  if(!r)return `<div class="tl-bloco">${b.linhas.map(l=>htmlLinha(l,'tl-hl')).join('')}</div>`;
  return `<div class="tl-bloco">${r.map(l=>htmlLinha(l.t,l.alvo?'tl-hl':l.tipo==='caput'?'tl-ctx':'')).join('')}</div>`;
}).join('');

const htmlLinha=(t,cls)=>{
  const m=t.match(/^\*\*(Art\.[^*]+)\*\*(.*)/);
  return `<p${cls?` class="${cls}"`:''}>${m?`<b>${esc(m[1])}</b>${esc(m[2])}`:esc(t)}</p>`;
};

// Lei inteira: cada artigo, parágrafo, inciso e alínea numa linha própria e com recuo próprio.
// Linha do .md sem a marcação: "## Título", "**10.** texto" (número em negrito), "- (a) alínea".
const limpaMd=l=>{const m=l.match(/^\*\*([^*]+)\*\*\s*(.*)/);return m?`<b>${esc(m[1])}</b> ${esc(m[2])}`:esc(l.replace(/^#+ |^- |\*\*/g,''))};
const md2html=(txt,hl,linhas)=>txt.split('\n').map((l,i)=>{
  if(!l.trim())return '';
  if(linhas&&linhas.has(i))return `<p class="tl-hl${/^- /.test(l)?' tl-p-ali':''}">${limpaMd(l)}</p>`;
  const h=l.match(/^(#{1,6}) (.*)/);
  if(h)return `<h4>${esc(h[2])}</h4>`;
  const it=l.match(/^\*\*([A-Z]?\d[\w.]*\.)\*\*\s*(.*)/);   // item de norma: "**10.** texto"
  if(it)return `<p class="tl-art"><b>${esc(it[1])}</b> ${esc(it[2])}</p>`;
  if(/^- /.test(l))return `<p class="tl-p-ali">${esc(l.slice(2))}</p>`;
  const m=l.match(/^\*\*(Art\.[^*]+)\*\*(.*)/);
  if(m){const k=norm(m[1].replace(/^Art\./,''));return `<p class="tl-art${hl&&hl.includes(k)?' tl-hl':''}" data-art="${esc(k)}"><b>${esc(m[1])}</b>${esc(m[2])}</p>`}
  if(/^(§|Par[áa]grafo [Úú]nico)/.test(l))return `<p class="tl-p-par">${esc(l)}</p>`;
  if(/^[IVXL]+(?:-[A-Z])?\s*[-–.]\s/.test(l))return `<p class="tl-p-inc">${esc(l)}</p>`;
  if(/^[a-z]\)\s/.test(l))return `<p class="tl-p-ali">${esc(l)}</p>`;
  if(/^\d+\.\s/.test(l))return `<p class="tl-p-item">${esc(l)}</p>`;
  return `<p>${esc(l)}</p>`;
}).join('');

// Pesquisa sem diferenciar maiúsculas nem acentos; marca as ocorrências e navega entre elas.
const simples=t=>[...t].map(c=>c.normalize('NFD')[0].toLowerCase()).join('');
let marcas=[],atual=-1,temporizador;
function limparMarcas(body){
  body.querySelectorAll('mark.tl-m').forEach(m=>m.replaceWith(m.textContent));body.normalize();marcas=[];atual=-1;
}
function marcar(body,termo){
  limparMarcas(body);
  const q=simples(termo.trim());if(!q)return;
  const nos=[],w=document.createTreeWalker(body,NodeFilter.SHOW_TEXT);
  for(let n;(n=w.nextNode());)nos.push(n);
  for(const no of nos){
    const t=no.nodeValue,st=simples(t);if(!st.includes(q))continue;
    const frag=document.createDocumentFragment();let i=0,j;
    while((j=st.indexOf(q,i))>=0){
      if(j>i)frag.append(t.slice(i,j));
      const m=document.createElement('mark');m.className='tl-m';m.textContent=t.slice(j,j+q.length);frag.append(m);i=j+q.length;
    }
    frag.append(t.slice(i));no.replaceWith(frag);
  }
  marcas=[...body.querySelectorAll('mark.tl-m')];
}
function irPara(i){
  const n=box.querySelector('.tl-n');
  if(!marcas.length){n.textContent=box.querySelector('.tl-q').value.trim()?'0 resultados':'';return}
  if(marcas[atual])marcas[atual].classList.remove('tl-cur');
  atual=(i+marcas.length)%marcas.length;
  marcas[atual].classList.add('tl-cur');marcas[atual].scrollIntoView({block:'center'});
  n.textContent=(atual+1)+' de '+marcas.length;
}
function iniciarBusca(){
  const q=box.querySelector('.tl-q'),body=box.querySelector('.tl-body');
  const buscar=()=>{
    // Os trechos citados são só uma amostra: ao pesquisar, passa para a lei inteira.
    if(q.value.trim()&&box._lei&&box._modo==='art')box._lei();
    marcar(body,q.value);irPara(0);
  };
  q.addEventListener('input',()=>{clearTimeout(temporizador);temporizador=setTimeout(buscar,200)});
  q.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();marcas.length?irPara(atual+(e.shiftKey?-1:1)):buscar()}});
  box.querySelector('.tl-next').onclick=()=>irPara(atual+1);
  box.querySelector('.tl-prev').onclick=()=>irPara(atual-1);
}

function criar(){
  box=document.createElement('div');
  box.className='tl-box';box.hidden=true;
  box.innerHTML='<div class="tl-head"><b class="tl-title"></b><button class="tl-toggle" type="button"></button><button class="tl-close" type="button" aria-label="Fechar">×</button></div><div class="tl-abas" role="tablist" hidden></div><div class="tl-busca"><input type="search" class="tl-q" placeholder="Pesquisar no texto…" aria-label="Pesquisar no texto"><span class="tl-n"></span><button type="button" class="tl-prev" aria-label="Anterior">▲</button><button type="button" class="tl-next" aria-label="Próximo">▼</button></div><div class="tl-body"></div>';
  iniciarBusca();
  document.body.appendChild(box);
  box.querySelector('.tl-close').onclick=()=>box.hidden=true;
  const head=box.querySelector('.tl-head');
  head.addEventListener('pointerdown',e=>{
    if(e.target.closest('button'))return;
    const r=box.getBoundingClientRect(),dx=e.clientX-r.left,dy=e.clientY-r.top;
    head.setPointerCapture(e.pointerId);
    const mv=ev=>{box.style.left=Math.max(0,Math.min(innerWidth-60,ev.clientX-dx))+'px';box.style.top=Math.max(0,Math.min(innerHeight-40,ev.clientY-dy))+'px';box.style.right='auto';box.style.bottom='auto'};
    const up=()=>{head.removeEventListener('pointermove',mv);head.removeEventListener('pointerup',up)};
    head.addEventListener('pointermove',mv);head.addEventListener('pointerup',up);
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!box.hidden)box.hidden=true});
}

// Só o recorte dos artigos de `citacao` ("Art. 37, XIII", "Arts. 16 e 17", "Art. 4º, § 6º"), em HTML,
// ou null se a lei não tem esses artigos. Usado pelo balão das referências (js/ref-lei.js).
window.trechoLei=async function(url,citacao,adct){
  if(!cache[url]){const r=await fetch(url);if(!r.ok)throw new Error('lei');cache[url]=await r.text()}
  const cit=artigosCitados(citacao,null,true),vistos=new Set();
  const achados=blocos(cache[url]).filter(b=>b.adct===!!adct&&b.k in cit&&!vistos.has(b.k)&&vistos.add(b.k));
  return achados.length?htmlArtigos(achados,cit):null;
};

// Linhas do texto (leis/*.md) onde está um trecho citado. Compara só letras e números, sem acento, para
// não depender de quebras de linha e pontuação da transcrição. Trecho cortado ("…") vale até onde bate.
const so=c=>{const d=c.normalize('NFD')[0].toLowerCase();return /[a-z0-9]/.test(d)?d:''};
const indices={};
function indice(url){
  if(indices[url])return indices[url];
  let t='';const lin=[];
  cache[url].split('\n').forEach((l,i)=>{for(const c of l){const d=so(c);if(d){t+=d;lin.push(i)}}});
  return indices[url]={t,lin};
}
function linhasDoTrecho(url,trecho){
  const {t,lin}=indice(url),out=new Set();
  for(const parte of trecho.split(/…|\[\.\.\.\]|\(\.\.\.\)/)){
    const q=[...parte].map(so).join('');if(q.length<20)continue;
    let p=t.indexOf(q),fim=p+q.length;
    if(p<0){p=t.indexOf(q.slice(0,60));if(p<0)continue;fim=p;while(fim-p<q.length&&t[fim]===q[fim-p])fim++}
    for(let i=lin[p];i<=lin[fim-1];i++)out.add(i);
  }
  return out;
}
// Só os trechos: cada sequência de linhas marcadas, com o título de seção mais próximo antes dela.
function htmlTrechos(md,linhas){
  const L=md.split('\n'),ord=[...linhas].sort((a,b)=>a-b),grupos=[];
  for(const i of ord){const g=grupos[grupos.length-1];if(g&&i-g[g.length-1]<=2)g.push(i);else grupos.push([i])}
  return grupos.map(g=>{
    let h=g[0];while(h>=0&&!/^#{1,6} /.test(L[h]))h--;
    const tit=h>=0?`<p class="tl-ctx"><b>${esc(L[h].replace(/^#+ /,''))}</b></p>`:'';
    const corpo=[];for(let i=g[0];i<=g[g.length-1];i++)if(L[i].trim())corpo.push(`<p class="tl-hl${/^- /.test(L[i])?' tl-p-ali':''}">${limpaMd(L[i])}</p>`);
    return `<div class="tl-bloco">${tit}${corpo.join('')}</div>`;
  }).join('');
}

// Uma fonte no balão. f = {titulo, url (leis/*.md ou null), artigos ("art. 150, I; art. 151"), adct,
// trecho (texto que explica a resposta: manual, norma), link (fonte externa), comentario (reserva p/ artigos)}.
async function mostrar(f){
  const body=box.querySelector('.tl-body'),toggle=box.querySelector('.tl-toggle'),q=box.querySelector('.tl-q');
  box.querySelector('.tl-title').textContent=f.titulo;q.value='';box.querySelector('.tl-n').textContent='';
  box.hidden=false;toggle.hidden=true;box._lei=null;box._modo=null;marcas=[];atual=-1;
  const ext=f.link?`<p class="tl-aviso">Fonte: <a href="${esc(f.link)}" target="_blank" rel="noopener noreferrer">${esc(f.link.replace(/^https?:\/\/(www\.)?/,'').split('/')[0])}</a></p>`:'';
  if(!f.url){
    body.innerHTML=(f.trecho?`<div class="tl-bloco"><p class="tl-hl">${esc(f.trecho)}</p></div>`:'')+
      '<p class="tl-aviso">O site não tem o texto inteiro desta fonte; acima, o trecho que fundamenta a resposta.</p>'+ext;
    return;
  }
  body.textContent='Carregando…';
  const url=f.url;
  try{if(!cache[url]){const r=await fetch(url);if(!r.ok)throw 0;cache[url]=await r.text()}}
  catch(e){body.textContent='Não foi possível carregar o texto.';return}
  const md=cache[url];
  let trechos,inteiro,achou;
  if(f.trecho){
    const linhas=linhasDoTrecho(url,f.trecho);achou=linhas.size>0;
    trechos=()=>htmlTrechos(md,linhas);inteiro=()=>md2html(md,null,linhas);
  }else{
    const lei=leiDaUrl(url),todos=blocos(md).filter(b=>f.adct===undefined||b.adct===!!f.adct);
    let cit=f.artigos?artigosCitados(f.artigos,lei,true):{};
    if(!Object.keys(cit).length&&f.comentario)cit=artigosCitados(f.comentario,lei);
    const vistos=new Set(),achados=todos.filter(b=>b.k in cit&&!vistos.has(b.k)&&vistos.add(b.k));achou=achados.length>0;
    trechos=()=>htmlArtigos(achados,cit);inteiro=()=>md2html(md,Object.keys(cit));
  }
  const nomeInteiro=f.trecho?'Texto inteiro':'Lei inteira';
  const verTrechos=()=>{
    body.innerHTML=trechos();body.scrollTop=0;limparMarcas(body);q.value='';box.querySelector('.tl-n').textContent='';
    toggle.textContent=nomeInteiro;toggle.onclick=verInteiro;toggle.hidden=false;box._modo='art';
  };
  const verInteiro=()=>{
    body.innerHTML=inteiro();box._modo='lei';marcas=[];atual=-1;
    toggle.textContent='Só o trecho da questão';toggle.onclick=verTrechos;toggle.hidden=!achou;
    const p=body.querySelector('.tl-hl');if(p)p.scrollIntoView({block:'start'});
  };
  box._lei=verInteiro;
  if(achou)verTrechos();
  else{verInteiro();body.insertAdjacentHTML('afterbegin',`<p class="tl-aviso">Não achei o trecho exato da questão; mostrando o texto inteiro.</p>`)}
}

// Várias fontes num balão só, uma aba para cada; abre na de índice i.
window.abrirFontes=function(lista,i=0){
  if(!box)criar();
  const abas=box.querySelector('.tl-abas');
  abas.hidden=lista.length<2;
  abas.innerHTML=lista.map((f,j)=>`<button type="button" role="tab" class="tl-aba" aria-selected="${j===i}" title="${esc(f.titulo)}">${esc(f.aba||f.titulo)}</button>`).join('');
  [...abas.children].forEach((b,j)=>b.onclick=()=>{[...abas.children].forEach((x,k)=>x.setAttribute('aria-selected',k===j));mostrar(lista[j])});
  return mostrar(lista[i]);
};

window.abrirTextoLei=function(url,titulo,comentario,legal,adct){
  // Com "Fundamento legal" da mesma lei, ele manda (é o dispositivo exato); senão vale o que o comentário cita.
  const lei=leiDaUrl(url),base=legal&&LEIS[lei]&&LEIS[lei][0].test(legal.title||'')?legal.article||'':'';
  return window.abrirFontes([{url,titulo,artigos:base,comentario,adct}]);
};
})();
