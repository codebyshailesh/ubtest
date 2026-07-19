<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$bookingId = (int) ($input['booking_id'] ?? 0);
$newStatus = $input['status'] ?? '';

$allowed = ['confirmed', 'rejected', 'cancelled', 'completed'];
if (!$bookingId || !in_array($newStatus, $allowed, true)) {
    json_response(['error' => 'Invalid request.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare(
    'SELECT b.*, t.owner_id FROM bookings b JOIN tools t ON t.id = b.tool_id WHERE b.id = :id'
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    json_response(['error' => 'Booking not found.'], 404);
}

$isOwner  = (int) $booking['owner_id'] === (int) $user['id'];
$isRenter = (int) $booking['renter_id'] === (int) $user['id'];

if (!$isOwner && !$isRenter) {
    json_response(['error' => 'You do not have access to this booking.'], 403);
}

// Owners confirm/reject/complete; renters may only cancel a pending request.
$ownerActions  = ['confirmed', 'rejected', 'completed'];
$renterActions = ['cancelled'];

if ($isOwner && !in_array($newStatus, $ownerActions, true)) {
    json_response(['error' => 'Owners can confirm, reject, or complete a booking.'], 403);
}
if ($isRenter && !$isOwner && !in_array($newStatus, $renterActions, true)) {
    json_response(['error' => 'Renters can only cancel a pending request.'], 403);
}
if ($newStatus === 'cancelled' && $booking['status'] !== 'pending') {
    json_response(['error' => 'Only pending requests can be cancelled.'], 422);
}

$update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
$update->execute(['status' => $newStatus, 'id' => $bookingId]);

json_response(['success' => true, 'status' => $newStatus]);
