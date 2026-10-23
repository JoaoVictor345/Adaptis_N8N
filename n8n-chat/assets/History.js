/* Histórico de conversas + compartilhamento.
   Funciona sem alterar o workflow.js: observa o #chatBox e salva cada .message.user / .message.bot. */
(() => {
  const API = 'api/history.php';
  const $ = (s) => document.querySelector(s);
  const chatBox = $('#chatBox');
  const params = new URLSearchParams(location.search);

  let currentId = null;
  let readOnly = false;
  let creating = null;
  let queue = Promise.resolve();
  const msgIds = new WeakMap();
  const timers = new WeakMap();
  let cache = [];

  async function api(action, data = {}) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
      body: JSON.stringify({ action, ...data }),
    });
    const json = await res.json().catch(() => ({ ok: false, error: 'Resposta inválida do servidor' }));
    if (!res.ok || json.ok === false) throw new Error(json.error || 'Erro inesperado');
    return json;
  }

  const fmt = (d) => {
    const dt = new Date(String(d).replace(' ', 'T'));
    const today = new Date();
    const same = dt.toDateString() === today.toDateString();
    return same
      ? dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
      : dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
  };
  const groupOf = (d) => {
    const days = (Date.now() - new Date(String(d).replace(' ', 'T'))) / 864e5;
    return days < 1 ? 'Hoje' : days < 2 ? 'Ontem' : days < 8 ? 'Últimos 7 dias' : 'Mais antigas';
  };

  /* ---------- Lista lateral ---------- */
  async function refreshList() {
    try { cache = (await api('list')).items; } catch (e) { cache = []; }
    renderList();
  }

  function renderList() {
    const box = $('#historyList');
    const q = ($('#historySearch').value || '').toLowerCase();
    const items = cache.filter((i) => i.title.toLowerCase().includes(q));
    box.textContent = '';
    if (!items.length) {
      const e = document.createElement('div');
      e.className = 'hist-empty';
      e.textContent = q ? 'Nenhuma conversa encontrada.' : 'Suas conversas aparecerão aqui.';
      box.append(e);
      return;
    }
    let lastGroup = '';
    items.forEach((i) => {
      const g = groupOf(i.updated_at);
      if (g !== lastGroup) {
        const h = document.createElement('div');
        h.className = 'hist-group';
        h.textContent = g;
        box.append(h);
        lastGroup = g;
      }
      const el = document.createElement('div');
      el.className = 'hist-item' + (i.id == currentId && !readOnly ? ' active' : '');
      el.innerHTML = `
        <div class="hist-title"></div>
        <div class="hist-meta"><span class="hist-date"></span></div>
        <div class="hist-actions">
          <button type="button" data-a="share" title="Compartilhar"><i class="bi bi-share"></i></button>
          <button type="button" data-a="rename" title="Renomear"><i class="bi bi-pencil"></i></button>
          <button type="button" data-a="delete" title="Excluir"><i class="bi bi-trash"></i></button>
        </div>`;
      el.querySelector('.hist-title').textContent = i.title;
      el.querySelector('.hist-date').textContent = fmt(i.updated_at);
      if (Number(i.shared)) {
        el.querySelector('.hist-meta').insertAdjacentHTML('beforeend', '<i class="bi bi-link-45deg" title="Compartilhada"></i>');
      }
      el.addEventListener('click', (ev) => {
        const btn = ev.target.closest('button[data-a]');
        if (!btn) return openConversation(i.id);
        ev.stopPropagation();
        if (btn.dataset.a === 'share') openShare(i.id);
        if (btn.dataset.a === 'rename') renameConv(i);
        if (btn.dataset.a === 'delete') deleteConv(i);
      });
      box.append(el);
    });
  }

  async function renameConv(i) {
    const title = prompt('Novo nome da conversa:', i.title);
    if (!title || !title.trim()) return;
    await api('rename', { id: i.id, title });
    if (i.id == currentId) setTitle(title.trim());
    refreshList();
  }

  async function deleteConv(i) {
    if (!confirm(`Excluir a conversa "${i.title}"? Links compartilhados deixarão de funcionar.`)) return;
    await api('delete', { id: i.id });
    if (i.id == currentId) return (location.href = 'chat.php');
    refreshList();
  }

  /* ---------- Renderizar mensagens antigas ---------- */
  function setTitle(t) { $('#convTitle').textContent = t; }

  function renderMessages(messages) {
    chatBox.textContent = '';
    messages.forEach((m) => {
      const el = document.createElement('div');
      el.className = 'message ' + (m.role === 'user' ? 'user' : 'bot');
      const txt = document.createElement('div');
      txt.className = 'msg-text';
      txt.textContent = m.content;
      const time = document.createElement('div');
      time.className = 'time';
      time.textContent = new Date(String(m.created_at).replace(' ', 'T'))
        .toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
      el.append(txt, time);
      if (m.id) msgIds.set(el, m.id);
      chatBox.append(el);
    });
    observer.takeRecords(); // não re-salvar o que acabou de ser carregado
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  async function openConversation(id) {
    try {
      const { conversation, messages } = await api('get', { id });
      currentId = conversation.id;
      setReadOnly(false);
      setTitle(conversation.title);
      renderMessages(messages);
      $('#shareCurrent').disabled = false;
      history.replaceState(null, '', 'chat.php?c=' + id);
      $('#historyPanel').classList.remove('open');
      renderList();
    } catch (e) { alert(e.message); }
  }

  function setReadOnly(on) {
    readOnly = on;
    $('#chatForm').style.display = on ? 'none' : '';
    document.querySelector('.quick-commands').style.display = on ? 'none' : '';
    if (!on) document.querySelector('.shared-banner')?.remove();
  }

  async function openShared(token) {
    try {
      const data = await api('shared', { token });
      if (data.is_owner) return openConversation(data.conversation.id);
      currentId = null;
      setReadOnly(true);
      setTitle(data.conversation.title);
      $('#shareCurrent').disabled = true;
      renderMessages(data.messages);
      const b = document.createElement('div');
      b.className = 'shared-banner';
      b.innerHTML = '<span>Conversa compartilhada com você. Modo somente leitura.</span><button type="button">Salvar cópia e continuar</button>';
      b.querySelector('button').onclick = async () => {
        const { id } = await api('clone', { token });
        location.href = 'chat.php?c=' + id;
      };
      document.querySelector('.chat-container').prepend(b);
    } catch (e) {
      setTitle('Link indisponível');
      chatBox.textContent = e.message;
    }
  }

  /* ---------- Salvamento automático ---------- */
  function ensureConversation(titleHint) {
    if (currentId) return Promise.resolve(currentId);
    creating ||= api('create', { title: titleHint }).then((r) => {
      currentId = r.id;
      setTitle(r.title);
      $('#shareCurrent').disabled = false;
      history.replaceState(null, '', 'chat.php?c=' + r.id);
      creating = null;
      return r.id;
    });
    return creating;
  }

  async function persist(el) {
    if (readOnly) return;
    const clone = el.cloneNode(true);
    clone.querySelectorAll('.time').forEach((t) => t.remove());
    const content = clone.textContent.trim();
    if (!content) return;
    const role = el.classList.contains('user') ? 'user' : 'bot';
    try {
      if (msgIds.has(el)) {
        await api('update_message', { id: msgIds.get(el), content });
      } else {
        const cid = await ensureConversation(content.slice(0, 60));
        const r = await api('add_message', { conversation_id: cid, role, content });
        msgIds.set(el, r.id);
      }
      refreshList();
    } catch (e) { console.warn('Histórico:', e.message); }
  }

  function schedule(el) {
    if (el.querySelector('#initialMessage')) return; // saudação inicial não é salva
    clearTimeout(timers.get(el));
    timers.set(el, setTimeout(() => { queue = queue.then(() => persist(el)); }, 1200));
  }

  const observer = new MutationObserver((records) => {
    if (readOnly) return;
    records.forEach((r) => {
      const node = r.target.nodeType === 1 ? r.target : r.target.parentElement;
      const el = node && node.closest('.message');
      if (el && chatBox.contains(el)) schedule(el);
      r.addedNodes.forEach((n) => {
        if (n.nodeType === 1 && n.classList.contains('message')) schedule(n);
      });
    });
  });
  observer.observe(chatBox, { childList: true, subtree: true, characterData: true });

  /* ---------- Compartilhar ---------- */
  async function openShare(id) {
    let token;
    try { token = (await api('share', { id })).token; } catch (e) { return alert(e.message); }
    const link = `${location.origin}${location.pathname}?shared=${token}`;

    const ov = document.createElement('div');
    ov.className = 'share-overlay';
    ov.innerHTML = `
      <div class="share-modal" role="dialog" aria-modal="true">
        <h3>Compartilhar conversa</h3>
        <p>Quem tiver este link e estiver logado no ADAPTIS poderá ler esta conversa e salvar uma cópia.</p>
        <div class="share-row"><input readonly><button type="button" class="share-btn primary" data-a="copy">Copiar link</button></div>
        <div class="share-foot">
          <button type="button" class="share-btn danger" data-a="stop">Parar de compartilhar</button>
          <span>${navigator.share ? '<button type="button" class="share-btn" data-a="send">Enviar…</button> ' : ''}<button type="button" class="share-btn" data-a="close">Fechar</button></span>
        </div>
      </div>`;
    ov.querySelector('input').value = link;
    const close = () => { ov.remove(); refreshList(); };
    ov.addEventListener('click', async (e) => {
      if (e.target === ov) return close();
      const a = e.target.closest('[data-a]')?.dataset.a;
      if (a === 'close') close();
      if (a === 'copy') {
        try { await navigator.clipboard.writeText(link); }
        catch { ov.querySelector('input').select(); document.execCommand('copy'); }
        e.target.textContent = 'Link copiado!';
      }
      if (a === 'send') navigator.share({ title: 'Conversa do ADAPTIS', url: link }).catch(() => {});
      if (a === 'stop') { await api('unshare', { id }); close(); }
    });
    document.body.append(ov);
    ov.querySelector('input').select();
  }

  /* ---------- Eventos e inicialização ---------- */
  $('#newChatBtn').onclick = () => (location.href = 'chat.php'); // recarrega e zera o contexto do assistente
  $('#historySearch').addEventListener('input', renderList);
  $('#shareCurrent').onclick = () => currentId && openShare(currentId);
  $('#historyToggle').onclick = () => $('#historyPanel').classList.toggle('open');

  refreshList();
  if (params.get('shared')) openShared(params.get('shared'));
  else if (params.get('c')) openConversation(params.get('c'));
})();