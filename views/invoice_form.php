<?php $isEdit = !empty($invoice['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit Invoice ' . e($invoice['invoice_no']) : 'New Invoice' ?></h1>
  <a class="btn" href="/invoices">← Back to Invoices</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="form-grid">
      <div class="field">
        <label>Project *</label>
        <select name="project_id" required>
          <option value="">— Select project —</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) ($invoice['project_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
              <?= e($p['name']) ?><?= $p['client_name'] ? ' — ' . e($p['client_name']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Invoice Type</label>
        <select name="type" id="inv-type">
          <option value="non_gst" <?= ($invoice['type'] ?? 'non_gst') === 'non_gst' ? 'selected' : '' ?>>Without GST</option>
          <option value="gst" <?= ($invoice['type'] ?? '') === 'gst' ? 'selected' : '' ?>>With GST</option>
        </select>
      </div>
      <div class="field">
        <label>GST %</label>
        <input type="number" step="0.01" min="0" name="gst_percent" value="<?= e((string) ($invoice['gst_percent'] ?? 18)) ?>">
      </div>
      <div class="field">
        <label>Discount (₹)</label>
        <input type="number" step="0.01" min="0" name="discount" value="<?= e((string) ($invoice['discount'] ?? 0)) ?>">
      </div>
      <div class="field">
        <label>Issue Date *</label>
        <input type="date" name="issue_date" required value="<?= e($invoice['issue_date'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="field">
        <label>Due Date <span class="muted">(default 7 days)</span></label>
        <input type="date" name="due_date" value="<?= e($invoice['due_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
      </div>
      <div class="field">
        <label>Lock with Password <span class="muted">(optional<?= $isEdit && $invoice['locked'] ? ' — currently locked; type "remove" to unlock' : '' ?>)</span></label>
        <input name="password" autocomplete="off" placeholder="<?= $isEdit && $invoice['locked'] ? 'Leave blank to keep current' : 'Leave blank for open link' ?>">
      </div>
      <div class="field full">
        <label>Payment Options shown on invoice <span class="muted">(tick one or more)</span></label>
        <?php
          $selectedIds = array_filter(array_map('intval', explode(',', $invoice['payment_account_ids'] ?? '')));
          $payAccounts = query_accounts(true);
        ?>
        <div style="display:flex;flex-wrap:wrap;gap:14px">
          <?php foreach ($payAccounts as $pa): ?>
            <label style="display:flex;align-items:center;gap:6px;font-weight:500;text-transform:none;font-size:13.5px;letter-spacing:0">
              <input type="checkbox" name="payment_account_ids[]" value="<?= (int) $pa['id'] ?>"
                     style="width:16px;height:16px"
                     <?= (!$isEdit || !$selectedIds || in_array((int) $pa['id'], $selectedIds, true)) ? 'checked' : '' ?>>
              <?= e($pa['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <h2 style="font-size:16px;margin:18px 0 10px">Line Items</h2>
    <table id="items">
      <tr><th style="width:170px">Category</th><th>Description</th><th style="width:80px">Qty</th><th style="width:130px">Rate (₹)</th><th style="width:40px"></th></tr>
      <?php
        $rows = $invoice['items'] ?? [['category' => 'project_charges', 'description' => '', 'qty' => 1, 'rate' => '']];
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
        <td><input name="item_description[]" style="width:100%" value="<?= e($it['description'] ?? '') ?>" placeholder="e.g. Website development — full project"></td>
        <td><input type="number" step="0.01" min="0" name="item_qty[]" style="width:100%" value="<?= e((string) ($it['qty'] ?? 1)) ?>"></td>
        <td><input type="number" step="0.01" min="0" name="item_rate[]" style="width:100%" value="<?= e((string) ($it['rate'] ?? '')) ?>"></td>
        <td><button class="btn btn-sm" type="button" onclick="this.closest('tr').remove()">✕</button></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p style="margin:10px 0"><button class="btn" type="button" onclick="addRow()">+ Add Item</button></p>

    <div class="field" style="margin-top:8px">
      <label>Terms & Conditions</label>
      <textarea name="terms" rows="5" style="width:100%"><?= e($invoice['terms'] ?? ($settings['default_terms'] ?? '')) ?></textarea>
    </div>
    <div class="field" style="margin-top:8px">
      <label>Notes (internal)</label>
      <input name="notes" style="width:100%" value="<?= e($invoice['notes'] ?? '') ?>">
    </div>
    <p style="margin-top:16px">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Invoice' ?></button>
    </p>
  </form>
</div>

<script>
function addRow() {
  const table = document.getElementById('items');
  const row = table.rows[1].cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = i.type === 'number' && i.name === 'item_qty[]' ? '1' : '');
  table.appendChild(row);
}
</script>
