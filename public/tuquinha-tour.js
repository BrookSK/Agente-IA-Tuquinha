(function () {
  'use strict';

  function qs(sel, root) {
    try { return (root || document).querySelector(sel); } catch (e) { return null; }
  }

  function qsa(sel, root) {
    try { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); } catch (e) { return []; }
  }

  function now() {
    return Date.now ? Date.now() : 0;
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function safeLocalStorage() {
    try {
      if (!window.localStorage) return null;
      var k = '__tuq_ls_test__' + String(now());
      localStorage.setItem(k, '1');
      localStorage.removeItem(k);
      return localStorage;
    } catch (e) {
      return null;
    }
  }

  function safeSessionStorage() {
    try {
      if (!window.sessionStorage) return null;
      var k = '__tuq_ss_test__' + String(now());
      sessionStorage.setItem(k, '1');
      sessionStorage.removeItem(k);
      return sessionStorage;
    } catch (e) {
      return null;
    }
  }

  function normalizePath(pathname) {
    var p = String(pathname || '/');
    if (p.length > 1 && p.endsWith('/')) p = p.slice(0, -1);
    return p;
  }

  function getPathnameFromUrl(url) {
    try {
      var u = String(url || '');
      var q = u.indexOf('?');
      if (q >= 0) u = u.slice(0, q);
      return normalizePath(u || '/');
    } catch (e) {
      return '/';
    }
  }

  function getConfig() {
    try {
      var cfg = window.TUQ_TOUR_CONFIG || {};
      return {
        onboarding: !!cfg.onboarding,
        force: !!cfg.force,
        allowFab: !!cfg.allowFab
      };
    } catch (e) {
      return { onboarding: false, force: false, allowFab: false };
    }
  }

  function readJson(ls, key) {
    try {
      if (!ls) return null;
      var raw = ls.getItem(key);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function writeJson(ls, key, val) {
    try {
      if (!ls) return;
      ls.setItem(key, JSON.stringify(val));
    } catch (e) {}
  }

  function getQueryParam(name) {
    try {
      var sp = new URLSearchParams(String(window.location.search || ''));
      return sp.get(name);
    } catch (e) {
      return null;
    }
  }

  function removeQueryParams(paramNames) {
    try {
      if (!window.history || !window.history.replaceState) return;
      var url = new URL(window.location.href);
      for (var i = 0; i < paramNames.length; i++) {
        url.searchParams.delete(paramNames[i]);
      }
      // Mantém pathname + query restante + hash
      var next = url.pathname + (url.search || '') + (url.hash || '');
      window.history.replaceState({}, '', next);
    } catch (e) {}
  }

  function appendOnboardingParams(url, idx) {
    try {
      var u = String(url || '/');
      var sep = u.indexOf('?') >= 0 ? '&' : '?';
      return u + sep + 'tuq_onb=1&tuq_idx=' + encodeURIComponent(String(idx || 0)) + '&tuq_ts=' + encodeURIComponent(String(now()));
    } catch (e) {
      return url;
    }
  }

  function buildOnboardingFlowFromDom() {
    // Sempre começa na Home
    var flow = ['/'];

    // Ordem sugerida: Home -> Novo chat (personalidades ou chat) -> Projetos -> Histórico -> Planos -> Cursos -> Minha conta
    var newChat = qs('[data-tour="nav-new-chat"]');
    if (newChat && newChat.getAttribute('href')) {
      var href = String(newChat.getAttribute('href') || '');
      if (href.indexOf('/personalidades') === 0) {
        flow.push('/personalidades');
        flow.push('/chat?new=1');
      } else {
        flow.push('/chat?new=1');
      }
    } else {
      flow.push('/chat?new=1');
    }

    if (qs('[data-tour="nav-projects"]')) flow.push('/projetos');
    if (qs('[data-tour="nav-history"]')) flow.push('/historico');
    if (qs('[data-tour="nav-plans"]')) flow.push('/planos');
    if (qs('[data-tour="nav-courses"]')) flow.push('/cursos');
    if (qs('[data-tour="nav-account"]')) flow.push('/conta');

    // Remove duplicados mantendo ordem
    var out = [];
    for (var i = 0; i < flow.length; i++) {
      if (out.indexOf(flow[i]) === -1) out.push(flow[i]);
    }
    return out;
  }

  function getOnboardingFlow() {
    var ls = safeLocalStorage();
    var stored = readJson(ls, 'tuq_onboarding_flow');
    if (stored && stored.length) return stored;
    var flow = buildOnboardingFlowFromDom();
    writeJson(ls, 'tuq_onboarding_flow', flow);
    return flow;
  }

  function clearAllToursDone() {
    var ls = safeLocalStorage();
    if (!ls) return;
    try {
      for (var i = ls.length - 1; i >= 0; i--) {
        var k = ls.key(i);
        if (!k) continue;
        if (String(k).indexOf('tuq_tour_done:') === 0) {
          ls.removeItem(k);
        }
      }
    } catch (e) {}
  }

  function onboardingState() {
    var ls = safeLocalStorage();
    if (!ls) return { active: false, idx: 0 };
    var active = ls.getItem('tuq_onboarding_active') === '1';
    var idx = parseInt(ls.getItem('tuq_onboarding_idx') || '0', 10);
    if (!isFinite(idx) || idx < 0) idx = 0;
    var startedAt = parseInt(ls.getItem('tuq_onboarding_started_at') || '0', 10);
    if (!isFinite(startedAt) || startedAt < 0) startedAt = 0;
    return { active: active, idx: idx, startedAt: startedAt };
  }

  function setOnboarding(active, idx) {
    var ls = safeLocalStorage();
    if (!ls) return;
    if (active) {
      ls.setItem('tuq_onboarding_active', '1');
      ls.setItem('tuq_onboarding_idx', String(idx || 0));
      if (!ls.getItem('tuq_onboarding_started_at')) {
        ls.setItem('tuq_onboarding_started_at', String(now()));
      }
      return;
    }
    ls.removeItem('tuq_onboarding_active');
    ls.removeItem('tuq_onboarding_idx');
    ls.removeItem('tuq_onboarding_flow');
    ls.removeItem('tuq_onboarding_started_at');
  }

  function finishOnboarding() {
    try {
      clearAllToursDone();
    } catch (e) {}
    setOnboarding(false, 0);
  }

  function shouldAbortRedirectLoop() {
    // Proteção contra loop infinito de redirecionamento
    var ss = safeSessionStorage();
    if (!ss) return false;
    try {
      var count = parseInt(ss.getItem('tuq_tour_redirect_count') || '0', 10);
      var ts = parseInt(ss.getItem('tuq_tour_redirect_ts') || '0', 10);
      if (!isFinite(count) || count < 0) count = 0;
      if (!isFinite(ts) || ts < 0) ts = 0;
      var t = now();

      if (!ts || (t - ts) > 8000) {
        count = 0;
        ts = t;
      }

      count += 1;
      ss.setItem('tuq_tour_redirect_count', String(count));
      ss.setItem('tuq_tour_redirect_ts', String(ts));

      return count >= 5;
    } catch (e) {
      return false;
    }
  }

  // Tours por página
  var TOURS = {
    '/': {
      id: 'home_v1',
      title: 'Tour: Início',
      steps: [
        {
          selector: '[data-tour="home-about"]',
          title: 'Quem é o Tuquinha',
          text: 'Aqui você entende o que é o Tuquinha e como ele te ajuda no dia a dia com branding.'
        },
        {
          selector: '[data-tour="home-guides"]',
          title: 'Guias rápidos',
          text: 'Aqui ficam guias práticos para você aplicar a metodologia nos seus projetos.'
        },
        {
          selector: '[data-tour="home-cta-chat"]',
          title: 'Começar um chat',
          text: 'Clique aqui quando quiser iniciar um papo com o Tuquinha.'
        }
      ]
    },
    '/personalidades': {
      id: 'personalidades_v1',
      title: 'Tour: Personalidades',
      steps: [
        {
          selector: 'h1',
          title: 'Escolha uma personalidade',
          text: 'Cada personalidade muda o foco e o jeito do Tuquinha. Aqui você escolhe como ele vai te ajudar no próximo chat.'
        },
        {
          selector: '#persona-carousel',
          title: 'Navegue pelas personalidades',
          text: 'Use o carrossel para ver as opções disponíveis. Você pode navegar e escolher a que combina com o que você precisa agora.'
        },
        {
          selector: '#persona-next',
          title: 'Próxima personalidade',
          text: 'Clique aqui para avançar no carrossel e ver a próxima personalidade.'
        }
      ]
    },
    '/chat': {
      id: 'chat_v1',
      title: 'Tour: Chat',
      steps: [
        {
          selector: '#chat-message',
          title: 'Escreva sua mensagem',
          text: 'Digite aqui sua pergunta/pedido. Quanto mais contexto, melhor a resposta.'
        },
        {
          selector: '#chat-send-btn',
          title: 'Enviar',
          text: 'Clique para enviar e iniciar a conversa com o Tuquinha.'
        },
        {
          selector: '#tuqChatMenuBtn',
          title: 'Opções do chat',
          text: 'Aqui você encontra ações como favoritar, renomear e outras opções do chat.'
        }
      ]
    },
    '/projetos': {
      id: 'projetos_v1',
      title: 'Tour: Projetos',
      steps: [
        {
          selector: 'h1',
          title: 'Seus projetos',
          text: 'Aqui você organiza seus trabalhos e conversas do Tuquinha por projeto.'
        },
        {
          selector: 'a[href="/projetos/novo"]',
          title: 'Criar novo projeto',
          text: 'Clique aqui para criar um novo projeto.'
        },
        {
          selector: '#projectsSearch',
          title: 'Buscar projetos',
          text: 'Use a busca para encontrar projetos rapidamente.'
        },
        {
          selector: '#projectsGrid',
          title: 'Lista de projetos',
          text: 'Aqui ficam seus projetos. Clique em um card para abrir.'
        }
      ]
    },
    '/historico': {
      id: 'historico_v1',
      title: 'Tour: Histórico',
      steps: [
        {
          selector: 'h1',
          title: 'Histórico de conversas',
          text: 'Aqui ficam seus chats recentes. Você pode abrir, buscar e excluir conversas.'
        },
        {
          selector: 'form[action="/historico"]',
          title: 'Buscar e filtrar',
          text: 'Use a busca e o filtro de favoritos para achar conversas rapidamente.'
        },
        {
          selector: 'a[href^="/chat?c="]',
          title: 'Abrir um chat',
          text: 'Clique em “Abrir chat” para voltar para a conversa.'
        }
      ]
    },
    '/planos': {
      id: 'planos_v1',
      title: 'Tour: Planos',
      steps: [
        {
          selector: 'h1',
          title: 'Planos e limites',
          text: 'Aqui você compara planos e entende seus limites (tokens, acesso a recursos etc.).'
        },
        {
          selector: '#plans-paid-wrapper',
          title: 'Opções de plano',
          text: 'Aqui ficam as opções de plano disponíveis. Você pode alternar por ciclo e escolher o melhor pra você.'
        }
      ]
    },
    '/cursos': {
      id: 'cursos_v1',
      title: 'Tour: Cursos',
      steps: [
        {
          selector: 'h1',
          title: 'Cursos do Tuquinha',
          text: 'Aqui você encontra cursos disponíveis pelo seu plano ou para compra avulsa.'
        },
        {
          selector: '.course-card',
          title: 'Cards de cursos',
          text: 'Clique em um curso para ver detalhes e assistir/acompanhar o conteúdo.'
        }
      ]
    },
    '/conta': {
      id: 'conta_v1',
      title: 'Tour: Minha conta',
      steps: [
        {
          selector: '#tuq-refazer-tour',
          title: 'Refazer tour quando quiser',
          text: 'Se você quiser rever o guia, é só clicar aqui e o Tuquinha te leva de novo pelas telas principais.'
        },
        {
          selector: 'form[action="/conta"]',
          title: 'Dados da conta',
          text: 'Aqui você ajusta seu nome, como o Tuquinha deve te chamar e define memórias e regras globais.'
        }
      ]
    }
  };

  function getTourForCurrentPage() {
    var path = normalizePath(window.location.pathname);
    return TOURS[path] || null;
  }

  function createEl(tag, attrs) {
    var el = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === 'style' && attrs.style && typeof attrs.style === 'object') {
          Object.keys(attrs.style).forEach(function (sk) { el.style[sk] = attrs.style[sk]; });
          return;
        }
        if (k === 'text') {
          el.textContent = String(attrs.text);
          return;
        }
        el.setAttribute(k, String(attrs[k]));
      });
    }
    return el;
  }

  function TourRunner(tour) {
    this.tour = tour;
    this.idx = 0;
    this.active = false;
    this._missingRetries = 0;
    this.overlay = null;
    this.hole = null;
    this.tooltip = null;
    this.titleEl = null;
    this.counterEl = null;
    this.textEl = null;
    this.btnPrev = null;
    this.btnNext = null;
    this.btnSkip = null;
    this.btnClose = null;
    this._boundReposition = null;
  }

  TourRunner.prototype._ensureUi = function () {
    if (this.overlay) return;

    var overlay = createEl('div', { 'data-tuquinha-tour': 'overlay' });
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.zIndex = '99999';
    overlay.style.pointerEvents = 'auto';

    var hole = createEl('div', { 'data-tuquinha-tour': 'hole' });
    hole.style.position = 'fixed';
    hole.style.left = '0';
    hole.style.top = '0';
    hole.style.width = '10px';
    hole.style.height = '10px';
    hole.style.borderRadius = '14px';
    hole.style.boxShadow = '0 0 0 9999px rgba(0,0,0,0.72)';
    hole.style.outline = '2px solid rgba(255,255,255,0.12)';
    hole.style.pointerEvents = 'none';
    hole.style.transition = 'all 180ms ease';

    var tooltip = createEl('div', { 'data-tuquinha-tour': 'tooltip' });
    tooltip.style.position = 'fixed';
    tooltip.style.maxWidth = 'min(360px, calc(100vw - 16px))';
    tooltip.style.background = 'rgba(17,17,24,0.98)';
    tooltip.style.border = '1px solid rgba(255,255,255,0.12)';
    tooltip.style.borderRadius = '14px';
    tooltip.style.boxShadow = '0 18px 45px rgba(0,0,0,0.45)';
    tooltip.style.padding = '12px 12px 10px 12px';
    tooltip.style.color = '#f5f5f5';
    tooltip.style.fontFamily = 'system-ui, -apple-system, Segoe UI, sans-serif';

    var header = createEl('div', { 'data-tuquinha-tour': 'header' });
    header.style.display = 'flex';
    header.style.alignItems = 'baseline';
    header.style.justifyContent = 'space-between';
    header.style.gap = '10px';
    header.style.marginBottom = '6px';

    var title = createEl('div', { 'data-tuquinha-tour': 'title' });
    title.style.fontSize = '14px';
    title.style.fontWeight = '750';

    var counter = createEl('div', { 'data-tuquinha-tour': 'counter' });
    counter.style.fontSize = '11px';
    counter.style.color = 'rgba(245,245,245,0.65)';
    counter.style.whiteSpace = 'nowrap';

    header.appendChild(title);
    header.appendChild(counter);

    var text = createEl('div', { 'data-tuquinha-tour': 'text' });
    text.style.fontSize = '12.5px';
    text.style.color = 'rgba(245,245,245,0.82)';
    text.style.lineHeight = '1.45';
    text.style.marginBottom = '10px';

    var actions = createEl('div', { 'data-tuquinha-tour': 'actions' });
    actions.style.display = 'flex';
    actions.style.gap = '8px';
    actions.style.alignItems = 'center';
    actions.style.justifyContent = 'space-between';

    var left = createEl('div');
    left.style.display = 'flex';
    left.style.gap = '8px';

    var right = createEl('div');
    right.style.display = 'flex';
    right.style.gap = '8px';

    function mkBtn(label, variant) {
      var btn = createEl('button', { type: 'button' });
      btn.textContent = label;
      btn.style.border = '1px solid rgba(255,255,255,0.12)';
      btn.style.borderRadius = '999px';
      btn.style.padding = '7px 10px';
      btn.style.fontSize = '12px';
      btn.style.cursor = 'pointer';
      btn.style.background = 'transparent';
      btn.style.color = '#f5f5f5';
      if (variant === 'primary') {
        btn.style.border = 'none';
        btn.style.background = 'linear-gradient(135deg, #e53935, #ff6f60)';
        btn.style.color = '#050509';
        btn.style.fontWeight = '750';
      }
      if (variant === 'danger') {
        btn.style.color = '#ffbaba';
      }
      return btn;
    }

    var btnPrev = mkBtn('Voltar');
    var btnNext = mkBtn('Próximo', 'primary');
    var btnSkip = mkBtn('Pular', 'danger');
    var btnClose = mkBtn('Fechar');

    left.appendChild(btnPrev);
    left.appendChild(btnNext);
    right.appendChild(btnSkip);
    right.appendChild(btnClose);

    actions.appendChild(left);
    actions.appendChild(right);

    tooltip.appendChild(header);
    tooltip.appendChild(text);
    tooltip.appendChild(actions);

    overlay.appendChild(hole);
    overlay.appendChild(tooltip);

    document.body.appendChild(overlay);

    this.overlay = overlay;
    this.hole = hole;
    this.tooltip = tooltip;
    this.titleEl = title;
    this.counterEl = counter;
    this.textEl = text;
    this.btnPrev = btnPrev;
    this.btnNext = btnNext;
    this.btnSkip = btnSkip;
    this.btnClose = btnClose;
  };

  TourRunner.prototype._findStepEl = function (step) {
    if (!step || !step.selector) return null;
    return qs(step.selector);
  };

  TourRunner.prototype._scrollIntoView = function (el) {
    try {
      if (!el) return;
      el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
    } catch (e) {}
  };

  TourRunner.prototype._position = function () {
    if (!this.active) return;
    var step = this.tour.steps[this.idx];
    var el = this._findStepEl(step);

    if (!el) {
      // Se não achar o elemento, aguarda um pouco (chat pode renderizar depois)
      var self = this;
      this._missingRetries = (this._missingRetries || 0) + 1;
      if (this._missingRetries <= 10) {
        setTimeout(function () { self._position(); }, 250);
        return;
      }
      // Se ainda não achar após retries, tenta pular automaticamente
      this._missingRetries = 0;
      this._next(true);
      return;
    }

    // achou elemento: reseta contador de retries
    this._missingRetries = 0;

    var r = el.getBoundingClientRect();
    var pad = 10;
    var x = clamp(r.left - pad, 8, window.innerWidth - 8);
    var y = clamp(r.top - pad, 8, window.innerHeight - 8);
    var w = clamp(r.width + pad * 2, 24, window.innerWidth - 16);
    var h = clamp(r.height + pad * 2, 24, window.innerHeight - 16);

    this.hole.style.left = x + 'px';
    this.hole.style.top = y + 'px';
    this.hole.style.width = w + 'px';
    this.hole.style.height = h + 'px';

    // Tooltip
    var tt = this.tooltip;
    var ttW = tt.offsetWidth || 320;
    var ttH = tt.offsetHeight || 140;

    // Mobile: fixa tooltip embaixo, centralizado
    if (window.innerWidth <= 520) {
      tt.style.left = '8px';
      tt.style.right = '8px';
      tt.style.top = '';
      tt.style.bottom = '12px';
      return;
    }

    tt.style.right = '';
    tt.style.bottom = '';

    var belowY = y + h + 12;
    var aboveY = y - ttH - 12;

    var placeBelow = (belowY + ttH) < (window.innerHeight - 8);
    var top = placeBelow ? belowY : aboveY;
    if (top < 8) top = 8;

    var left = clamp(x, 8, window.innerWidth - ttW - 8);
    // Se tiver espaço à direita do highlight, tenta alinhar melhor
    if (x + w + 12 + ttW < window.innerWidth - 8) {
      left = clamp(x + w + 12, 8, window.innerWidth - ttW - 8);
      top = clamp(y, 8, window.innerHeight - ttH - 8);
    }

    tt.style.left = left + 'px';
    tt.style.top = top + 'px';
  };

  TourRunner.prototype._renderStep = function () {
    this._ensureUi();

    var step = this.tour.steps[this.idx];
    var el = this._findStepEl(step);
    if (el) {
      this._scrollIntoView(el);
    }

    this.titleEl.textContent = String(step.title || this.tour.title || 'Tour');
    this.textEl.textContent = String(step.text || '');

    if (this.counterEl) {
      var total = (this.tour && this.tour.steps) ? this.tour.steps.length : 0;
      this.counterEl.textContent = total > 0 ? ('Passo ' + String(this.idx + 1) + '/' + String(total)) : '';
    }

    this.btnPrev.disabled = this.idx <= 0;
    this.btnPrev.style.opacity = this.btnPrev.disabled ? '0.55' : '1';
    this.btnPrev.style.cursor = this.btnPrev.disabled ? 'not-allowed' : 'pointer';

    var isLast = this.idx >= (this.tour.steps.length - 1);
    var cfg = getConfig();
    var st = onboardingState();
    var flow = (st.active || cfg.onboarding || cfg.force) ? getOnboardingFlow() : null;
    var pageIdx = st.idx || 0;

    if (isLast && flow && flow.length && pageIdx < flow.length - 1) {
      this.btnNext.textContent = 'Próxima página';

      // Deixa explícito o fluxo de página por página
      var baseText = String(step.text || '');
      if (baseText.indexOf('Próxima página') === -1) {
        this.textEl.textContent = baseText + (baseText ? ' ' : '') + 'Quando terminar aqui, clique em "Próxima página".';
      }
    } else {
      this.btnNext.textContent = isLast ? 'Finalizar' : 'Próximo';
    }

    this._position();
  };

  TourRunner.prototype._saveDone = function () {
    var ls = safeLocalStorage();
    if (!ls) return;
    var key = 'tuq_tour_done:' + String(this.tour.id);
    ls.setItem(key, '1');
  };

  TourRunner.prototype._clearDone = function () {
    var ls = safeLocalStorage();
    if (!ls) return;
    var key = 'tuq_tour_done:' + String(this.tour.id);
    ls.removeItem(key);
  };

  TourRunner.prototype.isDone = function () {
    var ls = safeLocalStorage();
    if (!ls) return false;
    var key = 'tuq_tour_done:' + String(this.tour.id);
    return ls.getItem(key) === '1';
  };

  TourRunner.prototype.start = function (force, autoSkipIfMissing) {
    if (this.active) return;
    if (!force && this.isDone()) return;

    this.active = true;
    this.idx = 0;
    this._missingRetries = 0;

    this._ensureUi();
    this.overlay.style.display = 'block';

    var self = this;
    this.btnPrev.onclick = function () { self._prev(); };
    this.btnNext.onclick = function () { self._next(false); };
    this.btnSkip.onclick = function () { self.cancel(); };
    this.btnClose.onclick = function () { self.cancel(); };

    this._boundReposition = function () { self._position(); };
    window.addEventListener('resize', this._boundReposition);
    window.addEventListener('scroll', this._boundReposition, true);

    if (autoSkipIfMissing) {
      // tenta iniciar no primeiro passo que existir no DOM
      var guard = 0;
      while (guard < 10) {
        var step = this.tour.steps[this.idx];
        if (this._findStepEl(step)) break;
        if (this.idx >= this.tour.steps.length - 1) break;
        this.idx += 1;
        guard += 1;
      }
    }

    this._renderStep();
  };

  TourRunner.prototype.cancel = function () {
    // Cancelamento explícito: não marca como concluído e interrompe qualquer onboarding
    try {
      setOnboarding(false, 0);
    } catch (e) {}
    this.finish(false);
  };

  TourRunner.prototype._closeUiOnly = function () {
    // Fecha o overlay sem mexer no estado do onboarding (usado ao trocar de página)
    try {
      this.active = false;
      if (this.overlay) this.overlay.style.display = 'none';
    } catch (e) {}

    if (this._boundReposition) {
      try {
        window.removeEventListener('resize', this._boundReposition);
        window.removeEventListener('scroll', this._boundReposition, true);
      } catch (e) {}
      this._boundReposition = null;
    }
  };

  TourRunner.prototype._prev = function () {
    if (!this.active) return;
    this.idx = Math.max(0, this.idx - 1);
    this._renderStep();
  };

  TourRunner.prototype._next = function (autoSkipIfMissing) {
    if (!this.active) return;

    var isLast = this.idx >= (this.tour.steps.length - 1);
    if (isLast) {
      var cfg = getConfig();
      var st = onboardingState();
      var flow = (st.active || cfg.onboarding || cfg.force) ? getOnboardingFlow() : null;
      var pageIdx = st.idx || 0;

      // No onboarding, no último passo a ação é ir para a próxima página (se existir)
      if (flow && flow.length && pageIdx < flow.length - 1) {
        var nextPath = flow[pageIdx + 1];
        setOnboarding(true, pageIdx + 1);
        this._closeUiOnly();
        window.location.href = appendOnboardingParams(nextPath, pageIdx + 1);
        return;
      }

      // Última página do onboarding: encerra
      if (flow && flow.length && pageIdx >= flow.length - 1) {
        finishOnboarding();
      }

      this.finish(true);
      return;
    }

    this.idx = Math.min(this.tour.steps.length - 1, this.idx + 1);

    if (autoSkipIfMissing) {
      // evita loop infinito
      var guard = 0;
      while (guard < 10) {
        var step = this.tour.steps[this.idx];
        if (this._findStepEl(step)) break;
        if (this.idx >= this.tour.steps.length - 1) break;
        this.idx += 1;
        guard += 1;
      }
    }

    this._renderStep();
  };

  TourRunner.prototype.finish = function (markDone) {
    if (!this.active) return;

    if (markDone) {
      this._saveDone();
    }

    this.active = false;

    try {
      if (this.overlay) this.overlay.style.display = 'none';
    } catch (e) {}

    if (this._boundReposition) {
      window.removeEventListener('resize', this._boundReposition);
      window.removeEventListener('scroll', this._boundReposition, true);
      this._boundReposition = null;
    }

    // Se o tour foi fechado/pulado, encerra onboarding
    try {
      if (!markDone) {
        var cfg = getConfig();
        var st = onboardingState();
        if (st.active || cfg.onboarding || cfg.force) {
          setOnboarding(false, 0);
        }
      }
    } catch (e) {}
  };

  function bootstrap() {
    var tour = getTourForCurrentPage();
    var cfg = getConfig();

    // Se veio por query param (troca de página do onboarding), restaura o estado
    try {
      var qpOnb = getQueryParam('tuq_onb');
      var qpIdx = getQueryParam('tuq_idx');
      if (qpOnb === '1' && qpIdx != null) {
        var restoredIdx = parseInt(String(qpIdx), 10);
        if (!isFinite(restoredIdx) || restoredIdx < 0) restoredIdx = 0;
        setOnboarding(true, restoredIdx);
        // Remove params temporários (mantém ?new=1, ?c=..., etc.)
        removeQueryParams(['tuq_onb', 'tuq_idx', 'tuq_ts']);
      }
    } catch (e) {}

    // Remove botão antigo (caso tenha ficado de uma versão anterior)
    try {
      var oldFab = qs('[data-tuquinha-tour="fab"]');
      if (oldFab && oldFab.parentNode) oldFab.parentNode.removeChild(oldFab);
    } catch (e) {}

    // Onboarding: ativa fluxo multi-página apenas quando sinalizado pelo backend
    if (cfg.onboarding || cfg.force) {
      // Reinicia o onboarding do zero (evita fluxo antigo preso no localStorage)
      setOnboarding(false, 0);
      clearAllToursDone();
      setOnboarding(true, 0);
      // garante que o fluxo seja calculado no contexto do DOM atual (sidebar)
      getOnboardingFlow();
    }

    var st = onboardingState();
    var currentPath = normalizePath(window.location.pathname);
    if (st.active) {
      // Se o onboarding ficou preso no localStorage sem sinal do backend, expira automaticamente
      if (!cfg.onboarding && !cfg.force) {
        var startedAt = st.startedAt || 0;
        if (!startedAt || (now() - startedAt) > (15 * 60 * 1000)) {
          setOnboarding(false, 0);
          return;
        }
      }

      var flow = getOnboardingFlow();
      var expected = flow[st.idx] || flow[0] || '/';
      var expectedPath = getPathnameFromUrl(expected);
      if (currentPath !== expectedPath) {
        if (shouldAbortRedirectLoop()) {
          finishOnboarding();
          return;
        }
        // Se estiver fora da página esperada, redireciona para manter o fluxo
        window.location.href = expected;
        return;
      }
    } else {
      // Sem onboarding: não inicia automaticamente
      if (!cfg.force && !cfg.onboarding) {
        if (!tour || !tour.steps || !tour.steps.length) return;
        return;
      }
    }

    if (!tour || !tour.steps || !tour.steps.length) return;

    var runner = new TourRunner(tour);

    // Auto start apenas no onboarding (ou force) - com tentativas extras
    var start = function () {
      try {
        runner.start(true, true);
      } catch (e) {
        try {
          console.error('[tuquinha-tour] Falha ao iniciar tour', {
            path: window.location.pathname,
            search: window.location.search,
            onboardingActive: !!(st && st.active),
            tourId: tour && tour.id,
            error: e
          });
        } catch (e2) {}
        try { runner.active = false; } catch (e3) {}
      }
    };
    setTimeout(start, 50);
    setTimeout(start, 450);

    // Algumas páginas (ex.: chat) podem renderizar componentes depois do DOMContentLoaded
    if (st && st.active) {
      setTimeout(start, 1200);
      setTimeout(start, 2500);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
