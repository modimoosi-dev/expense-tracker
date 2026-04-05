import './bootstrap';
import Alpine from 'alpinejs';
import { db } from './firebase';
import { collection, getDocs, query, where, addDoc } from 'firebase/firestore';
import categoriesData from './components/categories';
import expensesData from './components/expenses';
import budgetsData from './components/budgets';
import dashboardData from './components/dashboard';

window.Alpine = Alpine;
window.firebaseDB = db;

// Get CSRF token and current user ID from meta tags
window.csrfToken    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
window.currentUserId = parseInt(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '0');

// Global fetch wrapper that includes CSRF token
window.fetchWithCsrf = function(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken,
        ...options.headers
    };
    return fetch(url, { ...options, headers });
};

// Global currency formatter
window.formatCurrency = function(amount, currency = null) {
    const userCurrency = currency || localStorage.getItem('userCurrency') || 'BWP';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: userCurrency
    }).format(amount || 0);
};

// Fetch and store user currency on app load
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

// Global helper — add an expense to Firestore
window.addFirestoreExpense = async function(data) {
    const docRef = await addDoc(collection(db, 'expenses'), {
        user_id: window.currentUserId,
        ...data,
    });
    return docRef.id;
};

// Global helper — fetch current user's categories from Firestore
window.getUserCategories = async function(type = null) {
    const uid = window.currentUserId;
    let q = query(collection(db, 'categories'), where('user_id', '==', uid));
    if (type) q = query(collection(db, 'categories'), where('user_id', '==', uid), where('type', '==', type));
    const snap = await getDocs(q);
    return snap.docs.map(d => ({ id: d.id, ...d.data() }));
};

Alpine.data('categoriesData', categoriesData);
Alpine.data('expensesData', expensesData);
Alpine.data('budgetsData', budgetsData);
Alpine.data('dashboardData', dashboardData);
Alpine.start();

// On native: check if app was opened via "Open with" on a PDF/CSV file
if (window.Capacitor?.isNativePlatform()) {
    window.addEventListener('load', async () => {
        try {
            const CsvIntent = window.Capacitor?.Plugins?.CsvIntent;
            if (!CsvIntent) return;
            const { base64, mimeType } = await CsvIntent.getPendingFile();
            if (base64) {
                sessionStorage.setItem('pendingFileBase64', base64);
                sessionStorage.setItem('pendingFileMimeType', mimeType || 'application/pdf');
                if (!window.location.pathname.includes('/expenses')) {
                    window.location.href = '/expenses?import=statement';
                }
            }
        } catch { /* ignore */ }
    });
}
