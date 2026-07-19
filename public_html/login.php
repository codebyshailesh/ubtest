<?php
$pageTitle = 'Log in — NeighbourShed';
require_once __DIR__ . '/includes/header.php';
if ($__user) redirect('/');
?>
<section class="section" style="padding-top:56px;">
  <div class="container">
    <div class="card form-card">
      <h1 style="font-size:1.7rem;">Welcome back</h1>
      <p>Log in to manage your listings or bookings.</p>

      <div id="formAlert"></div>

      <form id="loginForm">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="you@example.com">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Your password">
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">Log in</button>
      </form>
      <p style="margin-top:16px; font-size:0.88rem;">New here? <a href="/register.php">Create an account</a></p>
    </div>
  </div>
</section>

<script>
document.getElementById('loginForm').addEventListener('submit', async function (evt) {
  evt.preventDefault();
  const alertBox = document.getElementById('formAlert');
  alertBox.innerHTML = '';
  const payload = Object.fromEntries(new FormData(evt.target).entries());
  const btn = evt.target.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Logging in…';

  try {
    const res = await fetch('/api/auth/login.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Invalid email or password.');
    window.location.href = data.redirect || '/';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${err.message}</div>`;
    btn.disabled = false; btn.textContent = 'Log in';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
