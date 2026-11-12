<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/campaign.php';
$origin=$_SERVER['HTTP_ORIGIN']??'';
$origins=array_filter(explode(',',env('APP_ORIGINS','http://localhost:8081,http://localhost:8000')));
if(in_array($origin,$origins,true)) { header('Access-Control-Allow-Origin: '.$origin); header('Vary: Origin'); }
header('Access-Control-Allow-Headers: Content-Type, Authorization'); header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']==='OPTIONS') { http_response_code(204); exit; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $path=preg_replace('~^.*?/api(?:/|$)~','/',$path); $path=rtrim($path,'/')?:'/';
$method=$_SERVER['REQUEST_METHOD'];
try {
 if($path==='/health') respond(['service'=>'IPT Store API','database'=>one('SELECT 1 ok')['ok']]);
 expireOrders();
 if($path==='/campaign'&&$method==='GET')respond(publicCampaign());
 if($path==='/config') respond(['terea_enabled'=>setting('terea_enabled','0')==='1','bank'=>['name'=>setting('bank_name'),'account'=>setting('bank_account'),'holder'=>setting('bank_holder')],'shipping'=>[['id'=>'reguler','name'=>'Reguler · 2–5 hari kerja','cost'=>20000],['id'=>'express','name'=>'Express · 1–2 hari kerja','cost'=>40000]],'demo_data'=>setting('demo_data','1')==='1']);
 if($path==='/auth/register' && $method==='POST') {
  rateLimit('register',10); $d=body(); required($d,['name','email','phone','password','birth_date']);
  if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) fail('Alamat email tidak valid.');
  if(strlen($d['password'])<8||strlen($d['password'])>72) fail('Password harus 8–72 karakter.');
  $date=DateTime::createFromFormat('!Y-m-d',$d['birth_date']); if(!$date||$date->format('Y-m-d')!==$d['birth_date']||$date>new DateTime()) fail('Tanggal lahir tidak valid.');
  if(strlen($d['name'])>120||!preg_match('/^[+0-9 ()-]{8,30}$/',$d['phone'])) fail('Nama atau nomor HP tidak valid.');
  if(one('SELECT id FROM users WHERE email=?',[$d['email']])) fail('Email sudah terdaftar.');
  query('INSERT INTO users(name,email,phone,password,birth_date) VALUES(?,?,?,?,?)',[trim($d['name']),strtolower(trim($d['email'])),$d['phone'],password_hash($d['password'],PASSWORD_BCRYPT),$d['birth_date']]);
  $u=one('SELECT * FROM users WHERE id=?',[db()->lastInsertId()]); respond(['user'=>safeUser($u),'token'=>token($u)],'Akun berhasil dibuat.',201);
 }
 if($path==='/auth/login' && $method==='POST') {
  rateLimit('login'); $d=body(); required($d,['email','password']); $u=one('SELECT * FROM users WHERE email=?',[$d['email']]);
  if(!$u||!password_verify($d['password'],$u['password'])) fail('Email atau password salah.',401);
  respond(['user'=>safeUser($u),'token'=>token($u)],'Selamat datang kembali.');
 }
 if($path==='/auth/logout'&&$method==='POST') { $u=auth(); query('UPDATE users SET token_version=token_version+1 WHERE id=?',[$u['id']]); respond(null,'Anda telah keluar.'); }
 if($path==='/me') {
  $u=auth(); if($method==='PUT') { $d=body(); required($d,['name','phone']); if(strlen($d['name'])>120||!preg_match('/^[+0-9 ()-]{8,30}$/',$d['phone'])) fail('Nama atau nomor HP tidak valid.'); query('UPDATE users SET name=?,phone=? WHERE id=?',[$d['name'],$d['phone'],$u['id']]); $u=one('SELECT * FROM users WHERE id=?',[$u['id']]); } respond(safeUser($u));
 }
 if($path==='/me/age-verification'&&$method==='POST') {
  $u=auth(); if(setting('terea_enabled','0')!=='1') fail('Kategori terbatas sedang dinonaktifkan.',403);
  if(new DateTime($u['birth_date'])>new DateTime('-21 years')) fail('Verifikasi memerlukan usia minimal 21 tahun.',403);
  if($u['age_status']==='disetujui'||$u['age_status']==='menunggu') fail('Verifikasi telah diajukan.');
  $file=upload('image','identity'); query("UPDATE users SET ktp_image=?,age_status='menunggu' WHERE id=?",[$file,$u['id']]); respond(null,'Dokumen diterima. Menunggu pemeriksaan admin.');
 }
 if(preg_match('~^/addresses(?:/(\d+))?$~',$path,$m)) {
  $u=auth(); $id=(int)($m[1]??0);
  if($method==='GET') respond(all('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id DESC',[$u['id']]));
  if($id&&!one('SELECT id FROM addresses WHERE id=? AND user_id=?',[$id,$u['id']])) fail('Alamat tidak ditemukan.',404);
  if($method==='DELETE') { query('DELETE FROM addresses WHERE id=? AND user_id=?',[$id,$u['id']]); respond(null,'Alamat dihapus.'); }
  $d=body(); required($d,['label','recipient','phone','address','city','province','postal_code']);
  if(!preg_match('/^[0-9]{5}$/',$d['postal_code'])) fail('Kode pos harus 5 angka.');
  $fields=['label','recipient','phone','address','city','province','postal_code']; $values=array_map(fn($k)=>trim($d[$k]),$fields); $default=!empty($d['is_default'])?1:0;
  db()->beginTransaction(); if($default) query('UPDATE addresses SET is_default=0 WHERE user_id=?',[$u['id']]);
  if($id) query('UPDATE addresses SET label=?,recipient=?,phone=?,address=?,city=?,province=?,postal_code=?,is_default=? WHERE id=? AND user_id=?',[...$values,$default,$id,$u['id']]);
  else query('INSERT INTO addresses(label,recipient,phone,address,city,province,postal_code,is_default,user_id) VALUES(?,?,?,?,?,?,?,?,?)',[...$values,$default,$u['id']]);
  db()->commit(); respond(all('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id DESC',[$u['id']]),'Alamat tersimpan.');
 }
 if($path==='/banners') respond(all('SELECT * FROM banners WHERE is_active=1 ORDER BY id'));
 if($path==='/categories') respond(all('SELECT c.*,(SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_active=1) product_count FROM categories c WHERE is_active=1 AND (is_age_restricted=0 OR ?=1) ORDER BY id',[setting('terea_enabled','0')]));
 if($path==='/products') {
  $u=optionalUser(); $params=[]; $campaign=campaignConfig();$priceExpr='p.price';$saleIds=array_values(array_filter(array_map('intval',$campaign['product_ids']),fn($id)=>$id>0));if(campaignLive($campaign)&&$saleIds){$priceExpr='CASE WHEN c.is_age_restricted=0 AND p.id IN ('.implode(',',$saleIds).') THEN FLOOR(p.price*'.(100-max(0,min(90,(int)$campaign['discount_percent']))).'/100) ELSE p.price END';} $where='p.is_active=1 AND c.is_active=1'; if(!allowedRestricted($u)) $where.=' AND c.is_age_restricted=0';
  if(!empty($_GET['sale'])){$where.=campaignLive($campaign)&&$saleIds?' AND c.is_age_restricted=0 AND p.id IN ('.implode(',',$saleIds).')':' AND 1=0';}
  if(!empty($_GET['category'])) {$where.=' AND c.slug=?'; $params[]=$_GET['category'];}
  if(!empty($_GET['q'])) {$where.=' AND p.name LIKE ?'; $params[]='%'.mb_substr($_GET['q'],0,120).'%';}
  if(isset($_GET['min'])) {$where.=' AND ('.$priceExpr.')>=?';$params[]=max(0,(int)$_GET['min']);}
  if(isset($_GET['max'])) {$where.=' AND ('.$priceExpr.')<=?';$params[]=max(0,(int)$_GET['max']);}
  $sort=['newest'=>'p.id DESC','price_asc'=>$priceExpr.' ASC','price_desc'=>$priceExpr.' DESC'][''.($_GET['sort']??'newest')]??'p.id DESC';
  $page=max(1,(int)($_GET['page']??1)); $count=(int)one("SELECT COUNT(*) n FROM products p JOIN categories c ON c.id=p.category_id WHERE $where",$params)['n'];
  $items=all("SELECT p.*,c.name category_name,c.slug category_slug,c.is_age_restricted,(SELECT image_url FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY $sort LIMIT 12 OFFSET ".(($page-1)*12),$params);
  $campaign=campaignConfig();$items=array_map(fn($p)=>campaignProduct($p,$campaign),$items);
  respond(['items'=>$items,'total'=>$count,'page'=>$page,'pages'=>(int)ceil($count/12)]);
 }
 if(preg_match('~^/products/(\d+)$~',$path,$m)&&$method==='GET') respond(product((int)$m[1],false,optionalUser()));
 if(preg_match('~^/cart(?:/(\d+))?$~',$path,$m)) {
  $u=auth(); $cid=cartId((int)$u['id']); $id=(int)($m[1]??0); $d=body();
  if($method==='DELETE') query('DELETE FROM cart_items WHERE id=? AND cart_id=?',[$id,$cid]);
  elseif($method==='POST'||$method==='PUT') {
   $qty=(int)($d['qty']??1); if($qty<1||$qty>999) fail('Jumlah produk harus 1–999.');
   if($method==='PUT') { $item=one('SELECT * FROM cart_items WHERE id=? AND cart_id=?',[$id,$cid]); if(!$item) fail('Item tidak ditemukan.',404); $vid=$item['variant_id']; }
   else $vid=(int)($d['variant_id']??0);
   $v=one('SELECT * FROM product_variants WHERE id=?',[$vid]); if(!$v) fail('Pilih varian yang tersedia.'); $p=product((int)$v['product_id'],false,$u);
   $old=one('SELECT * FROM cart_items WHERE cart_id=? AND variant_id=?',[$cid,$vid]); $total=$method==='POST'?$qty+(int)($old['qty']??0):$qty;
   if($total>min((int)$v['stock'],(int)$p['stock'])) fail('Jumlah melebihi stok yang tersedia.');
   query('INSERT INTO cart_items(cart_id,product_id,variant_id,qty) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE qty=?',[$cid,$p['id'],$vid,$total,$total]);
  }
  respond(cart((int)$u['id']),'Keranjang diperbarui.');
 }
 if($path==='/orders'&&$method==='POST') {
  $u=auth(); $d=body(); if(!setting('bank_account')||!setting('bank_holder')||!setting('bank_name')) fail('Rekening pembayaran belum disiapkan admin. Silakan hubungi IPT.',409);
  $address=one('SELECT * FROM addresses WHERE id=? AND user_id=?',[(int)($d['address_id']??0),$u['id']]); if(!$address) fail('Pilih alamat pengiriman.');
  $courier=$d['courier']??'reguler'; if(!in_array($courier,['reguler','express'],true)) fail('Metode pengiriman tidak valid.'); $shipping=$courier==='express'?40000:20000;
  db()->beginTransaction();
  try {
   $cid=cartId((int)$u['id']); query('SELECT id FROM carts WHERE id=? FOR UPDATE',[$cid]);
   $items=all('SELECT * FROM cart_items WHERE cart_id=? ORDER BY product_id,variant_id FOR UPDATE',[$cid]); if(!$items) throw new DomainException('Keranjang masih kosong.');
   $subtotal=0; $snap=[];$pricingCampaign=campaignConfig();$pricingTime=time();
   foreach($items as $i) {
    $p=one('SELECT p.*,c.is_age_restricted,c.is_active category_active FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? FOR UPDATE',[$i['product_id']]);
    $v=one('SELECT * FROM product_variants WHERE id=? FOR UPDATE',[$i['variant_id']]);
    if(!$p['is_active']||!$p['category_active']||($p['is_age_restricted']&&!allowedRestricted($u))) throw new DomainException('Produk tidak lagi tersedia.');
    if($i['qty']>min((int)$p['stock'],(int)$v['stock'])) throw new DomainException('Stok '.$p['name'].' tidak mencukupi.');
    $price=campaignPrice((int)($v['price_override']??$p['price']),(int)$p['id'],(bool)$p['is_age_restricted'],$pricingCampaign,$pricingTime); $subtotal+=$price*$i['qty']; $snap[]=[$p['id'],$v['id'],$p['name'].' · '.$v['name'],$price,$i['qty']];
    query('UPDATE products SET stock=stock-? WHERE id=?',[$i['qty'],$p['id']]); query('UPDATE product_variants SET stock=stock-? WHERE id=?',[$i['qty'],$v['id']]);
   }
   if(isset($d['expected_total'])&&(int)$d['expected_total']!==$subtotal+$shipping)throw new DomainException('Harga berubah. Periksa ringkasan lalu coba lagi.');
   $number='IPT-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
   query('INSERT INTO orders(order_number,user_id,address_snapshot,subtotal,shipping_cost,total,courier,expires_at) VALUES(?,?,?,?,?,?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))',[$number,$u['id'],json_encode($address),$subtotal,$shipping,$subtotal+$shipping,$courier]); $oid=(int)db()->lastInsertId();
   foreach($snap as $i) query('INSERT INTO order_items(order_id,product_id,variant_id,name_snapshot,price,qty) VALUES(?,?,?,?,?,?)',[$oid,...$i]);
   query('INSERT INTO payments(order_id,amount) VALUES(?,?)',[$oid,$subtotal+$shipping]); query('DELETE FROM cart_items WHERE cart_id=?',[$cid]); db()->commit(); respond(orderData($oid,$u),'Pesanan berhasil dibuat.',201);
  } catch(Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; }
 }
 if($path==='/orders'&&$method==='GET') { $u=auth(); respond(all('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[$u['id']])); }
 if(preg_match('~^/orders/(\d+)(?:/(payment-proof|cancel|complete))?$~',$path,$m)) {
  $u=auth(); $id=(int)$m[1]; $o=orderData($id,$u); $action=$m[2]??'';
  if($action&&$method==='POST') {
   db()->beginTransaction(); $locked=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
   if($action==='cancel') { if(!in_array($locked['status'],['menunggu_pembayaran','menunggu_verifikasi'])) throw new DomainException('Pesanan sudah diproses dan tidak bisa dibatalkan.'); cancelOrder($id); }
   elseif($action==='payment-proof') { if($locked['status']!=='menunggu_pembayaran') throw new DomainException('Bukti bayar tidak dapat diunggah pada status ini.'); $file=upload('image','payments'); query("UPDATE orders SET payment_proof=?,status='menunggu_verifikasi' WHERE id=?",[$file,$id]); query("UPDATE payments SET proof_image=?,status='menunggu_verifikasi' WHERE order_id=?",[$file,$id]); }
   else { if($locked['status']!=='dikirim') throw new DomainException('Pesanan belum dikirim.'); query("UPDATE orders SET status='selesai' WHERE id=?",[$id]); }
   db()->commit();
  }
  respond(orderData($id,$u));
 }
 if(str_starts_with($path,'/admin/')) { require __DIR__.'/../routes/admin.php'; }
 fail('Endpoint tidak ditemukan.',404);
} catch(DomainException $e) { if(db()->inTransaction()) db()->rollBack(); fail($e->getMessage()); }
catch(PDOException $e) { if(isset($GLOBALS['db'])&&db()->inTransaction()) db()->rollBack(); error_log($e->getMessage()); fail('Database belum siap atau data tidak dapat disimpan. Periksa konfigurasi backend.',503); }
catch(Throwable $e) { if(function_exists('db')) {try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}} error_log($e->getMessage()); fail('Terjadi kendala pada server. Silakan coba kembali.',500); }
