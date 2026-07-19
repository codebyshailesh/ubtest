<?php
require_once __DIR__ . '/includes/header.php';

$toolId = (int) ($_GET['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare(
    'SELECT t.*, u.name AS owner_name, u.phone AS owner_phone
     FROM tools t JOIN users u ON u.id = t.owner_id
     WHERE t.id = :id'
);
$stmt->execute(['id' => $toolId]);
$tool = $stmt->fetch();

if (!$tool || ($tool['status'] !== 'approved' && (!$__user || $__user['id'] != $tool['owner_id']))) {
    http_response_code(404);
    echo '<div class="container section"><div class="card empty-state">This tool listing isn\'t available.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Existing confirmed/pending bookings, so the renter can see busy dates.
$busyStmt = $pdo->prepare(
    "SELECT start_date, end_date FROM bookings
     WHERE tool_id = :id AND status IN ('pending','confirmed')
     ORDER BY start_date"
);
$busyStmt->execute(['id' => $toolId]);
$busyRanges = $busyStmt->fetchAll();

$isRenter = $__user && $__user['role'] === 'renter';
$isOwnTool = $__user && (int) $__user['id'] === (int) $tool['owner_id'];
$pageTitle = $tool['name'] . ' — NeighbourShed';
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="grid grid-cols-2" style="grid-template-columns: 1.3fr 1fr; align-items:flex-start;">
      <div>
        <span class="<?= status_badge_class($tool['status']) ?>"><?= e(ucfirst($tool['status'])) ?></span>
        <h1 style="margin-top:10px;"><?= e($tool['name']) ?></h1>
        <p class="flex gap-8" style="flex-wrap:wrap;">
          <span class="tag"><?= e($tool['category']) ?></span>
          <span class="tag"><?= e($tool['city']) ?>, <?= e($tool['state']) ?></span>
        </p>
        <p><?= nl2br(e($tool['description'] ?: 'No description provided.')) ?></p>

        <div class="card" style="margin-top:20px;">
          <h3 class="mt-0">Pickup details</h3>
          <p class="mb-0"><?= e($tool['address_line']) ?>, <?= e($tool['city']) ?>, <?= e($tool['state']) ?> <?= e($tool['postal_code']) ?></p>
          <p class="mb-0 muted">Owner: <?= e($tool['owner_name']) ?></p>
        </div>

        <?php if ($busyRanges): ?>
        <div class="card" style="margin-top:16px;">
          <h3 class="mt-0" style="font-size:1rem;">Already booked</h3>
          <?php foreach ($busyRanges as $r): ?>
            <p class="mb-0 muted" style="font-size:0.85rem;"><?= e($r['start_date']) ?> → <?= e($r['end_date']) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="tool-meta" style="margin-bottom:14px;">
          <span class="tool-rate" style="font-size:1.3rem;"><?= money((float) $tool['daily_rate']) ?><span class="muted" style="font-size:0.8rem;">/day</span></span>
          <?php if ((float) $tool['deposit_amount'] > 0): ?>
            <span class="tag">Deposit <?= money((float) $tool['deposit_amount']) ?></span>
          <?php endif; ?>
        </div>

        <?php if ($isOwnTool): ?>
          <p class="muted">This is your own listing. <a href="/owner/edit_tool.php?id=<?= (int) $tool['id'] ?>">Edit it</a>.</p>
        <?php elseif (!$__user): ?>
          <p class="muted">Log in as a renter to request this tool.</p>
          <a href="/login.php" class="btn btn-primary btn-block">Log in to borrow</a>
        <?php elseif (!$isRenter): ?>
          <p class="muted">Only renter accounts can request tools.</p>
        <?php elseif ($tool['status'] !== 'approved'): ?>
          <p class="muted">This listing is awaiting admin verification.</p>
        <?php else: ?>
          <div id="bookingAlert"></div>
          <form id="bookingForm">
            <input type="hidden" name="tool_id" value="<?= (int) $tool['id'] ?>">
            <label class="mt-0" for="start_date">Start date</label>
            <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
            <label for="end_date">End date</label>
            <input type="date" id="end_date" name="end_date" required min="<?= date('Y-m-d') ?>">
            <p class="field-hint" id="priceEstimate">Select dates to see the total.</p>
            <fieldset>
              <legend>Payment</legend>
              <p class="mb-0" style="font-size:0.86rem;">💵 Cash on delivery — pay the owner in person when you pick up the tool. No online payment is required.</p>
            </fieldset>
            <button type="submit" class="btn btn-accent btn-block" style="margin-top:18px;">Request to borrow</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
const dailyRate = <?= (float) $tool['daily_rate'] ?>;
const startInput = document.getElementById('start_date');
const endInput = document.getElementById('end_date');
const estimate = document.getElementById('priceEstimate');

function updateEstimate() {
  if (!startInput || !endInput || !startInput.value || !endInput.value) return;
  const start = new Date(startInput.value);
  const end = new Date(endInput.value);
  const days = Math.round((end - start) / 86400000) + 1;
  if (days > 0) {
    estimate.textContent = `${days} day${days > 1 ? 's' : ''} × Rs. ${dailyRate.toFixed(2)} = Rs. ${(days * dailyRate).toFixed(2)} (pay on delivery)`;
  } else {
    estimate.textContent = 'End date must be on or after the start date.';
  }
}
startInput?.addEventListener('change', () => { if (endInput.value < startInput.value) endInput.value = startInput.value; updateEstimate(); });
endInput?.addEventListener('change', updateEstimate);

document.getElementById('bookingForm')?.addEventListener('submit', async function (evt) {
  evt.preventDefault();
  const alertBox = document.getElementById('bookingAlert');
  alertBox.innerHTML = '';
  const payload = Object.fromEntries(new FormData(evt.target).entries());
  const btn = evt.target.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Sending request…';

  try {
    const res = await fetch('/api/bookings/create.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Could not create booking.');
    window.location.href = '/renter/dashboard.php';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
    btn.disabled = false; btn.textContent = 'Request to borrow';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
