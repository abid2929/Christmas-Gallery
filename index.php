<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/store.php';
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function bn_num($n): string { return strtr((string)$n, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']); }
function img_url(string $p): string { if ($p === '' || preg_match('~^(https?:|data:)~', $p)) return $p; return 'img/' . preg_replace('~^img/~', '', $p); }
function thumb_path(string $p): string { $u = img_url($p); if (preg_match('~^img/(.+)\.webp$~', $u, $m) && is_file(__DIR__ . '/img/' . $m[1] . '-s.webp')) return 'img/' . $m[1] . '-s.webp'; return $u; }
function csv_list($s): array { return array_values(array_filter(array_map('trim', explode(',', (string)$s)), 'strlen')); }
$site = pv_read('site'); $st = $site['store'] ?? []; $cms = $site['cms'] ?? []; $dl = $site['delivery'] ?? []; $pm = $site['payment'] ?? []; $seo = $site['seo'] ?? [];
$store = $st['name'] ?? 'Christmas Gallery';
$phone = $st['phone'] ?? '01779-443155';
$wa = preg_replace('/\D/', '', (string)($st['whatsapp'] ?? '')) ?: '88' . preg_replace('/\D/', '', $phone);
$tel = '+88' . preg_replace('/^\+?88/', '', preg_replace('/\D/', '', $phone));
$fb = $st['facebook'] ?? ''; $addr = $st['address'] ?? ''; $email = $st['email'] ?? '';
$TX = [ // admin-editable texts (Bangla) with defaults
    'notice' => ($cms['ticker'][0] ?? '') ?: 'All Christmas Decorate Items Here', 'store_tagline' => $st['tagline'] ?? '', 'delivery_time' => $dl['note'] ?? '',
    'cms_footer' => $cms['about'] ?? '',
];
foreach (['products_title', 'products_sub', 'trees_title', 'trees_sub', 'count_title', 'count_text', 'contact_title', 'contact_text', 'hero_caption'] as $k) $TX["cms_$k"] = $cms[$k] ?? '';
$S = fn(string $k, string $d = '') => (string)($TX[$k] ?? $d) !== '' ? (string)($TX[$k] ?? $d) : $d;
$on = fn(string $k) => ($cms[$k] ?? 1) ? true : false;
$dd = (int)($dl['inside'] ?? 70); $do = (int)($dl['outside'] ?? 130); $free = (int)($dl['free_over'] ?? 0);
$pixel = preg_replace('/\D/', '', (string)($site['pixel']['id'] ?? ''));

$cats = []; $treeCat = [];
foreach (pv_read('categories') as $c) if (!empty($c['on'])) { $cats[] = ['id' => (string)$c['k'], 'name' => $c['name'], 'img' => $c['img'] ?? '', 'order' => (int)($c['order'] ?? 99)]; if ($c['k'] === 'tree' || preg_match('/tree/i', $c['name'])) $treeCat[$c['k']] = 1; }
usort($cats, fn($a, $b) => $a['order'] <=> $b['order']);
$catOn = array_flip(array_column($cats, 'id'));
$revAll = array_values(array_filter(pv_read('reviews'), fn($r) => !empty($r['on'])));
$rv = []; foreach ($revAll as $r) $rv[mb_strtolower(trim((string)($r['pet'] ?? '')))][] = ['n' => $r['name'], 'r' => max(1, min(5, (int)($r['stars'] ?? 5))), 'b' => $r['text'], 'd' => ''];
$list = [];
foreach (pv_read('products') as $k => $items) {
    if (!isset($catOn[$k]) || !is_array($items)) continue;
    foreach ($items as $j => $x) {
        if (!empty($x['off'])) continue;
        $img = img_url((string)(($x['i'] ?? '') ?: ($x['r'] ?? '') ?: ($x['g'][0]['s'] ?? '')));
        $gal = []; foreach ((array)($x['g'] ?? []) as $g) if (!empty($g['s'])) $gal[] = ['s' => img_url((string)$g['s']), 'c' => (string)($g['c'] ?? '')];
        $qty = (isset($x['st']) && $x['st'] !== '' && $x['st'] !== null) ? (int)$x['st'] : null;
        $r = $rv[mb_strtolower(trim((string)$x['n']))] ?? [];
        $list[] = [
            'id' => (string)($x['id'] ?? $k . $j), 'type' => isset($treeCat[$k]) ? 'tree' : 'item', 'name' => (string)$x['n'],
            'desc' => (string)($x['sd'] ?? ''), 'desc_en' => (string)($x['sd_en'] ?? ''), 'details' => (string)($x['d'] ?? ''),
            'img' => $img, 'th' => thumb_path($img), 'gal' => array_column($gal, 's'), 'galc' => $gal,
            'price' => (int)($x['p'] ?? 0), 'old' => (int)($x['o'] ?? 0), 'badge' => '', 'size' => (string)($x['t'] ?? ''), 'cls' => '',
            'stock' => ($qty === null || $qty > 0) ? 1 : 0, 'qty' => $qty, 'cat' => (string)$k,
            'ft' => (int)!empty($x['ft']), 'fl' => (int)!empty($x['fs']), 'nw' => (int)!empty($x['nw']), 'bs' => (int)!empty($x['bs']), 'sold' => 0,
            'sizes' => csv_list($x['sz'] ?? ''), 'colors' => csv_list($x['cl'] ?? ''),
            'rating' => $r ? round(array_sum(array_column($r, 'r')) / count($r), 1) : 0, 'rc' => count($r), 'rv' => array_slice($r, 0, 20),
        ];
    }
}
$dealId = (string)($cms['deal']['id'] ?? ''); foreach ($list as &$p) if ($p['id'] === $dealId) $p['fl'] = 1; unset($p);
$banners = [];
foreach (pv_read('banners') as $b) if (!empty($b['on']) && !empty($b['img'])) $banners[] = [
    'img' => (string)$b['img'], 'title' => (string)(($b['title'] ?? '') ?: $store), 'title_en' => (string)($b['title_en'] ?? ''),
    'tag' => (string)(($b['tag'] ?? '') ?: 'Christmas Collection'), 'tag_en' => (string)($b['tag_en'] ?? ''),
    'subtitle' => (string)(($b['sub'] ?? '') ?: $S('cms_hero_caption')), 'subtitle_en' => (string)($b['sub_en'] ?? ''),
    'btn_text' => (string)(($b['btn'] ?? '') ?: 'Shop now'), 'btn_en' => (string)(($b['btn_en'] ?? '') ?: 'Shop now'), 'btn_link' => (string)(($b['link'] ?? '') ?: '#products'),
];
$heroMain = !$banners;
$allRev = []; foreach (array_slice($revAll, 0, 9) as $r) $allRev[] = ['n' => $r['name'], 'r' => max(1, min(5, (int)($r['stars'] ?? 5))), 'b' => $r['text'], 'p' => (string)($r['pet'] ?? ''), 'd' => ''];

$bk = trim((string)($pm['bkash'] ?? '')); $ng = trim((string)($pm['nagad'] ?? ''));
$pay = ['cod' => !empty($pm['cod']), 'bkash' => $bk !== '', 'nagad' => $ng !== '', 'bkash_no' => $bk, 'nagad_no' => $ng, 'note' => (string)($pm['note'] ?? '')];
if (!$pay['cod'] && !$pay['bkash'] && !$pay['nagad']) $pay['cod'] = true;
$json = json_encode([
    'items' => array_values(array_filter($list, fn($p) => $p['type'] === 'item')),
    'trees' => array_values(array_filter($list, fn($p) => $p['type'] === 'tree')),
    'cats' => array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name']], $cats),
    'wa' => $wa, 'tel' => $tel, 'dd' => $dd, 'do' => $do, 'free' => $free, 'pay' => $pay, 'pixel' => $pixel !== '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

$EN = [
    'notice' => 'All Christmas Decorate Items Here', 'store_tagline' => 'Every Christmas item in one place',
    'cms_products_title' => 'Our Christmas items', 'cms_products_sub' => 'Tap a photo to see it bigger. Add to cart and order, or order on WhatsApp.',
    'cms_trees_title' => 'Christmas Trees', 'cms_trees_sub' => 'Pick the size that fits your room',
    'cms_count_title' => 'Time left until Christmas', 'cms_count_text' => 'Order your decorations before the stock runs out.',
    'cms_contact_title' => 'For any question or order', 'cms_contact_text' => 'Message or call us to know price, stock or delivery.',
    'cms_footer' => 'Christmas balls, tree star, Santa doll, lights, wreath and Christmas trees, all in one place.',
    'cms_hero_caption' => 'Every Christmas decoration in one place', 'delivery_time' => 'Dhaka 1-2 days, outside Dhaka 2-4 days',
];
function bi(string $bn, string $en, string $tag = 'span', string $attr = ''): string { return "<$tag$attr data-en=\"" . h($en) . '">' . h($bn) . "</$tag>"; }
$T = function (string $k, string $tag = 'span', string $attr = '') use ($S, $EN) { $bn = $S($k); if ($bn === '') return ''; return bi($bn, $EN[$k] ?? $bn, $tag, $attr); };
$seoTitle = ($seo['title'] ?? '') ?: $store; $seoDesc = (string)($seo['desc'] ?? '');
$base = defined('CG_BASE') ? CG_BASE : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https://' : 'http://') . preg_replace('/[^a-z0-9.\-:]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
?><!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($seoTitle) ?></title>
<meta name="description" content="<?= h($seoDesc) ?>">
<?php if (!empty($seo['keys'])): ?><meta name="keywords" content="<?= h($seo['keys']) ?>"><?php endif; ?><?php if (!empty($seo['gsv'])): ?><meta name="google-site-verification" content="<?= h(preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$seo['gsv'])) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($seoTitle) ?>">
<meta property="og:description" content="<?= h($seoDesc) ?>">
<meta property="og:image" content="<?= h($base . 'img/banner.webp') ?>">
<meta name="theme-color" content="#0f3d2e">
<?php if (!defined('CG_DEMO')): ?><link rel="canonical" href="<?= h($base) ?>"><?php endif; ?>
<?php if (!$heroMain && $banners): $hi = $banners[0]['img']; ?><link rel="preload" as="image" href="<?= h(img_url(thumb_path($hi))) ?>" imagesrcset="<?= h(img_url(thumb_path($hi))) ?> 480w, <?= h(img_url($hi)) ?> 1000w" imagesizes="(max-width:760px) 250px, 400px" fetchpriority="high"><?php endif; ?>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E🎄%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@500;600&family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Poppins:wght@500;600;700&display=swap" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@500;600&family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Poppins:wght@500;600;700&display=swap"></noscript>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Store', 'name' => $store, 'telephone' => $tel, 'description' => $seoDesc, 'image' => 'img/banner.webp'] + ($addr ? ['address' => $addr] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php if ($pixel && !defined('CG_DEMO')): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?= $pixel ?>');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=<?= $pixel ?>&ev=PageView&noscript=1"></noscript>
<?php endif; ?>
<style>
:root{--em:#0f3d2e;--em2:#0a2c21;--em3:#155a43;--bur:#8c1a2b;--bur2:#6b1220;--gold:#c9a24b;--gold2:#e7cf8f;--ivory:#fbf7f0;--sand:#f3ece0;--ink:#1f1b17;--muted:#6f675d;--line:#e8dfd0;--card:#fff;--r:16px;--sh:0 1px 2px rgba(31,27,23,.05),0 10px 30px rgba(31,27,23,.07)}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:"Hind Siliguri","Poppins",system-ui,sans-serif;color:var(--ink);background:var(--ivory);line-height:1.6;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
.wrap{width:min(1200px,100% - 40px);margin-inline:auto}
.xmas{font-family:"Playfair Display",Georgia,serif;font-style:italic;font-weight:500;letter-spacing:.2px}
.ic{width:20px;height:20px;flex:0 0 auto}
:focus-visible{outline:2px solid var(--gold);outline-offset:2px}

/* top bar */
.strip{background:var(--em2);color:var(--gold2);font-size:13.5px;letter-spacing:.2px}
.strip .wrap{display:flex;justify-content:space-between;gap:12px;padding:8px 0;flex-wrap:wrap}
.strip a{display:inline-flex;align-items:center;gap:6px;color:#fff}
.strip .ic{width:16px;height:16px}
.strip .dt{opacity:.85}
@media (max-width:640px){.strip .dt{display:none}.strip{font-size:12.5px}}

/* header */
header{position:sticky;top:0;z-index:50;background:rgba(251,247,240,.94);backdrop-filter:saturate(1.4) blur(10px);-webkit-backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid var(--line)}
.nav{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0}
.brand{display:flex;align-items:center;gap:11px}
.brand .tree-ic{width:44px;height:44px;border-radius:50%;background:var(--em);display:grid;place-items:center;box-shadow:inset 0 0 0 2px var(--gold)}
.brand .tree-ic svg path{fill:#e7cf8f}.brand .tree-ic svg rect{fill:#c9a24b}.brand .tree-ic svg circle{fill:#fff}
.brand b{font-family:"Playfair Display",Georgia,serif;font-size:23px;font-weight:700;line-height:1;color:var(--em);letter-spacing:.2px;display:block}
.brand small{display:block;font-size:12px;color:var(--muted);margin-top:3px;letter-spacing:.3px}
.menu{display:flex;gap:2px}
.menu a{padding:8px 14px;border-radius:999px;font-weight:600;font-size:15px;color:var(--ink);position:relative}
.menu a:hover{color:var(--bur)}
.hbtn{display:inline-flex;align-items:center;gap:7px;background:var(--em);color:#fff;padding:10px 16px;border-radius:999px;font-weight:600;font-size:14.5px;white-space:nowrap}
.hbtn:hover{background:var(--em3)}
.lang{display:inline-flex;align-items:center;gap:5px;height:42px;padding:0 12px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--em);font-weight:700;font-size:13.5px}
.lang svg{width:16px;height:16px}
.cartbtn{position:relative;width:42px;height:42px;border-radius:50%;border:1px solid var(--line);background:#fff;color:var(--em);display:grid;place-items:center}
.cartbtn .ic{width:21px;height:21px}
.cart-n{position:absolute;top:-5px;right:-5px;min-width:20px;height:20px;padding:0 5px;border-radius:99px;background:var(--bur);color:#fff;font-size:12px;font-weight:700;display:none;place-items:center;line-height:1}
.burger{display:none;width:42px;height:42px;border-radius:50%;border:1px solid var(--line);background:#fff;color:var(--em);place-items:center}
@media (max-width:960px){
 .menu{position:absolute;left:16px;right:16px;top:calc(100% + 6px);flex-direction:column;background:#fff;border-radius:16px;padding:8px;box-shadow:0 18px 40px rgba(0,0,0,.15);display:none;border:1px solid var(--line)}
 .menu.open{display:flex}.menu a{border-radius:10px;padding:11px 14px}
 .burger{display:grid}.hbtn span{display:none}.hbtn{padding:0;width:42px;height:42px;justify-content:center}
}
@media (max-width:520px){.nav{gap:8px;padding:10px 0}.brand{gap:8px}.brand .tree-ic{width:38px;height:38px}.brand b{font-size:17px;white-space:nowrap}.brand small{display:none}
 .lang,.cartbtn,.burger,.hbtn{height:38px}.cartbtn,.burger,.hbtn{width:38px}.lang{padding:0 9px;font-size:12.5px}.lang svg{display:none}.nav>div{gap:6px!important}}

/* hero */
.hero{position:relative;background:radial-gradient(900px 500px at 80% 30%,rgba(201,162,75,.22),transparent 60%),radial-gradient(700px 500px at 10% 90%,rgba(140,26,43,.35),transparent 60%),linear-gradient(135deg,var(--em2),var(--em) 55%,#123f30);color:#fff;overflow:hidden}
.hero:after{content:"";position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M30 0v60M0 30h60' stroke='%23ffffff' stroke-opacity='.025'/%3E%3C/svg%3E");pointer-events:none}
.slides{position:relative;z-index:1;display:flex;flex-direction:column;justify-content:center}
.slide{display:none;animation:fade .8s ease}
.slide.on{display:block}
@keyframes fade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.slide-banner{padding:26px 0 54px}
.slide-banner img{width:100%;height:auto;border-radius:18px;box-shadow:0 24px 50px rgba(0,0,0,.35);border:1px solid rgba(231,207,143,.5)}
.bcap{display:none}
.slide-grid{display:grid;grid-template-columns:1.05fr .95fr;align-items:center;gap:40px;padding:56px 0 72px;min-height:470px}
.slide-grid h1,.slide-grid h2{font-family:"Hind Siliguri","Playfair Display",serif;font-weight:700;font-size:clamp(32px,4.6vw,56px);line-height:1.12;letter-spacing:-.3px}
.slide-grid p{font-size:17.5px;margin:16px 0 26px;color:rgba(255,255,255,.85);max-width:520px}
.slide-grid .pic{position:relative;display:grid;place-items:center}
.slide-grid .pic img{position:relative;width:min(400px,100%);height:auto;aspect-ratio:4/5;object-fit:cover;border-radius:200px 200px 22px 22px;box-shadow:0 30px 60px rgba(0,0,0,.4);border:6px solid rgba(255,255,255,.08);outline:1px solid rgba(231,207,143,.6);outline-offset:8px}
.slide-grid .pic .ring{position:absolute;width:min(460px,110%);aspect-ratio:1;border-radius:50%;background:radial-gradient(circle,rgba(231,207,143,.28),transparent 62%)}
.slide-grid .pic .note{position:absolute;left:4%;bottom:12%;background:#fff;color:var(--ink);border-radius:14px;padding:10px 14px;box-shadow:0 14px 30px rgba(0,0,0,.25);font-size:13.5px;font-weight:600;display:flex;gap:8px;align-items:center}
.slide-grid .pic .note b{color:var(--em);font-family:"Playfair Display",serif;font-size:16px;display:block;line-height:1.1}
.tag{display:inline-flex;align-items:center;gap:8px;color:var(--gold2);font-family:"Playfair Display",serif;font-style:italic;font-size:17px;margin-bottom:12px}
.tag:before{content:"";width:34px;height:1px;background:var(--gold)}
.btns{display:flex;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 26px;border-radius:999px;font-weight:600;font-size:15.5px;transition:transform .15s,box-shadow .2s,background .2s;line-height:1.2}
.btn:hover{transform:translateY(-1px)}
.btn-gold{background:linear-gradient(135deg,#e7cf8f,#c9a24b);color:#2b210c;box-shadow:0 8px 20px rgba(201,162,75,.35)}
.btn-wa{background:#13843f;color:#fff}
.btn-ghost{color:#fff;border:1px solid rgba(255,255,255,.45)}
.btn-cart{background:var(--em);color:#fff}.btn-cart:hover{background:var(--em3)}
.btn-sq{flex:0 0 auto!important;width:44px;padding:0!important}
.btn-off{background:#efeae2;color:#9a9186;cursor:default}
.btn-call{background:var(--bur);color:#fff;flex:0 0 auto!important;width:46px;padding:0!important}
.dots{position:absolute;left:0;right:0;bottom:22px;display:flex;justify-content:center;gap:8px;z-index:3}
.dots{gap:2px}.dots button{width:24px;height:24px;display:grid;place-items:center}
.dots button:before{content:"";width:8px;height:8px;border-radius:99px;background:rgba(255,255,255,.45);transition:width .3s}
.dots button.on{width:34px}.dots button.on:before{width:26px;background:var(--gold)}
.arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:46px;height:46px;border-radius:50%;border:1px solid rgba(255,255,255,.35);color:#fff;display:grid;place-items:center;font-size:22px;background:rgba(0,0,0,.12)}
.arrow:hover{background:rgba(255,255,255,.12)}
.arrow.l{left:16px}.arrow.r{right:16px}
.hero-wave{display:none}
#snow{position:absolute;inset:0;pointer-events:none;z-index:2}
@media (max-width:1240px){.arrow{display:none}}
@media (max-width:760px){
 .slide-grid{grid-template-columns:1fr;text-align:center;padding:30px 0 58px;gap:24px;min-height:0}
 .slide-grid p{margin:12px auto 20px;font-size:15.5px}
 .slide-grid .btns,.tag{justify-content:center}
 .slide-grid .pic{order:-1}
 .slide-grid .pic img{width:min(250px,70%);border-radius:140px 140px 18px 18px}
 .slide-grid .pic .note{left:2%;bottom:6%;padding:8px 10px;font-size:12px}
 .slide-banner{padding:16px 0 48px}.slide-banner img{border-radius:12px}
 .bcap{display:block;text-align:center;margin-top:18px}.bcap h2{font-size:26px;line-height:1.25;font-weight:700}.bcap .btns{justify-content:center;margin-top:14px}
}

/* trust strip */
.trust{background:#fff;border-bottom:1px solid var(--line)}
.trust .wrap{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:20px 0}
.trust div{display:flex;gap:12px;align-items:center}
.trust .ti{width:44px;height:44px;border-radius:50%;background:var(--sand);color:var(--em);display:grid;place-items:center;flex:0 0 auto}
.trust b{display:block;font-size:15px;line-height:1.25}
.trust small{color:var(--muted);font-size:13px}
@media (max-width:900px){.trust .wrap{grid-template-columns:repeat(2,1fr);gap:16px}}
@media (max-width:420px){.trust .ti{width:38px;height:38px}.trust b{font-size:13.5px}.trust small{font-size:12px}.trust div{gap:8px}}

/* sections */
section{padding:76px 0}
.head{text-align:center;margin-bottom:38px}
.head .xmas{color:#82621a;font-size:20px;display:inline-flex;align-items:center;gap:12px}
.head .xmas:before,.head .xmas:after{content:"";width:28px;height:1px;background:var(--gold)}
.head h2{font-family:"Hind Siliguri","Playfair Display",serif;font-weight:700;font-size:clamp(26px,3.4vw,38px);line-height:1.25;margin-top:4px;color:var(--em2)}
.head p{color:var(--muted);margin:8px auto 0;max-width:620px}
@media (max-width:640px){section{padding:54px 0}.head{margin-bottom:28px}}

/* categories */
.cats{display:grid;grid-template-columns:repeat(var(--n,8),1fr);gap:20px}
.cat{text-align:center;font-weight:600;font-size:15px;line-height:1.3}
.cat .c{aspect-ratio:1;border-radius:50%;overflow:hidden;background:#fff;padding:5px;box-shadow:0 0 0 1px var(--gold),var(--sh);margin:0 auto 12px;transition:transform .3s;max-width:170px}
.cat .c img{width:100%;height:100%;object-fit:cover;border-radius:50%;transition:transform .5s}
.cat:hover .c img{transform:scale(1.07)}
.cat small{display:block;color:var(--muted);font-weight:500;font-size:13px;margin-top:2px}
@media (max-width:760px){.cats{grid-template-columns:repeat(var(--n,8),minmax(96px,1fr));overflow-x:auto;padding:4px 4px 10px;scroll-snap-type:x mandatory;gap:14px}.cat{scroll-snap-align:start;font-size:13.5px}}

/* filter bar */
.fbar{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:26px}
.fbar>*{min-width:0;max-width:100%}
.chips{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none;min-width:0;flex:1 1 auto}
.chips::-webkit-scrollbar{display:none}
.chip{white-space:nowrap;border:1px solid var(--line);background:#fff;color:var(--ink);border-radius:999px;padding:8px 16px;font-weight:600;font-size:14px}
.chip:hover{border-color:var(--gold)}
.chip.on{background:var(--em);border-color:var(--em);color:#fff}
.ftools{display:flex;gap:8px;flex:0 0 auto}
.ftools input,.ftools select{font:inherit;font-size:14.5px;border:1px solid var(--line);border-radius:999px;padding:9px 16px;background:#fff;min-width:0;color:var(--ink)}
.ftools input{width:200px}
@media (max-width:760px){.fbar{display:grid;grid-template-columns:minmax(0,1fr)}.ftools input{flex:1;width:auto}}
.noitems{grid-column:1/-1;text-align:center;color:var(--muted);padding:30px 0;font-weight:600}

/* product cards */
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
@media (max-width:1100px){.grid{grid-template-columns:repeat(3,1fr)}}
.card{background:var(--card);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh);display:flex;flex-direction:column;border:1px solid #efe7da;transition:transform .25s,box-shadow .25s}
.card:hover{transform:translateY(-4px);box-shadow:0 2px 4px rgba(31,27,23,.05),0 20px 40px rgba(31,27,23,.12)}
.card .img{position:relative;aspect-ratio:1/1;background:var(--sand);overflow:hidden;cursor:pointer}
.card .img img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
.card:hover .img img{transform:scale(1.05)}
.card .img.contain img{object-fit:contain;padding:14px}
.badges{position:absolute;top:12px;left:12px;display:flex;flex-direction:column;gap:5px;align-items:flex-start;z-index:1}
.badge{background:var(--bur);color:#fff;font-size:12px;font-weight:600;padding:4px 11px;border-radius:999px;letter-spacing:.2px}
.badge.off{background:var(--gold2);color:#3a2a07}.badge.nw{background:var(--em)}.badge.bs{background:#1f1b17}.badge.g{background:var(--em)}
.size{position:absolute;right:12px;top:12px;background:rgba(255,255,255,.92);color:var(--em);font-weight:700;font-size:12.5px;border-radius:999px;padding:4px 11px}
.soldout{position:absolute;inset:auto 0 0 0;background:rgba(31,27,23,.78);color:#fff;text-align:center;font-weight:600;padding:7px;font-size:14px}
.card .body{padding:16px 16px 18px;display:flex;flex-direction:column;gap:7px;flex:1}
.card h3{font-family:"Poppins","Hind Siliguri",sans-serif;font-size:16.5px;font-weight:600;line-height:1.35;color:var(--ink)}
.card .desc{color:var(--muted);font-size:14.5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card .body>.order{margin-top:auto;padding-top:6px}
.price{font-family:"Poppins",sans-serif;font-weight:700;color:var(--bur);font-size:19px}
.price s{color:#a99f92;font-size:.72em;font-weight:500;margin-left:6px}
.price.ask{font-family:"Hind Siliguri";font-size:15.5px;color:var(--em3);font-weight:600}
.row{display:flex;align-items:center;gap:10px;justify-content:space-between}
.qty{display:flex;align-items:center;border:1px solid var(--line);border-radius:999px;overflow:hidden;background:#fff}
.qty button{width:34px;height:34px;font-size:19px;font-weight:600;color:var(--em)}
.qty span{min-width:24px;text-align:center;font-weight:700}
.order{display:flex;gap:8px}
.order .btn{flex:1;padding:11px 12px;font-size:14.5px}
.order .btn-wa.btn-sq{background:#fff;color:#1fae54;border:1px solid #cfe9d8}
.stars{color:#d9a21b;letter-spacing:1px;font-size:14px}.stars i{font-style:normal;color:#e6ddcf}
.rc{font-size:13px;color:var(--muted);display:flex;gap:5px;align-items:center}
.opts{font-size:12.5px;color:var(--muted);font-weight:600}
.lowq{font-size:12.5px;color:#b45309;font-weight:600}
@media (max-width:900px){.grid{grid-template-columns:repeat(2,1fr);gap:14px}}
@media (max-width:520px){
 .wrap{width:min(1200px,100% - 28px)}
 .grid{gap:10px}
 .card{border-radius:14px}
 .card .body{padding:11px 10px 12px;gap:5px}
 .card h3{font-size:14px}
 .card .desc,.opts{display:none}
 .price{font-size:15.5px}.price.ask{font-size:13px}.price s{display:block;margin:0;font-size:.75em}
 .order{gap:6px}.order .btn{font-size:13px;padding:10px 6px}
 .btn-sq{width:38px}.order .btn .ic{width:17px;height:17px}
 .badges{top:8px;left:8px}.badge{font-size:10.5px;padding:3px 8px}.size{top:8px;right:8px;font-size:11px;padding:3px 8px}
}

/* flash */
.flash{background:linear-gradient(180deg,var(--sand),var(--ivory));padding:56px 0}
.flash .fh{display:flex;align-items:end;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.flash .fh h2{font-family:"Hind Siliguri","Playfair Display",serif;font-size:clamp(24px,3vw,34px);display:flex;align-items:center;gap:12px;color:var(--em2);flex-wrap:wrap}
.flash .fh h2 em{font-style:normal;background:var(--bur);color:#fff;font-size:13px;padding:4px 12px;border-radius:999px;font-weight:600}
.flash .fh .btn{background:none;color:var(--em);border:1px solid var(--em);padding:10px 20px}
.hscroll{display:grid;grid-auto-flow:column;grid-auto-columns:calc((100% - 66px)/4);gap:22px;overflow-x:auto;padding:4px 2px 14px;scroll-snap-type:x mandatory}
.hscroll .card{scroll-snap-align:start}
@media (max-width:900px){.hscroll{grid-auto-columns:42%;gap:14px}}
@media (max-width:520px){.hscroll{grid-auto-columns:60%;gap:10px}}

/* trees */
.trees{background:radial-gradient(800px 400px at 50% 0,rgba(201,162,75,.18),transparent 70%),linear-gradient(180deg,var(--em),var(--em2));color:#fff}
.trees .head h2{color:#fff}.trees .head p{color:rgba(255,255,255,.75)}
.trees .card{border:0}

/* countdown */
.count{background:radial-gradient(600px 300px at 50% 0,rgba(231,207,143,.2),transparent 70%),linear-gradient(135deg,var(--bur2),var(--bur));color:#fff;text-align:center}
.count h2{font-family:"Hind Siliguri",serif;font-size:clamp(24px,3.2vw,36px);font-weight:700}
.timer{display:flex;justify-content:center;gap:14px;margin:26px 0 22px}
.timer div{border:1px solid rgba(231,207,143,.5);border-radius:16px;min-width:96px;padding:14px 8px;background:rgba(255,255,255,.06)}
.timer b{display:block;font-family:"Playfair Display",serif;font-size:clamp(30px,4vw,46px);line-height:1;color:var(--gold2)}
.timer small{font-size:13.5px;opacity:.85;letter-spacing:.3px}
@media (max-width:520px){.timer{gap:8px}.timer div{min-width:0;flex:1;padding:12px 4px}}

/* reviews */
.rgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.rcard{background:#fff;border-radius:var(--r);padding:22px;border:1px solid #efe7da;box-shadow:var(--sh);position:relative}
.rcard:before{content:"“";position:absolute;right:18px;top:4px;font-family:"Playfair Display",serif;font-size:64px;color:var(--gold2);line-height:1}
.rcard p{margin:10px 0;color:#4a433b}
.rcard small{color:var(--muted)}
@media (max-width:900px){.rgrid{grid-template-columns:1fr}}

/* steps */
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;counter-reset:s}
.step{background:#fff;border-radius:var(--r);padding:26px 20px;text-align:center;border:1px solid #efe7da;box-shadow:var(--sh)}
.step .n{width:58px;height:58px;border-radius:50%;margin:0 auto 14px;display:grid;place-items:center;background:var(--sand);color:var(--em);box-shadow:inset 0 0 0 1px var(--gold)}
.step h3{font-size:17px;margin-bottom:4px}
.step p{color:var(--muted);font-size:14.5px}
@media (max-width:900px){.steps{grid-template-columns:repeat(2,1fr)}}
@media (max-width:420px){.steps{gap:10px}.step{padding:18px 10px}.step h3{font-size:15px}.step p{font-size:13.5px}}

/* contact */
.cta{background:linear-gradient(135deg,var(--em2),var(--em));color:#fff;border-radius:24px;padding:44px;display:grid;grid-template-columns:1.2fr .8fr;gap:30px;align-items:center;overflow:hidden;position:relative}
.cta:after{content:"";position:absolute;right:-80px;top:-80px;width:280px;height:280px;border-radius:50%;border:1px solid rgba(231,207,143,.3)}
.cta .xmas{color:var(--gold2)!important}
.cta h2{font-family:"Hind Siliguri",serif;font-size:clamp(24px,3vw,34px);line-height:1.25}
.cta p{color:rgba(255,255,255,.8);margin:8px 0 18px}
.cta .ph{font-family:"Playfair Display",serif;font-size:clamp(28px,4vw,42px);font-weight:700;color:var(--gold2);display:block;margin-bottom:18px}
.cta img{border-radius:18px;width:100%;max-height:320px;object-fit:cover;position:relative;z-index:1}
@media (max-width:760px){.cta{grid-template-columns:1fr;padding:28px 22px;text-align:center}.cta .btns{justify-content:center}}

footer{background:var(--em2);color:rgba(255,255,255,.78);padding:56px 0 96px;margin-top:30px}
.fgrid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:30px}
footer h3{color:#fff;font-size:17px;margin-bottom:12px;font-family:"Playfair Display","Hind Siliguri",serif}
footer a{display:block;padding:3px 0}
footer a:hover{color:var(--gold2)}
.copy{border-top:1px solid rgba(255,255,255,.12);margin-top:30px;padding-top:18px;font-size:13.5px;opacity:.75;text-align:center}
@media (max-width:760px){.fgrid{grid-template-columns:1fr}}

.fab{position:fixed;right:16px;bottom:18px;z-index:60;width:56px;height:56px;border-radius:50%;background:#1fae54;color:#fff;display:grid;place-items:center;box-shadow:0 10px 24px rgba(0,0,0,.25)}

/* modal */
.modal{position:fixed;inset:0;z-index:80;background:rgba(15,20,17,.7);display:none;align-items:center;justify-content:center;padding:16px}
.modal.open{display:flex}
.mbox{background:#fff;border-radius:22px;max-width:900px;width:100%;display:grid;grid-template-columns:1fr 1fr;overflow:hidden;max-height:92vh;position:relative}
.mbox .mi{background:var(--sand);display:flex;flex-direction:column;justify-content:center;position:relative}
.mbox .mi>img{width:100%;flex:1;min-height:0;height:100%;object-fit:cover}
.ptag{align-self:flex-start;background:#e9f2ec;color:var(--em);font-weight:700;font-size:13px;border-radius:999px;padding:4px 12px}
.mbox .mt{padding:28px;display:flex;flex-direction:column;gap:10px;overflow:auto}
.mbox .mt>*{flex-shrink:0}
.mbox h3{font-family:"Poppins","Hind Siliguri",sans-serif;font-size:23px;line-height:1.3;font-weight:600}
.mbox .price{font-size:25px}.mbox .price.ask{font-size:17px}
.mbox .x{position:absolute;right:12px;top:12px;width:40px;height:40px;border-radius:50%;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.18);font-size:22px;z-index:2}
.thumbs{display:flex;gap:6px;padding:8px;overflow-x:auto;background:#fff}
.thumbs:empty{display:none}
.thumbs img{width:58px;height:58px;object-fit:cover;border-radius:10px;border:2px solid transparent;cursor:pointer;flex:0 0 auto}
.thumbs img.on{border-color:var(--gold)}
.pick{display:flex;flex-wrap:wrap;gap:7px}
.pick button{border:1px solid var(--line);border-radius:10px;padding:7px 14px;font-weight:600;font-size:14px;background:#fff}
.pick button.on{border-color:var(--em);background:var(--em);color:#fff}
.ml{font-weight:700;font-size:14px;margin-top:4px;color:var(--em2)}
.details{white-space:pre-line;font-size:14.5px;color:#4a433b;background:var(--ivory);border-radius:12px;padding:12px;border:1px solid var(--line)}
.mrev{border-top:1px solid var(--line);padding-top:10px}
.rv{padding:8px 0;border-bottom:1px dashed var(--line);font-size:14.5px}
.linkbtn{color:var(--bur);font-weight:700;text-decoration:underline;align-self:flex-start;margin-top:6px}
@media (max-width:700px){.mbox{grid-template-columns:1fr;overflow:auto}.mbox .mi>img{max-height:320px;aspect-ratio:1}.mbox .mt{padding:18px;overflow:visible}}
.mform{max-width:480px;grid-template-columns:1fr!important}
.mform .mt{padding:24px}
.rate{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px}
.rate{position:relative}.rate input{position:absolute;opacity:0;width:1px!important;height:1px;padding:0!important;border:0!important;pointer-events:none;left:0;top:0}
.rate label{font-size:32px;color:#e6ddcf;cursor:pointer;margin:0!important;line-height:1}
.rate input:checked~label,.rate label:hover,.rate label:hover~label{color:#d9a21b}

/* cart drawer */
.drawer{position:fixed;inset:0;z-index:90;background:rgba(15,20,17,.55);display:flex;justify-content:flex-end;opacity:0;pointer-events:none;transition:opacity .25s}
.drawer.open{opacity:1;pointer-events:auto}
.dpanel{width:min(440px,100%);height:100%;background:#fff;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .3s}
.drawer.open .dpanel{transform:none}
.dhead{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:var(--em);color:#fff}
.dhead b{font-size:19px;font-family:"Hind Siliguri",serif}
.dhead button{color:#fff;font-size:28px;width:40px;height:40px;line-height:1}
.dbody{flex:1;overflow:auto;padding:16px 20px}
.dstep[hidden]{display:none}
.ci{display:grid;grid-template-columns:72px 1fr 30px;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid var(--line)}
.ci img{width:72px;height:72px;object-fit:cover;border-radius:12px;background:var(--sand)}
.ci b{display:block;font-size:15px;line-height:1.3}
.ci .var{font-size:13px;color:var(--muted)}
.ci .cp{font-size:14px;color:var(--muted)}
.ci strong{color:var(--bur)}
.ci .rm{font-size:24px;color:#a99f92;width:30px;height:30px}
.qty.sm{margin-top:6px;display:inline-flex}.qty.sm button{width:28px;height:28px;font-size:17px}
.empty{text-align:center;padding:50px 0;font-size:46px}.empty p{font-size:18px;margin:8px 0 18px;color:var(--muted)}
.dfoot{border-top:1px solid var(--line);padding:14px 20px 18px;background:var(--ivory)}
.sumrow{display:flex;justify-content:space-between;padding:3px 0;font-size:15px}
.sumrow.t{font-size:19px;font-weight:700;color:var(--bur);border-top:1px dashed var(--line);margin-top:6px;padding-top:8px}
.dfoot .btn{width:100%;margin-top:12px}
.freebar{font-size:13.5px;color:var(--em3);font-weight:700;margin-bottom:6px}
.f label{display:block;font-weight:600;margin:12px 0 5px;font-size:14.5px}
.f input,.f textarea,.f select{width:100%;font:inherit;font-size:16px;padding:11px 13px;border:1px solid #ddd2c1;border-radius:12px;background:#fff;color:var(--ink)}
.f input:focus,.f textarea:focus,.f select:focus{outline:none;border-color:var(--em);box-shadow:0 0 0 3px rgba(15,61,46,.1)}
.areas{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.areas label,.pays label{margin:0;border:1px solid #ddd2c1;border-radius:12px;padding:10px;cursor:pointer;font-weight:600;font-size:14px;display:flex;gap:8px;align-items:center}
.areas label:has(input:checked),.pays label:has(input:checked){border-color:var(--em);background:#f1f7f3}
.areas input,.pays input{width:auto;accent-color:var(--em)}
.pays{display:grid;gap:8px}
.paybox{background:#fbf3df;border:1px solid #ecd9a6;border-radius:12px;padding:10px 12px;font-size:14.5px;margin-top:8px}
.paybox b{font-family:Poppins;font-size:17px}
.err{color:#b4232f;font-weight:600;min-height:20px;margin-top:8px;font-size:14.5px}
.done{text-align:center;padding:30px 4px}
.done .ok{width:76px;height:76px;border-radius:50%;background:#e6f3ec;color:var(--em);display:grid;place-items:center;margin:0 auto 14px;font-size:40px}
.done h3{font-size:22px}.done p{color:var(--muted);margin:8px 0}
.done .code{font-family:Poppins;font-weight:700;font-size:26px;color:var(--bur)}
.done .btn{width:100%;margin-top:10px}
.toast{position:fixed;left:50%;top:84px;transform:translate(-50%,-20px);background:var(--em2);color:#fff;padding:10px 20px;border-radius:999px;font-weight:600;opacity:0;transition:.25s;z-index:95;pointer-events:none;max-width:calc(100% - 32px);text-align:center}
.toast.on{opacity:1;transform:translate(-50%,0)}
.hp{position:absolute;left:-9999px}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important;scroll-behavior:auto!important}}
@media (max-width:420px){header .hbtn{display:none}}

/* ============ LUXE layer: editorial boutique look ============ */
:root{--serif:"Playfair Display","Noto Serif Bengali",Georgia,serif;--r:14px}
body{font-weight:400;letter-spacing:.1px}
.xmas{font-family:"Playfair Display",Georgia,serif}

/* header */
.strip{background:#06201a;font-size:13px}
.strip .wrap{padding:7px 0}
header{background:rgba(251,247,240,.96)}
.nav{padding:14px 0}
.brand .tree-ic{width:42px;height:42px;background:radial-gradient(circle at 35% 30%,#1a5a44,var(--em2));box-shadow:inset 0 0 0 1px rgba(231,207,143,.9),0 0 0 3px var(--ivory),0 0 0 4px rgba(201,162,75,.5)}
.brand b{font-size:24px;font-weight:600;color:var(--em2);letter-spacing:.3px}
.brand small{font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:#8a7a5c}
.menu a{font-weight:500;font-size:15px;border-radius:0;padding:8px 14px}
.menu a:after{content:"";position:absolute;left:14px;right:14px;bottom:2px;height:1px;background:var(--gold);transform:scaleX(0);transition:transform .25s}
.menu a:hover{color:var(--em)}.menu a:hover:after{transform:scaleX(1)}
.hbtn{background:transparent;color:var(--em2);border:1px solid var(--em2);padding:9px 16px;font-weight:500}
.hbtn:hover{background:var(--em2);color:#fff}
.lang,.cartbtn{background:transparent;border-color:#dccfb8}
.lang{font-weight:600}
.cart-n{background:var(--bur)!important}

/* hero: full-bleed photo, editorial type */
.hero{background:linear-gradient(90deg,#06201a 0%,#0a2c21 45%,#0f3d2e 100%)}
.hero:after{display:none}
#snow{opacity:.3}
.slide{position:relative}
.slide-grid{position:static;display:block;min-height:600px;padding:96px 0 110px}
.slide-grid>div:first-child{position:relative;z-index:2;max-width:600px}
.slide-grid .pic{position:absolute;inset:0 0 0 38%;display:block;z-index:1}
.slide-grid .pic .ring{display:none}
.slide-grid .pic img{width:100%;height:100%;max-width:none;aspect-ratio:auto;object-fit:cover;border:0;outline:0;border-radius:0;box-shadow:none;-webkit-mask-image:linear-gradient(90deg,transparent 0,#000 38%);mask-image:linear-gradient(90deg,transparent 0,#000 38%)}
.slide-grid .pic:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(6,32,26,.55),transparent 40%);pointer-events:none}
.slide-grid .pic .note{left:auto;right:48px;bottom:56px;z-index:2;background:rgba(6,32,26,.55);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);color:#fff;border:1px solid rgba(231,207,143,.45);border-radius:999px;padding:10px 18px;box-shadow:none;font-weight:400}
.slide-grid .pic .note b{color:var(--gold2);font-size:15px}
.tag{font-size:19px;color:var(--gold2);margin-bottom:18px}
.slide-grid h1,.slide-grid h2{font-family:var(--serif);font-weight:600;font-size:clamp(36px,4.8vw,62px);line-height:1.18;letter-spacing:0;text-shadow:0 2px 30px rgba(0,0,0,.25)}
.slide-grid p{font-size:17px;color:rgba(255,255,255,.78);margin:20px 0 32px;font-weight:300;max-width:470px}
.btn{border-radius:8px;font-weight:600;letter-spacing:.2px}
.btn-gold{background:linear-gradient(135deg,#f0dca2,#c9a24b 55%,#b48a33);color:#1f1608;box-shadow:0 10px 24px rgba(201,162,75,.28)}
.hero .btn-wa{background:transparent;border:1px solid rgba(255,255,255,.55);color:#fff}
.hero .btn-wa:hover{background:rgba(255,255,255,.1)}
.arrow{border-color:rgba(231,207,143,.45);background:rgba(6,32,26,.35);backdrop-filter:blur(6px)}
.dots{bottom:30px}
@media (max-width:760px){
 .slide-grid{min-height:0;padding:0 0 64px;display:flex;flex-direction:column}
 .slide-grid .pic{position:relative;inset:auto;order:-1;margin:0 -20px 22px;height:300px}
 .slide-grid .pic img{-webkit-mask-image:linear-gradient(0deg,transparent 0,#000 40%);mask-image:linear-gradient(0deg,transparent 0,#000 40%);width:100%;border-radius:0}
 .slide-grid .pic:after{display:none}
 .slide-grid .pic .note{right:14px;bottom:auto;top:14px;left:auto;padding:7px 12px;font-size:12px}
 .slide-grid .pic .note b{font-size:13px}
 .slide-grid h1,.slide-grid h2{font-size:clamp(30px,8.4vw,40px)}
 .slide-grid p{font-size:15.5px;margin:12px 0 22px}
 .tag{font-size:16px;margin-bottom:8px}
 .dots{bottom:20px}
}

/* trust */
.trust{background:var(--ivory);border-bottom:1px solid var(--line)}
.trust .wrap{padding:22px 0}
.trust .wrap>div+div{border-left:1px solid var(--line);padding-left:18px}
.trust .ti{background:transparent;color:#9a7a2e;box-shadow:inset 0 0 0 1px rgba(201,162,75,.6)}
.trust b{font-family:"Playfair Display",serif;font-weight:600;font-size:15.5px;color:var(--em2)}
@media (max-width:900px){.trust .wrap>div+div{border-left:0;padding-left:0}}

/* section heads */
section{padding:88px 0}
.head{margin-bottom:44px}
.head .xmas{font-size:18px;letter-spacing:.3px}
.head h2,.flash .fh h2,.count h2,.cta h2{font-family:var(--serif);font-weight:600;letter-spacing:0}
.head h2{font-size:clamp(28px,3.4vw,42px);color:var(--em2);margin-top:8px}
.head p{font-size:15.5px;font-weight:300;color:#7a7064}
@media (max-width:640px){section{padding:60px 0}}

/* categories: tall editorial tiles */
.cats{gap:16px}
.cat{position:relative;text-align:left;font-family:var(--serif);font-weight:600;font-size:16px;color:#fff;border-radius:var(--r);overflow:hidden;display:block}
.cat .c{aspect-ratio:3/4;max-width:none;border-radius:var(--r);padding:0;box-shadow:none;margin:0;background:var(--em2)}
.cat .c img{border-radius:0}
.cat .c:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(6,32,26,.85),rgba(6,32,26,.15) 45%,transparent 65%)}
.cat{line-height:1.25}
.cat{display:grid}
.cat>.c{grid-area:1/1}
.cat>small{grid-area:1/1;align-self:end;margin:0 0 14px 16px;position:relative;z-index:1;color:var(--gold2);font-family:"Hind Siliguri",sans-serif;font-size:12.5px;font-weight:500}
.cat .cn{grid-area:1/1;align-self:end;margin:0 14px 36px 16px;position:relative;z-index:1}
@media (max-width:760px){.cats{grid-template-columns:repeat(var(--n,8),minmax(130px,1fr));gap:12px}.cat{font-size:15px}}

/* product cards: borderless boutique */
.grid{gap:34px 24px}
.card{background:transparent;border:0;box-shadow:none;border-radius:0;overflow:visible}
.card:hover{transform:none;box-shadow:none}
.card .img{aspect-ratio:4/5;border-radius:var(--r);background:#efe7d9}
.card .img:after{content:"";position:absolute;inset:0;border-radius:inherit;box-shadow:inset 0 0 0 1px rgba(31,27,23,.06);pointer-events:none}
.card:hover .img img{transform:scale(1.06)}
.card .body{padding:16px 2px 0;gap:6px}
.card h3{font-family:var(--serif);font-weight:600;font-size:17px;line-height:1.3;color:var(--em2)}
.card .desc{display:none}
.price{font-family:"Poppins",sans-serif;font-weight:600;color:var(--ink);font-size:17px;letter-spacing:.2px}
.price s{color:#a89c8b}
.card .price{color:var(--bur)}
.price.ask{font-family:"Hind Siliguri",sans-serif;font-size:14.5px;color:#8a6a1f;font-weight:500}
.badges{top:14px;left:14px}
.badge{background:rgba(251,247,240,.94);color:var(--em2);font-size:11.5px;font-weight:600;padding:4px 11px;border-radius:4px;letter-spacing:.6px;text-transform:uppercase;backdrop-filter:blur(4px)}
.badge.off{background:var(--bur);color:#fff}
.badge.nw,.badge.bs,.badge.g{background:rgba(251,247,240,.94);color:var(--em2)}
.size{border-radius:4px;font-weight:600;font-size:11.5px;letter-spacing:.4px}
.order{margin-top:8px}
.order .btn{padding:11px 12px;font-size:14px;border-radius:8px}
.card .btn-cart{background:transparent;color:var(--em2);border:1px solid rgba(15,61,46,.55)}
.card .btn-cart:hover{background:var(--em2);color:#fff;border-color:var(--em2)}
.card .order .btn-wa:not(.btn-sq){background:transparent;color:#15803d;border:1px solid #9fd3b2}
.card .order .btn-wa:not(.btn-sq):hover{background:#15803d;color:#fff}
.order .btn-wa.btn-sq{background:transparent;border:1px solid #cfe2d6;border-radius:8px}
.rc{color:#8a7a5c}
.opts{font-weight:500;color:#8a7a5c;letter-spacing:.2px}
@media (max-width:520px){.grid{gap:26px 12px}.card h3{font-size:15px}.card .price{font-size:15.5px}.order .btn{padding:10px 6px;font-size:13px}.card .body{padding-top:12px}}

/* flash */
.flash{background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.flash .fh h2 em{border-radius:4px;font-size:11.5px;letter-spacing:.6px;text-transform:uppercase;font-family:"Hind Siliguri",sans-serif}
.flash .fh .btn{border-radius:8px}

/* filters */
.chip{border-radius:6px;font-weight:500;background:transparent;border-color:#dccfb8}
.chip.on{background:var(--em2);border-color:var(--em2)}
.ftools input,.ftools select{border-radius:8px;background:#fff;border-color:#dccfb8}

/* trees: deep emerald, light text on transparent cards */
.trees{background:radial-gradient(900px 420px at 50% 0,rgba(201,162,75,.16),transparent 70%),linear-gradient(180deg,#0f3d2e,#06201a)}
.trees .head h2{color:#fff}
.trees .card h3{color:#fff}
.trees .card .price{color:var(--gold2)}
.trees .card .price.ask{color:var(--gold2)}
.trees .card .opts,.trees .card .lowq{color:rgba(231,207,143,.85)}
.trees .card .btn-cart{color:var(--gold2);border-color:rgba(231,207,143,.6)}
.trees .card .btn-cart:hover{background:var(--gold2);color:#1f1608}
.trees .card .order .btn-wa.btn-sq{border-color:rgba(255,255,255,.25);color:#6ee7a0}
.trees .card .order .btn-wa:not(.btn-sq){color:#6ee7a0;border-color:rgba(110,231,160,.45)}
.trees .card .img{background:#0a2c21}

/* countdown */
.count{background:radial-gradient(700px 320px at 50% 0,rgba(231,207,143,.16),transparent 70%),linear-gradient(135deg,#4a0d17,#7a1626 60%,#5a1020)}
.timer div{border-radius:10px;background:rgba(0,0,0,.12)}
.timer b{font-weight:500}

/* reviews + steps */
.rcard{border-radius:var(--r);box-shadow:none}
.revband{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;border:1px solid var(--line);border-radius:var(--r);padding:26px 30px;background:#fff}
.revband h2{font-family:var(--serif);font-weight:600;font-size:clamp(20px,2.4vw,26px);color:var(--em2);line-height:1.3}
.revband p{color:var(--muted);font-weight:300}
.revband .btn{background:transparent;color:var(--em2);border:1px solid var(--em2)}
.revband .btn:hover{background:var(--em2);color:#fff}
.steps{gap:0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.step{background:transparent;border:0;box-shadow:none;border-radius:0;padding:34px 22px}
.step+.step{border-left:1px solid var(--line)}
.step .n{background:transparent;color:#9a7a2e;box-shadow:inset 0 0 0 1px rgba(201,162,75,.7)}
.step h3{font-family:var(--serif);font-weight:600;color:var(--em2)}
.step p{font-weight:300}
@media (max-width:900px){.step+.step{border-left:0}.step:nth-child(2n){border-left:1px solid var(--line)}.step:nth-child(n+3){border-top:1px solid var(--line)}}

/* contact */
.cta{border-radius:20px;background:linear-gradient(120deg,#06201a,#0f3d2e)}
.cta:after{border-color:rgba(231,207,143,.22)}
.cta .ph{font-weight:600}
.cta .btn-wa{background:transparent;border:1px solid rgba(255,255,255,.55)}
.cta img{border-radius:14px}

/* footer */
footer{background:#06201a;border-top:3px solid transparent;border-image:linear-gradient(90deg,transparent,var(--gold),transparent) 1}
footer h3{font-weight:600;letter-spacing:.3px;color:var(--gold2)}
footer .fbrand{font-family:"Playfair Display",serif;font-size:26px;color:#fff}
.paychips{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}
.paychips span{border:1px solid rgba(231,207,143,.35);color:var(--gold2);border-radius:6px;padding:4px 10px;font-size:12.5px;letter-spacing:.3px}
.fab{background:#128c4a!important}
@media (max-width:520px){.brand b{font-size:18px}.brand .tree-ic{width:36px;height:36px}.nav{padding:10px 0}}
@media (max-width:360px){.brand b{font-size:16px}.lang{padding:0 9px}}
</style>
</head>
<body>
<?php $WAIC = '<svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2m4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4a.5.5 0 0 0 0-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3"/></svg>';
$PHIC = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>'; ?>
<?php if (defined('CG_DEMO')): ?><div style="background:#111;color:#fff;font-size:14px;text-align:center;padding:8px 12px"><?= bi('🔎 এটা ডেমো ভার্সন (দামগুলো নমুনা)। অর্ডার দিয়ে দেখুন, তারপর Admin প্যানেলও দেখুন।', '🔎 This is a demo (sample prices). Try an order, then look at the Admin panel.') ?> <a href="admin/" style="color:#f6c33b;font-weight:700;text-decoration:underline">Admin →</a></div><?php endif; ?>
<div class="strip"><div class="wrap">
  <span>🎄 <?= $T('notice') ?> <span class="dt">· 🚚 <?= $T('delivery_time') ?></span></span>
  <a href="tel:<?= h($tel) ?>" data-contact><?= $PHIC ?><?= h($phone) ?></a>
</div></div>

<header>
 <div class="wrap nav">
  <a class="brand" href="#top">
   <span class="tree-ic"><svg width="22" height="22" viewBox="0 0 24 24"><path d="M12 1.5l2.2 7.1 7.3.2-5.9 4.4 2.2 7.1L12 16l-5.8 4.3 2.2-7.1-5.9-4.4 7.3-.2z" fill="#e7cf8f"/></svg></span>
   <span><b><?= h($store) ?></b><small><?= $T('store_tagline') ?></small></span>
  </a>
  <nav class="menu" id="menu">
   <?= bi('প্রোডাক্ট', 'Products', 'a', ' href="#products"') ?>
   <?= bi('Christmas Tree', 'Christmas Tree', 'a', ' href="#trees"') ?>
   <?php if ($on('cms_reviews')): ?><?= bi('রিভিউ', 'Reviews', 'a', ' href="#reviews"') ?><?php endif; ?>
   <?= bi('যোগাযোগ', 'Contact', 'a', ' href="#contact"') ?>
  </nav>
  <div style="display:flex;gap:8px;align-items:center">
   <a class="hbtn" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" data-contact><?= $WAIC ?><span>WhatsApp</span></a>
   <button class="lang" id="lang"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg><span id="langt">English</span></button>
   <button class="cartbtn" data-cart aria-label="Cart"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L22 8H6"/></svg><span class="cart-n">০</span></button>
   <button class="burger" id="burger" aria-label="Menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
  </div>
 </div>
</header>

<div class="hero" id="top">
 <canvas id="snow"></canvas>
 <div class="slides" id="slides">
  <?php if ($heroMain): ?>
  <div class="slide"><div class="wrap slide-banner">
   <h1 style="position:absolute;left:-9999px"><?= h($store) ?></h1>
   <picture><source media="(max-width:700px)" srcset="img/banner-s.webp"><img src="img/banner.webp" alt="<?= h($store) ?> banner" width="2048" height="758"></picture>
   <div class="bcap"><?= $T('cms_hero_caption', 'h2') ?><div class="btns"><a class="btn btn-gold" href="#products"><?= bi('কেনাকাটা শুরু করুন', 'Start shopping') ?></a><a class="btn btn-wa" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" data-contact>WhatsApp</a></div></div>
  </div></div>
  <?php endif; ?>
  <?php $bi = 0; foreach ($banners as $b): ?>
  <div class="slide"><div class="wrap slide-grid">
   <div>
    <?php if ($b['tag']): ?><?= bi($b['tag'], $b['tag_en'] ?: $b['tag'], 'span', ' class="tag"') ?><?php endif; ?>
    <?= bi($b['title'], $b['title_en'] ?: $b['title'], ($bi === 0 && !$heroMain) ? 'h1' : 'h2') ?>
    <?php if ($b['subtitle']): ?><?= bi($b['subtitle'], $b['subtitle_en'] ?: $b['subtitle'], 'p') ?><?php endif; ?>
    <div class="btns"><?php if ($b['btn_text']): ?><a class="btn btn-gold" href="<?= h($b['btn_link'] ?: '#products') ?>"><?= bi($b['btn_text'], $b['btn_en'] ?: $b['btn_text']) ?></a><?php endif; ?><a class="btn btn-wa" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" data-contact><?= bi('WhatsApp এ অর্ডার', 'Order on WhatsApp') ?></a></div>
   </div>
   <div class="pic"><span class="ring"></span><img src="<?= h(img_url(thumb_path($b['img']))) ?>" srcset="<?= h(img_url(thumb_path($b['img']))) ?> 480w, <?= h(img_url($b['img'])) ?> 1000w" sizes="(max-width:760px) 250px, 400px" alt="<?= h($b['title']) ?>" width="400" height="500"<?= $bi++ ? ' loading="lazy"' : ' fetchpriority="high"' ?>><span class="note">💵<span><b>Cash on Delivery</b><?= bi('সারা দেশে ডেলিভারি', 'All over Bangladesh') ?></span></span></div>
  </div></div>
  <?php endforeach; ?>
 </div>
 <button class="arrow l" data-dir="-1" aria-label="Previous">‹</button>
 <button class="arrow r" data-dir="1" aria-label="Next">›</button>
 <div class="dots" id="dots"></div>
</div>
<div class="trust"><div class="wrap">
 <div><span class="ti"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg></span><span><b>Cash on Delivery</b><small><?= bi('পণ্য হাতে পেয়ে টাকা', 'Pay when you receive') ?></small></span></div>
 <div><span class="ti"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></span><span><b>Fast Delivery</b><small><?= $T('delivery_time') ?></small></span></div>
 <div><span class="ti"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg></span><span><b>Premium Quality</b><small><?= bi('বাছাই করা সুন্দর জিনিস', 'Hand picked decorations') ?></small></span></div>
 <div><span class="ti"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v11H8l-4 4z"/></svg></span><span><b>WhatsApp Support</b><small><?= bi('যেকোনো প্রশ্নে মেসেজ করুন', 'Message us any time') ?></small></span></div>
</div></div>
<svg class="hero-wave" viewBox="0 0 1440 40" preserveAspectRatio="none" style="background:var(--red2)"><path d="M0 40V18c180 18 360 22 540 10S900 0 1080 6s270 20 360 22v12z" fill="currentColor"/></svg>

<?php if ($on('cms_cats') && $cats): ?>
<section id="cats" style="padding-top:36px">
 <div class="wrap">
  <div class="head"><span class="xmas">Shop by category</span><?= bi('কি কি পাবেন', 'What we have', 'h2') ?></div>
  <div class="cats" style="--n:<?= min(8, max(3, count($cats))) ?>">
   <?php foreach ($cats as $c): $n = count(array_filter($list, fn($p) => $p['cat'] === $c['id'])); ?>
   <a class="cat" href="#products" data-cat="<?= h($c['id']) ?>"><div class="c"><img src="<?= h(img_url(thumb_path($c['img']))) ?>" alt="" loading="lazy" width="170" height="170"></div><span class="cn"><?= h($c['name']) ?></span><small><?= bi(bn_num($n) . 'টি আইটেম', $n . ' items') ?></small></a>
   <?php endforeach; ?>
  </div>
 </div>
</section>
<?php endif; ?>

<?php if ($on('cms_flash')): ?>
<section class="flash" id="flash" hidden>
 <div class="wrap">
  <div class="fh"><div><span class="xmas" style="color:#82621a;font-size:19px">Limited Offer</span><h2><?= bi('ফ্ল্যাশ সেল', 'Flash sale') ?> <?= bi('সীমিত সময়', 'Limited time', 'em') ?></h2></div><a class="btn btn-cart" href="#products"><?= bi('সব দেখুন', 'See all') ?></a></div>
  <div class="hscroll" id="fgrid"></div>
 </div>
</section>
<?php endif; ?>

<section id="products" style="padding-top:28px">
 <div class="wrap">
  <div class="head"><span class="xmas">The Collection</span><?= $T('cms_products_title', 'h2') ?><?= $T('cms_products_sub', 'p') ?></div>
  <div class="fbar">
   <div class="chips" id="chips"></div>
   <div class="ftools"><input id="q" type="search" placeholder="খুঁজুন..." data-en-ph="Search..." aria-label="Search"><select id="sort" aria-label="Sort"><option value="" data-en="Popular">জনপ্রিয়</option><option value="new" data-en="Newest">নতুন আগে</option><option value="lo" data-en="Price: low to high">দাম: কম থেকে বেশি</option><option value="hi" data-en="Price: high to low">দাম: বেশি থেকে কম</option></select></div>
  </div>
  <div class="grid" id="pgrid"></div>
 </div>
</section>

<section class="trees" id="trees">
 <div class="wrap">
  <div class="head"><span class="xmas">Christmas trees</span><?= $T('cms_trees_title', 'h2') ?><?= $T('cms_trees_sub', 'p') ?></div>
  <div class="grid" id="tgrid"></div>
 </div>
</section>

<?php if ($on('cms_countdown')): ?>
<section class="count">
 <div class="wrap">
  <span class="xmas" style="font-size:22px;color:var(--gold2)">Merry Christmas</span>
  <?= $T('cms_count_title', 'h2') ?>
  <div class="timer" id="timer"><div><b id="cd-d">00</b><?= bi('দিন', 'Days', 'small') ?></div><div><b id="cd-h">00</b><?= bi('ঘণ্টা', 'Hours', 'small') ?></div><div><b id="cd-m">00</b><?= bi('মিনিট', 'Minutes', 'small') ?></div><div><b id="cd-s">00</b><?= bi('সেকেন্ড', 'Seconds', 'small') ?></div></div>
  <?= $T('cms_count_text', 'p', ' style="margin-bottom:20px;opacity:.95"') ?>
  <a class="btn btn-gold" href="#products"><?= bi('এখনই অর্ডার করুন', 'Order now') ?></a>
 </div>
</section>
<?php endif; ?>

<?php if ($on('cms_reviews')): ?>
<section id="reviews">
 <div class="wrap">
  <?php if (!$allRev): ?><div class="revband"><div><span class="xmas" style="color:#82621a;font-size:17px">Happy customers</span><?= bi('কিনে কেমন লাগলো? জানিয়ে দিন', 'Bought from us? Tell others', 'h2') ?><?= bi('আপনার মতামত অন্যদের বেছে নিতে সাহায্য করে', 'Your words help others choose', 'p') ?></div><button class="btn" data-review="0">★ <?= bi('রিভিউ লিখুন', 'Write a review') ?></button></div>
  <?php else: ?><div class="head"><span class="xmas">Happy customers</span><?= bi('কাস্টমারদের মতামত', 'What customers say', 'h2') ?></div><?php endif; ?>
  <?php if ($allRev): ?><div class="rgrid">
   <?php foreach ($allRev as $r): ?><div class="rcard"><span class="stars"><?= str_repeat('★', $r['r']) ?><i><?= str_repeat('★', 5 - $r['r']) ?></i></span><p><?= nl2br(h($r['b'])) ?></p><b><?= h($r['n']) ?></b><br><small><?= h($r['p']) ?></small></div><?php endforeach; ?>
  </div><?php endif; ?>
  <?php if ($allRev): ?><p style="text-align:center;margin-top:24px"><button class="btn btn-cart" data-review="0">★ <?= bi('রিভিউ লিখুন', 'Write a review') ?></button></p><?php endif; ?>
 </div>
</section>
<?php endif; ?>

<?php if ($on('cms_steps')): ?>
<section id="how" style="padding-top:<?= $on('cms_reviews') ? '10px' : '64px' ?>">
 <div class="wrap">
  <div class="head"><span class="xmas">How it works</span><?= bi('কিভাবে অর্ডার করবেন', 'How to order', 'h2') ?></div>
  <div class="steps">
   <div class="step"><div class="n"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></div><?= bi('১. পছন্দ করুন', '1. Choose', 'h3') ?><?= bi('ওয়েবসাইট থেকে আপনার পছন্দের আইটেম দেখুন', 'Browse the items you like on the website', 'p') ?></div>
   <div class="step"><div class="n"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L22 8H6"/></svg></div><?= bi('২. কার্টে নিন', '2. Add to cart', 'h3') ?><?= bi('“কার্টে নিন” চাপুন, অথবা সরাসরি WhatsApp এ অর্ডার দিন', 'Tap “Add to cart”, or order straight on WhatsApp', 'p') ?></div>
   <div class="step"><div class="n"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/><circle cx="12" cy="7" r="4"/></svg></div><?= bi('৩. ঠিকানা দিন', '3. Give your address', 'h3') ?><?= bi('নাম, মোবাইল নম্বর আর ঠিকানা লিখে অর্ডার কনফার্ম করুন', 'Write your name, mobile number and address to confirm', 'p') ?></div>
   <div class="step"><div class="n"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></div><?= bi('৪. হাতে পান', '4. Receive it', 'h3') ?><?= bi('ফোনে কনফার্ম করে পৌঁছে দেব, টাকা দেবেন পণ্য হাতে পেয়ে', 'We confirm by phone and deliver. Pay when you get it', 'p') ?></div>
  </div>
 </div>
</section>
<?php endif; ?>

<section id="contact" style="padding-top:10px">
 <div class="wrap">
  <div class="cta">
   <div>
    <span class="xmas" style="font-size:20px">Call or WhatsApp</span>
    <?= $T('cms_contact_title', 'h2') ?>
    <?= $T('cms_contact_text', 'p') ?>
    <a class="ph" href="tel:<?= h($tel) ?>" data-contact><?= h($phone) ?></a>
    <div class="btns"><a class="btn btn-wa" href="https://wa.me/<?= h($wa) ?>?text=Assalamu%20alaikum%2C%20Christmas%20item%20order%20korte%20chai" target="_blank" rel="noopener" data-contact><?= bi('WhatsApp এ মেসেজ', 'Message on WhatsApp') ?></a><a class="btn btn-gold" href="tel:<?= h($tel) ?>" data-contact><?= bi('কল করুন', 'Call us') ?></a></div>
   </div>
   <img src="img/p5-s.webp" alt="Santa Claus" loading="lazy" width="500" height="500">
  </div>
 </div>
</section>

<footer>
 <div class="wrap">
  <div class="fgrid">
   <div><div class="fbrand"><?= h($store) ?></div><?= $T('cms_footer', 'p') ?><div class="paychips"><span>Cash on Delivery</span><?php if ($pay['bkash']): ?><span>bKash</span><?php endif; ?><?php if ($pay['nagad']): ?><span>Nagad</span><?php endif; ?></div></div>
   <div><?= bi('লিংক', 'Links', 'h3') ?><?= bi('প্রোডাক্ট', 'Products', 'a', ' href="#products"') ?><?= bi('Christmas Tree', 'Christmas Tree', 'a', ' href="#trees"') ?><?php if ($on('cms_steps')): ?><?= bi('কিভাবে অর্ডার', 'How to order', 'a', ' href="#how"') ?><?php endif; ?></div>
   <div><?= bi('যোগাযোগ', 'Contact', 'h3') ?><a href="tel:<?= h($tel) ?>" data-contact>📞 <?= h($phone) ?></a><a href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" data-contact>💬 WhatsApp</a><?php if ($fb): ?><a href="<?= h($fb) ?>" target="_blank" rel="noopener">📘 Facebook</a><?php endif; ?><?php if ($email): ?><a href="mailto:<?= h($email) ?>">✉️ <?= h($email) ?></a><?php endif; ?><?php if ($addr): ?><span style="display:block;padding:3px 0">📍 <?= h($addr) ?></span><?php endif; ?></div>
  </div>
  <div class="copy">© <span id="yr">2026</span> <?= h($store) ?>. Merry Christmas! 🎄</div>
 </div>
</footer>

<a class="fab" href="https://wa.me/<?= h($wa) ?>?text=Christmas%20Gallery%20theke%20order%20korte%20chai" target="_blank" rel="noopener" aria-label="WhatsApp" data-contact><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2m4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4a.5.5 0 0 0 0-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3"/></svg></a>

<div class="drawer" id="drawer"><div class="dpanel" role="dialog" aria-modal="true" aria-labelledby="dtitle">
 <div class="dhead"><b id="dtitle">আপনার কার্ট</b><button data-close aria-label="Close">×</button></div>
 <div class="dstep" id="st-cart" style="display:flex;flex-direction:column;flex:1;min-height:0">
  <div class="dbody" id="citems"></div>
  <div class="dfoot" id="cfoot">
   <div class="freebar" id="freebar"></div>
   <div class="sumrow"><?= bi('প্রোডাক্টের দাম', 'Items total') ?><b id="c-sub2"></b></div>
   <button class="btn btn-gold" id="to-form"><?= bi('অর্ডার করতে এগিয়ে যান →', 'Proceed to order →') ?></button>
  </div>
 </div>
 <form class="dstep f" id="st-form" hidden style="flex:1;overflow:auto" novalidate>
  <div class="dbody" style="overflow:visible">
   <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
   <label for="f-name"><?= bi('আপনার নাম *', 'Your name *') ?></label><input id="f-name" name="cname" required maxlength="80" autocomplete="name">
   <label for="f-phone"><?= bi('মোবাইল নম্বর *', 'Mobile number *') ?></label><input id="f-phone" name="phone" required inputmode="tel" maxlength="16" placeholder="01XXXXXXXXX" autocomplete="tel">
   <label for="f-addr"><?= bi('পুরো ঠিকানা *', 'Full address *') ?></label><textarea id="f-addr" name="address" required rows="3" maxlength="400" placeholder="বাসা, রোড, এলাকা, থানা, জেলা" data-en-ph="House, road, area, thana, district"></textarea>
   <label><?= bi('ডেলিভারি এলাকা', 'Delivery area') ?></label>
   <div class="areas"><label><input type="radio" name="area" value="dhaka" checked> <?= bi('ঢাকার ভিতরে', 'Inside Dhaka') ?> <span class="num" data-n="<?= $dd ?>"></span></label><label><input type="radio" name="area" value="outside"> <?= bi('ঢাকার বাইরে', 'Outside Dhaka') ?> <span class="num" data-n="<?= $do ?>"></span></label></div>
   <label><?= bi('পেমেন্ট পদ্ধতি', 'Payment method') ?></label>
   <div class="pays">
    <?php if ($pay['cod']): ?><label><input type="radio" name="pay" value="cod" checked> 💵 <?= bi('ক্যাশ অন ডেলিভারি (পণ্য হাতে পেয়ে টাকা)', 'Cash on delivery (pay when you get it)') ?></label><?php endif; ?>
    <?php if ($pay['bkash']): ?><label><input type="radio" name="pay" value="bkash" <?= $pay['cod'] ? '' : 'checked' ?>> 📱 bKash</label><?php endif; ?>
    <?php if ($pay['nagad']): ?><label><input type="radio" name="pay" value="nagad" <?= ($pay['cod'] || $pay['bkash']) ? '' : 'checked' ?>> 📱 Nagad</label><?php endif; ?>
   </div>
   <div class="paybox" id="paybox" hidden><div id="payno"></div><div style="margin-top:4px"><?= h($pay['note']) ?></div>
    <label for="f-trx"><?= bi('Transaction ID *', 'Transaction ID *') ?></label><input id="f-trx" name="trx" maxlength="40" autocomplete="off" placeholder="যেমন 8N7A6B5C4D" data-en-ph="e.g. 8N7A6B5C4D"></div>
   <label for="f-note"><?= bi('কিছু বলার থাকলে (ঐচ্ছিক)', 'Anything to tell us (optional)') ?></label><input id="f-note" name="note" maxlength="400" placeholder="যেমন: কোন রঙ/ডিজাইন চান" data-en-ph="e.g. colour or design you want">
  </div>
  <div class="dfoot">
   <div class="sumrow"><?= bi('প্রোডাক্টের দাম', 'Items total') ?><b id="c-sub"></b></div>
   <div class="sumrow"><?= bi('ডেলিভারি চার্জ', 'Delivery charge') ?><b id="c-del"></b></div>
   <div class="sumrow t"><?= bi('মোট', 'Total') ?><span id="c-tot"></span></div>
   <div class="err" id="cerr" role="alert"></div>
   <button type="submit" class="btn btn-cart" style="margin-top:4px" id="submitbtn"><?= defined('CG_STATIC') ? bi('WhatsApp এ অর্ডার পাঠান', 'Send order on WhatsApp') : bi('অর্ডার কনফার্ম করুন', 'Confirm order') ?></button>
   <button type="button" class="btn" id="to-cart" style="background:none;color:var(--muted);margin-top:4px"><?= bi('← কার্টে ফিরে যান', '← Back to cart') ?></button>
  </div>
 </form>
 <div class="dstep dbody" id="st-done" hidden>
  <div class="done"><div class="ok">✓</div><?php if (defined('CG_STATIC')): ?><?= bi('ধন্যবাদ! আর এক ধাপ বাকি', 'Thank you! One more step', 'h3') ?><?php else: ?><?= bi('ধন্যবাদ! অর্ডার পেয়েছি', 'Thank you! We got your order', 'h3') ?><?php endif; ?><div id="d-nobox"><?= bi('অর্ডার নম্বর', 'Order number', 'p') ?><div class="code" id="d-code"></div></div><p id="d-wahint" hidden style="color:var(--muted)"><?= bi('WhatsApp এ অর্ডারের মেসেজ তৈরি হয়ে আছে। নিচের বাটনে চাপুন, তারপর Send করুন। আমরা কল করে কনফার্ম করব।', 'Your order message is ready on WhatsApp. Tap the button below and press Send. We will call to confirm.') ?></p><p><?= bi('মোট:', 'Total:') ?> <b id="d-total"></b></p><?= bi('আমরা শীঘ্রই ফোন করে অর্ডার কনফার্ম করব। দ্রুত কনফার্ম করতে WhatsApp এও পাঠাতে পারেন।', 'We will call you soon to confirm. For a faster confirmation you can also send it on WhatsApp.', 'p') ?>
  <a class="btn btn-wa" id="d-wa" target="_blank" rel="noopener"><?= bi('WhatsApp এ অর্ডার পাঠান', 'Send order on WhatsApp') ?></a><button class="btn btn-gold" data-close><?= bi('আরও কেনাকাটা করুন', 'Continue shopping') ?></button></div>
 </div>
</div></div>
<div class="toast" id="toast" role="status"></div>
<div class="modal" id="modal"><div class="mbox" role="dialog" aria-modal="true" aria-label="Product"><button class="x" id="mx" aria-label="Close">×</button><div class="mi"><img id="mimg" alt=""><div class="thumbs" id="thumbs"></div></div><div class="mt" id="mt"></div></div></div>
<div class="modal" id="rmodal"><div class="mbox mform" role="dialog" aria-modal="true" aria-label="Review"><button class="x" data-rclose aria-label="Close">×</button>
 <form class="mt f" id="rform" novalidate>
  <?= bi('রিভিউ লিখুন', 'Write a review', 'h3') ?>
  <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
  <label for="r-p"><?= bi('কোন প্রোডাক্ট', 'Which product') ?></label><select id="r-p" name="pid"></select>
  <label><?= bi('রেটিং', 'Rating') ?></label>
  <div class="rate"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" id="rt<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="rt<?= $i ?>" title="<?= $i ?>">★</label><?php endfor; ?></div>
  <label for="r-n"><?= bi('আপনার নাম *', 'Your name *') ?></label><input id="r-n" name="rname" maxlength="60" required>
  <label for="r-ph"><?= bi('মোবাইল নম্বর (সাইটে দেখাবে না)', 'Mobile number (not shown)') ?></label><input id="r-ph" name="rphone" inputmode="tel" maxlength="16">
  <label for="r-b"><?= bi('আপনার মতামত *', 'Your review *') ?></label><textarea id="r-b" name="body" rows="3" maxlength="1000" required></textarea>
  <div class="err" id="rerr" role="alert"></div>
  <button class="btn btn-cart" type="submit"><?= bi('রিভিউ পাঠান', 'Send review') ?></button>
 </form></div></div>
<script>
var CFG = <?= $json ?>;
var DEMO = <?= defined('CG_DEMO') ? 'true' : 'false' ?>, STATIC = <?= defined('CG_STATIC') ? 'true' : 'false' ?>;
var WA = CFG.wa, PRODUCTS = CFG.items, TREES = CFG.trees, ALL = {};
PRODUCTS.concat(TREES).forEach(function(p){ALL[p.id]=p});
var LANG='bn';try{LANG=localStorage.getItem('cg_lang')||'bn'}catch(e){}
var qm=/[?&]lang=(en|bn)/.exec(location.search);if(qm)LANG=qm[1];
function L(b,e){return LANG==='en'?e:b}
var bn=function(n){return String(n).replace(/\d/g,function(d){return "০১২৩৪৫৬৭৮৯"[d]})};
function num(n){return LANG==='en'?String(n):bn(n)}
function tk(n){return '৳ '+num(Number(n).toLocaleString('en-IN'))}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function en2(s){return String(s).replace(/[০-৯]/g,function(d){return "০১২৩৪৫৬৭৮৯".indexOf(d)})}
function px(ev,data,eid){try{if(window.fbq&&CFG.pixel){if(eid)fbq('track',ev,data||{},{eventID:eid});else fbq('track',ev,data||{})}}catch(e){}}
function uid(){return 'cg'+Date.now().toString(36)+Math.random().toString(36).slice(2,8)}

var WAIC='<svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2m0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2m4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4a.5.5 0 0 0 0-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3"/></svg>';
var CARTIC='<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L22 8H6"/></svg>';
var PHIC='<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>';

/* ===== product helpers ===== */
function desc(p){return LANG==='en'&&p.desc_en?p.desc_en:p.desc}
function off(p){return p.price&&p.old>p.price?Math.round(100-p.price/p.old*100):0}
function pick(p){return p.sizes.length||p.colors.length}
function stars(r){r=Math.round(r);return '<span class="stars">'+'★'.repeat(r)+'<i>'+'★'.repeat(5-r)+'</i></span>'}
function priceHTML(p){return p.price?'<span class="price">'+tk(p.price)+(p.old>p.price?' <s>'+tk(p.old)+'</s>':'')+'</span>':'<span class="price ask">'+L('দাম জানতে মেসেজ করুন','Message us for price')+'</span>'}
function waLink(p,q,s,c){var t="Assalamu alaikum, Christmas Gallery theke order korte chai:\n"+p.name+(p.size?" ("+p.size+")":"")+(s?"\nSize: "+s:"")+(c?"\nColor: "+c:"")+"\nQuantity: "+q+(p.price?"\nPrice: "+p.price+" tk":"")+"\n\nAmar naam:\nPhone:\nAddress:";return "https://wa.me/"+WA+"?text="+encodeURIComponent(t)}
function badges(p,tree){var o=off(p),b='';
 if(o)b='<span class="badge off">-'+num(o)+'%</span>';
 else if(p.fl)b='<span class="badge">'+L('ফ্ল্যাশ সেল','Flash sale')+'</span>';
 else if(p.nw)b='<span class="badge nw">New</span>';
 else if(p.bs)b='<span class="badge bs">Best Seller</span>';
 else if(p.badge)b='<span class="badge'+(tree?' g':'')+'">'+esc(p.badge)+'</span>';
 return b?'<div class="badges">'+b+'</div>':''}
function lowq(p){return p.stock&&p.qty!==null&&p.qty<10?'<span class="lowq">'+L('মাত্র '+num(p.qty)+'টি বাকি','Only '+p.qty+' left')+'</span>':''}
function buyBtns(p){
 var w='<a class="btn btn-wa btn-sq" target="_blank" rel="noopener" href="'+waLink(p,1)+'" aria-label="WhatsApp">'+WAIC+'</a>';
 if(!p.stock)return '<div class="order"><span class="btn btn-off">'+L('স্টক শেষ','Out of stock')+'</span>'+w+'</div>';
 if(!p.price)return '<div class="order"><a class="btn btn-wa" target="_blank" rel="noopener" href="'+waLink(p,1)+'">'+WAIC+' '+L('অর্ডার করুন','Order')+'</a></div>';
 if(pick(p))return '<div class="order"><button class="btn btn-cart" data-open="'+p.id+'">'+CARTIC+' '+L('বেছে নিন','Choose')+'</button>'+w+'</div>';
 return '<div class="order"><button class="btn btn-cart" data-add="'+p.id+'">'+CARTIC+' '+L('কার্টে নিন','Add to cart')+'</button>'+w+'</div>'}
function card(p,tree){
 var opt=[];if(p.sizes.length)opt.push(L(num(p.sizes.length)+'টি সাইজ',p.sizes.length+' sizes'));if(p.colors.length)opt.push(L(num(p.colors.length)+'টি কালার',p.colors.length+' colours'));
 return '<article class="card" data-id="'+p.id+'">'+
  '<div class="img '+(p.cls==='contain'?'contain ':'')+(p.cls&&p.cls!=='contain'?esc(p.cls):'')+'" data-open="'+p.id+'"><img src="'+esc(p.th||p.img)+'" alt="'+esc(p.name)+'" loading="lazy" width="600" height="600">'+
  badges(p,tree)+(p.size?'<span class="size">'+esc(p.size)+'</span>':'')+(!p.stock?'<span class="soldout">'+L('স্টক শেষ','Out of stock')+'</span>':'')+'</div>'+
  '<div class="body"><h3>'+esc(p.name)+'</h3>'+(p.rc?'<div class="rc">'+stars(p.rating)+' ('+num(p.rc)+')</div>':'')+'<p class="desc">'+esc(desc(p))+'</p>'+(opt.length?'<span class="opts">'+opt.join(' · ')+'</span>':'')+lowq(p)+
  '<div class="row">'+priceHTML(p)+'</div>'+
  buyBtns(p)+'</div></article>'}

/* ===== listing, filters ===== */
var CAT='',Q='',SORT='';
function renderChips(){var c=document.getElementById('chips');if(!c)return;
 c.innerHTML='<button class="chip'+(CAT?'':' on')+'" data-chip="0">'+L('সব','All')+'</button>'+CFG.cats.map(function(k){return '<button class="chip'+(CAT===k.id?' on':'')+'" data-chip="'+k.id+'">'+esc(k.name)+'</button>'}).join('')}
function sortList(a){a=a.slice();
 if(SORT==='new')a.sort(function(x,y){return y.id-x.id});
 else if(SORT==='lo'||SORT==='hi')a.sort(function(x,y){if(!x.price)return 1;if(!y.price)return -1;return SORT==='lo'?x.price-y.price:y.price-x.price});
 else a.sort(function(x,y){return (y.ft-x.ft)||(y.bs-x.bs)});
 return a}
function renderGrids(){
 var q=Q.trim().toLowerCase(),filtered=!!(CAT||q);
 var src=filtered?PRODUCTS.concat(TREES):PRODUCTS;
 var list=src.filter(function(p){return (!CAT||p.cat===CAT)&&(!q||(p.name+' '+p.desc+' '+p.desc_en+' '+p.badge).toLowerCase().indexOf(q)>-1)});
 document.getElementById('pgrid').innerHTML=sortList(list).map(function(p){return card(p,p.type==='tree')}).join('')||'<div class="noitems">'+L('কিছু পাওয়া যায়নি। অন্য কিছু লিখে খুঁজুন।','Nothing found. Try another word.')+'</div>';
 document.getElementById('tgrid').innerHTML=sortList(TREES).map(function(p){return card(p,true)}).join('');
 document.getElementById('trees').style.display=TREES.length&&!filtered?'':'none';
 var fs=document.getElementById('flash');if(fs){var fl=PRODUCTS.concat(TREES).filter(function(p){return p.fl&&p.price&&p.stock});fs.hidden=!fl.length;document.getElementById('fgrid').innerHTML=fl.map(function(p){return card(p,p.type==='tree')}).join('')}
}

/* ===== cart (saved in this browser) ===== */
var CART=[];try{CART=JSON.parse(localStorage.getItem('cg_cart')||'[]')}catch(e){}
CART=CART.filter(function(c){var p=ALL[c.id];return p&&p.price&&p.stock&&c.qty>0}).map(function(c){return {id:String(c.id),qty:+c.qty,s:c.s||'',c:c.c||''}});
function maxQ(p){return p.qty!==null?Math.max(1,Math.min(50,p.qty)):50}
function save(){try{localStorage.setItem('cg_cart',JSON.stringify(CART))}catch(e){}renderCart()}
function addCart(id,q,s,c){var p=ALL[id];s=s||'';c=c||'';var it=CART.find(function(x){return x.id==id&&x.s===s&&x.c===c});
 if(it)it.qty=Math.min(maxQ(p),it.qty+q);else CART.push({id:String(id),qty:Math.min(maxQ(p),q),s:s,c:c});save();toast(L('কার্টে যোগ হয়েছে ✓','Added to cart ✓'));
 px('AddToCart',{content_ids:[String(id)],content_name:p.name,content_type:'product',value:p.price*q,currency:'BDT'})}
function sub(){return CART.reduce(function(s,c){return s+ALL[c.id].price*c.qty},0)}
function delivery(){var s=sub();if(CFG.free>0&&s>=CFG.free)return 0;return area()==='outside'?CFG.do:CFG.dd}
function renderCart(){
 var n=CART.reduce(function(s,c){return s+c.qty},0);
 document.querySelectorAll('.cart-n').forEach(function(e){e.textContent=num(n);e.style.display=n?'grid':'none'});
 var box=document.getElementById('citems');
 if(!CART.length){box.innerHTML='<div class="empty">🎄<p>'+L('কার্ট খালি','Your cart is empty')+'</p><a class="btn btn-gold" href="#products" data-close>'+L('প্রোডাক্ট দেখুন','See products')+'</a></div>';document.getElementById('cfoot').style.display='none';return}
 document.getElementById('cfoot').style.display='';
 box.innerHTML=CART.map(function(c,i){var p=ALL[c.id];var v=[c.s?L('সাইজ: ','Size: ')+esc(c.s):'',c.c?L('কালার: ','Colour: ')+esc(c.c):''].filter(Boolean).join(' · ');
  return '<div class="ci"><img src="'+esc(p.th||p.img)+'" alt=""><div><b>'+esc(p.name)+(p.size?' ('+esc(p.size)+')':'')+'</b>'+(v?'<div class="var">'+v+'</div>':'')+'<div class="cp">'+tk(p.price)+' × '+num(c.qty)+' = <strong>'+tk(p.price*c.qty)+'</strong></div><div class="qty sm"><button data-cq="-1" data-i="'+i+'">−</button><span>'+num(c.qty)+'</span><button data-cq="1" data-i="'+i+'">+</button></div></div><button class="rm" data-rm="'+i+'" aria-label="remove">×</button></div>'}).join('');
 calc()}
function area(){var r=document.querySelector('input[name=area]:checked');return r?r.value:'dhaka'}
function payv(){var r=document.querySelector('input[name=pay]:checked');return r?r.value:'cod'}
function calc(){var s=sub(),d=delivery();
 document.getElementById('c-sub').textContent=tk(s);document.getElementById('c-sub2').textContent=tk(s);
 document.getElementById('c-del').textContent=d?tk(d):L('ফ্রি','Free');document.getElementById('c-tot').textContent=tk(s+d);
 var fb=document.getElementById('freebar');fb.textContent=CFG.free>0?(s>=CFG.free?L('🎉 আপনার ডেলিভারি ফ্রি!','🎉 Your delivery is free!'):L('আরও '+tk(CFG.free-s)+' কিনলে ডেলিভারি ফ্রি','Buy '+tk(CFG.free-s)+' more for free delivery')):''}
function payUI(){var v=payv(),b=document.getElementById('paybox');b.hidden=v==='cod';
 if(v!=='cod'){var no=v==='bkash'?CFG.pay.bkash_no:CFG.pay.nagad_no;document.getElementById('payno').innerHTML=(v==='bkash'?'bKash':'Nagad')+' '+L('নম্বর','number')+': <b>'+esc(no||'-')+'</b> (Send Money)'}}
function openCart(step){document.getElementById('toast').classList.remove('on');document.getElementById('drawer').classList.add('open');document.body.style.overflow='hidden';showStep(step||'cart')}
function closeCart(){document.getElementById('drawer').classList.remove('open');document.body.style.overflow=''}
var STEP='cart';
function showStep(s){STEP=s;['cart','form','done'].forEach(function(k){document.getElementById('st-'+k).style.display=(k!==s)?'none':(k==='done'?'block':'flex');if(k==='form')document.getElementById('st-form').style.flexDirection='column'});
 document.getElementById('dtitle').textContent={cart:L('আপনার কার্ট','Your cart'),form:L('অর্ডার তথ্য','Order details'),done:L('অর্ডার সম্পন্ন','Order placed')}[s];if(s==='form'){calc();payUI()}}
var tt;function toast(m){var t=document.getElementById('toast');t.textContent=m;t.classList.add('on');clearTimeout(tt);tt=setTimeout(function(){t.classList.remove('on')},1800)}

/* ===== product view ===== */
var MP=null;
function openProduct(id){var p=ALL[id];if(!p)return;MP={p:p,s:p.sizes[0]||'',c:p.colors[0]||'',q:1};renderModal();
 var imgs=[p.img].concat(p.gal||[]).filter(function(u,i,a){return a.indexOf(u)===i}),mi=document.getElementById('mimg');mi.src=imgs[0];mi.alt=p.name;
 document.getElementById('thumbs').innerHTML=imgs.length>1?imgs.map(function(u,i){return '<img src="'+esc(u)+'" alt="" data-th="'+esc(u)+'"'+(i?'':' class="on"')+'>'}).join(''):'';
 document.getElementById('modal').classList.add('open');px('ViewContent',{content_ids:[String(p.id)],content_name:p.name,content_type:'product',value:p.price,currency:'BDT'})}
function renderModal(){if(!MP)return;var p=MP.p,o=off(p),mt=document.getElementById('mt');
 var h=(p.size?'<span class="ptag">'+esc(p.size)+'</span>':'')+'<h3>'+esc(p.name)+'</h3>'+
  (p.rc?'<div class="rc">'+stars(p.rating)+' '+num(p.rating)+' ('+L(num(p.rc)+'টি রিভিউ',p.rc+' reviews')+')</div>':'')+
  '<div>'+priceHTML(p)+(o?' <span class="badge off" style="position:static;display:inline-block;margin-left:6px">-'+num(o)+'%</span>':'')+'</div>'+lowq(p)+
  '<p style="color:var(--muted)">'+esc(desc(p))+'</p>';
 if(p.sizes.length)h+='<div class="ml">'+L('সাইজ','Size')+'</div><div class="pick">'+p.sizes.map(function(s){return '<button data-ps="'+esc(s)+'" class="'+(s===MP.s?'on':'')+'">'+esc(s)+'</button>'}).join('')+'</div>';
 if(p.colors.length)h+='<div class="ml">'+L('কালার','Colour')+'</div><div class="pick">'+p.colors.map(function(c){return '<button data-pc="'+esc(c)+'" class="'+(c===MP.c?'on':'')+'">'+esc(c)+'</button>'}).join('')+'</div>';
 if(p.price&&p.stock)h+='<div class="qty" style="align-self:flex-start"><button data-mq="-1">−</button><span>'+num(MP.q)+'</span><button data-mq="1">+</button></div><div class="order"><button class="btn btn-cart" data-madd="0">'+CARTIC+' '+L('কার্টে নিন','Add to cart')+'</button><button class="btn btn-gold" data-madd="1">'+L('এখনই কিনুন','Buy now')+'</button></div>';
 else if(!p.stock)h+='<span class="btn btn-off">'+L('স্টক শেষ','Out of stock')+'</span>';
 h+='<div class="order"><a class="btn btn-wa" target="_blank" rel="noopener" href="'+waLink(p,MP.q,MP.s,MP.c)+'">'+WAIC+' '+L('WhatsApp এ অর্ডার','Order on WhatsApp')+'</a><a class="btn btn-call" href="tel:'+CFG.tel+'" aria-label="Call">'+PHIC+'</a></div>';
 if(p.details)h+='<div class="ml">'+L('বিস্তারিত','Details')+'</div><div class="details">'+esc(p.details)+'</div>';
 h+='<div class="mrev"><div class="ml">'+L('রিভিউ','Reviews')+(p.rc?' ('+num(p.rc)+')':'')+'</div>'+(p.rv.length?p.rv.map(function(r){return '<div class="rv">'+stars(r.r)+' <b>'+esc(r.n)+'</b><div>'+esc(r.b)+'</div></div>'}).join(''):'<p style="color:var(--muted);font-size:14.5px">'+L('এখনো কোনো রিভিউ নেই।','No reviews yet.')+'</p>')+'<button class="linkbtn" data-review="'+p.id+'">'+L('রিভিউ লিখুন','Write a review')+'</button></div>';
 mt.innerHTML=h}
function closeModal(){document.getElementById('modal').classList.remove('open');MP=null}

/* ===== reviews ===== */
function openReview(pid){var s=document.getElementById('r-p');s.innerHTML=PRODUCTS.concat(TREES).map(function(p){return '<option value="'+p.id+'"'+(p.id==pid?' selected':'')+'>'+esc(p.name)+'</option>'}).join('');
 document.getElementById('rerr').textContent='';closeModal();document.getElementById('rmodal').classList.add('open')}
document.getElementById('rform').addEventListener('submit',function(e){e.preventDefault();var f=e.target,er=document.getElementById('rerr'),b=f.querySelector('button[type=submit]');
 if(!f.rname.value.trim()||!f.body.value.trim()){er.textContent=L('নাম আর মতামত লিখুন','Please write your name and review');return}
 var rp=ALL[f.pid.value];var d={pid:f.pid.value,product:rp?rp.name:'',name:f.rname.value,phone:en2(f.rphone.value),rating:+(f.querySelector('input[name=rating]:checked')||{value:5}).value,body:f.body.value,website:f.website.value};
 b.disabled=true;
 (STATIC?Promise.resolve(waReview(d)):DEMO?Promise.resolve({ok:true}):fetch('shop-api.php?a=review',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)}).then(function(r){return r.json()}))
 .then(function(r){if(!r.ok)throw new Error(r.error||r.msg);f.reset();document.getElementById('rmodal').classList.remove('open');toast(L('ধন্যবাদ! যাচাইয়ের পর রিভিউ সাইটে দেখাবে','Thank you! Your review will show after a check'))})
 .catch(function(x){er.textContent=(x&&x.message&&x.message!=='Failed to fetch')?x.message:L('পাঠানো যায়নি, আবার চেষ্টা করুন','Could not send, please try again')}).finally(function(){b.disabled=false})});

/* ===== clicks ===== */
document.addEventListener('click',function(e){
 var t=e.target;
 var ch=t.closest('[data-chip]');if(ch){CAT=ch.dataset.chip==='0'?'':ch.dataset.chip;renderChips();renderGrids();return}
 var ct=t.closest('[data-cat]');if(ct){CAT=ct.dataset.cat;Q='';document.getElementById('q').value='';renderChips();renderGrids()}
 var th=t.closest('[data-th]');if(th){document.getElementById('mimg').src=th.dataset.th;document.querySelectorAll('#thumbs img').forEach(function(i){i.classList.toggle('on',i===th)});return}
 var ps=t.closest('[data-ps]');if(ps){MP.s=ps.dataset.ps;renderModal();return}
 var pc=t.closest('[data-pc]');if(pc){MP.c=pc.dataset.pc;var gm=(MP.p.galc||[]).find(function(g){return g.c&&g.c.toLowerCase()===MP.c.toLowerCase()});if(gm){document.getElementById('mimg').src=gm.s;document.querySelectorAll('#thumbs img').forEach(function(i){i.classList.toggle('on',i.dataset.th===gm.s)})}renderModal();return}
 var mq=t.closest('[data-mq]');if(mq){MP.q=Math.max(1,Math.min(maxQ(MP.p),MP.q+(+mq.dataset.mq)));renderModal();return}
 var ma=t.closest('[data-madd]');if(ma){addCart(MP.p.id,MP.q,MP.s,MP.c);if(ma.dataset.madd==='1'){closeModal();openCart()}return}
 var rv=t.closest('[data-review]');if(rv){openReview(rv.dataset.review);return}
 if(t.closest('[data-rclose]')||t.id==='rmodal'){document.getElementById('rmodal').classList.remove('open');return}
 var q=t.closest('[data-q]');
 if(q){var c=q.closest('.card'),s=c.querySelector('.qty span'),p=ALL[c.dataset.id],n=(+c.dataset.qty||1)+(+q.dataset.q);n=Math.max(1,Math.min(maxQ(p),n));c.dataset.qty=n;s.textContent=num(n);var w=c.querySelector('.btn-wa');if(w)w.href=waLink(p,n);return}
 var a=t.closest('[data-add]');if(a){var c2=a.closest('[data-id]');addCart(a.dataset.add,+(c2&&c2.dataset.qty)||1);return}
 var cq=t.closest('[data-cq]');if(cq){var it=CART[+cq.dataset.i];it.qty=Math.max(1,Math.min(maxQ(ALL[it.id]),it.qty+(+cq.dataset.cq)));save();return}
 var rm=t.closest('[data-rm]');if(rm){CART.splice(+rm.dataset.rm,1);save();return}
 if(t.closest('[data-cart]')){openCart();return}
 if(t.closest('[data-close]')||t.id==='drawer'){closeCart();return}
 if(t.closest('#to-form')){showStep('form');px('InitiateCheckout',{value:sub(),currency:'BDT',num_items:CART.reduce(function(s,c){return s+c.qty},0),content_ids:CART.map(function(c){return String(c.id)})});return}
 if(t.closest('#to-cart')){showStep('cart');return}
 var o=t.closest('[data-open]');if(o){openProduct(o.dataset.open);return}
 if(t.id==='modal'||t.closest('#mx')){closeModal()}
 if(t.closest('.menu a')){document.getElementById('menu').classList.remove('open')}
 var lk=t.closest('a[href*="wa.me"],a[href^="tel:"]');if(lk)px('Contact');
});
document.addEventListener('change',function(e){if(e.target.name==='area')calc();if(e.target.name==='pay')payUI();if(e.target.id==='sort'){SORT=e.target.value;renderGrids()}});
document.getElementById('q').addEventListener('input',function(e){Q=e.target.value;renderGrids()});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeModal();document.getElementById('rmodal').classList.remove('open');closeCart()}});
document.getElementById('burger').onclick=function(){document.getElementById('menu').classList.toggle('open')};

/* ===== order ===== */
document.getElementById('st-form').addEventListener('submit',function(e){
 e.preventDefault();var f=e.target,btn=document.getElementById('submitbtn'),er=document.getElementById('cerr');er.textContent='';
 if(!CART.length){showStep('cart');return}
 if(!f.cname.value.trim()){er.textContent=L('আপনার নাম লিখুন','Please write your name');f.cname.focus();return}
 var ph=en2(f.phone.value).replace(/[^0-9]/g,'').replace(/^88/,'');
 if(!/^01[3-9]\d{8}$/.test(ph)){er.textContent=L('সঠিক ১১ সংখ্যার মোবাইল নম্বর দিন','Please give a correct 11 digit mobile number');f.phone.focus();return}
 if(f.address.value.trim().length<5){er.textContent=L('পুরো ঠিকানা লিখুন','Please write your full address');f.address.focus();return}
 var pay=payv();if(pay!=='cod'&&f.trx.value.trim().length<6){er.textContent=L('Transaction ID লিখুন','Please write the Transaction ID');f.trx.focus();return}
 var old=btn.innerHTML;btn.disabled=true;btn.textContent=L('পাঠানো হচ্ছে...','Sending...');
 var lines=CART.map(function(c){var p=ALL[c.id];return {id:p.id,name:p.name,size:p.size,v:[c.s,c.c].filter(Boolean).join(' / '),price:p.price,qty:c.qty}});
 var payload={name:f.cname.value,phone:ph,address:f.address.value,area:area()==='outside'?'out':'in',note:f.note.value,website:f.website.value,pay:pay,trx:f.trx.value,items:lines.map(function(i){return {id:i.id,q:i.qty,v:i.v}})};
 (STATIC?Promise.resolve({ok:1,no:'',total:sub()+delivery(),ship:delivery(),wa:1}):DEMO?demoOrder(payload):fetch('shop-api.php?a=order',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(function(r){return r.json()}))
 .then(function(r){
  if(!r.ok)throw new Error(r.error||'error');
  var txt=lines.map(function(i){return '- '+i.name+(i.size?' ('+i.size+')':'')+(i.v?' ['+i.v+']':'')+' x'+i.qty+' = '+(i.price*i.qty)+' tk'}).join('\n');
  var msg='Assalamu alaikum, ami website theke order korte chai.'+(r.no?'\nOrder: '+r.no:'')+'\n'+txt+'\nDelivery: '+r.ship+' tk\nTotal: '+r.total+' tk'+(pay!=='cod'?'\nPayment: '+pay+' (TrxID '+f.trx.value+')':'')+'\n\nNaam: '+f.cname.value+'\nPhone: '+ph+'\nAddress: '+f.address.value;
  document.getElementById('d-code').textContent=r.no;document.getElementById('d-total').textContent=tk(r.total);
  var wl='https://wa.me/'+WA+'?text='+encodeURIComponent(msg);document.getElementById('d-wa').href=wl;
  if(r.wa){document.getElementById('d-nobox').hidden=true;document.getElementById('d-wahint').hidden=false;try{window.open(wl,'_blank')}catch(e){}}
  px('Purchase',{value:r.total,currency:'BDT',content_type:'product',content_ids:lines.map(function(i){return String(i.id)}),num_items:lines.reduce(function(s,i){return s+i.qty},0)},r.no);
  CART=[];save();f.reset();showStep('done');
 }).catch(function(x){er.textContent=(x&&x.message&&x.message!=='Failed to fetch'&&x.message!=='error')?x.message:L('অর্ডার পাঠানো যায়নি। ইন্টারনেট চেক করে আবার চেষ্টা করুন, অথবা WhatsApp এ অর্ডার দিন।','Could not send the order. Check your internet and try again, or order on WhatsApp.')})
 .finally(function(){btn.disabled=false;btn.innerHTML=old;applyLang()});
});
function waReview(d){try{window.open('https://wa.me/'+WA+'?text='+encodeURIComponent('Review ('+d.product+')\n'+'★'.repeat(d.rating)+'\n'+d.body+'\n- '+d.name),'_blank')}catch(e){}return {ok:1}}
function demoOrder(d){var subtotal=d.items.reduce(function(s,i){return s+ALL[i.id].price*i.q},0),del=delivery();
 return Promise.resolve({ok:1,no:'CG-'+Math.random().toString(16).slice(2,8).toUpperCase(),total:subtotal+del,ship:del})}

/* ===== language ===== */
function applyLang(){
 document.documentElement.lang=LANG==='en'?'en':'bn';
 document.querySelectorAll('[data-en]').forEach(function(el){if(el.dataset.bn===undefined)el.dataset.bn=el.textContent;el.textContent=LANG==='en'?el.dataset.en:el.dataset.bn});
 document.querySelectorAll('[data-en-ph]').forEach(function(el){if(el.dataset.bnph===undefined)el.dataset.bnph=el.placeholder;el.placeholder=LANG==='en'?el.dataset.enPh:el.dataset.bnph});
 document.querySelectorAll('.num[data-n]').forEach(function(el){el.textContent=tk(el.dataset.n)});
 document.getElementById('langt').textContent=LANG==='en'?'বাংলা':'English';
}
function setLang(l){LANG=l;try{localStorage.setItem('cg_lang',l)}catch(e){}applyLang();renderChips();renderGrids();renderCart();if(document.getElementById('drawer').classList.contains('open'))showStep(STEP);if(MP)renderModal();cd&&cd()}
document.getElementById('lang').onclick=function(){setLang(LANG==='en'?'bn':'en')};

/* hero slider */
(function(){var s=document.querySelectorAll('.slide'),d=document.getElementById('dots'),i=0,t;
 if(!s.length)return;
 if(s.length<2){document.querySelectorAll('.arrow').forEach(function(a){a.style.display='none'})}
 s.forEach(function(_,k){var b=document.createElement('button');b.setAttribute('aria-label','Slide '+(k+1));b.onclick=function(){go(k)};d.appendChild(b)});
 if(s.length<2)d.style.display='none';
 function go(k){s[i].classList.remove('on');d.children[i].classList.remove('on');i=(k+s.length)%s.length;s[i].classList.add('on');d.children[i].classList.add('on');clearInterval(t);if(s.length>1)t=setInterval(function(){go(i+1)},5000)}
 document.querySelectorAll('.arrow').forEach(function(a){a.onclick=function(){go(i+(+a.dataset.dir))}});
 var x0=null,h=document.querySelector('.hero');h.addEventListener('touchstart',function(e){x0=e.touches[0].clientX},{passive:true});
 h.addEventListener('touchend',function(e){if(x0===null)return;var dx=e.changedTouches[0].clientX-x0;if(Math.abs(dx)>45)go(i+(dx<0?1:-1));x0=null});
 go(0);
 function fit(){var m=0;var w=document.getElementById('slides');w.style.minHeight='';s.forEach(function(x){x.style.display='block';x.style.position='absolute';x.style.visibility='hidden';x.style.width='100%';m=Math.max(m,x.offsetHeight);x.style.display='';x.style.position='';x.style.visibility='';x.style.width=''});w.style.minHeight=m+'px'}
 window.addEventListener('load',fit);window.addEventListener('resize',fit);document.getElementById('lang').addEventListener('click',function(){setTimeout(fit,50)})})();

/* countdown to 25 December */
var cd=null;
(function(){if(!document.getElementById('cd-d'))return;cd=function(){var now=new Date(),y=now.getFullYear(),end=new Date(y,11,25);if(now>=new Date(y,11,26))end=new Date(y+1,11,25);
 var ms=Math.max(0,end-now),d=Math.floor(ms/864e5),h=Math.floor(ms/36e5)%24,m=Math.floor(ms/6e4)%60,s=Math.floor(ms/1e3)%60;
 function p(n){return num(String(n).padStart(2,'0'))}
 document.getElementById('cd-d').textContent=p(d);document.getElementById('cd-h').textContent=p(h);document.getElementById('cd-m').textContent=p(m);document.getElementById('cd-s').textContent=p(s)};
 cd();setInterval(cd,1000)})();
document.getElementById('yr').textContent=new Date().getFullYear();

<?php if ($on('cms_snow')): ?>
/* light snowfall in hero */
(function(){var c=document.getElementById('snow'),x=c.getContext('2d'),f=[],W,H;
 if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
 function rs(){W=c.width=c.offsetWidth;H=c.height=c.offsetHeight}rs();addEventListener('resize',rs);
 var N=innerWidth<600?35:70;for(var k=0;k<N;k++)f.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*2.4+.8,v:Math.random()*.7+.3,w:Math.random()*.6-.3});
 (function loop(){x.clearRect(0,0,W,H);x.fillStyle='rgba(255,255,255,.85)';f.forEach(function(p){p.y+=p.v;p.x+=p.w;if(p.y>H){p.y=-5;p.x=Math.random()*W}x.beginPath();x.arc(p.x,p.y,p.r,0,6.3);x.fill()});requestAnimationFrame(loop)})();})();
<?php endif; ?>

renderChips();renderGrids();renderCart();applyLang();
</script>
</body>
</html>
