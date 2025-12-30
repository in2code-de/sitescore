import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';

export default class SitescoreBackend {
  'use strict';

  #containerSelector = '[data-sitescore-container]';
  #categories = ['geo', 'performance', 'semantics', 'keywords', 'accessibility'];
  #gaugeRadius = 40;

  constructor() {
    DocumentService.ready().then(() => this.#initialize());
  }

  #initialize() {
    const container = document.querySelector(this.#containerSelector);
    if (!container) {
      return;
    }

    this.#attachEventListeners(container);
    this.#loadExistingAnalysis(container);
  };

  #attachEventListeners(container) {
    const analyzeButton = container.querySelector('[data-sitescore-action="analyze"]');
    const contentEl = document.getElementById('sitescore-content');
    const suggestionsEl = document.getElementById('sitescore-suggestions-list');

    if (analyzeButton) {
      analyzeButton.addEventListener('click', () => this.#analyzePageClick(container));
    }
    if (contentEl) {
      contentEl.addEventListener('hidden.bs.collapse', () => this.#saveCollapseState(true));
      contentEl.addEventListener('shown.bs.collapse', () => {
        this.#saveCollapseState(false);
        this.#showResultsIfAvailable(container);
      });
    }
    if (suggestionsEl) {
      suggestionsEl.addEventListener('show.bs.collapse', () => this.#updateSuggestionsButton(container, true));
      suggestionsEl.addEventListener('hide.bs.collapse', () => this.#updateSuggestionsButton(container, false));
    }
  };

  /*
   * AJAX request handlers
   */

  async #analyzePageClick(container) {
    const pageId = parseInt(container.getAttribute('data-page-id') || '0', 10);
    if (!pageId) {
      console.error('Sitescore: Missing page ID');
      return;
    }

    const loadingEl = container.querySelector('[data-sitescore-loading]');
    const errorEl = container.querySelector('[data-sitescore-error]');
    const resultsEl = container.querySelector('[data-sitescore-results]');

    this.#showLoading(loadingEl, errorEl, resultsEl);

    try {
      const url = TYPO3?.settings?.ajaxUrls?.sitescore_analyze;
      if (!url) {
        throw new Error('AJAX route "sitescore_analyze" not available');
      }

      const language = this.#getLanguageFromUrl();
      const separator = url.includes('?') ? '&' : '?';
      const response = await new AjaxRequest(url + separator + 'language=' + language).post({pageId});
      const data = await response.resolve();

      data?.success
        ? this.#handleAnalysisSuccess(container, data, loadingEl, resultsEl)
        : this.#handleAnalysisError(errorEl, loadingEl, data?.error);
    } catch (error) {
      this.#handleAnalysisError(errorEl, loadingEl, error.message);
    }
  };

  async #loadExistingAnalysis(container) {
    const pageId = parseInt(container.getAttribute('data-page-id') || '0', 10);
    if (!pageId) {
      return;
    }

    const resultsEl = container.querySelector('[data-sitescore-results]');
    const contentEl = document.getElementById('sitescore-content');
    const isVisible = contentEl?.classList.contains('show');

    try {
      const url = TYPO3?.settings?.ajaxUrls?.sitescore_load;
      if (!url) {
        console.error('Sitescore: AJAX route "sitescore_load" not available');
        return;
      }

      const language = this.#getLanguageFromUrl();
      const separator = url.includes('?') ? '&' : '?';
      const response = await new AjaxRequest(url + separator + 'pageId=' + pageId + '&language=' + language).get();
      const data = await response.resolve();

      if (data?.success && data?.hasData) {
        this.#renderScores(container, data.scores);
        this.#renderSuggestions(container, data.suggestions);
        this.#enableCollapseToggle(container);
        isVisible && (resultsEl.style.display = 'block');
      }
    } catch (error) {
      console.error('Sitescore: Failed to load existing analysis', error);
    }
  };

  async #saveCollapseState(collapsed) {
    try {
      const url = TYPO3?.settings?.ajaxUrls?.sitescore_toggle;
      if (!url) {
        console.error('Sitescore: AJAX route "sitescore_toggle" not available');
        return;
      }
      await new AjaxRequest(url).post({collapsed});
    } catch (error) {
      console.error('Sitescore: Failed to save collapse state', error);
    }
  };

  /*
   * Rendering methods
   */

  #renderScores(container, scores) {
    for (let i = 0; i < this.#categories.length; i++) {
      const category = this.#categories[i];
      const score = scores[category] || 0;
      const gauge = container.querySelector(`[data-sitescore-gauge="${category}"]`);
      if (!gauge) {
        continue;
      }

      const valueEl = gauge.querySelector('[data-gauge-value]');
      const circleEl = gauge.querySelector('[data-gauge-circle]');

      valueEl && (valueEl.textContent = score);

      if (circleEl) {
        const circumference = 2 * Math.PI * this.#gaugeRadius;
        const offset = circumference - (score / 100) * circumference;
        circleEl.style.strokeDasharray = `${circumference} ${circumference}`;
        circleEl.style.strokeDashoffset = String(offset);

        circleEl.classList.remove('score-high', 'score-medium', 'score-low');
        circleEl.classList.add(score >= 80 ? 'score-high' : score >= 50 ? 'score-medium' : 'score-low');
      }
    }
  };

