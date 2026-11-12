<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/campaign.php';
$c=['enabled'=>true,'starts_at'=>'2026-12-12 10:00','ends_at'=>'2026-12-12 11:00','discount_percent'=>15,'product_ids'=>[1]];
$start=strtotime($c['starts_at']);$end=strtotime($c['ends_at']);
$checks=[
 'Before campaign'=>!campaignLive($c,$start-1),
 'Exact start'=>campaignLive($c,$start),
 'One second before end'=>campaignLive($c,$end-1),
 'Exact end'=>!campaignLive($c,$end),
 'Discount integer rounding'=>campaignPrice(1001,1,false,$c,$start)===850,
 'Unselected product'=>campaignPrice(1001,2,false,$c,$start)===1001,
 'Restricted product'=>campaignPrice(1001,1,true,$c,$start)===1001,
 'Expired price'=>campaignPrice(1001,1,false,$c,$end)===1001,
 'Disabled event'=>campaignPrice(1001,1,false,[...$c,'enabled'=>false],$start)===1001,
];
foreach($checks as $name=>$ok){if(!$ok)throw new RuntimeException('FAIL: '.$name);echo "PASS: $name\n";}
