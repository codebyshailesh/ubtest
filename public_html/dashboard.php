<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_login();

redirect(match ($user['role']) {
    'tool_owner' => '/owner/dashboard.php',
    'admin'      => '/admin/dashboard.php',
    default      => '/renter/dashboard.php',
});
