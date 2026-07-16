<div class="page-head">
  <h1>Invoices</h1>
  <a class="btn btn-primary" href="/invoices/new">+ New Invoice</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/invoices">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Invoice no, project, client" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (INVOICE_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Project</label>
      <select name="project_id">
        <option value="">All projects</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (string) $filters['project_id'] === (string) $p['id'] ? 'selected' : '' ?>>
            <?= e($p['name']) ?><?= $p['client_name'] ? ' — ' . e($p['client_name']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Type</label>
      <select name="type">
        <option value="">All</option>
        <option value="gst" <?= $filters['type'] === 'gst' ? 'selected' : '' ?>>GST</option>
        <option value="non_gst" <?= $filters['type'] === 'non_gst' ? 'selected' : '' ?>>Non-GST</option>
      </select>
    </div>
    <div class="field">
      <label>Issued From</label>
      <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
    </div>
    <div class="field">
      <label>Issued To</label>
      <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/invoices">Reset</a>
  </form>
  <?php if ($invoices): ?>
    <?php
      $sumTotal = array_sum(array_column($invoices, 'total'));
      $sumBalance = array_sum(array_map(fn($i) => in_array($i['status'], ['pending', 'partial'], true) ? $i['balance'] : 0, $invoices));
    ?>
    <p class="muted" style="margin-bottom:12px;font-size:12.5px">
      <?= count($invoices) ?> invoice<?= count($invoices) === 1 ? '' : 's' ?> ·
      Total <strong style="color:var(--ink)">₹<?= number_format($sumTotal, 2) ?></strong> ·
      Outstanding <strong style="color:var(--danger)">₹<?= number_format($sumBalance, 2) ?></strong>
    </p>
  <?php endif; ?>

  <?php if (!$invoices): ?>
    <p class="muted">No invoices yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Invoice</th><th>Project</th><th>Client</th><th>Type</th><th>Issue</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr>
      <?php foreach ($invoices as $inv): ?>
      <tr>
        <td>
          <a href="/invoices/<?= (int) $inv['id'] ?>"><strong><?= e($inv['invoice_no']) ?></strong></a>
          <?= $inv['locked'] ? ' 🔒' : '' ?>
        </td>
        <td><?= e($inv['project_name'] ?: '—') ?></td>
        <td><?= e($inv['client_name'] ?: '—') ?></td>
        <td><span class="badge"><?= $inv['type'] === 'gst' ? 'GST' : 'Non-GST' ?></span></td>
        <td><?= e($inv['issue_date']) ?></td>
        <td>₹<?= number_format($inv['total'], 2) ?></td>
        <td>₹<?= number_format($inv['amount_paid'], 2) ?></td>
        <td><strong>₹<?= number_format($inv['balance'], 2) ?></strong></td>
        <td><span class="badge inv-<?= e($inv['status']) ?>"><?= e($inv['status']) ?></span></td>
        <td><a class="btn btn-sm" href="/i/<?= e($inv['public_token']) ?>" target="_blank">Public Link</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
