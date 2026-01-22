import { Controller } from '@hotwired/stimulus';

/*
 * Cart Controller
 * Gère les interactions du panier (quantités, suppression, etc.)
 */
export default class extends Controller {
    static targets = ['item', 'quantityInput', 'subtotal', 'summary', 'totalAmount'];

    connect() {
        console.log('Cart controller connected');
    }

    /**
     * Augmente la quantité d'un produit
     */
    increaseQuantity(event) {
        const itemId = event.currentTarget.dataset.itemId;
        const input = this.element.querySelector(`input[data-item-id="${itemId}"]`);
        const currentValue = parseInt(input.value);
        const maxQuantity = parseInt(input.dataset.maxQuantity) || 10;

        if (currentValue < maxQuantity) {
            input.value = currentValue + 1;
            this.updateQuantity({ target: input });
        } else {
            this.showNotification('Stock maximum atteint', 'warning');
        }
    }

    /**
     * Diminue la quantité d'un produit
     */
    decreaseQuantity(event) {
        const itemId = event.currentTarget.dataset.itemId;
        const input = this.element.querySelector(`input[data-item-id="${itemId}"]`);
        const currentValue = parseInt(input.value);

        if (currentValue > 1) {
            input.value = currentValue - 1;
            this.updateQuantity({ target: input });
        }
    }

    /**
     * Met à jour la quantité via API
     */
    async updateQuantity(event) {
        const input = event.target;
        const itemId = input.dataset.itemId;
        const quantity = parseInt(input.value);
        const maxQuantity = parseInt(input.dataset.maxQuantity) || 10;

        if (quantity < 1) {
            this.showNotification('Quantité minimale : 1', 'error');
            input.value = 1;
            return;
        }

        if (quantity > maxQuantity) {
            this.showNotification(`Stock maximum : ${maxQuantity}`, 'error');
            input.value = maxQuantity;
            return;
        }

        if (isNaN(quantity)) {
            this.showNotification('Quantité invalide', 'error');
            input.value = 1;
            return;
        }

        try {
            const response = await fetch(`/panier/update/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    quantity: quantity,
                    _token: this.getCsrfToken('update_cart_item_' + itemId)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartUI(data);
                this.updateButtonStates(itemId, quantity, maxQuantity);
                this.showNotification(data.message, 'success');
            } else {
                this.showNotification(data.message, 'error');
                // Restaurer la valeur précédente
                input.value = input.dataset.previousValue || 1;
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour:', error);
            this.showNotification('Erreur lors de la mise à jour', 'error');
        }

        input.dataset.previousValue = input.value;
    }

    /**
     * Retire un produit du panier
     */
    async removeItem(event) {
        const itemId = event.currentTarget.dataset.itemId;

        if (!confirm('Êtes-vous sûr de vouloir retirer ce produit ?')) {
            return;
        }

        try {
            const response = await fetch(`/panier/remove/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    _token: this.getCsrfToken('remove_cart_item_' + itemId)
                })
            });

            const data = await response.json();

            if (data.success) {
                // Supprimer visuellement l'item
                const itemElement = document.getElementById(`cart-item-${itemId}`);
                if (itemElement) {
                    itemElement.style.opacity = '0';
                    itemElement.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        itemElement.remove();

                        // Si panier vide, recharger la page
                        if (data.isEmpty) {
                            window.location.reload();
                        }
                    }, 300);
                }

                this.updateCartUI(data);
                this.showNotification(data.message, 'success');
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            this.showNotification('Erreur lors de la suppression', 'error');
        }
    }

    /**
     * Vide le panier
     */
    async clearCart(event) {
        if (!confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
            return;
        }

        try {
            const response = await fetch('/panier/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    _token: this.getCsrfToken('clear_cart')
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Erreur lors du vidage du panier:', error);
            this.showNotification('Erreur lors du vidage du panier', 'error');
        }
    }

    /**
     * Met à jour l'interface du panier
     */
    updateCartUI(data) {
        // Mettre à jour le total
        if (this.hasTotalAmountTarget) {
            this.totalAmountTargets.forEach(target => {
                target.textContent = data.cartTotalFormatted;
            });
        }

        // Mettre à jour le badge navbar (si existe)
        this.updateNavbarBadge(data.cartQuantity);
    }

    /**
     * Met à jour le badge du panier dans la navbar
     */
    updateNavbarBadge(quantity) {
        const badge = document.querySelector('.navbar-cart-badge');
        if (badge) {
            badge.textContent = quantity;
            if (quantity === 0) {
                badge.style.display = 'none';
            } else {
                badge.style.display = 'flex';
            }
        }
    }

    /**
     * Affiche une notification toast
     */
    showNotification(message, type = 'info') {
        // Créer notification
        const notification = document.createElement('div');
        notification.className = `cart-notification cart-notification-${type}`;
        notification.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            ${type === 'success'
            ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
            : type === 'warning'
                ? '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'
                : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
        }
        </svg>
        <span>${message}</span>
    `;

        // Ajouter au DOM
        document.body.appendChild(notification);

        // Animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Supprimer après 3s
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Récupère un token CSRF depuis le DOM
     */
    getCsrfToken(tokenId) {
        // Méthode 1 : Meta tag
        const metaTag = document.querySelector(`meta[name="csrf-token-${tokenId}"]`);
        if (metaTag && metaTag.content) {
            return metaTag.content;
        }

        // Méthode 2 : Input hidden
        const input = document.querySelector(`input[name="_token"][data-token-id="${tokenId}"]`);
        if (input && input.value) {
            return input.value;
        }

        console.warn(`Token CSRF "${tokenId}" introuvable.`);
        return '';
    }
}
