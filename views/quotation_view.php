<?php $publicUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/q/' . $quotation['public_token']; ?>
<div class="page-head">
  <h1><?= e($quotation['quote_no']) ?>
    <span class="badge quote-<?= e($quotation['status']) ?>" style="vertical-align:middle"><?= e($quotation['status']) ?></span>
    <?= $quotation['expired'] ? '<span class="badge inv-cancelled" style="vertical-align:middle">expired</span>' : '' ?>
  </h1>
  <div>
    <a class="btn" href="/quotations">← Back</a>
    <a class="btn" href="/quotations/<?= (int) $quotation['id'] ?>/edit">Edit</a>
    <a class="btn btn-primary" href="<?= e($publicUrl) ?>" target="_blank">Open Public Proposal</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="item"><div class="k">Project Title</div><div class="v"><?= e($quotation['title']) ?></div></div>
    <div class="item"><div class="k">Client / Prospect</div><div class="v"><?= e($quotation['display_name'] ?: '—') ?><?= $quotation['display_company'] ? ' (' . e($quotation['display_company']) . ')' : '' ?></div></div>
    <div class="item"><div class="k">Contact</div><div class="v"><?= e($quotation['display_phone'] ?: '—') ?> · <?= e($quotation['display_email'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Type</div><div class="v"><?= $quotation['type'] === 'gst' ? 'With GST (' . e((string) $quotation['gst_percent']) . '%)' : 'Without GST' ?></div></div>
    <div class="item"><div class="k">Issued / Valid Until</div><div class="v"><?= e($quotation['issue_date']) ?> → <?= e($quotation['valid_until'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Total</div><div class="v"><strong>₹<?= number_format($quotation['total'], 2) ?></strong></div></div>
  </div>
  <p style="margin-top:14px">
    <span class="muted" style="font-size:12px;text-transform:uppercase">Public link (share with client — they can accept online)</span><br>
    <input readonly value="<?= e($publicUrl) ?>" style="width:70%" onclick="this.select()">
    <button class="btn btn-sm" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>');this.textContent='Copied!'">Copy</button>
  </p>
  <p style="margin-top:14px">
    <?php if ($quotation['status'] === 'accepted' && !$quotation['project_id']): ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="_action" value="convert">
        <button class="btn btn-primary" type="submit">🚀 Convert to Project</button>
      </form>
    <?php elseif ($quotation['project_id']): ?>
      <a class="btn btn-primary" href="/projects/<?= (int) $quotation['project_id'] ?>">View Created Project →</a>
    <?php endif; ?>
    <?php if ($quotation['status'] === 'pending'): ?>
      <form method="post" style="display:inline"><input type="hidden" name="_action" value="accepted"><button class="btn" type="submit">Mark Accepted</button></form>
      <form method="post" style="display:inline"><input type="hidden" name="_action" value="rejected"><button class="btn" type="submit">Mark Rejected</button></form>
    <?php elseif (!$quotation['project_id']): ?>
      <form method="post" style="display:inline"><input type="hidden" name="_action" value="pending"><button class="btn" type="submit">Back to Pending</button></form>
    <?php endif; ?>
  </p>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Items</h2>
  <?php if ($quotation['scope']): ?>
    <p style="margin-bottom:12px"><span class="muted" style="font-size:12px;text-transform:uppercase">Scope</span><br><?= nl2br(e($quotation['scope'])) ?></p>
  <?php endif; ?>
  <table>
    <tr><th>Category</th><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>
    <?php foreach ($quotation['items'] as $it): ?>
    <tr>
      <td><span class="badge"><?= e(str_replace('_', ' ', $it['category'])) ?></span></td>
      <td><?= e($it['description']) ?></td>
      <td><?= e((string) $it['qty']) ?></td>
      <td>₹<?= number_format((float) $it['rate'], 2) ?></td>
      <td>₹<?= number_format((float) $it['qty'] * (float) $it['rate'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="4" style="text-align:right">Subtotal</td><td>₹<?= number_format($quotation['subtotal'], 2) ?></td></tr>
    <?php if ((float) $quotation['discount'] > 0): ?>
      <tr><td colspan="4" style="text-align:right">Discount</td><td>− ₹<?= number_format((float) $quotation['discount'], 2) ?></td></tr>
    <?php endif; ?>
    <?php if ($quotation['type'] === 'gst'): ?>
      <tr><td colspan="4" style="text-align:right">GST <?= e((string) $quotation['gst_percent']) ?>%</td><td>₹<?= number_format($quotation['gst_amount'], 2) ?></td></tr>
    <?php endif; ?>
    <tr><td colspan="4" style="text-align:right"><strong>Total</strong></td><td><strong>₹<?= number_format($quotation['total'], 2) ?></strong></td></tr>
  </table>
</div>
