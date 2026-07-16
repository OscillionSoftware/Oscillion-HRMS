<div class="page-head">
  <h1><?= e($project['name']) ?></h1>
  <div>
    <a class="btn" href="/projects">← Back</a>
    <a class="btn btn-primary" href="/projects/<?= (int) $project['id'] ?>/edit">Edit Project</a>
  </div>
</div>

<div class="card">
  <?php if ($project['client_id']): ?>
  <div style="margin-bottom:16px">
    <span class="muted" style="font-size:13px">Client:</span>
    <a href="/clients/<?= (int) $project['client_id'] ?>"><strong><?= e($project['client_name']) ?></strong></a>
    &nbsp;
    <a class="btn btn-sm" href="tel:<?= e($project['client_phone']) ?>">📞 Call</a>
    <a class="btn btn-sm" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D/', '', $project['client_phone'] ?? '')) ?>">💬 WhatsApp</a>
    <?php if (!empty($project['client_email'])): ?><a class="btn btn-sm" href="mailto:<?= e($project['client_email']) ?>">✉️ Email</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <div class="detail-grid">
    <div class="item"><div class="k">Status</div><div class="v"><span class="badge proj-<?= e($project['status']) ?>"><?= e(str_replace('_', ' ', $project['status'])) ?></span></div></div>
    <div class="item"><div class="k">Start Date</div><div class="v"><?= e($project['start_date'] ?: '—') ?></div></div>
    <div class="item"><div class="k">End Date</div><div class="v"><?= e($project['end_date'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Budget</div><div class="v"><?= $project['budget'] !== null ? '₹' . number_format((float) $project['budget'], 2) : '—' ?></div></div>
    <div class="item"><div class="k">Created</div><div class="v"><?= e($project['created_at']) ?></div></div>
  </div>
  <?php if ($project['description']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Description</span><br><?= nl2br(e($project['description'])) ?></p>
  <?php endif; ?>
  <?php if ($project['notes']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Notes</span><br><?= nl2br(e($project['notes'])) ?></p>
  <?php endif; ?>
</div>

<div class="card" id="hosting">
  <h2 style="font-size:17px;margin-bottom:12px">Hosting & Domains</h2>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="add_service">
    <div class="field">
      <label>Type</label>
      <select name="type">
        <?php foreach (SERVICE_TYPES as $s): ?>
          <option value="<?= $s ?>"><?= e(strtoupper($s) === 'SSL' ? 'SSL' : ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="min-width:170px">
      <label>Domain / Plan *</label>
      <input name="name" required placeholder="e.g. example.com">
    </div>
    <div class="field">
      <label>Provider</label>
      <input name="provider" placeholder="GoDaddy, Hostinger…">
    </div>
    <div class="field">
      <label>Expiry *</label>
      <input type="date" name="expiry_date" required>
    </div>
    <div class="field">
      <label>Term</label>
      <select name="years">
        <?php foreach ([1, 2, 3, 5] as $y): ?><option value="<?= $y ?>"><?= $y ?> year<?= $y > 1 ? 's' : '' ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Our Cost (₹)</label>
      <input type="number" step="0.01" min="0" name="our_cost" style="width:110px">
    </div>
    <div class="field">
      <label>Client Pays (₹)</label>
      <input type="number" step="0.01" min="0" name="client_charge" style="width:110px">
    </div>
    <div class="field">
      <label>Auto-renew</label>
      <input type="checkbox" name="auto_renew" value="1" style="width:20px;height:20px">
    </div>
    <button class="btn btn-primary" type="submit">Add</button>
  </form>

  <?php if (!$services): ?>
    <p class="muted">No hosting/domain records yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Type</th><th>Provider</th><th>Expiry</th><th>Left</th><th>Term</th><th>Our Cost</th><th>Client Pays</th></tr>
      <?php foreach ($services as $s): ?>
      <?php
        $days = (int) $s['days_left'];
        $cls = $days < 0 ? 'status-not_interested' : ($days <= 30 ? 'status-follow_up' : 'status-interested');
        $left = $days < 0 ? abs($days) . 'd overdue' : $days . ' days';
      ?>
      <tr>
        <td><strong><?= e($s['name']) ?></strong><?= $s['auto_renew'] ? ' <span class="badge">auto</span>' : '' ?></td>
        <td><span class="badge"><?= e($s['type']) ?></span></td>
        <td><?= e($s['provider'] ?: '—') ?></td>
        <td><?= e($s['expiry_date']) ?></td>
        <td><span class="badge <?= $cls ?>"><?= e($left) ?></span></td>
        <td><?= (int) $s['years'] ?> yr</td>
        <td><?= $s['our_cost'] !== null ? '₹' . number_format((float) $s['our_cost'], 2) : '—' ?></td>
        <td><?= $s['client_charge'] !== null ? '₹' . number_format((float) $s['client_charge'], 2) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<div class="card" id="credentials">
  <h2 style="font-size:17px;margin-bottom:12px">Credentials & Access</h2>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="add_credential">
    <div class="field">
      <label>Service</label>
      <select name="type">
        <?php foreach (CREDENTIAL_TYPES as $s): ?>
          <option value="<?= $s ?>"><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Label</label>
      <input name="label" placeholder="e.g. Production cPanel">
    </div>
    <div class="field" style="min-width:180px">
      <label>URL</label>
      <input name="url" placeholder="https://…">
    </div>
    <div class="field">
      <label>Username / Email</label>
      <input name="username">
    </div>
    <div class="field">
      <label>Password</label>
      <input name="password" autocomplete="off">
    </div>
    <button class="btn btn-primary" type="submit">Add</button>
  </form>

  <?php if (!$credentials): ?>
    <p class="muted">No credentials saved yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Service</th><th>Label</th><th>URL</th><th>Username</th><th>Password</th><th></th></tr>
      <?php foreach ($credentials as $c): ?>
      <tr>
        <td><span class="badge"><?= e($c['type']) ?></span></td>
        <td><?= e($c['label'] ?: '—') ?></td>
        <td><?php if ($c['url']): ?><a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"><?= e($c['url']) ?></a><?php else: ?>—<?php endif; ?></td>
        <td><?= e($c['username'] ?: '—') ?></td>
        <td>
          <?php if ($c['password'] !== ''): ?>
            <span class="pw" data-pw="<?= e($c['password']) ?>">••••••••</span>
            <button class="btn btn-sm" type="button" onclick="togglePw(this)">Show</button>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this credential?')">
            <input type="hidden" name="_action" value="delete_credential">
            <input type="hidden" name="credential_id" value="<?= (int) $c['id'] ?>">
            <button class="btn btn-sm" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <script>
      function togglePw(btn) {
        const span = btn.previousElementSibling;
        const hidden = span.textContent.startsWith('•');
        span.textContent = hidden ? span.dataset.pw : '••••••••';
        btn.textContent = hidden ? 'Hide' : 'Show';
      }
    </script>
  <?php endif; ?>
</div>

<div class="card" id="tasks">
  <h2 style="font-size:17px;margin-bottom:12px">
    Tasks
    <?php $done = count(array_filter($tasks, fn($t) => $t['status'] === 'done')); ?>
    <span class="muted" style="font-size:13px;font-weight:400">(<?= $done ?>/<?= count($tasks) ?> done)</span>
  </h2>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="add_task">
    <div class="field" style="flex:1;min-width:200px">
      <label>Task *</label>
      <input name="title" required placeholder="e.g. Design login screen">
    </div>
    <div class="field">
      <label>Assign To</label>
      <select name="assigned_to">
        <option value="">— Unassigned —</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int) $emp['id'] ?>"><?= e($emp['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Priority</label>
      <select name="priority">
        <?php foreach (LEAD_PRIORITIES as $s): ?>
          <option value="<?= $s ?>" <?= $s === 'normal' ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Due Date</label>
      <input type="date" name="due_date">
    </div>
    <button class="btn btn-primary" type="submit">Add Task</button>
  </form>

  <?php if (!$tasks): ?>
    <p class="muted">No tasks yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Task</th><th>Assignee</th><th>Priority</th><th>Due</th><th>Status</th><th></th></tr>
      <?php foreach ($tasks as $t): ?>
      <tr style="<?= $t['status'] === 'done' ? 'opacity:.55' : '' ?>">
        <td><strong><?= e($t['title']) ?></strong><?php if ($t['description']): ?><br><span class="muted" style="font-size:13px"><?= e($t['description']) ?></span><?php endif; ?></td>
        <td><?= e($t['assignee_name'] ?: '—') ?></td>
        <td><span class="badge prio-<?= e($t['priority']) ?>"><?= e($t['priority']) ?></span></td>
        <td><?= e($t['due_date'] ?: '—') ?></td>
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
