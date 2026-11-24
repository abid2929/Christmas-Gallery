<?php
// Christmas Gallery - Admin panel (Bangla)
declare(strict_types=1);
require __DIR__ . '/../inc/db.php';
session_name('cgadmin');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
date_default_timezone_set('Asia/Dhaka');

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Session শেষ। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।'); } }
function go(string $q, string $msg = ''): never { if ($msg) $_SESSION['flash'] = $msg; header('Location: ?' . $q); exit; }

$STATUS = ['new' => 'নতুন', 'confirmed' => 'কনফার্ম', 'shipped' => 'পাঠানো হয়েছে', 'delivered' => 'ডেলিভারি হয়েছে', 'cancelled' => 'বাতিল'];

// ---------- login / logout ----------
$err = '';
if (isset($_GET['logout'])) { session_destroy(); header('Location: ./'); exit; }
if (empty($_SESSION['ok'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
        $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
        if ($_SESSION['tries'] > 8) { sleep(2); }
        if (password_verify((string)$_POST['pass'], setting('admin_pass'))) {
            session_regenerate_id(true); $_SESSION['ok'] = 1; $_SESSION['tries'] = 0; $_SESSION['csrf'] = bin2hex(random_bytes(16));
            header('Location: ./'); exit;
        }
        $err = 'পাসওয়ার্ড ভুল';
    }
    ?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin | Christmas Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#7d0a12,#b3121f);font-family:"Hind Siliguri",sans-serif;padding:16px;box-sizing:border-box}
    form{background:#fff;border-radius:20px;padding:30px;width:100%;max-width:360px;box-shadow:0 20px 50px rgba(0,0,0,.3)}h1{margin:0 0 4px;font-size:24px}p{margin:0 0 18px;color:#777}
    input{width:100%;box-sizing:border-box;font:inherit;font-size:17px;padding:12px 14px;border:2px solid #ecd6d6;border-radius:12px;margin-bottom:12px}button{width:100%;font:inherit;font-size:17px;font-weight:700;padding:12px;border:0;border-radius:12px;background:#b3121f;color:#fff;cursor:pointer}.e{color:#c01622;font-weight:600;margin-bottom:10px}</style></head>
    <body><form method="post"><h1>🎄 Christmas Gallery</h1><p>Admin প্যানেলে ঢুকুন</p><?php if ($err): ?><div class="e"><?= h($err) ?></div><?php endif; ?>
    <input type="password" name="pass" placeholder="পাসওয়ার্ড" autofocus required><button>লগইন</button></form></body></html><?php
    exit;
}

// ---------- actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $a = $_POST['a'] ?? '';
    if ($a === 'status') {
        $s = $_POST['status'] ?? 'new';
        if (isset($STATUS[$s])) db()->prepare('UPDATE orders SET status=?, seen=1 WHERE id=?')->execute([$s, (int)$_POST['id']]);
        go('t=orders&f=' . urlencode($_GET['f'] ?? ''), 'স্ট্যাটাস আপডেট হয়েছে');
    }
    if ($a === 'del_order') {
        db()->prepare('DELETE FROM orders WHERE id=?')->execute([(int)$_POST['id']]);
        go('t=orders', 'অর্ডার মুছে ফেলা হয়েছে');
    }
    if ($a === 'save_product') {
        $id = (int)($_POST['id'] ?? 0);
        $f = [
            'type' => ($_POST['type'] ?? '') === 'tree' ? 'tree' : 'item',
            'name' => trim(mb_substr((string)$_POST['name'], 0, 120)),
            'descr' => trim(mb_substr((string)$_POST['descr'], 0, 600)),
            'price' => max(0, (int)bn2en($_POST['price'] ?? '0')),
            'old_price' => max(0, (int)bn2en($_POST['old_price'] ?? '0')),
            'badge' => trim(mb_substr((string)$_POST['badge'], 0, 20)),
            'size' => trim(mb_substr((string)$_POST['size'], 0, 20)),
            'stock' => !empty($_POST['stock']) ? 1 : 0,
            'active' => !empty($_POST['active']) ? 1 : 0,
            'sort' => (int)bn2en($_POST['sort'] ?? '0'),
        ];
        if ($f['name'] === '') go('t=products&edit=' . $id, 'নাম লিখুন');
        $img = save_upload('photo');
        if ($img === false) go('t=products&edit=' . $id, 'ছবিটা JPG/PNG/WEBP হতে হবে (১০ MB এর কম)');
        if ($id) {
            $sql = 'UPDATE products SET type=:type,name=:name,descr=:descr,price=:price,old_price=:old_price,badge=:badge,size=:size,stock=:stock,active=:active,sort=:sort' . ($img ? ',img=:img,img_cls=:cls' : '') . ' WHERE id=:id';
            $f['id'] = $id; if ($img) { $f['img'] = $img; $f['cls'] = ''; }
            db()->prepare($sql)->execute($f);
        } else {
            $f['img'] = $img ?: 'img/t1.webp'; $f['cls'] = '';
            db()->prepare('INSERT INTO products(type,name,descr,price,old_price,badge,size,stock,active,sort,img,img_cls) VALUES(:type,:name,:descr,:price,:old_price,:badge,:size,:stock,:active,:sort,:img,:cls)')->execute($f);
        }
        go('t=products', 'প্রোডাক্ট সেভ হয়েছে');
    }
    if ($a === 'del_product') {
        db()->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_POST['id']]);
        go('t=products', 'প্রোডাক্ট মুছে ফেলা হয়েছে');
    }
    if ($a === 'quick_price') {
        foreach ((array)($_POST['price'] ?? []) as $pid => $v) db()->prepare('UPDATE products SET price=? WHERE id=?')->execute([max(0, (int)bn2en((string)$v)), (int)$pid]);
        go('t=products', 'দাম সেভ হয়েছে');
    }
    if ($a === 'settings') {
        foreach (['phone', 'whatsapp', 'delivery_dhaka', 'delivery_outside', 'facebook', 'address', 'notice'] as $k) set_setting($k, trim(mb_substr((string)($_POST[$k] ?? ''), 0, 300)));
        $np = (string)($_POST['new_pass'] ?? '');
        if ($np !== '') {
            if (mb_strlen($np) < 6) go('t=settings', 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে');
            set_setting('admin_pass', password_hash($np, PASSWORD_DEFAULT));
        }
        go('t=settings', 'সেটিংস সেভ হয়েছে');
    }
}

function bn2en(string $s): string { return strtr($s, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']); }

/** returns '' (no file), false (bad file) or the saved relative path */
function save_upload(string $field) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) return false;
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return false;
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'p-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $src = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if ($src) {
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $o = (int)(@exif_read_data($f['tmp_name'])['Orientation'] ?? 1);
                if ($o === 3) $src = imagerotate($src, 180, 0); elseif ($o === 6) $src = imagerotate($src, -90, 0); elseif ($o === 8) $src = imagerotate($src, 90, 0);
            }
            $w = imagesx($src); $hh = imagesy($src); $max = 1000;
            $k = min(1, $max / max($w, $hh)); $nw = (int)round($w * $k); $nh = (int)round($hh * $k);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $hh);
            if (imagewebp($dst, "$dir/$name.webp", 84)) return "uploads/$name.webp";
        }
    }
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
    return move_uploaded_file($f['tmp_name'], "$dir/$name.$ext") ? "uploads/$name.$ext" : false;
}

