// Histórico por dia: questões respondidas (Caderno, Simulados e Refazer erros) e flashcards revisados em cada dia.
// Fica em localStorage 'historico_diario':
//   { "2026-11-08": { r: 12, a: 10, f: 30, fe: 4, caderno: {r: 9, a: 8}, simulados: {r: 3, a: 2}, refazer: {r: 0, a: 0} } }
//   r = questões respondidas, a = acertos (erros = r - a); f = flashcards revisados, fe = "Errei" nos flashcards.
// O Caderno, os Simulados e o Refazer erros chamam Historico.registrar(acertou, origem) ao responder; flashcards.js chama
// Historico.registrarFlash(errou) a cada resposta que muda a agenda (e desfazerFlash ao desfazer); o Início lê.
//
// Respostas antigas:
// - As do Refazer erros têm data (hist de cada questão) e entram sozinhas: ver completarRefazer().
// - As do Caderno e dos Simulados não guardam data. O Início mostra quantas são e oferece
//   importarSemData(r, a), que as soma ao dia de hoje quando o aluno confirma que foram hoje.
// - Os flashcards guardam a próxima revisão e o intervalo, então a data da última revisão de cada card
//   é (próxima revisão − intervalo). Na primeira vez que este arquivo roda, essas revisões entram no histórico
//   (uma por card: revisões mais antigas do mesmo card já não aparecem). A marca 'historico_fc_desde' evita repetir.
(function(){
const KEY='historico_diario', FC='lom_fc', FC_DESDE='historico_fc_desde';
const pad=n=>String(n).padStart(2,'0');
// Dia local (não UTC), para a virada acontecer à meia-noite daqui.
const dia=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
function lerChave(k){try{const v=JSON.parse(localStorage.getItem(k)||'{}');return v&&typeof v==='object'&&!Array.isArray(v)?v:{}}catch(_){return {}}}
const ler=()=>lerChave(KEY);
function gravar(h){try{localStorage.setItem(KEY,JSON.stringify(h))}catch(_){}}
function somar(k,campos){const h=ler(),d=Object.assign({r:0,a:0,f:0,fe:0},h[k]);for(const [c,n] of Object.entries(campos))d[c]=Math.max(0,(d[c]||0)+n);h[k]=d;gravar(h)}
const hojeK=()=>dia(new Date());

// origem (opcional): 'caderno', 'simulados' ou 'refazer'. Além do total do dia, soma em h[dia][origem] = {r, a}.
function registrar(acertou,origem){
 const k=hojeK();somar(k,{r:1,a:acertou?1:0});
 if(!origem)return;
 const h=ler(),d=h[k],o=Object.assign({r:0,a:0},d[origem]);o.r++;if(acertou)o.a++;d[origem]=o;gravar(h);
}
function registrarFlash(errou){somar(hojeK(),{f:1,fe:errou?1:0})}
function desfazerFlash(errou){somar(hojeK(),{f:-1,fe:errou?-1:0})}
// Respostas do Caderno/Simulados sem data que o aluno confirmou serem de hoje.
function importarSemData(r,a){if(r>0)somar(hojeK(),{r,a:Math.max(0,Math.min(a,r))})}

// Uma vez só: data da última revisão de cada flashcard = próxima revisão − intervalo.
(function backfillFlash(){
 try{if(localStorage.getItem(FC_DESDE))return}catch(_){return}
 const fc=lerChave(FC),por={};
 for(const s of Object.values(fc)){
  if(!s||!s.due||!(s.ivl>=1))continue;
  const [y,m,d]=s.due.split('-').map(Number),t=new Date(y,m-1,d);t.setDate(t.getDate()-s.ivl);
  const k=dia(t),v=por[k]||(por[k]={f:0,fe:0});v.f++;if(s.reps===0)v.fe++; // reps 0 = última resposta foi "Errei"
 }
 for(const [k,v] of Object.entries(por))somar(k,v);
 try{localStorage.setItem(FC_DESDE,hojeK())}catch(_){}
})();

// Refazer erros: cada tentativa fica com a data no próprio estado ('refazer-v1' → hist: [{d, ok}]). A cada carga, cada
// dia recebe só o que falta (tentativas no estado menos as já registradas em h[dia].refazer). Assim as sessões de
// antes do registro por dia entram no histórico, e as novas, que vão para os dois lugares, nunca contam em dobro.
(function completarRefazer(){
 const por={};
 for(const e of Object.values(lerChave('refazer-v1'))){
  if(!e||!Array.isArray(e.hist))continue;
  for(const x of e.hist){if(!x||!/^\d{4}-\d{2}-\d{2}$/.test(x.d||''))continue;const v=por[x.d]||(por[x.d]={r:0,a:0});v.r++;if(x.ok)v.a++}
 }
 const h=ler();let mudou=false;
 for(const [k,v] of Object.entries(por)){
  const d=Object.assign({r:0,a:0,f:0,fe:0},h[k]),o=Object.assign({r:0,a:0},d.refazer),dr=v.r-o.r;
  if(dr<=0)continue;
  const da=Math.max(0,Math.min(dr,v.a-o.a));
  d.r+=dr;d.a+=da;d.refazer={r:o.r+dr,a:o.a+da};h[k]=d;mudou=true;
 }
 if(mudou)gravar(h);
})();

// Últimos n dias, do mais antigo para hoje, incluindo os dias sem atividade.
function ultimos(n){
 const h=ler(),out=[],d=new Date();d.setHours(12,0,0,0);d.setDate(d.getDate()-(n-1));
 for(let i=0;i<n;i++){const k=dia(d),v=Object.assign({r:0,a:0,f:0,fe:0},h[k]),rf=Object.assign({r:0,a:0},v.refazer);out.push({dia:k,data:new Date(d),r:v.r,a:v.a,e:v.r-v.a,f:v.f,fe:v.fe,rf:rf.r});d.setDate(d.getDate()+1)}
 return out;
}
// Sem o Refazer erros: ele refaz questões que já estão salvas no Caderno ou nos Simulados, e este total é
// comparado com essas respostas salvas para achar as que não têm data.
function total(){let r=0,a=0,f=0;for(const v of Object.values(ler())){const rf=Object.assign({r:0,a:0},v.refazer);r+=(v.r||0)-rf.r;a+=(v.a||0)-rf.a;f+=v.f||0}return {r,a,e:r-a,f}}
// Respostas de um dia por página. As gravadas sem página (antes de o site separar Caderno e
// Simulados, ou importadas "sem data") contam no Caderno, onde fica a maior parte das questões.
function porOrigem(v,origem){
 const c=Object.assign({r:0,a:0},v.caderno),s=Object.assign({r:0,a:0},v.simulados),rf=Object.assign({r:0,a:0},v.refazer);
 const ur=Math.max(0,(v.r||0)-c.r-s.r-rf.r),ua=Math.max(0,Math.min(ur,(v.a||0)-c.a-s.a-rf.a));
 const o=origem==='caderno'?{r:c.r+ur,a:c.a+ua,semOrigem:ur}:{r:s.r,a:s.a,semOrigem:0};
 return Object.assign(o,{e:o.r-o.a});
}
// Dias com respostas de uma página, do mais recente para o mais antigo: [{dia, data, r, a, e, semOrigem}].
function dias(origem){
 const h=ler();
 return Object.keys(h).sort().reverse().map(k=>{
  const [y,m,d]=k.split('-').map(Number);
  return Object.assign({dia:k,data:new Date(y,m-1,d,12)},porOrigem(h[k],origem));
 }).filter(x=>x.r);
}
function hojeDe(origem){return porOrigem(ler()[hojeK()]||{},origem)}
function inicio(){const k=Object.keys(ler()).filter(k=>{const v=ler()[k];return v.r||v.f}).sort()[0];return k||null}

window.Historico={registrar,registrarFlash,desfazerFlash,importarSemData,ultimos,total,inicio,dias,hojeDe,hoje:hojeK};
})();
