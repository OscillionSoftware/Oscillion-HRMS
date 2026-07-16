<div class="page-head">
  <h1>Quotations / Proposals</h1>
  <a class="btn btn-primary" href="/quotations/new">+ New Quotation</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/quotations">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Quote no, title, client" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (QUOTE_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/quotations">Reset</a>
  </form>

  <?php if (!$quotations): ?>
    <p class="muted">No quotations yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Quote</th><th>Project Title</th><th>Client / Prospect</th><th>Type</th><th>Issued</th><th>Valid Till</th><th>Total</th><th>Status</th><th></th></tr>
      <?php foreach ($quotations as $q): ?>
      <tr>
        <td><a href="/quotations/<?= (int) $q['id'] ?>"><strong><?= e($q['quote_no']) ?></strong></a></td>
        <td><?= e($q['title']) ?></td>
        <td><?= e($q['display_name'] ?: '—') ?><?= $q['display_company'] ? ' <span class="muted">(' . e($q['display_company']) . ')</span>' : '' ?></td>
        <td><span class="badge"><?= $q['type'] === 'gst' ? 'GST' : 'Non-GST' ?></span></td>
        <td><?= e($q['issue_date']) ?></td>
        <td><?= e($q['valid_until'] ?: '—') ?><?= $q['expired'] ? ' <span class="badge inv-cancelled">expired</span>' : '' ?></td>
        <td><strong>₹<?= number_format($q['total'], 2) ?></strong></td>
        <td><span class="badge quote-<?= e($q['status']) ?>"><?= e($q['status']) ?></span></td>
        <td><a class="btn btn-sm" href="/q/<?= e($q['public_token']) ?>" target="_blank">Public Link</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
