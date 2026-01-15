
import '../css/app.css';
import * as bootstrap from 'bootstrap';
// resources/js/app.js
import '../css/marketing.css'
import '@fortawesome/fontawesome-free/css/all.css?inline';
import './charts'; // add this line


/**
 * Initialize Tooltips (Example)
 */
document.addEventListener('DOMContentLoaded', () => {
  // Basic setup for a global variable if you need it later
  window.bootstrap = bootstrap;

  // Example: Initialize tooltips if you use them
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});


// Import Axios
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Idle-aware CSRF refresh before submit (prevents 419 after idle)
(function () {
  let lastActivity = Date.now();
  const IDLE_LIMIT_MS = 5 * 60 * 1000; // 5 minutes

  const mark = () => { lastActivity = Date.now(); };
  ["click","keydown","mousemove","scroll","touchstart"].forEach(evt =>
    document.addEventListener(evt, mark, { passive: true })
  );

  async function refreshCsrf() {
    const res = await fetch("/_csrf", { credentials: "same-origin" });
    if (!res.ok) return null;
    const data = await res.json();
    return data?.token || null;
  }

  function applyToken(token) {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute("content", token);
    document.querySelectorAll('input[name="_token"]').forEach(i => { i.value = token; });
  }

  document.addEventListener("submit", async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    // Only handle state-changing methods
    const method = (form.getAttribute("method") || "GET").toUpperCase();
    if (method === "GET") return;

    // Avoid loop
    if (form.dataset.csrfRefreshed === "1") return;

    const idleFor = Date.now() - lastActivity;
    if (idleFor < IDLE_LIMIT_MS) return;

    e.preventDefault();

    const token = await refreshCsrf();
    if (token) {
      applyToken(token);
      form.dataset.csrfRefreshed = "1";
      form.submit();
      return;
    }

    // If token refresh fails, allow normal flow (will hit Handler fallback)
    form.submit();
  }, true);
})();

// resources/js/app.js
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const mainWrapper = document.getElementById('main-content-wrapper');

  const openBtn = document.getElementById('sidebar-open');     // header hamburger (mobile)
  const toggleBtn = document.getElementById('sidebar-toggle'); // chevron button (inside sidebar)

  const md = window.matchMedia('(min-width: 1024px)');
  const xl = window.matchMedia('(min-width: 1280px)');
  const KEY = 'oh.sidebar'; // 'collapsed' | 'expanded'

  if (!sidebar || !mainWrapper) return;

  const defaultDesktopState = () => (xl.matches ? 'expanded' : 'collapsed');
  const getDesktopState = () => localStorage.getItem(KEY) || defaultDesktopState();

  const openMobile = () => {
    if (!backdrop) return;
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebar.classList.remove('sidebar-collapsed', 'w-20');
    sidebar.classList.add('w-64');
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
    backdrop.classList.add('opacity-100');
    document.body.classList.add('overflow-hidden');
    openBtn?.setAttribute('aria-expanded', 'true');
  };

  const closeMobile = () => {
    if (!backdrop) return;
    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
    sidebar.classList.remove('sidebar-collapsed', 'w-20');
    sidebar.classList.add('w-64');
    backdrop.classList.add('opacity-0', 'pointer-events-none');
    backdrop.classList.remove('opacity-100');
    document.body.classList.remove('overflow-hidden');
    openBtn?.setAttribute('aria-expanded', 'false');
  };

  const applyDesktop = () => {
    const expanded = getDesktopState() === 'expanded';

    // ensure sidebar is onscreen on desktop
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');

    // sidebar width
    sidebar.classList.toggle('w-64', expanded);
    sidebar.classList.toggle('w-20', !expanded);
    sidebar.classList.toggle('sidebar-collapsed', !expanded);

    // content shift
    mainWrapper.classList.toggle('pl-64', expanded);
    mainWrapper.classList.toggle('pl-20', !expanded);
    mainWrapper.classList.remove('pl-0');

    // chevron direction (optional)
    const chevron = toggleBtn?.querySelector('i.fas');
    if (chevron) {
      chevron.classList.toggle('fa-chevron-left', expanded);
      chevron.classList.toggle('fa-chevron-right', !expanded);
    }
  };

  const apply = () => {
    if (!md.matches) {
      // Mobile: overlay sidebar, never shift content
      mainWrapper.classList.remove('pl-64', 'pl-20', 'pl-16');
      mainWrapper.classList.add('pl-0');
      sidebar.classList.remove('w-20', 'sidebar-collapsed');
      sidebar.classList.add('w-64');
      sidebar.classList.remove('translate-x-0');
      closeMobile();
      return;
    }

    // Desktop
    closeMobile();
    applyDesktop();
  };

  // Mobile open (hamburger)
  openBtn?.addEventListener('click', () => {
    if (md.matches) return; // only mobile
    openMobile();
  });

  // Toggle button inside sidebar:
  // - on mobile: close overlay
  // - on desktop: collapse/expand + persist
  toggleBtn?.addEventListener('click', () => {
    if (!md.matches) {
      closeMobile();
      return;
    }
    const next = getDesktopState() === 'expanded' ? 'collapsed' : 'expanded';
    localStorage.setItem(KEY, next);
    applyDesktop();
  });

  // Backdrop click closes mobile
  backdrop?.addEventListener('click', closeMobile);

  // Escape closes mobile
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMobile();
  });

  md.addEventListener?.('change', apply);
  xl.addEventListener?.('change', apply);

  apply();
});

