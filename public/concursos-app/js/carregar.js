// carregarJson(url): busca um arquivo de dados (.json) e devolve o objeto (cada url é buscada uma vez só).
// Os dados ficam fora das páginas e só funcionam por HTTP: abra o site pelo iniciar.py.
const _json = {};
window.carregarJson = url => _json[url] ||= fetch(url).then(r => {
  if (!r.ok) throw new Error(url + ': HTTP ' + r.status);
  return r.json();
}).catch(err => {
  document.body.insertAdjacentHTML('afterbegin',
    '<p style="margin:0;padding:.75rem 1rem;background:#fbe9e7;color:#b5352a;font:14px system-ui">' +
    'Não foi possível carregar os dados. Abra o site pelo atalho do desktop (iniciar.py), não pelo arquivo.</p>');
  throw err;
});
