<div class="page-head">
  <h1><?= e($employee['name']) ?></h1>
  <div>
    <a class="btn" href="/employees">← Back</a>
    <a class="btn btn-primary" href="/employees/<?= (int) $employee['id'] ?>/edit">Edit Employee</a>
  </div>
</div>

<div class="card">
  <div style="margin-bottom:16px">
    <a class="btn btn-primary" href="tel:<?= e($employee['phone']) ?>">📞 Call</a>
    <a class="btn" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D/', '', $employee['phone'])) ?>">💬 WhatsApp</a>
    <?php if ($employee['email']): ?><a class="btn" href="mailto:<?= e($employee['email']) ?>">✉️ Email</a><?php endif; ?>
  </div>
  <div class="detail-grid">
    <div class="item"><div class="k">Phone</div><div class="v"><?= e($employee['phone']) ?></div></div>
    <div class="item"><div class="k">Email</div><div class="v"><?= e($employee['email'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Designation</div><div class="v"><?= e($employee['designation'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Department</div><div class="v"><?= e($employee['department'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Joining Date</div><div class="v"><?= e($employee['joining_date'] ?: '—') ?></div></div>
    <?php if (is_admin($user)): ?>
    <div class="item"><div class="k">Salary</div><div class="v"><?= $employee['salary'] !== null ? '₹' . number_format((float) $employee['salary'], 2) : '—' ?></div></div>
    <?php endif; ?>
    <div class="item"><div class="k">Status</div><div class="v"><span class="badge emp-<?= e($employee['status']) ?>"><?= e(str_replace('_', ' ', $employee['status'])) ?></span></div></div>
    <div class="item"><div class="k">Added</div><div class="v"><?= e($employee['created_at']) ?></div></div>
  </div>
  <?php if ($employee['address']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Address</span><br><?= e($employee['address']) ?></p>
  <?php endif; ?>
  <?php if ($employee['notes']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Notes</span><br><?= nl2br(e($employee['notes'])) ?></p>
  <?php endif; ?>
</div>
