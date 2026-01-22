import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['cardElement', 'cardErrors', 'submitButton', 'buttonText', 'spinner'];
    static values = {
        publicKey: String,
        orderId: Number,
        orderTotal: String
    };

    stripe = null;
    cardElement = null;
    isInitialized = false;

    connect() {
        console.log('Stripe Payment Controller connected!');
        this.initializeStripe();
    }

    /**
     * Initialize Stripe, waiting for the library to be loaded if needed
     */
    async initializeStripe() {
        // Prevent double initialization
        if (this.isInitialized) {
            console.log('Stripe already initialized');
            return;
        }

        try {
            // Wait for Stripe to be available
            await this.waitForStripe();

            // Initialiser Stripe
            this.stripe = Stripe(this.publicKeyValue);
            const elements = this.stripe.elements();

            // Style personnalisé pour l'élément de carte
            const style = {
                base: {
                    fontSize: '16px',
                    color: '#1F2937',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#9CA3AF',
                    },
                },
                invalid: {
                    color: '#EF4444',
                    iconColor: '#EF4444'
                },
            };

            // Créer l'élément de carte SANS le code postal
            this.cardElement = elements.create('card', {
                style: style,
                hidePostalCode: true
            });

            // Vérifier que la target existe avant de monter
            if (this.hasCardElementTarget) {
                this.cardElement.mount(this.cardElementTarget);
                this.isInitialized = true;
                console.log('Card element mounted successfully!');
            } else {
                console.error('Card element target not found!');
            }

            // Écouter les changements pour afficher les erreurs en temps réel
            this.cardElement.on('change', (event) => {
                this.handleCardChange(event);
            });

        } catch (error) {
            console.error('Failed to initialize Stripe:', error);
            this.showError('Impossible de charger le module de paiement. Veuillez rafraîchir la page.');
        }
    }

    /**
     * Wait for Stripe.js to be loaded
     * If not loaded, dynamically load it
     */
    waitForStripe() {
        return new Promise((resolve, reject) => {
            // If Stripe is already available, resolve immediately
            if (typeof Stripe !== 'undefined') {
                console.log('Stripe already loaded');
                resolve();
                return;
            }

            console.log('Stripe not loaded, loading dynamically...');

            // Check if script already exists
            let stripeScript = document.querySelector('script[src="https://js.stripe.com/v3/"]');

            if (!stripeScript) {
                // Create and inject the script
                stripeScript = document.createElement('script');
                stripeScript.src = 'https://js.stripe.com/v3/';
                stripeScript.async = true;
                document.head.appendChild(stripeScript);
            }

            // Wait for script to load
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds max

            const checkStripe = setInterval(() => {
                attempts++;

                if (typeof Stripe !== 'undefined') {
                    clearInterval(checkStripe);
                    console.log('Stripe loaded successfully');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkStripe);
                    reject(new Error('Stripe failed to load after 5 seconds'));
                }
            }, 100);

            // Also listen for load event
            stripeScript.addEventListener('load', () => {
                if (typeof Stripe !== 'undefined') {
                    clearInterval(checkStripe);
                    resolve();
                }
            });

            stripeScript.addEventListener('error', () => {
                clearInterval(checkStripe);
                reject(new Error('Failed to load Stripe script'));
            });
        });
    }

    /**
     * Gérer les changements de l'élément de carte
     */
    handleCardChange(event) {
        if (event.error) {
            this.showError(event.error.message);
        } else {
            this.hideError();
        }
    }

    /**
     * Soumettre le paiement
     */
    async submit(event) {
        event.preventDefault();

        console.log('Payment form submitted');

        // Check if Stripe is initialized
        if (!this.isInitialized || !this.stripe || !this.cardElement) {
            console.log('Stripe not ready, attempting to initialize...');
            await this.initializeStripe();

            if (!this.isInitialized) {
                this.showError('Le module de paiement n\'est pas prêt. Veuillez rafraîchir la page.');
                return;
            }
        }

        // Désactiver le bouton
        this.setLoading(true);
        this.hideError();

        try {
            // 1. Créer le Payment Intent
            console.log('Creating payment intent for order:', this.orderIdValue);
            const paymentIntentData = await this.createPaymentIntent();

            if (!paymentIntentData.clientSecret) {
                throw new Error('Erreur lors de la création du paiement');
            }

            console.log('Payment intent created successfully');

            // 2. Confirmer le paiement avec Stripe
            console.log('Confirming card payment...');
            const { error, paymentIntent } = await this.stripe.confirmCardPayment(
                paymentIntentData.clientSecret,
                {
                    payment_method: {
                        card: this.cardElement,
                    },
                }
            );

            if (error) {
                throw new Error(error.message);
            }

            console.log('Payment confirmed with Stripe:', paymentIntent.id);

            // 3. Confirmer le paiement côté serveur
            console.log('Confirming payment on server...');
            const confirmData = await this.confirmPayment(paymentIntent.id);

            if (confirmData.success) {
                console.log('Payment confirmed on server, redirecting...');
                // Rediriger vers la page de confirmation
                window.location.href = confirmData.redirectUrl;
            } else {
                throw new Error(confirmData.error || 'Erreur lors de la confirmation du paiement');
            }

        } catch (error) {
            console.error('Payment error:', error);
            this.showError(error.message);
            this.setLoading(false);
        }
    }

    /**
     * Créer le Payment Intent via l'API
     */
    async createPaymentIntent() {
        const response = await fetch('/checkout/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                orderId: this.orderIdValue
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la création du paiement');
        }

        return await response.json();
    }

    /**
     * Confirmer le paiement côté serveur
     */
    async confirmPayment(paymentIntentId) {
        const response = await fetch('/checkout/confirm-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                paymentIntentId: paymentIntentId
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la confirmation');
        }

        return await response.json();
    }

    /**
     * Afficher une erreur
     */
    showError(message) {
        if (this.hasCardErrorsTarget) {
            this.cardErrorsTarget.textContent = message;
            this.cardErrorsTarget.style.display = 'block';
        }
    }

    /**
     * Masquer l'erreur
     */
    hideError() {
        if (this.hasCardErrorsTarget) {
            this.cardErrorsTarget.textContent = '';
            this.cardErrorsTarget.style.display = 'none';
        }
    }

    /**
     * Gérer l'état de chargement du bouton
     */
    setLoading(loading) {
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = loading;
        }

        if (this.hasButtonTextTarget && this.hasSpinnerTarget) {
            if (loading) {
                this.buttonTextTarget.style.display = 'none';
                this.spinnerTarget.style.display = 'inline-block';
            } else {
                this.buttonTextTarget.style.display = 'inline';
                this.spinnerTarget.style.display = 'none';
            }
        }
    }

    /**
     * Nettoyage lors de la déconnexion
     */
    disconnect() {
        console.log('Stripe Payment Controller disconnecting...');
        if (this.cardElement) {
            this.cardElement.destroy();
            this.cardElement = null;
        }
        this.stripe = null;
        this.isInitialized = false;
    }
}
