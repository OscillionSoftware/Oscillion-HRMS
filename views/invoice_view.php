<?php $publicUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/i/' . $invoice['public_token']; ?>
<div class="page-head">
  <h1><?= e($invoice['invoice_no']) ?> <?= $invoice['locked'] ? '🔒' : '' ?>
    <span class="badge inv-<?= e($invoice['status']) ?>" style="vertical-align:middle"><?= e($invoice['status']) ?></span>
  </h1>
  <div>
    <a class="btn" href="/invoices">← Back</a>
    <a class="btn" href="/invoices/<?= (int) $invoice['id'] ?>/edit">Edit</a>
    <a class="btn btn-primary" href="<?= e($publicUrl) ?>" target="_blank">Open Public Invoice</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="item"><div class="k">Project</div><div class="v"><a href="/projects/<?= (int) $invoice['project_id'] ?>"><?= e($invoice['project_name']) ?></a></div></div>
    <div class="item"><div class="k">Client</div><div class="v"><?= e($invoice['client_name'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Type</div><div class="v"><?= $invoice['type'] === 'gst' ? 'With GST (' . e((string) $invoice['gst_percent']) . '%)' : 'Without GST' ?></div></div>
    <div class="item"><div class="k">Issue / Due</div><div class="v"><?= e($invoice['issue_date']) ?> → <?= e($invoice['due_date'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Total</div><div class="v">₹<?= number_format($invoice['total'], 2) ?></div></div>
    <div class="item"><div class="k">Paid / Balance</div><div class="v">₹<?= number_format($invoice['amount_paid'], 2) ?> / <strong>₹<?= number_format($invoice['balance'], 2) ?></strong></div></div>
  </div>
  <p style="margin-top:14px">
    <span class="muted" style="font-size:12px;text-transform:uppercase">Public link (share with client)</span><br>
    <input readonly value="<?= e($publicUrl) ?>" style="width:70%" onclick="this.select()">
    <button class="btn btn-sm" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>');this.textContent='Copied!'">Copy</button>
    <?php if ($invoice['locked']): ?><span class="muted">(password protected)</span><?php endif; ?>
  </p>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Items</h2>
  <table>
    <tr><th>Category</th><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>
    <?php foreach ($invoice['items'] as $it): ?>
    <tr>
      <td><span class="badge"><?= e(str_replace('_', ' ', $it['category'])) ?></span></td>
      <td><?= e($it['description']) ?></td>
      <td><?= e((string) $it['qty']) ?></td>
      <td>₹<?= number_format((float) $it['rate'], 2) ?></td>
      <td>₹<?= number_format((float) $it['qty'] * (float) $it['rate'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="4" style="text-align:right">Subtotal</td><td>₹<?= number_format($invoice['subtotal'], 2) ?></td></tr>
    <?php if ((float) $invoice['discount'] > 0): ?>
      <tr><td colspan="4" style="text-align:right">Discount</td><td>− ₹<?= number_format((float) $invoice['discount'], 2) ?></td></tr>
    <?php endif; ?>
    <?php if ($invoice['type'] === 'gst'): ?>
      <tr><td colspan="4" style="text-align:right">GST <?= e((string) $invoice['gst_percent']) ?>%</td><td>₹<?= number_format($invoice['gst_amount'], 2) ?></td></tr>
    <?php endif; ?>
    <tr><td colspan="4" style="text-align:right"><strong>Total</strong></td><td><strong>₹<?= number_format($invoice['total'], 2) ?></strong></td></tr>
  </table>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Payments — ₹<?= number_format($invoice['amount_paid'], 2) ?> received</h2>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <?php if ($invoice['status'] !== 'cancelled' && $invoice['balance'] > 0): ?>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="add_payment">
    <div class="field">
      <label>Amount (₹) *</label>
      <input type="number" step="0.01" min="0.01" name="amount" required value="<?= e((string) $invoice['balance']) ?>">
    </div>
    <div class="field">
      <label>Date *</label>
      <input type="date" name="paid_date" required value="<?= e(date('Y-m-d')) ?>">
    </div>
    <div class="field">
      <label>Received In</label>
      <select name="account_id">
        <?php foreach ($accounts as $a): ?>
          <option value="<?= (int) $a['id'] ?>"><?= e($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Reference / UTR</label>
      <input name="reference" placeholder="txn id, cheque no…">
    </div>
    <button class="btn btn-primary" type="submit">Record Payment</button>
  </form>
  <?php endif; ?>

  <?php if (!$invoice['payments']): ?>
    <p class="muted">No payments recorded yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Date</th><th>Amount</th><th>Account</th><th>Reference</th></tr>
      <?php foreach ($invoice['payments'] as $pay): ?>
      <tr>
        <td><?= e($pay['paid_date']) ?></td>
        <td>₹<?= number_format((float) $pay['amount'], 2) ?></td>
        <td><?= e($pay['account_name'] ?: '—') ?></td>
        <td><?= e($pay['reference'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <p style="margin-top:14px">
    <?php if ($invoice['status'] !== 'cancelled'): ?>
      <form method="post" style="display:inline" onsubmit="return confirm('Cancel this invoice?')">
        <input type="hidden" name="_action" value="cancel">
        <button class="btn" type="submit">Cancel Invoice</button>
      </form>
    <?php else: ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="_action" value="reopen">
        <button class="btn" type="submit">Reopen Invoice</button>
      </form>
    <?php endif; ?>
    <form method="post" style="display:inline"
          onsubmit="return confirm('Delete invoice <?= e($invoice['invoice_no']) ?> permanently? Its payments will also be removed. This cannot be undone.')">
      <input type="hidden" name="_action" value="delete">
      <button class="btn" type="submit" style="color:var(--danger);border-color:#f2d3d0">Delete Invoice</button>
    </form>
  </p>
</div>
