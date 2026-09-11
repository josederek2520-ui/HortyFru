export default (options, rowIndex) => ({
    options,
    rowIndex,
    category: '',
    query: '',
    open: false,
    activeIndex: 0,
    busy: false,

    get selected() {
        const id = this.$wire.form.detalles[this.rowIndex]?.articulo_id;
        return this.options.find((item) => item.id === Number(id));
    },

    get categories() {
        return [...new Map(this.options.map((item) => [item.categoryId, {
            id: item.categoryId, name: item.category,
        }])).values()].sort((a, b) => a.name.localeCompare(b.name, 'es'));
    },

    normalize(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    },

    get filteredOptions() {
        const term = this.normalize(this.query.trim());
        return this.options.filter((item) =>
            (!this.category || String(item.categoryId) === this.category)
            && this.normalize(item.name).includes(term),
        ).sort((a, b) => a.category.localeCompare(b.category, 'es') || a.name.localeCompare(b.name, 'es'));
    },

    show() {
        this.query = '';
        this.activeIndex = 0;
        this.open = true;
        this.$nextTick(() => this.$refs.search.focus());
    },

    close(restoreFocus = false) {
        this.open = false;
        this.query = '';
        if (restoreFocus) {
            this.$refs.trigger.focus();
        }
    },

    async changeCategory() {
        this.query = '';
        this.activeIndex = 0;
        if (this.selected && this.category && String(this.selected.categoryId) !== this.category) {
            await this.choose(null, false);
        }
    },

    move(direction) {
        const count = this.filteredOptions.length;
        if (!count) {
            return;
        }
        this.activeIndex = (this.activeIndex + direction + count) % count;
        this.$nextTick(() => this.$refs.results.querySelectorAll('[role="option"]')[this.activeIndex]?.scrollIntoView({ block: 'nearest' }));
    },

    async choose(item, restoreFocus = true) {
        if (this.busy) {
            return;
        }
        this.close(restoreFocus);
        this.busy = true;
        try {
            await this.$wire.selectArticle(this.rowIndex, item ? String(item.id) : '');
        } finally {
            this.busy = false;
        }
    },
});
