import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import ApexCharts from 'apexcharts';
import { createPopper } from '@popperjs/core';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.createPopper = createPopper;

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

Livewire.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
});
