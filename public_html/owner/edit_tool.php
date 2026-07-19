<?php
$pageTitle = 'Edit tool — NeighbourShed';
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['tool_owner']);

$toolId = (int) ($_GET['id'] ?? 0);
$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM tools WHERE id = :id AND owner_id = :owner_id');
$stmt->execute(['id' => $toolId, 'owner_id' => $user['id']]);
$tool = $stmt->fetch();

if (!$tool) {
    echo '<div class="container section"><div class="card empty-state">Tool not found.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="card form-card wide">
      <h1 style="font-size:1.6rem;">Edit tool</h1>
      <p class="field-hint">Saving changes sends this listing back for admin re-verification.</p>

      <div id="formAlert"></div>

      <form id="toolForm">
        <input type="hidden" name="id" value="<?= (int) $tool['id'] ?>">
        <label class="mt-0" for="name">Tool name</label>
        <input type="text" id="name" name="name" required value="<?= e($tool['name']) ?>">

        <label for="category">Category</label>
        <select id="category" name="category" required>
          <?php foreach (TOOL_CATEGORIES as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $tool['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($tool['description'] ?? '') ?></textarea>

        <div class="field-row">
          <div>
            <label for="daily_rate">Daily rate (Rs.)</label>
            <input type="number" id="daily_rate" name="daily_rate" min="1" step="0.01" required value="<?= e((string) $tool['daily_rate']) ?>">
          </div>
          <div>
            <label for="deposit_amount">Refundable deposit (Rs.)</label>
            <input type="number" id="deposit_amount" name="deposit_amount" min="0" step="0.01" value="<?= e((string) $tool['deposit_amount']) ?>">
          </div>
        </div>

        <fieldset>
          <legend>Pickup address</legend>
          <label class="mt-0" for="address_line">Street address</label>
          <input type="text" id="address_line" name="address_line" required value="<?= e($tool['address_line']) ?>">
          <div class="field-row">
            <div>
              <label for="city">City / neighbourhood</label>
              <input type="text" id="city" name="city" required value="<?= e($tool['city']) ?>">
            </div>
            <div>
              <label for="state">State / province</label>
              <input type="text" id="state" name="state" required value="<?= e($tool['state']) ?>">
            </div>
          </div>
          <label for="postal_code">Postal code</label>
          <input type="text" id="postal_code" name="postal_code" required value="<?= e($tool['postal_code']) ?>">
        </fieldset>

        <button type="submit" class="btn btn-accent btn-block" style="margin-top:20px;">Save changes</button>
      </form>
    </div>
  </div>
</section>

<script>
document.getElementById('toolForm').addEventListener('submit', async function (evt) {
  evt.preventDefault();
  const alertBox = document.getElementById('formAlert');
  alertBox.innerHTML = '';
  const payload = Object.fromEntries(new FormData(evt.target).entries());
  const btn = evt.target.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    const res = await fetch('/api/tools/update.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Something went wrong.');
    window.location.href = data.redirect || '/owner/dashboard.php';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
    btn.disabled = false; btn.textContent = 'Save changes';
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
