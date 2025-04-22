
<?php 
include '../includes/index_nav.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Request Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #ffc107;
            --accent-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 1rem;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 15px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link:focus {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .main-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 2.5rem;
            position: relative;
            display: inline-block;
        }
        
        .main-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--secondary-color);
            border-radius: 2px;
        }
        
        .choice-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            height: 100%;
            background: white;
        }
        
        .choice-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 2.5rem;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #002244;
            border-color: #002244;
        }
        
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: var(--secondary-color);
            color: var(--dark-color) !important;
            transform: translateY(-3px);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #005588 100%);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 20px 20px;
        }
        
        .hero-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 0;
            }
            
            .main-title {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="hero-title display-4 fw-bold">NEUST Request Management System</h1>
            <p class="hero-subtitle lead">Streamlining university processes through digital request management</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="request_form.php" class="btn btn-warning btn-lg px-4"><i class="fas fa-plus-circle me-2"></i>Create Request</a>
                <a href="my_request.php" class="btn btn-outline-light btn-lg px-4"><i class="fas fa-list me-2"></i>View Requests</a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="text-center mb-5">
            <h2 class="main-title">Request Services</h2>
            <p class="text-muted">Select the service you need from our portal</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a href="../admin/request_form.php" class="text-decoration-none">
                    <div class="choice-card h-100">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3 class="card-title">Create New Request</h3>
                            <p class="text-muted">Submit a new request for documents, services, or other university needs.</p>
                            <button class="btn btn-primary mt-3">Get Started</button>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="../admin/my_request.php" class="text-decoration-none">
                    <div class="choice-card h-100">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3 class="card-title">View My Requests</h3>
                            <p class="text-muted">Track the status of your submitted requests and view history.</p>
                            <button class="btn btn-primary mt-3">View Now</button>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="#" class="text-decoration-none">
                    <div class="choice-card h-100">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h3 class="card-title">Help & Support</h3>
                            <p class="text-muted">Get assistance with using the portal or answers to common questions.</p>
                            <button class="btn btn-primary mt-3">Get Help</button>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Additional Features Section -->
        <div class="row mt-5 pt-5">
            <div class="col-lg-6 mb-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                    <h3 class="h4 mb-3"><i class="fas fa-bolt text-warning me-2"></i> Quick Links</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none"><i class="fas fa-arrow-right text-primary me-2"></i> Academic Calendar</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none"><i class="fas fa-arrow-right text-primary me-2"></i> University Policies</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none"><i class="fas fa-arrow-right text-primary me-2"></i> Frequently Asked Questions</a></li>
                        <li><a href="#" class="text-decoration-none"><i class="fas fa-arrow-right text-primary me-2"></i> Contact Departments</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                    <h3 class="h4 mb-3"><i class="fas fa-chart-line text-info me-2"></i> Request Statistics</h3>
                    <p class="text-muted">Our portal has processed:</p>
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <h4 class="text-primary">1,250+</h4>
                            <small>Requests this month</small>
                        </div>
                        <div class="text-center">
                            <h4 class="text-success">98%</h4>
                            <small>Completion rate</small>
                        </div>
                        <div class="text-center">
                            <h4 class="text-warning">24h</h4>
                            <small>Average response</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    
    <?php 
        include '../includes/index_footer.php';
        ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling to all links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Add animation to cards when they come into view
        const observerOptions = {
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.choice-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>