/* assets/js/dashboard.js */

document.addEventListener('DOMContentLoaded', function() {
    let patientChartInstance = null;
    let serviceChartInstance = null;
    let queueChartInstance = null;

    // Fetch and populate stats metrics and charts
    function loadDashboardData() {
        $.ajax({
            url: '../ajax/dashboard_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    updateMetrics(response.metrics);
                    renderCharts(response.charts);
                }
            },
            error: function(xhr, status, error) {
                console.error("Failed to load dashboard metrics:", error);
            }
        });
    }

    // Update summary metrics counters
    function updateMetrics(metrics) {
        // Direct value setting - animate count if possible
        animateCountValue('total-patients-val', metrics.total_patients);
        animateCountValue('today-appointments-val', metrics.today_appointments);
        animateCountValue('today-queue-val', metrics.today_queue);
        animateCountValue('active-users-val', metrics.active_users);
    }

    // Number counting transition effect
    function animateCountValue(id, endVal) {
        const obj = document.getElementById(id);
        if (!obj) return;
        
        let startVal = parseInt(obj.innerHTML) || 0;
        if (startVal === endVal) {
            obj.innerHTML = endVal;
            return;
        }

        const duration = 800; // ms
        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            obj.innerHTML = Math.floor(progress * (endVal - startVal) + startVal);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = endVal;
            }
        }
        window.requestAnimationFrame(step);
    }

    // Render Chart.js instances
    function renderCharts(chartData) {
        const themeColors = {
            primary: '#0D7377',
            primaryLight: '#14A3A8',
            accent: '#4A90D9',
            success: '#28A745',
            warning: '#FFC107',
            danger: '#DC3545',
            darkTeal: '#095B5E'
        };

        // 1. Patient Growth Line Chart
        const patientCtx = document.getElementById('patientGrowthChart');
        if (patientCtx) {
            if (patientChartInstance) patientChartInstance.destroy();
            
            patientChartInstance = new Chart(patientCtx, {
                type: 'line',
                data: {
                    labels: chartData.patient_growth.labels,
                    datasets: [{
                        label: 'Registrations',
                        data: chartData.patient_growth.data,
                        borderColor: themeColors.primary,
                        backgroundColor: 'rgba(20, 163, 168, 0.08)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: themeColors.primaryLight,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5] }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Appointments by Service Doughnut Chart
        const serviceCtx = document.getElementById('appointmentsServiceChart');
        if (serviceCtx) {
            if (serviceChartInstance) serviceChartInstance.destroy();

            serviceChartInstance = new Chart(serviceCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.appointments_by_service.labels,
                    datasets: [{
                        data: chartData.appointments_by_service.data,
                        backgroundColor: [
                            themeColors.primary,
                            themeColors.accent,
                            themeColors.success,
                            themeColors.warning,
                            themeColors.primaryLight
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { size: 12 }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // 3. Daily Queue Volume Bar Chart
        const queueCtx = document.getElementById('queueVolumeChart');
        if (queueCtx) {
            if (queueChartInstance) queueChartInstance.destroy();

            queueChartInstance = new Chart(queueCtx, {
                type: 'bar',
                data: {
                    labels: chartData.daily_queue.labels,
                    data: chartData.daily_queue.data, // Wait: standard is labels & datasets
                    datasets: [{
                        label: 'Patients Queued',
                        data: chartData.daily_queue.data,
                        backgroundColor: themeColors.accent,
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5] }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    }

    // Load active online users panel
    function loadActiveUsers() {
        $.ajax({
            url: '../ajax/active_users.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const listContainer = $('#online-users-list');
                    listContainer.empty();
                    
                    if (response.users && response.users.length > 0) {
                        response.users.forEach(function(user) {
                            const name = user.first_name + ' ' + user.last_name;
                            const avatar = (user.first_name[0] + user.last_name[0]).toUpperCase();
                            const roleBadge = user.role === 'admin' ? 'badge bg-danger' : 'badge bg-secondary';
                            
                            listContainer.append(`
                                <li class="online-users-item">
                                    <div class="online-user-details">
                                        <div class="online-user-avatar">${avatar}</div>
                                        <div>
                                            <div class="online-user-name">${name}</div>
                                            <div class="online-user-role"><span class="${roleBadge}">${user.role}</span></div>
                                        </div>
                                    </div>
                                    <div class="online-status-indicator" title="Online now"></div>
                                </li>
                            `);
                        });
                    } else {
                        listContainer.append('<li class="py-2 text-center text-muted">No other active sessions.</li>');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Failed to load online users:", error);
            }
        });
    }

    // Initial triggers
    loadDashboardData();
    loadActiveUsers();

    // Auto-refresh stats and active users every 30 seconds for live feel
    setInterval(loadDashboardData, 30000);
    setInterval(loadActiveUsers, 30000);
});
