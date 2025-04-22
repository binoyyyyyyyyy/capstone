<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type']; // Get user role
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | NEUST Registrar</title>
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--neust-blue);
            color: white;
            border-bottom: none;
            padding: 15px;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 86, 179, 0.05);
        }
        
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0 3px;
        }
        
        .page-title {
            color: var(--neust-blue);
            font-weight: 600;
            margin-bottom: 0;
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
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <h4 class="page-title">Manage Document Requests</h4>
            <div class="user-profile">
                <img src="../assets/avatar.jpg" alt="User Avatar">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst($role)); ?></small>
                </div>
            </div>
        </div>

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mb-4">
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div>
                <a href="request_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Request
                </a>
            </div>
        </div>


        <!-- Filter & Search Controls -->
<div class="row mb-3">
    <div class="col-md-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or request code...">
    </div>
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Ready to Pickup">Ready to Pickup</option>
            <option value="Rejected">Rejected</option>
            <option value="Completed">Completed</option>
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterDocument" class="form-select">
            <option value="">All Documents</option>
            <!-- Options will be filled dynamically -->
        </select>
    </div>
</div>


        <!-- Requests Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Request Code</th>
                                <th>Student Name</th>
                                <th>Document</th>
                                <th>Date Requested</th>
                                <th>Date Pickup</th>
                                <th>Date Release</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestTableBody">
                            <!-- Loading placeholder -->
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0">Loading requests...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../backend/delete_request.php" id="deleteForm">
                <input type="hidden" name="requestID" id="modalRequestIDInput">
                <div class="d-none" id="debugRequestID"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
    <div class="mb-3">
        <label for="password" class="form-label">Enter your password to confirm:</label>
        <input type="password" class="form-control" name="password" id="password" required>
        <div id="passwordError" class="text-danger mt-2 d-none"></div>
    </div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Request</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function () {
            fetchRequests();
            setInterval(fetchRequests, 30000);

            // Handle modal show event
            $('#confirmDeleteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var requestID = button.data('request-id');
                console.log("Captured request ID:", requestID);
                $('#modalRequestIDInput').val(requestID);
                $('#debugRequestID').text(requestID);
                
                // Clear password field and any previous error messages
                $('#password').val('');
                $('#passwordError').text('').addClass('d-none');
            });



// Global variable to store current requests
let allRequests = [];

function fetchRequests() {
    $.getJSON("../api/request_api.php", function(data) {
        if (data.status === "success") {
            allRequests = data.data; // Store data globally
            populateDocumentDropdown(allRequests);
            renderTable(allRequests);
        }
    }).fail(function() {
        $("#requestTableBody").html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">Failed to load requests. Please try again.</p>
                </td>
            </tr>
        `);
    });
}

function renderTable(requests) {
    const tableBody = $("#requestTableBody");
    tableBody.empty();

    if (requests.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No matching requests found</p>
                </td>
            </tr>
        `);
        return;
    }

    requests.forEach(request => {
        let formattedDateRequest = new Date(request.dateRequest).toLocaleString();
        let formattedDatePickup = request.datePickUp ? new Date(request.datePickUp).toLocaleDateString() : "N/A";
        let formattedDateRelease = request.dateRelease ? new Date(request.dateRelease).toLocaleString() : "N/A";

        let statusClass = getStatusClass(request.requestStatus);
        let statusIcon = request.requestStatus.toLowerCase() === "pending" ? "hourglass" :
                         (request.requestStatus.toLowerCase() === "approved" ? "check-circle" :
                         (request.requestStatus.toLowerCase() === "ready to pick" || request.requestStatus.toLowerCase() === "ready" ? "box-seam" :
                         (request.requestStatus.toLowerCase() === "rejected" ? "x-circle" : "check-circle-fill")));

        let row = `
        <tr>
            <td class="fw-bold">${request.requestCode}</td>
            <td>${request.firstname} ${request.lastname}</td>
            <td>${request.documentName}</td>
            <td>${formattedDateRequest}</td>
            <td>${formattedDatePickup}</td>
            <td>${formattedDateRelease}</td>
            <td>
                <span class="badge ${statusClass === 'completed' ? 'badge-completed' : 
                                      statusClass === 'ready' ? 'badge-ready' : 
                                      statusClass === 'approved' ? 'badge-approved' :
                                      'bg-' + statusClass}">
                    <i class="bi bi-${statusIcon} me-1"></i>
                    ${request.requestStatus}
                </span>
            </td>
            <td>
                <a href="update_request.php?id=${request.requestID}" class="btn btn-sm btn-outline-warning action-btn" title="Update">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="view_requests.php?id=${request.requestID}" class="btn btn-sm btn-outline-info action-btn" title="View">
                    <i class="bi bi-eye"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger action-btn" 
                        data-bs-toggle="modal" 
                        data-bs-target="#confirmDeleteModal" 
                        data-request-id="${request.requestID}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        `;
        tableBody.append(row);
    });
}

