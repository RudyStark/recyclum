import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropzone', 'fileInput', 'progressContainer', 'progressBar', 'progressText'];
    static values = {
        productId: Number,
        uploadUrl: String
    };

    connect() {
        this.setupDragAndDrop();
    }

    setupDragAndDrop() {
        const dropzone = this.dropzoneTarget;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, this.preventDefaults, false);
            document.body.addEventListener(eventName, this.preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => this.highlight(), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => this.unhighlight(), false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', (e) => this.handleDrop(e), false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight() {
        this.dropzoneTarget.classList.add('drag-over');
    }

    unhighlight() {
        this.dropzoneTarget.classList.remove('drag-over');
    }

    handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        this.uploadFilesFromInput(files);
    }

    triggerFileInput() {
        this.fileInputTarget.click();
    }

    handleFiles(event) {
        const files = event.target.files;
        this.uploadFilesFromInput(files);
    }

    uploadFilesFromInput(files) {
        const filesArray = [...files];

        if (filesArray.length === 0) return;

        this.showProgress();
        this.uploadFiles(filesArray);
    }

    showProgress() {
        this.progressContainerTarget.style.display = 'block';
        this.progressBarTarget.style.width = '0%';
        this.progressTextTarget.textContent = '0%';
    }

    hideProgress() {
        setTimeout(() => {
            this.progressContainerTarget.style.display = 'none';
        }, 1000);
    }

    async uploadFiles(files) {
        const total = files.length;
        let completed = 0;

        for (const file of files) {
            await this.uploadFile(file);
            completed++;
            const progress = Math.round((completed / total) * 100);
            this.updateProgress(progress);
        }

        this.hideProgress();

        // Recharge la page pour afficher les nouvelles images
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('productId', this.productIdValue);

        try {
            const response = await fetch(this.uploadUrlValue, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                alert('Erreur: ' + (error.error || 'Upload échoué'));
                throw new Error('Upload failed');
            }

            return await response.json();
        } catch (error) {
            console.error('Upload error:', error);
            throw error;
        }
    }

    updateProgress(percent) {
        this.progressBarTarget.style.width = percent + '%';
        this.progressTextTarget.textContent = percent + '%';
    }
}
