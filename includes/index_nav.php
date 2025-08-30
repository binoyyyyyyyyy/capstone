<?php
// Function to get the correct asset path
function getAssetPath($filename) {
    // Check if we're in admin directory (going up one level)
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        return '../assets/' . $filename;
    }
    // Otherwise we're in root directory
    return 'assets/' . $filename;
}
?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                 <img src="<?php echo getAssetPath('neustlogo.png'); ?>" alt="NEUST Logo">
                NEUST Request Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../admin/index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://neust.edu.ph/historical-background/"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://neust.edu.ph/"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                    
                </ul>
            </div>
        </div>
    </nav>
