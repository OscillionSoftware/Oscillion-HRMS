<div class="page-head">
  <h1>Dashboard</h1>
  <div>
    <a class="btn" href="/quotations/new">+ Quotation</a>
    <a class="btn" href="/invoices/new">+ Invoice</a>
    <a class="btn btn-primary" href="/leads/new">+ Add Lead</a>
  </div>
</div>

<div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);font-weight:700;margin-bottom:10px">All Modules</div>
<div class="module-grid">
  <?php
    $modules = [
        ['/leads',      '◎', 'Leads',      $stats['total']],
        ['/quotations', '✎', 'Quotes',     (int) $stats['pending_quotes'] . ' pending'],
        ['/clients',    '♟', 'Clients',    (int) $stats['total_clients']],
        ['/projects',   '▣', 'Projects',   (int) $stats['active_projects'] . ' active'],
        ['/tasks',      '☑', 'Tasks',      null],
        ['/employees',  '☺', 'Employees',  (int) $stats['total_employees']],
        ['/renewals',   '↻', 'Renewals',   count($stats['expiring_soon'] ?? []) . ' expiring'],
        ['/invoices',   '▤', 'Invoices',   '₹' . number_format($stats['invoice_outstanding'], 0) . ' due'],
        ['/expenses',   '↯', 'Expenses',   '₹' . number_format($stats['month_expenses'], 0) . ' ' . date('M')],
        ['/settings',   '⚙', 'Settings',   null],
    ];
  ?>
  <?php foreach ($modules as [$href, $icon, $label, $count]): ?>
    <a class="module-tile" href="<?= $href ?>">
      <span class="ic"><?= $icon ?></span>
      <span class="lbl"><?= e($label) ?></span>
      <?php if ($count !== null): ?><span class="cnt"><?= e((string) $count) ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($stats['attention_count'] > 0): ?>
