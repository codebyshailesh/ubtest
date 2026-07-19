<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = api_require_role(['tool_owner']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toolId = (int) ($input['id'] ?? 0);

$pdo = get_db();
$stmt = $pdo->prepare('DELETE FROM tools WHERE id = :id AND owner_id = :owner_id');
$stmt->execute(['id' => $toolId, 'owner_id' => $user['id']]);

if ($stmt->rowCount() === 0) {
    json_response(['error' => 'Tool not found.'], 404);
}

json_response(['success' => true]);
