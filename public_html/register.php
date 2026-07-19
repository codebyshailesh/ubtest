<?php
$pageTitle = 'Create your account — NeighbourShed';
require_once __DIR__ . '/includes/header.php';
if ($__user) redirect('/');
?>
<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="card form-card wide">
      <h1 style="font-size:1.7rem;">Join NeighbourShed</h1>
      <p>Lend the tools sitting in your garage, or borrow what you need from people nearby.</p>

      <div id="formAlert"></div>

      <form id="registerForm">
        <fieldset>
          <legend>I want to</legend>
          <div class="role-toggle">
            <div class="role-option">
              <input type="radio" name="role" id="role_renter" value="renter" checked>
              <label for="role_renter">Borrow tools</label>
            </div>
            <div class="role-option">
              <input type="radio" name="role" id="role_owner" value="tool_owner">
              <label for="role_owner">Lend my tools</label>
            </div>
          </div>
        </fieldset>

        <div class="field-row">
          <div>
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" required placeholder="Aayush Shrestha">
          </div>
          <div>
            <label for="phone">Phone number</label>
            <input type="tel" id="phone" name="phone" required placeholder="98XXXXXXXX">
          </div>
        </div>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="you@example.com">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">

        <fieldset>
          <legend>Address — used to show tools near you</legend>
          <label for="address_line" class="mt-0">Street address</label>
          <input type="text" id="address_line" name="address_line" required placeholder="House no., street, area">
          <div class="field-row">
            <div>
              <label for="city">City / neighbourhood</label>
              <input type="text" id="city" name="city" required placeholder="Kathmandu">
            </div>
            <div>
              <label for="state">State / province</label>
              <input type="text" id="state" name="state" required placeholder="Bagmati">
            </div>
          </div>
          <label for="postal_code">Postal code</label>
          <input type="text" id="postal_code" name="postal_code" required placeholder="44600">
          <p class="field-hint">Your address is required because tools and borrow requests are matched by location — it's never shown publicly, only the city.</p>
        </fieldset>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">Create account</button>
      </form>
      <p style="margin-top:16px; font-size:0.88rem;">Already have an account? <a href="/login.php">Log in</a></p>
    </div>
  </div>
</section>

<script>
document.getElementById('registerForm').addEventListener('submit', async function (evt) {
  evt.preventDefault();
  const alertBox = document.getElementById('formAlert');
  alertBox.innerHTML = '';
  const form = evt.target;
  const payload = Object.fromEntries(new FormData(form).entries());
  const btn = form.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Creating account…';

  try {
    const res = await fetch('/api/auth/register.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Something went wrong.');
    window.location.href = data.redirect || '/';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
    btn.disabled = false; btn.textContent = 'Create account';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
