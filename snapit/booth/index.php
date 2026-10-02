<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_login();

$active_session_id = current_booth_session_id($conn);

$where = "WHERE status IN ('confirmed','paid')";
$params = [];
$types = '';
if (is_customer()) {
    $where .= " AND user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
}

$sql = "SELECT b.*, p.name AS package_name FROM bookings b 
        LEFT JOIN packages p ON b.package_id = p.package_id 
        $where 
        ORDER BY b.event_date DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/alert.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 p-4">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-camera-retro fa-3x mb-2" style="color:var(--snapit-primary)"></i>
                    <h2 class="fw-bold" style="color:var(--snapit-primary)">Photo Booth</h2>
                    <p class="text-muted mb-0">Attach to a confirmed booking to start capturing moments.</p>
                </div>

                <?php if ($active_session_id): ?>
                    <div class="alert alert-primary mb-4" role="alert">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fa-solid fa-circle-play me-2"></i>
                                <strong>Active session in progress</strong>
                            </div>
                            <a href="<?= e(site_url('booth/session.php')) ?>" class="btn btn-primary btn-sm">
                                Resume Session <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= e(site_url('booth/start_session.php')) ?>">
                    <div class="mb-3">
                        <label for="booking_id" class="form-label fw-semibold">
                            <i class="fa-regular fa-calendar-check me-1"></i>Attach to a Confirmed Booking
                        </label>
                        <select class="form-select form-select-lg" id="booking_id" name="booking_id" required>
                            <option value="">-- Select a booking --</option>
                            <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                                <option value="<?= (int)$b['booking_id'] ?>">
                                    <?= e($b['event_name']) ?>
                                    &mdash; <?= e(format_date($b['event_date'])) ?>
                                    [<?= e($b['package_name']) ?>]
                                    <span class="badge <?= e(status_badge_class($b['status'])) ?> ms-1">
                                        <?= e(ucfirst($b['status'])) ?>
                                    </span>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if (mysqli_num_rows($bookings) === 0): ?>
                            <div class="form-text text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                No confirmed or paid bookings available.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="guest_name" class="form-label fw-semibold">
                            <i class="fa-regular fa-user me-1"></i>Guest Name
                            <span class="text-muted fw-normal small">(optional)</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="guest_name" name="guest_name"
                               placeholder="Enter guest name or leave blank">
                    </div>

                    <button type="submit" class="btn btn-snapit btn-lg w-100 fw-semibold">
                        <i class="fa-solid fa-bolt me-2"></i>Start Photo Booth Session
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
