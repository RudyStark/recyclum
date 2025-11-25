import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String
    };

    async delete(event) {
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
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 1.25rem;
            ">
                <i class="fa fa-trash"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="
                    font-weight: 800;
                    font-size: 0.95rem;
                    color: #1a2332;
                    margin: 0 0 0.25rem 0;
                ">Supprimer l'image</h4>
                <p style="
                    font-size: 0.875rem;
                    color: #6c7783;
                    margin: 0 0 0.75rem 0;
                ">Cette action est irréversible. Voulez-vous continuer ?</p>
                <div style="display: flex; gap: 0.5rem;">
                    <button data-toast-action="confirm" style="
                        padding: 0.5rem 1rem;
                        border-radius: 6px;
                        border: none;
                        font-weight: 700;
                        font-size: 0.875rem;
                        cursor: pointer;
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        color: white;
                        transition: all 0.2s ease;
                    ">Supprimer</button>
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
            confirmBtn.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.3)';
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
            this.performDelete();
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

    async performDelete() {
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Delete failed');
            }

            const galleryItem = this.element.closest('.gallery-item');
            galleryItem.style.transition = 'all 0.3s ease';
            galleryItem.style.opacity = '0';
            galleryItem.style.transform = 'scale(0.8)';

            setTimeout(() => {
                galleryItem.remove();

                const remainingImages = document.querySelectorAll('.gallery-item');
                if (remainingImages.length === 0) {
                    window.location.reload();
                }
            }, 300);

        } catch (error) {
            console.error('Delete error:', error);
            this.showErrorToast();
        }
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
                ">Impossible de supprimer l'image</p>
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
