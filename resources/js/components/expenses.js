import { collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, orderBy, where } from 'firebase/firestore';
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
        smsPreview: null,
        smsError: '',
        showStatementModal: false,
        statementRows: [],
        statementSelected: [],
        statementError: '',
        statementLoading: false,
        statementImporting: false,
        statementNeedsPassword: false,
        statementPassword: '',
        statementPendingFile: null,
        filters: {
            type: '',
            category_id: '',
            start_date: '',
            end_date: ''
        },
        form: {
            user_id: window.currentUserId,
            category_id: '',
            amount: '',
            type: 'expense',
            description: '',
            date: new Date().toISOString().split('T')[0],
            payment_method: '',
            reference: ''
        },
        async init() {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            const importParam = urlParams.get('import');

            if (importParam === 'statement') {
                this.openStatementModal();
            }

            // Check if app was opened via "Open with" on a PDF/CSV file
            const pendingBase64 = sessionStorage.getItem('pendingFileBase64');
            if (pendingBase64) {
                const mimeType = sessionStorage.getItem('pendingFileMimeType') || 'application/pdf';
                sessionStorage.removeItem('pendingFileBase64');
                sessionStorage.removeItem('pendingFileMimeType');
                this.openStatementModal();
                const binary = atob(pendingBase64);
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                const blob = new Blob([bytes], { type: mimeType });
                const ext  = mimeType.includes('pdf') ? 'pdf' : 'csv';
                const file = new File([blob], `statement.${ext}`, { type: mimeType });
                await this.uploadStatement({ target: { files: [file], value: '' } });
            }

            await this.fetchCategories();
            await this.fetchExpenses();
            this.filterCategoriesByType();

            if (type === 'expense' || type === 'income') {
                this.form.type = type;
                this.filterCategoriesByType();
                this.showModal = true;
            }
        },
        async fetchExpenses() {
            try {
                const uid = window.currentUserId;
                const querySnapshot = await getDocs(query(collection(db, 'expenses'), where('user_id', '==', uid), orderBy('date', 'desc')));
                
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
                const uid = window.currentUserId;
                const querySnapshot = await getDocs(query(collection(db, 'categories'), where('user_id', '==', uid)));
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
                user_id: window.currentUserId,
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
        openSmsModal() {
            this.showSmsModal = true;
            this.smsText = '';
            this.smsPreview = null;
            this.smsError = '';
        },
        async pasteFromClipboard() {
            try {
                const text = await navigator.clipboard.readText();
                if (text) {
                    this.smsText = text;
                    await this.previewSms();
                }
            } catch {
                this.smsError = 'Could not read clipboard. Paste manually below.';
            }
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
                    user_id: window.currentUserId,
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
        },

        // ── Bank Statement Import ──────────────────────────────────────────
        openStatementModal() {
            this.showStatementModal = true;
            this.statementRows = [];
            this.statementSelected = [];
            this.statementError = '';
            this.statementLoading = false;
            this.statementImporting = false;
            this.statementNeedsPassword = false;
            this.statementPassword = '';
            this.statementPendingFile = null;
        },
        async uploadStatement(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.statementPendingFile = file;
            this.statementNeedsPassword = false;
            this.statementPassword = '';
            await this._parseStatement(file, '');
            event.target.value = '';
        },
        async submitStatementPassword() {
            if (!this.statementPendingFile) return;
            await this._parseStatement(this.statementPendingFile, this.statementPassword);
        },
        async _parseStatement(file, password) {
            this.statementLoading = true;
            this.statementError = '';
            this.statementRows = [];
            this.statementSelected = [];
            this.statementNeedsPassword = false;
            try {
                const formData = new FormData();
                formData.append('file', file);
                if (password) formData.append('password', password);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch('/api/v1/statement/preview', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await resp.json();
                if (resp.ok) {
                    this.statementRows = data.transactions;
                    this.statementSelected = data.transactions.map((_, i) => i);
                } else if (data.error === 'password_required') {
                    this.statementNeedsPassword = true;
                    this.statementError = password ? 'Incorrect password. Try again.' : '';
                } else {
                    this.statementError = data.error || 'Failed to parse file.';
                }
            } catch {
                this.statementError = 'Network error uploading file.';
            } finally {
                this.statementLoading = false;
            }
        },
        toggleStatementRow(idx) {
            const pos = this.statementSelected.indexOf(idx);
            if (pos === -1) this.statementSelected.push(idx);
            else this.statementSelected.splice(pos, 1);
        },
        selectAllStatement() {
            this.statementSelected = this.statementRows.map((_, i) => i);
        },
        deselectAllStatement() {
            this.statementSelected = [];
        },
        async importStatement() {
            if (this.statementSelected.length === 0) return;
            this.statementImporting = true;
            try {
                const toImport = this.statementSelected.map(i => this.statementRows[i]);
                const promises = toImport.map(row => addDoc(collection(db, 'expenses'), {
                    user_id: window.currentUserId,
                    category_id: '',
                    amount: row.amount,
                    type: row.type,
                    description: row.description,
                    date: row.date,
                    payment_method: 'Bank Transfer',
                    reference: '',
                }));
                await Promise.all(promises);
                this.showStatementModal = false;
                await this.fetchExpenses();
                this.showVoiceHint(`${toImport.length} transactions imported!`, 3000);
            } catch {
                this.statementError = 'Failed to save transactions.';
            } finally {
                this.statementImporting = false;
            }
        },
    }
}
