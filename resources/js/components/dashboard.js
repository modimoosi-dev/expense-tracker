import { collection, getDocs } from 'firebase/firestore';
import { db } from '../firebase';
import Chart from 'chart.js/auto';

export default function dashboardData() {
    return {
        stats: {
            total_income: 0,
            total_expense: 0,
            balance: 0,
            expenses_by_category: [],
            income_by_category: []
        },
        pieChart: null,
        lineChart: null,
        async init() {
            await this.fetchStatsAndDrawCharts();
        },
        async fetchStatsAndDrawCharts() {
            try {
                // Fetch categories
                const catSnap = await getDocs(collection(db, 'categories'));
                const categories = catSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
                
                // Fetch expenses
                const expSnap = await getDocs(collection(db, 'expenses'));
                const expenses = expSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));

                let totalInc = 0;
                let totalExp = 0;
                let expByCategory = {};
                let incByCategory = {};
                
                const now = new Date();
                const currentMonth = now.getMonth() + 1; // 1-12
                const currentYear = now.getFullYear();
                const currentMonthStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}`;

                let dailyData = {}; 

                // Pre-fill days from 1 to current day of the month for a continuous trend line
                const today = now.getDate();
                for (let i = 1; i <= today; i++) {
                    const dateStr = `${currentMonthStr}-${String(i).padStart(2, '0')}`;
                    dailyData[dateStr] = { income: 0, expense: 0 };
                }

                expenses.forEach(exp => {
                    const amt = parseFloat(exp.amount) || 0;
                    if (exp.type === 'income') {
                        totalInc += amt;
                        incByCategory[exp.category_id] = (incByCategory[exp.category_id] || 0) + amt;
                    } else {
                        totalExp += amt;
                        expByCategory[exp.category_id] = (expByCategory[exp.category_id] || 0) + amt;
                    }

                    // Populate chart data only for the current month
                    if (exp.date && exp.date.startsWith(currentMonthStr)) {
                        if (!dailyData[exp.date]) dailyData[exp.date] = { income: 0, expense: 0 };
                        dailyData[exp.date][exp.type] += amt;
                    }
                });

                this.stats.total_income = totalInc;
                this.stats.total_expense = totalExp;
                this.stats.balance = totalInc - totalExp;

                this.stats.expenses_by_category = Object.keys(expByCategory).map(cid => {
                    const cat = categories.find(c => c.id === cid) || { name: 'Unknown', color: '#ccc' };
                    return { category_id: cid, category: cat, total: expByCategory[cid] };
                }).sort((a,b) => b.total - a.total);

                this.stats.income_by_category = Object.keys(incByCategory).map(cid => {
                    const cat = categories.find(c => c.id === cid) || { name: 'Unknown', color: '#ccc' };
                    return { category_id: cid, category: cat, total: incByCategory[cid] };
                }).sort((a,b) => b.total - a.total);

                this.drawPieChart();
                this.drawLineChart(dailyData);

            } catch (error) {
                console.error('Error fetching dashboard stats:', error);
            }
        },
        drawPieChart() {
            const canvas = document.getElementById('expensesPieChart');
            if(!canvas) return;
            const ctx = canvas.getContext('2d');
            if (this.pieChart) this.pieChart.destroy();
            
            const labels = this.stats.expenses_by_category.map(item => item.category.name);
            const data = this.stats.expenses_by_category.map(item => item.total);
            const bgColors = this.stats.expenses_by_category.map(item => item.category.color);
            
            this.pieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels.length ? labels : ['No Data'],
                    datasets: [{
                        data: data.length ? data : [1],
                        backgroundColor: bgColors.length ? bgColors : ['#e5e7eb'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        },
        drawLineChart(dailyData) {
            const canvas = document.getElementById('cashflowLineChart');
            if(!canvas) return;
            const ctx = canvas.getContext('2d');
            if (this.lineChart) this.lineChart.destroy();

            // Sort dates
            const sortedDates = Object.keys(dailyData).sort();
            const labels = sortedDates.map(d => {
                const date = new Date(d);
                return `${date.getDate()} ${date.toLocaleString('en-US', {month: 'short'})}`;
            });
            const incomeData = sortedDates.map(d => dailyData[d].income);
            const expenseData = sortedDates.map(d => dailyData[d].expense);

            this.lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: incomeData,
                            borderColor: '#10B981', 
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Expense',
                            data: expenseData,
                            borderColor: '#EF4444', 
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },
        formatCurrency(amount) {
            return window.formatCurrency ? window.formatCurrency(amount) : amount;
        }
    }
}
