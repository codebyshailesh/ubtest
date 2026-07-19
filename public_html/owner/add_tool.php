<?php
$pageTitle = 'List a tool — NeighbourShed';
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['tool_owner']);
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="card form-card wide">
      <h1 style="font-size:1.6rem;">List a tool</h1>
      <p>New listings are reviewed by an admin before they appear in search — usually within a day.</p>

      <div id="formAlert"></div>

      <form id="toolForm">
        <label for="name" class="mt-0">Tool name</label>
        <input type="text" id="name" name="name" required placeholder="e.g. Bosch Corded Drill">

        <label for="category">Category</label>
        <select id="category" name="category" required>
          <?php foreach (TOOL_CATEGORIES as $cat): ?>
            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Condition, accessories included, any usage notes…"></textarea>

        <div class="field-row">
          <div>
            <label for="daily_rate">Daily rate (Rs.)</label>
            <input type="number" id="daily_rate" name="daily_rate" min="1" step="0.01" required>
          </div>
          <div>
            <label for="deposit_amount">Refundable deposit (Rs., optional)</label>
            <input type="number" id="deposit_amount" name="deposit_amount" min="0" step="0.01" value="0">
          </div>
        </div>

        <fieldset>
          <legend>Pickup address</legend>
          <label class="mt-0" for="address_line">Street address</label>
          <input type="text" id="address_line" name="address_line" value="<?= e($user['address_line']) ?>" required>
          <div class="field-row">
            <div>
              <label for="city">City / neighbourhood</label>
              <input type="text" id="city" name="city" value="<?= e($user['city']) ?>" required>
            </div>
            <div>
              <label for="state">State / province</label>
              <input type="text" id="state" name="state" value="<?= e($user['state']) ?>" required>
            </div>
          </div>
          <label for="postal_code">Postal code</label>
          <input type="text" id="postal_code" name="postal_code" value="<?= e($user['postal_code']) ?>" required>
          <p class="field-hint">Defaults to your account address — change it if the tool is picked up elsewhere.</p>
        </fieldset>

        <button type="submit" class="btn btn-accent btn-block" style="margin-top:20px;">Submit for verification</button>
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
  btn.disabled = true; btn.textContent = 'Submitting…';

  try {
    const res = await fetch('/api/tools/create.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Something went wrong.');
    window.location.href = data.redirect || '/owner/dashboard.php';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
    btn.disabled = false; btn.textContent = 'Submit for verification';
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
