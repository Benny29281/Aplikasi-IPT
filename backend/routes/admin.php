<?php
$u=auth(true); $d=body();
if($path==='/admin/campaign') {
 if($u['role']!=='admin')fail('Hanya admin dapat mengatur event.',403);
 if($method==='PUT'){$c=validateCampaign($d);query('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=?',['campaign_config',json_encode($c),json_encode($c)]);}
 respond(campaignConfig());
}
if($path==='/admin/dashboard') respond([
 'revenue'=>(int)one("SELECT COALESCE(SUM(total),0) n FROM orders WHERE status IN ('diproses','dikirim','selesai')")['n'],
 'orders'=>(int)one('SELECT COUNT(*) n FROM orders')['n'],
 'pending'=>(int)one("SELECT COUNT(*) n FROM orders WHERE status='menunggu_verifikasi'")['n'],
 'low_stock'=>all('SELECT id,name,stock FROM products WHERE stock<=5 AND is_active=1'),
 'sales'=>all("SELECT DATE(created_at) day,SUM(total) total,COUNT(*) count FROM orders WHERE status IN ('diproses','dikirim','selesai') GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 30")
]);
if($path==='/admin/report') {
 $period=($_GET['period']??'daily')==='monthly'?'%Y-%m':'%Y-%m-%d';
 respond(all("SELECT DATE_FORMAT(created_at,'$period') period,COUNT(*) orders,SUM(subtotal) subtotal,SUM(shipping_cost) shipping,SUM(total) total FROM orders WHERE status IN ('diproses','dikirim','selesai') GROUP BY period ORDER BY period DESC"));
}
if($path==='/admin/orders') respond(all('SELECT o.*,u.name customer_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC'));
if(preg_match('~^/admin/orders/(\d+)(?:/(proof))?$~',$path,$m)) {
 $id=(int)$m[1]; $o=orderData($id,$u,true);
 if(!empty($m[2])) { if(!$o['payment_proof']) fail('Belum ada bukti pembayaran.',404); $file=__DIR__.'/../storage/'.$o['payment_proof']; respond('data:'.mime_content_type($file).';base64,'.base64_encode(file_get_contents($file))); }
 if($method==='PUT') {
  db()->beginTransaction(); $o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
  $next=$d['status']??''; $transitions=['menunggu_pembayaran'=>['dibatalkan'],'menunggu_verifikasi'=>['diproses','menunggu_pembayaran','dibatalkan'],'diproses'=>['dikirim'],'dikirim'=>['selesai']];
  if(!in_array($next,$transitions[$o['status']]??[],true)) throw new DomainException('Perubahan status tidak diizinkan.');
  if($next==='dikirim'&&empty(trim($d['tracking_number']??''))) throw new DomainException('Nomor resi wajib diisi.');
  if($next==='dibatalkan') cancelOrder($id);
  else {
   query('UPDATE orders SET status=?,tracking_number=? WHERE id=?',[$next,$d['tracking_number']??$o['tracking_number'],$id]);
   if($next==='diproses') query("UPDATE payments SET status='disetujui',verified_by=?,verified_at=NOW() WHERE order_id=?",[$u['id'],$id]);
   if($next==='menunggu_pembayaran') {query("UPDATE payments SET status='ditolak' WHERE order_id=?",[$id]);query('UPDATE orders SET expires_at=DATE_ADD(NOW(),INTERVAL 24 HOUR) WHERE id=?',[$id]);}
  }
  db()->commit();
 }
 respond(orderData($id,$u,true));
}
if($path==='/admin/users') { if($u['role']!=='admin') fail('Hanya admin dapat mengelola pengguna.',403); respond(all('SELECT id,name,email,phone,birth_date,role,age_status,created_at FROM users ORDER BY id DESC')); }
if(preg_match('~^/admin/users/(\d+)(?:/(identity))?$~',$path,$m)) {
 if($u['role']!=='admin') fail('Hanya admin dapat memeriksa identitas.',403); $id=(int)$m[1]; $user=one('SELECT * FROM users WHERE id=?',[$id]); if(!$user) fail('Pengguna tidak ditemukan.',404);
 if(!empty($m[2])) { if(!$user['ktp_image']) fail('Dokumen belum diunggah.',404); respond(identityImage($user['ktp_image'])); }
 if($method==='PUT') { if(!in_array($d['age_status']??'',['disetujui','ditolak'],true)||$user['age_status']!=='menunggu') fail('Status verifikasi tidak valid.'); if(new DateTime($user['birth_date'])>new DateTime('-21 years')) fail('Pengguna belum memenuhi usia.'); query('UPDATE users SET age_status=? WHERE id=?',[$d['age_status'],$id]); }
 respond(safeUser(one('SELECT * FROM users WHERE id=?',[$id])));
}
if($path==='/admin/settings') {
 if($u['role']!=='admin') fail('Hanya admin dapat mengubah pengaturan.',403);
 if($method==='PUT') foreach(['terea_enabled','bank_name','bank_account','bank_holder','demo_data'] as $key) if(isset($d[$key])) { if(in_array($key,['terea_enabled','demo_data'])&&!in_array((string)$d[$key],['0','1'],true)) fail('Pengaturan tidak valid.'); query('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=?',[$key,(string)$d[$key],(string)$d[$key]]); }
 respond(array_column(all('SELECT * FROM settings'),'value','key'));
}
if(preg_match('~^/admin/categories(?:/(\d+))?$~',$path,$m)) {
 if($u['role']!=='admin'&&$method!=='GET') fail('Hanya admin dapat mengelola kategori.',403); $id=(int)($m[1]??0);
 if($method==='DELETE') { if(one('SELECT id FROM products WHERE category_id=? LIMIT 1',[$id])) fail('Kategori masih memiliki produk. Nonaktifkan melalui edit.'); query('DELETE FROM categories WHERE id=?',[$id]); }
 elseif($method==='POST'||$method==='PUT') {
  required($d,['name','slug']); if(!preg_match('/^[a-z0-9-]+$/',$d['slug'])) fail('Slug hanya huruf kecil, angka, dan tanda hubung.');
  $values=[$d['name'],$d['slug'],!empty($d['is_age_restricted'])?1:0,!empty($d['is_active'])?1:0];
  if($id) query('UPDATE categories SET name=?,slug=?,is_age_restricted=?,is_active=? WHERE id=?',[...$values,$id]); else query('INSERT INTO categories(name,slug,is_age_restricted,is_active) VALUES(?,?,?,?)',$values);
 }
 respond(all('SELECT * FROM categories ORDER BY id'));
}
if($path==='/admin/upload'&&$method==='POST') {
 $file=upload('image','products'); respond(['image_url'=>'/media/'.$file]);
}
if(preg_match('~^/admin/products(?:/(\d+))?$~',$path,$m)) {
 $id=(int)($m[1]??0);
 if($method==='DELETE') { query('UPDATE products SET is_active=0 WHERE id=?',[$id]); respond(null,'Produk dinonaktifkan. Riwayat transaksi tetap tersimpan.'); }
 if($method==='POST'||$method==='PUT') {
  required($d,['name','category_id','description','price']); if(!is_numeric($d['price'])||$d['price']<0||$d['price']>999999999999) fail('Harga tidak valid.');
  if(!one('SELECT id FROM categories WHERE id=?',[(int)$d['category_id']])) fail('Kategori tidak valid.');
  $variants=$d['variants']??[]; if(!$variants||count($variants)>30) fail('Isi minimal satu varian, maksimal 30.'); $stock=0;
  foreach($variants as $v) { required($v,['name','sku']); if(!isset($v['stock'])||!is_numeric($v['stock'])||$v['stock']<0||$v['stock']>100000||floor((float)$v['stock'])!=(float)$v['stock']) fail('Stok varian harus bilangan bulat positif.'); if(isset($v['price_override'])&&$v['price_override']!==''&&(!is_numeric($v['price_override'])||$v['price_override']<0)) fail('Harga varian tidak valid.'); $stock+=(int)$v['stock']; }
  $images=$d['images']??[]; foreach($images as $img) if(!preg_match('~^/(assets/(products|banners)/|media/products/)[a-zA-Z0-9._-]+$~',$img)) fail('Lokasi foto produk tidak valid.');
  db()->beginTransaction();
  $values=[(int)$d['category_id'],$d['name'],$d['slug']??strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$d['name'])).'-'.bin2hex(random_bytes(2)),$d['description'],(int)$d['price'],$stock,max(0,(int)($d['weight_gram']??500)),!empty($d['is_active'])?1:0];
  if($id) { query('SELECT id FROM products WHERE id=? FOR UPDATE',[$id]); query('UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,stock=?,weight_gram=?,is_active=? WHERE id=?',[...$values,$id]); }
  else { query('INSERT INTO products(category_id,name,slug,description,price,stock,weight_gram,is_active) VALUES(?,?,?,?,?,?,?,?)',$values);$id=(int)db()->lastInsertId(); }
  $retained=[];
  foreach($variants as $v) {
   $vid=(int)($v['id']??0); $vals=[$v['name'],$v['sku'],isset($v['price_override'])&&$v['price_override']!==''?$v['price_override']:null,(int)$v['stock']];
   if($vid) { if(!one('SELECT id FROM product_variants WHERE id=? AND product_id=?',[$vid,$id])) throw new DomainException('Varian tidak sesuai produk.'); query('UPDATE product_variants SET name=?,sku=?,price_override=?,stock=? WHERE id=?',[...$vals,$vid]); }
   else { query('INSERT INTO product_variants(name,sku,price_override,stock,product_id) VALUES(?,?,?,?,?)',[...$vals,$id]); $vid=(int)db()->lastInsertId(); } $retained[]=$vid;
  }
  foreach(all('SELECT id FROM product_variants WHERE product_id=?',[$id]) as $v) if(!in_array((int)$v['id'],$retained,true)) {
   if(one('SELECT id FROM order_items WHERE variant_id=? LIMIT 1',[$v['id']])) throw new DomainException('Varian yang sudah dipesan tidak dapat dihapus. Atur stok menjadi 0.');
   query('DELETE FROM cart_items WHERE variant_id=?',[$v['id']]);query('DELETE FROM product_variants WHERE id=?',[$v['id']]);
  }
  query('DELETE FROM product_images WHERE product_id=?',[$id]); foreach($images as $n=>$img) query('INSERT INTO product_images(product_id,image_url,sort_order) VALUES(?,?,?)',[$id,$img,$n]); db()->commit(); respond(product($id,true),'Produk tersimpan.');
 }
 if($id) respond(product($id,true));
 respond(all('SELECT p.*,c.name category_name,(SELECT image_url FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC'));
}
fail('Endpoint admin tidak ditemukan.',404);
