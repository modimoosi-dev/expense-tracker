import './bootstrap';
import Alpine from 'alpinejs';
import { db } from './firebase';
import { Browser } from '@capacitor/browser';
import { App as CapApp } from '@capacitor/app';
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
    const userCurrency = currency || localStorage.getItem('userCurrency') || 'BWP';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: userCurrency
    }).format(amount || 0);
};

// Fetch and store user currency on app load (always re-fetch to avoid stale cache)
fetch('/api/v1/settings')
    .then(r => r.json())
    .then(data => {
        localStorage.setItem('userCurrency', data.currency || 'BWP');
    })
    .catch(() => {
        if (!localStorage.getItem('userCurrency')) {
            localStorage.setItem('userCurrency', 'BWP');
        }
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

// Google OAuth handler — used by login page button
window.handleGoogleLogin = async function(googleUrl) {
    if (window.Capacitor) {
        await Browser.open({ url: googleUrl, presentationStyle: 'popover', toolbarColor: '#4f46e5' });
    } else {
        window.location.href = googleUrl;
    }
};

// Handle Google OAuth deep link callback (mobile only)
if (window.Capacitor) {
    CapApp.addListener('appUrlOpen', async ({ url }) => {
        if (url.startsWith('com.expensetracker.bw://auth')) {
            const token = new URL(url).searchParams.get('token');
            if (!token) return;

            Browser.close().catch(() => {});

            const res = await fetch('/auth/verify-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                },
                body: JSON.stringify({ token }),
                credentials: 'include',
            });

            if (res.ok) window.location.href = '/dashboard';
        }
    });
}

