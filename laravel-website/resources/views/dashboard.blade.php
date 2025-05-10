@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="p-6 pt-8 bg-white rounded-xl shadow-md">
        <!-- Welcome Message -->
        <div class="bg-white rounded-lg p-8 pt-10 flex items-center justify-between border border-slate-200">
            <div>
                <h1 class="text-3xl font-semibold text-gray-800">Welcome back, {{ Auth::user()->name }} 👋</h1>
                <p class="text-slate-600 mt-1">Here’s what’s happening with your patent system today.</p>
            </div>
            {{-- <div class="hidden md:block">
                <img src="{{ asset('images/patent_image.jpg') }}" alt="Dashboard Illustration"
                    class="h-28 rounded-lg shadow-sm">
            </div> --}}
        </div>

        <!-- Statistics and Chart Section -->
        <div class="flex flex-col lg:flex-row gap-4 mt-4">
            <!-- Statistic Cards -->
            <div id="patent-statistics" class="flex flex-col gap-4 w-full lg:w-1/3">
                <div id="stats-loading"
                    class="flex items-center justify-center py-12 bg-white border border-gray-200 rounded-lg shadow-sm h-full">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="text-slate-700 text-sm">Loading statistics...</span>
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 w-full lg:w-2/3 border border-gray-200 shadow-sm">
                <h2 class="text-sm text-slate-500 uppercase tracking-wide mb-4">Patent Type Distribution</h2>

                <div class="relative h-full w-full flex justify-center items-center md:pb-8">
                    <!-- Optional: loading overlay -->
                    <div id="pieChartLoading" class="absolute inset-0 flex justify-center items-center z-10 bg-white/80">
                        <svg class="animate-spin h-6 w-6 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span class="text-slate-700 text-sm">Loading chart...</span>
                    </div>

                    <!-- Wrapper for the canvas -->
                    <div
                        class="relative w-[80%] h-[80%] max-h-[16rem] md:max-h-[20rem] flex justify-center items-center p-4 md:p-0">
                        <canvas id="patentPieChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Chart -->
        <div class="mt-4 bg-white rounded-lg p-4 border border-gray-200 shadow-sm w-full">
            <h2 class="text-sm text-slate-500 uppercase tracking-wide mb-4">Patent Trend Per Year</h2>
            <div class="relative h-72 w-full">
                <div id="lineChartLoading" class="absolute inset-0 flex justify-center items-center z-10 bg-white/80">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="text-slate-700 text-sm">Loading chart...</span>
                </div>
                <canvas id="yearlyLineChart" class="absolute inset-0 w-full h-full"></canvas>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const url = "{{ route('dashboard.statistics') }}";
            const yearlyUrl = "{{ route('dashboard.statistics.yearly') }}";
            const pieChartLoading = document.getElementById('pieChartLoading');
            const lineChartLoading = document.getElementById('lineChartLoading');
            const loadingText = document.getElementById('stats-loading');

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('patent-statistics');
                    loadingText.remove();
                    pieChartLoading.remove();

                    const createCard = (title, value, colorHex) => {
                        const div = document.createElement('div');
                        div.className =
                            "flex items-center bg-white border border-gray-200 rounded-lg shadow-sm p-4";

                        const colorIndicator = document.createElement('div');
                        colorIndicator.className = 'w-2 h-12 md:h-full rounded-full';
                        colorIndicator.style.backgroundColor = colorHex;

                        const content = document.createElement('div');
                        content.className = 'ml-4';
                        content.innerHTML = `
                            <div class="text-sm text-slate-500 uppercase tracking-wide">${title}</div>
                            <div class="mt-1 text-2xl font-bold text-slate-900">${value.toLocaleString()}</div>
                        `;

                        div.appendChild(colorIndicator);
                        div.appendChild(content);
                        return div;
                    };

                    const labels = [];
                    const counts = [];
                    const bgColors = [];

                    const colorMap = [
                        '#00609C',
                        '#FFDD00',
                        '#4C51BF',
                        '#38B2AC',
                        '#F56565',
                        '#D53F8C',
                    ];

                    container.appendChild(createCard('Total Patents', data.total, '#1f2937'));

                    data.by_patent_type.forEach((item, index) => {
                        const title = item.type.charAt(0).toUpperCase() + item.type.slice(1);
                        const colorHex = colorMap[index % colorMap.length];

                        container.appendChild(createCard(title, item.count, colorHex));

                        labels.push(title);
                        counts.push(item.count);
                        bgColors.push(colorHex);
                    });

                    const ctx = document.getElementById('patentPieChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: counts,
                                backgroundColor: bgColors,
                                borderColor: '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 16,
                                        font: {
                                            size: 14,
                                            family: 'Inter, sans-serif'
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Failed to fetch statistics:', error);
                    const container = document.getElementById('patent-statistics');
                    container.innerHTML = '<p class="text-red-600">Failed to load statistics.</p>';
                });

            // Fetch and draw yearly chart
            fetch(yearlyUrl)
                .then(response => response.json())
                .then(data => {
                    lineChartLoading.remove();

                    const years = data.map(entry => entry.year);
                    const counts = data.map(entry => entry.count);

                    const ctx = document.getElementById('yearlyLineChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: years,
                            datasets: [{
                                label: 'Number of Patents',
                                data: counts,
                                borderColor: '#00609C',
                                backgroundColor: 'rgba(37, 99, 235, 0.05)', // Light fill
                                fill: true,
                                tension: 0.4, // Smooth curve
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 5000
                                    },
                                    grid: {
                                        borderDash: [2, 2]
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Failed to load yearly data:', error);
                });
        });
    </script>
@endsection
