import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['overlay', 'image'];

    openWithSrc(event) {
        event.preventDefault();
        const imageSrc = event.currentTarget.dataset.imageSrc;
        this.imageTarget.src = imageSrc;
        this.overlayTarget.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    close(event) {
        event.preventDefault();
        this.overlayTarget.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
