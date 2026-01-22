import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'panel'];

    connect() {
        // Close dropdown when clicking outside
        this.clickOutsideHandler = this.clickOutside.bind(this);
        document.addEventListener('click', this.clickOutsideHandler);

        // Close dropdown on escape key
        this.escapeHandler = this.handleEscape.bind(this);
        document.addEventListener('keydown', this.escapeHandler);
    }

    disconnect() {
        document.removeEventListener('click', this.clickOutsideHandler);
        document.removeEventListener('keydown', this.escapeHandler);
    }

    toggle(event) {
        event.stopPropagation();
        const isOpen = this.element.classList.contains('is-open');

        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.element.classList.add('is-open');
        this.triggerTarget.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.element.classList.remove('is-open');
        this.triggerTarget.setAttribute('aria-expanded', 'false');
    }

    clickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }
}
