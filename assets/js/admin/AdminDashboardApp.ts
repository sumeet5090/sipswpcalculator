import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface AdminChartData {
    volumeLabels: string[];
    volumeData: number[];
    referrerLabels: string[];
    referrerData: number[];
    studioTabLabels: string[];
    studioTabData: number[];
    strategyStarterLabels: string[];
    strategyStarterData: number[];
    deviceLabels: string[];
    deviceData: number[];
    goalModeLabels: string[];
    goalModeData: number[];
    stepUpDoughnutData: number[];
    durationLabels: string[];
    durationData: number[];
    ambitionLabels: string[];
    ambitionData: number[];
}

export class AdminDashboardApp {
    public static init(): void {
        const island = document.getElementById('admin-dashboard-data');
        if (!island || !island.textContent) {
            return;
        }

        let data: AdminChartData;
        try {
            data = JSON.parse(island.textContent);
        } catch (e) {
            console.error('Failed to parse admin dashboard data island:', e);
            return;
        }

        Chart.defaults.color = '#64748b'; // Slate 500
        Chart.defaults.borderColor = 'rgba(226, 232, 240, 0.8)'; // Slate 200
        Chart.defaults.font.family = 'Plus Jakarta Sans, Inter, system-ui, sans-serif';

        const commonPlugins = {
            legend: {
                labels: { color: '#475569', padding: 20, font: { weight: 600 } }
            }
        };

        // 1. Volume Line Chart
        const volumeEl = document.getElementById('volumeChart') as HTMLCanvasElement | null;
        if (volumeEl) {
            new Chart(volumeEl, {
                type: 'line',
                data: {
                    labels: data.volumeLabels || [],
                    datasets: [{
                        label: 'Calculations',
                        data: data.volumeData || [],
                        borderColor: '#059669', // Emerald 600
                        backgroundColor: 'rgba(5, 150, 105, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#059669'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: commonPlugins,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 2. Search & Referrer Distribution Doughnut
        const referrerEl = document.getElementById('referrerChart') as HTMLCanvasElement | null;
        if (referrerEl) {
            const refData = data.referrerData || [];
            const refTotal = refData.reduce((a, b) => a + b, 0);
            const hasRefData = refTotal > 0;

            new Chart(referrerEl, {
                type: 'doughnut',
                data: {
                    labels: hasRefData ? (data.referrerLabels || []) : ['Direct / None'],
                    datasets: [{
                        data: hasRefData ? refData : [1],
                        backgroundColor: hasRefData
                            ? ['#059669', '#0284c7', '#8b5cf6', '#f59e0b', '#ec4899', '#64748b']
                            : ['#e2e8f0'],
                        borderWidth: 0,
                        hoverOffset: hasRefData ? 4 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        ...commonPlugins,
                        tooltip: { enabled: hasRefData }
                    },
                    cutout: '65%'
                }
            });
        }

        // 3. Interactive Studio Modules Bar Chart
        const studioTabEl = document.getElementById('studioTabChart') as HTMLCanvasElement | null;
        if (studioTabEl) {
            new Chart(studioTabEl, {
                type: 'bar',
                data: {
                    labels: data.studioTabLabels || [],
                    datasets: [{
                        label: 'Views',
                        data: data.studioTabData || [],
                        backgroundColor: '#0d9488', // Teal 600
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: commonPlugins,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 4. Strategy Starter Presets Bar Chart
        const strategyStarterEl = document.getElementById('strategyStarterChart') as HTMLCanvasElement | null;
        if (strategyStarterEl) {
            new Chart(strategyStarterEl, {
                type: 'bar',
                data: {
                    labels: data.strategyStarterLabels || [],
                    datasets: [{
                        label: 'Preset Clicks',
                        data: data.strategyStarterData || [],
                        backgroundColor: '#6366f1', // Indigo 500
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: commonPlugins,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 5. Device Form Factor Doughnut Chart
        const deviceEl = document.getElementById('deviceChart') as HTMLCanvasElement | null;
        if (deviceEl) {
            const devData = data.deviceData || [];
            const devTotal = devData.reduce((a, b) => a + b, 0);
            const hasDeviceData = devTotal > 0;

            new Chart(deviceEl, {
                type: 'doughnut',
                data: {
                    labels: hasDeviceData ? (data.deviceLabels || []) : ['No Activity Recorded'],
                    datasets: [{
                        data: hasDeviceData ? devData : [1],
                        backgroundColor: hasDeviceData
                            ? ['#059669', '#6366f1', '#f59e0b']
                            : ['#e2e8f0'],
                        borderWidth: 0,
                        hoverOffset: hasDeviceData ? 4 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        ...commonPlugins,
                        tooltip: { enabled: hasDeviceData }
                    },
                    cutout: '65%'
                }
            });
        }

        // 6. Goal Mode Bar Chart
        const goalModeEl = document.getElementById('goalModeChart') as HTMLCanvasElement | null;
        if (goalModeEl) {
            new Chart(goalModeEl, {
                type: 'bar',
                data: {
                    labels: data.goalModeLabels || [],
                    datasets: [{
                        label: 'Calculations by Mode',
                        data: data.goalModeData || [],
                        backgroundColor: ['#10b981', '#6366f1'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: commonPlugins,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 7. SIP Type Doughnut Chart
        const stepUpEl = document.getElementById('stepUpChart') as HTMLCanvasElement | null;
        if (stepUpEl) {
            const stepUpData = data.stepUpDoughnutData || [];
            const stepUpTotal = stepUpData.reduce((a, b) => a + b, 0);
            const hasStepUpData = stepUpTotal > 0;

            new Chart(stepUpEl, {
                type: 'doughnut',
                data: {
                    labels: hasStepUpData ? ['Step-Up SIP', 'Flat SIP'] : ['No Activity Recorded'],
                    datasets: [{
                        data: hasStepUpData ? stepUpData : [1],
                        backgroundColor: hasStepUpData
                            ? ['#10b981', '#14b8a6']
                            : ['#e2e8f0'],
                        borderWidth: 0,
                        hoverOffset: hasStepUpData ? 4 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        ...commonPlugins,
                        tooltip: { enabled: hasStepUpData }
                    },
                    cutout: '70%'
                }
            });
        }

        // 8. Duration Histogram
        const durationEl = document.getElementById('durationChart') as HTMLCanvasElement | null;
        if (durationEl) {
            new Chart(durationEl, {
                type: 'bar',
                data: {
                    labels: data.durationLabels || [],
                    datasets: [{
                        label: 'Frequency',
                        data: data.durationData || [],
                        backgroundColor: '#047857', // Emerald 700
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: commonPlugins,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 9. Ambition Index Bar Chart
        const ambitionEl = document.getElementById('ambitionChart') as HTMLCanvasElement | null;
        if (ambitionEl) {
            new Chart(ambitionEl, {
                type: 'bar',
                data: {
                    labels: data.ambitionLabels || [],
                    datasets: [{
                        label: 'Calculations',
                        data: data.ambitionData || [],
                        backgroundColor: '#0f766e', // Teal 700
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: commonPlugins,
                    scales: {
                        x: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.8)' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AdminDashboardApp.init());
} else {
    AdminDashboardApp.init();
}
