<div class="page-head">
  <h1>Settings</h1>
</div>

<?php if (!empty($user['must_change_password'])): ?>
  <div class="errors">You're signed in with a temporary password. Please set a new password below before continuing.</div>
<?php endif; ?>

<?php if (is_admin($user)): ?>
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
    <div class="full">
      <button class="btn btn-primary" type="submit">Save Settings</button>
    </div>
  </form>
</div>
<?php endif; ?>

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
  <h2 style="font-size:17px;margin-bottom:12px">Mobile App Access</h2>
  <p class="muted" style="font-size:13px;margin-bottom:12px">
    <?= !empty($user['api_token']) ? 'A mobile app is currently signed in to your account.' : 'No mobile app is currently signed in.' ?>
  </p>
  <form method="post">
    <input type="hidden" name="_action" value="revoke_api_token">
    <button class="btn btn-sm" type="submit" <?= empty($user['api_token']) ? 'disabled' : '' ?>>Revoke Mobile App Access</button>
  </form>
</div>

<?php if (is_admin($user)): ?>
<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Payment Accounts <span class="muted" style="font-size:13px;font-weight:400">(where you receive money)</span></h2>
  <?php foreach ($accounts as $a): ?>
    <form id="acct-save-<?= (int) $a['id'] ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_action" value="save_account">
      <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
    </form>
    <form id="acct-del-<?= (int) $a['id'] ?>" method="post"
          onsubmit="return confirm('Delete account <?= e($a['name']) ?>? Past payments/expenses keep their records but lose the account link.')">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_action" value="delete_account">
      <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
    </form>
  <?php endforeach; ?>
  <form id="acct-new" method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_action" value="save_account">
  </form>
  <table>
    <tr><th>Name</th><th>Type</th><th>UPI ID / Details</th><th>Account No & IFSC <span class="muted">(bank only)</span></th><th>Active</th><th></th></tr>
    <?php foreach ($accounts as $a): ?>
    <tr>
        <td><input form="acct-save-<?= (int) $a['id'] ?>" name="name" value="<?= e($a['name']) ?>"></td>
        <td>
          <select form="acct-save-<?= (int) $a['id'] ?>" name="type">
            <?php foreach (ACCOUNT_TYPES as $t): ?>
              <option value="<?= $t ?>" <?= $a['type'] === $t ? 'selected' : '' ?>><?= e(strtoupper($t) === 'UPI' ? 'UPI' : ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input form="acct-save-<?= (int) $a['id'] ?>" name="details" style="width:100%" placeholder="e.g. name@upi" value="<?= e($a['details'] ?? '') ?>"></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:6px">
            <input form="acct-save-<?= (int) $a['id'] ?>" name="account_no" placeholder="Account No" value="<?= e($a['account_no'] ?? '') ?>">
            <input form="acct-save-<?= (int) $a['id'] ?>" name="ifsc" placeholder="IFSC Code" value="<?= e($a['ifsc'] ?? '') ?>">
          </div>
        </td>
        <td><input form="acct-save-<?= (int) $a['id'] ?>" type="checkbox" name="active" value="1" <?= $a['active'] ? 'checked' : '' ?> style="width:18px;height:18px"></td>
        <td style="white-space:nowrap">
          <button form="acct-save-<?= (int) $a['id'] ?>" class="btn btn-sm" type="submit">Save</button>
          <button form="acct-del-<?= (int) $a['id'] ?>" class="btn btn-sm" type="submit" style="color:var(--danger);border-color:#f2d3d0">✕</button>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td><input form="acct-new" name="name" placeholder="New account name"></td>
        <td>
          <select form="acct-new" name="type">
            <?php foreach (ACCOUNT_TYPES as $t): ?>
              <option value="<?= $t ?>"><?= e(strtoupper($t) === 'UPI' ? 'UPI' : ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input form="acct-new" name="details" style="width:100%" placeholder="UPI ID (for UPI type)"></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:6px">
            <input form="acct-new" name="account_no" placeholder="Account No">
            <input form="acct-new" name="ifsc" placeholder="IFSC Code">
          </div>
        </td>
        <td><input form="acct-new" type="checkbox" name="active" value="1" checked style="width:18px;height:18px"></td>
        <td><button form="acct-new" class="btn btn-sm btn-primary" type="submit">Add</button></td>
    </tr>
  </table>
</div>
<?php endif; ?>
