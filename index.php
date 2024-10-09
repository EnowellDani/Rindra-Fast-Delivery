<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rindra Fast Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="style.css" rel="stylesheet"> 
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="img/Rindra-img.png" alt="Rindra Fast Delivery Logo" width="30" height="30" class="d-inline-block align-text-top">
            Rindra Fast Delivery
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white me-2" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white" href="sign_up.php">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section py-5 text-center" style="background-image: url('img/fast.jpg'); background-size: cover; background-position: center; color: white;">
    <div class="container">
        <h1 class="display-4 fw-bold">Welcome to Rindra Fast Delivery</h1>
        <p class="lead">Your fast and reliable delivery service</p>
        <a href="sign_up.php" class="btn btn-light btn-lg">Get Started</a>
    </div>
</header>

<!-- About Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">About Us</h2>
        <p class="text-center">At Rindra Fast Delivery, we pride ourselves on delivering your goods safely and on time. Our team is dedicated to providing excellent service to our customers.</p>
    </div>
</section>

<!-- Services Section -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Our Services</h2>
        <div class="row text-center">
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h4>Fast Delivery</h4>
                        <p>We ensure quick and efficient delivery of your items.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h4>Real-Time Tracking</h4>
                        <p>Track your orders in real-time for complete peace of mind.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h4>Customer Support</h4>
                        <p>Our support team is here to help you 24/7.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonial Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">What Our Clients Say</h2>
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="blockquote text-center p-4 border rounded shadow-sm" style="background-color: #f8f9fa;">
                    <p class="mb-0">"Rindra Fast Delivery is my go-to service for all my shipping needs. Highly recommend!"</p>
                    <footer class="blockquote-footer mt-3">Jane Doe</footer>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-primary text-white py-4">
    <div class="container text-center">
        <p>&copy; 2024 Rindra Fast Delivery. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>