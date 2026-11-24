#!/bin/bash
# builds: out/docs (GitHub Pages demo) and out/christmas-gallery.zip (cPanel)
set -e
cd $(cd "$(dirname "$0")/.." && pwd)
rm -rf out && mkdir -p out/docs/admin out/tmp
# --- demo ---
cp -r site out/tmp/s && rm -f out/tmp/s/data/shop.sqlite* out/tmp/s/uploads/*.webp
( cd out/tmp/s && php -r '
define("CG_DEMO",1); require_once "inc/db.php";
$pr=[1=>650,2=>850,3=>950,4=>450,5=>1200,6=>150,7=>1200,8=>2500,9=>4500];
foreach($pr as $id=>$p) db()->prepare("UPDATE products SET price=? WHERE id=?")->execute([$p,$id]);
db()->exec("UPDATE products SET old_price=750 WHERE id=1");
ob_start(); include "index.php"; file_put_contents("$(cd "$(dirname "$0")/.." && pwd)/out/docs/index.html", ob_get_clean());
$list=array_map(fn($p)=>["id"=>(int)$p["id"],"type"=>$p["type"],"name"=>$p["name"],"img"=>$p["img"],"price"=>(int)$p["price"],"size"=>$p["size"],"stock"=>(int)$p["stock"]], products(false));
file_put_contents("$(cd "$(dirname "$0")/.." && pwd)/out/tmp/products.json", json_encode($list, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG));
' )
cp -r site/img out/docs/img
python3 - <<'P'
d='$(cd "$(dirname "$0")/.." && pwd)/'
head='<!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin Demo | Christmas Gallery</title><link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">'
body=open(d+'demo-src/admin-body.html').read().replace('__PRODUCTS__',open(d+'out/tmp/products.json').read())
open(d+'out/docs/admin/index.html','w').write(head+open(d+'demo-src/admin-style.html').read()+'</head>'+body)
P
touch out/docs/.nojekyll
# --- cPanel zip ---
mkdir -p out/tmp/z && cp -r site/. out/tmp/z/ && rm -f out/tmp/z/data/shop.sqlite* out/tmp/z/uploads/*.webp
( cd out/tmp/z && zip -qr ../../christmas-gallery.zip . -x '*.DS_Store' )
rm -rf out/tmp
ls -la out out/docs; unzip -l out/christmas-gallery.zip | tail -25
