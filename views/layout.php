<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Oscillion HRMS</title>
<link rel="icon" type="image/png" href="/logo-black.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/style.css">
</head>
<body>
<?php
$p = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: 'dashboard';
$nav = [
    ['dashboard',  '/',           'Dashboard'],
    ['leads',      '/leads',      'Leads'],
    ['quotations', '/quotations', 'Quotes'],
    ['clients',    '/clients',    'Clients'],
    ['projects',   '/projects',   'Projects'],
    ['tasks',      '/tasks',      'Tasks'],
    ['employees',  '/employees',  'Employees'],
    ['renewals',   '/renewals',   'Renewals'],
    ['invoices',   '/invoices',   'Invoices'],
    ['expenses',   '/expenses',   'Expenses'],
    ['settings',   '/settings',   'Settings'],
];
function nav_active(string $key, string $p): bool {
    return $key === 'dashboard' ? $p === 'dashboard' : str_starts_with($p, $key);
}
?>
<header class="brandbar">
  <div class="wrap">
    <a class="brand" href="/">
      <img src="/logo-white.png" alt="">
      <span class="bname">Oscillion <em>HRMS</em></span>
    </a>
    <form class="topsearch" action="/search" method="get">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="q" placeholder="Search leads, clients, invoices…" value="<?= e($_GET['q'] ?? ($p === 'search' ? '' : '')) ?>">
    </form>
    <div class="brand-right">
      <span class="date"><?= e(date('D, d M Y')) ?></span>
      <div class="userchip">
        <span class="avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
        <span class="uname"><?= e($user['name']) ?></span>
      </div>
      <a class="logout" href="/logout" title="Logout">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H10a2 2 0 0 0-2 2v3h2V5h9v14h-9v-3H8v3a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
        <span>Logout</span>
      </a>
    </div>
  </div>
</header>

<nav class="tabbar">
  <div class="wrap tabs">
    <?php foreach ($nav as [$key, $href, $label]): ?>
      <a href="<?= $href ?>" class="<?= nav_active($key, $p) ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<main class="container">
  <?= $content ?>
</main>
</body>
</html>
