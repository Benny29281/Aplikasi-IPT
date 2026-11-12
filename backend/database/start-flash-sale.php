<?php
// Explicit CLI action: starts one shared, fixed 30-minute sale. Never invoked on page load.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/campaign.php';
$products=all('SELECT p.id FROM products p JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND p.stock>0 AND c.is_active=1 AND c.is_age_restricted=0 ORDER BY p.id LIMIT 20');
if(!$products)exit("Tidak ada produk tersedia untuk Flash Sale.\n");
$start=new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta'));
$end=$start->modify('+30 minutes');
$config=validateCampaign(['enabled'=>true,'label'=>'FLASH SALE','title'=>'Momen besar, pilihan istimewa.','starts_at'=>$start->format('Y-m-d H:i:s'),'ends_at'=>$end->format('Y-m-d H:i:s'),'discount_percent'=>10,'product_ids'=>array_column($products,'id')]);
$json=json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
query('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=?',['campaign_config',$json,$json]);
echo json_encode(['duration_minutes'=>30,'discount_percent'=>10,'products'=>count($products),'starts_at'=>$start->format(DATE_ATOM),'ends_at'=>$end->format(DATE_ATOM),'mode'=>publicCampaign()['mode']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
