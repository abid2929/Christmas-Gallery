<?php
// Christmas Gallery - database (SQLite, created automatically on first visit)
declare(strict_types=1);

const CG_VERSION = '1.1.0';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/shop.sqlite';
    $new = !file_exists($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    if ($new) cg_install($pdo);
    return $pdo;
}

function cg_install(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS products(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL DEFAULT 'item',      -- item | tree
        name TEXT NOT NULL,
        descr TEXT NOT NULL DEFAULT '',
        price INTEGER NOT NULL DEFAULT 0,       -- 0 = দাম জানতে মেসেজ
        old_price INTEGER NOT NULL DEFAULT 0,
        img TEXT NOT NULL DEFAULT '',
        badge TEXT NOT NULL DEFAULT '',
        size TEXT NOT NULL DEFAULT '',
        img_cls TEXT NOT NULL DEFAULT '',
        stock INTEGER NOT NULL DEFAULT 1,       -- 1 = স্টকে আছে, 0 = স্টক শেষ
        active INTEGER NOT NULL DEFAULT 1,
        sort INTEGER NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL,
        name TEXT NOT NULL, phone TEXT NOT NULL, address TEXT NOT NULL,
        area TEXT NOT NULL, note TEXT NOT NULL DEFAULT '',
        items TEXT NOT NULL, subtotal INTEGER NOT NULL, delivery INTEGER NOT NULL, total INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'new', seen INTEGER NOT NULL DEFAULT 0,
        ip TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT NOT NULL)");

    $seed = [
        ['item','Christmas Ball Tube Box','লম্বা টিউব বক্সে নীল-সিলভার রঙের Christmas ball সেট। ট্রি সাজানোর জন্য দারুণ।','img/p1.webp','Best seller','','contain'],
        ['item','Pink & Silver Ball Set','গোলাপি আর সিলভার রঙের ডিজাইন করা Christmas ball, সুন্দর গিফট বক্সে।','img/p2.webp','নতুন','',''],
        ['item','Golden Ball Gift Box','ষড়ভুজ গিফট বক্সে গোল্ডেন আর লাল Christmas ball, রিবন সহ।','img/p3.webp','Gift','',''],
        ['item','Light-up Tree Top Star','ট্রির মাথায় লাগানোর গোল্ডেন স্টার, ভেতরে আলো জ্বলে।','img/p4.webp','Light','',''],
        ['item','Dancing Santa Claus Doll','গান বাজিয়ে নাচে এমন Santa doll। কয়েকটা ডিজাইন আছে (গিটার, ব্যাগ ইত্যাদি)।','img/p5.webp','Hot','',''],
        ['item','Santa Hat','নরম লাল কাপড় আর সাদা ফার দেওয়া Santa টুপি। বড় ছোট সবার জন্য।','img/p6.webp','Party','',''],
        ['item','Star Fairy Lights (LED)','তারার আকারের ছোট ছোট LED লাইট। ট্রি, জানালা বা দেয়ালে লাগালে ঝলমল করে।','img/n-lights.webp','Light','',''],
        ['item','Green Christmas Wreath','পাইন পাতা, পাইন কোন আর গোল্ডেন ডেকোরেশন দেওয়া সবুজ রিদ। দরজায় ঝোলানোর জন্য।','img/n-wreath.webp','Door','',''],
        ['item','Golden Glitter Wreath','গোল্ডেন-সিলভার গ্লিটার রিদ, ঘরের দেয়াল বা দরজা সাজানোর জন্য।','img/n-goldwreath.webp','Premium','',''],
        ['item','Christmas Stocking','লাল ভেলভেট আর সাদা ফারের Christmas মোজা। গিফট রাখার জন্য ঝুলিয়ে রাখুন।','img/n-stocking.webp','Gift','',''],
        ['item','Glitter Christmas Ball','গ্লিটার দেওয়া রঙিন বড় Christmas ball, আলোতে চকচক করে।','img/n-glitterball.webp','নতুন','',''],
        ['item','Red & Gold Ball Set','লাল আর গোল্ডেন রঙের Christmas ball সেট, ট্রিতে দারুণ মানায়।','img/n-redgold.webp','','',''],
        ['item','Snowman Showpiece','সাদা ছোট Snowman শোপিস, লাল মাফলার আর কালো টুপি।','img/n-snowman.webp','Cute','',''],
        ['item','Christmas Gift Box Set','রঙিন র‍্যাপিং করা গিফট বক্স সেট। ট্রির নিচে সাজাতে বা গিফট দিতে।','img/n-giftset.webp','Gift','',''],
        ['item','Black & Red Gift Box','কালো বক্সে লাল রিবন, প্রিমিয়াম গিফট প্যাকিং।','img/n-giftbox.webp','','',''],
        ['item','Christmas Candle','সাদা পিলার মোমবাতি, Christmas এর সন্ধ্যায় ঘরে উষ্ণ আলো।','img/n-candles.webp','','',''],
        ['tree','Table Top Christmas Tree','টেবিল, শেলফ বা অফিস ডেস্কের জন্য ছোট ট্রি।','img/t1.webp','','ছোট','tree-s'],
        ['tree','Medium Christmas Tree','ড্রইং রুমের কোণার জন্য মাঝারি সাইজের ট্রি।','img/t1.webp','','মাঝারি','tree-m'],
        ['tree','Big Christmas Tree','বড় ঘর, অফিস বা দোকানের জন্য বড় ফ্লোর ট্রি।','img/t1.webp','','বড়','tree-l'],
        ['tree','Snow White Christmas Tree','সাদা তুষারের মতো দেখতে Christmas tree, সাথে বল আর লাইট দিয়ে সাজানো।','img/t-snow.webp','Snow','তুষার',''],
    ];
    $st = $pdo->prepare('INSERT INTO products(type,name,descr,img,badge,size,img_cls,sort) VALUES(?,?,?,?,?,?,?,?)');
    foreach ($seed as $i => $r) $st->execute([...$r, $i + 1]);

    $defaults = [
        'phone' => '01779-443155',
        'whatsapp' => '8801779443155',
        'delivery_dhaka' => '70',
        'delivery_outside' => '130',
        'facebook' => '',
        'address' => '',
        'notice' => 'All Christmas Decorate Items Here',
        'admin_pass' => password_hash('christmas2026', PASSWORD_DEFAULT),
    ];
    $st = $pdo->prepare('INSERT OR IGNORE INTO settings(k,v) VALUES(?,?)');
    foreach ($defaults as $k => $v) $st->execute([$k, $v]);
}

function setting(string $k, string $def = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT k,v FROM settings') as $r) $cache[$r['k']] = $r['v'];
    }
    return $cache[$k] ?? $def;
}

function set_setting(string $k, string $v): void {
    db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, $v]);
}

function products(bool $onlyActive = true): array {
    $sql = 'SELECT * FROM products' . ($onlyActive ? ' WHERE active=1' : '') . ' ORDER BY type, sort, id';
    return db()->query($sql)->fetchAll();
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function bn_num($n): string { return strtr((string)$n, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']); }
