(() => {
  'use strict';

  /* ---------------- 基础工具 ---------------- */
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const clamp = (n, a, b) => Math.max(a, Math.min(b, n));

  const ROLES = {
    wolf: { n: '狼人', i: '🐺', team: 'wolf', d: '每晚与队友共同袭击一名玩家。白天隐藏身份，混淆视听。' },
    seer: { n: '预言家', i: '🔮', team: 'good', d: '每晚查验一名玩家的身份，得知其是否为狼人。' },
    witch: { n: '女巫', i: '🧪', team: 'good', d: '拥有一瓶解药和一瓶毒药，每晚最多使用一瓶。' },
    hunter: { n: '猎人', i: '🎯', team: 'good', d: '被狼人杀害或被放逐时可以开枪带走一人，被毒杀时不能开枪。' },
    guard: { n: '守卫', i: '🛡️', team: 'good', d: '每晚守护一名玩家免受狼人袭击，不能连续两晚守护同一人。' },
    villager: { n: '平民', i: '👤', team: 'good', d: '没有特殊技能，靠发言和投票找出狼人。' },
  };
  const ROLE_ORDER = ['wolf', 'seer', 'witch', 'hunter', 'guard', 'villager'];

  const PRESETS = {
    5: { wolf: 1, seer: 1, witch: 1, hunter: 0, guard: 0, villager: 2 },
    6: { wolf: 2, seer: 1, witch: 1, hunter: 0, guard: 0, villager: 2 },
    7: { wolf: 2, seer: 1, witch: 1, hunter: 1, guard: 0, villager: 2 },
    8: { wolf: 2, seer: 1, witch: 1, hunter: 1, guard: 0, villager: 3 },
    9: { wolf: 3, seer: 1, witch: 1, hunter: 1, guard: 0, villager: 3 },
    10: { wolf: 3, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 3 },
    11: { wolf: 3, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 4 },
    12: { wolf: 4, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 4 },
    13: { wolf: 4, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 5 },
    14: { wolf: 4, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 6 },
    15: { wolf: 5, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 6 },
    16: { wolf: 5, seer: 1, witch: 1, hunter: 1, guard: 1, villager: 7 },
  };

  const S = {
    screen: 'home',
    nick: localStorage.getItem('lrs_nick') || '',
    code: '',
    token: '',
    st: null,
    cfg: null,
    cfgTimer: null,
    sel: null,
    mode: null,
    draft: '',
    actionKey: '',
    viewKey: '',
    clockOffset: 0,
    pollTimer: null,
    busy: false,
    rooms: [],
    history: [],
    modal: '',
    lastChatLen: 0,
  };

  /* ---------------- API ---------------- */
  async function api(action, data) {
    const res = await fetch('api.php?action=' + encodeURIComponent(action), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data || {}),
      cache: 'no-store',
    });
    let j = null;
    try { j = await res.json(); } catch (e) { throw new Error('网络异常，请重试'); }
    if (!res.ok || !j.ok) {
      const err = new Error(j.error || '请求失败');
      err.status = res.status;
      throw err;
    }
    return j;
  }

  let toastTimer = null;
  function toast(msg, isErr = false) {
    const el = $('#toast');
    el.textContent = msg;
    el.className = 'toast show' + (isErr ? ' err' : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.className = 'toast'; }, 2600);
  }

  /* ---------------- 会话 ---------------- */
  function tokenKey(code) { return 'lrs_tok_' + code; }

  function enterRoom(code, token, seat) {
    S.code = code.toUpperCase();
    S.token = token;
    localStorage.setItem(tokenKey(S.code), token);
    S.screen = 'room';
    S.st = null;
    S.cfg = null;
    S.sel = null;
    S.mode = null;
    S.draft = '';
    S.viewKey = '';
    S.lastChatLen = 0;
    history.replaceState(null, '', location.pathname + '?room=' + S.code);
    startPolling(true);
    render();
  }

  function leaveLocal() {
    stopPolling();
    S.screen = 'home';
    S.code = '';
    S.token = '';
    S.st = null;
    S.cfg = null;
    S.viewKey = '';
    history.replaceState(null, '', location.pathname);
    loadHome();
    render();
  }

  function startPolling(now = false) {
    stopPolling();
    if (now) poll();
    else S.pollTimer = setTimeout(poll, 400);
  }

  function stopPolling() {
    if (S.pollTimer) clearTimeout(S.pollTimer);
    S.pollTimer = null;
  }

  async function poll() {
    if (!S.code || !S.token) return;
    try {
      const r = await api('state', { code: S.code, token: S.token });
      S.st = r.state;
      S.clockOffset = r.server_now - Date.now();
      if (S.screen !== 'room') S.screen = 'room';
      render();
      updateClock();
    } catch (e) {
      if (e.status === 401 || e.status === 404) {
        localStorage.removeItem(tokenKey(S.code));
        toast(e.message || '房间已失效', true);
        leaveLocal();
        return;
      }
    }
    if (S.code) {
      const delay = document.hidden ? 4000 : 1200;
      S.pollTimer = setTimeout(poll, delay);
    }
  }

  function pollNow() {
    if (S.pollTimer) clearTimeout(S.pollTimer);
    S.pollTimer = setTimeout(poll, 150);
  }

  /* ---------------- 启动 ---------------- */
  function boot() {
    const code = (new URL(location.href).searchParams.get('room') || '').toUpperCase();
    loadHome();
    if (code) {
      const token = localStorage.getItem(tokenKey(code)) || '';
      if (token) {
        S.screen = 'room';
        S.code = code;
        S.token = token;
        render();
        startPolling(true);
        return;
      }
      S.pendingCode = code;
    }
    render();
    renderModal();
  }

  async function loadHome() {
    try {
      const [r1, r2] = await Promise.all([api('rooms', {}), api('history', {})]);
      S.rooms = r1.rooms || [];
      S.history = r2.history || [];
      if (S.screen === 'home') render();
    } catch (e) { /* 忽略 */ }
  }

  /* ---------------- 渲染 ---------------- */
  function render() {
    const app = $('#app');
    if (!app) return;
    let html = '';
    if (S.screen === 'home') html = homeView();
    else if (!S.st) html = loadingView();
    else if (S.st.status === 'lobby') html = lobbyView();
    else html = gameView();

    const chat = $('#chat');
    const atBottom = chat ? chat.scrollTop + chat.clientHeight >= chat.scrollHeight - 50 : true;
    const active = document.activeElement;
    const activeId = active && (active.id === 'speech-input' || active.id === 'nick' || active.id === 'joincode') ? active.id : '';
    const caret = activeId ? active.selectionStart : null;

    app.innerHTML = html;

    if (activeId) {
      const el = document.getElementById(activeId);
      if (el) {
        el.focus();
        if (caret !== null && el.setSelectionRange) {
          try { el.setSelectionRange(caret, caret); } catch (e) {}
        }
      }
    }
    const chat2 = $('#chat');
    if (chat2) {
      if (atBottom) chat2.scrollTop = chat2.scrollHeight;
      S.lastChatLen = S.st ? S.st.chat.length : 0;
    }
    bindInputs();
    renderModal();
  }

  function loadingView() {
    return `<div class="app" style="display:flex;align-items:center;justify-content:center;min-height:70vh">
      <div class="center"><div style="font-size:40px" class="float">🐺</div><div class="muted mt10">正在进入房间…</div></div></div>`;
  }

  function homeView() {
    const pending = S.pendingCode || '';
    const roomList = S.rooms.length
      ? S.rooms.map((r) => `<div class="room-item">
          <div><span class="room-code">${esc(r.code)}</span>
            <span class="tiny muted" style="margin-left:8px">${esc(r.host)}</span></div>
          <div class="row" style="gap:8px"><span class="tiny muted">${r.players}/${r.seats}</span>
          <button class="btn sm" data-act="quick" data-code="${esc(r.code)}">加入</button></div>
        </div>`).join('')
      : `<div class="tiny muted" style="padding:10px 2px">暂时没有等待中的房间，创建一个吧。</div>`;
    const history = S.history.length
      ? S.history.map((h) => `<div class="history-item">
          <span class="${h.winner === 'wolf' ? 'badge wolf' : 'badge good'}">${h.winner === 'wolf' ? '狼人胜' : '好人胜'}</span>
          <span style="margin-left:8px">${h.days} 天 · ${h.players} 人 · 狼：${esc(h.wolves)}</span>
          <span class="muted" style="float:right">房间 ${esc(h.code)}</span>
        </div>`).join('')
      : `<div class="tiny muted" style="padding:10px 2px">还没有对局记录。</div>`;

    return `
    <div class="home-head">
      <div class="moon">🐺</div>
      <h1>狼人杀</h1>
      <p>真人联机 · AI 陪玩 · 自由配置</p>
    </div>
    <div class="home-grid">
      <div class="card">
        <div class="field-label">你的昵称</div>
        <input id="nick" class="input" maxlength="12" placeholder="给自己起个名字" value="${esc(S.nick)}">
        <button class="btn primary block lg mt16" data-act="create">创建房间</button>
        <div class="mt16">
          <div class="field-label">输入房间号加入</div>
          <div class="row">
            <input id="joincode" class="input" maxlength="4" placeholder="如 A3F9" value="${esc(pending)}"
              style="text-transform:uppercase;letter-spacing:4px;font-weight:700;font-family:ui-monospace,monospace">
            <button class="btn" data-act="join">加入</button>
          </div>
        </div>
        <div class="mt16 tiny muted">支持 5–16 人，可自选角色配置，空位自动补 AI 机器人，机器人由服务器本地大模型生成发言。</div>
      </div>
      <div class="card">
        <div class="row between"><b>等待中的房间</b><button class="btn sm ghost" data-act="refresh">刷新</button></div>
        <div class="quick-rooms mt10">${roomList}</div>
      </div>
    </div>
    <div class="card mt16">
      <div class="row between"><b>最近战绩</b><button class="btn sm ghost" data-act="rules">玩法说明</button></div>
      <div class="mt6">${history}</div>
    </div>
    <div class="center tiny muted mt24">狼人杀 · 联机版 &nbsp;|&nbsp; 建议使用语音沟通，效果更佳</div>`;
  }

  function lobbyView() {
    const st = S.st;
    if (!S.cfg) S.cfg = JSON.parse(JSON.stringify(st.config));
    const cfg = S.cfg;
    const isHost = st.me.is_host === 1;
    const shown = isHost ? cfg : st.config;
    const players = st.players;
    const hostSeat = st.host_seat;

    const seatMap = {};
    players.forEach((p) => { seatMap[p.seat] = p; });
    let seatsHtml = '';
    for (let i = 0; i < shown.seats; i++) {
      const p = seatMap[i];
      if (p) {
        const canRemove = isHost && i !== hostSeat;
        seatsHtml += `<div class="seat occupied">
          <span class="num">${i}号</span>
          ${canRemove ? `<button class="kick" data-act="remove" data-seat="${i}" title="移除">✕</button>` : ''}
          <div class="avat">${esc(p.avatar)}</div>
          <div class="pname">${esc(p.name)}</div>
          <div class="flags">
            ${p.is_host ? '<span class="badge host">房主</span>' : ''}
            ${p.is_bot ? '<span class="badge bot">BOT</span>' : ''}
          </div>
        </div>`;
      } else {
        seatsHtml += `<div class="seat empty" ${isHost ? `data-act="addbot" data-seat="${i}"` : ''}>
          <div style="font-size:22px">＋</div><div class="tiny">空位</div>
          ${isHost ? '<div class="tiny" style="font-size:10px">点此加机器人</div>' : ''}
        </div>`;
      }
    }

    const roleRows = ROLE_ORDER.map((r) => {
      const def = ROLES[r];
      const limit = ['seer', 'witch', 'hunter', 'guard'].includes(r) ? 1 : (r === 'wolf' ? 8 : 12);
      const dis = !isHost || (limit === 1 && shown.roles[r] === 1);
      return `<div class="role-row ${isHost ? '' : 'disabled'}">
        <div class="rn"><span>${def.i}</span><span>${def.n}</span></div>
        <div class="stepper">
          <button data-act="role" data-role="${r}" data-delta="-1" ${!isHost || shown.roles[r] <= 0 ? 'disabled' : ''}>−</button>
          <span class="cnt">${shown.roles[r] ?? 0}</span>
          <button data-act="role" data-role="${r}" data-delta="1" ${dis ? 'disabled' : ''}>＋</button>
        </div>
      </div>`;
    }).join('');

    const sum = ROLE_ORDER.reduce((a, r) => a + (shown.roles[r] || 0), 0);
    const okSum = sum === shown.seats;
    const wolfOk = (shown.roles.wolf || 0) < (shown.seats - (shown.roles.wolf || 0));

    return `
    <div class="topbar">
      <div class="brand"><span class="logo">🐺</span><div>狼人杀<small>LOBBY</small></div></div>
      <div class="row"><button class="btn sm ghost" data-act="rules">规则</button><button class="btn sm danger" data-act="leave">退出</button></div>
    </div>
    <div class="card">
      <div class="row between wrap">
        <div>
          <div class="field-label">房间号</div>
          <div class="code-display">${esc(st.code)}</div>
        </div>
        <div class="row"><button class="btn sm" data-act="copy">复制邀请链接</button></div>
      </div>
      <div class="mt16 seat-grid">${seatsHtml}</div>
      <div class="mt10 row between">
        <span class="tiny muted">${isHost ? '点击空位添加机器人，开始时会自动补满' : '等待房主添加机器人或开始游戏'}</span>
        <span class="tiny muted">${players.length}/${shown.seats} 人</span>
      </div>
    </div>
    <div class="card mt16">
      <div class="row between">
        <b>游戏配置</b>
        ${isHost ? `<div class="row"><button class="btn sm ghost" data-act="resetcfg">恢复标准板</button></div>` : '<span class="badge">房主设置</span>'}
      </div>
      <div class="mt16 field-label">座位人数</div>
      <div class="row">
        <button class="btn sm" data-act="seats" data-delta="-1" ${!isHost || shown.seats <= 5 ? 'disabled' : ''}>−</button>
        <div style="flex:1;text-align:center;font-weight:800;font-size:20px">${shown.seats} 人</div>
        <button class="btn sm" data-act="seats" data-delta="1" ${!isHost || shown.seats >= 16 ? 'disabled' : ''}>＋</button>
      </div>
      <div class="row wrap mt10" style="gap:6px">
        ${[6, 8, 9, 10, 12].map((n) => `<button class="btn sm ghost" data-act="preset" data-n="${n}" ${!isHost ? 'disabled' : ''}>${n}人标准</button>`).join('')}
      </div>
      <div class="mt16 field-label">角色配置</div>
      <div class="role-editor">${roleRows}</div>
      <div class="sum-line">
        <span>角色总数 <b class="${okSum ? 'ok' : 'bad'}">${sum}</b> / ${shown.seats}</span>
        <span>${okSum ? (wolfOk ? '配置合法 ✓' : '狼人过多') : '人数不匹配'}</span>
      </div>
      <div class="mt16 field-label">机器人发言引擎</div>
      <div class="seg">
        <button class="${shown.ai === 'llm' ? 'on' : ''}" data-act="ai" data-v="llm" ${!isHost ? 'disabled' : ''}>本地大模型（拟真）</button>
        <button class="${shown.ai === 'rule' ? 'on' : ''}" data-act="ai" data-v="rule" ${!isHost ? 'disabled' : ''}>规则引擎（秒回）</button>
      </div>
      <div class="tiny muted mt6">${shown.ai === 'llm' ? '机器人由服务器本地 Qwen 模型生成发言，夜间阶段依然即时。' : '机器人使用内置话术，响应更快，不占用算力。'}</div>
      <div class="mt16">
        ${isHost
          ? `<button class="btn primary block lg" data-act="start" ${!okSum || !wolfOk ? 'disabled' : ''}>开始游戏${players.length < shown.seats ? `（自动补 ${shown.seats - players.length} 个机器人）` : ''}</button>`
          : `<div class="center muted small" style="padding:10px">等待房主开始游戏…</div>`}
      </div>
    </div>`;
  }

  function gameView() {
    const st = S.st;
    if (S.actionKey !== actionKey()) {
      S.actionKey = actionKey();
      S.sel = null;
      S.mode = null;
      S.draft = '';
    }

    const isHost = st.me.is_host === 1;
    const role = st.me.role ? ROLES[st.me.role] : null;
    const alive = st.players.filter((p) => p.alive).length;
    const night = st.phase.startsWith('night_') || st.phase === 'hunter';
    const over = st.status === 'over';

    let roleBanner = '';
    if (role && !over) {
      const mates = st.teammates && st.teammates.length
        ? `<div class="tiny" style="color:#ff9aac;margin-top:3px">狼队友：${st.teammates.map((s) => s + '号').join('、')}</div>` : '';
      const potion = st.me.role === 'witch'
        ? `<div class="tiny" style="color:#9cc7ff;margin-top:3px">解药 ${st.me.state.antidote ? '✓' : '✕'} &nbsp; 毒药 ${st.me.state.poison ? '✓' : '✕'}</div>` : '';
      let checks = '';
      if (st.me.role === 'seer' && st.me.state && st.me.state.checks && Object.keys(st.me.state.checks).length) {
        checks = `<div class="tiny" style="color:#c9b6ff;margin-top:3px">查验：${Object.keys(st.me.state.checks).map((s) => `${s}号 ${st.me.state.checks[s] ? '狼人' : '好人'}`).join(' · ')}</div>`;
      }
      roleBanner = `<div class="role-banner">
        <div class="ic">${role.i}</div>
        <div><div class="rn">你是 ${role.n}${st.me.alive ? '' : '（已出局）'}</div>
        <div class="rd">${role.d}</div>${mates}${potion}${checks}</div>
      </div>`;
    }

    const players = st.players.map((p) => {
      const me = p.seat === st.my_seat;
      const talking = st.turn_seat === p.seat && ['discuss', 'lastword'].includes(st.phase);
      const isWolfMate = st.me.role === 'wolf' && (st.teammates || []).includes(p.seat);
      const voted = st.vote && (st.vote.my_vote === p.seat);
      let miniRole = '';
      if (over && p.role) miniRole = `<span class="mini-role">${ROLES[p.role] ? ROLES[p.role].i : ''}</span>`;
      return `<div class="pcard ${me ? 'me' : ''} ${p.alive ? '' : 'dead'} ${talking ? 'talking' : ''} ${isWolfMate ? 'wolfmate' : ''}">
        ${voted ? '<span class="votemark">✓</span>' : ''}
        ${miniRole}
        <div class="avat">${esc(p.avatar)}</div>
        <div class="pn">${esc(p.name)}</div>
        <div class="ps">${p.seat}号${p.is_bot ? ' · AI' : ''}${p.is_host ? ' · 房主' : ''}${p.alive ? '' : ' · 出局'}</div>
      </div>`;
    }).join('');

    return `
    <div class="topbar">
      <div class="brand"><span class="logo">🐺</span><div>狼人杀<small>ROOM ${esc(st.code)}</small></div></div>
      <div class="row">
        <button class="btn sm ghost" data-act="rules">规则</button>
        <button class="btn sm ghost" data-act="leave">离开</button>
      </div>
    </div>
    <div class="hud ${night ? 'night' : 'day'}">
      <div>
        <div class="ph">${st.day > 0 ? `第 ${st.day} 天 · ` : ''}${esc(st.phase_text)}</div>
        <div class="sub">${alive} 人存活 · ${st.players.length} 人局 · ${st.ai_mode === 'rule' ? '规则AI' : '大模型AI'}</div>
      </div>
      <div class="row" style="gap:10px">
        <div class="clock" id="clock"></div>
      </div>
    </div>
    ${roleBanner ? `<div class="mb12">${roleBanner}</div>` : ''}
    <div class="players">${players}</div>
    ${over ? overPanel() : panelView()}
    <div class="chat" id="chat">${chatView()}</div>
    <div class="center tiny muted mt10">房间 ${esc(st.code)} · ${isHost ? '你是房主' : '房主 ' + hostName()}</div>`;
  }

  function hostName() {
    const h = S.st.players.find((p) => p.is_host);
    return h ? esc(h.name) : '';
  }

  function actionKey() {
    const st = S.st;
    if (!st || !st.action) return '';
    return [st.action.type, st.day, st.phase, st.turn_seat, st.action.targets ? st.action.targets.join(',') : ''].join(':');
  }

  function panelView() {
    const st = S.st;
    const a = st.action;
    const done = st.action_done;
    const phase = st.phase;

    /* 夜晚 */
    if (phase.startsWith('night_')) {
      if (a) return actionPanel(a);
      if (done) {
        return `<div class="panel"><div class="night-sky"><div class="big">🌙</div>
          <div class="txt mt6">${esc(done.text)}</div></div>
          ${st.me.role === 'wolf' && st.wolf_votes.length ? `<div class="center tiny muted mt10">狼队选择：${st.wolf_votes.map((v) => v.seat + '→' + v.target + '号').join(' · ')}</div>` : ''}
          <div class="center mt10"><span class="typing"><i></i><i></i><i></i></span></div>
        </div>`;
      }
      const hints = {
        night_guard: '守卫正在选择守护对象',
        night_wolf: '狼人正在密谋袭击目标',
        night_seer: '预言家正在查验身份',
        night_witch: '女巫正在考虑是否用药',
      };
      return `<div class="panel"><div class="night-sky"><div class="big">🌙</div>
        <div class="txt mt6">${esc(hints[phase] || '天黑请闭眼')}…</div>
        <div class="tiny muted mt6">${esc(st.phase_text)}</div></div></div>`;
    }

    /* 猎人 */
    if (phase === 'hunter') {
      if (a) return actionPanel(a);
      return `<div class="panel"><h3>🎯 猎人开枪</h3><p class="phint">等待猎人选择目标…</p></div>`;
    }

    /* 发言 / 遗言 */
    if (phase === 'discuss' || phase === 'lastword') {
      const sp = st.speech;
      const head = `<div class="panel">
        <div class="row between">
          <h3>${phase === 'lastword' ? '🕯️ 遗言' : '💬 白天发言'}</h3>
          <span class="badge">${sp ? esc(sp.name) + '（' + sp.seat + '号）' : ''}</span>
        </div>`;
      if (a && a.type === 'speak') {
        return `${head}
          <p class="phint">${esc(a.hint)}</p>
          <div class="speech-box">
            <textarea id="speech-input" maxlength="300" placeholder="输入你的发言…（可分析局势、点出怀疑对象）">${esc(S.draft)}</textarea>
            <div class="speech-actions">
              <span class="tiny muted"><span id="charcount">${S.draft.length}</span>/300</span>
              <button class="btn primary" data-act="send">发送发言</button>
            </div>
          </div></div>`;
      }
      if (sp) {
        const deaths = deathText();
        return `${head}
          <p class="phint">${sp.is_me ? '轮到你发言了' : '正在等待 ' + esc(sp.name) + ' 发言'}</p>
          ${sp.typing
            ? `<div class="row mt10" style="gap:8px"><span class="typing"><i></i><i></i><i></i></span><span class="tiny muted">${sp.is_bot ? 'AI 正在组织语言' : '正在输入'}</span></div>`
            : `<div class="tiny muted mt10">…</div>`}
          ${deaths}
          ${st.me.alive ? '' : '<div class="tiny muted mt10">你已出局，正在观战。</div>'}
        </div>`;
      }
      return `${head}<p class="phint">准备中…</p></div>`;
    }

    /* 投票 */
    if (phase === 'vote') {
      const v = st.vote || { voted: 0, total: 0 };
      const pct = v.total ? Math.round((v.voted / v.total) * 100) : 0;
      const progress = `<div class="vote-line"><div class="bar"><i style="width:${pct}%"></i></div>
        <span class="tiny muted">${v.voted}/${v.total} 已投票</span></div>`;
      if (a && a.type === 'vote') {
        return `<div class="panel"><h3>🗳️ 投票放逐</h3>
          <p class="phint">${esc(a.hint)}</p>
          <div class="target-grid">${a.targets.map((s) => targetBtn(s)).join('')}</div>
          ${progress}
          <div class="row mt16" style="gap:8px">
            <button class="btn primary grow" data-act="confirm" ${S.sel === null ? 'disabled' : ''}>确认投票</button>
            <button class="btn" data-act="vote-pass">弃票</button>
          </div></div>`;
      }
      return `<div class="panel"><h3>🗳️ 投票放逐</h3>
        <p class="phint">你已投票${v.my_vote ? '（' + v.my_vote + '号）' : '（弃票）'}，等待其他玩家…</p>
        ${progress}
        ${st.me.alive ? '' : '<div class="tiny muted mt10">你已出局，无权投票。</div>'}
      </div>`;
    }

    return `<div class="panel"><h3>${esc(st.phase_text)}</h3><p class="phint">请稍候…</p></div>`;
  }

  function deathText() {
    const r = S.st.last_result;
    if (!r || !r.deaths || !r.deaths.length) return S.st.day > 1 ? '<div class="tiny muted mt10">昨晚是平安夜。</div>' : '';
    const names = r.deaths.map((d) => {
      const p = S.st.players.find((x) => x.seat === d.seat);
      return `${esc(p ? p.name : '')}（${d.seat}号）`;
    });
    return `<div class="tiny mt10" style="color:#ff9aac">昨晚死亡：${names.join('、')}</div>`;
  }

  function targetBtn(seat) {
    const p = S.st.players.find((x) => x.seat === seat);
    if (!p) return '';
    const sel = S.sel === seat ? ' sel' : '';
    return `<button class="target${sel}" data-act="pick" data-seat="${seat}">
      <span class="avat">${esc(p.avatar)}</span><span>${esc(p.name)}</span><span class="tiny muted">${seat}号</span>
    </button>`;
  }

  function actionPanel(a) {
    if (a.type === 'speak') return panelView();

    if (a.type === 'witch') {
      if (S.mode === 'poison') {
        return `<div class="panel"><h3>🧪 使用毒药</h3>
          <p class="phint">选择要毒杀的玩家，毒药无视守护。</p>
          <div class="target-grid">${a.targets.map((s) => targetBtn(s)).join('')}</div>
          <div class="row mt16" style="gap:8px">
            <button class="btn danger grow" data-act="confirm" ${S.sel === null ? 'disabled' : ''}>确认毒杀</button>
            <button class="btn" data-act="witch-cancel">返回</button>
          </div></div>`;
      }
      const victim = (a.victim !== null && a.victim !== undefined) ? S.st.players.find((p) => p.seat === a.victim) : null;
      return `<div class="panel"><h3>🧪 女巫用药</h3>
        <p class="phint">${victim ? `今晚 ${esc(victim.name)}（${a.victim}号）被狼人袭击` : '今晚暂时没有人被袭击'}</p>
        <div class="row wrap mt16" style="gap:8px">
          ${a.can_save ? `<button class="btn primary" data-act="witch-save">使用解药救 ${a.victim}号</button>` : '<button class="btn" disabled>解药已用完</button>'}
          ${a.can_poison ? `<button class="btn danger" data-act="witch-poison">使用毒药</button>` : '<button class="btn" disabled>毒药已用完</button>'}
          <button class="btn ghost" data-act="witch-none">不用药</button>
        </div>
        <div class="tiny muted mt10">解药与毒药每晚只能用一瓶，用完即消失。</div></div>`;
    }

    const isHunter = a.type === 'hunter_shoot';
    return `<div class="panel">
      <h3>${isHunter ? '🎯 猎人开枪' : a.title}</h3>
      <p class="phint">${esc(a.hint || '点击选择目标')}</p>
      <div class="target-grid">${(a.targets || []).map((s) => targetBtn(s)).join('')}</div>
      <div class="row mt16" style="gap:8px">
        <button class="btn primary grow" data-act="confirm" ${S.sel === null ? 'disabled' : ''}>${isHunter ? '开枪' : '确认'}</button>
        ${a.can_pass ? `<button class="btn" data-act="pass">${isHunter ? '不开枪' : '跳过'}</button>` : ''}
      </div>
    </div>`;
  }

  function overPanel() {
    const st = S.st;
    const wolfWin = st.winner === 'wolf';
    const isHost = st.me.is_host === 1;
    const items = st.players.map((p) => {
      const r = ROLES[p.role] || { n: p.role, i: '❓' };
      return `<div class="reveal-item ${p.role === 'wolf' ? 'wolf' : ''} ${p.alive ? '' : 'dead'}">
        <span style="font-size:17px">${r.i}</span>
        <span class="grow">${esc(p.name)}<span class="tiny muted"> ${p.seat}号</span></span>
        <span class="tiny ${p.role === 'wolf' ? 'badge wolf' : 'badge good'}">${r.n}</span>
      </div>`;
    }).join('');
    return `<div class="panel">
      <div class="winner-banner ${wolfWin ? 'wolf' : 'good'}">
        <div class="crown">${wolfWin ? '🐺' : '🏆'}</div>
        <h2>${wolfWin ? '狼人阵营胜利' : '好人阵营胜利'}</h2>
        <div class="tiny muted">本局共 ${st.day} 天</div>
      </div>
      <div class="reveal-grid">${items}</div>
      <div class="row mt16" style="gap:8px">
        ${isHost ? '<button class="btn primary grow" data-act="restart">再来一局</button>' : ''}
        <button class="btn ${isHost ? '' : 'grow'}" data-act="leave">返回首页</button>
      </div>
    </div>`;
  }

  function chatView() {
    const st = S.st;
    let html = '';
    let lastDay = -1;
    st.chat.forEach((m) => {
      if (m.day !== lastDay && m.day > 0) {
        lastDay = m.day;
        html += `<div class="day-sep">— 第 ${m.day} 天 —</div>`;
      }
      if (m.kind === 'system') {
        const strong = /投票结果|被放逐|平安夜|死亡|获胜|游戏开始/.test(m.text);
        const death = /死亡|被放逐/.test(m.text);
        html += `<div class="sys ${death ? 'death' : strong ? 'strong' : ''}">${esc(m.text)}</div>`;
        return;
      }
      const p = st.players.find((x) => x.seat === m.seat);
      const self = m.seat === st.my_seat;
      const lw = m.kind === 'lastword';
      html += `<div class="msg-row ${self ? 'self' : ''}">
        <div class="mav">${esc(p ? p.avatar : '👤')}</div>
        <div class="body">
          <div class="meta"><span>${esc(p ? p.name : '')} · ${m.seat}号</span>${lw ? '<span class="badge wolf">遗言</span>' : ''}</div>
          <div class="bubble ${lw ? 'lastword' : ''}">${esc(m.text)}</div>
        </div>
      </div>`;
    });
    if (!html) html = '<div class="sys">游戏即将开始…</div>';
    return html;
  }

  /* ---------------- 时钟 ---------------- */
  function updateClock() {
    const el = $('#clock');
    if (!el) return;
    const st = S.st;
    if (!st || !st.phase_end) { el.textContent = ''; return; }
    const left = Math.max(0, Math.ceil((st.phase_end - (Date.now() + S.clockOffset)) / 1000));
    el.textContent = left > 0 ? left + 's' : '';
    el.classList.toggle('warn', left > 0 && left <= 10);
  }
  setInterval(updateClock, 250);

  /* ---------------- 输入绑定 ---------------- */
  function bindInputs() {
    const nick = $('#nick');
    if (nick) {
      nick.addEventListener('input', () => { S.nick = nick.value.trim(); });
      nick.addEventListener('keydown', (e) => { if (e.key === 'Enter') createRoom(); });
    }
    const jc = $('#joincode');
    if (jc) {
      jc.addEventListener('input', () => { jc.value = jc.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); });
      jc.addEventListener('keydown', (e) => { if (e.key === 'Enter') joinRoom(); });
    }
    const sp = $('#speech-input');
    if (sp) {
      sp.addEventListener('input', () => {
        S.draft = sp.value;
        const cc = $('#charcount');
        if (cc) cc.textContent = sp.value.length;
      });
      sp.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendSpeech(); }
      });
    }
  }

  function getNick() {
    const v = S.nick || (localStorage.getItem('lrs_nick') || '');
    return v.trim().slice(0, 12);
  }

  function requireNick() {
    const n = getNick();
    if (!n) { toast('请先填写昵称', true); const el = $('#nick'); if (el) el.focus(); return null; }
    localStorage.setItem('lrs_nick', n);
    S.nick = n;
    return n;
  }

  /* ---------------- 操作 ---------------- */
  async function createRoom() {
    if (S.busy) return;
    const nick = requireNick();
    if (!nick) return;
    S.busy = true;
    try {
      const cfg = { seats: 9, roles: PRESETS[9], ai: 'llm' };
      const r = await api('create', { name: nick, config: cfg });
      enterRoom(r.code, r.token, r.seat);
      toast('房间创建成功，房间号 ' + r.code);
    } catch (e) { toast(e.message, true); }
    finally { S.busy = false; }
  }

  async function joinRoom(codeArg) {
    if (S.busy) return;
    const nick = getNick();
    const code = (codeArg || ($('#joincode') ? $('#joincode').value : '') || '').trim().toUpperCase();
    if (!code) { toast('请输入房间号', true); return; }
    if (!nick) { toast('请先填写昵称', true); const el = $('#nick'); if (el) el.focus(); return; }
    localStorage.setItem('lrs_nick', nick);
    S.nick = nick;
    S.busy = true;
    try {
      const saved = localStorage.getItem(tokenKey(code)) || '';
      const r = await api('join', { code, name: nick, token: saved });
      enterRoom(r.code, r.token, r.seat);
      toast('已加入房间 ' + r.code);
    } catch (e) { toast(e.message, true); }
    finally { S.busy = false; }
  }

  async function sendSpeech() {
    const ta = $('#speech-input');
    if (!ta) return;
    const text = ta.value.trim();
    if (text.length < 2) { toast('多说两句吧', true); return; }
    if (S.busy) return;
    S.busy = true;
    try {
      await api('action', { code: S.code, token: S.token, type: 'speak', text });
      S.draft = '';
      if (ta) ta.value = '';
      pollNow();
    } catch (e) { toast(e.message, true); }
    finally { S.busy = false; }
  }

  async function doAction(type, target) {
    if (S.busy) return;
    S.busy = true;
    try {
      await api('action', { code: S.code, token: S.token, type, target });
      S.sel = null;
      S.mode = null;
      pollNow();
    } catch (e) { toast(e.message, true); }
    finally { S.busy = false; }
  }

  function saveConfig() {
    const cfg = S.cfg;
    if (!cfg) return;
    clearTimeout(S.cfgTimer);
    S.cfgTimer = setTimeout(async () => {
      try {
        const r = await api('config', { code: S.code, token: S.token, config: cfg });
        if (r.config) S.cfg = r.config;
      } catch (e) { toast(e.message, true); }
    }, 350);
  }

  function adjustRole(role, delta) {
    const cfg = S.cfg;
    if (!cfg) return;
    const roles = { ...cfg.roles };
    const max = ['seer', 'witch', 'hunter', 'guard'].includes(role) ? 1 : (role === 'wolf' ? 8 : 12);
    const next = clamp((roles[role] || 0) + delta, 0, max);
    if (next === roles[role]) return;
    const old = roles[role] || 0;
    roles[role] = next;
    const sum0 = ROLE_ORDER.reduce((a, r) => a + (roles[r] || 0), 0);
    const diff = cfg.seats - sum0;
    if (role !== 'villager') {
      const v = (roles.villager || 0) + diff;
      if (v < 0) { toast('平民数量不足，请先调整其他角色', true); return; }
      roles.villager = v;
    } else if (sum0 !== cfg.seats) {
      roles[role] = old;
      return;
    }
    cfg.roles = roles;
    S.viewKey = '';
    saveConfig();
    render();
  }

  function setSeats(n) {
    const cfg = S.cfg;
    cfg.seats = clamp(n, 5, 16);
    cfg.roles = { ...PRESETS[cfg.seats] };
    S.viewKey = '';
    saveConfig();
    render();
  }

  /* ---------------- 事件 ---------------- */
  document.addEventListener('click', async (e) => {
    const el = e.target.closest('[data-act]');
    if (!el) return;
    const act = el.dataset.act;

    switch (act) {
      case 'create': createRoom(); break;
      case 'join': joinRoom(); break;
      case 'quick': joinRoom(el.dataset.code); break;
      case 'refresh': loadHome(); toast('已刷新'); break;
      case 'copy': {
        const url = location.origin + location.pathname + '?room=' + S.code;
        try { await navigator.clipboard.writeText(url); toast('邀请链接已复制'); }
        catch (err) { toast(url); }
        break;
      }
      case 'leave': {
        if (S.st && S.st.status === 'playing' && !confirm('游戏进行中，离开后将由机器人托管，确定离开？')) return;
        try { await api('leave', { code: S.code, token: S.token }); } catch (err) {}
        localStorage.removeItem(tokenKey(S.code));
        leaveLocal();
        break;
      }
      case 'addbot': {
        if (S.st.me.is_host !== 1) break;
        try { await api('add_bot', { code: S.code, token: S.token }); pollNow(); } catch (err) { toast(err.message, true); }
        break;
      }
      case 'remove': {
        const seat = +el.dataset.seat;
        const p = S.st.players.find((x) => x.seat === seat);
        try {
          await api(p && p.is_bot ? 'remove_bot' : 'kick', { code: S.code, token: S.token, seat });
          pollNow();
        } catch (err) { toast(err.message, true); }
        break;
      }
      case 'start': {
        if (S.busy) break;
        S.busy = true;
        try {
          clearTimeout(S.cfgTimer);
          await api('config', { code: S.code, token: S.token, config: S.cfg });
          await api('start', { code: S.code, token: S.token });
          S.lastChatLen = 0;
          pollNow();
        } catch (err) { toast(err.message, true); }
        finally { S.busy = false; }
        break;
      }
      case 'restart': {
        try { await api('restart', { code: S.code, token: S.token }); S.viewKey = ''; S.lastChatLen = 0; pollNow(); }
        catch (err) { toast(err.message, true); }
        break;
      }
      case 'resetcfg': {
        setSeats(S.cfg ? S.cfg.seats : 9);
        break;
      }
      case 'seats': {
        if (!S.cfg) break;
        setSeats(S.cfg.seats + (+el.dataset.delta));
        break;
      }
      case 'preset': {
        setSeats(+el.dataset.n);
        break;
      }
      case 'role': {
        adjustRole(el.dataset.role, +el.dataset.delta);
        break;
      }
      case 'ai': {
        S.cfg.ai = el.dataset.v;
        saveConfig();
        render();
        break;
      }
      case 'pick': {
        S.sel = +el.dataset.seat;
        $$('.target').forEach((t) => t.classList.toggle('sel', +t.dataset.seat === S.sel));
        const btn = $('[data-act="confirm"]');
        if (btn) btn.disabled = false;
        break;
      }
      case 'confirm': {
        const a = S.st.action;
        if (!a || S.sel === null) break;
        const map = { witch: 'witch_poison', hunter_shoot: 'hunter_shoot' };
        const type = map[a.type] || a.type;
        doAction(type, S.sel);
        break;
      }
      case 'pass': {
        const a = S.st.action;
        const type = a && a.type === 'hunter_shoot' ? 'hunter_shoot' : 'vote';
        doAction(type, -1);
        break;
      }
      case 'vote-pass': doAction('vote', -1); break;
      case 'witch-save': doAction('witch_save', S.st.action.victim); break;
      case 'witch-none': doAction('witch_none', 0); break;
      case 'witch-poison': S.mode = 'poison'; S.sel = null; S.viewKey = ''; render(); break;
      case 'witch-cancel': S.mode = null; S.sel = null; render(); break;
      case 'send': sendSpeech(); break;
      case 'rules': S.modal = 'rules'; renderModal(); break;
      case 'close-modal': S.modal = ''; renderModal(); break;
      default: break;
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && S.modal) { S.modal = ''; renderModal(); }
  });

  /* ---------------- 弹层 ---------------- */
  function renderModal() {
    const wrap = $('#modal');
    if (!wrap) return;
    if (!S.modal) {
      wrap.hidden = true;
      wrap.innerHTML = '';
      wrap.dataset.mode = '';
      return;
    }
    if (wrap.dataset.mode === S.modal) return;
    wrap.dataset.mode = S.modal;
    wrap.hidden = false;
    if (S.modal === 'rules') {
      wrap.innerHTML = `<div class="modal">
        <h3>🐺 玩法说明</h3>
        <div class="rules-list">
          <p><b>阵营：</b>狼人每晚可以共同袭击一名玩家；好人通过发言和投票找出所有狼人。狼人全部出局则好人胜；狼人数 ≥ 好人数则狼人胜。</p>
          <p><b>夜晚顺序：</b>守卫守护 → 狼人袭击 → 预言家查验 → 女巫用药。女巫解药可救当晚被袭击者，毒药可毒杀一人，每晚限用一瓶；同守同救会死亡。</p>
          <p><b>白天：</b>公布死讯 → 依次发言 → 投票放逐。平票则本轮无人出局。</p>
          <p><b>猎人：</b>被狼人杀害或被放逐时可以开枪；被女巫毒杀时不能开枪。</p>
          <p><b>机器人：</b>空位会自动补 AI。大模型模式由服务器本地 Qwen 模型结合局势与性格生成发言；规则模式使用内置话术，响应更快。</p>
          <p><b>提示：</b>发言阶段轮到你时输入内容发送；夜间按提示选择目标。掉线后座位由机器人托管。</p>
        </div>
        <button class="btn primary block mt16" data-act="close-modal">知道了</button>
      </div>`;
      wrap.addEventListener('click', (e) => {
        if (e.target === wrap) { S.modal = ''; renderModal(); }
      }, { once: true });
    }
  }

  boot();
  setInterval(() => { if (S.screen === 'home') loadHome(); }, 15000);
})();
