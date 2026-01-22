import { Controller } from '@hotwired/stimulus';

/*
 * Add to Cart Controller
 * Gère le bouton "Ajouter au panier" sur les fiches produits
 */
export default class extends Controller {
    static targets = ['button', 'quantity'];
    static values = {
        productId: Number,
        productTitle: String
    };

    connect() {
        console.log('Add to Cart controller connected');
    }

    /**
     * Ajoute le produit au panier
     */
    async addToCart(event) {
        event.preventDefault();

        const button = this.hasButtonTarget ? this.buttonTarget : event.currentTarget;
        const quantity = this.hasQuantityTarget ? parseInt(this.quantityTarget.value) : 1;
        const productId = this.productIdValue;

        // 🔐 RÉCUPÉRER LE TOKEN CSRF
        const token = this.getCsrfToken('add_to_cart_' + productId);

        if (!token) {
            alert('⚠️ Token CSRF introuvable ! Veuillez recharger la page.');
            return;
        }

        // Désactiver le bouton
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Ajout...';

        try {
            const response = await fetch(`/panier/add/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    quantity: quantity,
                    _token: token  // 🔐 TOKEN CSRF ENVOYÉ
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccessModal(data);
                this.updateNavbarBadge(data.cartQuantity);
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier:', error);
            alert('Erreur lors de l\'ajout au panier');
        } finally {
            // Réactiver le bouton
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    /**
     * Affiche une modal de confirmation (CSS pur, sans Bootstrap)
     */
    showSuccessModal(data) {
        const modal = document.createElement('div');
        modal.className = 'cart-modal-overlay';
        modal.innerHTML = `
            <div class="cart-modal">
                <button class="cart-modal-close" onclick="this.closest('.cart-modal-overlay').remove()">
                    ×
                </button>
                <div class="cart-modal-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h3>Produit ajouté !</h3>
                <p>${data.message}</p>
                <div class="cart-modal-info">
                    <span>Votre panier</span>
                    <strong>${data.cartQuantity} article${data.cartQuantity > 1 ? 's' : ''}</strong>
                </div>
                <div class="cart-modal-actions">
                    <button onclick="this.closest('.cart-modal-overlay').remove()" class="btn-continue">
                        Continuer mes achats
                    </button>
                    <a href="/panier" class="btn-cart">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Voir le panier
                    </a>
                </div>
            </div>
        `;

        // Ajouter au DOM
        document.body.appendChild(modal);

        // Animation d'apparition
        setTimeout(() => modal.classList.add('show'), 10);

        // Fermeture en cliquant sur l'overlay
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        };
    }

    /**
     * Met à jour le badge du panier dans la navbar
     */
    updateNavbarBadge(quantity) {
        const badge = document.querySelector('.navbar-cart-badge');
        if (badge) {
            badge.textContent = quantity;
            badge.style.display = quantity > 0 ? 'flex' : 'none';

            // Animation pulse
            badge.classList.add('cart-badge-pulse');
            setTimeout(() => {
                badge.classList.remove('cart-badge-pulse');
            }, 600);
        }
    }

    /**
     * 🔐 RÉCUPÈRE UN TOKEN CSRF DEPUIS LE DOM
     * Méthode 1 : Meta tag (prioritaire)
     * Méthode 2 : Input hidden (fallback)
     */
    getCsrfToken(tokenId) {
        // Méthode 1 : Meta tag
        const metaTag = document.querySelector(`meta[name="csrf-token-${tokenId}"]`);
        if (metaTag && metaTag.content) {
            console.log('✅ Token CSRF trouvé via meta tag');
            return metaTag.content;
        }

        // Méthode 2 : Input hidden
        const input = document.querySelector(`input[name="_token"][data-token-id="${tokenId}"]`);
        if (input && input.value) {
            console.log('✅ Token CSRF trouvé via input hidden');
            return input.value;
        }

        // Aucun token trouvé
        console.error(`❌ Token CSRF "${tokenId}" introuvable !`);
        console.error('Vérifiez que le meta tag existe dans le <head> :');
        console.error(`<meta name="csrf-token-${tokenId}" content="...">`);
        return '';
    }
}
