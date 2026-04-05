import { collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, where } from 'firebase/firestore';
import { db } from '../firebase';

export default function budgetsData() {
    return {
        budgets: [],
        categories: [],
        expenses: [],
        showModal: false,
        editingBudget: null,
        form: {
            user_id: window.currentUserId,
            category_id: '',
            name: '',
            amount: '',
            period: 'monthly',
            start_date: '',
            end_date: '',
            is_active: true
        },
        get activeBudgets() {
            return this.budgets.filter(b => b.is_active);
        },
        get inactiveBudgets() {
            return this.budgets.filter(b => !b.is_active);
        },
        async init() {
            await this.fetchCategories();
            await this.fetchExpenses();
            await this.fetchBudgets();
            this.setDefaultDates();
        },
        async fetchExpenses() {
            try {
                const uid = window.currentUserId;
                const querySnapshot = await getDocs(query(collection(db, 'expenses'), where('user_id', '==', uid)));
                this.expenses = querySnapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
            } catch (error) {
                console.error('Error fetching expenses:', error);
            }
        },
        async fetchCategories() {
            try {
                const uid = window.currentUserId;
                const querySnapshot = await getDocs(query(collection(db, 'categories'), where('user_id', '==', uid)));
                this.categories = querySnapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async fetchBudgets() {
            try {
                const uid = window.currentUserId;
                const querySnapshot = await getDocs(query(collection(db, 'budgets'), where('user_id', '==', uid)));
                let rawBudgets = querySnapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
                
                this.budgets = rawBudgets.map(budget => {
                    const budgetAmt = parseFloat(budget.amount) || 0;
                    
                    let spent = 0;
                    this.expenses.forEach(exp => {
                        if (exp.type === 'expense') {
                           if (exp.date >= budget.start_date && exp.date <= budget.end_date) {
                               if (!budget.category_id || exp.category_id === budget.category_id) {
                                   spent += parseFloat(exp.amount) || 0;
                               }
                           }
                        }
                    });

                    const cat = this.categories.find(c => c.id === budget.category_id);

                    return {
                        ...budget,
                        amount: budgetAmt,
                        spent_amount: spent,
                        remaining_amount: budgetAmt - spent,
                        percentage_used: budgetAmt > 0 ? (spent / budgetAmt) * 100 : 0,
                        category: cat || null
                    };
                });
            } catch (error) {
                console.error('Error fetching budgets:', error);
            }
        },
        setDefaultDates() {
            const now = new Date();
            this.form.start_date = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            this.updateDates();
        },
        updateDates() {
            if (!this.form.start_date) return;
            const start = new Date(this.form.start_date);
            let end = new Date(start);

            if (this.form.period === 'monthly') {
                end.setMonth(end.getMonth() + 1);
                end.setDate(end.getDate() - 1);
            } else if (this.form.period === 'quarterly') {
                end.setMonth(end.getMonth() + 3);
                end.setDate(end.getDate() - 1);
            } else if (this.form.period === 'yearly') {
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1);
            }
            this.form.end_date = end.toISOString().split('T')[0];
        },
        openModal() {
            this.showModal = true;
            this.editingBudget = null;
            this.setDefaultDates();
            this.form = {
                user_id: window.currentUserId, category_id: '', name: '', amount: '', period: 'monthly',
                start_date: this.form.start_date, end_date: this.form.end_date, is_active: true
            };
        },
        closeModal() {
            this.showModal = false;
            this.editingBudget = null;
        },
        editBudget(budget) {
            this.editingBudget = budget;
            this.form = {
                user_id: budget.user_id,
                category_id: budget.category_id || '',
                name: budget.name,
                amount: budget.amount,
                period: budget.period,
                start_date: budget.start_date,
                end_date: budget.end_date,
                is_active: budget.is_active
            };
            this.showModal = true;
        },
        async saveBudget() {
            try {
                const dataToSave = { ...this.form, amount: parseFloat(this.form.amount) };
                if (this.editingBudget) {
                    await updateDoc(doc(db, 'budgets', this.editingBudget.id), dataToSave);
                } else {
                    await addDoc(collection(db, 'budgets'), dataToSave);
                }
                await this.fetchBudgets();
                this.closeModal();
            } catch (error) {
                console.error('Error saving budget:', error);
            }
        },
        async toggleActive(budget) {
            try {
                await updateDoc(doc(db, 'budgets', budget.id), { is_active: !budget.is_active });
                await this.fetchBudgets();
            } catch (error) {
                console.error('Error toggling budget:', error);
            }
        },
        async deleteBudget(id) {
            if (!await window.confirmDelete('this budget')) return;
            try {
                await deleteDoc(doc(db, 'budgets', id));
                await this.fetchBudgets();
            } catch (error) {
                console.error('Error deleting budget:', error);
            }
        },
        formatCurrency(amount) { return window.formatCurrency(amount); },
        formatDate(date) { return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); },
        formatPeriod(period) { return period.charAt(0).toUpperCase() + period.slice(1); }
    }
}
