import './bootstrap';
import Alpine from 'alpinejs';
import { db } from './firebase';
import categoriesData from './components/categories';
import expensesData from './components/expenses';
import budgetsData from './components/budgets';
import dashboardData from './components/dashboard';

window.Alpine = Alpine;
window.firebaseDB = db;

// Get CSRF token from meta tag
window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Global fetch wrapper that includes CSRF token
window.fetchWithCsrf = function(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken,
        ...options.headers
    };

    return fetch(url, {
        ...options,
        headers
    });
};

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
        localStorage.setItem('userCurrency', data.currency || 'BWP');
    })
    .catch(() => {
        localStorage.setItem('userCurrency', 'BWP');
    });

// Register service worker for PWA / offline support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

Alpine.data('categoriesData', categoriesData);
Alpine.data('expensesData', expensesData);
Alpine.data('budgetsData', budgetsData);
Alpine.data('dashboardData', dashboardData);
Alpine.start();

