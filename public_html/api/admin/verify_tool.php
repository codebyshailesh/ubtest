<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = api_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toolId = (int) ($input['id'] ?? 0);
$decision = $input['decision'] ?? '';
$notes = trim($input['notes'] ?? '');

if (!$toolId || !in_array($decision, ['approved', 'rejected'], true)) {
    json_response(['error' => 'Invalid request.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('UPDATE tools SET status = :status, admin_notes = :notes WHERE id = :id');
$stmt->execute(['status' => $decision, 'notes' => $notes ?: null, 'id' => $toolId]);

if ($stmt->rowCount() === 0) {
    json_response(['error' => 'Tool not found.'], 404);
}

json_response(['success' => true]);
