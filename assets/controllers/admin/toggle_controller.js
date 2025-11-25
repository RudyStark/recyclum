import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel'];

    toggleFilters() {
        const panel = this.panelTarget;

        if (panel.style.display === 'none') {
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    }
}
