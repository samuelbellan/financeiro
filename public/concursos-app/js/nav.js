// Barra de navegação única do site. Inclua logo depois de <body>:
//   <script src="js/config.js"></script><script src="js/nav.js"></script>
// O script escreve a barra no lugar onde foi incluído (antes do resto da página ser desenhado,
// sem piscar) e marca a página atual. O botão "Edital" abre a janela de edital.js, se a página
// carregar esse script; senão, leva para a página inicial com a janela aberta.
(function(){
const PAGES=[
 ['inicio.html','Início'],
 ['caderno.html','Caderno'],
 ['simulados.html','Simulados'],
 ['flashcards.html','Flashcards'],
 ['refazer.html','Refazer erros'],
 ['resumos.html','Resumos'],
 ['leis.html','Lei seca'],
];
const here=(location.pathname.split('/').pop()||'inicio.html').toLowerCase();
const items=PAGES.map(([href,label])=>
 `<li><a class="sn-item" href="${href}"${href===here?' aria-current="page"':''}>${label}</a></li>`).join('');
const NOME=(window.CONFIG&&CONFIG.nome)||'Estudo para Concursos';
const escN=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const html=`<header class="sn"><a class="sn-brand" href="inicio.html" aria-label="${escN(NOME)}, página inicial"><i aria-hidden="true"></i><span>${escN(NOME)}</span></a>`+
 `<nav aria-label="Páginas do site" style="display:flex;justify-content:center;min-width:0"><ul class="sn-links">${items}`+
 `<li aria-hidden="true" class="sn-sep"></li>`+
 `<li><button type="button" class="sn-item" id="snEdital" aria-haspopup="dialog">Edital <span class="sn-pct" id="snEditalPct"></span></button></li>`+
 `</ul></nav><a class="sn-back" href="/home" title="Voltar ao painel principal do sistema">← Painel</a></header>`;
const me=document.currentScript;
if(me)me.insertAdjacentHTML('afterend',html);else document.body.insertAdjacentHTML('afterbegin',html);

function ready(){
 const btn=document.getElementById('snEdital'),pct=document.getElementById('snEditalPct');
 if(!btn)return;
 const E=window.Edital;
 if(!E){btn.onclick=()=>{location.href='inicio.html#edital'};return}
 btn.onclick=()=>E.open();
 const paint=()=>{const o=E.overall();pct.textContent=o.done?o.pct+'%':'';
  btn.setAttribute('aria-label',o.done?`Edital verticalizado, ${o.pct}% estudado`:'Edital verticalizado')};
 paint();E.subscribe(paint);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready);else ready();
})();

