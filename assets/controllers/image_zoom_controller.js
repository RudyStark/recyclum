import { Controller } from '@hotwired/stimulus';

/**
 * Zoom sur image au survol (détail produit)
 * Usage: <div data-controller="image-zoom">
 */
export default class extends Controller {
    static targets = ['image', 'lens'];

    connect() {
        this.element.addEventListener('mouseenter', this.showLens.bind(this));
        this.element.addEventListener('mouseleave', this.hideLens.bind(this));
        this.element.addEventListener('mousemove', this.moveLens.bind(this));
    }

    showLens() {
        if (this.hasLensTarget) {
            this.lensTarget.style.display = 'block';
        }
    }

    hideLens() {
        if (this.hasLensTarget) {
            this.lensTarget.style.display = 'none';
        }
    }

    moveLens(event) {
        if (!this.hasLensTarget || !this.hasImageTarget) return;

        const bounds = this.element.getBoundingClientRect();
        const x = event.clientX - bounds.left;
        const y = event.clientY - bounds.top;

        const lensSize = this.lensTarget.offsetWidth / 2;

        this.lensTarget.style.left = `${Math.min(Math.max(x - lensSize, 0), bounds.width - lensSize * 2)}px`;
        this.lensTarget.style.top = `${Math.min(Math.max(y - lensSize, 0), bounds.height - lensSize * 2)}px`;

        // Zoom sur l'image
        const zoomFactor = 2;
        this.imageTarget.style.transformOrigin = `${(x / bounds.width) * 100}% ${(y / bounds.height) * 100}%`;
        this.imageTarget.style.transform = `scale(${zoomFactor})`;
    }
}
