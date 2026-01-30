import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'zone'];
    static values = {
        maxFiles: { type: Number, default: 5 },
        maxSize: { type: Number, default: 10485760 } // 10MB
    };

    connect() {
        this.files = [];
        this.setupDragAndDrop();
    }

    setupDragAndDrop() {
        const zone = this.zoneTarget;

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');

            if (e.dataTransfer.files.length > 0) {
                this.handleFiles(e.dataTransfer.files);
            }
        });
    }

    onChange(event) {
        this.handleFiles(event.target.files);
    }

    handleFiles(fileList) {
        const newFiles = Array.from(fileList);

        // Validate and add files
        newFiles.forEach(file => {
            if (this.files.length >= this.maxFilesValue) {
                return;
            }

            if (file.size > this.maxSizeValue) {
                alert(`Le fichier "${file.name}" dépasse la taille maximale de 10 Mo.`);
                return;
            }

            if (!this.isAllowedType(file)) {
                alert(`Le type de fichier "${file.name}" n'est pas autorisé.`);
                return;
            }

            this.files.push(file);
        });

        this.updatePreview();
        this.updateInput();
    }

    isAllowedType(file) {
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];
        return allowedTypes.includes(file.type);
    }

    updatePreview() {
        this.previewTarget.innerHTML = '';

        this.files.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'upload-preview-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                item.appendChild(img);
            } else {
                const iconDiv = document.createElement('div');
                iconDiv.className = 'file-icon';

                let iconClass = 'bi-file-earmark';
                if (file.type === 'application/pdf') {
                    iconClass = 'bi-file-pdf';
                } else if (file.type.includes('word') || file.type.includes('document')) {
                    iconClass = 'bi-file-word';
                } else if (file.type.includes('excel') || file.type.includes('spreadsheet')) {
                    iconClass = 'bi-file-excel';
                }

                iconDiv.innerHTML = `
                    <i class="bi ${iconClass}"></i>
                    <span>${this.getExtension(file.name)}</span>
                `;
                item.appendChild(iconDiv);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeFile(index);
            });
            item.appendChild(removeBtn);

            this.previewTarget.appendChild(item);
        });
    }

    updateInput() {
        // Create a new DataTransfer to update the file input
        const dt = new DataTransfer();
        this.files.forEach(file => dt.items.add(file));
        this.inputTarget.files = dt.files;
    }

    removeFile(index) {
        this.files.splice(index, 1);
        this.updatePreview();
        this.updateInput();
    }

    getExtension(filename) {
        return filename.split('.').pop().toUpperCase();
    }
}
