<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = api_require_role(['tool_owner']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toolId = (int) ($input['id'] ?? 0);

$pdo = get_db();
$check = $pdo->prepare('SELECT * FROM tools WHERE id = :id AND owner_id = :owner_id');
$check->execute(['id' => $toolId, 'owner_id' => $user['id']]);
$tool = $check->fetch();

if (!$tool) {
    json_response(['error' => 'Tool not found.'], 404);
}

$name        = trim($input['name'] ?? $tool['name']);
$description = trim($input['description'] ?? $tool['description']);
$category    = trim($input['category'] ?? $tool['category']);
$dailyRate   = (float) ($input['daily_rate'] ?? $tool['daily_rate']);
$deposit     = (float) ($input['deposit_amount'] ?? $tool['deposit_amount']);
$addressLine = trim($input['address_line'] ?? $tool['address_line']);
$city        = trim($input['city'] ?? $tool['city']);
$state       = trim($input['state'] ?? $tool['state']);
$postalCode  = trim($input['postal_code'] ?? $tool['postal_code']);

if ($name === '' || !in_array($category, TOOL_CATEGORIES, true) || $dailyRate <= 0) {
    json_response(['error' => 'Check the tool name, category, and daily rate.'], 422);
}

// Any edit needs a fresh admin check before it goes back live.
$stmt = $pdo->prepare(
    'UPDATE tools SET name = :name, description = :description, category = :category,
        daily_rate = :daily_rate, deposit_amount = :deposit_amount,
        address_line = :address_line, city = :city, state = :state, postal_code = :postal_code,
        status = "pending", admin_notes = NULL
     WHERE id = :id AND owner_id = :owner_id'
);
$stmt->execute([
    'name' => $name, 'description' => $description, 'category' => $category,
    'daily_rate' => $dailyRate, 'deposit_amount' => $deposit,
    'address_line' => $addressLine, 'city' => $city, 'state' => $state, 'postal_code' => $postalCode,
    'id' => $toolId, 'owner_id' => $user['id'],
]);

json_response(['success' => true, 'redirect' => '/owner/dashboard.php']);
