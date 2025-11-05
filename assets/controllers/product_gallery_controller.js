// assets/controllers/product_gallery_controller.js
import { Controller } from '@hotwired/stimulus'
import GLightbox from 'glightbox'
import 'glightbox/dist/css/glightbox.css' // <- CSS via NPM

export default class extends Controller {
    connect() {
        const galleryId = this.element.dataset.galleryId
        this.lightbox = GLightbox({
            selector: `[data-gallery="${galleryId}"]`,
            touchNavigation: true,
            loop: true,
            zoomable: true,
            openEffect: 'zoom',
            closeEffect: 'zoom',
        })
    }

    disconnect() {
        if (this.lightbox?.destroy) this.lightbox.destroy()
    }
}
