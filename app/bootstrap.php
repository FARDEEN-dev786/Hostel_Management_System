<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('hostel_portal');
    session_start();
}
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) { header('Location: login.php'); exit; } }
function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['account_role'], $roles, true)) { http_response_code(403); exit('You are not authorised to access this section.'); }
}
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_input(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Session expired. Refresh the page and try again.');
    }
}
function flash(string $kind, string $message): void { $_SESSION['flash'] = ['kind'=>$kind, 'message'=>$message]; }
function go(string $page = 'dashboard'): never { header('Location: index.php?page=' . urlencode($page)); exit; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function current_member(PDO $pdo, string $studentId): ?array {
    $q = $pdo->prepare("SELECT am.allocation_id, am.member_no, ra.room_id, r.room_number, r.hostel_id, h.hostel_name
        FROM allocation_members am JOIN room_allocations ra ON ra.allocation_id=am.allocation_id
        JOIN rooms r ON r.room_id=ra.room_id JOIN hostels h ON h.hostel_id=r.hostel_id
        WHERE am.student_id=? AND am.leave_date IS NULL AND ra.allocation_status='Active' LIMIT 1");
    $q->execute([$studentId]); return $q->fetch() ?: null;
}
function scalar(PDO $pdo, string $sql, array $params=[]): int|float|string|null { $q=$pdo->prepare($sql); $q->execute($params); return $q->fetchColumn(); }
function rows(PDO $pdo, string $sql, array $params=[]): array { $q=$pdo->prepare($sql); $q->execute($params); return $q->fetchAll(); }
