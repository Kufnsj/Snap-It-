<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

require_login();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }

    if (!empty($errors)) {
        set_flash(implode(' ', $errors), 'danger');
        redirect(site_url('users/profile.php'));
    }

    $update_stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, 'sssi', $name, $phone, $address, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['name'] = $name;
        set_flash('Profile updated successfully.', 'success');
        redirect(site_url('users/profile.php'));
    }

    set_flash('Profile update failed. Please try again.', 'danger');
    redirect(site_url('users/profile.php'));
}

$user_stmt = mysqli_prepare($conn, "SELECT user_id, name, email, phone, address, role, status, created_at FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    session_unset();
    session_destroy();
    set_flash('Session expired. Please login again.', 'warning');
    redirect(site_url('users/login.php'));
}

$bookings_stmt = mysqli_prepare($conn, "SELECT b.booking_id, b.event_name, b.event_date, b.start_time, b.duration_hours, b.total_amount, b.status, b.created_at, p.name AS package_name
    FROM bookings b
    LEFT JOIN packages p ON b.package_id = p.package_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 10");
mysqli_stmt_bind_param($bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/alert.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-white fw-bold"
                            style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#6f42c1,#e83e8c);font-size:2.2rem">
                            <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                        </div>
                        <h4 class="fw-bold mb-1"><?= e($user['name']) ?></h4>
                        <p class="text-muted mb-2"><?= e($user['email']) ?></p>
                        <span class="badge badge-snapit"><i class="fa-solid fa-user-tag me-1"></i><?= e(ucfirst($user['role'])) ?></span>
                        <span class="badge rounded-pill ms-1 <?= e(status_badge_class($user['status'])) ?>"><?= e(ucfirst($user['status'])) ?></span>
                    </div>
                    <hr>
                    <div class="text-start small text-muted mt-3">
                        <div class="mb-2"><i class="fa-solid fa-calendar me-2"></i>Member since: <?= e(format_date($user['created_at'])) ?></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color:#6f42c1"></i>Contact Details</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="fa-solid fa-phone me-2 text-muted"></i>
                            <span class="fw-medium"><?= e($user['phone'] ?? '-') ?></span>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-location-dot me-2 text-muted"></i>
                            <span class="fw-medium"><?= e($user['address'] ?? '-') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="fa-regular fa-pen-to-square me-2" style="color:#6f42c1"></i>Edit Profile</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= e(site_url('users/profile.php')) ?>" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?= e($user['name']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" value="<?= e($user['email']) ?>" readonly disabled>
                            </div>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?= e($user['phone'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Complete Address</label>
                            <div class="input-group">
                                <span class="input-group-text align-top"><i class="fa-solid fa-location-dot mt-2"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-snapit fw-semibold px-4">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="fa-regular fa-calendar-check me-2" style="color:#e83e8c"></i>Recent Bookings</h5>
                    <?php if (is_customer()): ?>
                        <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-sm btn-accent fw-semibold">
                            <i class="fa-solid fa-plus me-1"></i>New Booking
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Booking</th>
                                        <th>Package</th>
                                        <th>Event Date</th>
                                        <th>Amount</th>
                                        <th class="px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="fw-semibold"><?= e($booking['event_name']) ?></div>
                                                <div class="small text-muted">#<?= e(str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT)) ?></div>
                                            </td>
                                            <td>
                                                <span class="fw-medium"><?= e($booking['package_name'] ?? 'N/A') ?></span>
                                                <div class="small text-muted">
                                                    <i class="fa-regular fa-clock me-1"></i><?= e((int)$booking['duration_hours']) ?>h
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?= e(format_date($booking['event_date'])) ?></div>
                                                <div class="small text-muted">
                                                    <i class="fa-solid fa-clock me-1"></i><?= e(date('g:i A', strtotime($booking['start_time']))) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold" style="color:#6f42c1"><?= e(format_money($booking['total_amount'])) ?></span>
                                            </td>
                                            <td class="px-4">
                                                <span class="badge rounded-pill <?= e(status_badge_class($booking['status'])) ?> px-3 py-2">
                                                    <?= e(ucfirst($booking['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fa-regular fa-calendar-xmark fa-3x text-muted mb-3"></i>
                            <h6 class="fw-bold text-muted mb-1">No bookings yet</h6>
                            <p class="text-muted small mb-3">You haven't made any bookings yet.</p>
                            <?php if (is_customer()): ?>
                                <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-snapit fw-semibold">
                                    <i class="fa-regular fa-calendar-plus me-2"></i>Book Your First Event
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
