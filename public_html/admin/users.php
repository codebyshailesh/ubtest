<?php
$pageTitle = 'Users — Admin';
require_once __DIR__ . '/../includes/header.php';
$user = require_role(['admin']);
$pdo = get_db();

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users';
$params = [];
if (in_array($roleFilter, ['tool_owner', 'renter', 'admin'], true)) {
    $sql .= ' WHERE role = :role';
    $params['role'] = $roleFilter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="dash-header">
      <div>
        <h1 class="mb-0">Users</h1>
        <p class="mb-0"><?= count($users) ?> registered accounts.</p>
      </div>
      <div class="flex gap-8">
        <a href="?role=" class="btn btn-sm <?= $roleFilter === '' ? 'btn-primary' : 'btn-ghost' ?>">All</a>
        <a href="?role=tool_owner" class="btn btn-sm <?= $roleFilter === 'tool_owner' ? 'btn-primary' : 'btn-ghost' ?>">Owners</a>
        <a href="?role=renter" class="btn btn-sm <?= $roleFilter === 'renter' ? 'btn-primary' : 'btn-ghost' ?>">Renters</a>
        <a href="?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-ghost' ?>">Admins</a>
      </div>
    </div>

    <div class="card" style="padding:0; overflow-x:auto;">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Location</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="tag"><?= e(str_replace('_', ' ', $u['role'])) ?></span></td>
            <td><?= e($u['city']) ?>, <?= e($u['state']) ?></td>
            <td class="muted"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
