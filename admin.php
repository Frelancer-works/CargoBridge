<?php
session_start();

// ===== НАСТРОЙКА =====
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'cargo123'; // замените на свой, можно хранить в .env и читать через getenv()

$csvFile = __DIR__ . '/leads.csv';

// ===== ЛОГИН =====
if (isset($_POST['login']) && isset($_POST['password'])) {
  $u = trim($_POST['login']); $p = trim($_POST['password']);
  if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
    $_SESSION['auth'] = true;
    header('Location: admin.php'); exit;
  } else {
    $error = 'Неверные логин или пароль';
  }
}

// ===== ЛОГАУТ =====
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: admin.php'); exit;
}

// ===== ФУНКЦИЯ ЧТЕНИЯ CSV =====
function readLeads($file) {
  $rows = [];
  if (!file_exists($file)) return $rows;
  if (($h = fopen($file, 'r')) !== false) {
    while (($data = fgetcsv($h, 0, ';')) !== false) {
      // ожидаем: time, source, name, phone, route, mode, cargo, message, ip, ua
      $rows[] = $data;
    }
    fclose($h);
  }
  return array_reverse($rows); // новые сверху
}

// ===== ЭКСПОРТ =====
if (isset($_GET['export']) && (!empty($_SESSION['auth']))) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="leads_export.csv"');
  readfile($csvFile); exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Админка лидов | CargoBridge</title>
<style>
:root{font-size:62.5%;--text:#0e1621;--muted:#6b7b8f;--brand:#2aa56b;--card:#f7f9fc;--radius:1.2rem;--shadow:0 .8rem 2.4rem rgba(0,0,0,.08)}
body{margin:0;font:400 1.6rem/1.6 Inter,system-ui,sans-serif;color:var(--text);background:#fff}
.container{max-width:118rem;margin:0 auto;padding:0 2rem}
header{position:sticky;top:0;background:#fff;border-bottom:1px solid #e8eef6}
.nav{display:flex;align-items:center;gap:2rem;min-height:6.4rem}
.brand{font-weight:700}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:1rem 1.6rem;border-radius:999rem;background:var(--brand);color:#fff;border:0;font-weight:700;cursor:pointer}
.card{background:var(--card);border:1px solid #e8eef6;border-radius:var(--radius);box-shadow:var(--shadow);padding:2rem}
table{width:100%;border-collapse:collapse}
th,td{padding:1rem;border-bottom:1px solid #e8eef6;vertical-align:top}
th{background:#fff;font-weight:700;text-align:left;position:sticky;top:6.4rem}
.muted{color:var(--muted);font-size:1.4rem}
form.login{max-width:36rem;margin:6rem auto}
.input{width:100%;padding:1.2rem 1.4rem;border:1px solid #dce6f1;border-radius:.8rem}
</style>
</head>
<body>
<header>
  <div class="container nav">
    <div class="brand">📊 Админка лидов</div>
    <div style="margin-left:auto;display:flex;gap:.8rem">
      <?php if(!empty($_SESSION['auth'])): ?>
        <a class="btn" href="?export=1">Экспорт CSV</a>
        <a class="btn" href="?logout=1" style="background:#a33">Выйти</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="container" style="padding:2rem 0 4rem">
<?php if(empty($_SESSION['auth'])): ?>
  <form class="login card" method="post" autocomplete="off">
    <h1 style="margin-top:0">Вход</h1>
    <?php if(!empty($error)): ?><div class="muted" style="color:#a33;margin-bottom:1rem"><?php echo htmlspecialchars($error,ENT_QUOTES); ?></div><?php endif; ?>
    <div style="margin-bottom:1rem"><input class="input" name="login" placeholder="Логин" required></div>
    <div style="margin-bottom:1rem"><input class="input" name="password" type="password" placeholder="Пароль" required></div>
    <button class="btn" type="submit">Войти</button>
  </form>
<?php else: 
  $rows = readLeads($csvFile);
?>
  <div class="card" style="overflow:auto">
    <h1 style="margin-top:0">Лиды (<?php echo count($rows); ?>)</h1>
    <table>
      <thead>
        <tr>
          <th>Время</th>
          <th>Источник</th>
          <th>Имя</th>
          <th>Контакт</th>
          <th>Маршрут</th>
          <th>Тип</th>
          <th>Груз</th>
          <th>Сообщение</th>
          <th>IP</th>
          <th>UA</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): 
          // нормализуем массив до 10 колонок
          for ($i=count($r); $i<10; $i++) $r[$i] = '';
        ?>
        <tr>
          <td><?php echo htmlspecialchars($r[0]); ?></td>
          <td><?php echo htmlspecialchars($r[1]); ?></td>
          <td><?php echo htmlspecialchars($r[2]); ?></td>
          <td><?php echo htmlspecialchars($r[3]); ?></td>
          <td><?php echo htmlspecialchars($r[4]); ?></td>
          <td><?php echo htmlspecialchars($r[5]); ?></td>
          <td><?php echo htmlspecialchars($r[6]); ?></td>
          <td><?php echo htmlspecialchars($r[7]); ?></td>
          <td class="muted"><?php echo htmlspecialchars($r[8]); ?></td>
          <td class="muted"><?php echo htmlspecialchars(substr($r[9],0,80)); ?>…</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if(!count($rows)): ?><p class="muted">Пока пусто. Отправьте пробную заявку с сайта.</p><?php endif; ?>
  </div>
<?php endif; ?>
</div>
</body>
</html>
