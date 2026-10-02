<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (is_loggedin()) {
    $_SESSION = [];
    session_unset();
    session_destroy();
}

set_flash('You have been logged out successfully.', 'success');
redirect(site_url('users/login.php'));
