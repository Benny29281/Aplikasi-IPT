<?php
// Campaigns are stored in settings, so existing installations need no schema migration.
function campaignConfig(): array {
    $stored=json_decode(setting('campaign_config','{}'),true);
    return array_replace(['enabled'=>false,'label'=>'11.11','title'=>'Momen besar, pilihan istimewa.','starts_at'=>'','ends_at'=>'','discount_percent'=>10,'product_ids'=>[]],is_array($stored)?$stored:[]);
}
function campaignLive(?array $config=null,?int $now=null): bool {
    $c=$config??campaignConfig();$now=$now??time();
    $start=strtotime($c['starts_at']);$end=strtotime($c['ends_at']);
    return !empty($c['enabled'])&&$start!==false&&$end!==false&&$now>=$start&&$now<$end;
}
function campaignPrice(int $base,int $productId,bool $restricted=false,?array $config=null,?int $now=null): int {
    $c=$config??campaignConfig();
    if($restricted||!campaignLive($c,$now)||!in_array($productId,array_map('intval',$c['product_ids']),true))return $base;
    return (int)floor($base*(100-max(0,min(90,(int)$c['discount_percent'])))/100);
}
function campaignProduct(array $p,?array $config=null): array {
    $c=$config??campaignConfig();$id=(int)($p['product_id']??$p['id']);
    $p['sale_price']=null;$p['discount_percent']=0;$p['sale_ends_at']=null;
    if(empty($p['is_age_restricted'])&&campaignLive($c)&&in_array($id,array_map('intval',$c['product_ids']),true)) {
        $p['sale_price']=campaignPrice((int)$p['price'],$id,false,$c);
        $p['discount_percent']=(int)$c['discount_percent'];$p['sale_ends_at']=date(DATE_ATOM,strtotime($c['ends_at']));
    }
    return $p;
}
function publicCampaign(): array {
    $c=campaignConfig();$start=strtotime($c['starts_at']);$end=strtotime($c['ends_at']);
    $mode='disabled';
    if(!empty($c['enabled'])&&$start!==false&&$end!==false)$mode=time()<$start?'scheduled':(time()<$end?'live':'ended');
    elseif(setting('demo_data','1')==='1')$mode='preview';
    $products=[];$ids=array_values(array_filter(array_map('intval',$c['product_ids']),fn($id)=>$id>0));
    if($mode==='live'&&$ids){
        $marks=implode(',',array_fill(0,count($ids),'?'));
        $products=all("SELECT p.*,c.name category_name,c.slug category_slug,c.is_age_restricted,(SELECT image_url FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id IN ($marks) AND p.is_active=1 AND c.is_active=1 AND c.is_age_restricted=0 ORDER BY p.id LIMIT 12",$ids);
        $products=array_map(fn($p)=>campaignProduct($p,$c),$products);
    }
    return [...$c,'mode'=>$mode,'starts_at'=>$start===false?null:date(DATE_ATOM,$start),'ends_at'=>$end===false?null:date(DATE_ATOM,$end),'products'=>$products,'server_now'=>date(DATE_ATOM)];
}
function validateCampaign(array $d): array {
    required($d,['label','title']);
    if(mb_strlen($d['label'])>20||mb_strlen($d['title'])>120)fail('Judul event terlalu panjang.');
    $enabled=!empty($d['enabled']);$start=trim($d['starts_at']??'');$end=trim($d['ends_at']??'');
    foreach([$start,$end] as $value)if($value!==''){
        $format=strlen($value)===19?'Y-m-d H:i:s':'Y-m-d H:i';
        $dt=DateTime::createFromFormat('!'.$format,$value);
        if(!$dt||$dt->format($format)!==$value)fail('Jadwal event harus menggunakan format YYYY-MM-DD HH:mm (WIB).');
    }
    if($enabled&&(!$start||!$end))fail('Isi waktu mulai dan selesai sebelum mengaktifkan event.');
    if($start&&$end&&strtotime($end)<=strtotime($start))fail('Waktu selesai harus setelah waktu mulai.');
    if(!is_numeric($d['discount_percent']??null)||(float)$d['discount_percent']!=(int)$d['discount_percent']||$d['discount_percent']<1||$d['discount_percent']>90)fail('Diskon event harus berupa angka 1–90 persen.');
    if(!is_array($d['product_ids']??null))fail('Pilih produk event.');
    $ids=array_values(array_unique(array_map('intval',$d['product_ids'])));
    if(count($ids)>20||($enabled&&!$ids))fail('Pilih 1–20 produk untuk event aktif.');
    foreach($ids as $id){$p=one('SELECT p.id,c.is_age_restricted FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1 AND c.is_active=1',[$id]);if(!$p||$p['is_age_restricted'])fail('Produk event harus aktif dan bukan kategori terbatas.');}
    return ['enabled'=>$enabled,'label'=>trim($d['label']),'title'=>trim($d['title']),'starts_at'=>$start,'ends_at'=>$end,'discount_percent'=>(int)$d['discount_percent'],'product_ids'=>$ids];
}
