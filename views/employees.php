<div class="page-head">
  <h1>Employees</h1>
  <a class="btn btn-primary" href="/employees/new">+ Add Employee</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/employees">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Name, phone, email, designation, department" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (EMPLOYEE_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/employees">Reset</a>
  </form>

  <?php if (!$employees): ?>
    <p class="muted">No employees found.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Designation</th><th>Department</th><th>Phone</th><th>Joined</th><th>Status</th><th>Connect</th><th></th></tr>
      <?php foreach ($employees as $emp): ?>
      <tr>
        <td><a href="/employees/<?= (int) $emp['id'] ?>"><strong><?= e($emp['name']) ?></strong></a></td>
        <td><?= e($emp['designation'] ?: '—') ?></td>
        <td><?= e($emp['department'] ?: '—') ?></td>
        <td><?= e($emp['phone']) ?></td>
        <td><?= e($emp['joining_date'] ?: '—') ?></td>
        <td><span class="badge emp-<?= e($emp['status']) ?>"><?= e(str_replace('_', ' ', $emp['status'])) ?></span></td>
        <td>
          <a class="btn btn-sm" href="tel:<?= e($emp['phone']) ?>">📞 Call</a>
          <a class="btn btn-sm" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D/', '', $emp['phone'])) ?>">💬 WhatsApp</a>
          <?php if ($emp['email']): ?><a class="btn btn-sm" href="mailto:<?= e($emp['email']) ?>">✉️ Email</a><?php endif; ?>
        </td>
        <td><a class="btn btn-sm" href="/employees/<?= (int) $emp['id'] ?>/edit">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
