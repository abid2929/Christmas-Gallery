<?php
// Receives an order from the checkout form and saves it.
declare(strict_types=1);
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $a, int $code = 200): never { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'msg' => 'POST only'], 405);
$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) out(['ok' => false, 'msg' => 'ভুল তথ্য'], 400);
if (!empty($in['website'])) out(['ok' => true, 'code' => 'CG0000']); // honeypot

$name = trim(mb_substr((string)($in['name'] ?? ''), 0, 80));
$phone = preg_replace('/[^0-9+]/', '', strtr((string)($in['phone'] ?? ''), ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']));
$address = trim(mb_substr((string)($in['address'] ?? ''), 0, 400));
$note = trim(mb_substr((string)($in['note'] ?? ''), 0, 400));
$area = ($in['area'] ?? '') === 'outside' ? 'outside' : 'dhaka';
$digits = preg_replace('/^\+?88/', '', $phone);

if ($name === '' || $address === '') out(['ok' => false, 'msg' => 'নাম আর ঠিকানা লিখুন'], 422);
if (!preg_match('/^01[3-9]\d{8}$/', $digits)) out(['ok' => false, 'msg' => 'সঠিক মোবাইল নম্বর দিন (১১ সংখ্যা)'], 422);

// simple flood guard: max 5 orders per IP per 10 minutes
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$since = (new DateTime('-10 minutes', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d H:i:s');
$c = db()->prepare('SELECT COUNT(*) FROM orders WHERE ip=? AND created_at > ?');
$c->execute([$ip, $since]);
if ((int)$c->fetchColumn() >= 5) out(['ok' => false, 'msg' => 'একটু পরে আবার চেষ্টা করুন'], 429);

// prices always come from the database, never from the browser
$items = []; $subtotal = 0;
$q = db()->prepare('SELECT * FROM products WHERE id=? AND active=1');
foreach ((array)($in['items'] ?? []) as $it) {
    $id = (int)($it['id'] ?? 0); $qty = max(1, min(50, (int)($it['qty'] ?? 1)));
    $q->execute([$id]); $p = $q->fetch();
    if (!$p || (int)$p['price'] <= 0 || !(int)$p['stock']) continue;
    $line = (int)$p['price'] * $qty; $subtotal += $line;
    $items[] = ['id' => $id, 'name' => $p['name'], 'size' => $p['size'], 'price' => (int)$p['price'], 'qty' => $qty, 'line' => $line];
    if (count($items) >= 30) break;
}
if (!$items) out(['ok' => false, 'msg' => 'কার্টে কোনো প্রোডাক্ট নেই'], 422);

$delivery = (int)setting($area === 'outside' ? 'delivery_outside' : 'delivery_dhaka', '0');
$total = $subtotal + $delivery;
$now = (new DateTime('now', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d H:i:s');

db()->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,ip,created_at) VALUES("",?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$name, $digits, $address, $area, $note, json_encode($items, JSON_UNESCAPED_UNICODE), $subtotal, $delivery, $total, $ip, $now]);
$id = (int)db()->lastInsertId();
$code = 'CG' . str_pad((string)(1000 + $id), 4, '0', STR_PAD_LEFT);
db()->prepare('UPDATE orders SET code=? WHERE id=?')->execute([$code, $id]);

out(['ok' => true, 'code' => $code, 'items' => $items, 'subtotal' => $subtotal, 'delivery' => $delivery, 'total' => $total]);