$tab = $_GET['t'] ?? 'orders';
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$newCount = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
$defaultPass = password_verify('christmas2026', setting('admin_pass'));
?><!doctype html>
<html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Admin | Christmas Gallery</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--red:#b3121f;--red2:#7d0a12;--green:#14713f;--gold:#f6c33b;--bg:#faf5f2;--line:#efe0e0;--muted:#776}
*{box-sizing:border-box}body{margin:0;font-family:"Hind Siliguri",sans-serif;background:var(--bg);color:#2a1a1a;font-size:16px}
a{color:inherit}
header{background:linear-gradient(90deg,var(--red2),var(--red));color:#fff;position:sticky;top:0;z-index:5}
.top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;max-width:1150px;margin:auto}
.top b{font-size:19px}.top a{color:#fff;text-decoration:none;font-weight:600;font-size:15px;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:99px}
nav{display:flex;gap:6px;padding:0 16px 10px;max-width:1150px;margin:auto;overflow-x:auto}
nav a{color:#fff;text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:600;white-space:nowrap;background:rgba(255,255,255,.1)}
nav a.on{background:#fff;color:var(--red)}
.pill{display:inline-grid;place-items:center;min-width:22px;height:22px;border-radius:99px;background:var(--gold);color:#5a0a10;font-size:13px;margin-left:4px;padding:0 6px}
main{max-width:1150px;margin:auto;padding:18px 16px 60px}
.flash{background:#e8f6ee;color:var(--green);font-weight:600;padding:10px 14px;border-radius:12px;margin-bottom:14px}
.warn{background:#fff4d6;color:#7a5200;font-weight:600;padding:10px 14px;border-radius:12px;margin-bottom:14px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.stat{background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px}.stat small{color:var(--muted)}.stat b{display:block;font-size:24px}
.filters{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.filters a{text-decoration:none;padding:6px 12px;border-radius:99px;background:#fff;border:1px solid var(--line);font-weight:600;font-size:14px}.filters a.on{background:var(--red);color:#fff;border-color:var(--red)}
.ord{background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px;margin-bottom:12px}
.ord.new{border:2px solid var(--gold);background:#fffdf3}
.oh{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
.oh b{font-size:18px}.oh small{color:var(--muted)}
.og{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:10px}
.og ul{margin:0;padding-left:18px}.og p{margin:2px 0}
.tot{font-weight:700;color:var(--red);font-size:17px}
.acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center}
.btn{display:inline-flex;align-items:center;gap:6px;border:0;cursor:pointer;font:inherit;font-weight:700;font-size:15px;padding:8px 14px;border-radius:10px;text-decoration:none;background:#eee;color:#333}
.b-red{background:var(--red);color:#fff}.b-green{background:#25d366;color:#fff}.b-gold{background:var(--gold);color:#4a0a0e}.b-ghost{background:#fff;border:1px solid var(--line)}
select,input,textarea{font:inherit;font-size:16px;padding:9px 11px;border:2px solid #ecd6d6;border-radius:10px;background:#fff;width:100%}
select{width:auto}
.st{font-size:13px;font-weight:700;padding:3px 10px;border-radius:99px;background:#eee}
.st-new{background:var(--gold);color:#5a0a10}.st-confirmed{background:#dbeafe;color:#1d4ed8}.st-shipped{background:#ede9fe;color:#6d28d9}.st-delivered{background:#dcfce7;color:#15803d}.st-cancelled{background:#fee2e2;color:#b91c1c}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden;border:1px solid var(--line)}
td,th{padding:10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
th{background:#fff4f4;font-size:14px}
td img{width:56px;height:56px;object-fit:cover;border-radius:10px;background:#f6eaea}
td input.pr{width:110px}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:16px}
.fg{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fg .full{grid-column:1/-1}
label{font-weight:600;font-size:15px;display:block;margin-bottom:4px}
.chk{display:flex;gap:18px;flex-wrap:wrap}.chk label{display:flex;gap:6px;align-items:center;font-weight:600}.chk input{width:auto}
.hint{color:var(--muted);font-size:14px;margin-top:3px}
.empty{text-align:center;color:var(--muted);padding:40px;background:#fff;border-radius:16px;border:1px solid var(--line)}
@media(max-width:760px){.stats{grid-template-columns:1fr 1fr}.og,.fg{grid-template-columns:1fr}
 table.resp thead{display:none}table.resp tr{display:grid;grid-template-columns:60px 1fr auto;gap:6px 10px;padding:10px;border-bottom:1px solid var(--line);align-items:center}table.resp td{border:0;padding:0}table.resp td:first-child{grid-row:span 3}table.resp td:nth-child(2),table.resp td:nth-child(4){grid-column:2/4}table.resp td:nth-child(3){grid-column:2;grid-row:3}table.resp td:nth-child(5){grid-column:3;grid-row:3}table.resp td:nth-child(4){grid-row:2}
 .top b{font-size:16px}.top b span{display:none}.top a{padding:6px 10px;font-size:14px;white-space:nowrap}}
</style></head><body>
<header>
 <div class="top"><b>🎄 Christmas Gallery<span> Admin</span></b><span style="display:flex;gap:6px"><a href="../" target="_blank">সাইট দেখুন</a><a href="?logout=1">লগআউট</a></span></div>
 <nav><a href="?t=orders" class="<?= $tab === 'orders' ? 'on' : '' ?>">অর্ডার<?php if ($newCount): ?><span class="pill"><?= bn_num($newCount) ?></span><?php endif; ?></a><a href="?t=products" class="<?= $tab === 'products' ? 'on' : '' ?>">প্রোডাক্ট ও দাম</a><a href="?t=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">সেটিংস</a></nav>
</header>
<main>
<?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
<?php if ($defaultPass): ?><div class="warn">⚠️ এখনো শুরুর পাসওয়ার্ড চালু আছে। <a href="?t=settings">সেটিংস</a> থেকে নতুন পাসওয়ার্ড দিন।</div><?php endif; ?>

<?php if ($tab === 'orders'):
    $today = date('Y-m-d');
    $st = db()->prepare("SELECT COUNT(*) FROM orders WHERE substr(created_at,1,10)=?"); $st->execute([$today]); $todayN = (int)$st->fetchColumn();
    $all = (int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $sales = (int)db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
    $f = $_GET['f'] ?? '';
    if (isset($STATUS[$f])) { $q = db()->prepare('SELECT * FROM orders WHERE status=? ORDER BY id DESC LIMIT 300'); $q->execute([$f]); }
    else { $f = ''; $q = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 300'); }
    $orders = $q->fetchAll();
?>
 <div class="stats">
  <div class="stat"><small>নতুন অর্ডার</small><b><?= bn_num($newCount) ?></b></div>
  <div class="stat"><small>আজকের অর্ডার</small><b><?= bn_num($todayN) ?></b></div>
  <div class="stat"><small>মোট অর্ডার</small><b><?= bn_num($all) ?></b></div>
  <div class="stat"><small>ডেলিভারি হওয়া বিক্রি</small><b>৳ <?= bn_num($sales) ?></b></div>
 </div>
 <div class="filters"><a href="?t=orders" class="<?= $f === '' ? 'on' : '' ?>">সব</a><?php foreach ($STATUS as $k => $v): ?><a href="?t=orders&f=<?= $k ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach; ?></div>
 <?php if (!$orders): ?><div class="empty">এখনো কোনো অর্ডার নেই 🎄</div><?php endif; ?>
 <?php foreach ($orders as $o): $items = json_decode($o['items'], true) ?: []; $ph = $o['phone'];
   $wa = 'https://wa.me/88' . $ph . '?text=' . rawurlencode("Assalamu alaikum {$o['name']}, Christmas Gallery theke bolchi. Apnar order {$o['code']} (total {$o['total']} tk) confirm korte chai."); ?>
 <div class="ord <?= $o['status'] === 'new' ? 'new' : '' ?>">
  <div class="oh"><div><b><?= h($o['code']) ?></b> <span class="st st-<?= h($o['status']) ?>"><?= h($STATUS[$o['status']] ?? $o['status']) ?></span></div><small><?= h($o['created_at']) ?></small></div>
  <div class="og">
   <div><p><b><?= h($o['name']) ?></b></p><p>📞 <a href="tel:<?= h($ph) ?>"><?= h($ph) ?></a></p><p>📍 <?= nl2br(h($o['address'])) ?></p><p><small><?= $o['area'] === 'outside' ? 'ঢাকার বাইরে' : 'ঢাকার ভিতরে' ?></small></p><?php if ($o['note'] !== ''): ?><p>📝 <?= h($o['note']) ?></p><?php endif; ?></div>
   <div><ul><?php foreach ($items as $it): ?><li><?= h($it['name']) ?><?= $it['size'] ? ' (' . h($it['size']) . ')' : '' ?> × <?= (int)$it['qty'] ?> = ৳<?= (int)$it['line'] ?></li><?php endforeach; ?></ul>
    <p>ডেলিভারি: ৳<?= (int)$o['delivery'] ?></p><p class="tot">মোট: ৳<?= (int)$o['total'] ?></p></div>
  </div>
  <div class="acts">
   <a class="btn b-red" href="tel:<?= h($ph) ?>">কল</a><a class="btn b-green" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
   <form method="post" style="display:flex;gap:6px"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="status"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
    <select name="status"><?php foreach ($STATUS as $k => $v): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select><button class="btn b-gold">সেভ</button></form>
   <form method="post" onsubmit="return confirm('এই অর্ডারটা মুছে ফেলবেন?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="del_order"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn b-ghost">মুছুন</button></form>
  </div>
 </div>
 <?php endforeach; ?>

<?php elseif ($tab === 'products'):
    $edit = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
    if ($edit !== null):
        $p = ['id' => 0, 'type' => $_GET['type'] ?? 'item', 'name' => '', 'descr' => '', 'price' => 0, 'old_price' => 0, 'badge' => '', 'size' => '', 'stock' => 1, 'active' => 1, 'sort' => 0, 'img' => ''];
        if ($edit) { $q = db()->prepare('SELECT * FROM products WHERE id=?'); $q->execute([$edit]); $p = $q->fetch() ?: $p; }
?>
 <div class="card">
  <h2 style="margin-top:0"><?= $p['id'] ? 'প্রোডাক্ট এডিট' : 'নতুন প্রোডাক্ট' ?></h2>
  <form method="post" enctype="multipart/form-data" class="fg">
   <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="save_product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
   <div class="full" style="display:flex;gap:14px;align-items:center"><?php if ($p['img']): ?><img src="../<?= h($p['img']) ?>" style="width:110px;height:110px;object-fit:cover;border-radius:14px;background:#f6eaea" alt=""><?php endif; ?>
    <div style="flex:1"><label>ছবি <?= $p['img'] ? '(বদলাতে চাইলে নতুন ছবি দিন)' : '' ?></label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><div class="hint">মোবাইলে তোলা ছবি দিলেই হবে, সাইজ নিজে থেকে ছোট হয়ে যাবে।</div></div></div>
   <div class="full"><label>প্রোডাক্টের নাম *</label><input name="name" required value="<?= h($p['name']) ?>"></div>
   <div class="full"><label>বিবরণ</label><textarea name="descr" rows="3"><?= h($p['descr']) ?></textarea></div>
   <div><label>দাম (টাকা)</label><input name="price" inputmode="numeric" value="<?= (int)$p['price'] ?>"><div class="hint">০ দিলে সাইটে দেখাবে “দাম জানতে মেসেজ করুন”</div></div>
   <div><label>আগের দাম (কাটা দাম, ঐচ্ছিক)</label><input name="old_price" inputmode="numeric" value="<?= (int)$p['old_price'] ?>"></div>
   <div><label>ধরন</label><select name="type" style="width:100%"><option value="item" <?= $p['type'] === 'item' ? 'selected' : '' ?>>সাজানোর আইটেম</option><option value="tree" <?= $p['type'] === 'tree' ? 'selected' : '' ?>>Christmas Tree</option></select></div>
   <div><label>সাইজ (ট্রির জন্য, যেমন: ৪ ফুট)</label><input name="size" value="<?= h($p['size']) ?>"></div>
   <div><label>ব্যাজ (যেমন: নতুন, Hot, 20% ছাড়)</label><input name="badge" value="<?= h($p['badge']) ?>"></div>
   <div><label>সিরিয়াল (ছোট নম্বর আগে দেখাবে)</label><input name="sort" inputmode="numeric" value="<?= (int)$p['sort'] ?>"></div>
   <div class="full chk"><label><input type="checkbox" name="stock" value="1" <?= $p['stock'] ? 'checked' : '' ?>> স্টকে আছে</label><label><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>> সাইটে দেখাবে</label></div>
   <div class="full" style="display:flex;gap:8px"><button class="btn b-red">সেভ করুন</button><a class="btn b-ghost" href="?t=products">ফিরে যান</a></div>
  </form>
  <?php if ($p['id']): ?><form method="post" onsubmit="return confirm('প্রোডাক্টটা একদম মুছে ফেলবেন?')" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="del_product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn b-ghost" style="color:#b91c1c">এই প্রোডাক্ট মুছুন</button></form><?php endif; ?>
 </div>
<?php else: $list = products(false); ?>
 <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px"><a class="btn b-red" href="?t=products&edit=0">+ নতুন আইটেম</a><a class="btn b-green" href="?t=products&edit=0&type=tree">+ নতুন Christmas Tree</a></div>
 <form method="post">
 <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="quick_price">
 <table class="resp"><thead><tr><th>ছবি</th><th>নাম</th><th>দাম (৳)</th><th>অবস্থা</th><th></th></tr></thead><tbody>
 <?php foreach ($list as $p): ?>
  <tr><td><img src="../<?= h($p['img']) ?>" alt=""></td><td><b><?= h($p['name']) ?></b><br><small><?= $p['type'] === 'tree' ? '🎄 Tree' : 'আইটেম' ?><?= $p['size'] ? ' · ' . h($p['size']) : '' ?></small></td>
  <td><input class="pr" name="price[<?= (int)$p['id'] ?>]" inputmode="numeric" value="<?= (int)$p['price'] ?>"></td>
  <td><?= $p['active'] ? '' : '<span class="st">লুকানো</span> ' ?><?= $p['stock'] ? '<span class="st st-delivered">স্টকে আছে</span>' : '<span class="st st-cancelled">স্টক শেষ</span>' ?></td>
  <td><a class="btn b-ghost" href="?t=products&edit=<?= (int)$p['id'] ?>">এডিট</a></td></tr>
 <?php endforeach; ?>
 </tbody></table>
 <p><button class="btn b-gold">সব দাম একসাথে সেভ করুন</button> <span class="hint">দাম ০ রাখলে সাইটে “দাম জানতে মেসেজ করুন” দেখাবে আর কার্ট বাটন আসবে না।</span></p>
 </form>
<?php endif; ?>

<?php else: ?>
 <div class="card">
  <h2 style="margin-top:0">সেটিংস</h2>
  <form method="post" class="fg">
   <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="settings">
   <div><label>ফোন নম্বর (সাইটে দেখাবে)</label><input name="phone" value="<?= h(setting('phone')) ?>"></div>
   <div><label>WhatsApp নম্বর (88 দিয়ে শুরু)</label><input name="whatsapp" value="<?= h(setting('whatsapp')) ?>"></div>
   <div><label>ঢাকার ভিতরে ডেলিভারি চার্জ (৳)</label><input name="delivery_dhaka" inputmode="numeric" value="<?= h(setting('delivery_dhaka')) ?>"></div>
   <div><label>ঢাকার বাইরে ডেলিভারি চার্জ (৳)</label><input name="delivery_outside" inputmode="numeric" value="<?= h(setting('delivery_outside')) ?>"></div>
   <div class="full"><label>উপরের হলুদ লাইনের লেখা</label><input name="notice" value="<?= h(setting('notice')) ?>"></div>
   <div class="full"><label>Facebook পেজের লিংক (ঐচ্ছিক)</label><input name="facebook" value="<?= h(setting('facebook')) ?>" placeholder="https://facebook.com/..."></div>
   <div class="full"><label>দোকানের ঠিকানা (ঐচ্ছিক)</label><input name="address" value="<?= h(setting('address')) ?>"></div>
   <div class="full"><label>নতুন Admin পাসওয়ার্ড (বদলাতে না চাইলে খালি রাখুন)</label><input name="new_pass" type="password" autocomplete="new-password"></div>
   <div class="full"><button class="btn b-red">সেভ করুন</button></div>
  </form>
 </div>
 <p class="hint">Version <?= CG_VERSION ?></p>
<?php endif; ?>
</main></body></html>
