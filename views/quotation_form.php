<?php $isEdit = !empty($quotation['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit ' . e($quotation['quote_no']) : 'New Quotation' ?></h1>
  <a class="btn" href="/quotations">← Back to Quotations</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="form-grid">
      <div class="field full">
        <label>Project Title *</label>
        <input name="title" required placeholder="e.g. E-commerce Website for Khan Retail" value="<?= e($quotation['title'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Existing Client</label>
        <select name="client_id">
          <option value="">— New prospect (fill below) —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($quotation['client_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?><?= $c['company_name'] ? ' (' . e($c['company_name']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Prospect Name <span class="muted">(if not a client yet)</span></label>
        <input name="prospect_name" value="<?= e($quotation['prospect_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Prospect Company</label>
        <input name="prospect_company" value="<?= e($quotation['prospect_company'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Prospect Phone</label>
        <input name="prospect_phone" value="<?= e($quotation['prospect_phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Prospect Email</label>
        <input name="prospect_email" value="<?= e($quotation['prospect_email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Type</label>
        <select name="type">
          <option value="non_gst" <?= ($quotation['type'] ?? 'non_gst') === 'non_gst' ? 'selected' : '' ?>>Without GST</option>
          <option value="gst" <?= ($quotation['type'] ?? '') === 'gst' ? 'selected' : '' ?>>With GST</option>
        </select>
      </div>
      <div class="field">
        <label>GST %</label>
        <input type="number" step="0.01" min="0" name="gst_percent" value="<?= e((string) ($quotation['gst_percent'] ?? 18)) ?>">
      </div>
      <div class="field">
        <label>Discount (₹)</label>
        <input type="number" step="0.01" min="0" name="discount" value="<?= e((string) ($quotation['discount'] ?? 0)) ?>">
      </div>
      <div class="field">
        <label>Issue Date *</label>
        <input type="date" name="issue_date" required value="<?= e($quotation['issue_date'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="field">
        <label>Valid Until</label>
        <input type="date" name="valid_until" value="<?= e($quotation['valid_until'] ?? date('Y-m-d', strtotime('+15 days'))) ?>">
      </div>
      <div class="field full">
        <label>Scope / Description <span class="muted">(what's included — shown on the proposal)</span></label>
        <textarea name="scope" rows="4"><?= e($quotation['scope'] ?? '') ?></textarea>
      </div>
    </div>

    <h2 style="font-size:16px;margin:18px 0 10px">Line Items</h2>
    <table id="items">
      <tr><th style="width:170px">Category</th><th>Description</th><th style="width:80px">Qty</th><th style="width:130px">Rate (₹)</th><th style="width:40px"></th></tr>
      <?php
        $rows = $quotation['items'] ?? [['category' => 'project_charges', 'description' => '', 'qty' => 1, 'rate' => '']];
        foreach ($rows as $it):
      ?>
      <tr>
        <td>
          <select name="item_category[]">
            <?php foreach (ITEM_CATEGORIES as $cat): ?>
              <option value="<?= $cat ?>" <?= ($it['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $cat))) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input name="item_description[]" style="width:100%" value="<?= e($it['description'] ?? '') ?>"></td>
        <td><input type="number" step="0.01" min="0" name="item_qty[]" style="width:100%" value="<?= e((string) ($it['qty'] ?? 1)) ?>"></td>
        <td><input type="number" step="0.01" min="0" name="item_rate[]" style="width:100%" value="<?= e((string) ($it['rate'] ?? '')) ?>"></td>
        <td><button class="btn btn-sm" type="button" onclick="this.closest('tr').remove()">✕</button></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p style="margin:10px 0"><button class="btn" type="button" onclick="addRow()">+ Add Item</button></p>

    <div class="field" style="margin-top:8px">
      <label>Terms & Conditions</label>
      <textarea name="terms" rows="5" style="width:100%"><?= e($quotation['terms'] ?? ($settings['default_terms'] ?? '')) ?></textarea>
    </div>
    <p style="margin-top:16px">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Quotation' ?></button>
    </p>
  </form>
</div>

<script>
function addRow() {
  const table = document.getElementById('items');
  const row = table.rows[1].cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = i.name === 'item_qty[]' ? '1' : '');
  table.appendChild(row);
}
</script>
