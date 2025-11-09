import { Controller } from '@hotwired/stimulus';

/**
 * Gestion des CTA (smooth scroll vers sections)
 * Usage: <div data-controller="cta-actions">
 *          <a data-action="click->cta-actions#scrollToSection"
 *             data-cta-actions-section-param="contact">
 */
export default class extends Controller {
    scrollToSection(event) {
        event.preventDefault();

        const sectionId = event.params.section;
        const targetElement = document.getElementById(sectionId);

        if (targetElement) {
            const offset = 80; // Hauteur du header
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        } else {
            // Si la section n'existe pas encore (future implémentation),
            // on peut logger ou afficher un message
            console.warn(`Section #${sectionId} not found`);
        }
    }
}