  #renderSuggestions(container, suggestions) {
    const listEl = container.querySelector('[data-sitescore-suggestions-list]');
    const toggleButton = container.querySelector('[data-sitescore-toggle-suggestions]');
    const countEl = toggleButton?.querySelector('[data-toggle-count]');
    const suggestionsContainer = container.querySelector('[data-sitescore-suggestions]');
    const labelNone = suggestionsContainer?.dataset.labelNone || 'No suggestions available.';

    if (!listEl) {
      return;
    }

    listEl.innerHTML = '';

    if (!suggestions || suggestions.length === 0) {
      listEl.innerHTML = `<p class="text-muted">${labelNone}</p>`;
      countEl && (countEl.textContent = ' (0)');
      return;
    }

    countEl && (countEl.textContent = ` (${suggestions.length})`);

    const ul = document.createElement('ul');
    ul.className = 'list-group';

    for (let i = 0; i < suggestions.length; i++) {
      const suggestion = suggestions[i];
      const li = this.#createSuggestionElement(suggestion);
      ul.appendChild(li);
    }

    listEl.appendChild(ul);
  };

  #createSuggestionElement(suggestion) {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex align-items-start';

    const icon = this.#createIconElement(suggestion.type);
    const message = document.createElement('span');
    message.textContent = suggestion.message || '';

    li.appendChild(icon);
    li.appendChild(message);

    return li;
  };

  #createIconElement(type) {
    const icon = document.createElement('span');
    icon.className = 'me-2';

    const iconMap = {
      'success': {html: '✓', color: '#28a745'},
      'warning': {html: '⚠️', color: '#ffc107'},
      'default': {html: '⚠️', color: '#dc3545'}
    };

    const iconConfig = iconMap[type] || iconMap.default;
    icon.innerHTML = iconConfig.html;
    icon.style.color = iconConfig.color;

    return icon;
  };

  #updateSuggestionsButton(container, isShowing) {
    const toggleButton = container.querySelector('[data-sitescore-toggle-suggestions]');
    const toggleText = toggleButton?.querySelector('[data-toggle-text]');
    const suggestionsContainer = container.querySelector('[data-sitescore-suggestions]');

    if (!toggleButton || !suggestionsContainer) {
      return;
    }

    const labelShow = suggestionsContainer.dataset.labelShow || 'Show details';
    const labelHide = suggestionsContainer.dataset.labelHide || 'Hide details';

    toggleText && (toggleText.textContent = isShowing ? labelHide : labelShow);
  };

  /*
   * Helper methods
   */

  #enableCollapseToggle(container) {
    const toggleButton = container.querySelector('[data-sitescore-action="toggle-collapse"]');
    const toggleIcon = container.querySelector('[data-sitescore-toggle-icon]');

    if (toggleButton) {
      toggleButton.style.pointerEvents = 'auto';
    }
    if (toggleIcon) {
      toggleIcon.style.display = 'inline';
    }
  };

  #getLanguageFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return parseInt(urlParams.get('language') || urlParams.get('L') || '0', 10);
  };

  #showLoading(loadingEl, errorEl, resultsEl) {
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    resultsEl.style.display = 'none';
  };

  #handleAnalysisSuccess(container, data, loadingEl, resultsEl) {
    this.#renderScores(container, data.scores);
    this.#renderSuggestions(container, data.suggestions);
    this.#enableCollapseToggle(container);
    loadingEl.style.display = 'none';
    resultsEl.style.display = 'block';
  };

  #handleAnalysisError(errorEl, loadingEl, errorMessage) {
    console.error('Sitescore: Analysis failed', errorMessage);
    loadingEl.style.display = 'none';
    errorEl.style.display = 'block';
    const errorMsg = errorEl.querySelector('[data-sitescore-error-message]');
    errorMsg && (errorMsg.textContent = errorMessage || 'Page could not be analyzed');
  };

  #showResultsIfAvailable(container) {
    const resultsEl = container.querySelector('[data-sitescore-results]');
    const loadingEl = container.querySelector('[data-sitescore-loading]');
    const errorEl = container.querySelector('[data-sitescore-error]');

    const hasResults = resultsEl && resultsEl.querySelector('[data-gauge-value]')?.textContent !== '--';

    if (hasResults) {
      loadingEl.style.display = 'none';
      errorEl.style.display = 'none';
      resultsEl.style.display = 'block';
    }
  };
}

new SitescoreBackend();
