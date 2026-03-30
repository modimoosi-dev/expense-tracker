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
                let q = collection(db, 'categories');
                if (this.filterType !== 'all') {
                    q = query(q, where('type', '==', this.filterType));
                }
                const querySnapshot = await getDocs(q);
                this.categories = querySnapshot.docs.map(doc => ({
                    id: doc.id,
                    ...doc.data()
                }));
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
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
                alert('Failed to save: ' + (error.code || error.message));
            }
        },
        async deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;
            try {
                await deleteDoc(doc(db, 'categories', id));
                await this.fetchCategories();
            } catch (error) {
                console.error('Error deleting category:', error);
            }
        }
    }
}
