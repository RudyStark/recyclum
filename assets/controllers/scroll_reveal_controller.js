import { Controller } from '@hotwired/stimulus';

/**
 * Animation au scroll (reveal progressif)
 * Usage: <div data-controller="scroll-reveal">
 */
export default class extends Controller {
    connect() {
        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        this.observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px',
            }
        );

        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer.disconnect();
    }
}
