<?php
session_start();
require_once '../config/config.php'; // Database connection
include '../includes/sidevar.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user status is pending - redirect to pending dashboard
if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
    header("Location: pending_user_dashboard.php");
    exit();
}

$role = $_SESSION['role_type']; // Get user role
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEUST Registrar Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --neust-blue: #0056b3;
            --neust-yellow: #FFD700;
            --neust-green: #28a745;
            --neust-red: #dc3545;
            --neust-purple: #6f42c1;
            --neust-orange: #fd7e14;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--neust-blue), #003366);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        .sidebar-brand h4 {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        .topbar {
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 10px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card .card-icon {
            font-size: 2.5rem;
            opacity: 0.7;
            margin-bottom: 15px;
        }
        
        .stat-card .card-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        .stat-card .card-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            color: white;
            border-radius: 10px;
            overflow: hidden;
            border: none;
        }
        
        .welcome-card .card-body {
            padding: 2rem;
        }
        
        .welcome-card h2 {
            font-weight: 600;
        }
        
        .welcome-card .role-badge {
            background-color: var(--neust-yellow);
            color: #333;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .badge-completed {
    background-color: var(--neust-purple);
    color: white;
}

.badge-ready {
    background-color: var(--neust-orange);
    color: white;
}

.badge-approved {
    background-color: var(--neust-green);
    color: white;
}

.badge-rejected {
    background-color: var(--neust-red);
    color: white;
}

.badge-pending {
    background-color: var(--neust-yellow);
    color: #333; /* Dark text for better contrast on yellow */
}
        
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <h4 class="mb-0">Dashboard Overview</h4>
            <div class="user-profile">
                <img src="../assets/avatar.jpg" alt="User Avatar">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst($role)); ?></small>
                </div>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="card-body">
                        <h2>Welcome back!</h2>
                        <p class="mb-2">You're logged in as <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span></p>
                        <p class="mb-0">Here's what's happening with your requests today.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row g-3">
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon text-primary">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h5 class="card-title">Total Requests</h5>
                        <p class="card-value text-primary" id="totalRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h5 class="card-title">Pending</h5>
                        <p class="card-value text-warning" id="pendingRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h5 class="card-title">Approved</h5>
                        <p class="card-value text-success" id="approvedRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <h5 class="card-title">Rejected</h5>
                        <p class="card-value text-danger" id="rejectedRequests">0</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon" style="color: var(--neust-orange);">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h5 class="card-title">Ready to Pickup</h5>
                        <p class="card-value" style="color: var(--neust-orange);" id="readyRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="stat-card bg-white">
                    <div class="card-body text-center">
                        <div class="card-icon text-purple">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h5 class="card-title">Completed</h5>
                        <p class="card-value" style="color: var(--neust-purple);" id="completedRequests">0</p>
                    </div>
                </div>
            </div>
        </div>




        <!-- Add this after the Dashboard Stats row, before the Request Status Chart -->
<!-- Report Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Generate Report</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="bi bi-file-earmark-bar-graph"></i> Generate
                </button>
            </div>
            <div class="card-body" id="reportResult" style="display:none;">
                <!-- Report results will be shown here -->
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="reportForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reportModalLabel">Generate Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="reportType" class="form-label">Report Type</label>
            <select class="form-select" id="reportType" name="reportType" required>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>
          <div class="mb-3" id="datePickerContainer">
            <label for="reportDate" class="form-label">Select Date</label>
            <input type="date" class="form-control" id="reportDate" name="reportDate" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Generate</button>
        </div>
      </div>
    </form>
  </div>
