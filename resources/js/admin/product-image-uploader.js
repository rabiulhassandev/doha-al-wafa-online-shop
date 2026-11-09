/**
 * Uploads product photos one at a time, shrinking large phone photos in the browser first.
 * One photo per request keeps each upload small and fast on mobile data, and avoids server size limits.
 */
const MAX_SIDE = 2000;
const SHRINK_ABOVE_BYTES = 2_500_000;

export default (uploadUrl) => ({
    focus: 'center',
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
                body.append('images[]', await shrink(file), file.name.replace(/\.\w+$/, '') + '.jpg');
                body.append('focus', this.focus);

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

/**
 * Resize a photo so its longest side is at most MAX_SIDE pixels, honoring the phone's rotation.
 * Returns the original file when it is already small or the browser cannot resize.
 */
async function shrink(file) {
    if (!('createImageBitmap' in window)) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));

        if (scale === 1 && file.size <= SHRINK_ABOVE_BYTES) {
            bitmap.close();

            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        return await new Promise((resolve) => canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', 0.9));
    } catch {
        return file;
    }
}

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
