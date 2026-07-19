<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = api_require_role(['tool_owner']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$name        = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$category    = trim($input['category'] ?? '');
$dailyRate   = (float) ($input['daily_rate'] ?? 0);
$deposit     = (float) ($input['deposit_amount'] ?? 0);
// Pickup address defaults to the owner's own registered address unless overridden.
$addressLine = trim($input['address_line'] ?? $user['address_line']);
$city        = trim($input['city'] ?? $user['city']);
$state       = trim($input['state'] ?? $user['state']);
$postalCode  = trim($input['postal_code'] ?? $user['postal_code']);

if ($name === '' || $category === '') {
    json_response(['error' => 'Name and category are required.'], 422);
}
if (!in_array($category, TOOL_CATEGORIES, true)) {
    json_response(['error' => 'Choose a valid category.'], 422);
}
if ($dailyRate <= 0) {
    json_response(['error' => 'Daily rate must be greater than zero.'], 422);
}
if ($addressLine === '' || $city === '' || $state === '' || $postalCode === '') {
    json_response(['error' => 'A pickup address is required.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare(
    'INSERT INTO tools (owner_id, name, description, category, daily_rate, deposit_amount, address_line, city, state, postal_code, status)
     VALUES (:owner_id, :name, :description, :category, :daily_rate, :deposit_amount, :address_line, :city, :state, :postal_code, "pending")'
);
$stmt->execute([
    'owner_id'       => $user['id'],
    'name'           => $name,
    'description'    => $description,
    'category'       => $category,
    'daily_rate'     => $dailyRate,
    'deposit_amount' => $deposit,
    'address_line'   => $addressLine,
    'city'           => $city,
    'state'          => $state,
    'postal_code'    => $postalCode,
]);

json_response(['success' => true, 'tool_id' => (int) $pdo->lastInsertId(), 'redirect' => '/owner/dashboard.php']);
