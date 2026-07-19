<?php
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/functions.php';
$__user = current_user();
$__pageTitle = $pageTitle ?? 'NeighbourShed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($__pageTitle) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="/" class="brand"><span class="brand-mark">NS</span> NeighbourShed</a>
    <div class="nav-links">
      <a href="/">Browse tools</a>
      <?php if ($__user): ?>
        <?php if ($__user['role'] === 'tool_owner'): ?>
          <a href="/owner/dashboard.php">My tools</a>
        <?php elseif ($__user['role'] === 'renter'): ?>
          <a href="/renter/dashboard.php">My bookings</a>
        <?php elseif ($__user['role'] === 'admin'): ?>
          <a href="/admin/dashboard.php">Admin</a>
        <?php endif; ?>
        <span class="nav-user">Hi, <?= e(explode(' ', $__user['name'])[0]) ?></span>
        <a href="/logout.php" class="btn btn-ghost btn-sm">Log out</a>
      <?php else: ?>
        <a href="/login.php">Log in</a>
        <a href="/register.php" class="btn btn-primary btn-sm">Join free</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
