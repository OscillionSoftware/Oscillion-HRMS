<div class="page-head">
  <h1><?= e($lead['name']) ?></h1>
  <div>
    <a class="btn" href="/leads">← Back</a>
    <a class="btn btn-primary" href="/leads/<?= (int) $lead['id'] ?>/edit">Edit Lead</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="item"><div class="k">Phone</div><div class="v"><a href="tel:<?= e($lead['phone']) ?>"><?= e($lead['phone']) ?></a></div></div>
    <div class="item"><div class="k">Email</div><div class="v"><?= e($lead['email'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Company</div><div class="v"><?= e($lead['company_name'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Status</div><div class="v"><span class="badge status-<?= e($lead['status']) ?>"><?= e(str_replace('_', ' ', $lead['status'])) ?></span></div></div>
    <div class="item"><div class="k">Behaviour</div><div class="v"><span class="badge"><?= e(str_replace('_', ' ', $lead['behaviour'])) ?></span></div></div>
    <div class="item"><div class="k">Priority</div><div class="v"><span class="badge prio-<?= e($lead['priority']) ?>"><?= e($lead['priority']) ?></span></div></div>
    <div class="item"><div class="k">Created</div><div class="v"><?= e($lead['created_at']) ?></div></div>
  </div>
  <?php if ($lead['notes']): ?>
    <p style="margin-top:14px"><span class="k muted" style="font-size:12px;text-transform:uppercase">Notes</span><br><?= nl2br(e($lead['notes'])) ?></p>
  <?php endif; ?>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Add Follow-up</h2>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
  <?php endif; ?>
  <form method="post" class="filters">
    <input type="hidden" name="_action" value="add_followup">
    <div class="field">
      <label>Next Date *</label>
      <input type="date" name="followup_date" required value="<?= e(date('Y-m-d')) ?>">
    </div>
    <div class="field">
      <label>Type</label>
      <select name="type">
        <?php foreach (FOLLOWUP_TYPES as $t): ?>
          <option value="<?= $t ?>"><?= e(ucfirst($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="flex:1;min-width:220px">
      <label>Message / Notes</label>
      <input name="message" placeholder="e.g. Send quotation and call after lunch">
    </div>
    <button class="btn btn-primary" type="submit">Add Follow-up</button>
  </form>
</div>

<div class="card">
  <h2 style="font-size:17px;margin-bottom:12px">Follow-up History</h2>
  <?php if (!$followups): ?>
    <p class="muted">No follow-ups yet.</p>
  <?php else: ?>
    <?php foreach ($followups as $fu): ?>
      <div class="followup <?= $fu['status'] === 'done' ? 'done' : '' ?>">
        <strong><?= e($fu['followup_date']) ?></strong>
        <span class="badge"><?= e($fu['type']) ?></span>
        <span class="badge <?= $fu['status'] === 'done' ? 'status-converted' : 'status-follow_up' ?>"><?= e($fu['status']) ?></span>
        <?php if ($fu['message']): ?><div style="margin-top:4px"><?= e($fu['message']) ?></div><?php endif; ?>
        <div class="meta">
          Added by <?= e($fu['created_by_name'] ?? '—') ?> on <?= e($fu['created_at']) ?>
          <?php if ($fu['status'] === 'pending'): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="_action" value="followup_done">
              <input type="hidden" name="followup_id" value="<?= (int) $fu['id'] ?>">
              <button class="btn btn-sm" type="submit">Mark Done</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
