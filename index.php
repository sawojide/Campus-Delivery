<?php
session_start();
// NO REDIRECT - Let everyone see the landing page!
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Runs - Fast & Secure Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #dc3545; --primary-dark: #c82333; }
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; padding: 120px 0 80px; min-height: 80vh; display: flex; align-items: center;
        }
        .feature-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem; }
        .category-card {
            transition: all 0.3s ease; border: 1px solid rgba(0,0,0,0.05);
        }
        .category-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; 
        }
        .step-number {
            width: 50px; height: 50px; background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; font-weight: bold; margin: 0 auto 1rem;
        }
        .navbar { backdrop-filter: blur(10px); background: rgba(255,255,255,0.95) !important; }
    </style>
    <!-- PWA Meta Tags -->
<meta name="theme-color" content="#dc3545">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Campus Delivery">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icons/icon-192x192.png">

</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="bi bi-bag-heart-fill"></i> Campus Runs
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#categories">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item ms-lg-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-outline-danger btn-sm">Dashboard</a>
                        <a href="logout.php" class="btn btn-danger btn-sm ms-2">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-danger btn-sm">Login</a>
                        <a href="register.php" class="btn btn-danger btn-sm ms-2">Sign Up</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3">Campus Delivery, <br>Reimagined. 🍔</h1>
                <p class="lead mb-4 opacity-75">Order food, suya, perfumes, and essentials delivered straight to your hostel door. Safe, fast, and cashless.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="register.php" class="btn btn-light btn-lg px-4 fw-bold text-danger">
                        <i class="bi bi-rocket-takeoff"></i> Get Started
                    </a>
                    <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-play-circle"></i> How It Works
                    </a>
                </div>
                <div class="mt-5 d-flex gap-4 text-white-50 small">
                    <span><i class="bi bi-check-circle-fill text-white me-1"></i> 20-Min Delivery</span>
                    <span><i class="bi bi-check-circle-fill text-white me-1"></i> Secure Wallet</span>
                    <span><i class="bi bi-check-circle-fill text-white me-1"></i> 500+ Items</span>
                </div>
            </div>
            <div class="col-lg-6 text-center d-none d-lg-block">
                <i class="bi bi-bag-heart display-1" style="font-size: 12rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-danger fw-bold text-uppercase">Why Choose Us</h6>
            <h2 class="fw-bold">Built for Campus Life</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 text-center h-100 bg-light rounded-3">
                    <i class="bi bi-shield-check feature-icon"></i>
                    <h5>Safe & Secure</h5>
                    <p class="text-muted small mb-0">No more risky off-campus trips at night. We bring it to your door.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 text-center h-100 bg-light rounded-3">
                    <i class="bi bi-lightning-charge feature-icon"></i>
                    <h5>Lightning Fast</h5>
                    <p class="text-muted small mb-0">Average delivery time of 20 minutes. Hot food stays hot.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 text-center h-100 bg-light rounded-3">
                    <i class="bi bi-wallet2 feature-icon"></i>
                    <h5>Cashless Wallet</h5>
                    <p class="text-muted small mb-0">Fund once, pay instantly. No cash handling, zero stress.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section id="categories" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-danger fw-bold text-uppercase">Our Marketplace</h6>
            <h2 class="fw-bold">What Can You Order?</h2>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="browse.php?category=food" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-fire text-danger display-6"></i>
                    <h6 class="mt-3 fw-bold">Suya & BBQ</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=food" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-cup-hot text-warning display-6"></i>
                    <h6 class="mt-3 fw-bold">Hot Meals</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=perfumes" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-droplet text-primary display-6"></i>
                    <h6 class="mt-3 fw-bold">Perfumes</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=provisions" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-box-seam text-success display-6"></i>
                    <h6 class="mt-3 fw-bold">Provisions</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=electronics" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-phone text-info display-6"></i>
                    <h6 class="mt-3 fw-bold">Electronics</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=books" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-book text-secondary display-6"></i>
                    <h6 class="mt-3 fw-bold">Stationery</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=provisions" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-capsule text-danger display-6"></i>
                    <h6 class="mt-3 fw-bold">Health & Beauty</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="browse.php?category=snacks" class="category-card card h-100 border-0 shadow-sm p-4 text-center text-decoration-none text-dark">
                    <i class="bi bi-cup-straw text-warning display-6"></i>
                    <h6 class="mt-3 fw-bold">Snacks & Drinks</h6>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-danger fw-bold text-uppercase">Simple Process</h6>
            <h2 class="fw-bold">How It Works</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="step-number">1</div>
                <h5 class="fw-bold">Browse & Order</h5>
                <p class="text-muted small">Select from hundreds of items and checkout securely with your wallet.</p>
            </div>
            <div class="col-md-4">
                <div class="step-number">2</div>
                <h5 class="fw-bold">We Prepare</h5>
                <p class="text-muted small">Your trusted campus vendor receives and prepares your order fresh.</p>
            </div>
            <div class="col-md-4">
                <div class="step-number">3</div>
                <h5 class="fw-bold">Fast Delivery</h5>
                <p class="text-muted small">Our verified student riders deliver straight to your hostel door.</p>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="register.php" class="btn btn-danger btn-lg px-5 shadow-sm">
                <i class="bi bi-rocket-takeoff"></i> Start Ordering Now
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-danger text-white">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold mb-0">500+</h2>
                <p class="mb-0 opacity-75">Products</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold mb-0">20+</h2>
                <p class="mb-0 opacity-75">Vendors</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold mb-0">20m</h2>
                <p class="mb-0 opacity-75">Avg. Delivery</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-5 fw-bold mb-0">24/7</h2>
                <p class="mb-0 opacity-75">Support</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-4 border-top border-secondary">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center text-md-start">
                <h5 class="fw-bold mb-1"><i class="bi bi-bag-heart-fill text-danger"></i> Campus Runs</h5>
                <p class="text-muted small mb-0">Making campus life easier, one delivery at a time.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="d-flex justify-content-center gap-3 small">
                    <a href="login.php" class="text-muted text-decoration-none">Login</a>
                    <a href="register.php" class="text-muted text-decoration-none">Register</a>
                    <a href="#features" class="text-muted text-decoration-none">Features</a>
                    <a href="user-manual.html" target="_blank" class="btn-manual">📖 User Manual</a>
                </div>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <p class="text-muted small mb-0">
                    <i class="bi bi-envelope me-1"></i> support@campusruns.ng
                </p>
            </div>
        </div>
        <hr class="border-secondary my-3">
        <div class="text-center text-muted small">
            &copy; 2026 Campus Runs. All rights reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered successfully:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
</body>
</html>