<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="<?= e(site_url('includes/style/style.css')) ?>" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
    <title>Snap It &mdash; Photo Booth Rental</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-snapit">
    <div class="container-fluid container-lg">
        <a class="navbar-brand fw-bold" href="<?= e(site_url()) ?>">
            <i class="fa-solid fa-camera-retro me-2"></i>Snap It
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#snapitNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="snapitNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= e(site_url()) ?>">Home</a></li>
                <?php if (is_loggedin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(site_url('booking/index.php')) ?>">My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(site_url('gallery/index.php')) ?>">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(site_url('booth/index.php')) ?>">Photo Booth</a></li>
                <?php endif; ?>
                <?php if (is_staff()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= e(site_url('admin/index.php')) ?>"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= e(site_url('booking/index.php')) ?>"><i class="fa-regular fa-calendar me-2"></i>Bookings</a></li>
                            <li><a class="dropdown-item" href="<?= e(site_url('inventory/index.php')) ?>"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventory</a></li>
                            <li><a class="dropdown-item" href="<?= e(site_url('inventory/print_queue.php')) ?>"><i class="fa-solid fa-print me-2"></i>Print Queue</a></li>
                            <li><a class="dropdown-item" href="<?= e(site_url('payment/index.php')) ?>"><i class="fa-solid fa-money-bill-wave me-2"></i>Payments</a></li>
                            <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= e(site_url('admin/customers.php')) ?>"><i class="fa-solid fa-users me-2"></i>Customers</a></li>
                                <li><a class="dropdown-item" href="<?= e(site_url('admin/staff.php')) ?>"><i class="fa-solid fa-user-tie me-2"></i>Staff</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (is_loggedin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(site_url('users/profile.php')) ?>">
                            <i class="fa-regular fa-circle-user me-1"></i><?= e($_SESSION['name'] ?? 'Account') ?>
                            <span class="badge badge-snapit ms-1"><?= e(ucfirst($_SESSION['role'] ?? 'customer')) ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(site_url('users/logout.php')) ?>">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(site_url('users/login.php')) ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(site_url('users/register.php')) ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
