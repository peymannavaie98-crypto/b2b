import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('b2bCustomerPanel', () => ({
        init() {
            console.log('B2B Customer Panel Initialized.');
        },
        openRmaModal() {
            // RMA modal logic
        }
    }));
});

window.Alpine = Alpine;
Alpine.start();
