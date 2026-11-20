import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'dropzone', 'fileInput', 'fileName', 'submitBtn', 'errorBox', 'errorMessage', 'resultBox', 'resultCount', 'downloadForm', 'downloadToken'];

    connect() {
        this.syncFile();
    }

    dragOver(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.add('is-dragover');
    }

    dragLeave(event) {
        if (!event.relatedTarget || !this.dropzoneTarget.contains(event.relatedTarget)) {
            this.dropzoneTarget.classList.remove('is-dragover');
        }
    }

    drop(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('is-dragover');
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;

        const dt = new DataTransfer();
        dt.items.add(file);
        this.fileInputTarget.files = dt.files;
        this.syncFile();
    }

    clearFile(event) {
        event.preventDefault();
        event.stopPropagation();
        this.fileInputTarget.value = '';
        this.syncFile();
    }

    syncFile() {
        const file = this.fileInputTarget.files?.[0];
        this.dropzoneTarget.classList.toggle('has-file', Boolean(file));
        this.fileNameTarget.textContent = file ? file.name : '';
        this.submitBtnTarget.disabled = !file;
        if (file) {
            this.errorBoxTarget.classList.add('d-none');
        }
    }

    async submit(event) {
        event.preventDefault();
        this.errorBoxTarget.classList.add('d-none');
        this.resultBoxTarget.classList.add('d-none');
        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.classList.add('is-loading');

        try {
            const response = await fetch(this.formTarget.action, {
                method: 'POST',
                body: new FormData(this.formTarget),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await response.json().catch(() => null);

            if (!response.ok || !data?.success) {
                this.errorMessageTarget.textContent = data?.message || "Une erreur est survenue pendant l'import.";
                this.errorBoxTarget.classList.remove('d-none');
                return;
            }

            this.resultCountTarget.textContent = String(data.count);
            this.downloadTokenTarget.value = data.token;
            this.resultBoxTarget.classList.remove('d-none');
            this.fileInputTarget.value = '';
            this.syncFile();
            this.downloadFormTarget.submit();
        } catch {
            this.errorMessageTarget.textContent = 'Impossible de joindre le serveur pour importer le fichier.';
            this.errorBoxTarget.classList.remove('d-none');
        } finally {
            this.submitBtnTarget.classList.remove('is-loading');
            this.submitBtnTarget.disabled = !this.fileInputTarget.files?.length;
        }
    }

    resetView() {
        this.errorBoxTarget.classList.add('d-none');
        this.resultBoxTarget.classList.add('d-none');
        this.fileInputTarget.value = '';
        this.syncFile();
    }
}
