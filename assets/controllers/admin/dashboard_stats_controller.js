import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

// Enregistrer tous les composants Chart.js
Chart.register(...registerables);

export default class extends Controller {
    static targets = ['buybacksChart', 'repairsChart'];

    connect() {
        console.log('✅ Dashboard stats controller connected');

        // Initialiser les graphiques
        if (this.hasBuybacksChartTarget) {
            this.initBuybacksChart();
        }

        if (this.hasRepairsChartTarget) {
            this.initRepairsChart();
        }
    }

    disconnect() {
        // Détruire les graphiques lors de la déconnexion
        if (this.buybacksChartInstance) {
            this.buybacksChartInstance.destroy();
        }
        if (this.repairsChartInstance) {
            this.repairsChartInstance.destroy();
        }
    }

    /**
     * Initialise le graphique Bar des rachats par statut
     */
    initBuybacksChart() {
        const canvas = this.buybacksChartTarget;
        const labels = JSON.parse(canvas.dataset.labels);
        const values = JSON.parse(canvas.dataset.values);

        // Couleurs pour chaque statut
        const colors = [
            'rgba(243, 156, 18, 0.8)',  // En attente - Orange
            'rgba(52, 152, 219, 0.8)',   // Validé - Bleu
            'rgba(155, 89, 182, 0.8)',   // RDV planifié - Violet
            'rgba(39, 174, 96, 0.8)',    // Collecté - Vert foncé
            'rgba(22, 198, 105, 0.8)',   // Payé - Vert Recyclum
        ];

        const borderColors = [
            '#f39c12',
            '#3498db',
            '#9b59b6',
            '#27ae60',
            '#16C669',
        ];

        this.buybacksChartInstance = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nombre de rachats',
                    data: values,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.8,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12,
                                family: "'Inter', sans-serif"
                            },
                            color: '#6C7783'
                        },
                        grid: {
                            color: 'rgba(230, 237, 242, 0.6)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Inter', sans-serif",
                                weight: '600'
                            },
                            color: '#0F1418'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 20, 24, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        titleFont: {
                            size: 14,
                            weight: '700'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} rachat${context.parsed.y > 1 ? 's' : ''}`;
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    /**
     * Initialise le graphique Doughnut des réparations par statut
     */
    initRepairsChart() {
        const canvas = this.repairsChartTarget;
        const labels = JSON.parse(canvas.dataset.labels);
        const values = JSON.parse(canvas.dataset.values);

        // Couleurs pour chaque statut
        const colors = [
            '#f39c12',  // En attente - Orange
            '#3498db',  // Contacté - Bleu
            '#9b59b6',  // Planifié - Violet
            '#27ae60',  // Terminé - Vert
        ];

        this.repairsChartInstance = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.8,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 13,
                                family: "'Inter', sans-serif",
                                weight: '600'
                            },
                            color: '#0F1418',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 20, 24, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        titleFont: {
                            size: 14,
                            weight: '700'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} réparation${value > 1 ? 's' : ''} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
}
