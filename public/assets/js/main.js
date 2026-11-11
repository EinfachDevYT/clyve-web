(() => {
  'use strict';

  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const SVGNS = 'http://www.w3.org/2000/svg';
  let cleanups = [];

  console.log(
    '%c✿ Clyve.dev%c\n\nEngineered by EinfachDev — https://einfachdev.de',
    'font: 800 22px system-ui; color: #E9D5FF; text-shadow: 0 0 12px #A855F7; padding: 6px 0;',
    'font: 500 12px ui-monospace, monospace; color: #A78BFA; line-height: 1.6;'
  );

  /* ---------- falling petals ---------- */

  function startPetals() {
    const canvas = document.getElementById('petals');
    if (!canvas || reduced) return;
    const ctx = canvas.getContext('2d');
    const colors = ['#F7F3FF', '#E9D5FF', '#C4B5FD', '#D8B4FE'];
    let W = 0, H = 0, petals = [];

    const spawn = (anywhere) => ({
      x: Math.random() * W,
      y: anywhere ? Math.random() * H : -20,
      s: 3 + Math.random() * 5,
      vy: 0.35 + Math.random() * 0.75,
      vx: -0.2 + Math.random() * 0.4,
      rot: Math.random() * Math.PI * 2,
      vr: (Math.random() - 0.5) * 0.03,
      sway: Math.random() * Math.PI * 2,
      flip: Math.random() * Math.PI * 2,
      a: 0.3 + Math.random() * 0.5,
      c: colors[(Math.random() * colors.length) | 0],
    });

    const resize = () => {
      const dpr = Math.min(devicePixelRatio || 1, 2);
      W = canvas.clientWidth; H = canvas.clientHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const target = Math.round(Math.min(42, W / 34));
      while (petals.length < target) petals.push(spawn(true));
      petals.length = target;
    };

    const draw = (p) => {
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rot);
      ctx.scale(1, Math.abs(Math.cos(p.flip)) * 0.7 + 0.3);
      ctx.globalAlpha = p.a;
      ctx.fillStyle = p.c;
      ctx.shadowColor = 'rgba(168,85,247,.6)';
      ctx.shadowBlur = 6;
      const s = p.s;
      ctx.beginPath();
      ctx.moveTo(0, -s);
      ctx.bezierCurveTo(s * 0.9, -s * 0.6, s * 0.8, s * 0.6, 0, s);
      ctx.bezierCurveTo(-s * 0.8, s * 0.6, -s * 0.9, -s * 0.6, 0, -s);
      ctx.fill();
      ctx.restore();
    };

    let last = performance.now();
    const tick = (now) => {
      const dt = Math.min(3, (now - last) / 16.7);
      last = now;
      if (!document.hidden) {
        ctx.clearRect(0, 0, W, H);
        for (let i = 0; i < petals.length; i++) {
          const p = petals[i];
          p.sway += 0.02 * dt; p.flip += 0.03 * dt; p.rot += p.vr * dt;
          p.x += (p.vx + Math.sin(p.sway) * 0.5) * dt;
          p.y += p.vy * dt;
          if (p.y > H + 20 || p.x < -30 || p.x > W + 30) petals[i] = spawn(false);
          draw(p);
        }
      }
      requestAnimationFrame(tick);
    };

    addEventListener('resize', resize, { passive: true });
    resize();
    requestAnimationFrame(tick);
  }

  /* ---------- page features (re-run after every swap) ---------- */

  function blossom(scale) {
    const g = document.createElementNS(SVGNS, 'g');
    const inner = document.createElementNS(SVGNS, 'g');
    inner.setAttribute('class', 'bud');
    for (let i = 0; i < 5; i++) {
      const e = document.createElementNS(SVGNS, 'ellipse');
      e.setAttribute('cy', '-4.4');
      e.setAttribute('rx', '2.6');
      e.setAttribute('ry', '4.4');
      e.setAttribute('transform', `rotate(${i * 72})`);
      e.setAttribute('fill', i % 2 ? '#E9D5FF' : '#F7F3FF');
      inner.appendChild(e);
    }
    const c = document.createElementNS(SVGNS, 'circle');
    c.setAttribute('r', '1.6');
    c.setAttribute('fill', '#A855F7');
    inner.appendChild(c);
    inner.style.animationDelay = `${(-Math.random() * 6).toFixed(2)}s`;
    g.appendChild(inner);
    return g;
  }

  function growBranches(root) {
    root.querySelectorAll('.branch:not(.branch-f):not([data-grown])').forEach((svg) => {
      svg.dataset.grown = '1';
      svg.querySelectorAll('path').forEach((path, i) => {
        const len = path.getTotalLength();
        const count = Math.round(len / (i === 0 ? 34 : 46));
        for (let n = 1; n <= count; n++) {
          const t = Math.max(0, Math.min(len, (n / (count + 1)) * len + (Math.random() - 0.5) * 18));
          const pt = path.getPointAtLength(t);
          const s = 0.9 + Math.random() * 1.4;
          const b = blossom(s);
          const x = pt.x + (Math.random() - 0.5) * 22;
          const y = pt.y + (Math.random() - 0.5) * 22;
          b.setAttribute('transform', `translate(${x.toFixed(1)} ${y.toFixed(1)}) scale(${s.toFixed(2)}) rotate(${(Math.random() * 72) | 0})`);
          b.setAttribute('opacity', (0.65 + Math.random() * 0.35).toFixed(2));
          svg.appendChild(b);
        }
      });
    });
  }

  function typer(root) {
    const el = root.querySelector('#typer');
    if (!el) return;
    const lines = ['FiveM Scripts in Lua', 'Websites mit PHP & JavaScript', 'Discord Bots mit discord.py', 'Dein Projekt, deine Idee 🌸'];
    if (reduced) { el.textContent = lines[0]; return; }
    let li = 0, ci = 0, del = false, timer;
    const step = () => {
      const l = lines[li];
      el.textContent = l.slice(0, ci);
      if (!del && ci < l.length) { ci++; timer = setTimeout(step, 55); }
      else if (!del) { del = true; timer = setTimeout(step, 1600); }
      else if (ci > 0) { ci--; timer = setTimeout(step, 28); }
      else { del = false; li = (li + 1) % lines.length; timer = setTimeout(step, 300); }
    };
    step();
    cleanups.push(() => clearTimeout(timer));
  }

  function reveal(root) {
    const items = root.querySelectorAll('.reveal');
    if (!items.length) return;
    if (reduced || !('IntersectionObserver' in window)) {
      items.forEach((el) => el.classList.add('in'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.15 });
    items.forEach((el, i) => { el.style.transitionDelay = `${(i % 4) * 80}ms`; io.observe(el); });
    cleanups.push(() => io.disconnect());
  }

  /* Sections with data-title drive the tab title, the active nav link and the #hash while scrolling. */
  function sectionTitles(root) {
    const sections = [...root.querySelectorAll('section[id][data-title]')];
    if (!sections.length || !('IntersectionObserver' in window)) return;

    const brand = 'Clyve.dev';
    const baseTitle = document.title;
    const path = location.pathname;
    let current = null;

    const activate = (section) => {
      if (section === current) return;
      current = section;
      const label = section.dataset.title;
      document.title = label ? `${label} · ${brand}` : baseTitle;
      document.querySelectorAll('.nav-links a[href^="/#"]').forEach((a) => {
        if (a.getAttribute('href') === `/#${section.id}`) a.setAttribute('aria-current', 'location');
        else a.removeAttribute('aria-current');
      });
      if (location.pathname === path) {
        const hash = label ? `#${section.id}` : '';
        if (location.hash !== hash) history.replaceState(history.state, '', path + location.search + hash);
      }
    };

    const io = new IntersectionObserver((entries) => {
      const hit = entries.filter((en) => en.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (hit) activate(hit.target);
    }, { rootMargin: '-45% 0px -50% 0px', threshold: [0, 0.01] });

    sections.forEach((s) => io.observe(s));
    cleanups.push(() => {
      io.disconnect();
      document.querySelectorAll('.nav-links a[aria-current="location"]').forEach((a) => a.removeAttribute('aria-current'));
    });
  }

  function toasts(root) {
    root.querySelectorAll('[data-toasts] .toast').forEach((t, i) => {
      const id = setTimeout(() => {
        t.classList.add('out');
        setTimeout(() => t.remove(), 400);
      }, 4200 + i * 400);
      cleanups.push(() => clearTimeout(id));
    });
  }

  function requestForm(root) {
    const form = root.querySelector('#requestForm');
    if (!form) return;

    const steps = [...form.querySelectorAll('[data-step]')];
    const dots = [...form.querySelectorAll('[data-step-dot]')];
    const prevBtn = form.querySelector('[data-step-prev]');
    const nextBtn = form.querySelector('[data-step-next]');
    const submitBtn = form.querySelector('#submitBtn');
    const status = form.querySelector('#formStatus');
    const hint = form.querySelector('#contactHint');
    const msg = form.querySelector('[name="message"]');
    const count = form.querySelector('#msgCount');
    const summary = form.querySelector('#requestSummary');
    let current = 1;

    const date = form.querySelector('[name="deadline"]');
    if (date) date.min = new Date().toISOString().slice(0, 10);
    msg.addEventListener('input', () => { count.textContent = msg.value.length; });

    const fieldOf = (name) => {
      const el = name.startsWith('details_')
        ? form.querySelector(`[name^="details[${name.slice(8)}]"]`)
        : form.querySelector(`[name="${name}"]`);
      return el?.closest('.field, .consent, .type-cards') || null;
    };
    const stepOfError = (key) => {
      if (key === 'type') return 1;
      if (key.startsWith('details_') || ['title', 'budget', 'deadline'].includes(key)) return 2;
      if (key === 'message') return 3;
      return 4;
    };
    const setStatus = (text, ok) => { status.textContent = text; status.className = 'form-status ' + (ok ? 'ok' : 'err'); };
    const clear = () => { form.querySelectorAll('.invalid').forEach((f) => f.classList.remove('invalid')); hint.classList.remove('error'); setStatus('', true); };
    const mark = (errors) => {
      Object.keys(errors).forEach((k) => {
        if (k === 'contact') {
          hint.classList.add('error');
          fieldOf('discord')?.classList.add('invalid');
          fieldOf('email')?.classList.add('invalid');
        } else fieldOf(k)?.classList.add('invalid');
      });
    };

    const selectedType = () => form.querySelector('[name="type"]:checked')?.value || '';
    const syncType = () => {
      const type = selectedType();
      form.querySelectorAll('[data-for-type]').forEach((box) => {
        const on = box.dataset.forType === type;
        box.hidden = !on;
        box.querySelectorAll('input, select').forEach((el) => { el.disabled = !on; });
      });
    };

    const validate = (step) => {
      const d = new FormData(form), e = {};
      if (step === 1 && !d.get('type')) e.type = 'Bitte wähle eine Kategorie.';
      if (step === 3 && String(d.get('message') || '').trim().length < 20) e.message = 'Bitte beschreibe dein Projekt etwas genauer (mind. 20 Zeichen).';
      if (step === 4) {
        if (String(d.get('name') || '').trim().length < 2) e.name = 'Bitte gib deinen Namen an.';
        const mail = String(d.get('email') || '').trim(), dc = String(d.get('discord') || '').trim();
        if (mail && !form.email.checkValidity()) e.email = 'Die E-Mail-Adresse sieht nicht richtig aus.';
        if (!mail && !dc && !form.dataset.loggedIn) e.contact = 'Bitte gib Discord oder E-Mail an.';
        if (!d.get('consent')) e.consent = 'Bitte stimme der Datenverarbeitung zu.';
      }
      return e;
    };

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const renderSummary = () => {
      const d = new FormData(form);
      const rows = [];
      const typeCard = form.querySelector('[name="type"]:checked')?.closest('.type-card')?.querySelector('b');
      if (typeCard) rows.push(['Kategorie', typeCard.textContent]);
      form.querySelectorAll(`[data-for-type="${selectedType()}"] .field`).forEach((f) => {
        const label = f.querySelector('span, legend')?.childNodes[0]?.textContent.trim();
        const values = [...f.querySelectorAll('select, input:checked')].map((el) => el.value).filter(Boolean);
        if (label && values.length) rows.push([label, values.join(', ')]);
      });
      ['title:Titel', 'budget:Budget', 'deadline:Termin'].forEach((pair) => {
        const [k, l] = pair.split(':');
        const v = String(d.get(k) || '').trim();
        if (v) rows.push([l, k === 'deadline' ? new Date(v).toLocaleDateString('de-DE') : v]);
      });
      const text = String(d.get('message') || '').trim();
      summary.innerHTML = '<b>Zusammenfassung</b><dl>' + rows.map(([k, v]) => `<dt>${esc(k)}</dt><dd>${esc(v)}</dd>`).join('') + '</dl>'
        + (text ? `<p>${esc(text.length > 220 ? text.slice(0, 220) + '…' : text)}</p>` : '');
      summary.hidden = false;
    };

    const show = (step, focus = true) => {
      current = step;
      steps.forEach((s) => { s.hidden = Number(s.dataset.step) !== step; });
      dots.forEach((dot) => {
        const n = Number(dot.dataset.stepDot);
        dot.classList.toggle('is-active', n === step);
        dot.classList.toggle('is-done', n < step);
      });
      prevBtn.hidden = step === 1;
      nextBtn.hidden = step === steps.length;
      submitBtn.hidden = step !== steps.length;
      if (step === steps.length) renderSummary();
      if (focus) {
        form.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
        steps[step - 1].querySelector('input:not([type=hidden]):not([disabled]), select, textarea')?.focus({ preventScroll: true });
      }
    };

    const next = () => {
      clear();
      const errs = validate(current);
      if (Object.keys(errs).length) {
        mark(errs);
        setStatus(Object.values(errs)[0], false);
        return;
      }
      show(Math.min(current + 1, steps.length));
    };

    form.noValidate = true;
    form.classList.add('is-wizard');
    const preset = form.querySelector(`[name="type"][value="${new URLSearchParams(location.search).get('typ')}"]:not(:disabled)`);
    if (preset) preset.checked = true;
    syncType();
    show(preset ? 2 : 1, false);

    form.addEventListener('change', (e) => {
      if (e.target.name === 'type') { syncType(); clear(); setTimeout(next, 180); }
    });
    nextBtn.addEventListener('click', next);
    prevBtn.addEventListener('click', () => { clear(); show(Math.max(1, current - 1)); });
    dots.forEach((dot) => dot.addEventListener('click', () => {
      const n = Number(dot.dataset.stepDot);
      if (n < current) { clear(); show(n); }
    }));
    form.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT' && current < steps.length) { e.preventDefault(); next(); }
    });

    root.querySelectorAll('[data-pick]').forEach((a) => a.addEventListener('click', () => {
      const r = form.querySelector(`[name="type"][value="${a.dataset.pick}"]:not(:disabled)`);
      if (r) { r.checked = true; syncType(); show(2, false); }
    }));

    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      clear();
      for (let s = 1; s <= steps.length; s++) {
        const errs = validate(s);
        if (Object.keys(errs).length) { show(s); mark(errs); setStatus(Object.values(errs)[0], false); return; }
      }
      submitBtn.classList.add('loading');
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
          const panel = root.querySelector('#requestSuccess');
          if (panel && data.url) {
            panel.querySelector('#successTitle').textContent = data.message || 'Danke!';
            panel.querySelector('#successText').textContent = data.mailed
              ? 'Ich habe dir eine Bestätigung mit deinem persönlichen Link per E-Mail geschickt.'
              : 'Über deinen persönlichen Link siehst du jederzeit den Stand deiner Anfrage.';
            panel.querySelector('#successLink').href = data.url;
            form.hidden = true;
            panel.hidden = false;
            panel.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'center' });
          } else {
            form.reset();
            setStatus(data.message, true);
          }
        } else {
          if (data.errors) {
            const first = Math.min(...Object.keys(data.errors).map(stepOfError));
            show(first);
            mark(data.errors);
          }
          setStatus(data.message || 'Etwas ist schiefgelaufen.', false);
        }
      } catch {
        setStatus('Keine Verbindung zum Server.', false);
      } finally {
        submitBtn.classList.remove('loading');
      }
    });
  }

  function initPage(root = document) {
    cleanups.forEach((fn) => fn());
    cleanups = [];
    growBranches(root);
    typer(root);
    reveal(root);
    sectionTitles(root);
    root.querySelectorAll('[data-progress]').forEach((el) => { el.style.width = `${Math.max(0, Math.min(100, Number(el.dataset.progress) || 0))}%`; });
    toasts(root);
    requestForm(root);
  }

  /* ---------- seamless navigation ---------- */

  const progress = {
    el: null,
    timer: 0,
    start() {
      this.el ??= document.getElementById('progress');
      if (!this.el) return;
      clearTimeout(this.timer);
      this.el.className = 'progress';
      this.timer = setTimeout(() => this.el.classList.add('run'), 120);
    },
    done() {
      if (!this.el) return;
      clearTimeout(this.timer);
      if (this.el.classList.contains('run')) {
        this.el.classList.add('done');
        setTimeout(() => (this.el.className = 'progress'), 350);
      }
    },
  };

  const cache = new Map();
  const CACHE_MS = 10000;

  const sameOrigin = (url) => url.origin === location.origin;
  const isPage = (url) => !/\.(pdf|png|jpe?g|svg|webp|zip|css|js)$/i.test(url.pathname) && !/\/druck$/.test(url.pathname);

  function prefetch(url) {
    const key = url.href;
    const hit = cache.get(key);
    if (hit && Date.now() - hit.t < CACHE_MS) return hit.p;
    const p = fetch(key, { credentials: 'same-origin', headers: { 'X-Requested-With': 'nav' } })
      .then(async (r) => ({ ok: r.ok || r.status < 500, url: r.url, type: r.headers.get('content-type') || '', html: await r.text() }));
    cache.set(key, { t: Date.now(), p });
    p.catch(() => cache.delete(key));
    return p;
  }

  function swap(html, url, { push, scroll }) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const incoming = [...doc.querySelectorAll('[data-swap]')];
    const current = [...document.querySelectorAll('[data-swap]')];
    const keys = (list) => list.map((el) => el.dataset.swap).sort().join(',');

    const assets = (d) => [...d.querySelectorAll('link[rel="stylesheet"], script[src]')].map((el) => el.getAttribute('href') || el.getAttribute('src')).join('|');

    if (!incoming.length || keys(incoming) !== keys(current) || doc.body.className !== document.body.className || assets(doc) !== assets(document)) {
      location.href = url;
      return;
    }

    const apply = () => {
      incoming.forEach((el) => {
        document.querySelector(`[data-swap="${el.dataset.swap}"]`)?.replaceWith(document.adoptNode(el));
      });
      document.title = doc.title;
      if (push) history.pushState({}, '', url);
      const hash = new URL(url).hash;
      const target = hash && document.getElementById(decodeURIComponent(hash.slice(1)));
      if (target) target.scrollIntoView();
      else if (scroll !== false) scrollTo(0, 0);
      initPage(document.getElementById('app') || document);
    };

    if (document.startViewTransition && !reduced) {
      const t = document.startViewTransition(apply);
      t.ready.catch(() => {});
      t.finished.catch(() => {});
    } else apply();
  }

  async function navigate(href, opts = { push: true }) {
    const url = new URL(href, location.href);
    progress.start();
    try {
      const res = await prefetch(url);
      if (!res.ok || !res.type.includes('text/html')) { location.href = url.href; return; }
      const finalUrl = new URL(res.url);
      finalUrl.hash = url.hash;
      swap(res.html, finalUrl.href, opts);
    } catch {
      location.href = url.href;
    } finally {
      progress.done();
    }
  }

  async function submit(form, submitter) {
    const method = (form.getAttribute('method') || 'get').toUpperCase();
    const action = new URL(form.getAttribute('action') || location.href, location.href);
    const data = new FormData(form, submitter);

    if (method === 'GET') {
      action.search = new URLSearchParams(data).toString();
      return navigate(action.href);
    }

    progress.start();
    form.classList.add('is-busy');
    try {
      const res = await fetch(action.href, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'nav' },
      });
      const type = res.headers.get('content-type') || '';
      if (!type.includes('text/html')) { location.reload(); return; }
      cache.clear();
      const html = await res.text();
      const changed = res.url !== location.href;
      swap(html, res.url, { push: changed, scroll: changed });
    } catch {
      form.submit();
    } finally {
      form.classList.remove('is-busy');
      progress.done();
    }
  }

  function linkFromEvent(e) {
    const a = e.target.closest?.('a[href]');
    if (!a || a.target || a.hasAttribute('download') || a.hasAttribute('data-native')) return null;
    const url = new URL(a.href, location.href);
    if (!sameOrigin(url) || !isPage(url) || url.protocol !== location.protocol) return null;
    return { a, url };
  }

  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    if (e.target.closest('#navToggle')) {
      const links = document.getElementById('navLinks');
      const t = document.getElementById('navToggle');
      const open = links.classList.toggle('open');
      t.setAttribute('aria-expanded', String(open));
      return;
    }

    const hit = linkFromEvent(e);
    if (!hit) return;
    const { url } = hit;

    document.getElementById('navLinks')?.classList.remove('open');

    if (url.pathname === location.pathname && url.search === location.search && url.hash) {
      const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
      if (target) {
        e.preventDefault();
        history.pushState({}, '', url.href);
        target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
        return;
      }
    }

    e.preventDefault();
    navigate(url.href);
  });

  const warm = (e) => {
    const hit = linkFromEvent(e);
    if (hit && hit.url.href !== location.href) prefetch(hit.url);
  };
  document.addEventListener('pointerover', warm, { passive: true });
  document.addEventListener('touchstart', warm, { passive: true });

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || form.hasAttribute('data-native') || form.hasAttribute('data-json')) return;
    if (form.dataset.confirm && !confirm(form.dataset.confirm)) { e.preventDefault(); return; }
    const action = new URL(form.getAttribute('action') || location.href, location.href);
    if (!sameOrigin(action)) return;
    e.preventDefault();
    submit(form, e.submitter);
  });

  document.addEventListener('click', (e) => {
    const ins = e.target.closest?.('[data-insert]');
    if (ins) {
      const ta = document.querySelector(ins.dataset.target);
      if (ta) {
        ta.value = ta.value.trim() ? ta.value.replace(/\s*$/, '\n\n') + ins.dataset.insert : ins.dataset.insert;
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
      }
      return;
    }
    const copy = e.target.closest?.('[data-copy]');
    if (copy && navigator.clipboard) {
      navigator.clipboard.writeText(copy.dataset.copy).then(() => {
        const old = copy.textContent;
        copy.textContent = 'Kopiert ✓';
        setTimeout(() => { copy.textContent = old; }, 1400);
      });
    }
  });

  document.addEventListener('input', (e) => {
    if (e.target.matches?.('[data-range]')) {
      const out = e.target.closest('form')?.querySelector('[data-range-out]');
      if (out) out.textContent = e.target.value;
    }
  });

  addEventListener('popstate', () => navigate(location.href, { push: false, scroll: false }));

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { startPetals(); initPage(); });
  } else {
    startPetals();
    initPage();
  }
})();
