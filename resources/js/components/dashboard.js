import { collection, getDocs, query, where } from 'firebase/firestore';
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
        },
        recentTransactions: [],
        allExpenses: [],
        allCategories: [],
        cashflowPeriod: 'monthly',
        categoryPeriod: 'all',
        pieChart: null,
        lineChart: null,

        async init() {
            await this.fetchStatsAndDrawCharts();
        },

        async fetchStatsAndDrawCharts() {
            try {
                const uid = window.currentUserId;
                const catSnap = await getDocs(query(collection(db, 'categories'), where('user_id', '==', uid)));
                this.allCategories = catSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));

                const expSnap = await getDocs(query(collection(db, 'expenses'), where('user_id', '==', uid)));
                this.allExpenses = expSnap.docs.map(doc => ({ id: doc.id, ...doc.data() }));

                this.computeStats();
                this.redrawCashflow();
                this.redrawPie();

                this.recentTransactions = this.allExpenses
                    .map(exp => ({
                        ...exp,
                        category: this.allCategories.find(c => c.id === exp.category_id) || { name: 'Unknown', color: '#e5e7eb', icon: '💸' }
                    }))
                    .sort((a, b) => new Date(b.date) - new Date(a.date))
                    .slice(0, 8);

            } catch (error) {
                console.error('Error fetching dashboard stats:', error);
            }
        },

        computeStats() {
            let totalInc = 0, totalExp = 0;
            this.allExpenses.forEach(exp => {
                const amt = parseFloat(exp.amount) || 0;
                if (exp.type === 'income') totalInc += amt;
                else totalExp += amt;
            });
            this.stats.total_income  = totalInc;
            this.stats.total_expense = totalExp;
            this.stats.balance       = totalInc - totalExp;
            this.stats.savings_rate  = totalInc > 0 ? Math.round(((totalInc - totalExp) / totalInc) * 100) : 0;
            this.stats.spend_pct     = totalInc > 0 ? Math.round((totalExp / totalInc) * 100) : 0;
        },

        // ── Cashflow chart ────────────────────────────────────────────────
        redrawCashflow() {
            const period = this.cashflowPeriod;
            const now    = new Date();
            let grouped  = {};
            let filtered = [];

            if (period === 'daily') {
                // Current month, day by day
                const monthStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
                for (let i = 1; i <= now.getDate(); i++) {
                    grouped[`${monthStr}-${String(i).padStart(2, '0')}`] = { income: 0, expense: 0 };
                }
                filtered = this.allExpenses.filter(e => e.date && e.date.startsWith(monthStr));
                filtered.forEach(e => {
                    if (!grouped[e.date]) grouped[e.date] = { income: 0, expense: 0 };
                    grouped[e.date][e.type] += parseFloat(e.amount) || 0;
                });

            } else if (period === 'weekly') {
                // Last 12 weeks
                for (let i = 11; i >= 0; i--) {
                    const d = new Date(now);
                    d.setDate(now.getDate() - i * 7);
                    const key = this.weekKey(d);
                    grouped[key] = grouped[key] || { income: 0, expense: 0 };
                }
                this.allExpenses.forEach(e => {
                    if (!e.date) return;
                    const key = this.weekKey(new Date(e.date + 'T00:00:00'));
                    if (grouped[key] !== undefined) {
                        grouped[key][e.type] += parseFloat(e.amount) || 0;
                    }
                });

            } else {
                // Monthly — last 12 months
                for (let i = 11; i >= 0; i--) {
                    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
                    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
                    grouped[key] = { income: 0, expense: 0 };
                }
                this.allExpenses.forEach(e => {
                    if (!e.date) return;
                    const key = e.date.substring(0, 7);
                    if (grouped[key] !== undefined) {
                        grouped[key][e.type] += parseFloat(e.amount) || 0;
                    }
                });
            }

            const keys   = Object.keys(grouped).sort();
            const labels = keys.map(k => this.formatChartLabel(k, period));
            const incomeData  = keys.map(k => grouped[k].income);
            const expenseData = keys.map(k => grouped[k].expense);

            this.drawBarChart(labels, incomeData, expenseData);
        },

        weekKey(date) {
            const d = new Date(date);
            d.setDate(d.getDate() - d.getDay()); // Sunday of that week
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },

        formatChartLabel(key, period) {
            if (period === 'daily') {
                const d = new Date(key + 'T00:00:00');
                return `${d.getDate()} ${d.toLocaleString('en-US', { month: 'short' })}`;
            }
            if (period === 'weekly') {
                const d = new Date(key + 'T00:00:00');
                return `${d.getDate()} ${d.toLocaleString('en-US', { month: 'short' })}`;
            }
            // monthly: "Jan", "Feb" etc
            const [year, month] = key.split('-');
            const d = new Date(year, parseInt(month) - 1, 1);
            return d.toLocaleString('en-US', { month: 'short', year: '2-digit' });
        },

        drawBarChart(labels, incomeData, expenseData) {
            const canvas = document.getElementById('cashflowLineChart');
            if (!canvas) return;
            if (this.lineChart) this.lineChart.destroy();

            const isDark    = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            this.lineChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: incomeData,
                            backgroundColor: 'rgba(16,185,129,0.85)',
                            borderRadius: 4,
                            borderSkipped: false,
                        },
                        {
                            label: 'Expense',
                            data: expenseData,
                            backgroundColor: 'rgba(239,68,68,0.85)',
                            borderRadius: 4,
                            borderSkipped: false,
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
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.dataset.label}: ${window.formatCurrency ? window.formatCurrency(ctx.raw) : ctx.raw}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { size: 10 }, maxRotation: 0, maxTicksLimit: 12 },
                            grid: { display: false }
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

        // ── Pie chart ─────────────────────────────────────────────────────
        redrawPie() {
            const period = this.categoryPeriod;
            const now    = new Date();
            let filtered = this.allExpenses.filter(e => e.type === 'expense');

            if (period === 'month') {
                const monthStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
                filtered = filtered.filter(e => e.date && e.date.startsWith(monthStr));
            } else if (period === 'week') {
                const weekStart = new Date(now);
                weekStart.setDate(now.getDate() - now.getDay());
                weekStart.setHours(0, 0, 0, 0);
                filtered = filtered.filter(e => e.date && new Date(e.date + 'T00:00:00') >= weekStart);
            }

            const expByCategory = {};
            filtered.forEach(e => {
                expByCategory[e.category_id] = (expByCategory[e.category_id] || 0) + (parseFloat(e.amount) || 0);
            });

            this.stats.expenses_by_category = Object.keys(expByCategory).map(cid => {
                const cat = this.allCategories.find(c => c.id === cid) || { name: 'Unknown', color: '#ccc' };
                return { category_id: cid, category: cat, total: expByCategory[cid] };
            }).sort((a, b) => b.total - a.total);

            this.drawPieChart();
        },

        drawPieChart() {
            const canvas = document.getElementById('expensesPieChart');
            if (!canvas) return;
            if (this.pieChart) this.pieChart.destroy();

            const isDark    = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            // Fallback palette when categories have no color set
            const palette = [
                '#6366f1','#f97316','#10b981','#ef4444','#3b82f6',
                '#ec4899','#8b5cf6','#eab308','#06b6d4','#84cc16',
                '#f43f5e','#14b8a6',
            ];

            const labels   = this.stats.expenses_by_category.map(i => i.category.name);
            const data     = this.stats.expenses_by_category.map(i => i.total);
            const bgColors = this.stats.expenses_by_category.map((i, idx) =>
                i.category.color && i.category.color !== '#ccc' ? i.category.color : palette[idx % palette.length]
            );

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
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 10,
                                font: { size: 11 },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.label}: ${window.formatCurrency ? window.formatCurrency(ctx.raw) : ctx.raw}`
                            }
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
