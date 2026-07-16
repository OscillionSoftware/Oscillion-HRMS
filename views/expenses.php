<?php
$month = $filters['month'];
$prev = $month ? date('Y-m', strtotime($month . '-01 -1 month')) : '';
$next = $month ? date('Y-m', strtotime($month . '-01 +1 month')) : '';
$navQuery = fn($m) => '/expenses?' . http_build_query(array_filter(['month' => $m,
    'category' => $filters['category'], 'account_id' => $filters['account_id'], 'search' => $filters['search']]));
$diff = $prevTotal > 0 ? round(($summary['total'] - $prevTotal) / $prevTotal * 100) : null;
?>
<div class="page-head">
  <h1>Expenses</h1>
  <?php if ($month): ?>
  <div style="display:flex;align-items:center;gap:10px">
    <a class="btn btn-sm" href="<?= e($navQuery($prev)) ?>">←</a>
    <strong style="min-width:110px;text-align:center"><?= e(date('F Y', strtotime($month . '-01'))) ?></strong>
    <a class="btn btn-sm" href="<?= e($navQuery($next)) ?>">→</a>
    <a class="btn btn-sm" href="/expenses?month=">All Time</a>
  </div>
  <?php else: ?>
  <a class="btn btn-sm" href="<?= e($navQuery(date('Y-m'))) ?>">← Back to <?= e(date('F Y')) ?></a>
  <?php endif; ?>
</div>

<div class="stat-grid">
  <div class="stat">
    <div class="num">₹<?= number_format($summary['total'], 2) ?></div>
    <div class="label">
      Total (<?= $month ? e(date('M Y', strtotime($month . '-01'))) : 'all time' ?>)
      <?php if ($diff !== null): ?>
        · <span style="color:<?= $diff > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700">
          <?= $diff > 0 ? '▲' : '▼' ?> <?= abs($diff) ?>%</span> vs <?= e(date('M', strtotime($prevMonth . '-01'))) ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="stat">
    <div class="num"><?= count($expenses) ?></div>
    <div class="label">Entries</div>
  </div>
  <div class="stat">
    <div class="num" style="font-size:18px;line-height:1.5"><?= $summary['by_category'] ? e(ucwords(str_replace('_', ' ', array_key_first($summary['by_category'])))) : '—' ?></div>
    <div class="label">Top Category</div>
  </div>
  <div class="stat">
    <div class="num">₹<?= number_format($prevTotal, 0) ?></div>
    <div class="label">Last Month (<?= $prevMonth ? e(date('M', strtotime($prevMonth . '-01'))) : '—' ?>)</div>
  </div>
</div>

<?php if ($summary['by_category'] && $summary['total'] > 0): ?>
<div class="card">
  <h2 style="font-size:15px;margin-bottom:14px">Where the money went</h2>
  <?php foreach ($summary['by_category'] as $cat => $amt): ?>
    <?php $pct = round($amt / $summary['total'] * 100); ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
      <div style="width:150px;font-size:12.5px;font-weight:600;text-transform:capitalize"><?= e(str_replace('_', ' ', $cat)) ?></div>
      <div style="flex:1;background:#f0f0ee;border-radius:6px;height:18px;overflow:hidden">
        <div style="width:<?= max($pct, 2) ?>%;background:var(--ink);height:100%;border-radius:6px"></div>
      </div>
      <div style="width:120px;text-align:right;font-size:12.5px;font-variant-numeric:tabular-nums">
        <strong>₹<?= number_format($amt, 0) ?></strong> <span class="muted">(<?= $pct ?>%)</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" <?= $editing ? 'style="border-color:var(--ink)"' : '' ?>>
  <h2 style="font-size:16px;margin-bottom:12px">
    <?= $editing ? 'Edit Expense — ' . e($editing['description']) : 'Add Expense' ?>
    <?php if ($editing): ?>
      <a class="btn btn-sm" style="margin-left:8px" href="<?= e($navQuery($month)) ?>">Cancel</a>
    <?php endif; ?>
  </h2>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="<?= $editing ? 'update_expense' : 'add_expense' ?>">
    <?php if ($editing): ?><input type="hidden" name="expense_id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <div class="field">
      <label>Date *</label>
      <input type="date" name="expense_date" required value="<?= e($editing['expense_date'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="field">
      <label>Category</label>
      <select name="category">
        <?php foreach (EXPENSE_CATEGORIES as $cat): ?>
          <option value="<?= $cat ?>" <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $cat))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="flex:1;min-width:180px">
      <label>Description *</label>
      <input name="description" required placeholder="e.g. Office rent July" value="<?= e($editing['description'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Amount (₹) *</label>
      <input type="number" step="0.01" min="0.01" name="amount" required style="width:110px" value="<?= e((string) ($editing['amount'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Paid To</label>
      <input name="paid_to" placeholder="vendor / person" value="<?= e($editing['paid_to'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Paid From</label>
      <select name="account_id">
        <option value="">—</option>
        <?php foreach ($accounts as $a): ?>
          <option value="<?= (int) $a['id'] ?>" <?= (int) ($editing['account_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Project (optional)</label>
      <select name="project_id">
        <option value="">—</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (int) ($editing['project_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit"><?= $editing ? 'Update' : 'Add' ?></button>
  </form>
</div>

<div class="card">
  <form class="filters" method="get" action="/expenses">
    <input type="hidden" name="month" value="<?= e($month) ?>">
    <div class="field">
      <label>Category</label>
      <select name="category">
        <option value="">All</option>
        <?php foreach (EXPENSE_CATEGORIES as $cat): ?>
          <option value="<?= $cat ?>" <?= $filters['category'] === $cat ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $cat))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Paid From</label>
      <select name="account_id">
        <option value="">All accounts</option>
        <?php foreach ($accounts as $a): ?>
          <option value="<?= (int) $a['id'] ?>" <?= (string) $filters['account_id'] === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Search</label>
      <input name="search" placeholder="Description, vendor, project" value="<?= e($filters['search']) ?>">
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="<?= e($navQuery($month)) ?>">Reset</a>
  </form>

  <?php if (!$expenses): ?>
    <p class="muted">No expenses recorded for this period.</p>
  <?php else: ?>
    <table>
      <tr><th>Date</th><th>Category</th><th>Description</th><th>Paid To</th><th>Paid From</th><th>Project</th><th>Amount</th><th></th></tr>
      <?php foreach ($expenses as $ex): ?>
      <tr <?= $editing && (int) $editing['id'] === (int) $ex['id'] ? 'style="background:#f0f0ee"' : '' ?>>
        <td style="white-space:nowrap"><?= e($ex['expense_date']) ?></td>
        <td><span class="badge"><?= e(str_replace('_', ' ', $ex['category'])) ?></span></td>
        <td><?= e($ex['description']) ?></td>
        <td><?= e($ex['paid_to'] ?: '—') ?></td>
        <td><?= e($ex['account_name'] ?: '—') ?></td>
        <td><?= e($ex['project_name'] ?: '—') ?></td>
        <td style="white-space:nowrap"><strong>₹<?= number_format((float) $ex['amount'], 2) ?></strong></td>
        <td style="white-space:nowrap">
          <a class="btn btn-sm" href="<?= e($navQuery($month)) ?>&edit=<?= (int) $ex['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this expense?')">
            <input type="hidden" name="_action" value="delete_expense">
            <input type="hidden" name="expense_id" value="<?= (int) $ex['id'] ?>">
            <button class="btn btn-sm" type="submit">✕</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
