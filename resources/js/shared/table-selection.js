/**
 * Generic bulk-selection manager for tables: tracks checked row IDs across
 * paginated pages, persists them to localStorage (so selection survives
 * a page/sort/search change), and clears itself when the user navigates away.
 */
export class TableSelection {
    constructor(storageKey) {
        this.storageKey = storageKey;
        this.ids = new Set(this.#load());

        window.addEventListener('pagehide', () => this.clear());
        window.addEventListener('beforeunload', () => this.clear());
    }

    #load() {
        try {
            const raw = localStorage.getItem(this.storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch {
            return [];
        }
    }

    #save() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify([...this.ids]));
        } catch {
            // Ignore storage failures (e.g. private browsing quota).
        }
    }

    clear() {
        this.ids.clear();
        try {
            localStorage.removeItem(this.storageKey);
        } catch {
            // Ignore storage failures.
        }
    }

    has(id) {
        return this.ids.has(id);
    }

    toggle(id, checked) {
        if (checked) {
            this.ids.add(id);
        } else {
            this.ids.delete(id);
        }

        this.#save();
    }

    selectVisible(ids, checked) {
        ids.forEach((id) => (checked ? this.ids.add(id) : this.ids.delete(id)));
        this.#save();
    }

    get size() {
        return this.ids.size;
    }

    get selectedIds() {
        return [...this.ids];
    }
}
