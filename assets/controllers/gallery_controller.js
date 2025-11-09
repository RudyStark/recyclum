import { Controller } from '@hotwired/stimulus';
import GLightbox from 'glightbox';

/**
 * Galerie photos avec GLightbox
 * Usage: <div data-controller="gallery">
 */
export default class extends Controller {
    static values = {
        selector: { type: String, default: '.glightbox' }
    };

    connect() {
        this.lightbox = GLightbox({
            selector: this.selectorValue,
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            zoomable: true,
            draggable: true,
            closeButton: true,
            touchFollowAxis: true,
        });
    }

    disconnect() {
        if (this.lightbox) {
            this.lightbox.destroy();
        }
    }
}
