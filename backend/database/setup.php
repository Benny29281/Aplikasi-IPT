<?php
if(PHP_SAPI!=='cli') { http_response_code(404);exit; }
require __DIR__.'/../app/bootstrap.php';
$host=env('DB_HOST','127.0.0.1');$port=env('DB_PORT','3306');$name=env('DB_DATABASE','ipt_store');
if(!preg_match('/^[a-zA-Z0-9_]+$/',$name)) exit("Nama database tidak valid.\n");
$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",env('DB_USERNAME','root'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if(!one("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=? AND table_name='users'",[$name])['n']) db()->exec(file_get_contents(__DIR__.'/schema.sql'));
if(one('SELECT COUNT(*) n FROM products')['n']) exit("Database sudah berisi produk. Tidak ada data ditimpa.\n");
db()->beginTransaction();
foreach([['Instax Camera','instax-camera',0],['Instax Film','instax-film',0],['Nvidia & Hardware','nvidia',0],['QuickSnap','quicksnap',0],['TEREA · 21+','terea',1]] as $n=>$c) query('INSERT INTO categories(id,name,slug,is_age_restricted) VALUES(?,?,?,?)',[$n+1,...$c]);
$products=[
 [1,'Instax SQ40','instax-sq40',2499000,'Kamera instan dengan desain klasik dan format foto square. Abadikan momen dengan auto exposure dan mode selfie. Gunakan film Instax Square (dijual terpisah).','instax-sq40.png',[['Black',12]],'instax-sq40-description.png'],
 [1,'Instax Wide 400','instax-wide-400',2699000,'Lebih banyak cerita dalam satu bingkai. Kamera instan format wide untuk foto bersama dan momen sehari-hari. Gunakan film Instax Wide (dijual terpisah).','instax-wide-400-green.png',[['Green',8],['Black',6]],'instax-wide-400.png'],
 [2,'Instax Mini Pink Lemonade','instax-mini-pink-lemonade',149000,'Film instan Instax Mini dengan bingkai Pink Lemonade. Isi 10 lembar. Kompatibel dengan kamera dan printer Instax format Mini.','instax-mini-pink-lemonade.png',[['10 lembar',30]]],
 [2,'Instax Square Black Frame','instax-square-black-frame',159000,'Film Instax Square dengan bingkai hitam. Isi 10 lembar. Untuk kamera dan printer Instax format Square, termasuk SQ40.','instax-square-black-frame.png',[['10 lembar',24]]],
 [2,'Instax Wide Twin Pack','instax-wide-twin-pack',249000,'Film Instax Wide berisi 20 lembar (2 × 10). Untuk kamera format Wide, termasuk Wide 400. Simpan di tempat sejuk dan kering.','instax-wide-twin-pack.png',[['20 lembar',18]]],
 [4,'Fujifilm QuickSnap','fujifilm-quicksnap',299000,'Kamera sekali pakai Fujifilm QuickSnap. Praktis untuk mendokumentasikan perjalanan dengan pengalaman fotografi analog. Hasil foto memerlukan proses cuci film.','quicksnap.png',[['Standard',15]]],
 [3,'Gigabyte Graphic Card','gigabyte-graphic-card',4599000,'Kartu grafis Gigabyte. Foto menggunakan aset yang disediakan. Model, kapasitas memori, spesifikasi, dan garansi belum dikonfirmasi; data ini adalah contoh dan harus dilengkapi admin sebelum penjualan.','gigabyte-graphic-card.png',[['Model perlu konfirmasi',0]]]
];
foreach($products as $idx=>$p) {
 $stock=array_sum(array_column($p[6],1)); query('INSERT INTO products(id,category_id,name,slug,price,description,stock) VALUES(?,?,?,?,?,?,?)',[$idx+1,$p[0],$p[1],$p[2],$p[3],$p[4],$stock]);$id=$idx+1;
 foreach($p[6] as $n=>$v) query('INSERT INTO product_variants(product_id,name,sku,stock) VALUES(?,?,?,?)',[$id,$v[0],'IPT-'.($idx+1).'-'.($n+1),$v[1]]);
 query('INSERT INTO product_images(product_id,image_url) VALUES(?,?)',[$id,'/assets/products/'.$p[5]]);
 if(isset($p[7])) query('INSERT INTO product_images(product_id,image_url,sort_order) VALUES(?,?,1)',[$id,'/assets/products/'.$p[7]]);
}
foreach(['terea_enabled'=>'0','demo_data'=>'1','bank_name'=>env('BANK_NAME'),'bank_account'=>env('BANK_ACCOUNT'),'bank_holder'=>env('BANK_HOLDER')] as $k=>$v) query('INSERT IGNORE INTO settings(`key`,value) VALUES(?,?)',[$k,$v]);
foreach(['instax-sq40.jpg','instax-mini-evo-gentle-rose.png','instax-mini-13.png'] as $f) query('INSERT INTO banners(image_url,link) VALUES(?,?)',['/assets/banners/'.$f,'instax-camera']);
db()->commit(); echo "Database dan katalog contoh siap. Jalankan create-admin.php untuk membuat admin.\n";
