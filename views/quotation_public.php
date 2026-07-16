<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Quotation <?= e($quotation['quote_no']) ?> — <?= e($settings['company_name'] ?? '') ?></title>
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
  .btn-accept { background: #1a7a44; border-color: #1a7a44; color: #fff; font-size: 15px; padding: 14px 28px; }

  /* ---- Header ---- */
  .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;
          padding-bottom: 16px; border-bottom: 2.5px solid var(--ink); }
  .brand { display: flex; flex-direction: column; align-items: flex-start; gap: 9px; }
  .brand .lockup { height: 13.4mm; width: auto; }
  .brand .gstin { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
  .brand .gstin b { color: var(--ink); font-weight: 700; }
  .inv-meta { text-align: right; }
  .inv-meta h2 { font-size: 28px; font-weight: 800; letter-spacing: .08em; color: var(--ink); }
  .inv-meta .no { font-size: 13px; font-weight: 700; margin-top: 2px; }
  .status { display: inline-block; margin-top: 8px; padding: 4px 14px; border-radius: 999px; font-weight: 700;
            font-size: 11px; text-transform: uppercase; letter-spacing: .08em; border: 1px solid transparent; }
  .st-pending { background: #fffaf0; color: #b54708; border-color: #f5e3c8; }
  .st-accepted { background: #f0faf4; color: #067647; border-color: #cfe8da; }
  .st-rejected { background: #fdf1f0; color: #b42318; border-color: #f2d3d0; }

  /* ---- Parties / meta ---- */
  .parties { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 18px; padding: 16px 0; border-bottom: 1px solid var(--border); }
  .k { font-size: 9.5px; text-transform: uppercase; color: var(--muted); letter-spacing: .12em; font-weight: 700; margin-bottom: 5px; }
  .parties strong { font-size: 14px; }
  .parties .v { line-height: 1.55; color: #333; }
  .kv { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 1.5px 0; }
  .kv b { font-weight: 600; }

  /* ---- Scope ---- */
  .scope-box { margin-top: 16px; background: var(--soft); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; }
  .scope-box .txt { font-size: 12.5px; line-height: 1.7; color: #333; white-space: pre-line; margin-top: 3px; }

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

  /* ---- Accept bar ---- */
  .accept-bar { text-align: center; margin: 20px 0; padding: 18px; background: var(--soft); border: 1px solid var(--border); border-radius: 10px; }
  .accept-bar p { color: var(--muted); font-size: 11.5px; margin-top: 8px; }
  .accepted-note { background: #f0faf4; color: #067647; border: 1px solid #cfe8da; border-radius: 8px; padding: 12px 16px; text-align: center; font-weight: 700; margin: 16px 0; font-size: 13px; }
  .rejected-note { background: #fdf1f0; color: #b42318; border: 1px solid #f2d3d0; border-radius: 8px; padding: 12px 16px; text-align: center; font-weight: 700; margin: 16px 0; font-size: 13px; }
  .expired-note { background: #fff4e0; color: #b5750f; border: 1px solid #f5e3c8; border-radius: 8px; padding: 12px 16px; text-align: center; font-weight: 700; margin: 16px 0; font-size: 13px; }

  /* ---- Footer area ---- */
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

  @media print {
    @page { size: A4; margin: 0; }
    body { background: #fff; }
    .toolbar, .accept-bar form button { display: none; }
    .toolbar { display: none; }
    .sheet { width: 100%; min-height: 100vh; margin: 0; border-radius: 0; box-shadow: none; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <span class="size">FORMAT: A4 · 210 × 297 mm</span>
    <button class="btn btn-primary" onclick="window.print()">⬇ Download PDF (A4)</button>
  </div>

  <div class="sheet">
    <!-- Header -->
    <div class="head">
      <div class="brand">
        <img class="lockup" src="/logo-full.png" alt="<?= e($settings['company_name'] ?? '') ?>">
        <?php if ($quotation['type'] === 'gst'): ?>
          <div class="gstin">GSTIN: <b><?= e($settings['company_gstin'] ?? '') ?></b></div>
        <?php endif; ?>
      </div>
      <div class="inv-meta">
        <h2>QUOTATION</h2>
        <div class="no"><?= e($quotation['quote_no']) ?></div>
        <span class="status st-<?= e($quotation['status']) ?>"><?= e($quotation['status']) ?></span>
      </div>
    </div>

    <!-- Parties -->
    <div class="parties">
      <div>
        <div class="k">Prepared For</div>
        <div class="v">
          <strong><?= e($quotation['display_name'] ?: 'Client') ?></strong><br>
          <?= $quotation['display_company'] ? e($quotation['display_company']) . '<br>' : '' ?>
          <?php
            $lastLine = array_filter([
                $quotation['display_phone'] ? 'Mob: ' . $quotation['display_phone'] : null,
                $quotation['display_email'] ?: null,
            ]);
          ?>
          <?= $lastLine ? e(implode(' · ', $lastLine)) : '' ?>
        </div>
      </div>
      <div>
        <div class="k">Project</div>
        <div class="v"><strong><?= e($quotation['title']) ?></strong></div>
      </div>
      <div>
        <div class="k">Quotation Details</div>
        <div class="kv"><span>Issue Date</span><b><?= e($quotation['issue_date']) ?></b></div>
        <div class="kv"><span>Valid Until</span><b><?= e($quotation['valid_until'] ?: '—') ?></b></div>
        <?php if ($quotation['type'] === 'gst'): ?>
          <div class="kv"><span>GST Rate</span><b><?= e((string) $quotation['gst_percent']) ?>%</b></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($quotation['scope']): ?>
      <div class="scope-box">
        <div class="k">Scope of Work</div>
        <div class="txt"><?= e($quotation['scope']) ?></div>
      </div>
    <?php endif; ?>

    <!-- Items -->
    <table class="items">
      <tr>
        <th style="width:28px">#</th>
        <th>Description</th>
        <th class="num" style="width:56px">Qty</th>
        <th class="num" style="width:96px">Rate (₹)</th>
        <th class="num" style="width:110px">Amount (₹)</th>
      </tr>
      <?php foreach ($quotation['items'] as $i => $it): ?>
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
        <div class="w"><?= e(inr_words($quotation['total'])) ?></div>
      </div>
      <table class="totals">
        <tr><td>Subtotal</td><td class="num">₹<?= number_format($quotation['subtotal'], 2) ?></td></tr>
        <?php if ((float) $quotation['discount'] > 0): ?>
          <tr><td>Discount</td><td class="num">− ₹<?= number_format((float) $quotation['discount'], 2) ?></td></tr>
        <?php endif; ?>
        <?php if ($quotation['type'] === 'gst'): ?>
          <tr><td>GST @ <?= e((string) $quotation['gst_percent']) ?>%</td><td class="num">₹<?= number_format($quotation['gst_amount'], 2) ?></td></tr>
        <?php endif; ?>
        <tr class="grand"><td>Total</td><td class="num">₹<?= number_format($quotation['total'], 2) ?></td></tr>
      </table>
    </div>

    <?php if (!empty($accepted)): ?>
      <div class="accepted-note">🎉 Thank you! Your acceptance has been recorded. We'll contact you shortly to begin the project.</div>
    <?php elseif ($quotation['status'] === 'accepted'): ?>
      <div class="accepted-note">✓ This quotation has been accepted.</div>
    <?php elseif ($quotation['status'] === 'rejected'): ?>
      <div class="rejected-note">This quotation was declined. Contact us if you'd like a revised offer.</div>
    <?php elseif ($quotation['expired']): ?>
      <div class="expired-note">This quotation expired on <?= e($quotation['valid_until']) ?>. Contact us for an updated quotation.</div>
    <?php else: ?>
      <div class="accept-bar">
        <form method="post" onsubmit="return confirm('Accept this quotation for ₹<?= number_format($quotation['total'], 2) ?>?')">
          <input type="hidden" name="_action" value="accept">
          <button class="btn btn-accept" type="submit">✓ Accept This Quotation</button>
        </form>
        <p>By accepting you agree to the terms below. Valid until <?= e($quotation['valid_until'] ?: 'further notice') ?>.</p>
      </div>
    <?php endif; ?>

    <div class="bottom">
      <div class="tcol">
        <?php $terms = trim($quotation['terms'] ?? '') ?: trim($settings['default_terms'] ?? ''); ?>
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
      <div class="thanks">Thank you for considering us for your project!</div>
      <div class="strip">
        <span>📞 <?= e($settings['company_phone'] ?? '') ?> &nbsp;·&nbsp; ✉ <?= e($settings['company_email'] ?? '') ?></span>
        <span class="cn"><?= e($settings['company_name'] ?? '') ?></span>
      </div>
    </div>
  </div>
</body>
</html>
