import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle', 'drawer', 'backdrop'];

    connect() {
        // Fermer le drawer au chargement
        this.close();
    }

    toggle() {
        const isOpen = this.drawerTarget.classList.contains('is-open');

        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.drawerTarget.classList.add('is-open');
        this.backdropTarget.classList.add('is-visible');
        this.toggleTarget.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.drawerTarget.classList.remove('is-open');
        this.backdropTarget.classList.remove('is-visible');
        this.toggleTarget.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}
