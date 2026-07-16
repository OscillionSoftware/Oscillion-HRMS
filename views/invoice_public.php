<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Invoice <?= e($invoice['invoice_no']) ?> — <?= e($settings['company_name'] ?? '') ?></title>
<link rel="icon" type="image/png" href="/logo-black.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Great+Vibes&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  :root { --ink: #111114; --muted: #6f6f78; --border: #e5e5e3; --soft: #f6f6f5; }
  body { font-family: "Inter", -apple-system, "Segoe UI", Roboto, sans-serif; background: #d9dce2; color: #1a1a1e;
         font-size: 13px; letter-spacing: -0.005em; }

  /* ---- A4 sheet: 210mm × 297mm ---- */
  .sheet { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; border-radius: 4px;
           box-shadow: 0 3px 18px rgba(0,0,0,.16); padding: 14mm 14mm 10mm; display: flex; flex-direction: column; }
  .toolbar { width: 210mm; margin: 14px auto 0; display: flex; justify-content: space-between; align-items: center; }
  .toolbar .size { color: #5c626e; font-size: 12px; font-weight: 600; letter-spacing: .06em; }
  .btn { padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #fff; cursor: pointer;
         font-family: inherit; font-size: 13.5px; font-weight: 700; }
  .btn-primary { background: var(--ink); border-color: var(--ink); color: #fff; }

  /* ---- Header ---- */
  .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;
          padding-bottom: 16px; border-bottom: 2.5px solid var(--ink); }
  .brand { display: flex; flex-direction: column; align-items: flex-start; gap: 9px; }
  .brand .lockup { height: 13.4mm; width: auto; }
  .brand .gstin { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
  .brand .gstin b { color: var(--ink); font-weight: 700; }
  .inv-meta { text-align: right; }
  .inv-meta h2 { font-size: 30px; font-weight: 800; letter-spacing: .1em; color: var(--ink); }
  .inv-meta .no { font-size: 13px; font-weight: 700; margin-top: 2px; }
  .status { display: inline-block; margin-top: 8px; padding: 4px 14px; border-radius: 999px; font-weight: 700;
            font-size: 11px; text-transform: uppercase; letter-spacing: .08em; border: 1px solid transparent; }
  .st-pending { background: #fffaf0; color: #b54708; border-color: #f5e3c8; }
  .st-partial { background: #eff6ff; color: #175cd3; border-color: #d3e3fb; }
  .st-paid { background: #f0faf4; color: #067647; border-color: #cfe8da; }
  .st-cancelled { background: #fdf1f0; color: #b42318; border-color: #f2d3d0; }

  /* ---- Parties / meta ---- */
  .parties { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 18px; padding: 16px 0; border-bottom: 1px solid var(--border); }
  .k { font-size: 9.5px; text-transform: uppercase; color: var(--muted); letter-spacing: .12em; font-weight: 700; margin-bottom: 5px; }
  .parties strong { font-size: 14px; }
  .parties .v { line-height: 1.55; color: #333; }
  .kv { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 1.5px 0; }
  .kv b { font-weight: 600; }

  /* ---- Items ---- */
  table { width: 100%; border-collapse: collapse; }
  .items { margin-top: 16px; }
  .items th { background: var(--ink); color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .09em;
              padding: 9px 10px; text-align: left; font-weight: 700; }
  .items th:first-child { border-radius: 6px 0 0 6px; }
  .items th:last-child { border-radius: 0 6px 6px 0; }
  .items td { padding: 10px; border-bottom: 1px solid var(--border); font-size: 12.5px; vertical-align: top; }
  .items .desc { font-weight: 600; }
  .items .cat { color: var(--muted); font-size: 11px; margin-top: 2px; }
  td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

  /* ---- Totals ---- */
  .sumrow { display: flex; gap: 26px; margin-top: 14px; align-items: flex-start; }
  .words { flex: 1; background: var(--soft); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; }
  .words .w { font-size: 12.5px; font-weight: 600; line-height: 1.5; margin-top: 3px; }
  .totals { width: 250px; }
  .totals td { padding: 4.5px 0; font-size: 13px; }
  .totals .grand td { border-top: 2px solid var(--ink); font-size: 16px; font-weight: 800; padding-top: 9px; }
  .totals .due td { color: #b42318; font-weight: 800; }

  /* ---- Footer area ---- */
  .payrow { display: flex; gap: 16px; align-items: flex-start; }
  .payto { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; flex: 1; }
  .qrbox { border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px 8px; text-align: center; }
  .qrbox #upiqr img, .qrbox #upiqr canvas { display: block; margin: 0 auto; }
  .qrbox .ql { font-size: 8.5px; letter-spacing: .14em; font-weight: 700; color: var(--muted); margin-top: 7px; }
  .qrbox .qa { font-size: 13px; font-weight: 800; margin-top: 1px; }
  .payto div { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 11.5px; line-height: 1.5; }
  .payto b { font-size: 12px; }
  .bottom { display: flex; gap: 26px; margin-top: 6px; align-items: flex-end; }
  .bottom .tcol { flex: 1; }
  .terms { white-space: pre-line; color: #494950; font-size: 10.8px; line-height: 1.8; }
  .sign { width: 210px; flex-shrink: 0; text-align: center; margin: 24px 0 4px; }
  .sign .handsign { font-family: 'Great Vibes', cursive; font-size: 34px; color: #1a1a5e;
                    transform: rotate(-4deg); line-height: 1.1; padding-bottom: 4px; }
  .sign .rule { border-top: 1px solid var(--ink); margin-bottom: 6px; }
  .sign .for { font-size: 11px; font-weight: 700; }
  .sign .auth { font-size: 10px; color: var(--muted); }
  .foot { margin-top: auto; padding-top: 16px; }
  .foot .thanks { text-align: center; color: var(--muted); font-size: 11px; margin-bottom: 8px; }
  .foot .cn { font-weight: 700; color: var(--ink); }
  .foot .strip { border-top: 1px solid var(--border); padding-top: 10px; display: flex; justify-content: space-between;
                 gap: 14px; color: var(--muted); font-size: 10.3px; }
  .section-title { font-size: 9.5px; text-transform: uppercase; color: var(--muted); letter-spacing: .12em;
                   font-weight: 700; margin: 24px 0 10px; }

  /* ---- Lock screen ---- */
  .lockbox { max-width: 380px; margin: 12vh auto; background: #fff; border-radius: 14px; padding: 32px; text-align: center;
             box-shadow: 0 10px 40px rgba(0,0,0,.2); }
  .lockbox input { width: 100%; padding: 10px 12px; border: 1px solid #d4d4d1; border-radius: 8px; margin: 14px 0;
                   font-size: 15px; font-family: inherit; }
  .err { color: #b42318; font-size: 13px; }

  @media print {
    @page { size: A4; margin: 0; }
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { width: 100%; min-height: 100vh; margin: 0; border-radius: 0; box-shadow: none; }
  }
</style>
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
</head>
<body>
<?php if (!$unlocked): ?>
  <form class="lockbox" method="post">
    <img src="/logo-black.png" alt="" style="width:54px;height:54px">
    <h1 style="font-size:18px;margin-top:10px">This invoice is protected</h1>
    <p style="color:#6f6f78;font-size:13px;margin-top:4px">Enter the password shared with you to view invoice <?= e($invoice['invoice_no']) ?>.</p>
    <?php if ($pwError): ?><p class="err" style="margin-top:8px"><?= e($pwError) ?></p><?php endif; ?>
    <input type="password" name="password" placeholder="Password" autofocus required>
    <button class="btn btn-primary" style="width:100%" type="submit">Unlock Invoice</button>
  </form>
<?php else: ?>
  <div class="toolbar">
    <span class="size">FORMAT: A4 · 210 × 297 mm</span>
    <button class="btn btn-primary" onclick="window.print()">⬇ Download PDF (A4)</button>
  </div>

  <div class="sheet">
    <!-- Header -->
    <div class="head">
      <div class="brand">
        <img class="lockup" src="/logo-full.png" alt="<?= e($settings['company_name'] ?? '') ?>">
        <?php if ($invoice['type'] === 'gst'): ?>
          <div class="gstin">GSTIN: <b><?= e($settings['company_gstin'] ?? '') ?></b></div>
        <?php endif; ?>
      </div>
      <div class="inv-meta">
        <h2><?= $invoice['type'] === 'gst' ? 'TAX INVOICE' : 'INVOICE' ?></h2>
        <div class="no"><?= e($invoice['invoice_no']) ?></div>
        <span class="status st-<?= e($invoice['status']) ?>"><?= e($invoice['status']) ?></span>
      </div>
    </div>

    <!-- Parties -->
    <div class="parties">
      <div>
        <div class="k">Billed To</div>
        <div class="v">
          <strong><?= e($invoice['client_name'] ?: 'Client') ?></strong><br>
          <?= $invoice['client_company'] ? e($invoice['client_company']) . '<br>' : '' ?>
          <?= $invoice['client_address'] ? e($invoice['client_address']) . '<br>' : '' ?>
          <?php
            $lastLine = array_filter([
                $invoice['client_city'] ?: null,
                $invoice['client_phone'] ? 'Mob: ' . $invoice['client_phone'] : null,
            ]);
          ?>
          <?= $lastLine ? e(implode(' · ', $lastLine)) : '' ?>
        </div>
      </div>
      <div>
        <div class="k">Project</div>
        <div class="v"><strong><?= e($invoice['project_name']) ?></strong></div>
      </div>
      <div>
        <div class="k">Invoice Details</div>
        <div class="kv"><span>Issue Date</span><b><?= e($invoice['issue_date']) ?></b></div>
        <div class="kv"><span>Due Date</span><b><?= e($invoice['due_date'] ?: '—') ?></b></div>
        <?php if ($invoice['type'] === 'gst'): ?>
          <div class="kv"><span>GST Rate</span><b><?= e((string) $invoice['gst_percent']) ?>%</b></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Items -->
    <table class="items">
      <tr>
        <th style="width:28px">#</th>
        <th>Description</th>
        <th class="num" style="width:56px">Qty</th>
        <th class="num" style="width:96px">Rate (₹)</th>
        <th class="num" style="width:110px">Amount (₹)</th>
      </tr>
      <?php foreach ($invoice['items'] as $i => $it): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td>
          <div class="desc"><?= e($it['description']) ?></div>
          <div class="cat"><?= e(ucwords(str_replace('_', ' ', $it['category']))) ?></div>
        </td>
        <td class="num"><?= rtrim(rtrim(number_format((float) $it['qty'], 2), '0'), '.') ?></td>
        <td class="num"><?= number_format((float) $it['rate'], 2) ?></td>
        <td class="num"><?= number_format((float) $it['qty'] * (float) $it['rate'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <!-- Totals + amount in words -->
    <div class="sumrow">
      <div class="words">
        <div class="k">Amount in Words</div>
        <div class="w"><?= e(inr_words($invoice['total'])) ?></div>
      </div>
      <table class="totals">
        <tr><td>Subtotal</td><td class="num">₹<?= number_format($invoice['subtotal'], 2) ?></td></tr>
        <?php if ((float) $invoice['discount'] > 0): ?>
          <tr><td>Discount</td><td class="num">− ₹<?= number_format((float) $invoice['discount'], 2) ?></td></tr>
        <?php endif; ?>
        <?php if ($invoice['type'] === 'gst'): ?>
          <tr><td>GST @ <?= e((string) $invoice['gst_percent']) ?>%</td><td class="num">₹<?= number_format($invoice['gst_amount'], 2) ?></td></tr>
        <?php endif; ?>
        <tr class="grand"><td>Total</td><td class="num">₹<?= number_format($invoice['total'], 2) ?></td></tr>
        <?php if ($invoice['amount_paid'] > 0): ?>
          <tr><td>Received</td><td class="num">₹<?= number_format($invoice['amount_paid'], 2) ?></td></tr>
          <tr class="due"><td>Balance Due</td><td class="num">₹<?= number_format($invoice['balance'], 2) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <?php
      $selIds = array_filter(array_map('intval', explode(',', $invoice['payment_account_ids'] ?? '')));
      $showAccounts = $selIds
          ? array_values(array_filter($accounts, fn($a) => in_array((int) $a['id'], $selIds, true)))
          : $accounts;
    ?>
    <?php
      // First selected UPI account with a valid UPI id -> scan-to-pay QR
      $upiId = null;
      foreach ($showAccounts as $a) {
          if ($a['type'] === 'upi' && preg_match('/^[\w.\-]{2,}@[\w]{2,}$/', trim($a['details'] ?? ''))) {
              $upiId = trim($a['details']);
              break;
          }
      }
    ?>
    <?php if ($showAccounts && $invoice['balance'] > 0 && $invoice['status'] !== 'cancelled'): ?>
      <div class="section-title">Payment Options</div>
      <div class="payrow">
        <div class="payto">
          <?php foreach ($showAccounts as $a): ?>
            <?php
              $isBank = $a['type'] === 'bank';
              if ($a['type'] === 'cash' && !$a['details']) continue;
              if ($isBank && !trim($a['account_no'] ?? '') && !trim($a['details'] ?? '')) continue;
            ?>
            <div>
              <b><?= e($a['name']) ?></b>
              <?php if ($isBank): ?>
                <?= trim($a['account_no'] ?? '') ? '<br>A/c No: ' . e($a['account_no']) : '' ?>
                <?= trim($a['ifsc'] ?? '') ? '<br>IFSC: ' . e($a['ifsc']) : '' ?>
              <?php else: ?>
                <?= $a['details'] ? '<br>' . e($a['details']) : '' ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($upiId): ?>
          <div class="qrbox">
            <div id="upiqr"></div>
            <div class="ql">SCAN TO PAY</div>
            <div class="qa">₹<?= number_format($invoice['balance'], 2) ?></div>
          </div>
          <script>
            new QRCode(document.getElementById('upiqr'), {
              text: <?= json_encode('upi://pay?pa=' . $upiId
                        . '&pn=' . rawurlencode($settings['company_name'] ?? '')
                        . '&am=' . number_format($invoice['balance'], 2, '.', '')
                        . '&cu=INR&tn=' . rawurlencode($invoice['invoice_no'])) ?>,
              width: 104, height: 104, correctLevel: QRCode.CorrectLevel.M
            });
          </script>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="bottom">
      <div class="tcol">
        <?php $terms = trim($invoice['terms'] ?? '') ?: trim($settings['default_terms'] ?? ''); ?>
        <?php if ($terms): ?>
          <div class="section-title">Terms & Conditions</div>
          <div class="terms"><?= e($terms) ?></div>
        <?php endif; ?>
      </div>
      <div class="sign">
        <div class="handsign">Deepika</div>
        <div class="rule"></div>
        <div class="for">For <?= e($settings['company_name'] ?? '') ?></div>
        <div class="auth">Authorised Signatory</div>
      </div>
    </div>

    <div class="foot">
      <div class="thanks">Thank you for your business!</div>
      <div class="strip">
        <span>📞 <?= e($settings['company_phone'] ?? '') ?> &nbsp;·&nbsp; ✉ <?= e($settings['company_email'] ?? '') ?></span>
        <span class="cn"><?= e($settings['company_name'] ?? '') ?></span>
      </div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
