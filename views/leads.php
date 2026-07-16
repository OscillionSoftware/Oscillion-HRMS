<div class="page-head">
  <h1>Leads</h1>
  <a class="btn btn-primary" href="/leads/new">+ Add Lead</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/leads">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Name, phone, email, company" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (LEAD_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Priority</label>
      <select name="priority">
        <option value="">All</option>
        <?php foreach (LEAD_PRIORITIES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['priority'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Behaviour</label>
      <select name="behaviour">
        <option value="">All</option>
        <?php foreach (LEAD_BEHAVIOURS as $s): ?>
          <option value="<?= $s ?>" <?= $filters['behaviour'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Follow-up date</label>
      <input type="date" name="followup_date" value="<?= e($filters['followup_date']) ?>">
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/leads">Reset</a>
  </form>

  <?php if (!$leads): ?>
    <p class="muted">No leads found.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Phone</th><th>Email</th><th>Company</th><th>Status</th><th>Behaviour</th><th>Priority</th><th>Next Follow-up</th><th></th></tr>
      <?php foreach ($leads as $l): ?>
      <tr>
        <td><a href="/leads/<?= (int) $l['id'] ?>"><strong><?= e($l['name']) ?></strong></a></td>
        <td><a href="tel:<?= e($l['phone']) ?>"><?= e($l['phone']) ?></a></td>
        <td><?= e($l['email']) ?></td>
        <td><?= e($l['company_name']) ?></td>
        <td><span class="badge status-<?= e($l['status']) ?>"><?= e(str_replace('_', ' ', $l['status'])) ?></span></td>
        <td><span class="badge"><?= e(str_replace('_', ' ', $l['behaviour'])) ?></span></td>
        <td><span class="badge prio-<?= e($l['priority']) ?>"><?= e($l['priority']) ?></span></td>
        <td><?= e($l['next_followup'] ?? '—') ?></td>
        <td><a class="btn btn-sm" href="/leads/<?= (int) $l['id'] ?>/edit">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
