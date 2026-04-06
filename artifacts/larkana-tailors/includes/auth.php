<?php
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ?page=login');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ?page=dashboard&err=access');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
    ];
}

function handleLogin(): ?string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    // Verify pre-auth CSRF token (guards against login-CSRF).
    $loginCsrf   = $_SESSION['login_csrf'] ?? '';
    $providedCsrf = $_POST['csrf'] ?? '';
    if (!$loginCsrf || !$providedCsrf || !hash_equals($loginCsrf, $providedCsrf)) {
        return 'Invalid form token. Please refresh and try again.';
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) return 'Please enter username and password.';

    // Simple session-based throttle: 5 failed attempts → 30 s lockout.
    $attempts  = $_SESSION['login_attempts'] ?? 0;
    $lastFail  = $_SESSION['login_last_fail'] ?? 0;
    if ($attempts >= 5 && (time() - $lastFail) < 30) {
        $wait = 30 - (time() - $lastFail);
        return "Too many failed attempts. Please wait $wait second(s) and try again.";
    }
    if ((time() - $lastFail) >= 30) {
        $attempts = 0;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['login_last_fail'] = time();
        return 'Invalid username or password.';
    }
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['csrf_token']    = bin2hex(random_bytes(16));
    unset($_SESSION['login_attempts'], $_SESSION['login_last_fail']);
    // Role-differentiated landing: workers go straight to the orders list.
    header($user['role'] === 'admin' ? 'Location: ?page=dashboard' : 'Location: ?page=orders');
    exit;
}

function handleLogout(): void {
    session_destroy();
    header('Location: ?page=login');
    exit;
}

function getCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

// Pre-auth CSRF token for the login form (defense against login-CSRF attacks).
function getCsrfLogin(): string {
    if (empty($_SESSION['login_csrf'])) {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['login_csrf'];
}

function verifyCsrf(): void {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $provided     = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    // Both values must be non-empty; empty == empty would bypass protection.
    if (!$sessionToken || !$provided || !hash_equals($sessionToken, $provided)) {
        http_response_code(403);
        die('<p style="font-family:Arial;padding:20px;color:#c62828;">Invalid or expired form token. Please go back and try again.</p>');
    }
}

function handleAddWorker(): ?string {
    if (!isAdmin()) return 'Access denied.';
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    if (!$username || !$password) return 'Username and password required.';
    $db = getDB();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'worker', ?)")
           ->execute([$username, $hash, $full_name]);
        return null;
    } catch (PDOException $e) {
        return 'Username already exists.';
    }
}
