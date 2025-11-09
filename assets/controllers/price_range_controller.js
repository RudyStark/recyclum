import { Controller } from '@hotwired/stimulus';

/**
 * Gestion du slider de prix avec affichage dynamique
 * Usage: <div data-controller="price-range" data-price-range-min-value="0" data-price-range-max-value="5000">
 */
export default class extends Controller {
    static targets = ['minInput', 'maxInput', 'minDisplay', 'maxDisplay', 'track'];
    static values = {
        min: { type: Number, default: 0 },
        max: { type: Number, default: 5000 }
    };

    connect() {
        this.updateDisplay();
        this.updateTrack();
    }

    updateMin() {
        const min = parseInt(this.minInputTarget.value) || this.minValue;
        const max = parseInt(this.maxInputTarget.value) || this.maxValue;

        if (min > max) {
            this.minInputTarget.value = max;
        }

        this.updateDisplay();
        this.updateTrack();
    }

    updateMax() {
        const min = parseInt(this.minInputTarget.value) || this.minValue;
        const max = parseInt(this.maxInputTarget.value) || this.maxValue;

        if (max < min) {
            this.maxInputTarget.value = min;
        }

        this.updateDisplay();
        this.updateTrack();
    }

    updateDisplay() {
        const min = parseInt(this.minInputTarget.value) || this.minValue;
        const max = parseInt(this.maxInputTarget.value) || this.maxValue;

        if (this.hasMinDisplayTarget) {
            this.minDisplayTarget.textContent = `${min} €`;
        }
        if (this.hasMaxDisplayTarget) {
            this.maxDisplayTarget.textContent = `${max} €`;
        }
    }

    updateTrack() {
        if (!this.hasTrackTarget) return;

        const min = parseInt(this.minInputTarget.value) || this.minValue;
        const max = parseInt(this.maxInputTarget.value) || this.maxValue;

        const percentMin = ((min - this.minValue) / (this.maxValue - this.minValue)) * 100;
        const percentMax = ((max - this.minValue) / (this.maxValue - this.minValue)) * 100;

        this.trackTarget.style.left = `${percentMin}%`;
        this.trackTarget.style.right = `${100 - percentMax}%`;
    }
}
