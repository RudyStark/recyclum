import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['amountInput', 'methodSelect', 'noteInput', 'submitBtn'];
    static values = {
        requestId: Number,
        finalPrice: Number
    };

    // ✅ Flag pour empêcher double submit
    isSubmitting = false;

    connect() {
        console.log('Payment modal controller connected for request', this.requestIdValue);
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

        const amount = parseInt(this.amountInputTarget.value);
        const method = this.methodSelectTarget.value;
        const note = this.noteInputTarget.value;

        if (!amount || amount <= 0) {
            alert('Veuillez entrer un montant valide');
            return;
        }

        console.log('Starting payment submission for request', this.requestIdValue);
        this.isSubmitting = true;

        // Désactiver le bouton pendant la requête
        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enregistrement...';

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/mark-paid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: amount,
                    payment_method: method,
                    note: note
                })
            });

            const data = await response.json();

            console.log('Payment response:', data);

            // ✅ Vérifier data.success
            if (data.success) {
                console.log('Payment successful, reloading...');
                this.modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Payment failed:', data.error);
                alert('Erreur : ' + (data.error || 'Une erreur est survenue'));
                this.submitBtnTarget.disabled = false;
                this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Confirmer le paiement';
                this.isSubmitting = false;
            }
        } catch (error) {
            console.error('Payment error:', error);
            alert('Erreur lors de l\'enregistrement');
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.innerHTML = '<i class="fa fa-check"></i> Confirmer le paiement';
            this.isSubmitting = false;
        }
    }

    // ✅ AJOUT : Réinitialiser
    disconnect() {
        console.log('Payment modal controller disconnected');
        this.isSubmitting = false;
    }
}
