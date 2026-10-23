(() => {
  function textWithHighlight(text, term) {
    const fragment = document.createDocumentFragment();
    const value = String(text || '');
    const index = value.toLocaleLowerCase().indexOf(term.toLocaleLowerCase());

    if (!term || index < 0) {
      fragment.append(document.createTextNode(value));
      return fragment;
    }

    fragment.append(document.createTextNode(value.slice(0, index)));
    const mark = document.createElement('mark');
    mark.className = 'rounded bg-warning/20 px-0 text-inherit dark:bg-warning/15';
    mark.textContent = value.slice(index, index + term.length);
    fragment.append(mark, document.createTextNode(value.slice(index + term.length)));
    return fragment;
  }

  function message(text, detail = '') {
    const wrapper = document.createElement('div');
    wrapper.className = 'px-4 py-8 text-center';
    const title = document.createElement('p');
    title.className = 'font-medium text-slate-700 dark:text-navy-100';
    title.textContent = text;
    wrapper.append(title);

    if (detail) {
      const description = document.createElement('p');
      description.className = 'mt-1 text-xs text-slate-400 dark:text-navy-300';
      description.textContent = detail;
      wrapper.append(description);
    }

    return wrapper;
  }

  function resultRow(result, term, remember) {
    const link = document.createElement('a');
    link.href = result.url;
    link.dataset.searchOption = '';
    link.className = 'group flex items-center gap-2.5 px-3 py-2.5 outline-hidden transition-colors hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600';
    link.addEventListener('click', () => remember(term));

    const icon = document.createElement('span');
    icon.className = `flex size-8 shrink-0 items-center justify-center rounded-full text-tiny-plus font-semibold ${result.icon_class}`;
    icon.textContent = result.icon;

    const content = document.createElement('span');
    content.className = 'min-w-0 grow';
    const title = document.createElement('span');
    title.className = 'block truncate text-xs-plus font-medium text-slate-700 dark:text-navy-100';
    title.append(textWithHighlight(result.title, term));
    content.append(title);

    if (result.subtitle) {
      const subtitle = document.createElement('span');
      subtitle.className = 'mt-0.5 block truncate text-tiny-plus text-slate-400 dark:text-navy-300';
      subtitle.append(textWithHighlight(result.subtitle, term));
      content.append(subtitle);
    }

    const type = document.createElement('span');
    type.className = 'shrink-0 text-tiny text-slate-400 dark:text-navy-300';
    type.textContent = result.type;
    link.append(icon, content, type);
    return link;
  }

  function initGlobalSearch() {
    const root = document.querySelector('[data-global-search]');
    if (!root || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    const endpoint = root.dataset.endpoint;
    const rememberEndpoint = root.dataset.rememberEndpoint;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const inputs = [...document.querySelectorAll('#searchbar-ref, .mobile-searchbar-input')];
    const panels = [...document.querySelectorAll('[data-global-search-results]')];
    let timer;
    let controller;
    let activeIndex = -1;
    let currentTerm = '';

    function syncInputs(value, source) {
      inputs.forEach((input) => {
        if (input !== source) input.value = value;
      });
    }

    function renderRecent(recent) {
      panels.forEach((panel) => {
        panel.replaceChildren();
        const header = document.createElement('div');
        header.className = 'flex items-center justify-between bg-slate-100 px-3 py-1.5 dark:bg-navy-800';
        const label = document.createElement('p');
        label.className = 'text-xs uppercase';
        label.textContent = 'Pencarian Terbaru';
        header.append(label);
        panel.append(header);

        if (!recent.length) {
          panel.append(message('Belum ada pencarian terbaru', 'Ketik minimal 2 karakter untuk mulai mencari.'));
          return;
        }

        recent.forEach((term) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.dataset.searchOption = '';
          button.className = 'group flex w-full items-center gap-2.5 px-3 py-2.5 text-left outline-hidden transition-colors hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600';
          button.innerHTML = '<span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-150 text-slate-400 dark:bg-navy-500 dark:text-navy-200">↗</span>';
          const text = document.createElement('span');
          text.className = 'truncate text-xs-plus font-medium text-slate-700 dark:text-navy-100';
          text.textContent = term;
          button.append(text);
          button.addEventListener('click', () => {
            inputs.forEach((input) => { input.value = term; });
            performSearch(term);
          });
          panel.append(button);
        });
      });
      activeIndex = -1;
    }

    function renderResults(payload) {
      panels.forEach((panel) => {
        panel.replaceChildren();
        if (!payload.results.length) {
          panel.append(message('Tidak ada hasil ditemukan', 'Coba gunakan nama atau kata kunci yang berbeda.'));
          return;
        }

        payload.results.forEach((result) => panel.append(resultRow(result, payload.query, remember)));

        const all = document.createElement('a');
        all.href = payload.all_results_url;
        all.dataset.searchOption = '';
        all.className = 'flex items-center justify-center border-t border-slate-150 px-3 py-2.5 text-xs-plus font-medium text-primary outline-hidden transition-colors hover:bg-slate-100 focus:bg-slate-100 dark:border-navy-600 dark:text-accent-light dark:hover:bg-navy-600 dark:focus:bg-navy-600';
        all.textContent = `Lihat semua hasil untuk “${payload.query}”`;
        all.addEventListener('click', () => remember(payload.query));
        panel.append(all);
      });
      activeIndex = -1;
    }

    function renderLoading() {
      panels.forEach((panel) => panel.replaceChildren(message('Mencari...', 'Mohon tunggu sebentar.')));
    }

    async function remember(term) {
      try {
        await fetch(rememberEndpoint, {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
          body: JSON.stringify({q: term}),
          keepalive: true,
        });
      } catch (_) {}
    }

    async function performSearch(term = '') {
      currentTerm = term.trim();
      controller?.abort();
      controller = new AbortController();
      renderLoading();

      try {
        const response = await fetch(`${endpoint}?q=${encodeURIComponent(currentTerm)}`, {
          headers: {'Accept': 'application/json'},
          signal: controller.signal,
        });
        if (!response.ok) throw new Error('Pencarian gagal dimuat.');
        const payload = await response.json();
        currentTerm ? renderResults(payload) : renderRecent(payload.recent || []);
      } catch (error) {
        if (error.name !== 'AbortError') {
          panels.forEach((panel) => panel.replaceChildren(message('Pencarian tidak dapat dimuat', 'Silakan coba kembali.')));
        }
      }
    }

    function selectOption(input, direction) {
      const panel = input.closest('.mobile-searchbar')?.querySelector('[data-global-search-results]') || document.querySelector('#searchbar-box [data-global-search-results]');
      const options = [...(panel?.querySelectorAll('[data-search-option]') || [])];
      if (!options.length) return;
      activeIndex = (activeIndex + direction + options.length) % options.length;
      options.forEach((item, index) => {
        item.classList.toggle('bg-slate-100', index === activeIndex);
        item.classList.toggle('dark:bg-navy-600', index === activeIndex);
        item.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false');
      });
      options[activeIndex].scrollIntoView({block: 'nearest'});
    }

    inputs.forEach((input) => {
      input.setAttribute('autocomplete', 'off');
      input.setAttribute('aria-label', 'Pencarian global');
      input.addEventListener('focus', () => {
        if (!input.value.trim()) performSearch();
      });
      input.addEventListener('input', () => {
        syncInputs(input.value, input);
        clearTimeout(timer);
        const term = input.value.trim();
        if (!term) return performSearch();
        if (term.length < Number(root.dataset.minimumCharacters || 2)) {
          panels.forEach((panel) => panel.replaceChildren(message('Teruskan mengetik', 'Masukkan minimal 2 karakter.')));
          return;
        }
        timer = setTimeout(() => performSearch(term), 300);
      });
      input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          selectOption(input, event.key === 'ArrowDown' ? 1 : -1);
        }
        if (event.key === 'Enter') {
          const panel = input.closest('.mobile-searchbar')?.querySelector('[data-global-search-results]') || document.querySelector('#searchbar-box [data-global-search-results]');
          const options = [...(panel?.querySelectorAll('[data-search-option]') || [])];
          if (activeIndex >= 0 && options[activeIndex]) return options[activeIndex].click();
          if (currentTerm.length < Number(root.dataset.minimumCharacters || 2)) return;
          event.preventDefault();
          remember(currentTerm);
          window.location.assign(`${root.dataset.resultsUrl}?q=${encodeURIComponent(currentTerm)}`);
        }
        if (event.key === 'Escape') {
          input.blur();
          input.closest('.mobile-searchbar')?.querySelector('.mobile-searchbar-hide')?.click();
        }
      });
    });

    performSearch();
  }

  document.addEventListener('DOMContentLoaded', initGlobalSearch);
  document.addEventListener('livewire:navigated', initGlobalSearch);
})();
