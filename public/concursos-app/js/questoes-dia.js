// Placar da tela de questões (Caderno e Simulados): respondidas, acertos e erros DE HOJE,
// o aproveitamento TOTAL (o #sPct que a página já calcula) e um botão "Histórico" com os dias.
// Lê historico.js. Inclua no fim da página, depois do script principal:
//   <script src="questoes-dia.js" data-origem="caderno"></script>   (ou "simulados")
(function(){
const origem=document.currentScript&&document.currentScript.dataset.origem;
const stats=document.querySelector('.top .stats'),pct=document.getElementById('sPct'),sA=document.getElementById('sA');
if(!origem||!window.Historico||!stats||!pct||!sA)return;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const plural=(n,um,varios)=>`${n} ${n===1?um:varios}`;
const nome=origem==='caderno'?'Caderno':'Simulados';

// Os três contadores totais da página continuam sendo atualizados por ela, mas ficam escondidos;
// o percentual total sai de dentro deles e fica visível ao lado dos números de hoje.
// O placar de hoje é o próprio botão que abre o histórico.
const hoje=document.createElement('button');hoje.type='button';hoje.className='qd-hoje';hoje.setAttribute('aria-haspopup','dialog');hoje.title='Ver o histórico por dia';
const totais=[...new Set(['sA','sC','sE'].map(id=>document.getElementById(id).parentElement))];
stats.insertBefore(hoje,totais[0]);
totais.forEach(el=>el.classList.add('qd-oculto'));
hoje.after(pct);
pct.title='Aproveitamento em todas as questões que você já respondeu';

function pintar(){
 const h=Historico.hojeDe(origem);
 hoje.innerHTML=`<span class="qd-rot">Hoje</span> <b>${h.r}</b> ${h.r===1?'respondida':'respondidas'}`+
  ` <span class="ok"><b>${h.a}</b> ${h.a===1?'acerto':'acertos'}</span> <span class="no"><b>${h.e}</b> ${h.e===1?'erro':'erros'}</span><span class="qd-seta" aria-hidden="true"></span>`;
 hoje.setAttribute('aria-label',`Hoje: ${h.r} respondidas, ${h.a} acertos, ${h.e} erros. Ver o histórico por dia`);
}
pintar();
// A página chama stats() depois de cada resposta (e depois de registrar no histórico).
new MutationObserver(pintar).observe(sA,{childList:true,characterData:true,subtree:true});
addEventListener('storage',e=>{if(e.key==='historico_diario'||e.key===null)pintar()});

// ---------- janela do histórico ----------
const dlg=document.createElement('dialog');dlg.className='qd-dlg';dlg.setAttribute('aria-labelledby','qdTitulo');
document.body.appendChild(dlg);
function abrir(){
 const dias=Historico.dias(origem),fmt=d=>d.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'2-digit'});
 const hojeK=Historico.hoje(),max=Math.max(1,...dias.map(d=>d.r));
 let r=0,a=0;for(const d of dias){r+=d.r;a+=d.a}
 const semOrigem=dias.reduce((s,d)=>s+d.semOrigem,0);
 const linhas=dias.filter(d=>d.r).map(d=>{const p=Math.round(d.a/d.r*100);
  return `<tr${d.dia===hojeK?' class="qd-hoje-l"':''}><th scope="row">${d.dia===hojeK?'Hoje':esc(fmt(d.data))}</th>`+
   `<td>${d.r}</td><td class="ok">${d.a}</td><td class="no">${d.e}</td><td>${p}%</td>`+
   `<td class="qd-barra" aria-hidden="true"><span style="width:${d.r/max*100}%"><i class="a" style="flex:${d.a}"></i><i class="e" style="flex:${d.e}"></i></span></td></tr>`}).join('');
 dlg.innerHTML=`<div class="qd-cab"><div><h2 id="qdTitulo">Histórico por dia · ${nome}</h2>`+
  `<p>${r?`${plural(r,'questão respondida','questões respondidas')} desde que o registro começou · ${Math.round(a/r*100)}% de acerto`:'Nenhuma resposta registrada ainda.'}</p></div>`+
  `<button type="button" class="qd-x" aria-label="Fechar">×</button></div>`+
  (linhas?`<div class="qd-corpo"><table><thead><tr><th scope="col">Dia</th><th scope="col">Respondidas</th><th scope="col">Acertos</th><th scope="col">Erros</th><th scope="col">Acerto</th><th scope="col"><span class="qd-leg"><i class="a"></i>acertos <i class="e"></i>erros</span></th></tr></thead><tbody>${linhas}</tbody></table></div>`
   :`<p class="qd-vazio">Responda uma questão e ela aparece aqui, no dia de hoje.</p>`)+
  `<p class="qd-nota">O registro por dia começou em ${esc((Historico.inicio()||hojeK).split('-').reverse().slice(0,2).join('/'))}; respostas anteriores não têm data.`+
  (origem==='caderno'&&semOrigem?` Inclui ${plural(semOrigem,'resposta registrada','respostas registradas')} antes de o site separar Caderno e Simulados.`:'')+
  (origem==='simulados'?' Respostas registradas antes de o site separar as páginas contam no Caderno.':'')+`</p>`;
 dlg.querySelector('.qd-x').onclick=()=>dlg.close();
 dlg.showModal();
}
hoje.onclick=abrir;
dlg.addEventListener('click',e=>{if(e.target===dlg)dlg.close()});
})();
