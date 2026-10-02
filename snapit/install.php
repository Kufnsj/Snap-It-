<?php
require_once __DIR__ . '/includes/functions.php';

$step = $_GET['step'] ?? 1;
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? 'db_snapit');

    $connTest = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$connTest) {
        $message = 'Could not connect to MySQL: ' . mysqli_connect_error();
        $messageType = 'danger';
    } else {
        mysqli_query($connTest, "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_select_db($connTest, $db_name);
        mysqli_set_charset($connTest, 'utf8mb4');

        $configContent = "<?php\n";
        $configContent .= '$db_host = ' . var_export($db_host, true) . ";\n";
        $configContent .= '$db_username = ' . var_export($db_user, true) . ";\n";
        $configContent .= '$db_passwd = ' . var_export($db_pass, true) . ";\n";
        $configContent .= '$db_name = ' . var_export($db_name, true) . ";\n\n";
        $configContent .= '$conn = mysqli_connect($db_host, $db_username, $db_passwd, $db_name);' . "\n\n";
        $configContent .= "if (!\$conn) {\n    die(\"Connection failed: \" . mysqli_connect_error());\n}\n\n";
        $configContent .= "mysqli_set_charset(\$conn, \"utf8mb4\");\n";

        @file_put_contents(__DIR__ . '/includes/config.php', $configContent);
        require __DIR__ . '/includes/config.php';

        $sql = file_get_contents(__DIR__ . '/includes/schema.sql');
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));
        $errors = [];
        foreach ($statements as $stmt) {
            if (empty($stmt)) continue;
            if (!mysqli_query($conn, $stmt)) {
                $errors[] = mysqli_error($conn);
            }
        }

        if (empty($errors)) {
            $seed = file_get_contents(__DIR__ . '/includes/seed.sql');
            $seedStmts = array_filter(array_map('trim', explode(";\n", $seed)));
            foreach ($seedStmts as $stmt) {
                if (empty($stmt)) continue;
                if (!mysqli_query($conn, $stmt)) {
                    $errors[] = mysqli_error($conn);
                }
            }
        }

        if (empty($errors)) {
            set_flash('Installation completed successfully. You can now log in.', 'success');
            redirect('users/login.php');
        } else {
            $message = 'Errors during install: ' . implode(', ', array_slice($errors, 0, 3));
            $messageType = 'danger';
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/alert.php';
?>

<div class="container-lg py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-lg">
                <div class="card-header text-center py-4">
                    <h2 class="mb-0">
                        <i class="fa-solid fa-camera-retro me-2" style="color:#6f42c1"></i>
                        Snap It &mdash; Installer
                    </h2>
                    <p class="text-muted mb-0 mt-2">Photo Customization Booth and Rental System</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
                    <?php endif; ?>

                    <div class="step-indicator mb-4">
                        <div class="step active">1</div>
                        <div class="step">2</div>
                        <div class="step">3</div>
                    </div>
                    <p class="text-center text-muted mb-4">Step 1 &mdash; Configure Database Connection</p>

                    <form method="POST" action="<?= e($_SERVER['PHP_SELF']) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Database Host</label>
                            <input type="text" name="db_host" class="form-control form-control-lg" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Database Username</label>
                            <input type="text" name="db_user" class="form-control form-control-lg" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Database Password</label>
                            <input type="password" name="db_pass" class="form-control form-control-lg">
                            <small class="text-muted">Leave blank for default XAMPP/WAMP local server.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Database Name</label>
                            <input type="text" name="db_name" class="form-control form-control-lg" value="db_snapit" required>
                            <small class="text-muted">Will be created if it does not exist.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-snapit btn-lg py-3">
                                <i class="fa-solid fa-database me-2"></i>Install &amp; Seed Database
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="fa-solid fa-info-circle me-1" style="color:#6f42c1"></i> Default accounts after install:</h6>
                            <ul class="mb-0 small">
                                <li><strong>Admin:</strong> admin@snapit.ph / <code>admin123</code></li>
                                <li><strong>Staff:</strong> staff@snapit.ph / <code>staff123</code></li>
                                <li><strong>Customer:</strong> customer@snapit.ph / <code>cust123</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
