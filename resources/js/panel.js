import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { createPopper } from '@popperjs/core';
import orderArticleSelect from './components/order-article-select';
import purchaseArticleSelect from './components/purchase-article-select';

Alpine.data('orderArticleSelect', orderArticleSelect);
Alpine.data('purchaseArticleSelect', purchaseArticleSelect);

// flatpickr
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';
window.Alpine = Alpine;
window.flatpickr = flatpickr;
window.flatpickrSpanish = Spanish;
window.createPopper = createPopper;

Alpine.data('panelLayout', () => ({
    desktop: window.innerWidth >= 1280,

    init() {
        this.$store.theme.updateTheme();
        this.$store.sidebar.isMobileOpen = false;
        this.$store.sidebar.isHovered = false;
    },

    checkViewport() {
        const desktop = window.innerWidth >= 1280;

        if (desktop !== this.desktop) {
            this.$store.sidebar.isExpanded = desktop;
            this.$store.sidebar.isMobileOpen = false;
            this.$store.sidebar.isHovered = false;
            this.desktop = desktop;
        }
    },
}));

Alpine.data('quickAccess', () => ({
    isApplicationMenuOpen: false,
    searchOpen: false,
    query: '',
    activeIndex: 0,
    items: [],
    keyboardHandler: null,

    init() {
        this.items = JSON.parse(this.$el.dataset.quickAccessItems || '[]');
        this.keyboardHandler = (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.searchOpen ? this.closeSearch() : this.openSearch();
            }
        };
        window.addEventListener('keydown', this.keyboardHandler);
    },

    destroy() {
        window.removeEventListener('keydown', this.keyboardHandler);
    },

    toggleApplicationMenu() {
        this.isApplicationMenuOpen = !this.isApplicationMenuOpen;
    },

    openSearch() {
        this.searchOpen = true;
        this.query = '';
        this.activeIndex = 0;
        this.$nextTick(() => this.$refs.quickSearchInput.focus());
    },

    closeSearch() {
        this.searchOpen = false;
        this.query = '';
        this.activeIndex = 0;
    },

    resetSelection() {
        this.activeIndex = 0;
    },

    normalize(value) {
        return value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    },

    get filteredItems() {
        const term = this.normalize(this.query.trim());

        if (!term) {
            return this.items.slice(0, 10);
        }

        return this.items
            .filter((item) => this.normalize(`${item.name} ${item.section}`).includes(term))
            .slice(0, 10);
    },

    moveSelection(direction) {
        const total = this.filteredItems.length;

        if (total === 0) {
            return;
        }

        this.activeIndex = (this.activeIndex + direction + total) % total;
    },

    selectActive() {
        const item = this.filteredItems[this.activeIndex];

        if (item) {
            this.navigate(item);
        }
    },

    navigate(item) {
        this.closeSearch();
        Livewire.navigate(item.path);
    },
}));

Alpine.data('toastNotification', () => ({
    visible: false,
    title: '',
    message: '',
    type: 'success',
    progressFull: false,
    timerId: null,

    init() {
        const message = this.$el.dataset.initialMessage;

        if (message) {
            this.show({
                message,
                title: this.$el.dataset.initialTitle,
                type: this.$el.dataset.initialType,
            });
        }
    },

    destroy() {
        window.clearTimeout(this.timerId);
    },

    show(payload = {}) {
        window.clearTimeout(this.timerId);

        this.type = ['success', 'info', 'warning', 'error'].includes(payload.type)
            ? payload.type
            : 'success';
        this.title = payload.title || 'Operación completada';
        this.message = payload.message || '';
        this.visible = true;
        this.progressFull = true;

        this.$nextTick(() => {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    this.progressFull = false;
                });
            });
        });

        this.timerId = window.setTimeout(() => this.close(), 3000);
    },

    close() {
        window.clearTimeout(this.timerId);
        this.visible = false;
    },

    get appearance() {
        return {
            success: {
                container: 'border-success-200 dark:border-success-500/30',
                iconBackground: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
                progress: 'bg-success-500',
            },
            info: {
                container: 'border-brand-200 dark:border-brand-500/30',
                iconBackground: 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                progress: 'bg-brand-500',
            },
            warning: {
                container: 'border-warning-200 dark:border-warning-500/30',
                iconBackground: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
                progress: 'bg-warning-500',
            },
            error: {
                container: 'border-error-200 dark:border-error-500/30',
                iconBackground: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
                progress: 'bg-error-500',
            },
        }[this.type];
    },
}));

const pageWidgets = [
    ['#mapOne', () => import('./components/map'), 'initMap'],
    ['#chartOne', () => import('./components/chart/chart-1'), 'initChartOne'],
    ['#chartTwo', () => import('./components/chart/chart-2'), 'initChartTwo'],
    ['#chartThree', () => import('./components/chart/chart-3'), 'initChartThree'],
    ['#chartSix', () => import('./components/chart/chart-6'), 'initChartSix'],
    ['#chartEight', () => import('./components/chart/chart-8'), 'initChartEight'],
    ['#chartThirteen', () => import('./components/chart/chart-13'), 'initChartThirteen'],
    ['#calendar', () => import('./components/calendar-init'), 'calendarInit'],
];

let pageGeneration = 0;
const activeWidgets = new Map();

document.addEventListener('livewire:navigating', () => {
    pageGeneration++;

    for (const instance of activeWidgets.values()) {
        instance?.destroy();
    }

    activeWidgets.clear();
});

document.addEventListener('livewire:navigated', () => {
    const generation = pageGeneration;

    for (const [selector, load, initialize] of pageWidgets) {
        const element = document.querySelector(selector);

        if (!element || activeWidgets.has(element)) {
            continue;
        }

        activeWidgets.set(element, null);
        load().then((module) => {
            if (generation !== pageGeneration || !element.isConnected) {
                return;
            }

            element.replaceChildren();
            activeWidgets.set(element, module[initialize]());
        }).catch((error) => {
            activeWidgets.delete(element);
            console.error('No se pudo inicializar el componente del panel:', selector, error);
        });
    }
});

Livewire.start();
