<?php
// submit.php
header('Content-Type: application/json; charset=utf-8');

// === НАСТРОЙКИ ===
$toEmail = "sales@example.com"; // <-- ЗАМЕНИТЕ на ваш e-mail
$subject = "Новая заявка с сайта CargoBridge";

// === СБОР ДАННЫХ (FormData или JSON) ===
$data = [];
if (!empty($_POST)) {
  $data = $_POST;
} else {
  $raw = file_get_contents("php://input");
  if ($raw) {
    $json = json_decode($raw, true);
    if (is_array($json)) $data = $json;
  }
}

function val($key, $default='') {
  return isset($_POST[$key]) ? trim($_POST[$key]) :
         (isset($_GET[$key]) ? trim($_GET[$key]) :
         (isset($GLOBALS['data'][$key]) ? trim($GLOBALS['data'][$key]) : $default));
}

$name    = val('name');
$phone   = val('phone');
$route   = val('route');
$mode    = val('mode');
$cargo   = val('cargo');
$message = val('message');
$source  = val('source','form');
$consent = val('consent');

// Простая валидация
if (!$name || !$phone) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>'Укажите имя и телефон/контакт.']);
  exit;
}

// Собираем письмо
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$lines = [
  "Источник: $source",
  "Имя: $name",
  "Контакт: $phone",
];
if ($route) $lines[] = "Маршрут: $route";
if ($mode)  $lines[] = "Тип доставки: $mode";
if ($cargo) $lines[] = "Груз: $cargo";
if ($message) $lines[] = "Сообщение: $message";
$lines[] = "Время: $time";
$lines[] = "IP: $ip";
$lines[] = "UA: $ua";

$body = implode("\n", $lines);

// Почта (простой mail). При необходимости замените на SMTP.
$headers = "MIME-Version: 1.0\r\n";
$headers.= "Content-Type: text/plain; charset=utf-8\r\n";
$headers.= "From: Website <no-reply@{$_SERVER['HTTP_HOST']}>\r\n";

@mail($toEmail, $subject, $body, $headers);

// Лог в CSV
$logLine = [
  $time, $source, $name, $phone, $route, $mode,
  str_replace(["\r","\n",";"], [' ',' ',' '], (string)$cargo),
  str_replace(["\r","\n",";"], [' ',' ',' '], (string)$message),
  $ip, $ua
];
$csv = fopen(__DIR__ . "/leads.csv", "a");
if ($csv) {
  fputcsv($csv, $logLine, ';');
  fclose($csv);
}

echo json_encode(['ok'=>true]);
