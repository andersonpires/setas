/**
 * SETAS-WEB - Scripts globais
 * Tema light/dark, sidebar toggle
 */

(function() {
  'use strict';

  const STORAGE_THEME = 'setas_theme';
  const STORAGE_SIDEBAR = 'setas_sidebar_expanded';

  // Aplicar tema salvo
  function applyTheme(theme) {
    const html = document.getElementById('html-theme');
    if (!html) return;
    html.setAttribute('data-theme', theme || 'light');
    localStorage.setItem(STORAGE_THEME, theme || 'light');
    toggleThemeIcons(theme === 'dark');
  }

  function toggleThemeIcons(isDark) {
    const sun = document.querySelector('.icon-sun');
    const moon = document.querySelector('.icon-moon');
    if (sun && moon) {
      sun.style.display = isDark ? 'block' : 'none';
      moon.style.display = isDark ? 'none' : 'block';
    }
  }

  function doThemeToggle() {
    const current = document.getElementById('html-theme')?.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  }

  // Inicializar tema
  function initTheme() {
    const saved = localStorage.getItem(STORAGE_THEME);
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);
  }

  // Toggle tema (desktop e mobile)
  function initThemeToggle() {
    document.querySelectorAll('#themeToggle, .theme-toggle-mobile').forEach(function(btn) {
      if (!btn) return;
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        doThemeToggle();
      });
    });
  }

  // Sidebar toggle - no mobile inicia fechado
  function initSidebarToggle() {
    const btn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    if (!btn || !sidebar) return;

    const isMobile = () => window.innerWidth < 768;
    if (isMobile()) {
      sidebar.classList.remove('expanded');
      if (main) main.classList.remove('expanded-layout');
    } else {
      const saved = localStorage.getItem(STORAGE_SIDEBAR);
      if (saved === 'true') {
        sidebar.classList.add('expanded');
        if (main) main.classList.add('expanded-layout');
      }
    }

    btn.addEventListener('click', function() {
      sidebar.classList.toggle('expanded');
      if (main) main.classList.toggle('expanded-layout');
      if (!isMobile()) {
        localStorage.setItem(STORAGE_SIDEBAR, sidebar.classList.contains('expanded'));
      }
    });
  }

  // Toggle visibilidade senha
  function initPasswordToggle() {
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const wrapper = this.closest('.password-wrapper');
        const input = wrapper?.querySelector('input');
        if (!input) return;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.setAttribute('aria-label', type === 'password' ? 'Mostrar senha' : 'Ocultar senha');
      });
    });
  }

  // Menu suspenso mobile
  function initNavbarDropdown() {
    const toggle = document.getElementById('navbarDropdownToggle');
    const dropdown = document.getElementById('navbarDropdown');
    const container = document.querySelector('.navbar-actions-mobile');
    if (!toggle || !container) return;

    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      container.classList.toggle('open');
    });

    document.addEventListener('click', function() {
      container.classList.remove('open');
    });

    if (dropdown) {
      dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        if (e.target.closest('a')) {
          container.classList.remove('open');
        }
      });
    }
  }

  // Modal: abrir, fechar e dragging
  window.abrirModal = function(id) {
    var m = document.getElementById(id);
    if (m) {
      var dialog = m.querySelector('.modal-dialog');
      if (dialog) {
        dialog.style.position = '';
        dialog.style.left = '';
        dialog.style.top = '';
        dialog.style.margin = '';
      }
      m.classList.add('show');
      initModalDrag(m);
    }
  };
  window.fecharModal = function(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('show');
  };

  function initModalDrag(modalEl) {
    var dialog = modalEl.querySelector('.modal-dialog');
    var header = modalEl.querySelector('.modal-header[data-drag-handle]');
    if (!dialog || !header || dialog._dragInit) return;
    dialog._dragInit = true;
    var isDown = false, startX, startY, startLeft, startTop;
    header.addEventListener('mousedown', function(e) {
      if (e.target.closest('.close')) return;
      isDown = true;
      var rect = dialog.getBoundingClientRect();
      startX = e.clientX;
      startY = e.clientY;
      startLeft = rect.left;
      startTop = rect.top;
      dialog.style.position = 'fixed';
      dialog.style.margin = '0';
      dialog.style.left = startLeft + 'px';
      dialog.style.top = startTop + 'px';
    });
    document.addEventListener('mousemove', function(e) {
      if (!isDown) return;
      e.preventDefault();
      var dx = e.clientX - startX;
      var dy = e.clientY - startY;
      dialog.style.left = (startLeft + dx) + 'px';
      dialog.style.top = (startTop + dy) + 'px';
    });
    document.addEventListener('mouseup', function() {
      if (isDown) isDown = false;
    });
  }

  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
      e.target.classList.remove('show');
    }
  });

  // Overlay de carregamento global
  window.showLoadingOverlay = function() {
    var el = document.getElementById('loadingOverlay');
    if (el) {
      el.classList.add('show');
      el.setAttribute('aria-hidden', 'false');
    }
  };
  window.hideLoadingOverlay = function() {
    var el = document.getElementById('loadingOverlay');
    if (el) {
      el.classList.remove('show');
      el.setAttribute('aria-hidden', 'true');
    }
  };

  function initLoadingOverlayLinks() {
    document.addEventListener('click', function(e) {
      var a = e.target.closest('a');
      if (!a || a.target === '_blank') return;
      var href = a.getAttribute('href');
      if (!href || href === '#' || href.startsWith('javascript:')) return;
      var baseUrl = window.BASE_URL || '';
      var isInternal = baseUrl && href.indexOf(baseUrl) === 0;
      if (!isInternal && href.startsWith('/') && href.length > 1) isInternal = true;
      if (isInternal) showLoadingOverlay();
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initThemeToggle();
    initSidebarToggle();
    initPasswordToggle();
    initNavbarDropdown();
    initLoadingOverlayLinks();
  });
})();
