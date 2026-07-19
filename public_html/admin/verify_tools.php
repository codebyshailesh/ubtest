<?php
$pageTitle = 'Verify tools — Admin';
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['admin']);
$pdo = get_db();

$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected'], true)) $filter = 'pending';

$stmt = $pdo->prepare(
    "SELECT t.*, u.name AS owner_name, u.phone AS owner_phone
     FROM tools t JOIN users u ON u.id = t.owner_id
     WHERE t.status = :status ORDER BY t.created_at ASC"
);
$stmt->execute(['status' => $filter]);
$tools = $stmt->fetchAll();
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="dash-header">
      <div>
        <h1 class="mb-0">Verify tool listings</h1>
        <p class="mb-0">Confirm a listing is a real, safe tool before it's shown to renters.</p>
      </div>
      <div class="flex gap-8">
        <a href="?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-ghost' ?>">Pending</a>
        <a href="?status=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-primary' : 'btn-ghost' ?>">Approved</a>
        <a href="?status=rejected" class="btn btn-sm <?= $filter === 'rejected' ? 'btn-primary' : 'btn-ghost' ?>">Rejected</a>
      </div>
    </div>

    <?php if (!$tools): ?>
      <div class="card empty-state">No <?= e($filter) ?> listings right now.</div>
    <?php else: ?>
      <div class="grid grid-cols-2">
        <?php foreach ($tools as $t): ?>
          <div class="card" id="tool-<?= (int) $t['id'] ?>">
            <div class="tool-meta">
              <h3 class="mt-0 mb-0"><?= e($t['name']) ?></h3>
              <span class="<?= status_badge_class($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span>
            </div>
            <p class="mb-0" style="font-size:0.88rem;"><?= e($t['category']) ?> · <?= money((float) $t['daily_rate']) ?>/day</p>
            <p style="font-size:0.88rem;"><?= e($t['description'] ?: 'No description provided.') ?></p>
            <p class="mb-0 muted" style="font-size:0.82rem;">
              Owner: <?= e($t['owner_name']) ?> (<?= e($t['owner_phone']) ?>)<br>
              Pickup: <?= e($t['address_line']) ?>, <?= e($t['city']) ?>, <?= e($t['state']) ?> <?= e($t['postal_code']) ?>
            </p>
            <?php if ($filter === 'pending'): ?>
              <label class="mt-0" for="notes-<?= (int) $t['id'] ?>" style="margin-top:14px;">Note (optional, shown to owner if rejected)</label>
              <input type="text" id="notes-<?= (int) $t['id'] ?>" placeholder="e.g. Add a clearer photo">
              <div class="flex gap-8" style="margin-top:12px;">
                <button class="btn btn-primary btn-sm" onclick="decide(<?= (int) $t['id'] ?>, 'approved')">Approve</button>
                <button class="btn btn-danger btn-sm" onclick="decide(<?= (int) $t['id'] ?>, 'rejected')">Reject</button>
              </div>
            <?php elseif ($t['admin_notes']): ?>
              <p class="mb-0" style="font-size:0.82rem; color:var(--danger);">Note: <?= e($t['admin_notes']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
async function decide(id, decision) {
  const notesEl = document.getElementById('notes-' + id);
  const notes = notesEl ? notesEl.value : '';
  const res = await fetch('/api/admin/verify_tool.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, decision, notes })
  });
  const data = await res.json();
  if (!res.ok) { alert(data.error || 'Something went wrong.'); return; }
  document.getElementById('tool-' + id).remove();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
