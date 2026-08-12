export function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}
