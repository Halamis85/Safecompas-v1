import {Chart, registerables} from 'chart.js';

Chart.register(...registerables);

function statik() {


    console.log('statistika');

    const ctx1 = document.getElementById('statistikaGraf')?.getContext('2d');
    console.log('statistika');
    const ctx2 = document.getElementById('grafVydaje')?.getContext('2d');
    const aktualniRok = new Date().getFullYear();

    // PIE graf – statistiky produktů
    if (ctx1) {
        fetch(`/statistiky`)
            .then(response => response.json())
            .then(data => {
                const labels = data.map(item => item.produkt);
                const values = data.map(item => item.pocet);

                new Chart(ctx1, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(203,102,6,0.7)',
                                'rgb(18,30,63)',
                                'rgba(255,255,255,0.49)',
                                'rgba(246,6,6,0.37)',
                                'rgba(255,59,59,0.14)',
                                'rgba(143,28,169,0.7)',
                                'rgba(10,83,105,0.7)'

                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(203,102,6, 1)',
                                'rgb(18,30,63, 1)',
                                'rgba(255,255,255, 1)',
                                'rgba(246,6,6, 1)',
                                'rgba(255,59,59, 1)',
                                'rgba(143,28,169, 1)',
                                'rgba(10,83,105, 1)'
                            ],
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position:"bottom",
                                labels: {
                                    color: 'rgba(255,255,255,0.71)',
                                    font: {
                                        size: 14,
                                        weight: '400'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.17)',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 14
                                },
                                padding: 0,
                                cornerRadius: 5,
                                caretSize: 8
                            }
                        },
                        layout: {
                            padding: {
                                top: 0,
                                left: 10,
                                right: 10,
                            }
                        },
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        }
                    }
                });
            })
            .catch(error => console.error('Chyba při načítání statistik:', error));
    }

    // BAR graf – výdaje za měsíc
    if (ctx2) {
        fetch(`/statistiky/vydaje`)
            .then(response => response.json())
            .then(data => {
                const hodnoty = data.map(item => item.vydaje); // např. ['Leden', 'Únor',...]
                const mesice = data.map(item => item.mesic);

                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: mesice,
                        datasets: [{
                            label: 'Výdaje (Kč)',
                            data: hodnoty,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(203,102,6,0.7)',
                                'rgb(18,30,63,0.7)',
                                'rgba(255,255,255,0.49)',
                                'rgba(246,6,6,0.37)',
                                'rgba(255,59,59,0.14)',
                                'rgba(143,28,169,0.7)',
                                'rgba(10,83,105,0.7)',
                                'rgba(240,250,15,0.7)',
                                'rgba(197,5,139,0.7)',
                            ],
                            borderRadius: 3,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        layout: {
                            padding: {
                                left: 0,
                                right: 20,
                                top: 0,
                                bottom: 0
                            }
                        },
                        plugins: {
                            tooltip: {
                                enabled: false   //  Zde vypínáš tooltip
                            },
                            legend: {
                                display: false   //  Zde vypínáš legendu
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: 8
                                    }
                                },
                                border: {
                                    color: 'rgb(255,255,255)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 8
                                    }
                                },
                                border: {
                                    color: 'rgb(255,255,255)'
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Chyba při načítání výdajů:', error));
    }
}
document.addEventListener('DOMContentLoaded', statik);
export { statik };
