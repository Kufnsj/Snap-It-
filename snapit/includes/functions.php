<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function set_flash($message, $type = 'warning') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function has_flash() {
    return isset($_SESSION['flash_message']);
}

function get_flash_message() {
    if (has_flash()) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'warning';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $msg, 'type' => $type];
    }
    return null;
}

function is_loggedin() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_customer() {
    return is_loggedin() && ($_SESSION['role'] ?? '') === 'customer';
}

function is_staff() {
    return is_loggedin() && in_array(($_SESSION['role'] ?? ''), ['staff', 'admin']);
}

function is_admin() {
    return is_loggedin() && ($_SESSION['role'] ?? '') === 'admin';
}

function require_login() {
    if (!is_loggedin()) {
        set_flash('Please log in to access this page.', 'warning');
        redirect('../users/login.php');
    }
}

function require_staff() {
    require_login();
    if (!is_staff()) {
        set_flash('Staff access required.', 'danger');
        redirect('../index.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('Administrator access required.', 'danger');
        redirect('../index.php');
    }
}

function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function status_badge_class($status) {
    $map = [
        'pending' => 'bg-warning text-dark',
        'confirmed' => 'bg-info text-dark',
        'paid' => 'bg-success',
        'completed' => 'bg-secondary',
        'cancelled' => 'bg-danger',
        'active' => 'bg-primary',
        'inactive' => 'bg-secondary',
        'printed' => 'bg-success',
        'error' => 'bg-danger',
    ];
    return $map[strtolower($status)] ?? 'bg-secondary';
}

function format_money($amount) {
    return 'PHP ' . number_format((float)$amount, 2);
}

function format_date($dateStr, $withTime = false) {
    if (empty($dateStr)) return '-';
    $ts = strtotime($dateStr);
    if ($withTime) return date('M j, Y h:i A', $ts);
    return date('M j, Y', $ts);
}

function is_date_past($dateStr) {
    $today = date('Y-m-d');
    return $dateStr < $today;
}

function time_to_minutes($timeStr) {
    list($h, $m) = explode(':', substr($timeStr, 0, 5));
    return ((int)$h * 60) + (int)$m;
}

function booking_has_conflict($conn, $event_date, $start_time, $duration_hours, $exclude_booking_id = null) {
    $start_min = time_to_minutes($start_time);
    $end_min = $start_min + ((int)$duration_hours * 60);

    $sql = "SELECT booking_id, start_time, duration_hours FROM bookings 
            WHERE event_date = ? AND status NOT IN ('cancelled')";
    $params = [$event_date];
    $types = 's';
    if ($exclude_booking_id) {
        $sql .= " AND booking_id <> ?";
        $params[] = $exclude_booking_id;
        $types .= 'i';
    }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $exist_start = time_to_minutes($row['start_time']);
        $exist_end = $exist_start + ((int)$row['duration_hours'] * 60);
        if ($start_min < $exist_end && $end_min > $exist_start) {
            return true;
        }
    }
    return false;
}

function in_business_hours($start_time, $duration_hours) {
    $open = 8 * 60;
    $close = 22 * 60;
    $start_min = time_to_minutes($start_time);
    $end_min = $start_min + ((int)$duration_hours * 60);
    return $start_min >= $open && $end_min <= $close;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function site_base() {
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $needle = '/snapit/';
    $pos = strpos($script, $needle);
    if ($pos === false) {
        $base = rtrim(dirname($script), '/');
    } else {
        $base = substr($script, 0, $pos) . '/snapit';
    }
    return $base;
}

function site_url($path = '') {
    $b = site_base();
    if ($path === '') return $b . '/';
    $path = ltrim($path, '/');
    return $b . '/' . $path;
}

function current_booth_session_id($conn) {
    if (empty($_SESSION['active_session_id'])) return null;
    $stmt = mysqli_prepare($conn, "SELECT session_id FROM guest_sessions WHERE session_id = ? AND status = 'active' LIMIT 1");
    $sid = $_SESSION['active_session_id'];
    mysqli_stmt_bind_param($stmt, 'i', $sid);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($r) > 0) return $sid;
    unset($_SESSION['active_session_id']);
    return null;
}
