import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        requestId: Number
    };

    showAcceptModal() {
        this.showModal('accept', 'Accepter la demande', 'Envoyer une confirmation au Client');
    }

    showRejectModal() {
        this.showModal('reject', 'Refuser la demande', 'Envoyer un refus au Client');
    }

    showModal(type, title, subtitle) {
        const modal = document.createElement('div');
        modal.className = 'repair-modal-overlay';
        modal.innerHTML = `
            <div class="repair-modal" style="
                background: white;
                border-radius: 16px;
                max-width: 600px;
                width: 90%;
                padding: 0;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: modalSlideIn 0.3s ease;
            ">
                <div class="repair-modal-header" style="
                    background: linear-gradient(135deg, ${type === 'accept' ? '#16C669 0%, #12a356' : '#ef4444 0%, #dc2626'} 100%);
                    padding: 2rem;
                    border-radius: 16px 16px 0 0;
                    color: white;
                ">
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 900;">${title}</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.95rem;">${subtitle}</p>
                </div>
                <div class="repair-modal-body" style="padding: 2rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="
                            display: block;
                            font-weight: 700;
                            font-size: 0.875rem;
                            color: #1a2332;
                            margin-bottom: 0.5rem;
                        ">Message personnalisé</label>
                        <textarea class="modal-message" rows="6" placeholder="Ajoutez un message pour le client..." style="
                            width: 100%;
                            padding: 1rem;
                            border: 2px solid #e6edf2;
                            border-radius: 8px;
                            font-size: 0.9rem;
                            font-family: inherit;
                            resize: vertical;
                            transition: all 0.2s ease;
                        "></textarea>
                    </div>

                    ${type === 'accept' ? `
                        <div style="
                            background: linear-gradient(135deg, rgba(22, 198, 105, 0.08) 0%, rgba(22, 198, 105, 0.02) 100%);
                            border: 2px solid rgba(22, 198, 105, 0.2);
                            border-radius: 10px;
                            padding: 1rem;
                            margin-bottom: 1.5rem;
                        ">
                            <div style="display: flex; gap: 0.75rem; align-items: start;">
                                <i class="fa fa-info-circle" style="color: #16C669; font-size: 1.25rem; margin-top: 0.125rem;"></i>
                                <div style="flex: 1;">
                                    <strong style="display: block; margin-bottom: 0.25rem; color: #1a2332;">Email de confirmation</strong>
                                    <p style="margin: 0; font-size: 0.875rem; color: #6c7783; line-height: 1.5;">
                                        Un email sera envoyé au client pour confirmer la prise en charge de sa demande.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div style="
                            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.02) 100%);
                            border: 2px solid rgba(239, 68, 68, 0.2);
                            border-radius: 10px;
                            padding: 1rem;
                            margin-bottom: 1.5rem;
                        ">
                            <div style="display: flex; gap: 0.75rem; align-items: start;">
                                <i class="fa fa-exclamation-triangle" style="color: #ef4444; font-size: 1.25rem; margin-top: 0.125rem;"></i>
                                <div style="flex: 1;">
                                    <strong style="display: block; margin-bottom: 0.25rem; color: #1a2332;">Email de refus</strong>
                                    <p style="margin: 0; font-size: 0.875rem; color: #6c7783; line-height: 1.5;">
                                        Un email sera envoyé au client pour l'informer du refus de sa demande.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `}

                    <div style="display: flex; gap: 1rem;">
                        <button class="modal-btn-cancel" style="
                            flex: 1;
                            padding: 0.875rem;
                            border: 2px solid #e6edf2;
                            background: white;
                            border-radius: 10px;
                            font-weight: 700;
                            font-size: 0.95rem;
                            color: #6c7783;
                            cursor: pointer;
                            transition: all 0.2s ease;
                        ">Annuler</button>
                        <button class="modal-btn-confirm" style="
                            flex: 1;
                            padding: 0.875rem;
                            border: none;
                            background: linear-gradient(135deg, ${type === 'accept' ? '#16C669 0%, #12a356' : '#ef4444 0%, #dc2626'} 100%);
                            border-radius: 10px;
                            font-weight: 700;
                            font-size: 0.95rem;
                            color: white;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            box-shadow: 0 4px 12px ${type === 'accept' ? 'rgba(22, 198, 105, 0.3)' : 'rgba(239, 68, 68, 0.3)'};
                        ">${type === 'accept' ? 'Envoyer la confirmation' : 'Envoyer le refus'}</button>
                    </div>
                </div>
            </div>
        `;

        // Styles pour l'overlay
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            animation: fadeIn 0.3s ease;
        `;

        document.body.appendChild(modal);

        // Focus sur le textarea
        const textarea = modal.querySelector('.modal-message');
        textarea.style.outline = 'none';
        textarea.addEventListener('focus', () => {
            textarea.style.borderColor = type === 'accept' ? '#16C669' : '#ef4444';
            textarea.style.boxShadow = type === 'accept'
                ? '0 0 0 3px rgba(22, 198, 105, 0.1)'
                : '0 0 0 3px rgba(239, 68, 68, 0.1)';
        });
        textarea.addEventListener('blur', () => {
            textarea.style.borderColor = '#e6edf2';
            textarea.style.boxShadow = 'none';
        });

        // Hover effects
        const cancelBtn = modal.querySelector('.modal-btn-cancel');
        const confirmBtn = modal.querySelector('.modal-btn-confirm');

        cancelBtn.addEventListener('mouseenter', () => {
            cancelBtn.style.borderColor = '#1a2332';
            cancelBtn.style.color = '#1a2332';
        });
        cancelBtn.addEventListener('mouseleave', () => {
            cancelBtn.style.borderColor = '#e6edf2';
            cancelBtn.style.color = '#6c7783';
        });

        confirmBtn.addEventListener('mouseenter', () => {
            confirmBtn.style.transform = 'translateY(-2px)';
            confirmBtn.style.boxShadow = type === 'accept'
                ? '0 6px 20px rgba(22, 198, 105, 0.4)'
                : '0 6px 20px rgba(239, 68, 68, 0.4)';
        });
        confirmBtn.addEventListener('mouseleave', () => {
            confirmBtn.style.transform = 'translateY(0)';
            confirmBtn.style.boxShadow = type === 'accept'
                ? '0 4px 12px rgba(22, 198, 105, 0.3)'
                : '0 4px 12px rgba(239, 68, 68, 0.3)';
        });

        // Event listeners
        cancelBtn.addEventListener('click', () => this.closeModal(modal));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.closeModal(modal);
        });

        confirmBtn.addEventListener('click', () => {
            const message = textarea.value;
            this.sendResponse(type, message, modal);
        });

        // Animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes modalSlideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    async sendResponse(type, message, modal) {
        const url = `/admin/repair-request/send-response`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: this.requestIdValue,
                    type: type,
                    message: message
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi');
            }

            const data = await response.json();

            this.closeModal(modal);
            this.showSuccessToast(type === 'accept' ? 'Demande acceptée !' : 'Demande refusée', data.message);

            // Recharge après 2 secondes
            setTimeout(() => {
                window.location.reload();
            }, 2000);

        } catch (error) {
            console.error('Error:', error);
            this.showErrorToast('Une erreur est survenue');
        }
    }

    closeModal(modal) {
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.remove();
        }, 300);
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