function applyFilters() {
    const searchValue = $('#searchInput').val().toLowerCase();
    const selectedStatus = $('#filterStatus').val().toLowerCase();
    const selectedDocument = $('#filterDocument').val().toLowerCase();

    const filtered = allRequests.filter(req => {
        const fullName = (req.firstname + ' ' + req.lastname).toLowerCase();
        const codeMatch = req.requestCode.toLowerCase().includes(searchValue);
        const nameMatch = fullName.includes(searchValue);

        const statusMatch = selectedStatus === "" || req.requestStatus.toLowerCase() === selectedStatus;
        const docMatch = selectedDocument === "" || req.documentName.toLowerCase() === selectedDocument;

        return (codeMatch || nameMatch) && statusMatch && docMatch;
    });

    renderTable(filtered);
}

function populateDocumentDropdown(requests) {
    const documents = [...new Set(requests.map(req => req.documentName))];
    const $dropdown = $('#filterDocument');
    $dropdown.find('option:not(:first)').remove(); // Clear previous
    documents.forEach(doc => {
        $dropdown.append(`<option value="${doc}">${doc}</option>`);
    });
}

// Bind filter events
$('#searchInput, #filterStatus, #filterDocument').on('input change', applyFilters);




            // Handle form submission
// Handle form submission
$('#deleteForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    console.log("Form data being submitted:", formData);
    
    $.ajax({
        type: 'POST',
        url: $(this).attr('action'),
        data: formData,
        dataType: 'json', // Expect JSON response
        success: function(response) {
            if (response.success) {
                // Success case
                $('#confirmDeleteModal').modal('hide');
                fetchRequests(); // Refresh the table
                
                // Show success message
                $('.main-content').prepend(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        ${response.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            } else if (response.error) {
                // Error case - show in modal
                $('#passwordError').text(response.error).removeClass('d-none');
                $('#password').val('').focus();
            }
        },
        error: function(xhr, status, error) {
            console.error("Delete error:", error);
            $('#passwordError').text('An error occurred. Please try again.').removeClass('d-none');
        }
    });
});

            // Clear modal when hidden
            $('#confirmDeleteModal').on('hidden.bs.modal', function () {
                $('#password').val('');
                $('#passwordError').text('').addClass('d-none');
            });
        });

        function fetchRequests() {
            $.getJSON("../api/request_api.php", function(data) {
                if (data.status === "success") {
                    let tableBody = $("#requestTableBody");
                    tableBody.empty();

                    if (data.data.length === 0) {
                        tableBody.append(`
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No requests found</p>
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    data.data.forEach(request => {
                        let formattedDateRequest = new Date(request.dateRequest).toLocaleString();
                        let formattedDatePickup = request.datePickUp ? new Date(request.datePickUp).toLocaleDateString() : "N/A";
                        let formattedDateRelease = request.dateRelease ? new Date(request.dateRelease).toLocaleString() : "N/A";
                        
                        let statusClass = getStatusClass(request.requestStatus);
                        let statusIcon = request.requestStatus.toLowerCase() === "pending" ? "hourglass" : 
                                 (request.requestStatus.toLowerCase() === "approved" ? "check-circle" : 
                                 (request.requestStatus.toLowerCase() === "ready to pick" || request.requestStatus.toLowerCase() === "ready" ? "box-seam" :
                                 (request.requestStatus.toLowerCase() === "rejected" ? "x-circle" : "check-circle-fill")));

                        let row = `
                        <tr>
                            <td class="fw-bold">${request.requestCode}</td>
                            <td>${request.firstname} ${request.lastname}</td>
                            <td>${request.documentName}</td>
                            <td>${formattedDateRequest}</td>
                            <td>${formattedDatePickup}</td>
                            <td>${formattedDateRelease}</td>
                            <td>
                                <span class="badge ${statusClass === 'completed' ? 'badge-completed' : 
                                                  statusClass === 'ready' ? 'badge-ready' : 
                                                  statusClass === 'approved' ? 'badge-approved' :
                                                  'bg-' + statusClass}">
                                    <i class="bi bi-${statusIcon} me-1"></i>
                                    ${request.requestStatus}
                                </span>
                            </td>
                            <td>
                                <a href="update_request.php?id=${request.requestID}" class="btn btn-sm btn-outline-warning action-btn" title="Update">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="view_requests.php?id=${request.requestID}" class="btn btn-sm btn-outline-info action-btn" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger action-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmDeleteModal" 
                                        data-request-id="${request.requestID}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        `;
                        tableBody.append(row);
                    });
                }
            }).fail(function() {
                $("#requestTableBody").html(`
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">Failed to load requests. Please try again.</p>
                        </td>
                    </tr>
                `);
                console.error("Error fetching requests.");
            });
        }

        function getStatusClass(status) {
            switch(status.toLowerCase()) {
                case "pending": return "warning";
                case "approved": return "approved";
                case "ready to pickup": 
                case "ready": return "ready";
                case "rejected": return "danger";
                case "completed": return "completed";
                default: return "secondary";
            }
        }
    </script>
</body>
</html>