import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select'];

    connect() {
        console.log('Energy select controller connected!');

        // Empêche la création multiple du custom select
        if (this.selectTarget.dataset.customized === 'true') {
            console.log('Select already customized, skipping...');
            return;
        }

        this.badgeMap = {
            'A': { color: 'success', label: 'A' },
            'B': { color: 'success', label: 'B' },
            'C': { color: 'info', label: 'C' },
            'D': { color: 'primary', label: 'D' },
            'E': { color: 'warning', label: 'E' },
            'F': { color: 'danger', label: 'F' },
            'G': { color: 'danger', label: 'G' },
            'NA': { color: 'secondary', label: 'Non classé' }
        };

        this.createCustomSelect();
        this.selectTarget.dataset.customized = 'true';
    }

    createCustomSelect() {
        // Vérifie si le custom select existe déjà
        const existingWrapper = this.selectTarget.parentNode.querySelector('.custom-energy-select');
        if (existingWrapper) {
            console.log('Custom select already exists, removing old one...');
            existingWrapper.remove();
        }

        this.selectTarget.style.display = 'none';

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-energy-select';

        this.displayElement = document.createElement('div');
        this.displayElement.className = 'custom-select-display';
        this.displayElement.addEventListener('click', () => this.toggleDropdown());

        this.dropdownElement = document.createElement('div');
        this.dropdownElement.className = 'custom-select-dropdown';

        Array.from(this.selectTarget.options).forEach(option => {
            if (option.value === '') return;

            const badge = this.badgeMap[option.value];
            if (!badge) return;

            const optionEl = document.createElement('div');
            optionEl.className = `custom-select-option badge-${badge.color}`;
            optionEl.dataset.value = option.value;
            optionEl.dataset.label = badge.label;
            optionEl.textContent = ''; // Le texte sera dans ::before
            optionEl.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectOption(option.value);
            });

            this.dropdownElement.appendChild(optionEl);
        });

        wrapper.appendChild(this.displayElement);
        wrapper.appendChild(this.dropdownElement);
        this.selectTarget.parentNode.insertBefore(wrapper, this.selectTarget.nextSibling);

        this.clickOutsideHandler = (e) => {
            if (!wrapper.contains(e.target)) {
                this.closeDropdown();
            }
        };
        document.addEventListener('click', this.clickOutsideHandler);

        this.updateDisplay();
    }

    toggleDropdown() {
        this.dropdownElement.classList.toggle('open');
    }

    closeDropdown() {
        if (this.dropdownElement) {
            this.dropdownElement.classList.remove('open');
        }
    }

    selectOption(value) {
        this.selectTarget.value = value;
        this.selectTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.updateDisplay();
        this.closeDropdown();
    }

    updateDisplay() {
        if (!this.displayElement) return;

        const selectedValue = this.selectTarget.value;
        const badge = this.badgeMap[selectedValue];

        if (badge) {
            this.displayElement.className = `custom-select-display badge-${badge.color}`;
            this.displayElement.innerHTML = `${badge.label}<i class="fa fa-chevron-down"></i>`;
        } else {
            this.displayElement.className = 'custom-select-display';
            this.displayElement.innerHTML = `Sélectionnez une étiquette<i class="fa fa-chevron-down"></i>`;
        }
    }

    disconnect() {
        if (this.clickOutsideHandler) {
            document.removeEventListener('click', this.clickOutsideHandler);
        }

        // Nettoie le custom select
        const wrapper = this.element.querySelector('.custom-energy-select');
        if (wrapper) {
            wrapper.remove();
        }

        // Réaffiche le select natif
        this.selectTarget.style.display = '';
        this.selectTarget.dataset.customized = 'false';
    }
}
