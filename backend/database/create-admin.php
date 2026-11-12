<?php
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
echo "Email admin: "; $email=trim(fgets(STDIN));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) exit("Email tidak valid.\n");
echo "Nama admin: "; $name=trim(fgets(STDIN));
echo "Password (minimal 12 karakter, terlihat saat diketik): "; $password=trim(fgets(STDIN));
if(strlen($password)<12||strlen($password)>72||!$name) exit("Nama/password tidak valid.\n");
if(one('SELECT id FROM users WHERE email=?',[$email])) exit("Email sudah terdaftar. Tidak ada akun yang diubah.\n");
query("INSERT INTO users(name,email,phone,password,birth_date,role) VALUES(?,?,'-',?,'1990-01-01','admin')",[$name,$email,password_hash($password,PASSWORD_BCRYPT)]);
echo "Admin dibuat. Masuk melalui aplikasi untuk membuka panel admin.\n";
