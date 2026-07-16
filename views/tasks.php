<?php $done = count(array_filter($tasks, fn($t) => $t['status'] === 'done')); ?>
<div class="page-head">
  <h1>All Tasks <span class="muted" style="font-size:14px;font-weight:500">(<?= $done ?>/<?= count($tasks) ?> done)</span></h1>
</div>

<div class="card">
  <form class="filters" method="get" action="/tasks">
    <div class="field">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <?php foreach (TASK_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Project</label>
      <select name="project_id">
        <option value="">All projects</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (string) $filters['project_id'] === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Assignee</label>
      <select name="assigned_to">
        <option value="">Anyone</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int) $emp['id'] ?>" <?= (string) $filters['assigned_to'] === (string) $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Search</label>
      <input name="search" placeholder="Task, project" value="<?= e($filters['search']) ?>">
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/tasks">Reset</a>
  </form>

  <?php if (!$tasks): ?>
    <p class="muted">No tasks found.</p>
  <?php else: ?>
    <table>
      <tr><th>Task</th><th>Project</th><th>Assignee</th><th>Priority</th><th>Due</th><th>Status</th><th></th></tr>
      <?php foreach ($tasks as $t): ?>
      <tr style="<?= $t['status'] === 'done' ? 'opacity:.55' : '' ?>">
        <td><strong><?= e($t['title']) ?></strong><?php if ($t['description']): ?><br><span class="muted" style="font-size:12.5px"><?= e($t['description']) ?></span><?php endif; ?></td>
        <td><a href="/projects/<?= (int) $t['project_id'] ?>#tasks"><?= e($t['project_name']) ?></a></td>
        <td><?= e($t['assignee_name'] ?: '—') ?></td>
        <td><span class="badge prio-<?= e($t['priority']) ?>"><?= e($t['priority']) ?></span></td>
        <td style="white-space:nowrap"><?= e($t['due_date'] ?: '—') ?></td>
        <td><span class="badge task-<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', $t['status'])) ?></span></td>
        <td style="white-space:nowrap">
          <?php if ($t['status'] !== 'done'): ?>
            <?php if ($t['status'] === 'todo'): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="_action" value="task_status">
                <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                <input type="hidden" name="status" value="in_progress">
                <button class="btn btn-sm" type="submit">Start</button>
              </form>
            <?php endif; ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="_action" value="task_status">
              <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
              <input type="hidden" name="status" value="done">
              <button class="btn btn-sm" type="submit">✓ Done</button>
            </form>
          <?php else: ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="_action" value="task_status">
              <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
              <input type="hidden" name="status" value="todo">
              <button class="btn btn-sm" type="submit">Reopen</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
