// Faz a questão inteira (enunciado + alternativas) caber no cartão, sem rolar.
// Começa no tamanho normal do texto e, se a questão não couber, reduz o font-size do .body
// (o CSS de questoes.css usa em) até caber ou até o mínimo legível. Se nem no mínimo couber
// (celular, enunciado enorme), a rolagem volta a funcionar normalmente.
// Inclua no fim da página do Caderno e da de Simulados: <script src="questoes-fit.js"></script>
(function(){
const MAX=16, MIN=12, STEP=.5;
const body=document.querySelector('.card .body');
if(!body)return;
let queued=false;

function fit(){
 queued=false;
 body.style.fontSize=MAX+'px';
 if(!body.offsetParent||innerWidth<=700)return;     // escondida (solução aberta no celular) ou celular, onde a página rola
 if(body.querySelector('details.study-law[open]'))return; // o aluno abriu a lei: aí pode rolar
 const stem=document.getElementById('stem');
 if(stem)stem.classList.remove('cols');
 if(shrink(14))return;
 // Texto longo de interpretação: em duas colunas antes de diminuir mais a letra.
 if(stem&&stem.offsetHeight>body.clientHeight*.4&&body.clientWidth>=800){stem.classList.add('cols');body.style.fontSize=MAX+'px'}
 shrink(MIN);
}
const fits=()=>body.scrollHeight<=body.clientHeight+1;
function shrink(min){let size=parseFloat(body.style.fontSize)||MAX;while(!fits()&&size>min){size-=STEP;body.style.fontSize=size+'px'}return fits()}
function later(){if(!queued){queued=true;requestAnimationFrame(fit)}}

// Nova questão, alternativa riscada, solução aberta ou fechada, janela redimensionada.
new MutationObserver(later).observe(body,{childList:true,subtree:true,characterData:true});
const sol=document.getElementById('sol');
if(sol)new MutationObserver(later).observe(sol,{attributes:true,attributeFilter:['class']});
body.addEventListener('toggle',later,true);
addEventListener('resize',later);
if(document.fonts&&document.fonts.ready)document.fonts.ready.then(later);
later();
window.QuestoesFit={fit};
})();
