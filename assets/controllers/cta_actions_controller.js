import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    scrollToSection(event) {
        event.preventDefault();

        const sectionId = event.params.section;
        const section = document.getElementById(sectionId);

        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        } else {
            // Fallback : si la section n'existe pas, redirige vers la page contact
            window.location.href = `/contact?produit=${this.getProductSlug()}`;
        }
    }

    getProductSlug() {
        // Récupère le slug depuis l'URL
        const pathParts = window.location.pathname.split('/');
        return pathParts[pathParts.length - 1];
    }
}
