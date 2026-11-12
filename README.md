# IPT Store — Indonesia Pacific Trade

Aplikasi e-commerce berdasarkan **PRD (5).md**, dengan React Native + Expo SDK 58 untuk Android, iOS, iPad, dan web; PHP 8 REST API; serta MySQL 8. Logo, banner, dan foto menggunakan aset asli dalam folder proyek.

## Buka aplikasi

Pada lingkungan ini aplikasi tersedia di **http://localhost:8000** selama server lokal berjalan.

Untuk menyalakan kembali dari PowerShell di folder proyek:

```powershell
.\scripts\Start-IPT.cmd
```

Skrip memakai PHP/MySQL Laragon yang terpasang, menyiapkan database lokal pada port **3307** dalam `.runtime/mysql`, lalu menjalankan aplikasi di port **8000**. Database Laragon lain tidak diubah. File `backend/.env` dan kunci lokal tidak masuk Git. Saat pertama kali menjalankan di komputer lain, pasang dependensi terlebih dahulu:

```powershell
cd mobile
npm ci
npm run build:web
cd ..
.\scripts\Start-IPT.cmd
```

Node 22 disediakan sebagai dependensi lokal agar Node sistem tidak perlu diganti. Untuk perintah langsung, executable-nya `mobile/node_modules/node/bin/node.exe`. Instalasi awal `npm ci` memerlukan internet. Port server 8000 dapat diakses dari jaringan lokal, mengikuti pengaturan firewall komputer.

## Akun admin dan rekening

Tidak ada password admin bawaan. Buat akun admin melalui CLI:

```powershell
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' backend/database/create-admin.php
```

Masukkan email, nama, dan password minimal 12 karakter saat diminta. Masuk melalui aplikasi, buka **Akun → Panel Admin → Pengaturan**, lalu isi bank, nomor rekening, dan nama pemilik rekening perusahaan. Checkout sengaja ditolak sampai rekening lengkap.

**Harga dan stok awal adalah data contoh**, ditandai di antarmuka. GPU Gigabyte diset stok 0 karena model dan spesifikasinya belum dikonfirmasi. Ganti data produk dan rekening dengan data yang benar sebelum digunakan untuk transaksi nyata. Penanda data contoh dapat diubah oleh admin setelah data sudah diperiksa.

## HP / iPad dengan Expo Go

1. Laptop dan perangkat berada pada Wi-Fi yang sama. Jalankan `Start-IPT.cmd`.
2. Cari IPv4 LAN laptop melalui `ipconfig`, misalnya `192.168.1.10`.
3. Jalankan:

```powershell
.\scripts\Start-Mobile.cmd 192.168.1.10
```

4. Scan QR menggunakan Expo Go yang mendukung SDK 58. Jika versi Expo Go perangkat belum cocok, gunakan versi yang sesuai atau development build. Izinkan koneksi jaringan lokal bila diminta perangkat. Tidak memakai `localhost` untuk API pada perangkat fisik.

Alternatif memakai browser HP/iPad: buka `http://<IP-LAN-LAPTOP>:8000`. Antarmuka memakai grid 2 kolom pada HP, 3 kolom pada tablet, dan 4 kolom pada beranda laptop. Navigasi HP berada di bawah; checkout dan formulir ditumpuk pada layar di bawah 900 px.

## Animasi pembuka

Saat pertama membuka aplikasi, logo IPT tampil di tengah dengan animasi fade/scale singkat (sekitar 2 detik), lalu otomatis masuk ke beranda setelah data awal siap. Di browser, animasi tampil sekali per sesi tab; navigasi dan refresh dalam sesi yang sama tidak mengulanginya. Pada aplikasi native, intro tampil saat aplikasi dimulai kembali. Tautan langsung tetap menuju halaman tujuannya sesudah intro.

Pengaturan reduced motion perangkat memakai intro statis singkat. Logo menggunakan gambar asli secara utuh (`contain`); kegagalan gambar memiliki fallback tulisan. Pemilihan bahasa dan pemuatan akun berjalan bersamaan dengan intro, tanpa tambahan halaman onboarding yang perlu ditekan.

## Event & Flash Sale

Beranda kini memiliki banner event animasi, label seperti 11.11/12.12, hitung mundur hari/jam/menit/detik, serta kartu harga promo. Animasi dapat dijeda dan mengikuti preferensi reduced motion perangkat; animasi dan polling berhenti saat aplikasi tidak aktif.

