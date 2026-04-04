import { collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, where, orderBy } from 'firebase/firestore';
import { db } from '../firebase';

export default function expensesData() {
    return {
        expenses: { data: [], current_page: 1, last_page: 1, total: 0 },
        categories: [],
        filteredCategories: [],
        isNative: window.Capacitor?.isNativePlatform() ?? false,
        showModal: false,
        editingExpense: null,
        listening: false,
        voiceHint: '',
        showSmsModal: false,
        smsText: '',
        smsList: [],
        smsLoading: false,
        smsPreview: null,
        smsError: '',
        filters: {
            type: '',
            category_id: '',
            start_date: '',
            end_date: ''
        },
        form: {
            user_id: 1,
            category_id: '',
            amount: '',
            type: 'expense',
            description: '',
            date: new Date().toISOString().split('T')[0],
            payment_method: '',
            reference: ''
        },
        async init() {
            await this.fetchCategories();
            await this.fetchExpenses();
            this.filterCategoriesByType();

            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            if (type === 'expense' || type === 'income') {
                this.form.type = type;
                this.filterCategoriesByType();
                this.showModal = true;
            }
        },
        async fetchExpenses() {
            try {
                let q = collection(db, 'expenses');
                const querySnapshot = await getDocs(query(q, orderBy('date', 'desc')));
                
                let results = querySnapshot.docs.map(doc => ({
                    id: doc.id,
                    ...doc.data()
                }));

                results = results.map(exp => {
                    const cat = this.categories.find(c => c.id === exp.category_id);
                    return { ...exp, category: cat || { name: 'Unknown', color: '#ccc' } };
                });

                if (this.filters.type) {
                    results = results.filter(e => e.type === this.filters.type);
                }
                if (this.filters.category_id) {
                    results = results.filter(e => e.category_id === this.filters.category_id);
                }
                if (this.filters.start_date) {
                    results = results.filter(e => e.date >= this.filters.start_date);
                }
                if (this.filters.end_date) {
                    results = results.filter(e => e.date <= this.filters.end_date);
                }

                this.expenses.data = results.slice(0, 50);
                this.expenses.total = results.length;
            } catch (error) {
                console.error('Error fetching expenses:', error);
            }
        },
        async fetchCategories() {
            try {
                const querySnapshot = await getDocs(collection(db, 'categories'));
                this.categories = querySnapshot.docs.map(doc => ({
                    id: doc.id,
                    ...doc.data()
                }));
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        filterCategoriesByType() {
            this.filteredCategories = this.categories.filter(c => c.type === this.form.type);
        },
        openModal() {
            this.showModal = true;
            this.editingExpense = null;
            this.form = {
                user_id: 1,
                category_id: '',
                amount: '',
                type: 'expense',
                description: '',
                date: new Date().toISOString().split('T')[0],
                payment_method: '',
                reference: ''
            };
            this.filterCategoriesByType();
        },
        closeModal() {
            this.showModal = false;
            this.editingExpense = null;
        },
        editExpense(expense) {
            this.editingExpense = expense;
            this.form = { ...expense, amount: parseFloat(expense.amount) };
            this.filterCategoriesByType();
            this.showModal = true;
        },
        async saveExpense() {
            try {
                const dataToSave = {
                    ...this.form,
                    amount: parseFloat(this.form.amount)
                };
                
                if (this.editingExpense) {
                    await updateDoc(doc(db, 'expenses', this.editingExpense.id), dataToSave);
                } else {
                    await addDoc(collection(db, 'expenses'), dataToSave);
                }
                await this.fetchExpenses();
                this.closeModal();
            } catch (error) {
                console.error('Error saving expense:', error);
                alert('Error processing transaction');
            }
        },
        async deleteExpense(id) {
            if (!await window.confirmDelete('this transaction')) return;
            try {
                await deleteDoc(doc(db, 'expenses', id));
                await this.fetchExpenses();
            } catch (error) {
                console.error('Error deleting expense:', error);
            }
        },
        formatCurrency(amount) {
            return window.formatCurrency(amount);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
        },
        startVoiceInput() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) { alert('Voice not supported.'); return; }
            if (this.listening) return;
            this.listening = true;
            this.showVoiceHint('Say: "Add expense, lunch, P85"');
            const recognition = new SR();
            recognition.lang = 'en-US';
            recognition.onresult = (e) => this.parseVoiceCommand(e.results[0][0].transcript.toLowerCase());
            recognition.onerror = () => { this.listening = false; this.voiceHint = ''; };
            recognition.onend = () => { this.listening = false; };
            recognition.start();
        },
        parseVoiceCommand(text) {
            const typeMatch = text.match(/\b(income|expense)\b/i);
            const amountMatch = text.match(/p?\s*([\d]+(?:\.\d{1,2})?)/i);
            let desc = text.replace(/add\s+/i, '').replace(/\b(income|expense)\b/i, '').replace(/p?\s*[\d]+(?:\.\d{1,2})?/, '').replace(/[,\.]+/g, ' ').trim();
            if (!amountMatch) { this.showVoiceHint('Could not detect amount.', 3000); return; }
            this.openModal();
            this.form.type = typeMatch ? typeMatch[1].toLowerCase() : 'expense';
            this.form.amount = parseFloat(amountMatch[1]);
            this.form.description = desc;
            this.filterCategoriesByType();
            const lower = desc.toLowerCase();
            const matched = this.filteredCategories.find(c => lower.includes(c.name.toLowerCase()));
            if (matched) this.form.category_id = matched.id;
        },
        showVoiceHint(msg, duration = 0) {
            this.voiceHint = msg;
            if (duration > 0) setTimeout(() => this.voiceHint = '', duration);
        },
        async openSmsModal() {
            this.showSmsModal = true;
            this.smsText = '';
            this.smsList = [];
            this.smsPreview = null;
            this.smsError = '';

            if (this.isNative) {
                this.smsLoading = true;
                try {
                    const SmsPlugin = window.Capacitor?.Plugins?.SmsPlugin;
                    if (!SmsPlugin) throw new Error('SmsPlugin not available');
                    const result = await SmsPlugin.getSmsInbox({ limit: 150 });
                    this.smsList = result.messages || [];
                    if (this.smsList.length === 0) {
                        this.smsError = 'No financial SMS found in your inbox.';
                    }
                } catch (err) {
                    this.smsError = err.message || 'Could not read SMS inbox.';
                } finally {
                    this.smsLoading = false;
                }
            }
        },
        async selectSms(body) {
            this.smsText = body;
            await this.previewSms();
        },
        async previewSms() {
            this.smsError = '';
            try {
                const resp = await window.fetchWithCsrf('/api/v1/sms/preview', {
                    method: 'POST', body: JSON.stringify({ sms: this.smsText })
                });
                const data = await resp.json();
                if (resp.ok) { this.smsPreview = data; } else { this.smsError = data.error || 'Could not parse SMS.'; }
            } catch { this.smsError = 'Network error.'; }
        },
        async importSms() {
            if (!this.smsPreview) return;
            try {
                const dataToSave = {
                    user_id: 1,
                    category_id: this.smsPreview.category_id || '',
                    amount: parseFloat(this.smsPreview.amount),
                    type: this.smsPreview.type || 'expense',
                    description: this.smsPreview.description || 'Imported via SMS',
                    date: this.smsPreview.date || new Date().toISOString().split('T')[0],
                    payment_method: 'Mobile Money',
                    reference: this.smsPreview.reference || ''
                };
                await addDoc(collection(db, 'expenses'), dataToSave);
                this.showSmsModal = false;
                await this.fetchExpenses();
                this.showVoiceHint('SMS Imported!', 3000);
            } catch (err) {
                this.smsError = 'Failed to save to Firestore.';
            }
        }
    }
}
