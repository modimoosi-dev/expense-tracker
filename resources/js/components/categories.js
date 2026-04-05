import { collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, where } from 'firebase/firestore';
import { db } from '../firebase';

export default function categoriesData() {
    return {
        categories: [],
        showModal: false,
        editingCategory: null,
        filterType: 'all',
        form: {
            name: '',
            type: 'expense',
            color: '#3B82F6'
        },
        async init() {
            await this.fetchCategories();
        },
        async fetchCategories() {
            try {
                const uid = window.currentUserId;
                let q = query(collection(db, 'categories'), where('user_id', '==', uid));
                if (this.filterType !== 'all') {
                    q = query(collection(db, 'categories'), where('user_id', '==', uid), where('type', '==', this.filterType));
                }
                const querySnapshot = await getDocs(q);
                this.categories = querySnapshot.docs.map(doc => ({
                    id: doc.id,
                    ...doc.data()
                }));
                if (this.categories.length === 0 && this.filterType === 'all') {
                    await this.seedDefaultCategories();
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async seedDefaultCategories() {
            const uid = window.currentUserId;
            const defaults = [
                { name: 'Food & Dining',    type: 'expense', color: '#f97316', icon: '🍽️' },
                { name: 'Transport',        type: 'expense', color: '#3b82f6', icon: '🚗' },
                { name: 'Shopping',         type: 'expense', color: '#ec4899', icon: '🛍️' },
                { name: 'Utilities',        type: 'expense', color: '#8b5cf6', icon: '💡' },
                { name: 'Health',           type: 'expense', color: '#ef4444', icon: '🏥' },
                { name: 'Entertainment',    type: 'expense', color: '#06b6d4', icon: '🎬' },
                { name: 'Education',        type: 'expense', color: '#10b981', icon: '📚' },
                { name: 'Housing',          type: 'expense', color: '#6366f1', icon: '🏠' },
                { name: 'Salary',           type: 'income',  color: '#22c55e', icon: '💼' },
                { name: 'Freelance',        type: 'income',  color: '#84cc16', icon: '💻' },
                { name: 'Business',         type: 'income',  color: '#eab308', icon: '🏢' },
                { name: 'Other Income',     type: 'income',  color: '#14b8a6', icon: '💰' },
            ];
            await Promise.all(defaults.map(cat => addDoc(collection(db, 'categories'), { ...cat, user_id: uid })));
            await this.fetchCategories();
        },
        openModal() {
            this.showModal = true;
            this.editingCategory = null;
            this.form = { name: '', type: 'expense', color: '#3B82F6' };
        },
        closeModal() {
            this.showModal = false;
            this.editingCategory = null;
        },
        editCategory(category) {
            this.editingCategory = category;
            this.form = { ...category };
            this.showModal = true;
        },
        async saveCategory() {
            try {
                const dataToSave = {
                    user_id: window.currentUserId,
                    name: this.form.name,
                    type: this.form.type,
                    color: this.form.color
                };

                if (this.editingCategory) {
                    await updateDoc(doc(db, 'categories', this.editingCategory.id), dataToSave);
                } else {
                    await addDoc(collection(db, 'categories'), dataToSave);
                }
                await this.fetchCategories();
                this.closeModal();
            } catch (error) {
                console.error('Firestore error:', error);
                window.showAlert?.('Save Failed', error.code || error.message, 'error');
            }
        },
        async deleteCategory(id) {
            if (!await window.confirmDelete('this category')) return;
            try {
                await deleteDoc(doc(db, 'categories', id));
                await this.fetchCategories();
            } catch (error) {
                console.error('Error deleting category:', error);
            }
        }
    }
}
