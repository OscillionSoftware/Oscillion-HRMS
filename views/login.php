<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Oscillion HRMS</title>
<link rel="icon" type="image/png" href="/logo-black.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="login-wrap">
  <form class="login-card" method="post" action="/login">
    <img src="/logo-black.png" alt="Oscillion" style="width:58px;height:58px;display:block;margin:0 auto 16px">
    <h1 style="text-align:center">Oscillion <span>HRMS</span></h1>
    <p style="text-align:center;letter-spacing:.14em;text-transform:uppercase;font-size:10.5px">Build Beyond Boundaries</p>
    <?php if (!empty($error)): ?><div class="errors"><?= e($error) ?></div><?php endif; ?>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn btn-primary" type="submit">Sign In</button>
  </form>
</div>
<?= csrf_boot_script() ?>
</body>
</html>
