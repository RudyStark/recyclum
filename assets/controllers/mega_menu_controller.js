import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'panel'];

    timeout = null;

    show() {
        clearTimeout(this.timeout);
        this.panelTarget.classList.add('is-visible');
        const button = this.element.querySelector('.nav-dropdown');
        if (button) {
            button.classList.add('is-open');
        }
    }

    hide() {
        this.timeout = setTimeout(() => {
            this.panelTarget.classList.remove('is-visible');
            const button = this.element.querySelector('.nav-dropdown');
            if (button) {
                button.classList.remove('is-open');
            }
        }, 200);
    }

    toggle(event) {
        event.preventDefault();
        if (this.panelTarget.classList.contains('is-visible')) {
            this.hide();
        } else {
            this.show();
        }
    }
}
