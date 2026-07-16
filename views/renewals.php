<div class="page-head">
  <h1>Hosting & Domain Renewals</h1>
</div>

<div class="card">
  <form class="filters" method="get" action="/renewals">
    <div class="field">
      <label>Search</label>
      <input type="text" name="search" placeholder="Domain, provider, project" value="<?= e($filters['search']) ?>">
    </div>
    <div class="field">
      <label>Type</label>
      <select name="type">
        <option value="">All</option>
        <?php foreach (SERVICE_TYPES as $s): ?>
          <option value="<?= $s ?>" <?= $filters['type'] === $s ? 'selected' : '' ?>><?= e(strtoupper($s) === 'SSL' ? 'SSL' : ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Expiring within</label>
      <select name="expiring_days">
        <option value="">Any time</option>
        <?php foreach ([30 => '30 days', 60 => '60 days', 90 => '90 days'] as $d => $lbl): ?>
          <option value="<?= $d ?>" <?= (string) $filters['expiring_days'] === (string) $d ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/renewals">Reset</a>
  </form>

  <?php if (!$services): ?>
    <p class="muted">No hosting/domain records. Add them from a project's page.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Type</th><th>Provider</th><th>Project</th><th>Expiry</th><th>Left</th><th>Term</th><th>Our Cost</th><th>Client Pays</th><th>Margin</th></tr>
      <?php foreach ($services as $s): ?>
      <?php
        $days = (int) $s['days_left'];
        $cls = $days < 0 ? 'status-not_interested' : ($days <= 30 ? 'status-follow_up' : 'status-interested');
        $left = $days < 0 ? abs($days) . 'd overdue' : $days . ' days';
        $margin = ($s['our_cost'] !== null && $s['client_charge'] !== null) ? $s['client_charge'] - $s['our_cost'] : null;
      ?>
      <tr>
        <td><strong><?= e($s['name']) ?></strong><?= $s['auto_renew'] ? ' <span class="badge">auto</span>' : '' ?></td>
        <td><span class="badge"><?= e($s['type']) ?></span></td>
        <td><?= e($s['provider'] ?: '—') ?></td>
        <td><a href="/projects/<?= (int) $s['project_id'] ?>"><?= e($s['project_name']) ?></a></td>
        <td><?= e($s['expiry_date']) ?></td>
        <td><span class="badge <?= $cls ?>"><?= e($left) ?></span></td>
        <td><?= (int) $s['years'] ?> yr</td>
        <td><?= $s['our_cost'] !== null ? '₹' . number_format((float) $s['our_cost'], 2) : '—' ?></td>
        <td><?= $s['client_charge'] !== null ? '₹' . number_format((float) $s['client_charge'], 2) : '—' ?></td>
        <td><?= $margin !== null ? '₹' . number_format($margin, 2) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
