import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        console.log('Admin notifications dropdown controller connected');

        // Ferme le dropdown si on clique ailleurs
        this.boundCloseOnClickOutside = this.closeOnClickOutside.bind(this);
        document.addEventListener('click', this.boundCloseOnClickOutside);

        // Empêche la fermeture si on clique dans le dropdown
        this.menuTarget.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    disconnect() {
        document.removeEventListener('click', this.boundCloseOnClickOutside);
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        const isOpen = this.menuTarget.classList.contains('show');

        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        // Ferme tous les autres dropdowns ouverts
        document.querySelectorAll('.notification-dropdown-menu.show').forEach(menu => {
            if (menu !== this.menuTarget) {
                menu.classList.remove('show');
            }
        });

        this.menuTarget.classList.add('show');
    }

    close() {
        this.menuTarget.classList.remove('show');
    }

    closeOnClickOutside(event) {
        // Si on clique en dehors du dropdown, on le ferme
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
