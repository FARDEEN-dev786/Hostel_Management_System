<?php
require_once __DIR__ . '/app/bootstrap.php';
if (current_user()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    try {
        $q = db()->prepare('SELECT ua.*, s.full_name AS student_name, st.full_name AS staff_name FROM user_accounts ua LEFT JOIN students s ON s.student_id=ua.student_id LEFT JOIN staff st ON st.staff_id=ua.staff_id WHERE ua.username=? AND ua.is_active=1 LIMIT 1');
        $q->execute([$username]); $user = $q->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash']); $_SESSION['user'] = $user;
            header('Location: index.php'); exit;
        }
        $error = 'The username or password is incorrect.';
    } catch (Throwable $e) {
        $error = 'Database connection failed. Start Apache and MySQL in XAMPP, import sql/schema.sql and sql/seed.sql in phpMyAdmin, and check config.php.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in | Hostel Portal</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><main class="login-shell"><section class="login-story"><div class="brand brand-light"><span class="brand-mark">H</span><span>HOSTEL PORTAL</span></div><div class="story-copy"><p class="eyebrow light">CAMPUS LIVING, WELL MANAGED</p><h1 class="portal-tagline">ALL IN ONE<br><em>HOSTEL MANAGEMENT PORTAL</em></h1><p>One place for room information, resident services, facility bookings and the people who keep the hostel running.</p></div><div class="story-foot">COLLEGE HOSTEL MANAGEMENT SYSTEM <span>•</span> STUDENT / STAFF / ADMIN</div></section>
<section class="login-panel"><div class="login-form-wrap"><div class="mobile-brand brand"><span class="brand-mark">H</span><span>HOSTEL PORTAL</span></div><p class="eyebrow">WELCOME BACK</p><h2>Sign in to your portal</h2><p class="muted">Use the account provided by your hostel administration.</p><?php if ($error): ?><div class="alert error"><?=h($error)?></div><?php endif; ?>
<form method="post" class="form-stack"><?=csrf_input()?>
<label>Username<input name="username" autocomplete="username" placeholder="Enter your username" required autofocus></label>
<label>Password<input type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></label>
<button class="button primary full" type="submit">Sign in <span>→</span></button></form>
<div class="login-note"><strong>Demo accounts</strong><span>Admin: admin / admin123</span><span>Staff: staff01 / staff123</span><span>Student: 2025UCA1890 / student123</span><small>Change demo passwords before real use.</small></div></div><footer>Need access? Contact your hostel office.</footer></section></main></body></html>
