/* Native DOM APIs; independent state for each shortcode instance. */
(() => {
  'use strict';
  document.querySelectorAll('.bae').forEach((root) => {
    const form = root.querySelector('form');
    const results = root.querySelector('.bae-results');
    const status = root.querySelector('.bae-status');
    const pagination = root.querySelector('.bae-pagination');
    const pageLabel = root.querySelector('.bae-page');
    const previous = pagination.querySelector('[data-direction="-1"]');
    const next = pagination.querySelector('[data-direction="1"]');
    let page = 1;
    let pages = next.disabled ? 1 : 2; // Exact count arrives with the first REST response.
    let activeFilters = new FormData(form);
    let controller;
    let sequence = 0;

    const updateNavigation = () => {
      previous.disabled = page <= 1;
      next.disabled = page >= pages;
      pageLabel.textContent = `Page ${page}`;
    };

    async function load(targetPage, filters) {
      controller?.abort();
      controller = new AbortController();
      const requestId = ++sequence;
      const url = new URL(root.dataset.endpoint, window.location.href);
      url.searchParams.set('search', filters.get('s') || '');
      url.searchParams.set('category', filters.get('cat') || '0');
      url.searchParams.set('page', String(targetPage));
      root.setAttribute('aria-busy', 'true');
      previous.disabled = true;
      next.disabled = true;
      status.textContent = 'Loading articles…';
      try {
        const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (typeof data.html !== 'string' || !Number.isInteger(data.total) || !Number.isInteger(data.pages)) {
          throw new Error('Unexpected response');
        }
        if (requestId !== sequence) return;
        // HTML comes only from this plugin's escaped, same-origin PHP renderer.
        results.innerHTML = data.html;
        page = targetPage;
        pages = data.pages;
        activeFilters = filters;
        status.textContent = data.total === 0 ? 'No articles found. Try another search or category.' : `${data.total} articles found. Page ${page} of ${pages}.`;
      } catch (error) {
        if (requestId !== sequence || error.name === 'AbortError') return;
        status.textContent = 'Articles could not be loaded. Your previous results are still shown. Please try again.';
      } finally {
        if (requestId === sequence) {
          root.removeAttribute('aria-busy');
          updateNavigation();
        }
      }
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      load(1, new FormData(form));
    });
    pagination.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-direction]');
      if (!button || button.disabled) return;
      load(page + Number(button.dataset.direction), activeFilters);
    });
    pagination.hidden = false;
    updateNavigation();
  });
})();
