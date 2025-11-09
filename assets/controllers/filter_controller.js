import { Controller } from '@hotwired/stimulus';

/**
 * Gestion des filtres du catalogue
 * Usage: <form data-controller="filter">
 */
export default class extends Controller {
    static targets = ['form', 'reset'];

    connect() {
        // Auto-submit au changement (débounce pour inputs text)
        this.element.querySelectorAll('input[type="radio"], input[type="checkbox"], select').forEach(input => {
            input.addEventListener('change', () => this.submit());
        });

        this.element.querySelectorAll('input[type="number"], input[type="text"]').forEach(input => {
            let timeout;
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.submit(), 500);
            });
        });
    }

    submit() {
        this.element.requestSubmit();
    }

    reset(event) {
        event.preventDefault();
        // Reset tous les inputs
        this.element.reset();
        // Redirect vers URL propre
        window.location.href = this.element.action;
    }

    clearFilter(event) {
        const filterGroup = event.target.closest('.filter-group');
        const input = filterGroup.querySelector('input:checked');
        if (input) {
            input.checked = false;
            this.submit();
        }
    }
}