Atur melalui **Panel Admin → Event & Flash Sale**: label, judul, tanggal mulai/selesai dalam WIB (`YYYY-MM-DD HH:mm`), diskon 1–90%, dan maksimal 20 produk aktif. Centang aktivasi dan simpan. Kategori terbatas selalu dikecualikan. Harga promo diterapkan oleh server pada harga dasar/varian, keranjang, filter harga, dan checkout; tidak ditumpuk dengan promo lain. Jika total berubah saat checkout, pesanan ditolak untuk ditinjau kembali. Pesanan yang sudah dibuat menyimpan harga saat itu.

Sebelum event dikonfigurasi, mode data contoh menampilkan **pratinjau**: tanpa diskon aktual dan tanpa hitung mundur palsu. Pada toko tanpa penanda data contoh, event yang nonaktif disembunyikan. Event terjadwal dimulai/berakhir otomatis berdasarkan waktu server, tanpa cron untuk penetapan harga. Promo menggunakan tabel `settings` yang sudah ada, tanpa migrasi atau perubahan harga dasar.

Pemeriksaan tambahan: `npm run check:promotion`, `backend/tests/campaign.php`, dan skenario event dalam `backend/tests/integration.php` (database pengujian terpisah).

## Pilihan bahasa

Gunakan tombol **globe / ID** di header, atau **Akun → Bahasa aplikasi**, untuk memilih Bahasa Indonesia, English, 繁體中文（香港） (Tionghoa Tradisional Hong Kong), 한국어 (Korea), atau 简体中文 (Tionghoa Sederhana).

Pilihan disimpan pada perangkat melalui AsyncStorage dan diterapkan kembali saat aplikasi dibuka. Pergantian bahasa tidak menghapus formulir atau keranjang. Navigasi, akun, checkout, pesanan, panel admin, pesan validasi, serta deskripsi katalog bawaan memiliki terjemahan lokal tanpa layanan penerjemahan eksternal. Nama produk, data pelanggan, alamat, dan rekening tidak diterjemahkan otomatis; konten baru yang diisi admin tetap menggunakan bahasa sumbernya. Tulisan di dalam foto/banner mengikuti gambar asli. Tanggal menyesuaikan bahasa, sedangkan mata uang tetap Rupiah (IDR).

Kamus berada di `mobile/src/i18n/`. Jalankan `npm run check:i18n` dari folder `mobile` untuk memeriksa kelengkapan terjemahan, parameter pesan, perlindungan data asli, serta format tanggal dan mata uang.

## Struktur

```text
mobile/
  App.tsx
  app.json
  src/
    api/           Axios, penyimpanan token, pemuatan data
    screens/       Beranda, katalog, akun, checkout, pesanan, admin
    components/    Layout, kartu produk, komponen UI
    navigation/    Rute web dan navigasi native
    context/       Akun, keranjang, konfigurasi
    utils/         Format Rupiah dan status
backend/
  app/             Koneksi PDO, JWT, validasi, stok, upload
  routes/          Operasi admin
  database/        Skema SQL, katalog awal, pembuatan admin
  public/          Entry point API
  storage/         Upload privat, identitas terenkripsi
  tests/           Pengujian integrasi API
scripts/           Peluncur Windows / Expo
brand/, banners/, products/, assets/   Aset yang disediakan
```

## Fitur

- Registrasi, login JWT, logout dengan pencabutan token, profil, dan beberapa alamat.
- Katalog berpaginasi, pencarian, kategori, batas harga, urutan harga/terbaru, galeri foto, dan varian.
- Keranjang persisten per akun dengan validasi stok.
- Checkout memakai harga server, alamat snapshot, kurir reguler/express, ongkir flat, dan transfer manual.
- Bukti pembayaran JPG/PNG/WebP maksimal 5 MB, pemeriksaan MIME, dan akses admin.
- Pesanan bernomor unik, pembayaran 24 jam, status, resi, konfirmasi penerimaan, dan pembatalan sebelum diproses.
- Stok produk/varian dikunci dalam transaksi saat checkout; pembatalan dan kedaluwarsa mengembalikan stok sekali saja.
- Admin/staff: produk, varian, foto, stok, pesanan, pembayaran, pengiriman, laporan harian/bulanan dan ekspor CSV melalui web.
- Khusus admin: kategori, pengaturan rekening, feature flag TEREA, dan pemeriksaan usia.
- TEREA **nonaktif secara bawaan**, tanpa produk tembakau contoh. Jika diaktifkan, akses produk dan checkout tetap memerlukan usia ≥21 dan persetujuan admin.
- Dokumen identitas disimpan dengan AES-256-GCM menggunakan `DATA_KEY`, di luar folder publik. Pembayaran dan identitas hanya diambil melalui endpoint terautentikasi sesuai peran.

