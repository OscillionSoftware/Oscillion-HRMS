<div class="page-head">
  <h1>Projects</h1>
  <a class="btn btn-primary" href="/projects/new">+ Add Project</a>
</div>

<div class="card">
  <form class="filters" method="get" action="/projects">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Project, client, company" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (PROJECT_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/projects">Reset</a>
  </form>

  <?php if (!$projects): ?>
    <p class="muted">No projects found.</p>
  <?php else: ?>
    <table>
      <tr><th>Project</th><th>Client</th><th>Status</th><th>Tasks</th><th>Start</th><th>End</th><th>Budget</th><th></th></tr>
      <?php foreach ($projects as $p): ?>
      <tr>
        <td><a href="/projects/<?= (int) $p['id'] ?>"><strong><?= e($p['name']) ?></strong></a></td>
        <td><?php if ($p['client_id']): ?><a href="/clients/<?= (int) $p['client_id'] ?>"><?= e($p['client_name']) ?></a><?php else: ?>—<?php endif; ?></td>
        <td><span class="badge proj-<?= e($p['status']) ?>"><?= e(str_replace('_', ' ', $p['status'])) ?></span></td>
        <td><?= (int) $p['task_total'] ? (int) $p['task_done'] . '/' . (int) $p['task_total'] : '—' ?></td>
        <td><?= e($p['start_date'] ?: '—') ?></td>
        <td><?= e($p['end_date'] ?: '—') ?></td>
        <td><?= $p['budget'] !== null ? '₹' . number_format((float) $p['budget'], 2) : '—' ?></td>
        <td><a class="btn btn-sm" href="/projects/<?= (int) $p['id'] ?>/edit">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
