<div class="page-head">
  <h1>Settings</h1>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Company Profile <span class="muted" style="font-size:13px;font-weight:400">(shown on invoices)</span></h2>
  <?php if (!empty($saved)): ?><div class="errors" style="background:#e8f8ee;color:var(--success);border-color:#bfe6cd">Settings saved.</div><?php endif; ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="_action" value="save_settings">
    <div class="field">
      <label>Company Name</label>
      <input name="company_name" value="<?= e($settings['company_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Tagline</label>
      <input name="company_tagline" value="<?= e($settings['company_tagline'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Phone</label>
      <input name="company_phone" value="<?= e($settings['company_phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Email</label>
      <input name="company_email" value="<?= e($settings['company_email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>GSTIN</label>
      <input name="company_gstin" value="<?= e($settings['company_gstin'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Invoice Prefix</label>
      <input name="invoice_prefix" value="<?= e($settings['invoice_prefix'] ?? 'OSC') ?>">
    </div>
    <div class="field full">
      <label>Address</label>
      <input name="company_address" value="<?= e($settings['company_address'] ?? '') ?>">
    </div>
    <div class="field full">
      <label>Default Terms & Conditions</label>
      <textarea name="default_terms" rows="6"><?= e($settings['default_terms'] ?? '') ?></textarea>
    </div>
    <div class="field full">
      <label>About Us <span class="muted">(page 2 of every proposal PDF)</span></label>
      <textarea name="about_us" rows="8"><?= e($settings['about_us'] ?? '') ?></textarea>
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit">Save Settings</button>
    </div>
  </form>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Change Password</h2>
  <?php if (!empty($pwdSaved)): ?><div class="errors" style="background:#e8f8ee;color:var(--success);border-color:#bfe6cd">Password changed.</div><?php endif; ?>
  <?php if (!empty($pwdError)): ?><div class="errors"><?= e($pwdError) ?></div><?php endif; ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="_action" value="change_password">
    <div class="field">
      <label>Current Password</label>
      <input type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="field">
      <label>New Password</label>
      <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="field">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit">Change Password</button>
    </div>
  </form>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Payment Accounts <span class="muted" style="font-size:13px;font-weight:400">(where you receive money)</span></h2>
  <table>
    <tr><th>Name</th><th>Type</th><th>UPI ID / Details</th><th>Account No & IFSC <span class="muted">(bank only)</span></th><th>Active</th><th></th></tr>
    <?php foreach ($accounts as $a): ?>
    <tr>
      <form method="post">
        <input type="hidden" name="_action" value="save_account">
        <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
        <td><input name="name" value="<?= e($a['name']) ?>"></td>
        <td>
          <select name="type">
            <?php foreach (ACCOUNT_TYPES as $t): ?>
              <option value="<?= $t ?>" <?= $a['type'] === $t ? 'selected' : '' ?>><?= e(strtoupper($t) === 'UPI' ? 'UPI' : ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input name="details" style="width:100%" placeholder="e.g. name@upi" value="<?= e($a['details'] ?? '') ?>"></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:6px">
            <input name="account_no" placeholder="Account No" value="<?= e($a['account_no'] ?? '') ?>">
            <input name="ifsc" placeholder="IFSC Code" value="<?= e($a['ifsc'] ?? '') ?>">
          </div>
        </td>
        <td><input type="checkbox" name="active" value="1" <?= $a['active'] ? 'checked' : '' ?> style="width:18px;height:18px"></td>
        <td style="white-space:nowrap">
          <button class="btn btn-sm" type="submit">Save</button>
      </form>
          <form method="post" style="display:inline"
                onsubmit="return confirm('Delete account <?= e($a['name']) ?>? Past payments/expenses keep their records but lose the account link.')">
            <input type="hidden" name="_action" value="delete_account">
            <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
            <button class="btn btn-sm" type="submit" style="color:var(--danger);border-color:#f2d3d0">✕</button>
          </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <form method="post">
        <input type="hidden" name="_action" value="save_account">
        <td><input name="name" placeholder="New account name"></td>
        <td>
          <select name="type">
            <?php foreach (ACCOUNT_TYPES as $t): ?>
              <option value="<?= $t ?>"><?= e(strtoupper($t) === 'UPI' ? 'UPI' : ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input name="details" style="width:100%" placeholder="UPI ID (for UPI type)"></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:6px">
            <input name="account_no" placeholder="Account No">
            <input name="ifsc" placeholder="IFSC Code">
          </div>
        </td>
        <td><input type="checkbox" name="active" value="1" checked style="width:18px;height:18px"></td>
        <td><button class="btn btn-sm btn-primary" type="submit">Add</button></td>
      </form>
    </tr>
  </table>
</div>
