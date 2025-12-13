import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        console.log('✅ Admin notifications dropdown controller connected');

        // Bind des méthodes
        this.boundCloseOnClickOutside = this.closeOnClickOutside.bind(this);
        this.boundStopPropagation = this.stopPropagation.bind(this);

        // Écoute des clics sur le document
        document.addEventListener('click', this.boundCloseOnClickOutside);

        // Empêche la fermeture si on clique dans le menu
        if (this.hasMenuTarget) {
            this.menuTarget.addEventListener('click', this.boundStopPropagation);
        }
    }

    disconnect() {
        console.log('❌ Admin notifications dropdown controller disconnected');

        // Nettoyage complet des event listeners
        document.removeEventListener('click', this.boundCloseOnClickOutside);

        if (this.hasMenuTarget) {
            this.menuTarget.removeEventListener('click', this.boundStopPropagation);
        }

        // Force la fermeture du menu
        this.close();
    }

    toggle(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        console.log('🔔 Toggle notifications');

        if (!this.hasMenuTarget) {
            console.error('❌ Menu target not found');
            return;
        }

        const isOpen = this.menuTarget.classList.contains('show');

        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (!this.hasMenuTarget) return;

        console.log('📂 Opening notifications menu');

        // Ferme tous les autres dropdowns ouverts
        document.querySelectorAll('.notification-dropdown-menu.show').forEach(menu => {
            if (menu !== this.menuTarget) {
                menu.classList.remove('show');
            }
        });

        this.menuTarget.classList.add('show');
    }

    close() {
        if (!this.hasMenuTarget) return;

        console.log('📁 Closing notifications menu');
        this.menuTarget.classList.remove('show');
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    closeOnClickOutside(event) {
        // Si on clique en dehors du dropdown, on le ferme
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
