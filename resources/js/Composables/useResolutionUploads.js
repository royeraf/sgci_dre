import { reactive } from 'vue';

export const resolutionUploads = reactive({ jobs: [] });
let nextId = 0;
export function openResolutionUploads(options = {}) {
    for (const job of resolutionUploads.jobs) job.open = false;
    const id = ++nextId;
    resolutionUploads.jobs.push({ id, label: `Carga ${id}`, open: true, busy: false, maxMb: options.maxMb || 100, currentYear: options.currentYear || new Date().getFullYear() });
}

export function revealResolutionUpload(id) {
    for (const job of resolutionUploads.jobs) job.open = job.id === id;
}

// All folders share five network slots, including files retried after a failure.
let occupied = 0;
const waiting = [];
const pausedError = () => Object.assign(new Error('Carga pausada'), { uploadPaused: true });
function drain() {
    while (occupied < 5 && waiting.length) {
        const request = waiting.shift();
        request.signal?.removeEventListener('abort', request.abort);
        if (request.signal?.aborted) { request.reject(pausedError()); continue; }
        occupied++;
        let released = false;
        request.resolve(() => { if (released) return; released = true; occupied--; drain(); });
    }
}
export function acquireResolutionUploadSlot(signal) {
    if (signal?.aborted) return Promise.reject(pausedError());
    return new Promise((resolve, reject) => {
        const request = { resolve, reject, signal, abort: null };
        request.abort = () => {
            const index = waiting.indexOf(request);
            if (index >= 0) waiting.splice(index, 1);
            signal?.removeEventListener('abort', request.abort);
            reject(pausedError());
        };
        waiting.push(request);
        signal?.addEventListener('abort', request.abort, { once: true });
        drain();
    });
}
