import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['messageInput', 'submitBtn'];
    static values = {
        requestId: Number,
        estimatedPrice: Number
    };

    // ✅ Flag pour empêcher double submit
    isSubmitting = false;

    connect() {
        console.log('Validate modal controller connected for request', this.requestIdValue);
        this.modal = new Modal(this.element);
    }

    async submit(event) {
        // ✅ CRITIQUE : Empêcher propagation et comportement par défaut
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        // ✅ Bloquer si déjà en cours
        if (this.isSubmitting) {
            console.warn('Submit already in progress, ignoring duplicate');
            return;
        }

        console.log('Starting validation for request', this.requestIdValue);
        this.isSubmitting = true;

        const message = this.messageInputTarget.value;

        // Désactiver le bouton pendant la requête
        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Validation...';

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    custom_message: message
                })
            });

            const data = await response.json();

            console.log('Response status:', response.status);
            console.log('Response data:', data);

            // ✅ Vérifier data.success
            if (data.success) {
                console.log('Validation successful, reloading...');
                this.modal.hide();
                // ✅ Petit délai avant reload pour laisser la modale se fermer
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Validation failed:', data.error);
                this.submitBtnTarget.disabled = false;
                this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Valider la demande';
                this.isSubmitting = false;
            }
        } catch (error) {
            console.error('Validation error:', error);
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Valider la demande';
            this.isSubmitting = false;
        }
    }

    // Réinitialiser quand la modale se ferme
    disconnect() {
        console.log('Validate modal controller disconnected');
        this.isSubmitting = false;
    }
}