// Sidebar accordion with animations
document.addEventListener("DOMContentLoaded", () => {
  const sections = Array.from(document.querySelectorAll(".oh-sidebar-section"));
  const toggles = Array.from(document.querySelectorAll(".oh-sidebar-section [data-collapse-toggle]"));
  const STORAGE_KEY = "oh.sidebar.section";

  const getSection = (btn) => btn.closest(".oh-sidebar-section");
  const getContent = (btn) => {
    const id = btn.getAttribute("aria-controls");
    return id ? document.getElementById(id) : null;
  };
  const setChevron = (btn, open) => {
    const chev = btn.querySelector(".sidebar-chevron");
    if (chev) chev.classList.toggle("is-open", open);
  };

  const sidebar = document.querySelector(".oh-sidebar");
  const isCollapsed = () => sidebar?.classList.contains("sidebar-collapsed");
  const resetInlineStyles = (content) => {
    content.style.display = "";
    content.style.height = "";
    content.style.opacity = "";
    content.style.transform = "";
  };

  const closeSection = (section) => {
    const btn = section.querySelector("[data-collapse-toggle]");
    const content = btn ? getContent(btn) : null;
    if (!btn || !content) return;

    section.setAttribute("aria-expanded", "false");
    btn.setAttribute("aria-expanded", "false");
    setChevron(btn, false);
    section.dataset.open = "0";

    if (isCollapsed()) {
      resetInlineStyles(content);
      return;
    }

    content.style.display = "block";
    content.style.height = content.scrollHeight + "px";
    requestAnimationFrame(() => {
      content.style.height = "0px";
      content.style.opacity = "0";
      content.style.transform = "translateY(-4px)";
    });
  };

  const openSection = (section) => {
    const btn = section.querySelector("[data-collapse-toggle]");
    const content = btn ? getContent(btn) : null;
    if (!btn || !content) return;

    section.setAttribute("aria-expanded", "true");
    btn.setAttribute("aria-expanded", "true");
    setChevron(btn, true);
    section.dataset.open = "1";

    if (isCollapsed()) {
      resetInlineStyles(content);
      return;
    }

    content.style.display = "block";
    content.style.height = content.scrollHeight + "px";
    content.style.opacity = "1";
    content.style.transform = "translateY(0)";
  };

  const closeAll = () => sections.forEach(closeSection);

  // Capture which sections server marked open before we close them
  const initiallyOpen = sections.filter((s) => s.getAttribute("aria-expanded") === "true");

  // initial state: close all
  closeAll();

  const storedKey = localStorage.getItem(STORAGE_KEY);
  const storedSection = storedKey
    ? sections.find((s) => s.dataset.sectionKey === storedKey)
    : null;

  if (!isCollapsed()) {
    if (storedSection) {
      openSection(storedSection);
    } else {
      // open the first initially-open section (if any) unless it's Operations
      const firstOpen = initiallyOpen.find((s) => s.dataset.isOperations !== "1");
      if (firstOpen) {
        openSection(firstOpen);
        localStorage.setItem(STORAGE_KEY, firstOpen.dataset.sectionKey || "");
      }
    }
  }

  const closeButtons = Array.from(document.querySelectorAll("[data-section-close]"));

  // accordion behavior
  toggles.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const section = getSection(btn);
      const content = getContent(btn);
      if (!section || !content) return;

      const wasOpen = section.getAttribute("aria-expanded") === "true";

      // close others
      sections.forEach((s) => {
        if (s !== section) closeSection(s);
      });

      // toggle current
      if (wasOpen) {
        closeSection(section);
        if (localStorage.getItem(STORAGE_KEY) === section.dataset.sectionKey) {
          localStorage.removeItem(STORAGE_KEY);
        }
      } else {
        openSection(section);
        if (section.dataset.sectionKey) {
          localStorage.setItem(STORAGE_KEY, section.dataset.sectionKey);
        }
      }
    });
  });

  closeButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const section = getSection(btn);
      if (!section) return;
      closeSection(section);
      if (localStorage.getItem(STORAGE_KEY) === section.dataset.sectionKey) {
        localStorage.removeItem(STORAGE_KEY);
      }
    });
  });

  // resize recalculates heights for open sections
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (isCollapsed()) return;
      sections.forEach((section) => {
        if (section.getAttribute("aria-expanded") === "true") {
          const btn = section.querySelector("[data-collapse-toggle]");
          const content = btn ? getContent(btn) : null;
          if (content) {
            content.style.height = content.scrollHeight + "px";
          }
        }
      });
    }, 120);
  });
});
