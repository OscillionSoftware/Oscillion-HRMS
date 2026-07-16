<?php $isEdit = !empty($project['id']); ?>
<div class="page-head">
  <h1><?= $isEdit ? 'Edit Project' : 'Add Project' ?></h1>
  <a class="btn" href="/projects">← Back to Projects</a>
</div>

<div class="card">
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="field">
      <label>Project Name *</label>
      <input name="name" required value="<?= e($project['name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Client</label>
      <select name="client_id">
        <option value="">— No client —</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) ($project['client_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?><?= $c['company_name'] ? ' (' . e($c['company_name']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (PROJECT_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= ($project['status'] ?? 'planning') === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Budget (₹)</label>
      <input type="number" step="0.01" min="0" name="budget" value="<?= e((string) ($project['budget'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Start Date</label>
      <input type="date" name="start_date" value="<?= e($project['start_date'] ?? '') ?>">
    </div>
    <div class="field">
      <label>End Date / Deadline</label>
      <input type="date" name="end_date" value="<?= e($project['end_date'] ?? '') ?>">
    </div>
    <div class="field full">
      <label>Description</label>
      <textarea name="description" rows="3"><?= e($project['description'] ?? '') ?></textarea>
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes" rows="2"><?= e($project['notes'] ?? '') ?></textarea>
    </div>
    <div class="full">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Project' ?></button>
    </div>
  </form>
</div>
