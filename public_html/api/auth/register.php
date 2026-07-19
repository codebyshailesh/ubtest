<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$name         = trim($input['name'] ?? '');
$email        = trim(strtolower($input['email'] ?? ''));
$password     = (string) ($input['password'] ?? '');
$role         = $input['role'] ?? 'renter';
$phone        = trim($input['phone'] ?? '');
$addressLine  = trim($input['address_line'] ?? '');
$city         = trim($input['city'] ?? '');
$state        = trim($input['state'] ?? '');
$postalCode   = trim($input['postal_code'] ?? '');

if (!in_array($role, ['renter', 'tool_owner'], true)) {
    json_response(['error' => 'Invalid role selected.'], 422);
}
if ($name === '' || $phone === '') {
    json_response(['error' => 'Name and phone number are required.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid email address.'], 422);
}
if (strlen($password) < 8) {
    json_response(['error' => 'Password must be at least 8 characters.'], 422);
}
if ($addressLine === '' || $city === '' || $state === '' || $postalCode === '') {
    json_response(['error' => 'A full address is required — tools are matched to renters by location.'], 422);
}

$pdo = get_db();

$check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    json_response(['error' => 'An account with that email already exists.'], 409);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role, phone, address_line, city, state, postal_code)
     VALUES (:name, :email, :password_hash, :role, :phone, :address_line, :city, :state, :postal_code)'
);
$stmt->execute([
    'name'          => $name,
    'email'         => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role'          => $role,
    'phone'         => $phone,
    'address_line'  => $addressLine,
    'city'          => $city,
    'state'         => $state,
    'postal_code'   => $postalCode,
]);

$userId = (int) $pdo->lastInsertId();
create_auth_token($userId);

$redirect = $role === 'tool_owner' ? '/owner/dashboard.php' : '/renter/dashboard.php';
json_response(['success' => true, 'redirect' => $redirect]);
