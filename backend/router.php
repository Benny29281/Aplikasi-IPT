<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$dist=realpath(__DIR__.'/../mobile/dist');
$static=$dist?realpath($dist.($path==='/'?'/index.html':$path)):false;
if($static&&is_file($static)&&str_starts_with($static,$dist.DIRECTORY_SEPARATOR)) {
 $ext=pathinfo($static,PATHINFO_EXTENSION); header('Content-Type: '.(['js'=>'application/javascript','css'=>'text/css','html'=>'text/html','ttf'=>'font/ttf','ico'=>'image/x-icon'][$ext]??mime_content_type($static))); readfile($static);return;
}
if(str_starts_with($path,'/media/products/')) {
 $root=realpath(__DIR__.'/storage/products'); $file=realpath(__DIR__.'/storage'.substr($path,6));
 if($root&&$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&preg_match('~\.(png|jpg|jpeg|webp)$~i',$file)) {header('Content-Type: '.mime_content_type($file));header('X-Content-Type-Options: nosniff');readfile($file);return;}
 http_response_code(404);return;
}
if(str_starts_with($path,'/assets/')) {
 $root=realpath(__DIR__.'/..'); $file=realpath($root.substr($path,7));
 if($file && str_starts_with($file,$root.DIRECTORY_SEPARATOR) && preg_match('~\.(png|jpg|jpeg|webp)$~i',$file)) { header('Content-Type: '.mime_content_type($file)); header('Cache-Control: public, max-age=86400'); readfile($file); return; }
 http_response_code(404); return;
}
if($path==='/'||$path==='/index.html'||str_starts_with($path,'/_expo/')||$path==='/favicon.ico') {
 $root=realpath(__DIR__.'/../mobile/dist'); $file=$root ? realpath($root.$path) : false;
 if($path==='/'||$path==='/index.html') $file=$root ? $root.'/index.html' : false;
 if($file&&is_file($file)&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)) { $ext=pathinfo($file,PATHINFO_EXTENSION); header('Content-Type: '.(['js'=>'application/javascript','css'=>'text/css','html'=>'text/html','ico'=>'image/x-icon'][$ext]??mime_content_type($file))); readfile($file); return; }
 if($path==='/') { header('Location: http://localhost:8081'); return; }
}
require __DIR__.'/public/index.php';
