<?php
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['renter']);
$pdo = get_db();

$bookings = $pdo->prepare(
    "SELECT b.*, t.name AS tool_name, t.city, t.address_line, u.name AS owner_name, u.phone AS owner_phone
     FROM bookings b
     JOIN tools t ON t.id = b.tool_id
     JOIN users u ON u.id = t.owner_id
     WHERE b.renter_id = :id
     ORDER BY FIELD(b.status,'pending','confirmed','completed','rejected','cancelled'), b.start_date"
);
$bookings->execute(['id' => $user['id']]);
$bookings = $bookings->fetchAll();

$active = count(array_filter($bookings, fn($b) => in_array($b['status'], ['pending', 'confirmed'], true)));
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="dash-header">
      <div>
        <h1 class="mb-0">Your bookings</h1>
        <p class="mb-0">Track requests you've sent to tool owners nearby.</p>
      </div>
      <a href="/#browse" class="btn btn-primary">Browse more tools</a>
    </div>

    <div class="stat-row">
      <div class="card stat"><div class="stat-num"><?= count($bookings) ?></div><div class="stat-label">Total requests</div></div>
      <div class="card stat"><div class="stat-num"><?= $active ?></div><div class="stat-label">Active / pending</div></div>
    </div>

    <?php if (!$bookings): ?>
      <div class="card empty-state">You haven't requested any tools yet. <a href="/#browse">Find one nearby</a>.</div>
    <?php else: ?>
      <div class="card" style="padding:0; overflow-x:auto;">
        <table>
          <thead><tr><th>Tool</th><th>Owner</th><th>Dates</th><th>Total (COD)</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= e($b['tool_name']) ?><br><span class="muted" style="font-size:0.8rem;"><?= e($b['city']) ?></span></td>
              <td><?= e($b['owner_name']) ?><br><span class="muted" style="font-size:0.8rem;"><?= e($b['owner_phone']) ?></span></td>
              <td><?= e($b['start_date']) ?> → <?= e($b['end_date']) ?> (<?= (int) $b['total_days'] ?>d)</td>
              <td><?= money((float) $b['total_price']) ?></td>
              <td><span class="<?= status_badge_class($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?= (int) $b['id'] ?>)">Cancel</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
async function cancelBooking(id) {
  if (!confirm('Cancel this booking request?')) return;
  const res = await fetch('/api/bookings/update_status.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ booking_id: id, status: 'cancelled' })
  });
  const data = await res.json();
  if (!res.ok) { alert(data.error || 'Something went wrong.'); return; }
  location.reload();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
