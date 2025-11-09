import { Controller } from '@hotwired/stimulus';

/**
 * Gestion du select de tri (auto-submit le form)
 * Usage: <div data-controller="sort-select">
 *          <select data-action="change->sort-select#submit">
 */
export default class extends Controller {
    submit(event) {
        const form = document.getElementById('filters-form');
        if (form) {
            form.requestSubmit();
        }
    }
}
