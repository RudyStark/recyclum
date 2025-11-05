// Aperçu live d'un <input type="file"> (VichImageType compatible)
// data-controller="image-preview" sur un wrapper autour de l'input
// data-action="change->image-preview#pick" sur l'<input>
// Targets (si non fournis par Twig), le contrôleur les crée tout seul.

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'previewWrapper', 'preview'];

    connect() {
        // 1) Récupère l'input fichier même si la target manque
        this._fileInput = this.hasInputTarget
            ? this.inputTarget
            : this.element.querySelector('input[type="file"]');

        if (!this._fileInput) {
            // Rien à faire : on log proprement et on sort
            console.warn('[image-preview] Aucun <input type="file"> trouvé dans', this.element);
            return;
        }

        // 2) Crée au besoin le bloc d’aperçu (si targets absentes)
        if (!this.hasPreviewWrapperTarget || !this.hasPreviewTarget) {
            const wrap = document.createElement('div');
            wrap.className = 'ea-live-preview mt-2';
            wrap.dataset.imagePreviewTarget = 'previewWrapper';
            wrap.style.display = 'none';

            const label = document.createElement('div');
            label.className = 'small text-muted mb-1';
            label.textContent = 'Aperçu sélection :';

            const img = document.createElement('img');
            img.className = 'ea-upload-thumb';
            img.dataset.imagePreviewTarget = 'preview';
            img.alt = 'Aperçu';

            wrap.appendChild(label);
            wrap.appendChild(img);

            // on place l’aperçu juste après l’input
            this._fileInput.insertAdjacentElement('afterend', wrap);
            // Stimulus récupère automatiquement les nouvelles targets
        }

        // 3) Écoute le change si l’attribut data-action n’est pas déjà posé
        // (sécurité double ceinture)
        this._fileInput.addEventListener('change', () => this.pick());
    }

    pick() {
        const input = this.hasInputTarget ? this.inputTarget : this._fileInput;
        if (!input || !input.files || !input.files[0]) {
            return this.reset();
        }

        const file = input.files[0];
        if (!file.type || !file.type.startsWith('image/')) {
            this.reset();
            return;
        }

        const url = URL.createObjectURL(file);
        this.previewTarget.src = url;
        this.previewWrapperTarget.style.display = '';
        this.previewTarget.onload = () => URL.revokeObjectURL(url);
    }

    reset() {
        if (this.hasPreviewTarget) {
            this.previewTarget.removeAttribute('src');
        }
        if (this.hasPreviewWrapperTarget) {
            this.previewWrapperTarget.style.display = 'none';
        }
    }
}
