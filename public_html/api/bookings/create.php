<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = api_require_role(['renter']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toolId    = (int) ($input['tool_id'] ?? 0);
$startDate = $input['start_date'] ?? '';
$endDate   = $input['end_date'] ?? '';

if (!$toolId || !$startDate || !$endDate) {
    json_response(['error' => 'Select a start and end date.'], 422);
}

$today = new DateTime('today');
try {
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
} catch (Exception $e) {
    json_response(['error' => 'Invalid dates.'], 422);
}

if ($start < $today) {
    json_response(['error' => 'The start date cannot be in the past.'], 422);
}
if ($end < $start) {
    json_response(['error' => 'The end date must be on or after the start date.'], 422);
}

$pdo = get_db();

$toolStmt = $pdo->prepare("SELECT * FROM tools WHERE id = :id AND status = 'approved'");
$toolStmt->execute(['id' => $toolId]);
$tool = $toolStmt->fetch();

if (!$tool) {
    json_response(['error' => 'This tool is not available to borrow.'], 404);
}
if ((int) $tool['owner_id'] === (int) $user['id']) {
    json_response(['error' => 'You cannot borrow your own tool.'], 422);
}

// Reject overlapping date ranges against existing pending/confirmed bookings.
$overlapStmt = $pdo->prepare(
    "SELECT id FROM bookings
     WHERE tool_id = :tool_id AND status IN ('pending','confirmed')
     AND start_date <= :end_date AND end_date >= :start_date"
);
$overlapStmt->execute([
    'tool_id'    => $toolId,
    'start_date' => $start->format('Y-m-d'),
    'end_date'   => $end->format('Y-m-d'),
]);
if ($overlapStmt->fetch()) {
    json_response(['error' => 'This tool is already booked for part of that date range.'], 409);
}

$days  = days_between($start->format('Y-m-d'), $end->format('Y-m-d'));
$total = round($days * (float) $tool['daily_rate'], 2);

$insert = $pdo->prepare(
    'INSERT INTO bookings (tool_id, renter_id, start_date, end_date, total_days, total_price, payment_method, status)
     VALUES (:tool_id, :renter_id, :start_date, :end_date, :total_days, :total_price, :payment_method, :status)'
);
$insert->execute([
    'tool_id'        => $toolId,
    'renter_id'      => $user['id'],
    'start_date'     => $start->format('Y-m-d'),
    'end_date'       => $end->format('Y-m-d'),
    'total_days'     => $days,
    'total_price'    => $total,
    'payment_method' => 'cash_on_delivery',
    'status'         => 'pending',
]);

json_response(['success' => true, 'booking_id' => (int) $pdo->lastInsertId(), 'total_price' => $total]);
