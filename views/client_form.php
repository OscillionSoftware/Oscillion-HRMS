<?php $isEdit = !empty($client['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit Client' : 'Add Client' ?></h1>
  <a class="btn" href="/clients">← Back to Clients</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="field">
      <label>Name *</label>
      <input name="name" required value="<?= e($client['name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Phone *</label>
      <input name="phone" required value="<?= e($client['phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" value="<?= e($client['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Company Name</label>
      <input name="company_name" value="<?= e($client['company_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>City</label>
      <input name="city" value="<?= e($client['city'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (CLIENT_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= ($client['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full">
      <label>Address</label>
      <input name="address" value="<?= e($client['address'] ?? '') ?>">
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes" rows="3"><?= e($client['notes'] ?? '') ?></textarea>
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Client' ?></button>
    </div>
  </form>
</div>
