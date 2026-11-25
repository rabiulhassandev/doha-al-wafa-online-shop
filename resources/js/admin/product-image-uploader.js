/**
 * Uploads original product photos one at a time without resizing or changing the file format.
 */
export default (uploadUrl) => ({
    busy: false,
    done: 0,
    total: 0,
    error: null,

    async upload(fileList) {
        const files = [...(fileList ?? [])];

        if (files.length === 0 || this.busy) {
            return;
        }

        this.busy = true;
        this.done = 0;
        this.total = files.length;
        this.error = null;

        for (const file of files) {
            try {
                const body = new FormData();
                body.append('images[]', file, file.name);

                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body,
                });

                if (!response.ok) {
                    this.error = await errorMessage(response, file.name);
                    break;
                }

                this.done++;
            } catch {
                this.error = `“${file.name}” could not be uploaded. Check your connection and try again.`;
                break;
            }
        }

        if (this.error === null) {
            window.location.reload();

            return;
        }

        this.busy = false;

        if (this.done > 0) {
            this.error += ` ${this.done} of ${this.total} photos were uploaded. Reload the page to see them.`;
        }
    },
});

async function errorMessage(response, fileName) {
    if (response.status === 413) {
        return `“${fileName}” is too large to upload.`;
    }

    try {
        const data = await response.json();
        const firstError = Object.values(data.errors ?? {})[0]?.[0];

        return firstError ?? data.message ?? `“${fileName}” could not be uploaded.`;
    } catch {
        return `“${fileName}” could not be uploaded.`;
    }
}
