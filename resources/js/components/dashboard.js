import { collection, getDocs } from 'firebase/firestore';
import { db } from '../firebase';
import Chart from 'chart.js/auto';

export default function dashboardData() {
    return {
        stats: {
            total_income: 0,
            total_expense: 0,
            balance: 0,
            savings_rate: 0,
            spend_pct: 0,
            expenses_by_category: [],
            income_by_category: []
        },
        recentTransactions: [],
        pieChart: null,
        lineChart: null,
        async init() {
            await this.fetchStatsAndDrawCharts();
        },
        async fetchStatsAndDrawCharts() {
            try {
                const catSnap = await getDocs(collection(db, 'categories'));
                const categories = catSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));

                const expSnap = await getDocs(collection(db, 'expenses'));
                const expenses = expSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));

                let totalInc = 0;
                let totalExp = 0;
                let expByCategory = {};
                let incByCategory = {};

                const now = new Date();
                const currentMonthStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
                let dailyData = {};
                for (let i = 1; i <= now.getDate(); i++) {
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
                    if (exp.date && exp.date.startsWith(currentMonthStr)) {
                        if (!dailyData[exp.date]) dailyData[exp.date] = { income: 0, expense: 0 };
                        dailyData[exp.date][exp.type] += amt;
                    }
                });

                this.stats.total_income = totalInc;
                this.stats.total_expense = totalExp;
                this.stats.balance = totalInc - totalExp;
                this.stats.savings_rate = totalInc > 0 ? Math.round(((totalInc - totalExp) / totalInc) * 100) : 0;
                this.stats.spend_pct = totalInc > 0 ? Math.round((totalExp / totalInc) * 100) : 0;

                this.stats.expenses_by_category = Object.keys(expByCategory).map(cid => {
                    const cat = categories.find(c => c.id === cid) || { name: 'Unknown', color: '#ccc' };
                    return { category_id: cid, category: cat, total: expByCategory[cid] };
                }).sort((a, b) => b.total - a.total);

                this.stats.income_by_category = Object.keys(incByCategory).map(cid => {
                    const cat = categories.find(c => c.id === cid) || { name: 'Unknown', color: '#ccc' };
                    return { category_id: cid, category: cat, total: incByCategory[cid] };
                }).sort((a, b) => b.total - a.total);

                // Recent transactions: last 8 sorted by date desc
                this.recentTransactions = expenses
                    .map(exp => ({
                        ...exp,
                        category: categories.find(c => c.id === exp.category_id) || { name: 'Unknown', color: '#e5e7eb', icon: '💸' }
                    }))
                    .sort((a, b) => new Date(b.date) - new Date(a.date))
                    .slice(0, 8);

                this.drawPieChart();
                this.drawLineChart(dailyData);
            } catch (error) {
                console.error('Error fetching dashboard stats:', error);
            }
        },
        drawPieChart() {
            const canvas = document.getElementById('expensesPieChart');
            if (!canvas) return;
            if (this.pieChart) this.pieChart.destroy();

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            const labels = this.stats.expenses_by_category.map(i => i.category.name);
            const data = this.stats.expenses_by_category.map(i => i.total);
            const bgColors = this.stats.expenses_by_category.map(i => i.category.color);

            this.pieChart = new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels.length ? labels : ['No Data'],
                    datasets: [{
                        data: data.length ? data : [1],
                        backgroundColor: bgColors.length ? bgColors : ['#e5e7eb'],
                        borderWidth: 2,
                        borderColor: isDark ? '#1f2937' : '#fff',
                        hoverBorderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, boxWidth: 12, padding: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        },
        drawLineChart(dailyData) {
            const canvas = document.getElementById('cashflowLineChart');
            if (!canvas) return;
            if (this.lineChart) this.lineChart.destroy();

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            const sortedDates = Object.keys(dailyData).sort();
            const labels = sortedDates.map(d => {
                const date = new Date(d + 'T00:00:00');
                return `${date.getDate()} ${date.toLocaleString('en-US', { month: 'short' })}`;
            });

            this.lineChart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: sortedDates.map(d => dailyData[d].income),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.08)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointBackgroundColor: '#10b981'
                        },
                        {
                            label: 'Expense',
                            data: sortedDates.map(d => dailyData[d].expense),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.08)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointBackgroundColor: '#ef4444'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: textColor, boxWidth: 12, padding: 12, font: { size: 11 } }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { size: 10 }, maxTicksLimit: 8 },
                            grid: { color: gridColor }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor, font: { size: 10 } },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        },
        formatCurrency(amount) {
            return window.formatCurrency ? window.formatCurrency(amount) : amount;
        },
        formatDate(date) {
            if (!date) return '';
            const d = new Date(date + 'T00:00:00');
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            if (d.toDateString() === today.toDateString()) return 'Today';
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    }
}