<div class="card" style="border-left:4px solid var(--danger)">
  <h2 style="font-size:16px;margin-bottom:12px">Needs Your Attention Today <span class="muted" style="font-weight:400;font-size:13px">(<?= $stats['attention_count'] ?> item<?= $stats['attention_count'] === 1 ? '' : 's' ?>)</span></h2>
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <?php if ($stats['today_followups']): ?>
      <a class="btn btn-sm" href="/leads" style="border-color:#f2d3d0">📞 <?= count($stats['today_followups']) ?> follow-up<?= count($stats['today_followups']) === 1 ? '' : 's' ?> due</a>
    <?php endif; ?>
    <?php if ($stats['due_tasks']): ?>
      <a class="btn btn-sm" href="/tasks" style="border-color:#f2d3d0">☑ <?= count($stats['due_tasks']) ?> task<?= count($stats['due_tasks']) === 1 ? '' : 's' ?> due/overdue</a>
    <?php endif; ?>
    <?php if ($stats['overdue_invoices']): ?>
      <a class="btn btn-sm" href="/invoices?status=pending" style="border-color:#f2d3d0">▤ <?= count($stats['overdue_invoices']) ?> invoice<?= count($stats['overdue_invoices']) === 1 ? '' : 's' ?> overdue (₹<?= number_format(array_sum(array_column($stats['overdue_invoices'], 'balance')), 0) ?>)</a>
    <?php endif; ?>
    <?php if ($stats['urgent_renewals']): ?>
      <a class="btn btn-sm" href="/renewals" style="border-color:#f2d3d0">↻ <?= count($stats['urgent_renewals']) ?> domain/hosting expiring within 7 days</a>
    <?php endif; ?>
  </div>
  <?php if ($stats['due_tasks']): ?>
    <table style="margin-top:14px">
      <tr><th>Task</th><th>Project</th><th>Assignee</th><th>Due</th></tr>
      <?php foreach (array_slice($stats['due_tasks'], 0, 5) as $t): ?>
        <?php $overdue = strtotime($t['due_date']) < strtotime(date('Y-m-d')); ?>
        <tr>
          <td><strong><?= e($t['title']) ?></strong></td>
          <td><a href="/projects/<?= (int) $t['project_id'] ?>#tasks"><?= e($t['project_name']) ?></a></td>
          <td><?= e($t['assignee_name'] ?: '—') ?></td>
          <td><span class="badge <?= $overdue ? 'status-not_interested' : 'status-follow_up' ?>"><?= e($t['due_date']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat">
    <div class="num"><?= $stats['total'] ?></div>
    <div class="label"><a href="/leads">Leads</a> · <?= (int) ($stats['by_status']['follow_up'] ?? 0) ?> follow up</div>
  </div>
  <div class="stat">
    <div class="num"><?= (int) $stats['pending_quotes'] ?></div>
    <div class="label"><a href="/quotations?status=pending">Pending Quotes</a></div>
  </div>
  <div class="stat">
    <div class="num"><?= (int) $stats['total_clients'] ?></div>
    <div class="label"><a href="/clients">Clients</a></div>
  </div>
  <div class="stat">
    <div class="num"><?= (int) $stats['active_projects'] ?><span class="muted" style="font-size:15px">/<?= (int) $stats['total_projects'] ?></span></div>
    <div class="label"><a href="/projects">Active Projects</a></div>
  </div>
  <div class="stat">
    <div class="num"><?= (int) $stats['total_employees'] ?></div>
    <div class="label"><a href="/employees">Employees</a></div>
  </div>
  <div class="stat">
    <div class="num">₹<?= number_format($stats['invoice_outstanding'], 0) ?></div>
    <div class="label"><a href="/invoices?status=pending">To Collect</a></div>
  </div>
  <div class="stat">
    <div class="num">₹<?= number_format($stats['month_expenses'], 0) ?></div>
    <div class="label"><a href="/expenses">Expenses (<?= date('M') ?>)</a></div>
  </div>
</div>

<?php if (!empty($stats['expiring_soon'])): ?>
<div class="card" style="border-left:4px solid var(--warning)">
  <h2 style="font-size:16px;margin-bottom:12px">Hosting / Domains expiring within 30 days</h2>
  <table>
    <tr><th>Name</th><th>Type</th><th>Project</th><th>Expiry</th><th>Left</th></tr>
    <?php foreach ($stats['expiring_soon'] as $s): ?>
    <?php $days = (int) $s['days_left']; ?>
    <tr>
      <td><strong><?= e($s['name']) ?></strong></td>
      <td><span class="badge"><?= e($s['type']) ?></span></td>
      <td><a href="/projects/<?= (int) $s['project_id'] ?>"><?= e($s['project_name']) ?></a></td>
      <td><?= e($s['expiry_date']) ?></td>
      <td><span class="badge <?= $days < 0 ? 'status-not_interested' : 'status-follow_up' ?>"><?= $days < 0 ? abs($days) . 'd overdue' : $days . ' days' ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<div class="dash-cols">
  <div class="card">
    <h2 style="font-size:16px;margin-bottom:12px">Today's Follow-ups <span class="muted" style="font-weight:400;font-size:13px">(incl. overdue)</span></h2>
    <?php if (!$stats['today_followups']): ?>
      <p class="muted">Nothing due today. 🎉</p>
    <?php else: ?>
      <table>
        <tr><th>Date</th><th>Lead</th><th>Type</th><th>Message</th><th></th></tr>
        <?php foreach ($stats['today_followups'] as $fu): ?>
        <tr>
          <td style="white-space:nowrap"><?= e($fu['followup_date']) ?></td>
          <td>
            <a href="/leads/<?= (int) $fu['lead_id'] ?>"><strong><?= e($fu['lead_name']) ?></strong></a><br>
            <a href="tel:<?= e($fu['lead_phone']) ?>" class="muted" style="font-size:12px"><?= e($fu['lead_phone']) ?></a>
          </td>
          <td><span class="badge"><?= e($fu['type']) ?></span></td>
          <td><?= e($fu['message']) ?></td>
          <td><a class="btn btn-sm" href="/leads/<?= (int) $fu['lead_id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin-bottom:12px">Open Invoices <span class="muted" style="font-weight:400;font-size:13px">₹<?= number_format($stats['invoice_outstanding'], 2) ?> to collect</span></h2>
    <?php if (!$stats['open_invoices']): ?>
      <p class="muted">No pending invoices. All collected. ✅</p>
    <?php else: ?>
      <table>
        <tr><th>Invoice</th><th>Client</th><th>Due</th><th>Balance</th><th>Status</th></tr>
        <?php foreach (array_slice($stats['open_invoices'], 0, 6) as $inv): ?>
        <tr>
          <td><a href="/invoices/<?= (int) $inv['id'] ?>"><strong><?= e($inv['invoice_no']) ?></strong></a></td>
          <td><?= e($inv['client_name'] ?: $inv['project_name']) ?></td>
          <td style="white-space:nowrap"><?= e($inv['due_date'] ?: '—') ?></td>
          <td><strong>₹<?= number_format($inv['balance'], 0) ?></strong></td>
          <td><span class="badge inv-<?= e($inv['status']) ?>"><?= e($inv['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
