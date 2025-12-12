import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['textarea', 'saveStatus'];
    static values = {
        requestId: Number
    };

    connect() {
        console.log('Buyback notes controller connected');
    }

    async save() {
        const notes = this.textareaTarget.value;

        try {
            const response = await fetch(`/admin/buyback-requests/${this.requestIdValue}/notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notes })
            });

            if (response.ok) {
                this.showSaveStatus();
            }
        } catch (error) {
            console.error('Error saving notes:', error);
        }
    }

    showSaveStatus() {
        this.saveStatusTarget.style.display = 'block';

        setTimeout(() => {
            this.saveStatusTarget.style.display = 'none';
        }, 2000);
    }
}
