(() => {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const POLL_MS = 8000;

  const api = async (path, options = {}) => {
    const res = await fetch(`${LKTECH.rest}${path}`, {
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": LKTECH.nonce,
        ...(options.headers || {}),
      },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || data.code || "Ошибка запроса");
    return data;
  };

  const qs = (sel, root = document) => root.querySelector(sel);
  const qsa = (sel, root = document) => [...root.querySelectorAll(sel)];
  const escapeHtml = (v) =>
    String(v || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const state = {
    status: "",
    source: "",
    type: "",
    s: "",
    selectedId: 0,
    maxId: 0,
    items: [],
    sound: localStorage.getItem("lktech_sound") === "1",
    pollTimer: null,
  };

  function toast(text) {
    const el = qs("[data-toast]");
    if (!el) return;
    el.textContent = text;
    el.classList.add("is-on");
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove("is-on"), 2800);
  }

  function playNotifySound() {
    if (!state.sound || reduce) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.type = "sine";
      o.frequency.value = 880;
      g.gain.value = 0.0001;
      o.connect(g);
      g.connect(ctx.destination);
      const now = ctx.currentTime;
      g.gain.exponentialRampToValueAtTime(0.08, now + 0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);
      o.start(now);
      o.stop(now + 0.4);
      setTimeout(() => ctx.close(), 600);
    } catch (_) {}
  }

  function initials(item) {
    const src = item.name || item.telegram || item.phone || "?";
    return String(src)
      .replace(/@/g, "")
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0])
      .join("")
      .toUpperCase();
  }

  function chip(mod, label) {
    return `<span class="lk-chip is-${escapeHtml(mod)}">${escapeHtml(label)}</span>`;
  }

  function closeSelects(except) {
    qsa("[data-lk-select].is-open").forEach((box) => {
      if (box === except) return;
      box.classList.remove("is-open");
      qs(".lk-select-trigger", box)?.setAttribute("aria-expanded", "false");
      const menu = qs(".lk-select-menu", box);
      if (menu) menu.hidden = true;
    });
  }

  function initSelects(root = document) {
    qsa("[data-lk-select]", root).forEach((box) => {
      if (box.dataset.ready === "1") return;
      box.dataset.ready = "1";
      const trigger = qs(".lk-select-trigger", box);
      const menu = qs(".lk-select-menu", box);
      const input = qs("[data-lk-select-input]", box);
      const label = qs("[data-lk-select-label]", box);
      if (!trigger || !menu || !input) return;

      trigger.addEventListener("click", (e) => {
        e.stopPropagation();
        const open = !box.classList.contains("is-open");
        closeSelects(box);
        box.classList.toggle("is-open", open);
        trigger.setAttribute("aria-expanded", open ? "true" : "false");
        menu.hidden = !open;
      });

      menu.addEventListener("click", (e) => {
        const option = e.target.closest("[data-value]");
        if (!option) return;
        input.value = option.getAttribute("data-value") || "";
        if (label) label.textContent = option.textContent.trim();
        qsa("[role=option]", menu).forEach((i) => i.removeAttribute("aria-selected"));
        option.setAttribute("aria-selected", "true");
        closeSelects();
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });
  }

  document.addEventListener("click", () => closeSelects());
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeSelects();
  });

  function syncSoundButton() {
    const btn = qs("[data-sound-toggle]");
    if (!btn) return;
    btn.classList.toggle("is-on", state.sound);
    btn.setAttribute("aria-pressed", state.sound ? "true" : "false");
    btn.textContent = state.sound ? "Звук вкл" : "Звук выкл";
  }

  function setMobileView(view) {
    const app = qs("[data-dashboard]");
    if (!app) return;
    app.classList.remove("is-show-list", "is-show-detail", "is-show-tools");
    app.classList.add(`is-show-${view}`);
    qsa("[data-mobile-view]").forEach((b) => {
      b.classList.toggle("is-active", b.dataset.mobileView === view);
    });
  }

  function renderList(items) {
    const list = qs("[data-list]");
    if (!list) return;
    if (!items.length) {
      list.innerHTML = `<div class="lk-empty-mini">Заявок пока нет</div>`;
      return;
    }
    list.innerHTML = items
      .map((item) => {
        const who = item.name || item.phone || (item.telegram ? `@${item.telegram}` : "Без имени");
        return `
      <button type="button" class="lk-lead-card${item.id === state.selectedId ? " is-active" : ""}" data-open-lead="${item.id}">
        <span class="lk-avatar">${escapeHtml(initials(item))}</span>
        <span>
          <h3>${escapeHtml(who)}</h3>
          <small>${escapeHtml(item.type_label)} · ${escapeHtml(item.created_human)}</small>
          <span class="lk-lead-meta">
            ${chip(item.status, item.status_label)}
            ${chip(item.source, item.source_label)}
          </span>
        </span>
      </button>`;
      })
      .join("");
  }

  function renderStats(stats) {
    if (!stats) return;
    qsa("[data-stat]").forEach((el) => {
      const key = el.dataset.stat;
      if (key && key in stats) el.textContent = String(stats[key]);
    });
  }

  function contactButtons(c) {
    if (!c) return "";
    const bits = [];
    if (c.phone?.available && c.phone.href) {
      bits.push(`<a class="lk-contact-btn" href="${escapeHtml(c.phone.href)}">Телефон</a>`);
      if (c.phone.sms) bits.push(`<a class="lk-contact-btn is-soft" href="${escapeHtml(c.phone.sms)}">SMS</a>`);
    }
    if (c.telegram?.available && c.telegram.href) {
      bits.push(`<a class="lk-contact-btn is-tg" href="${escapeHtml(c.telegram.href)}" target="_blank" rel="noopener">Telegram</a>`);
    }
    if (c.max?.href) {
      bits.push(`<a class="lk-contact-btn is-max" href="${escapeHtml(c.max.href)}" target="_blank" rel="noopener">MAX</a>`);
    }
    bits.push(`<button type="button" class="lk-contact-btn is-soft" data-copy-message>Скопировать текст</button>`);
    return bits.join("");
  }

  function commentsHtml(comments) {
    if (!comments?.length) return `<p class="lk-quiet">Комментариев пока нет.</p>`;
    return comments
      .map(
        (c) => `
      <article class="lk-comment">
        <header><strong>${escapeHtml(c.user_name || "")}</strong><time>${escapeHtml(c.created_human || "")}</time></header>
        <p>${escapeHtml(c.text || "")}</p>
      </article>`
      )
      .join("");
  }

  function titleLines(lead) {
    return [lead.subject || lead.type_label, lead.name, lead.phone, lead.telegram ? `@${lead.telegram}` : ""]
      .filter((x) => String(x || "").trim())
      .map((line, i) => `<span class="${i === 0 ? "" : "lk-lead-title-line"}">${escapeHtml(line)}</span>`)
      .join("");
  }

  function detailRows(lead) {
    const rows = [
      ["Тип", lead.type_label],
      ["Источник", lead.source_label],
      ["Город", lead.city],
      ["Email", lead.email],
      ["Марка", lead.car_brand],
      ["Модель", lead.car_model],
      ["Год", lead.car_year],
      ["Госномер", lead.plate],
      ["VIN", lead.vin],
      ["Менеджер", lead.assigned_name],
      ["Создана", lead.created_human],
    ];
    return rows
      .filter(([, v]) => v)
      .map(([k, v]) => `<dt>${escapeHtml(k)}</dt><dd>${escapeHtml(v)}</dd>`)
      .join("");
  }

  function showLeadWorkspace(show) {
    const empty = qs("[data-empty]");
    const detail = qs("[data-detail]");
    const aside = qs("[data-aside]");
    if (empty) empty.hidden = !!show;
    if (detail) detail.hidden = !show;
    if (aside) aside.hidden = !show;
  }

  function renderDetail(lead) {
    const main = qs("[data-detail]");
    const aside = qs("[data-aside]");
    if (!main || !aside) return;
    showLeadWorkspace(true);

    main.innerHTML = `
      <section class="lk-profile">
        <div class="lk-profile-top">
          <div class="lk-profile-photo">${escapeHtml(initials(lead))}</div>
          <h1 class="lk-lead-title">${titleLines(lead)}</h1>
          <div class="lk-profile-tags">
            ${chip(lead.status, lead.status_label)}
            ${chip(lead.source, lead.source_label)}
          </div>
        </div>
        <div class="lk-contact-bar">${contactButtons(lead.contacts)}</div>
        <p class="lk-preset" data-preset>${escapeHtml(lead.preset_message || "")}</p>
        <div class="lk-tabs">
          <button type="button" class="lk-tab is-active" data-tab="details">Детали</button>
          <button type="button" class="lk-tab" data-tab="comments">Комментарии</button>
        </div>
        <div class="lk-panel-card" data-pane="details">
          <dl class="lk-kv">${detailRows(lead)}</dl>
          ${lead.message ? `<h3 style="margin:16px 0 8px;font-size:1rem">Сообщение</h3><p style="margin:0;white-space:pre-wrap">${escapeHtml(lead.message)}</p>` : ""}
        </div>
        <div class="lk-panel-card" data-pane="comments" hidden>
          <div class="lk-comments" data-comments>${commentsHtml(lead.comments || [])}</div>
          <div class="lk-comment-form">
            <textarea data-comment-text placeholder="Комментарий к заявке…"></textarea>
            <button type="button" class="lk-btn lk-btn-sm" data-add-comment style="margin-top:8px">Добавить</button>
          </div>
        </div>
      </section>`;

    const statusOptions = Object.entries({
      new: "Новая",
      in_progress: "В работе",
      done: "Закрыта",
      cancelled: "Отменена",
    })
      .map(
        ([value, label]) =>
          `<li role="option" data-value="${value}" ${value === lead.status ? 'aria-selected="true"' : ""}>${label}</li>`
      )
      .join("");

    aside.innerHTML = `
      <div class="lk-widget lk-widget-lavender">
        <h2>${escapeHtml(lead.type_label || "Заявка")}</h2>
        <div class="big">${escapeHtml(lead.status_label)}</div>
        <p class="lk-quiet" style="margin:0">Источник: ${escapeHtml(lead.source_label)}</p>
      </div>
      <div class="lk-widget lk-widget-lime">
        <h2>Работа с заявкой</h2>
        <div class="lk-field">
          <span class="lk-field-label">Статус</span>
          <div class="lk-select" data-lk-select data-name="status">
            <button type="button" class="lk-select-trigger" aria-haspopup="listbox" aria-expanded="false">
              <span class="lk-select-value" data-lk-select-label>${escapeHtml(lead.status_label)}</span>
              <span class="lk-select-caret" aria-hidden="true"></span>
            </button>
            <ul class="lk-select-menu" role="listbox" hidden>${statusOptions}</ul>
            <input type="hidden" value="${escapeHtml(lead.status)}" data-lk-select-input>
          </div>
        </div>
        <div class="lk-actions">
          <button type="button" class="lk-btn lk-btn-sm" data-save-status>Сохранить</button>
          <button type="button" class="lk-btn lk-btn-ghost lk-btn-sm" data-take>Взять</button>
        </div>
        <hr class="lk-sep">
        <button type="button" class="lk-btn lk-btn-danger" data-delete-lead>Удалить заявку</button>
        <p class="lk-feedback" data-feedback></p>
      </div>
      <div class="lk-widget lk-widget-white">
        <h2>Менеджер</h2>
        <p style="margin:0;font-weight:700">${escapeHtml(lead.assigned_name || qs("[data-user-name]")?.textContent || "—")}</p>
      </div>`;

    initSelects(aside);
    bindDetailEvents(lead);
    setMobileView("detail");
  }

  function flash(text, ok = true) {
    const el = qs("[data-feedback]");
    if (!el) return;
    el.textContent = text;
    el.classList.toggle("is-bad", !ok);
  }

  function bindDetailEvents(lead) {
    qs("[data-copy-message]")?.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(qs("[data-preset]")?.textContent || "");
        toast("Текст скопирован");
      } catch (_) {
        toast("Не удалось скопировать");
      }
    });

    qsa("[data-tab]").forEach((tab) => {
      tab.addEventListener("click", () => {
        qsa("[data-tab]").forEach((t) => t.classList.toggle("is-active", t === tab));
        qsa("[data-pane]").forEach((p) => {
          p.hidden = p.dataset.pane !== tab.dataset.tab;
        });
      });
    });

    qs("[data-add-comment]")?.addEventListener("click", async () => {
      const text = qs("[data-comment-text]")?.value.trim() || "";
      if (!text) return;
      try {
        const res = await api(`/leads/${lead.id}/comments`, {
          method: "POST",
          body: JSON.stringify({ text }),
        });
        qs("[data-comments]").innerHTML = commentsHtml(res.comments || []);
        qs("[data-comment-text]").value = "";
        toast("Комментарий добавлен");
        refreshFeed({ silent: true });
      } catch (e) {
        flash(e.message, false);
      }
    });

    qs("[data-save-status]")?.addEventListener("click", async () => {
      const status = qs('[data-name="status"] [data-lk-select-input]')?.value || lead.status;
      try {
        const updated = await api(`/leads/${lead.id}/status`, {
          method: "POST",
          body: JSON.stringify({ status }),
        });
        toast("Статус сохранён");
        renderDetail(updated);
        refreshFeed({ silent: true });
      } catch (e) {
        flash(e.message, false);
      }
    });

    qs("[data-take]")?.addEventListener("click", async () => {
      const status = qs('[data-name="status"] [data-lk-select-input]')?.value || "in_progress";
      try {
        const updated = await api(`/leads/${lead.id}/status`, {
          method: "POST",
          body: JSON.stringify({ status, take: true }),
        });
        toast("Заявка закреплена");
        renderDetail(updated);
        refreshFeed({ silent: true });
      } catch (e) {
        flash(e.message, false);
      }
    });

    qs("[data-delete-lead]")?.addEventListener("click", async () => {
      if (!confirm("Удалить заявку?")) return;
      try {
        await api(`/leads/${lead.id}`, { method: "DELETE" });
        state.selectedId = 0;
        showLeadWorkspace(false);
        toast("Заявка удалена");
        refreshFeed({ silent: true });
        setMobileView("list");
      } catch (e) {
        flash(e.message, false);
      }
    });
  }

  async function openLead(id) {
    state.selectedId = id;
    renderList(state.items);
    try {
      const lead = await api(`/leads/${id}`);
      renderDetail(lead);
      const url = new URL(window.location.href);
      url.searchParams.set("lead", String(id));
      history.replaceState({}, "", url);
    } catch (e) {
      toast(e.message);
    }
  }

  async function refreshFeed({ silent = false } = {}) {
    const params = new URLSearchParams();
    if (state.status) params.set("status", state.status);
    if (state.source) params.set("source", state.source);
    if (state.type) params.set("type", state.type);
    if (state.s) params.set("s", state.s);
    if (state.maxId) params.set("since_id", String(state.maxId));
    const data = await api(`/feed?${params}`);
    const prevMax = state.maxId;
    state.items = data.items || [];
    renderStats(data.stats);
    renderList(state.items);
    const newMax = Number(data.max_id || 0);
    if (prevMax > 0 && newMax > prevMax) {
      const count = (data.new_ids || []).length || 1;
      playNotifySound();
      toast(count === 1 ? "Новая заявка" : `Новых заявок: ${count}`);
    }
    if (newMax > state.maxId) state.maxId = newMax;
    if (!silent && state.selectedId) {
      const still = state.items.some((i) => i.id === state.selectedId);
      if (!still) {
        state.selectedId = 0;
        showLeadWorkspace(false);
      }
    }
  }

  function startPolling() {
    clearInterval(state.pollTimer);
    state.pollTimer = setInterval(() => {
      refreshFeed({ silent: true }).catch(() => {});
    }, POLL_MS);
  }

  async function initDashboard() {
    const app = qs("[data-dashboard]");
    if (!app) return;
    initSelects();
    syncSoundButton();

    qs("[data-sound-toggle]")?.addEventListener("click", async () => {
      state.sound = !state.sound;
      localStorage.setItem("lktech_sound", state.sound ? "1" : "0");
      syncSoundButton();
      if (state.sound) {
        try {
          await Notification.requestPermission();
        } catch (_) {}
        playNotifySound();
        toast("Звук включён");
      }
    });

    qs("[data-list]")?.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-open-lead]");
      if (!btn) return;
      openLead(Number(btn.dataset.openLead));
    });

    qsa("[data-filter]").forEach((el) => {
      const apply = () => {
        state[el.dataset.filter] = el.value.trim ? el.value.trim() : el.value;
        refreshFeed({ silent: true }).catch((err) => toast(err.message));
      };
      el.addEventListener("change", apply);
      if (el.dataset.filter === "s") {
        el.addEventListener("input", () => {
          clearTimeout(el._t);
          el._t = setTimeout(apply, 280);
        });
      }
    });

    qsa("[data-mobile-view]").forEach((btn) => {
      btn.addEventListener("click", () => setMobileView(btn.dataset.mobileView));
    });

    qs("[data-focus-list]")?.addEventListener("click", () => setMobileView("list"));

    await refreshFeed();
    const initial = Number(app.dataset.initialLead || 0);
    if (initial) await openLead(initial);
    startPolling();
  }

  document.addEventListener("DOMContentLoaded", () => {
    initDashboard().catch((e) => toast(e.message));
  });
})();
