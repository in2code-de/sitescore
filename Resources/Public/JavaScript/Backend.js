import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';

class SitescoreBackend {
  constructor() {
    DocumentService.ready().then(() => this.initialize());
  }

  initialize() {
    const container = document.querySelector('[data-sitescore-container]');
    if (!container) return;

    const analyzeButton = container.querySelector('[data-sitescore-action="analyze"]');
    if (analyzeButton) {
      analyzeButton.addEventListener('click', () => this.analyzePageClick(container));
    }

    const toggleButton = container.querySelector('[data-sitescore-toggle-suggestions]');
    if (toggleButton) {
      toggleButton.addEventListener('click', () => this.toggleSuggestions(container));
    }

    // Load existing analysis data on page load
    this.loadExistingAnalysis(container);
  }

  async analyzePageClick(container) {
    const pageId = parseInt(container.getAttribute('data-page-id') || '0', 10);

    if (!pageId ) {
      console.error('Sitescore: Missing page ID');
      return;
    }

    const loadingEl = container.querySelector('[data-sitescore-loading]');
    const errorEl = container.querySelector('[data-sitescore-error]');
    const resultsEl = container.querySelector('[data-sitescore-results]');

    // Show loading
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    resultsEl.style.display = 'none';

    try {
      const url = TYPO3?.settings?.ajaxUrls?.sitescore_analyze;
      if (!url) {
        throw new Error('AJAX route "sitescore_analyze" not available');
      }

      const response = await new AjaxRequest(url).post({ pageId });
      const data = await response.resolve();

      if (data?.success) {
        this.renderScores(container, data.scores);
        this.renderSuggestions(container, data.suggestions);
        loadingEl.style.display = 'none';
        resultsEl.style.display = 'block';
      } else {
        throw new Error(data?.error || 'Unknown error');
      }
    } catch (error) {
      console.error('Sitescore: Analysis failed', error);
      loadingEl.style.display = 'none';
      errorEl.style.display = 'block';
      const errorMsg = errorEl.querySelector('[data-sitescore-error-message]');
      if (errorMsg) {
        errorMsg.textContent = error.message || 'Unknown error';
      }
    }
  }

  renderScores(container, scores) {
    const categories = ['geo', 'performance', 'semantics', 'keywords', 'marketing'];

    categories.forEach(category => {
      const score = scores[category] || 0;
      const gauge = container.querySelector(`[data-sitescore-gauge="${category}"]`);
      if (!gauge) return;

      const valueEl = gauge.querySelector('[data-gauge-value]');
      const circleEl = gauge.querySelector('[data-gauge-circle]');

      if (valueEl) {
        valueEl.textContent = score;
      }

      if (circleEl) {
        const circumference = 2 * Math.PI * 40; // radius = 40
        const offset = circumference - (score / 100) * circumference;
        circleEl.style.strokeDasharray = `${circumference} ${circumference}`;
        circleEl.style.strokeDashoffset = offset;

        // Set color class based on score
        circleEl.classList.remove('score-high', 'score-medium', 'score-low');
        if (score >= 80) {
          circleEl.classList.add('score-high');
        } else if (score >= 50) {
          circleEl.classList.add('score-medium');
        } else {
          circleEl.classList.add('score-low');
        }
      }
    });
  }

  renderSuggestions(container, suggestions) {
    const listEl = container.querySelector('[data-sitescore-suggestions-list]');
    if (!listEl) return;

    listEl.innerHTML = '';

    if (!suggestions || suggestions.length === 0) {
      listEl.innerHTML = '<p class="text-muted">No suggestions available.</p>';
      return;
    }

    const ul = document.createElement('ul');
    ul.className = 'list-group';

    suggestions.forEach(suggestion => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex align-items-start';

      const icon = document.createElement('span');
      icon.className = 'me-2';
      if (suggestion.type === 'success') {
        icon.innerHTML = '✓';
        icon.style.color = '#28a745';
      } else if (suggestion.type === 'warning') {
        icon.innerHTML = '⚠️';
        icon.style.color = '#ffc107';
      } else {
        icon.innerHTML = '⚠️';
        icon.style.color = '#dc3545';
      }

      const message = document.createElement('span');
      message.textContent = suggestion.message || '';

      li.appendChild(icon);
      li.appendChild(message);
      ul.appendChild(li);
    });

    listEl.appendChild(ul);
  }

  toggleSuggestions(container) {
    const listEl = container.querySelector('[data-sitescore-suggestions-list]');
    const toggleButton = container.querySelector('[data-sitescore-toggle-suggestions]');
    const toggleText = toggleButton?.querySelector('[data-toggle-text]');

    if (!listEl) return;

    if (listEl.style.display === 'none') {
      listEl.style.display = 'block';
      if (toggleText) {
        toggleText.textContent = 'Hide details';
      }
    } else {
      listEl.style.display = 'none';
      if (toggleText) {
        toggleText.textContent = 'Show details';
      }
    }
  }

  async loadExistingAnalysis(container) {
    const pageId = parseInt(container.getAttribute('data-page-id') || '0', 10);
    if (!pageId) return;

    const resultsEl = container.querySelector('[data-sitescore-results]');

    try {
      const url = TYPO3?.settings?.ajaxUrls?.sitescore_load;
      if (!url) {
        console.error('Sitescore: AJAX route "sitescore_load" not available');
        return;
      }

      const separator = url.includes('?') ? '&' : '?';
      const fullUrl = url + separator + 'pageId=' + pageId;

      const response = await new AjaxRequest(fullUrl).get();
      const data = await response.resolve();

      if (data?.success && data?.hasData) {
        this.renderScores(container, data.scores);
        this.renderSuggestions(container, data.suggestions);
        resultsEl.style.display = 'block';
      }
    } catch (error) {
      console.error('Sitescore: Failed to load existing analysis', error);
    }
  }
}

export default new SitescoreBackend();
