<?php
// Entry point for Laragon Apache. PHP's local server uses backend/router.php.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
$local=$base&&str_starts_with($path,$base)?substr($path,strlen($base)):$path;
if(str_starts_with($local,'/api/')) {require __DIR__.'/backend/public/index.php';exit;}
$distRoot=realpath(__DIR__.'/mobile/dist');$staticFile=$distRoot?realpath($distRoot.($local==='/'?'/index.html':$local)):false;
if($staticFile&&is_file($staticFile)&&str_starts_with($staticFile,$distRoot.DIRECTORY_SEPARATOR)) {header('Content-Type: '.(['js'=>'application/javascript','html'=>'text/html','css'=>'text/css','ttf'=>'font/ttf'][pathinfo($staticFile,PATHINFO_EXTENSION)]??mime_content_type($staticFile)));readfile($staticFile);exit;}
if(str_starts_with($local,'/assets/')||str_starts_with($local,'/media/')) {
 $isMedia=str_starts_with($local,'/media/products/');
 $root=$isMedia?realpath(__DIR__.'/backend/storage/products'):realpath(__DIR__);
 $file=$isMedia?realpath(__DIR__.'/backend/storage'.substr($local,6)):realpath(__DIR__.substr($local,7));
 if($root&&$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&preg_match('~\.(png|jpg|jpeg|webp)$~i',$file)) {header('Content-Type: '.mime_content_type($file));header('X-Content-Type-Options: nosniff');readfile($file);exit;}
 http_response_code(404);exit;
}
$root=realpath(__DIR__.'/mobile/dist');$file=$root?realpath($root.($local==='/'?'/index.html':$local)):false;
if($file&&is_file($file)&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)) {header('Content-Type: '.(['js'=>'application/javascript','html'=>'text/html','css'=>'text/css'][pathinfo($file,PATHINFO_EXTENSION)]??mime_content_type($file)));readfile($file);exit;}
http_response_code(503);echo 'IPT Store: build aplikasi terlebih dahulu. Lihat README.md.';
