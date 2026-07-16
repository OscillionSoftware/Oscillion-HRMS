<div class="page-head">
  <h1><?= e($client['name']) ?></h1>
  <div>
    <a class="btn" href="/clients">← Back</a>
    <a class="btn btn-primary" href="/clients/<?= (int) $client['id'] ?>/edit">Edit Client</a>
  </div>
</div>

<div class="card">
  <div style="margin-bottom:16px">
    <a class="btn btn-primary" href="tel:<?= e($client['phone']) ?>">📞 Call</a>
    <a class="btn" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D/', '', $client['phone'])) ?>">💬 WhatsApp</a>
    <?php if ($client['email']): ?><a class="btn" href="mailto:<?= e($client['email']) ?>">✉️ Email</a><?php endif; ?>
  </div>
  <div class="detail-grid">
    <div class="item"><div class="k">Phone</div><div class="v"><?= e($client['phone']) ?></div></div>
    <div class="item"><div class="k">Email</div><div class="v"><?= e($client['email'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Company</div><div class="v"><?= e($client['company_name'] ?: '—') ?></div></div>
    <div class="item"><div class="k">City</div><div class="v"><?= e($client['city'] ?: '—') ?></div></div>
    <div class="item"><div class="k">Status</div><div class="v"><span class="badge <?= $client['status'] === 'active' ? 'status-interested' : 'status-closed' ?>"><?= e($client['status']) ?></span></div></div>
    <div class="item"><div class="k">Added</div><div class="v"><?= e($client['created_at']) ?></div></div>
  </div>
  <?php if ($client['address']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Address</span><br><?= e($client['address']) ?></p>
  <?php endif; ?>
  <?php if ($client['notes']): ?>
    <p style="margin-top:14px"><span class="muted" style="font-size:12px;text-transform:uppercase">Notes</span><br><?= nl2br(e($client['notes'])) ?></p>
  <?php endif; ?>
</div>
