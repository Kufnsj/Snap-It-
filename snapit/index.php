<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/config.php';

$packages_stmt = mysqli_prepare($conn, "SELECT * FROM packages WHERE is_active = 1 ORDER BY base_price ASC");
mysqli_stmt_execute($packages_stmt);
$packages = mysqli_stmt_get_result($packages_stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/alert.php';
?>

<section class="hero text-center">
    <div class="container">
        <h1><i class="fa-solid fa-camera-retro me-3"></i>Snap It!</h1>
        <p class="lead mt-3">Capture. Customize. Celebrate.<br>The all-in-one photo customization booth and rental platform.</p>
        <?php if (!is_loggedin()): ?>
            <div class="mt-4">
                <a href="<?= e(site_url('users/register.php')) ?>" class="btn btn-light btn-lg me-2 fw-semibold px-4">
                    <i class="fa-solid fa-user-plus me-1"></i> Create Account
                </a>
                <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-accent btn-lg fw-semibold px-4">
                    <i class="fa-regular fa-calendar-check me-1"></i> Book Your Event Now
                </a>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-light btn-lg fw-semibold px-5">
                    <i class="fa-regular fa-calendar-plus me-2"></i>Book an Event
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card h-100 p-4 shadow-sm border-0">
                <div class="mb-3"><i class="fa-solid fa-camera fa-3x" style="color:#6f42c1"></i></div>
                <h5 class="fw-bold">Live Capture Booth</h5>
                <p class="text-muted">Filters, presets, layouts &mdash; your guests shoot, retake, and keep every shot with a fun countdown.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 shadow-sm border-0">
                <div class="mb-3"><i class="fa-regular fa-calendar-check fa-3x" style="color:#e83e8c"></i></div>
                <h5 class="fw-bold">Smart Booking Calendar</h5>
                <p class="text-muted">Prevent double-bookings with conflict checks and choose packages that match your event size.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 shadow-sm border-0">
                <div class="mb-3"><i class="fa-solid fa-envelopes-bulk fa-3x" style="color:#fd7e14"></i></div>
                <h5 class="fw-bold">Instant Photo Delivery</h5>
                <p class="text-muted">Email up to 10 recipients with the finished photo set or download directly as PNG.</p>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5">
    <h2 class="text-center fw-bold mb-4" style="color:#6f42c1">
        <i class="fa-solid fa-gift me-2"></i>Choose Your Package
    </h2>
    <div class="row g-4">
        <?php while ($pkg = mysqli_fetch_assoc($packages)): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card card-package h-100">
                    <div class="card-header py-3 text-center">
                        <h4 class="fw-bold mb-0"><?= e($pkg['name']) ?></h4>
                        <div class="small opacity-75 mt-1"><?= (int)$pkg['duration_hours'] ?> hours &bull;
                            <?= (int)$pkg['softcopy_count'] ?> soft / <?= (int)$pkg['hardcopy_count'] ?> hard copies
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3"><?= e($pkg['description'] ?? '') ?></p>
                        <div class="mb-3">
                            <span class="badge badge-snapit me-2"><i class="fa-regular fa-file-image me-1"></i><?= (int)$pkg['softcopy_count'] ?> Soft Copies</span>
                            <span class="badge bg-secondary"><i class="fa-solid fa-print me-1"></i><?= (int)$pkg['hardcopy_count'] ?> Hard Copies</span>
                        </div>
                        <ul class="list-unstyled mb-4 small">
                            <li class="mb-1"><i class="fa-solid fa-circle-check me-2 text-success"></i>Live countdown capture</li>
                            <li class="mb-1"><i class="fa-solid fa-circle-check me-2 text-success"></i>Filters, layouts & frame designs</li>
                            <li class="mb-1"><i class="fa-solid fa-circle-check me-2 text-success"></i>Conflict-free date booking</li>
                            <?php if ($pkg['has_softcopy_addon']): ?>
                                <li class="mb-1"><i class="fa-solid fa-circle-plus me-2 text-info"></i>Softcopy add-on available</li>
                            <?php endif; ?>
                        </ul>
                        <div class="d-flex align-items-end justify-content-between">
                            <div>
                                <div class="small text-muted">Starting at</div>
                                <div class="fw-bold" style="font-size:1.7rem;color:#6f42c1"><?= e(format_money($pkg['base_price'])) ?></div>
                                <?php if ($pkg['has_softcopy_addon'] && $pkg['softcopy_addon_price'] > 0): ?>
                                    <div class="small text-muted">+ <?= e(format_money($pkg['softcopy_addon_price'])) ?> softcopy add-on</div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= e(site_url('booking/create.php?package=' . (int)$pkg['package_id'])) ?>" class="btn btn-snapit">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<?php
$cfg_path = __DIR__ . '/includes/config.php';
$db_ready = false;
if (file_exists($cfg_path)) {
    $cfg_include = @include $cfg_path;
    if (isset($conn) && $conn instanceof mysqli && !mysqli_connect_error()) {
        $tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'packages'");
        if ($tbl && mysqli_num_rows($tbl) > 0) {
            $db_ready = true;
        }
    }
}
?>
<section class="container mb-5">
    <div class="card shadow-sm border-0 p-4 text-center" style="background: linear-gradient(90deg, #6f42c1, #e83e8c); color:#fff; border-radius:24px;">
        <h3 class="fw-bold mb-2">Ready to snap the best moments of your event?</h3>
        <p class="mb-4 opacity-90">From debuts to corporate launches, we've got a package for every celebration.</p>
        <?php if (!$db_ready): ?>
            <a href="<?= e(site_url('install.php')) ?>" class="btn btn-light btn-lg fw-semibold px-5">
                <i class="fa-solid fa-rocket me-2"></i>Run the Installer to get started
            </a>
        <?php else: ?>
            <?php if (!is_loggedin()): ?>
                <a href="<?= e(site_url('users/register.php')) ?>" class="btn btn-light btn-lg fw-semibold px-5 me-2">
                    <i class="fa-solid fa-user-plus me-2"></i>Create Your Account
                </a>
                <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-accent btn-lg fw-semibold px-5">
                    <i class="fa-regular fa-calendar-check me-2"></i>Book Now
                </a>
            <?php else: ?>
                <a href="<?= e(site_url('booking/create.php')) ?>" class="btn btn-light btn-lg fw-semibold px-5">
                    <i class="fa-regular fa-calendar-plus me-2"></i>Book Your Next Event
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
