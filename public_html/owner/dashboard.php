<?php
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['tool_owner']);
$pdo = get_db();

$tools = $pdo->prepare('SELECT * FROM tools WHERE owner_id = :id ORDER BY created_at DESC');
$tools->execute(['id' => $user['id']]);
$tools = $tools->fetchAll();

$bookings = $pdo->prepare(
    "SELECT b.*, t.name AS tool_name, u.name AS renter_name, u.phone AS renter_phone
     FROM bookings b
     JOIN tools t ON t.id = b.tool_id
     JOIN users u ON u.id = b.renter_id
     WHERE t.owner_id = :id
     ORDER BY FIELD(b.status,'pending','confirmed','completed','rejected','cancelled'), b.start_date"
);
$bookings->execute(['id' => $user['id']]);
$bookings = $bookings->fetchAll();

$counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
foreach ($tools as $t) { $counts[$t['status']] = ($counts[$t['status']] ?? 0) + 1; }
$pendingBookings = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="dash-header">
      <div>
        <h1 class="mb-0">Your tool shed</h1>
        <p class="mb-0">Manage listings and respond to borrow requests.</p>
      </div>
      <a href="/owner/add_tool.php" class="btn btn-accent">+ List a new tool</a>
    </div>

    <div class="stat-row">
      <div class="card stat"><div class="stat-num"><?= (int) ($counts['approved'] ?? 0) ?></div><div class="stat-label">Live listings</div></div>
      <div class="card stat"><div class="stat-num"><?= (int) ($counts['pending'] ?? 0) ?></div><div class="stat-label">Awaiting verification</div></div>
      <div class="card stat"><div class="stat-num"><?= $pendingBookings ?></div><div class="stat-label">Borrow requests to review</div></div>
    </div>

    <h2>Borrow requests</h2>
    <?php if (!$bookings): ?>
      <div class="card empty-state">No borrow requests yet.</div>
    <?php else: ?>
      <div class="card" style="padding:0; overflow-x:auto;">
        <table>
          <thead><tr><th>Tool</th><th>Renter</th><th>Dates</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= e($b['tool_name']) ?></td>
              <td><?= e($b['renter_name']) ?><br><span class="muted" style="font-size:0.8rem;"><?= e($b['renter_phone']) ?></span></td>
              <td><?= e($b['start_date']) ?> → <?= e($b['end_date']) ?> (<?= (int) $b['total_days'] ?>d)</td>
              <td><?= money((float) $b['total_price']) ?> <span class="muted" style="font-size:0.75rem;">COD</span></td>
              <td><span class="<?= status_badge_class($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <button class="btn btn-primary btn-sm" onclick="setStatus(<?= (int) $b['id'] ?>, 'confirmed')">Confirm</button>
                  <button class="btn btn-danger btn-sm" onclick="setStatus(<?= (int) $b['id'] ?>, 'rejected')">Reject</button>
                <?php elseif ($b['status'] === 'confirmed'): ?>
                  <button class="btn btn-ghost btn-sm" onclick="setStatus(<?= (int) $b['id'] ?>, 'completed')">Mark returned</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <h2 style="margin-top:40px;">Your listings</h2>
    <?php if (!$tools): ?>
      <div class="card empty-state">You haven't listed any tools yet. <a href="/owner/add_tool.php">List your first one</a>.</div>
    <?php else: ?>
      <div class="grid grid-cols-3">
        <?php foreach ($tools as $t): ?>
          <div class="card tool-card">
            <div class="tool-body">
              <div class="tool-meta">
                <h3 class="mt-0 mb-0" style="font-size:1.05rem;"><?= e($t['name']) ?></h3>
                <span class="<?= status_badge_class($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span>
              </div>
              <p class="mb-0" style="font-size:0.86rem;"><?= e($t['category']) ?> · <?= money((float) $t['daily_rate']) ?>/day</p>
              <?php if ($t['status'] === 'rejected' && $t['admin_notes']): ?>
                <p class="mb-0" style="font-size:0.8rem; color:var(--danger);">Admin note: <?= e($t['admin_notes']) ?></p>
              <?php endif; ?>
              <div class="flex gap-8" style="margin-top:8px;">
                <a href="/owner/edit_tool.php?id=<?= (int) $t['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                <a href="/tool.php?id=<?= (int) $t['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                <button class="btn btn-danger btn-sm" onclick="deleteTool(<?= (int) $t['id'] ?>)">Delete</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
async function setStatus(bookingId, status) {
  if (!confirm(`Mark this booking as ${status}?`)) return;
  const res = await fetch('/api/bookings/update_status.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ booking_id: bookingId, status })
  });
  const data = await res.json();
  if (!res.ok) { alert(data.error || 'Something went wrong.'); return; }
  location.reload();
}
async function deleteTool(id) {
  if (!confirm('Delete this tool listing? This cannot be undone.')) return;
  const res = await fetch('/api/tools/delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });
  const data = await res.json();
  if (!res.ok) { alert(data.error || 'Something went wrong.'); return; }
  location.reload();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
