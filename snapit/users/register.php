<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (is_loggedin()) {
    redirect(site_url('index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }

    if (!empty($errors)) {
        set_flash(implode(' ', $errors), 'danger');
        redirect(site_url('users/register.php'));
    }

    $check_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, 's', $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    if (mysqli_num_rows($check_result) > 0) {
        set_flash('Email is already registered. Please use a different email or login.', 'danger');
        redirect(site_url('users/register.php'));
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $role = 'customer';
    $status = 'active';

    $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone, address, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, 'sssssss', $name, $email, $hashed_password, $phone, $address, $role, $status);

    if (mysqli_stmt_execute($insert_stmt)) {
        set_flash('Registration successful! Please sign in to continue.', 'success');
        redirect(site_url('users/login.php'));
    }

    set_flash('Registration failed. Please try again.', 'danger');
    redirect(site_url('users/register.php'));
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/alert.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-user-plus fa-3x mb-3" style="color:#6f42c1"></i>
                        <h2 class="fw-bold">Create Your Account</h2>
                        <p class="text-muted">Join Snap It and start booking your events</p>
                    </div>

                    <form action="<?= e(site_url('users/register.php')) ?>" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                                <input type="text" class="form-control form-control-lg" id="name" name="name"
                                    value="<?= e($_POST['name'] ?? '') ?>" required autocomplete="name">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                    value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password"
                                        required autocomplete="new-password">
                                </div>
                                <div class="form-text">At least 6 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock-open"></i></span>
                                    <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password"
                                        required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone"
                                    value="<?= e($_POST['phone'] ?? '') ?>" required autocomplete="tel">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Complete Address</label>
                            <div class="input-group">
                                <span class="input-group-text align-top"><i class="fa-solid fa-location-dot mt-2"></i></span>
                                <textarea class="form-control form-control-lg" id="address" name="address"
                                    rows="3" required autocomplete="street-address"><?= e($_POST['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-snapit btn-lg w-100 fw-semibold">
                            <i class="fa-solid fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="mb-0 text-muted">Already have an account?
                            <a href="<?= e(site_url('users/login.php')) ?>" class="fw-semibold text-decoration-none" style="color:#6f42c1">Sign in here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
