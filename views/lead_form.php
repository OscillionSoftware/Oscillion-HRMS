<?php $isEdit = !empty($lead['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit Lead' : 'Add Lead' ?></h1>
  <a class="btn" href="/leads">← Back to Leads</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="field">
      <label>Name *</label>
      <input name="name" required value="<?= e($lead['name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Phone *</label>
      <input name="phone" required value="<?= e($lead['phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" value="<?= e($lead['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Company Name</label>
      <input name="company_name" value="<?= e($lead['company_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (LEAD_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= ($lead['status'] ?? 'new') === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Behaviour</label>
      <select name="behaviour">
        <?php foreach (LEAD_BEHAVIOURS as $s): ?>
          <option value="<?= $s ?>" <?= ($lead['behaviour'] ?? 'normal') === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Priority</label>
      <select name="priority">
        <?php foreach (LEAD_PRIORITIES as $s): ?>
          <option value="<?= $s ?>" <?= ($lead['priority'] ?? 'normal') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes" rows="3"><?= e($lead['notes'] ?? '') ?></textarea>
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Lead' ?></button>
    </div>
  </form>
</div>
