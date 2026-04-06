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
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) return 'Please enter username and password.';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return 'Invalid username or password.';
    }
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    header('Location: ?page=dashboard');
    exit;
}

function handleLogout(): void {
    session_destroy();
    header('Location: ?page=login');
    exit;
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
