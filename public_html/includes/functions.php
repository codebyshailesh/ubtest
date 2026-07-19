<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Number of whole days between two Y-m-d dates, inclusive of the start day. */
function days_between(string $start, string $end): int
{
    $s = new DateTime($start);
    $e = new DateTime($end);
    return max(1, (int) $s->diff($e)->days + 1);
}

function money(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'approved', 'confirmed', 'completed' => 'badge badge-success',
        'pending' => 'badge badge-pending',
        'rejected', 'cancelled' => 'badge badge-danger',
        default => 'badge',
    };
}

const TOOL_CATEGORIES = [
    'Power Tools', 'Hand Tools', 'Gardening', 'Ladders & Access',
    'Cleaning Equipment', 'Painting & Decorating', 'Automotive', 'Other',
];
