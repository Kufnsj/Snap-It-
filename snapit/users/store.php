<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (is_loggedin()) {
    redirect(site_url('index.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(site_url('users/register.php'));
}

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
