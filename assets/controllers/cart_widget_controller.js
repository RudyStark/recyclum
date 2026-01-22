import { Controller } from '@hotwired/stimulus';

/*
 * Cart Widget Controller
 * Initialise et met à jour le badge du panier dans la navbar
 */
export default class extends Controller {
    connect() {
        console.log('Cart Widget connected');
        this.loadCartQuantity();
    }

    /**
     * Charge la quantité du panier au chargement de la page
     */
    async loadCartQuantity() {
        try {
            const response = await fetch('/panier/widget');
            const data = await response.json();

            this.updateBadge(data.quantity);
        } catch (error) {
            console.error('Erreur chargement panier:', error);
        }
    }

    /**
     * Met à jour le badge
     */
    updateBadge(quantity) {
        const badge = document.querySelector('.navbar-cart-badge');
        if (badge) {
            badge.textContent = quantity;
            badge.style.display = quantity > 0 ? 'flex' : 'none';
        }
    }
}
