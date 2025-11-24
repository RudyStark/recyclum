import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static targets = ['item'];
    static values = {
        reorderUrl: String
    };

    connect() {
        this.sortable = Sortable.create(this.element, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: (evt) => this.handleReorder(evt)
        });
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }

    async handleReorder(evt) {
        const positions = {};

        this.itemTargets.forEach((item, index) => {
            const imageId = item.dataset.imageId;
            positions[imageId] = index + 1;
        });

        try {
            const response = await fetch(this.reorderUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ positions })
            });

            if (!response.ok) {
                throw new Error('Reorder failed');
            }

            // Update position numbers in UI
            this.itemTargets.forEach((item, index) => {
                const positionSpan = item.querySelector('.image-position');
                if (positionSpan) {
                    positionSpan.textContent = `#${index + 1}`;
                }
            });

        } catch (error) {
            console.error('Reorder error:', error);
            alert('Erreur lors de la réorganisation');
            window.location.reload();
        }
    }
}
