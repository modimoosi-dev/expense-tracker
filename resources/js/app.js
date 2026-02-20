import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global currency formatter
window.formatCurrency = function(amount, currency = null) {
    const userCurrency = currency || localStorage.getItem('userCurrency') || 'USD';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: userCurrency
    }).format(amount || 0);
};

// Fetch and store user currency on app load
fetch('/api/v1/settings?user_id=1')
    .then(r => r.json())
    .then(data => {
        localStorage.setItem('userCurrency', data.currency || 'USD');
    })
    .catch(() => {
        localStorage.setItem('userCurrency', 'USD');
    });

Alpine.start();
