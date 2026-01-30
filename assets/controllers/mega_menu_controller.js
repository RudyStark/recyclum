import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel'];

    timeout = null;

    connect() {
        // S'assurer que le panel est fermé au démarrage
        this.close();
    }

    show() {
        clearTimeout(this.timeout);
        if (this.hasPanelTarget) {
            this.panelTarget.classList.add('is-visible');
        }
        this.element.classList.add('is-open');
        const button = this.element.querySelector('.nav-dropdown');
        if (button) {
            button.classList.add('is-open');
        }
    }

    hide() {
        this.timeout = setTimeout(() => {
            this.close();
        }, 150);
    }

    close() {
        if (this.hasPanelTarget) {
            this.panelTarget.classList.remove('is-visible');
        }
        this.element.classList.remove('is-open');
        const button = this.element.querySelector('.nav-dropdown');
        if (button) {
            button.classList.remove('is-open');
        }
    }

    toggle(event) {
        event.preventDefault();
        if (this.hasPanelTarget && this.panelTarget.classList.contains('is-visible')) {
            this.close();
        } else {
            this.show();
        }
    }
}
