import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String
    };

    async setMain(event) {
        event.preventDefault();
        this.showConfirmToast();
    }

    showConfirmToast() {
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: white;
            border: 2px solid #e6edf2;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 380px;
            max-width: 450px;
            animation: slideIn 0.3s ease;
            margin-bottom: 1rem;
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
                <i class="fa fa-star"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="
                    font-weight: 800;
                    font-size: 0.95rem;
                    color: #1a2332;
                    margin: 0 0 0.25rem 0;
                ">Changer l'image principale</h4>
                <p style="
                    font-size: 0.875rem;
                    color: #6c7783;
                    margin: 0 0 0.75rem 0;
                ">Voulez-vous définir cette image comme image principale ?</p>
                <div style="display: flex; gap: 0.5rem;">
                    <button data-toast-action="confirm" style="
                        padding: 0.5rem 1rem;
                        border-radius: 6px;
                        border: none;
                        font-weight: 700;
                        font-size: 0.875rem;
                        cursor: pointer;
                        background: linear-gradient(135deg, #16C669 0%, #12a356 100%);
                        color: white;
                        transition: all 0.2s ease;
                    ">Confirmer</button>
                    <button data-toast-action="cancel" style="
                        padding: 0.5rem 1rem;
                        border-radius: 6px;
                        border: none;
                        font-weight: 700;
                        font-size: 0.875rem;
                        cursor: pointer;
                        background: transparent;
                        color: #6c7783;
                        transition: all 0.2s ease;
                    ">Annuler</button>
                </div>
            </div>
        `;

        let container = document.querySelector('.recyclum-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'recyclum-toast-container';
            container.style.cssText = `
                position: fixed !important;
                bottom: 2rem !important;
                right: 2rem !important;
                z-index: 999999 !important;
                display: flex !important;
                flex-direction: column !important;
                pointer-events: auto !important;
            `;
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        const confirmBtn = toast.querySelector('[data-toast-action="confirm"]');
        const cancelBtn = toast.querySelector('[data-toast-action="cancel"]');

        confirmBtn.addEventListener('mouseenter', () => {
            confirmBtn.style.transform = 'translateY(-1px)';
            confirmBtn.style.boxShadow = '0 4px 12px rgba(22, 198, 105, 0.3)';
        });
        confirmBtn.addEventListener('mouseleave', () => {
            confirmBtn.style.transform = 'translateY(0)';
            confirmBtn.style.boxShadow = 'none';
        });

        cancelBtn.addEventListener('mouseenter', () => {
            cancelBtn.style.background = 'rgba(0, 0, 0, 0.05)';
            cancelBtn.style.color = '#1a2332';
        });
        cancelBtn.addEventListener('mouseleave', () => {
            cancelBtn.style.background = 'transparent';
            cancelBtn.style.color = '#6c7783';
        });

        confirmBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.performSetMain();
            this.removeToast(toast);
        });

        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.removeToast(toast);
        });

        setTimeout(() => {
            if (toast.parentElement) {
                this.removeToast(toast);
            }
        }, 10000);
    }

    removeToast(toast) {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }

    async performSetMain() {
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Set main failed');
            }

            this.showSuccessToast();

            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } catch (error) {
            console.error('Set main error:', error);
            this.showErrorToast();
        }
    }

    showSuccessToast() {
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: white;
            border: 2px solid #22c55e;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 350px;
            animation: slideIn 0.3s ease;
        `;

        toast.innerHTML = `
            <div style="
                width: 44px;
                height: 44px;
                border-radius: 10px;
                background: rgba(34, 197, 94, 0.1);
                color: #22c55e;
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
                ">Succès !</h4>
                <p style="
                    font-size: 0.875rem;
                    color: #6c7783;
                    margin: 0;
                ">L'image principale a été mise à jour</p>
            </div>
        `;

        let container = document.querySelector('.recyclum-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'recyclum-toast-container';
            container.style.cssText = `
                position: fixed !important;
                bottom: 2rem !important;
                right: 2rem !important;
                z-index: 999999 !important;
                display: flex !important;
                flex-direction: column !important;
                pointer-events: auto !important;
                gap: 1rem;
            `;
            document.body.appendChild(container);
        }
        container.appendChild(toast);

        setTimeout(() => {
            this.removeToast(toast);
        }, 3000);
    }

    showErrorToast() {
        const toast = document.createElement('div');
        toast.style.cssText = `
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
                ">Impossible de définir l'image principale</p>
            </div>
        `;

        let container = document.querySelector('.recyclum-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'recyclum-toast-container';
            container.style.cssText = `
                position: fixed !important;
                bottom: 2rem !important;
                right: 2rem !important;
                z-index: 999999 !important;
                display: flex !important;
                flex-direction: column !important;
                pointer-events: auto !important;
                gap: 1rem;
            `;
            document.body.appendChild(container);
        }
        container.appendChild(toast);

        setTimeout(() => {
            this.removeToast(toast);
        }, 5000);
    }
}
