import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        requestId: Number
    };

    connect() {
        this.isUpdating = false; // ← Protection contre les appels multiples
    }

    async updateStatus(event) {
        // ✅ Protection : si déjà en cours, ignore
        if (this.isUpdating) {
            console.log('Update already in progress, ignoring...');
            return;
        }

        const newStatus = event.target.value;
        const url = `/admin/repair-request/update-status`;

        this.isUpdating = true; // ← Marque comme en cours

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: this.requestIdValue,
                    status: newStatus
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour');
            }

            const data = await response.json();
            this.showSuccessToast('Statut mis à jour !', `Le statut a été changé en "${data.statusLabel}"`);

            // Recharge après 1.5 secondes
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        } catch (error) {
            console.error('Error:', error);
            this.showErrorToast('Impossible de mettre à jour le statut');
            this.isUpdating = false; // ← Réinitialise en cas d'erreur
        }
    }

    showSuccessToast(title, message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            border: 2px solid #16C669;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 40px rgba(22, 198, 105, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 350px;
            animation: slideIn 0.3s ease;
            z-index: 999999;
        `;

        toast.innerHTML = `
            <div style="
                width: 44px;
                height: 44px;
                border-radius: 10px;
                background: rgba(22, 198, 105, 0.1);
                color: #16C669;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 1.25rem;
            ">
                <i class="fa fa-check"></i>
            </div>
            <div>
                <h4 style="
                    font-weight: 800;
                    font-size: 0.95rem;
                    color: #1a2332;
                    margin: 0 0 0.25rem 0;
                ">${title}</h4>
                <p style="
                    font-size: 0.875rem;
                    color: #6c7783;
                    margin: 0;
                ">${message}</p>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    showErrorToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 40px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 350px;
            animation: slideIn 0.3s ease;
            z-index: 999999;
        `;

        toast.innerHTML = `
            <div style="
                width: 44px;
                height: 44px;
                border-radius: 10px;
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 1.25rem;
            ">
                <i class="fa fa-times"></i>
            </div>
            <div>
                <h4 style="
                    font-weight: 800;
                    font-size: 0.95rem;
                    color: #1a2332;
                    margin: 0 0 0.25rem 0;
                ">Erreur</h4>
                <p style="
                    font-size: 0.875rem;
                    color: #6c7783;
                    margin: 0;
                ">${message}</p>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}
