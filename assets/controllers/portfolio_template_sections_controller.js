import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        prototype: String,
        index: Number,
    };

    connect() {
        let maxIndex = this.indexValue - 1;
        this.containerTarget.querySelectorAll('[name*="[sections]["]').forEach((field) => {
            const match = field.name.match(/\[sections\]\[(\d+)\]/);
            if (match) {
                const idx = parseInt(match[1], 10);
                if (idx > maxIndex) {
                    maxIndex = idx;
                }
            }
        });
        this.indexValue = maxIndex + 1;
        this.renumber();
    }

    add() {
        const html = this.prototypeValue.replace(/__name__/g, String(this.indexValue));
        this.indexValue += 1;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const newItem = wrapper.firstElementChild;
        if (newItem) {
            this.containerTarget.appendChild(newItem);
            this.renumber();
        }
    }

    remove(event) {
        const item = event.currentTarget.closest('.section-item');
        if (!item) return;

        item.remove();
        this.renumber();
    }

    moveUp(event) {
        const item = event.currentTarget.closest('.section-item');
        if (!item) return;

        const prev = item.previousElementSibling;
        if (prev) {
            this.containerTarget.insertBefore(item, prev);
            this.renumber();
        }
    }

    moveDown(event) {
        const item = event.currentTarget.closest('.section-item');
        if (!item) return;

        const next = item.nextElementSibling;
        if (next) {
            this.containerTarget.insertBefore(next, item);
            this.renumber();
        }
    }

    renumber() {
        const items = this.containerTarget.querySelectorAll('.section-item');
        items.forEach((item, idx) => {
            const orderInput = item.querySelector('.section-ordre-input');
            if (orderInput) {
                orderInput.value = String(idx + 1);
            }
            const titleLabel = item.querySelector('.section-title-label');
            if (titleLabel) {
                titleLabel.textContent = 'Section ' + (idx + 1);
            }
        });
    }
}
