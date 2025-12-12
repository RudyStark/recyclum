import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['reasonInput', 'submitBtn'];
    static values = {
        requestId: Number
    };

    // ✅ Flag pour empêcher double submit
    isSubmitting = false;

    connect() {
        console.log('Refuse modal controller connected for request', this.requestIdValue);
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

        const reason = this.reasonInputTarget.value.trim();

        if (!reason) {
            alert('Veuillez indiquer un motif de refus');
            this.reasonInputTarget.focus();
            return;
        }

        console.log('Starting refusal for request', this.requestIdValue);
        this.isSubmitting = true;

        // Désactiver le bouton pendant la requête
        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refus...';

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/refuse`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    refusal_reason: reason
                })
            });

            const data = await response.json();

            console.log('Refusal response:', data);

            // ✅ Vérifier data.success
            if (data.success) {
                console.log('Refusal successful, reloading...');
                this.modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Refusal failed:', data.error);
                alert('Erreur : ' + (data.error || 'Une erreur est survenue'));
                this.submitBtnTarget.disabled = false;
                this.submitBtnTarget.innerHTML = '<i class="fa fa-times-circle"></i> Confirmer le refus';
                this.isSubmitting = false;
            }
        } catch (error) {
            console.error('Refusal error:', error);
            alert('Erreur lors du refus');
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.innerHTML = '<i class="fa fa-times-circle"></i> Confirmer le refus';
            this.isSubmitting = false;
        }
    }

    // ✅ AJOUT : Réinitialiser
    disconnect() {
        console.log('Refuse modal controller disconnected');
        this.isSubmitting = false;
    }
}
