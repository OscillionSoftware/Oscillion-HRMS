<div class="page-head">
  <h1>Search <?= $q !== '' ? '— "' . e($q) . '"' : '' ?></h1>
</div>

<div class="card">
  <form class="filters" method="get" action="/search">
    <div class="field" style="flex:1">
      <label>Search everything</label>
      <input type="text" name="q" autofocus placeholder="Name, phone, company, invoice no…" value="<?= e($q) ?>">
    </div>
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
</div>

<?php
$total = count($results['leads']) + count($results['clients']) + count($results['projects'])
       + count($results['invoices']) + count($results['quotations']);
?>

<?php if ($q === ''): ?>
  <p class="muted">Type a name, phone number, company, invoice number or quote number above.</p>
<?php elseif ($total === 0): ?>
  <p class="muted">No matches for "<?= e($q) ?>".</p>
<?php else: ?>

  <?php if ($results['leads']): ?>
  <div class="card">
    <h2 style="font-size:15px;margin-bottom:10px">Leads</h2>
    <table>
      <tr><th>Name</th><th>Phone</th><th>Company</th><th></th></tr>
      <?php foreach ($results['leads'] as $r): ?>
        <tr>
          <td><a href="/leads/<?= (int) $r['id'] ?>"><strong><?= e($r['name']) ?></strong></a></td>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['company_name'] ?: '—') ?></td>
          <td><a class="btn btn-sm" href="/leads/<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($results['clients']): ?>
  <div class="card">
    <h2 style="font-size:15px;margin-bottom:10px">Clients</h2>
    <table>
      <tr><th>Name</th><th>Phone</th><th>Company</th><th></th></tr>
      <?php foreach ($results['clients'] as $r): ?>
        <tr>
          <td><a href="/clients/<?= (int) $r['id'] ?>"><strong><?= e($r['name']) ?></strong></a></td>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['company_name'] ?: '—') ?></td>
          <td><a class="btn btn-sm" href="/clients/<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($results['projects']): ?>
  <div class="card">
    <h2 style="font-size:15px;margin-bottom:10px">Projects</h2>
    <table>
      <tr><th>Project</th><th>Client</th><th></th></tr>
      <?php foreach ($results['projects'] as $r): ?>
        <tr>
          <td><a href="/projects/<?= (int) $r['id'] ?>"><strong><?= e($r['name']) ?></strong></a></td>
          <td><?= e($r['client_name'] ?: '—') ?></td>
          <td><a class="btn btn-sm" href="/projects/<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($results['invoices']): ?>
  <div class="card">
    <h2 style="font-size:15px;margin-bottom:10px">Invoices</h2>
    <table>
      <tr><th>Invoice No</th><th>Client</th><th></th></tr>
      <?php foreach ($results['invoices'] as $r): ?>
        <tr>
          <td><a href="/invoices/<?= (int) $r['id'] ?>"><strong><?= e($r['invoice_no']) ?></strong></a></td>
          <td><?= e($r['client_name'] ?: '—') ?></td>
          <td><a class="btn btn-sm" href="/invoices/<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($results['quotations']): ?>
  <div class="card">
    <h2 style="font-size:15px;margin-bottom:10px">Quotations</h2>
    <table>
      <tr><th>Quote No</th><th>Title</th><th>Prospect</th><th></th></tr>
      <?php foreach ($results['quotations'] as $r): ?>
        <tr>
          <td><a href="/quotations/<?= (int) $r['id'] ?>"><strong><?= e($r['quote_no']) ?></strong></a></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['prospect_name'] ?: '—') ?></td>
          <td><a class="btn btn-sm" href="/quotations/<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

<?php endif; ?>
