<div class="page-head">
  <h1>Clients</h1>
  <a class="btn btn-primary" href="/clients/new">+ Add Client</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/clients">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Name, phone, email, company, city" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (CLIENT_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/clients">Reset</a>
  </form>

  <?php if (!$clients): ?>
    <p class="muted">No clients found.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Phone</th><th>Email</th><th>Company</th><th>City</th><th>Status</th><th>Connect</th><th></th></tr>
      <?php foreach ($clients as $c): ?>
      <tr>
        <td><a href="/clients/<?= (int) $c['id'] ?>"><strong><?= e($c['name']) ?></strong></a></td>
        <td><?= e($c['phone']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><?= e($c['company_name']) ?></td>
        <td><?= e($c['city']) ?></td>
        <td><span class="badge <?= $c['status'] === 'active' ? 'status-interested' : 'status-closed' ?>"><?= e($c['status']) ?></span></td>
        <td>
          <a class="btn btn-sm" href="tel:<?= e($c['phone']) ?>">📞 Call</a>
          <a class="btn btn-sm" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D/', '', $c['phone'])) ?>">💬 WhatsApp</a>
          <?php if ($c['email']): ?><a class="btn btn-sm" href="mailto:<?= e($c['email']) ?>">✉️ Email</a><?php endif; ?>
        </td>
        <td><a class="btn btn-sm" href="/clients/<?= (int) $c['id'] ?>/edit">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
