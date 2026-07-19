<?php
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['admin']);
$pdo = get_db();

$counts = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM tools WHERE status='pending') AS pending_tools,
        (SELECT COUNT(*) FROM tools WHERE status='approved') AS approved_tools,
        (SELECT COUNT(*) FROM users WHERE role='tool_owner') AS owners,
        (SELECT COUNT(*) FROM users WHERE role='renter') AS renters,
        (SELECT COUNT(*) FROM bookings) AS bookings"
)->fetch();

$pending = $pdo->query(
    "SELECT t.*, u.name AS owner_name FROM tools t JOIN users u ON u.id = t.owner_id
     WHERE t.status = 'pending' ORDER BY t.created_at ASC LIMIT 5"
)->fetchAll();
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="dash-header">
      <div>
        <h1 class="mb-0">Admin overview</h1>
        <p class="mb-0">Verify listings and keep an eye on activity.</p>
      </div>
      <a href="/admin/verify_tools.php" class="btn btn-primary">Review pending tools</a>
    </div>

    <div class="stat-row">
      <div class="card stat"><div class="stat-num"><?= (int) $counts['pending_tools'] ?></div><div class="stat-label">Pending verification</div></div>
      <div class="card stat"><div class="stat-num"><?= (int) $counts['approved_tools'] ?></div><div class="stat-label">Live listings</div></div>
      <div class="card stat"><div class="stat-num"><?= (int) $counts['owners'] ?></div><div class="stat-label">Tool owners</div></div>
      <div class="card stat"><div class="stat-num"><?= (int) $counts['renters'] ?></div><div class="stat-label">Renters</div></div>
      <div class="card stat"><div class="stat-num"><?= (int) $counts['bookings'] ?></div><div class="stat-label">Total bookings</div></div>
    </div>

    <div class="section-head">
      <h2 class="mb-0">Newest pending listings</h2>
      <a href="/admin/verify_tools.php">See all →</a>
    </div>
    <?php if (!$pending): ?>
      <div class="card empty-state">Nothing waiting on review right now.</div>
    <?php else: ?>
      <div class="card" style="padding:0;">
        <table>
          <thead><tr><th>Tool</th><th>Owner</th><th>Location</th><th>Rate</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($pending as $t): ?>
            <tr>
              <td><?= e($t['name']) ?></td>
              <td><?= e($t['owner_name']) ?></td>
              <td><?= e($t['city']) ?></td>
              <td><?= money((float) $t['daily_rate']) ?>/day</td>
              <td><a href="/admin/verify_tools.php" class="btn btn-ghost btn-sm">Review</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <p style="margin-top:24px;"><a href="/admin/users.php">View all users →</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
