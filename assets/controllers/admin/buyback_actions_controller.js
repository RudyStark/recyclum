import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['statusSelect'];
    static values = {
        requestId: Number,
        currentStatus: String
    };

    connect() {
        console.log('Buyback actions controller connected', this.requestIdValue);
    }

    changeStatus(event) {
        const action = event.target.value;

        if (!action) return;

        // Reset le select
        event.target.value = '';

        // Route vers l'action appropriée
        switch(action) {
            case 'validate':
                this.showValidateModal();
                break;
            case 'refuse':
                this.showRefuseModal();
                break;
            case 'awaiting_collection':
                this.markAsAwaitingCollection();
                break;
            case 'collected':
                this.markAsCollected();
                break;
            case 'paid':
                this.showPaymentModal();
                break;
            case 'cancel':
                this.cancelRequest();
                break;
        }
    }

    showValidateModal() {
        const modal = new bootstrap.Modal(document.getElementById('validateModal'));
        modal.show();
    }

    showRefuseModal() {
        const modal = new bootstrap.Modal(document.getElementById('refuseModal'));
        modal.show();
    }

    async markAsAwaitingCollection() {
        if (!confirm('Marquer cette demande en attente de dépôt magasin ?')) {
            return;
        }

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    status: 'awaiting_collection'
                })
            });

            if (response.ok) {
                window.location.reload();
            } else {
                const data = await response.json();
                alert('Erreur : ' + (data.error || 'Une erreur est survenue'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de la mise à jour du statut');
        }
    }

    async markAsCollected() {
        // ✅ NOUVEAU : Ouvrir la modale au lieu du confirm
        const modal = new bootstrap.Modal(document.getElementById('collectedModal'));
        modal.show();
    }

    showPaymentModal() {
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    async cancelRequest() {
        const reason = prompt('Motif d\'annulation (optionnel) :');

        if (reason === null) return; // Annulation

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    status: 'cancelled',
                    notes: reason ? `Annulation : ${reason}` : undefined
                })
            });

            if (response.ok) {
                window.location.reload();
            } else {
                const data = await response.json();
                alert('Erreur : ' + (data.error || 'Une erreur est survenue'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de l\'annulation');
        }
    }
}
