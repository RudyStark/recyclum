import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'form',
        'subjectOption',
        'orderSection',
        'orderSelect',
        'productSection',
        'productSelect',
        'warrantyInfo',
        'warrantyStatus'
    ];

    connect() {
        this.ordersLoaded = false;
        this.selectedSubject = null;
    }

    /**
     * Quand on change le sujet
     */
    async onSubjectChange(event) {
        const radio = event.target;
        const requiresOrder = radio.dataset.requiresOrder === '1';
        const requiresProduct = radio.dataset.requiresProduct === '1';
        this.selectedSubject = radio.value;

        // Highlight l'option sélectionnée
        this.subjectOptionTargets.forEach(option => {
            option.classList.toggle('selected', option.querySelector('input').value === this.selectedSubject);
        });

        // Afficher/masquer la section commande
        if (this.hasOrderSectionTarget) {
            if (requiresOrder || requiresProduct) {
                this.orderSectionTarget.style.display = 'block';
                this.orderSectionTarget.classList.add('fade-in');

                // Charger les commandes si pas encore fait
                if (!this.ordersLoaded) {
                    await this.loadOrders();
                }
            } else {
                this.orderSectionTarget.style.display = 'none';
            }

            // Afficher/masquer la section produit
            if (this.hasProductSectionTarget) {
                this.productSectionTarget.style.display = requiresProduct ? 'block' : 'none';
            }
        }
    }

    /**
     * Charge les commandes de l'utilisateur
     */
    async loadOrders() {
        if (!this.hasOrderSelectTarget) return;

        try {
            const response = await fetch('/contact/orders');
            const data = await response.json();

            this.orderSelectTarget.innerHTML = '<option value="">-- Choisir une commande --</option>';

            data.orders.forEach(order => {
                const option = document.createElement('option');
                option.value = order.id;
                option.textContent = `${order.orderNumber} - ${order.date} (${order.total})`;
                this.orderSelectTarget.appendChild(option);
            });

            this.ordersLoaded = true;
        } catch (error) {
            console.error('Erreur chargement commandes:', error);
        }
    }

    /**
     * Quand on change la commande sélectionnée
     */
    async onOrderChange(event) {
        const orderId = event.target.value;

        if (!orderId || !this.hasProductSelectTarget) {
            if (this.hasProductSectionTarget) {
                this.productSelectTarget.innerHTML = '<option value="">-- Choisir un produit --</option>';
            }
            if (this.hasWarrantyInfoTarget) {
                this.warrantyInfoTarget.style.display = 'none';
            }
            return;
        }

        // Charger les produits de la commande
        await this.loadOrderItems(orderId);
    }

    /**
     * Charge les produits d'une commande
     */
    async loadOrderItems(orderId) {
        if (!this.hasProductSelectTarget) return;

        try {
            const response = await fetch(`/contact/order/${orderId}/items`);
            const data = await response.json();

            this.productSelectTarget.innerHTML = '<option value="">-- Choisir un produit --</option>';

            data.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.productName} (${item.price})`;
                option.dataset.warrantyStatus = item.warrantyStatus;
                option.dataset.warrantyEnd = item.warrantyEnd || '';
                this.productSelectTarget.appendChild(option);
            });

            // Écouter le changement de produit
            this.productSelectTarget.addEventListener('change', (e) => this.onProductChange(e));
        } catch (error) {
            console.error('Erreur chargement produits:', error);
        }
    }

    /**
     * Quand on change le produit sélectionné
     */
    onProductChange(event) {
        const selectedOption = event.target.selectedOptions[0];

        if (!selectedOption || !selectedOption.value) {
            if (this.hasWarrantyInfoTarget) {
                this.warrantyInfoTarget.style.display = 'none';
            }
            return;
        }

        const warrantyStatus = selectedOption.dataset.warrantyStatus;
        const warrantyEnd = selectedOption.dataset.warrantyEnd;

        if (this.hasWarrantyInfoTarget && this.hasWarrantyStatusTarget) {
            if (warrantyStatus && warrantyStatus !== 'N/A') {
                this.warrantyInfoTarget.style.display = 'flex';
                this.warrantyInfoTarget.classList.remove('valid', 'expired');

                if (warrantyStatus === 'Valide') {
                    this.warrantyInfoTarget.classList.add('valid');
                    this.warrantyStatusTarget.textContent = `Garantie valide jusqu'au ${warrantyEnd}`;
                } else {
                    this.warrantyInfoTarget.classList.add('expired');
                    this.warrantyStatusTarget.textContent = 'Garantie expirée';
                }
            } else {
                this.warrantyInfoTarget.style.display = 'none';
            }
        }
    }
}
