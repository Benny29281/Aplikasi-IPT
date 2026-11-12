<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');
foreach (file_exists(__DIR__.'/../.env') ? file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k,$v] = explode('=', $line, 2); if (getenv(trim($k)) === false) putenv(trim($k).'='.trim($v," \t\n\r\0\x0B\"'"));
}
function env(string $key, string $default=''): string { $v=getenv($key); return $v===false ? $default : $v; }
function db(): PDO {
    static $db; if ($db) return $db;
    $db = new PDO('mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306').';dbname='.env('DB_DATABASE','ipt_store').';charset=utf8mb4',env('DB_USERNAME','root'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $db->exec("SET time_zone = '+07:00'"); return $db;
}
function query(string $sql, array $params=[]): PDOStatement { $q=db()->prepare($sql); $q->execute($params); return $q; }
function one(string $sql,array $params=[]): ?array { return query($sql,$params)->fetch() ?: null; }
function all(string $sql,array $params=[]): array { return query($sql,$params)->fetchAll(); }
function setting(string $key,string $default=''): string { return one('SELECT value FROM settings WHERE `key`=?',[$key])['value']??$default; }
function respond(mixed $data=null,string $message='',int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>$status<400,'data'=>$data,'message'=>$message],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function fail(string $msg,int $code=422): never { respond(null,$msg,$code); }
function body(): array { $raw=file_get_contents('php://input'); $data=json_decode($raw,true); return is_array($data) ? $data : $_POST; }
function required(array $data,array $keys): void { foreach($keys as $key) if(!isset($data[$key])||trim((string)$data[$key])==='') fail('Kolom '.str_replace('_',' ',$key).' wajib diisi.'); }
function b64(string $s): string { return rtrim(strtr(base64_encode($s),'+/','-_'),'='); }
function secret(): string { $s=env('JWT_SECRET'); if(strlen($s)<32) throw new RuntimeException('JWT_SECRET belum dikonfigurasi.'); return $s; }
function token(array $u): string { $h=b64('{"alg":"HS256","typ":"JWT"}'); $p=b64(json_encode(['sub'=>(int)$u['id'],'ver'=>(int)$u['token_version'],'exp'=>time()+86400*7])); return "$h.$p.".b64(hash_hmac('sha256',"$h.$p",secret(),true)); }
function auth(bool $admin=false): array {
    $header=$_SERVER['HTTP_AUTHORIZATION']??''; $t=explode('.',preg_replace('/^Bearer /','',$header));
    if(count($t)!==3||!hash_equals(b64(hash_hmac('sha256',"$t[0].$t[1]",secret(),true)),$t[2])) fail('Silakan masuk untuk melanjutkan.',401);
    $p=json_decode(base64_decode(strtr($t[1],'-_','+/')),true); if(!$p||($p['exp']??0)<time()) fail('Sesi berakhir. Silakan masuk kembali.',401);
    $u=one('SELECT * FROM users WHERE id=?',[$p['sub']??0]); if(!$u||(int)$u['token_version']!==($p['ver']??-1)) fail('Sesi tidak valid.',401);
    if($admin&&!in_array($u['role'],['admin','staff'],true)) fail('Akses admin diperlukan.',403); return $u;
}
function safeUser(array $u): array { unset($u['password'],$u['ktp_image'],$u['token_version']); return $u; }
function optionalUser(): ?array { return empty($_SERVER['HTTP_AUTHORIZATION']) ? null : auth(); }
function allowedRestricted(?array $u): bool { return setting('terea_enabled','0')==='1' && $u && $u['age_status']==='disetujui' && new DateTime($u['birth_date']) <= new DateTime('-21 years'); }
function product(int $id,bool $admin=false,?array $u=null): array {
    $p=one('SELECT p.*,c.name category_name,c.slug category_slug,c.is_age_restricted FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=?'.($admin?'':' AND p.is_active=1 AND c.is_active=1'),[$id]);
    if(!$p) fail('Produk tidak ditemukan.',404); if(!$admin&&$p['is_age_restricted']&&!allowedRestricted($u)) fail('Kategori tidak tersedia atau memerlukan verifikasi usia.',403);
    $p['variants']=all('SELECT * FROM product_variants WHERE product_id=? ORDER BY id',[$id]); $p['images']=all('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order,id',[$id]); return !$admin&&function_exists('campaignProduct')?campaignProduct($p):$p;
}
function cartId(int $uid): int { query('INSERT IGNORE INTO carts(user_id) VALUES(?)',[$uid]); return (int)one('SELECT id FROM carts WHERE user_id=?',[$uid])['id']; }
function cart(int $uid): array { $items=all('SELECT ci.*,p.name,p.stock product_stock,p.is_active,c.is_age_restricted,c.is_active category_active,v.name variant_name,v.stock,COALESCE(v.price_override,p.price) price,(SELECT image_url FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) image FROM cart_items ci JOIN products p ON p.id=ci.product_id JOIN categories c ON c.id=p.category_id JOIN product_variants v ON v.id=ci.variant_id WHERE ci.cart_id=? ORDER BY ci.id', [cartId($uid)]); if(!function_exists('campaignProduct'))return $items;$campaign=campaignConfig();return array_map(fn($p)=>campaignProduct($p,$campaign),$items); }
function upload(string $field,string $folder): string {
    $f=$_FILES[$field]??null; if(!$f||$f['error']!==UPLOAD_ERR_OK) fail('Pilih foto yang valid (maksimal 5 MB).');
    if($f['size']>5*1024*1024) fail('Ukuran foto maksimal 5 MB.');
    $type=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$type]??null;
    if(!$ext||!getimagesize($f['tmp_name'])) fail('Gunakan foto JPG, PNG, atau WebP.');
    $dir=__DIR__.'/../storage/'.$folder; if(!is_dir($dir)) mkdir($dir,0700,true); $name=bin2hex(random_bytes(20)).'.'.$ext;
    if($folder==='identity') {
        $key=env('DATA_KEY'); if(strlen($key)<32) throw new RuntimeException('DATA_KEY belum dikonfigurasi.');
        $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt(file_get_contents($f['tmp_name']),'aes-256-gcm',hash('sha256',$key,true),OPENSSL_RAW_DATA,$iv,$tag);
        if($cipher===false) throw new RuntimeException('Enkripsi identitas gagal.');$name.='.enc';
        if(file_put_contents("$dir/$name",json_encode(['type'=>$type,'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)]),LOCK_EX)===false) throw new RuntimeException('Gagal menyimpan identitas.');
    } elseif(!move_uploaded_file($f['tmp_name'],"$dir/$name")) throw new RuntimeException('Gagal menyimpan foto.'); return "$folder/$name";
}
function identityImage(string $path): string {
    $raw=json_decode(file_get_contents(__DIR__.'/../storage/'.$path),true);$key=env('DATA_KEY');
    if(!$raw||strlen($key)<32) throw new RuntimeException('Dokumen tidak dapat dibuka.');
    $data=openssl_decrypt(base64_decode($raw['data']),'aes-256-gcm',hash('sha256',$key,true),OPENSSL_RAW_DATA,base64_decode($raw['iv']),base64_decode($raw['tag']));
    if($data===false) throw new RuntimeException('Dekripsi dokumen gagal.');return 'data:'.$raw['type'].';base64,'.base64_encode($data);
}
function cancelOrder(int $id): void {
    foreach(all('SELECT * FROM order_items WHERE order_id=? ORDER BY product_id,variant_id',[$id]) as $i) {
        query('UPDATE products SET stock=stock+? WHERE id=?',[$i['qty'],$i['product_id']]);
        query('UPDATE product_variants SET stock=stock+? WHERE id=?',[$i['qty'],$i['variant_id']]);
    }
    query("UPDATE orders SET status='dibatalkan' WHERE id=?",[$id]); query("UPDATE payments SET status='dibatalkan' WHERE order_id=?",[$id]);
}
function expireOrders(): void {
    db()->beginTransaction(); try {
        foreach(all("SELECT id FROM orders WHERE status='menunggu_pembayaran' AND expires_at<NOW() ORDER BY id FOR UPDATE") as $o) cancelOrder((int)$o['id']); db()->commit();
    } catch(Throwable $e) { db()->rollBack(); throw $e; }
}
function orderData(int $id,array $u,bool $admin=false): array {
    $o=one('SELECT o.*,u.name customer_name,u.email customer_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?'.($admin?'':' AND user_id=?'),$admin?[$id]:[$id,$u['id']]);
    if(!$o) fail('Pesanan tidak ditemukan.',404); $o['address_snapshot']=json_decode($o['address_snapshot'],true);
    $o['items']=all('SELECT oi.*,(SELECT image_url FROM product_images WHERE product_id=oi.product_id ORDER BY sort_order,id LIMIT 1) image FROM order_items oi WHERE order_id=?',[$id]);
    $o['payment']=one('SELECT status,verified_at FROM payments WHERE order_id=?',[$id]); return $o;
}
function rateLimit(string $key,int $limit=10): void {
    $bucket=hash('sha256',$key.'|'.($_SERVER['REMOTE_ADDR']??'cli')); $now=time();
    query('INSERT INTO rate_limits(bucket,attempts,window_start) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(window_start<?,1,attempts+1),window_start=IF(window_start<?,?,window_start)',[$bucket,$now,$now-900,$now-900,$now]);
    if((int)one('SELECT attempts FROM rate_limits WHERE bucket=?',[$bucket])['attempts']>$limit) fail('Terlalu banyak percobaan. Coba kembali dalam 15 menit.',429);
}
