<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login &mdash; Larkana Tailors</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body style="background: linear-gradient(135deg, #0a1018 0%, #1B242D 55%, #253240 100%); min-height:100vh;">
<div style="padding-top:30px;">
<div class="login-wrap">
  <div class="login-head" style="padding:20px 16px 14px;">
    <img src="assets/logo.jpeg" alt="Larkana Tailors Logo"
         style="height:80px; width:auto; display:block; margin:0 auto 10px; border-radius:4px; object-fit:cover;">
    <h1 style="font-size:18px; letter-spacing:.5px;">Larkana Tailors &amp; Cloth House</h1>
    <p style="font-size:12px; color:#9bb0bf; margin-top:4px;">Gents Specialist &mdash; Islamabad</p>
  </div>
  <div class="login-body">
    <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="?action=login">
      <input type="hidden" name="csrf" value="<?= h(getCsrfLogin()) ?>">
      <div class="form-group">
        <label>Username (یوزر نیم)</label>
        <input type="text" name="username" autocomplete="username" autofocus required value="<?= h($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group" style="margin-top:10px;">
        <label>Password (پاس ورڈ)</label>
        <input type="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="login-btn" style="margin-top:14px;">Login &#10132;</button>
    </form>
  </div>
  <div class="login-footer">
    Lakhmir Khan &mdash; 0300-2151261<br>SOAN GARDEN, Shahid Arcade, Islamabad
  </div>
</div>
</div>
</body>
</html>