</div>









        <!-- Request Status Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Request Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="requestsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Student Name</th>
                                        <th>Document Type</th>
                                        <th>Status</th>
                                        <th>Date Requested</th>
                                    </tr>
                                </thead>
                                <tbody id="recentActivity">
                                    <!-- Will be populated by JavaScript -->
                                    <tr>
                                        <td colspan="5" class="text-center">Loading recent activity...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    
    <!-- Dashboard Scripts -->
    <script>
    let requestsChart = null;

    function fetchDashboardStats() {
        $.getJSON("../api/dashboard_api.php", function(data) {
            if (data.status === "success") {
                // Update stat cards
                $("#totalRequests").text(data.total);
                $("#pendingRequests").text(data.pending);
                $("#approvedRequests").text(data.approved);
                $("#rejectedRequests").text(data.rejected);
                $("#readyRequests").text(data.ready);
                $("#completedRequests").text(data.completed);
               

                // Update recent activity
                // Update recent activity
// Update recent activity
if (data.recent && data.recent.length > 0) {
    let html = '';
    data.recent.forEach(activity => {
        let statusClass = '';
        let displayStatus = activity.requestStatus;
        
        switch (activity.requestStatus.toLowerCase()) {
            case 'approved': 
                statusClass = 'badge-approved'; 
                break;
            case 'rejected': 
                statusClass = 'badge-rejected'; 
                break;
            case 'pending': 
                statusClass = 'badge-pending'; 
                break;
            case 'ready to pickup': 
            case 'ready': 
                statusClass = 'badge-ready'; 
                displayStatus = 'Ready to Pickup';
                break;
            case 'completed': 
                statusClass = 'badge-completed'; 
                break;
            default:
                statusClass = 'bg-secondary';
        }

        html += `
            <tr>
                <td>${activity.requestID}</td>
                <td>${activity.firstname} ${activity.lastname}</td>
                <td>${activity.documentName}</td>
                <td><span class="badge ${statusClass}">${displayStatus}</span></td>
                <td>${activity.dateRequest}</td>
            </tr>
        `;
    });
    $("#recentActivity").html(html);
} else {
    $("#recentActivity").html('<tr><td colspan="5" class="text-center">No recent activity found</td></tr>');
}

                // Update chart
                updateChart(data.total, data.pending, data.approved, data.rejected,  data.ready, data.completed);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching dashboard stats:", textStatus, errorThrown);
        });
    }

    function initializeChart() {
        const ctx = document.getElementById('requestsChart').getContext('2d');

        requestsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total', 'Pending', 'Approved', 'Rejected','Ready to Pickup', 'Completed'],
                datasets: [{
                    label: 'Request Status',
                    data: [0, 0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#0056b3',
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#fd7e14',
                        '#6f42c1'
                    ],
                    borderColor: [
                        '#0056b3',
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#fd7e14',
                        '#6f42c1'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                if (value % 1 === 0) return value;
                            }
                        }
                    }
                }
            }
        });
    }

    function updateChart(total, pending, approved, rejected, ready, completed) {
        if (!requestsChart) {
            initializeChart();
        }

        requestsChart.data.datasets[0].data = [total, pending, approved, rejected, ready, completed];
        requestsChart.update();
    }

    $(document).ready(function() {
        initializeChart();
        fetchDashboardStats();
        setInterval(fetchDashboardStats, 10000); // Refresh every 10s
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // Pusher Configuration for Real-time Notifications
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var pusher = new Pusher('ed1a40e7a469cee7f86c', {
        cluster: 'ap1'
    });

    var channel = pusher.subscribe('admin-channel');
    channel.bind('new-request', function(data) {
        // Show notification for new request
        if (data.type === 'new_request') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 400px;';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-bell-fill me-2" style="font-size: 1.2rem;"></i>
                    <div class="flex-grow-1">
                        <strong>New Document Request</strong><br>
                        <small>${data.message}</small><br>
                        <small class="text-muted">Student: ${data.studentName}</small><br>
                        <small class="text-muted">Document: ${data.documentName}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Play notification sound (optional)
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
            audio.play().catch(e => console.log('Audio play failed:', e));
            
            // Auto remove after 8 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 8000);
            
            // Refresh dashboard stats immediately
            fetchDashboardStats();
        }
    });

// ...existing code...

$('#reportType').on('change', function() {
    const type = $(this).val();
    if (type === 'monthly') {
        $('#reportDate').attr('type', 'month');
        $('#reportDate').val('');
    } else if (type === 'weekly') {
        $('#reportDate').attr('type', 'week');
        $('#reportDate').val('');
    } else {
        $('#reportDate').attr('type', 'date');
        $('#reportDate').val('');
    }
});

// Set initial type on page load
$(document).ready(function() {
    $('#reportType').trigger('change');
    // ...existing code...
});

// Handle report form submission
$('#reportForm').on('submit', function(e) {
    e.preventDefault();
    const type = $('#reportType').val();
    const date = $('#reportDate').val();

    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-hourglass-split"></i> Generating...').prop('disabled', true);

    // Try AJAX first for better error handling
    $.ajax({
        url: '../api/dashboard_report_api.php',
        type: 'POST',
        data: {
            type: type,
            date: date
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(data, status, xhr) {
            // Check if response is actually CSV
            const contentType = xhr.getResponseHeader('Content-Type');
            if (contentType && contentType.includes('text/csv')) {
                // Create download link
                const blob = new Blob([data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `requests_report_${type}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                // Fallback to form submission
                submitFormDirectly(type, date);
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX failed, trying alternative method...');
            // Try alternative API first, then direct form submission
            tryAlternativeAPI(type, date);
        },
        complete: function() {
            // Reset button state
            submitBtn.html(originalText).prop('disabled', false);
            $('#reportModal').modal('hide');
            $('#reportResult').hide().html('');
        }
    });
});

// Try alternative API method
function tryAlternativeAPI(type, date) {
    $.ajax({
        url: '../api/dashboard_report_api_alternative.php',
        type: 'POST',
        data: {
            type: type,
            date: date
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(data, status, xhr) {
            const contentType = xhr.getResponseHeader('Content-Type');
            if (contentType && contentType.includes('text/csv')) {
                const blob = new Blob([data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `requests_report_${type}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                submitFormDirectly(type, date);
            }
        },
        error: function() {
            console.log('Alternative API failed, trying direct form submission...');
            submitFormDirectly(type, date);
        }
    });
}

// Fallback function for direct form submission
function submitFormDirectly(type, date) {
    const form = $('<form>', {
        method: 'POST',
        action: '../api/dashboard_report_api.php',
        target: '_blank'
    }).append(
        $('<input>', { type: 'hidden', name: 'type', value: type }),
        $('<input>', { type: 'hidden', name: 'date', value: date })
    );
    
    $('body').append(form);
    form.submit();
    form.remove();
}
</script>
</body>
</html>