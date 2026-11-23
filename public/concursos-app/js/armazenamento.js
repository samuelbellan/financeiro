// Armazenamento do site: o banco SQLite do servidor local (iniciar.py → dados_usuario/progresso.db) é a ÚNICA fonte
// da verdade. O localStorage do navegador NÃO é usado: este script o substitui por um objeto com a mesma interface
// (getItem, setItem, removeItem, clear, key, length), então o resto do código segue chamando `localStorage` normalmente.
//   - Abertura: carrega tudo do banco (requisição síncrona) para uma cópia em memória.
//   - Gravação: setItem/removeItem/clear vão ao banco ANTES de retornar (síncrono, ~1 ms no servidor local).
//     Se o servidor não responder, a gravação lança erro e aparece uma faixa vermelha; nada fica só no navegador.
//   - Várias abas: ao voltar para uma aba (ou chamar __armazenamento.atualizar()), a cópia em memória é recarregada do banco.
// Carregar ANTES de qualquer outro script que use localStorage.
(function () {
  if (window.__armazenamento) return;
  var dados = Object.create(null); // cópia de leitura em memória (chave -> texto)

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function xhr(metodo, corpo) {
    var x = new XMLHttpRequest();
    x.open(metodo, '/concursos/__estado', false);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (metodo === 'POST') {
      x.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
      var tok = csrfToken();
      if (tok) x.setRequestHeader('X-CSRF-TOKEN', tok);
    }
    x.send(corpo || null);
    if (x.status !== 200) throw new Error('servidor respondeu ' + x.status);
    return JSON.parse(x.responseText);
  }

  function aviso(falhou) {
    var el = document.getElementById('__aviso_salvar');
    if (!falhou) { if (el) el.remove(); return; }
    if (el) return;
    var montar = function () {
      if (document.getElementById('__aviso_salvar')) return;
      el = document.createElement('div'); el.id = '__aviso_salvar'; el.setAttribute('role', 'alert');
      el.textContent = 'Não foi possível salvar no banco de dados. Seu progresso recente NÃO foi gravado — verifique sua conexão ou se sua sessão expirou.';
      el.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#b42318;color:#fff;padding:.5rem 1rem;font:600 .875rem system-ui,sans-serif;text-align:center';
      document.body.appendChild(el);
    };
    if (document.body) montar(); else document.addEventListener('DOMContentLoaded', montar);
  }

  function atualizar() {
    var banco = xhr('GET');
    dados = Object.create(null);
    Object.keys(banco).forEach(function (k) { dados[k] = banco[k]; });
    aviso(false);
  }

  function gravar(set, del) {
    try { xhr('POST', JSON.stringify({ set: set, del: del })); aviso(false); }
    catch (e) { aviso(true); throw e; }
  }

  var Armazenamento = {
    getItem: function (k) { k = String(k); return k in dados ? dados[k] : null; },
    setItem: function (k, v) {
      k = String(k); v = String(v);
      var o = {}; o[k] = v;
      gravar(o, []);
      dados[k] = v; // só depois de o banco confirmar
    },
    removeItem: function (k) {
      k = String(k);
      if (!(k in dados)) return;
      gravar({}, [k]);
      delete dados[k];
    },
    clear: function () {
      var ks = Object.keys(dados);
      if (!ks.length) return;
      gravar({}, ks);
      dados = Object.create(null);
    },
    key: function (i) { var ks = Object.keys(dados); return i >= 0 && i < ks.length ? ks[i] : null; }
  };
  Object.defineProperty(Armazenamento, 'length', { get: function () { return Object.keys(dados).length; } });

  try { atualizar(); } catch (e) { aviso(true); }
  Object.defineProperty(window, 'localStorage', { value: Armazenamento, configurable: true, writable: true });

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') { try { atualizar(); } catch (_) { aviso(true); } }
  });
  window.__armazenamento = { ativo: true, atualizar: atualizar, salvarAgora: function () { } };
})();