## API dan database

Contoh konfigurasi ada pada `backend/.env.example`. Salin ke `backend/.env` untuk memakai MySQL Laragon port 3306 atau database sendiri. Isi `JWT_SECRET` dan `DATA_KEY` berbeda, masing-masing setidaknya 32 karakter acak. **Simpan DATA_KEY dengan aman**; menggantinya tanpa migrasi membuat dokumen lama tidak bisa didekripsi.

Jalankan `backend/database/setup.php` dengan PHP CLI untuk membuat skema dan katalog awal. SQL untuk phpMyAdmin ada di `backend/database/schema.sql`; gunakan pada database kosong. Setup tidak menimpa produk yang sudah ada.

API dasar: `http://localhost:8000/api`. Format respons: `{success, data, message}`. Rute utama mengikuti PRD: `/auth/*`, `/me`, `/addresses`, `/categories`, `/products`, `/cart`, `/orders`, `/admin/*`. CORS mengizinkan origin yang tercantum pada `APP_ORIGINS`; tambahkan origin Expo web LAN jika diperlukan. Aplikasi web yang disajikan dari port 8000 memakai origin yang sama.

Untuk memakai Apache Laragon, arahkan virtual host ke **root proyek ini** dan aktifkan mod_rewrite. Build web menggunakan path root (`/_expo`); jalankan lewat virtual host atau port 8000, bukan subfolder `localhost/aplikasi ipt`. `.htaccess` mencegah akses langsung ke source backend, konfigurasi, penyimpanan, dan database lokal.

## Verifikasi

Pemeriksaan yang telah dilakukan:

- TypeScript (`npm run check`).
- Lint sintaks seluruh PHP.
- Export bundle web, Android, dan iOS.
- 43 pemeriksaan integrasi API: autentikasi, otorisasi, filter, stok, checkout, pembayaran, status, resi, pembatalan berulang, kedaluwarsa, laporan, feature flag, dan verifikasi usia.
- HTTP 200 untuk halaman, bundle, logo, dan font lokal.

Pengujian API terisolasi memakai database **ipt_store_test** dan server port 8001. Untuk mengulang, nyalakan MySQL, lalu di terminal pertama:

```powershell
$env:DB_DATABASE='ipt_store_test'
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' backend/database/setup.php
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' -S 127.0.0.1:8001 -t backend/public backend/router.php
```

Di terminal kedua:

```powershell
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' backend/tests/integration.php
```

Tes memakai data fiktif, rekening bukan rekening pembayaran, dan logo sebagai gambar uji. Tidak mengubah data aplikasi utama. Jangan jalankan pada database produksi.

Pratinjau visual otomatis terhalang karena alat browser tidak dapat memverifikasi URL aktif. Tampilan pada perangkat fisik, performa 4G, dan target Android/iOS minimum belum terverifikasi. Bundle berhasil bukan berarti APK/IPA sudah ditandatangani atau dipublikasikan.

## Batas MVP

Lupa password via email, payment gateway, integrasi ongkir otomatis, push notification, wishlist, dan ulasan mengikuti fase berikutnya dalam PRD. Pengiriman saat ini tarif flat; status dan resi dimasukkan admin. Masa berlaku pesanan diperiksa pada permintaan API; untuk operasi produksi tambahkan penjadwal yang menjalankan `expireOrders()` secara rutin.

Sebelum rilis publik, perusahaan perlu menyelesaikan peninjauan kategori TEREA, prosedur dan retensi dokumen identitas, HTTPS, backup database/kunci, rekening asli, serta build dan uji perangkat. Tidak ada publikasi atau pengiriman ke App Store/Play Store dalam pekerjaan lokal ini.
