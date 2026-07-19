<?php
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();

$city     = trim($_GET['city'] ?? ($__user['city'] ?? ''));
$category = trim($_GET['category'] ?? '');

$sql = "SELECT t.*, u.name AS owner_name
        FROM tools t JOIN users u ON u.id = t.owner_id
        WHERE t.status = 'approved'";
$params = [];

if ($city !== '') {
  $sql .= ' AND t.city LIKE :city';
  $params['city'] = '%' . $city . '%';
}
if ($category !== '') {
  $sql .= ' AND t.category = :category';
  $params['category'] = $category;
}
$sql .= ' ORDER BY t.created_at DESC LIMIT 24';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tools = $stmt->fetchAll();

$icons = [
  'Power Tools' => '🔌',
  'Hand Tools' => '🔧',
  'Gardening' => '🌱',
  'Ladders & Access' => '🪜',
  'Cleaning Equipment' => '🧹',
  'Painting & Decorating' => '🖌️',
  'Automotive' => '🚗',
  'Other' => '🧰',
];
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow">● Verified neighbourhood listings</span>
      <h1>Borrow the tool your neighbour already owns.</h1>
      <p style="font-size:1.05rem; max-width: 46ch;">
        NeighbourShed connects tool owners with people nearby who need a drill,
        a ladder, or a lawnmower for a day — not a lifetime. Every listing is
        checked by an admin before it goes live, and every borrow is settled
        in cash when the tool changes hands.
      </p>
      <div class="hero-actions">
        <a href="#browse" class="btn btn-primary">Browse tools nearby</a>
        <?php if (!$__user): ?>
          <a href="/register.php" class="btn btn-ghost">List your tools</a>
        <?php elseif ($__user['role'] === 'tool_owner'): ?>
          <a href="/owner/add_tool.php" class="btn btn-ghost">List a new tool</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="pegboard" aria-hidden="true">
      <div class="peg-tool hook" style="top: 18%; left: 14%;">
        <div class="peg-icon">🔧</div>Wrench
      </div>
      <div class="peg-tool hook" style="top: 14%; left: 58%;">
        <div class="peg-icon">🪚</div>Saw
      </div>
      <div class="peg-tool hook" style="top: 52%; left: 30%;">
        <div class="peg-icon">🪜</div>Ladder
      </div>
      <div class="peg-tool hook" style="top: 58%; left: 70%;">
        <div class="peg-icon">🔌</div>Drill
      </div>
    </div>
  </div>
</section>

<section class="section" id="browse">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Tools near you</h2>
        <p class="mb-0">Filtered by neighbourhood, closest listings first.</p>
      </div>
    </div>

    <form method="get" class="card flex gap-12" style="margin-bottom:28px; flex-wrap:wrap;">
      <div style="flex:1; min-width:180px;">
        <label class="mt-0">City / neighbourhood</label>
        <input type="text" name="city" value="<?= e($city) ?>" placeholder="e.g. Lalitpur">
      </div>
      <div style="flex:1; min-width:180px;">
        <label class="mt-0">Category</label>
        <select name="category">
          <option value="">All categories</option>
          <?php foreach (TOOL_CATEGORIES as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="align-self:flex-end;">
        <button class="btn btn-primary" type="submit">Filter</button>
      </div>
    </form>

    <?php if (!$tools): ?>
      <div class="empty-state card">
        No verified tools match that search yet. Try a wider area, or
        <a href="/register.php">list your own tool</a> to get things started.
      </div>
    <?php else: ?>
      <div class="grid grid-cols-3">
        <?php foreach ($tools as $tool): ?>
          <a href="/tool.php?id=<?= (int) $tool['id'] ?>" class="card tool-card">
            <div class="tool-thumb"><?= $icons[$tool['category']] ?? '🧰' ?></div>
            <div class="tool-body">
              <div class="tool-meta">
                <h3 class="mt-0 mb-0" style="font-size:1.05rem;"><?= e($tool['name']) ?></h3>
              </div>
              <p class="mb-0" style="font-size:0.86rem;"><?= e(mb_strimwidth($tool['description'] ?? '', 0, 80, '…')) ?></p>
              <div class="tool-meta">
                <span class="tool-rate"><?= money((float) $tool['daily_rate']) ?>/day</span>
                <span class="tag"><?= e($tool['city']) ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>