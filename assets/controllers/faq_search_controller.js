import { Controller } from '@hotwired/stimulus';
import { Collapse } from 'bootstrap';

export default class extends Controller {
    static targets = ['input', 'item', 'categories', 'section'];

    connect() {
        console.log('FAQ Search Controller connected - Split Screen');
        console.log('Found items:', this.itemTargets.length);
    }

    search(event) {
        const searchTerm = event.target.value.toLowerCase().trim();

        if (searchTerm === '') {
            this.resetSearch();
            return;
        }

        // Masquer les catégories pendant la recherche
        this.hideCategories();

        let hasResults = false;
        let foundCount = 0;
        let firstVisibleItem = null;

        // Parcourir tous les items
        this.itemTargets.forEach(item => {
            const question = item.querySelector('.faq-question-text');
            const answer = item.querySelector('.faq-answer-content');

            const questionText = question ? question.textContent.toLowerCase() : '';
            const answerText = answer ? answer.textContent.toLowerCase() : '';

            if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                item.style.display = 'block';
                hasResults = true;
                foundCount++;

                // Mémoriser le premier item visible
                if (!firstVisibleItem) {
                    firstVisibleItem = item;
                }

                // Auto-ouvrir l'accordéon
                this.openAccordion(item);
            } else {
                item.style.display = 'none';
                this.closeAccordion(item);
            }
        });

        // Masquer les sections vides
        this.hideSectionsWithoutResults();

        if (hasResults) {
            this.hideNoResults();
            this.showResultsCount(foundCount, searchTerm);

            // Scroll vers le premier résultat dans le panel de droite
            setTimeout(() => {
                if (firstVisibleItem) {
                    const resultsPanel = document.querySelector('.faq-results-panel');
                    if (resultsPanel) {
                        const itemPosition = firstVisibleItem.offsetTop;
                        resultsPanel.scrollTo({
                            top: itemPosition - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            }, 100);
        } else {
            this.showNoResults(searchTerm);
        }
    }

    hideSectionsWithoutResults() {
        this.sectionTargets.forEach(section => {
            const visibleItems = Array.from(section.querySelectorAll('.faq-item'))
                .filter(item => item.style.display !== 'none');

            if (visibleItems.length === 0) {
                section.style.display = 'none';
            } else {
                section.style.display = 'block';
            }
        });
    }

    hideCategories() {
        if (this.hasCategoriesTarget) {
            this.categoriesTarget.style.display = 'none';
        }
    }

    showCategories() {
        if (this.hasCategoriesTarget) {
            this.categoriesTarget.style.display = 'block';
        }
    }

    openAccordion(item) {
        const collapseElement = item.querySelector('.collapse');
        if (collapseElement && !collapseElement.classList.contains('show')) {
            const bsCollapse = new Collapse(collapseElement, { toggle: true });
        }
    }

    closeAccordion(item) {
        const collapseElement = item.querySelector('.collapse');
        if (collapseElement && collapseElement.classList.contains('show')) {
            const bsCollapse = Collapse.getInstance(collapseElement);
            if (bsCollapse) {
                bsCollapse.hide();
            }
        }
    }

    resetSearch() {
        // Réafficher tout
        this.itemTargets.forEach(item => {
            item.style.display = 'block';
        });

        this.sectionTargets.forEach(section => {
            section.style.display = 'block';
        });

        this.showCategories();
        this.hideNoResults();
        this.hideResultsCount();

        // Scroll en haut du panel de résultats
        const resultsPanel = document.querySelector('.faq-results-panel');
        if (resultsPanel) {
            resultsPanel.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    showResultsCount(count, term) {
        this.hideResultsCount();

        const container = document.getElementById('faq-results-info');
        if (!container) return;

        const resultsInfo = document.createElement('div');
        resultsInfo.id = 'faq-results-count';
        resultsInfo.className = 'faq-results-count';
        resultsInfo.innerHTML = `
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <strong>${count} résultat${count > 1 ? 's' : ''}</strong> trouvé${count > 1 ? 's' : ''} pour "<span class="text-primary">${this.escapeHtml(term)}</span>"
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="click->faq-search#clearSearch">
                    <i class="bi bi-x-circle"></i> Effacer
                </button>
            </div>
        `;

        container.appendChild(resultsInfo);
    }

    hideResultsCount() {
        const existing = document.getElementById('faq-results-count');
        if (existing) {
            existing.remove();
        }
    }

    clearSearch() {
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
            this.resetSearch();
            this.inputTarget.focus();
        }
    }

    showNoResults(term) {
        this.hideNoResults();

        const container = document.getElementById('faq-no-results-container');
        if (!container) return;

        const noResultsDiv = document.createElement('div');
        noResultsDiv.id = 'faq-no-results';
        noResultsDiv.className = 'faq-no-results';
        noResultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 3rem; color: #9CA3AF; margin-bottom: 1rem; display: block;"></i>
                <h3 class="h5 fw-bold mb-2">Aucun résultat trouvé</h3>
                <p class="text-soft mb-3">Aucune question ne correspond à "${this.escapeHtml(term)}"</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary" data-action="click->faq-search#clearSearch">
                        <i class="bi bi-arrow-left"></i> Retour
                    </button>
                    <a href="/contact" class="btn btn-primary">
                        <i class="bi bi-envelope"></i> Contactez-nous
                    </a>
                </div>
            </div>
        `;

        container.appendChild(noResultsDiv);
    }

    hideNoResults() {
        const existing = document.getElementById('faq-no-results');
        if (existing) {
            existing.remove();
        }
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    disconnect() {
        this.hideNoResults();
        this.hideResultsCount();
    }
}
