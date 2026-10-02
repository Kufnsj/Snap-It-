<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(site_url('booth/index.php'));
}

$booking_id = (int)($_POST['booking_id'] ?? 0);
$guest_name = trim($_POST['guest_name'] ?? '');

if ($booking_id <= 0) {
    set_flash('Please select a valid booking.', 'warning');
    redirect(site_url('booth/index.php'));
}

$where = "WHERE booking_id = ? AND status IN ('confirmed','paid')";
$params = [$booking_id];
$types = 'i';
if (is_customer()) {
    $where .= " AND user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
}

$stmt = mysqli_prepare($conn, "SELECT booking_id FROM bookings $where LIMIT 1");
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    set_flash('Booking not found or not eligible for booth sessions.', 'danger');
    redirect(site_url('booth/index.php'));
}

$stmt = mysqli_prepare($conn, "INSERT INTO guest_sessions (booking_id, guest_name, status, idle_timeout_at, started_at)
                               VALUES (?, ?, 'active', NOW() + INTERVAL 3 MINUTE, NOW())");
$gn = empty($guest_name) ? null : $guest_name;
mysqli_stmt_bind_param($stmt, 'is', $booking_id, $gn);
mysqli_stmt_execute($stmt);

$session_id = mysqli_insert_id($conn);
$_SESSION['active_session_id'] = $session_id;
unset($_SESSION['capture_index'], $_SESSION['captured']);

set_flash('Session started! Customize your booth experience.', 'success');
redirect(site_url('booth/capture.php'));
