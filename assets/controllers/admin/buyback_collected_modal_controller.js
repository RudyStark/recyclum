import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['priceInput', 'noteInput', 'submitBtn'];
    static values = {
        requestId: Number,
        estimatedPrice: Number
    };

    // ✅ Flag pour empêcher double submit
    isSubmitting = false;

    connect() {
        console.log('Collected modal controller connected for request', this.requestIdValue);
        this.modal = new Modal(this.element);
    }

    async submit(event) {
        // ✅ CRITIQUE : Empêcher propagation
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        // ✅ Bloquer si déjà en cours
        if (this.isSubmitting) {
            console.warn('Submit already in progress, ignoring duplicate');
            return;
        }

        const confirmedPrice = parseInt(this.priceInputTarget.value);
        const note = this.noteInputTarget.value;

        if (!confirmedPrice || confirmedPrice <= 0) {
            alert('Veuillez entrer un prix valide');
            return;
        }

        console.log('Starting collection confirmation for request', this.requestIdValue);
        this.isSubmitting = true;

        // Désactiver le bouton pendant la requête
        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enregistrement...';

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/mark-collected`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirmed_price: confirmedPrice,
                    note: note
                })
            });

            const data = await response.json();

            console.log('Collection response:', data);

            // ✅ Vérifier data.success
            if (data.success) {
                console.log('Collection successful, reloading...');
                this.modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Collection failed:', data.error);
                alert('Erreur : ' + (data.error || 'Une erreur est survenue'));
                this.submitBtnTarget.disabled = false;
                this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Confirmer la collecte';
                this.isSubmitting = false;
            }
        } catch (error) {
            console.error('Collection error:', error);
            alert('Erreur lors de l\'enregistrement');
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Confirmer la collecte';
            this.isSubmitting = false;
        }
    }

    // ✅ AJOUT : Réinitialiser
    disconnect() {
        console.log('Collected modal controller disconnected');
        this.isSubmitting = false;
    }
}
