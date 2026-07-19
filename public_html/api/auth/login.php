<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim(strtolower($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['error' => 'Enter your email and password.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Invalid email or password.'], 401);
}

create_auth_token((int) $user['id']);

$redirect = match ($user['role']) {
    'tool_owner' => '/owner/dashboard.php',
    'admin'      => '/admin/dashboard.php',
    default      => '/renter/dashboard.php',
};

json_response(['success' => true, 'redirect' => $redirect]);
