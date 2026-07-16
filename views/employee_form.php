<?php $isEdit = !empty($employee['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit Employee' : 'Add Employee' ?></h1>
  <a class="btn" href="/employees">← Back to Employees</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="field">
      <label>Name *</label>
      <input name="name" required value="<?= e($employee['name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Phone *</label>
      <input name="phone" required value="<?= e($employee['phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" value="<?= e($employee['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Designation</label>
      <input name="designation" placeholder="e.g. Software Developer" value="<?= e($employee['designation'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Department</label>
      <input name="department" placeholder="e.g. Development" value="<?= e($employee['department'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Joining Date</label>
      <input type="date" name="joining_date" value="<?= e($employee['joining_date'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Salary (₹/month)</label>
      <input type="number" step="0.01" min="0" name="salary" value="<?= e((string) ($employee['salary'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (EMPLOYEE_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= ($employee['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full">
      <label>Address</label>
      <input name="address" value="<?= e($employee['address'] ?? '') ?>">
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes" rows="3"><?= e($employee['notes'] ?? '') ?></textarea>
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Employee' ?></button>
    </div>
  </form>
</div>
