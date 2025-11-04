(() => {
  const isRoom = typeof window.SYC_ROOM_ID !== 'undefined';
  if (!isRoom) return;

  const roomId = window.SYC_ROOM_ID;
  const isReadOnly = !!window.SYC_READONLY;
  const POLL_MS = 1000;
  const editorEl = document.getElementById('editor');
  const languageSel = document.getElementById('language');
  const themeSel = document.getElementById('theme');
  const filenameInput = document.getElementById('filename');
  const chatLog = document.getElementById('chat-log');
  const chatForm = document.getElementById('chat-form');
  const chatName = document.getElementById('chat-name');
  const chatText = document.getElementById('chat-text');
  const downloadBtn = document.getElementById('download-code');
  const copyReadonlyBtn = document.getElementById('copy-readonly-link');

  let codeMirror;
  let currentRevision = 0;
  let isApplyingRemote = false;

  // Editor init
  function initEditor() {
    const savedLang = localStorage.getItem('syc.lang') || 'javascript';
    const savedTheme = localStorage.getItem('syc.theme') || 'default';
    const savedFilename = localStorage.getItem('syc.filename') || 'snippet.js';
    languageSel.value = savedLang;
    themeSel.value = savedTheme;
    filenameInput.value = savedFilename;

    codeMirror = CodeMirror.fromTextArea(editorEl, {
      lineNumbers: true,
      mode: savedLang,
      theme: savedTheme === 'default' ? undefined : savedTheme,
      indentUnit: 2,
      tabSize: 2,
      readOnly: isReadOnly ? 'nocursor' : false,
    });

    languageSel.addEventListener('change', () => {
      localStorage.setItem('syc.lang', languageSel.value);
      codeMirror.setOption('mode', languageSel.value);
    });
    themeSel.addEventListener('change', () => {
      localStorage.setItem('syc.theme', themeSel.value);
      codeMirror.setOption('theme', themeSel.value === 'default' ? undefined : themeSel.value);
    });

    codeMirror.on('change', debounce(sendUpdate, 400));
    filenameInput.addEventListener('input', () => {
      const name = filenameInput.value.trim();
      localStorage.setItem('syc.filename', name || 'snippet');
      const detected = detectModeFromFilename(name);
      if (detected) {
        languageSel.value = detected;
        localStorage.setItem('syc.lang', detected);
        codeMirror.setOption('mode', detected);
        // push language/filename meta update
        sendUpdateMeta();
      }
    });
  }

  // Clipboard link
  const copyBtn = document.getElementById('copy-link');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const url = window.location.href;
      try { await navigator.clipboard.writeText(url); copyBtn.textContent = 'Lien copié!'; }
      catch { prompt('Copiez ce lien:', url); }
      setTimeout(() => copyBtn.textContent = 'Copier le lien', 1500);
    });
  }

  if (copyReadonlyBtn) {
    copyReadonlyBtn.addEventListener('click', async () => {
      const url = new URL(window.location.href);
      url.searchParams.set('readonly', '1');
      const s = url.toString();
      try { await navigator.clipboard.writeText(s); copyReadonlyBtn.textContent = 'Lien RO copié!'; }
      catch { prompt('Copiez ce lien (RO):', s); }
      setTimeout(() => copyReadonlyBtn.textContent = 'Lien lecture seule', 1500);
    });
  }

  // Networking
  async function fetchRoom() {
    const res = await fetch(`api/room_state.php?roomId=${encodeURIComponent(roomId)}&action=get`);
    const data = await res.json();
    if (!data.ok) return;
    const { room } = data;
    if (!room) return;
    if (room.revision !== currentRevision) {
      isApplyingRemote = true;
      codeMirror.setValue(room.code || '');
      currentRevision = room.revision;
      isApplyingRemote = false;
    }
  }

  async function sendUpdate() {
    if (isReadOnly) return;
    if (isApplyingRemote) return; // don't echo remote updates back
    try {
      const payload = { action: 'update', roomId, code: codeMirror.getValue(), baseRevision: currentRevision, filename: filenameInput.value.trim() || 'snippet', language: languageSel.value };
      const res = await fetch('api/room_state.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.ok) {
        currentRevision = data.room.revision;
      } else if (data.conflict && data.room) {
        // Rebase by taking latest; naive strategy
        isApplyingRemote = true;
        codeMirror.setValue(data.room.code || '');
        currentRevision = data.room.revision;
        isApplyingRemote = false;
      }
    } catch (e) {
      // ignore transient
    }
  }

  async function sendUpdateMeta() {
    if (isReadOnly) return;
    try {
      const payload = { action: 'update', roomId, code: codeMirror.getValue(), baseRevision: currentRevision, filename: filenameInput.value.trim() || 'snippet', language: languageSel.value };
      const res = await fetch('api/room_state.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const data = await res.json();
      if (data.ok) currentRevision = data.room.revision;
    } catch {}
  }

  // Chat
  function renderChat(chat) {
    chatLog.innerHTML = '';
    const msgs = (chat && chat.messages) ? chat.messages : [];
    msgs.forEach(m => {
      const div = document.createElement('div');
      div.className = 'chat-item';
      const date = new Date((m.ts || 0) * 1000);
      div.innerHTML = `<div class="meta"><strong>${m.name || 'Anonyme'}</strong> — ${date.toLocaleTimeString()}</div>`+
                      `<div class="text">${m.text || ''}</div>`;
      chatLog.appendChild(div);
    });
    chatLog.scrollTop = chatLog.scrollHeight;
  }

  async function fetchChat() {
    if (isReadOnly) { // still fetch to view chat, but disable input below
      // continue
    }
    const res = await fetch(`api/chat.php?roomId=${encodeURIComponent(roomId)}&action=get`);
    const data = await res.json();
    if (data.ok) renderChat(data.chat);
  }

  if (isReadOnly) {
    chatForm.style.display = 'none';
  }

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (isReadOnly) return;
    const name = chatName.value.trim() || 'Anonyme';
    const text = chatText.value.trim();
    if (!text) return;
    try {
      const res = await fetch('api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ roomId, name, text })
      });
      const data = await res.json();
      if (data.ok) {
        renderChat(data.chat);
        chatText.value = '';
      }
    } catch (e) { /* ignore */ }
  });

  // Polling loops
  function startPolling() {
    fetchRoom();
    fetchChat();
    setInterval(fetchRoom, POLL_MS);
    setInterval(fetchChat, POLL_MS);
  }

  function debounce(fn, wait) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(null, args), wait); };
  }

  // Boot
  initEditor();
  // initial fetch also sets initial revision
  fetch(`api/room_state.php?roomId=${encodeURIComponent(roomId)}&action=get`).then(r => r.json()).then(d => {
    if (d.ok && d.room) {
      isApplyingRemote = true;
      codeMirror.setValue(d.room.code || '');
      currentRevision = d.room.revision || 1;
      if (d.room.filename) {
        filenameInput.value = d.room.filename;
        localStorage.setItem('syc.filename', d.room.filename);
        const detected = detectModeFromFilename(d.room.filename);
        if (detected) {
          languageSel.value = detected;
          localStorage.setItem('syc.lang', detected);
          codeMirror.setOption('mode', detected);
        }
      }
      if (d.room.language) {
        languageSel.value = d.room.language;
        localStorage.setItem('syc.lang', d.room.language);
        codeMirror.setOption('mode', d.room.language);
      }
      isApplyingRemote = false;
    }
    startPolling();
  });

  // Download
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
      const content = codeMirror.getValue();
      const name = (filenameInput.value.trim() || 'snippet');
      const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    });
  }

  function detectModeFromFilename(name) {
    const lower = (name || '').toLowerCase();
    if (lower.endsWith('.js') || lower.endsWith('.mjs') || lower.endsWith('.cjs')) return 'javascript';
    if (lower.endsWith('.ts')) return 'javascript'; // CM5 needs separate typescript addon; fallback to js
    if (lower.endsWith('.html') || lower.endsWith('.htm')) return 'htmlmixed';
    if (lower.endsWith('.css')) return 'css';
    if (lower.endsWith('.php')) return 'php';
    if (lower.endsWith('.json')) return 'javascript';
    if (lower.endsWith('.xml')) return 'xml';
    return null;
  }
})();


