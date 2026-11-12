# PRD — Aplikasi E-Commerce Indonesia Pacific Trade

| Item | Keterangan |
|---|---|
| Nama Produk | IPT Store (nama sementara) |
| Perusahaan | Indonesia Pacific Trade |
| Platform | Mobile (Android & iOS) via React Native + Expo |
| Backend | PHP REST API (Laragon) |
| Database | MySQL / MariaDB (phpMyAdmin via Laragon) |
| Testing | Expo Go di HP fisik |
| Versi Dokumen | 0.1 (Draft) |
| Tanggal | 12 November 2026 |

---

## 1. Latar Belakang & Tujuan

Indonesia Pacific Trade menjual beberapa lini produk: kamera instan dan film (Instax Mini, Instax Paper), produk komputasi (Nvidia), dan produk tembakau panas (IQOS TEREA). Saat ini belum ada kanal penjualan digital berbasis aplikasi mobile yang terpusat.

**Tujuan:**
1. Menyediakan aplikasi katalog dan pembelian produk yang mudah dipakai pelanggan.
2. Memberi admin dashboard sederhana untuk mengelola produk, stok, dan pesanan.
3. Membangun fondasi yang bisa dikembangkan (payment gateway, pengiriman otomatis, dll.).

**Metrik keberhasilan (MVP):**
- Pengguna bisa registrasi, browsing, checkout, dan melihat riwayat pesanan tanpa bantuan.
- Admin bisa memperbarui stok dan status pesanan dalam kurang dari 1 menit.
- Waktu muat halaman daftar produk < 3 detik di jaringan 4G.

---

## 2. Asumsi & Catatan Penting

1. **"Instax Paper"** diasumsikan sebagai film/kertas foto Instax Mini.
2. **"Nvidia"** diasumsikan sebagai produk kartu grafis (GPU) / hardware Nvidia. *Perlu dikonfirmasi.*
3. **React Native tidak bisa terhubung langsung ke MySQL.** Aplikasi harus memanggil **REST API PHP** yang berjalan di Laragon, dan API itulah yang mengakses database.
4. Saat memakai **Expo Go di HP**, jangan gunakan `localhost`. Gunakan IP LAN laptop (contoh `http://192.168.1.10/ipt-api`), dan HP serta laptop harus berada di Wi-Fi yang sama.
5. Pembayaran pada MVP memakai **transfer bank manual + upload bukti bayar** (payment gateway masuk fase berikutnya).

### ⚠️ Kepatuhan Regulasi — Produk TEREA (Tembakau)

Produk tembakau (termasuk tembakau yang dipanaskan) diatur ketat di Indonesia (antara lain PP No. 28 Tahun 2024). Ketentuan yang perlu **diverifikasi dengan konsultan hukum perusahaan** sebelum rilis publik:
- Batas usia pembeli (aturan terbaru menyebut minimal 21 tahun).
- Pembatasan/larangan penjualan melalui platform atau aplikasi elektronik komersial.
- Pembatasan iklan, promosi, dan tampilan produk tembakau.
- Peringatan kesehatan dan ketentuan pita cukai.

**Mitigasi di desain:** verifikasi usia (tanggal lahir + verifikasi KTP), modul TEREA dapat dinonaktifkan lewat *feature flag* admin tanpa merilis ulang aplikasi, tidak ada diskon/promosi agresif pada kategori ini, dan peringatan kesehatan ditampilkan pada halaman produk.

---

## 3. Target Pengguna

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| Pelanggan | Individu dewasa yang membeli produk | Cari produk, lihat harga/stok, checkout cepat, lacak pesanan |
| Admin | Staf Indonesia Pacific Trade | Kelola produk, stok, pesanan, dan pengguna |

---

## 4. Ruang Lingkup

### 4.1 In Scope (MVP)
- Registrasi, login, logout, profil
- Verifikasi usia untuk kategori TEREA
- Katalog produk per kategori, pencarian, filter, detail produk
- Keranjang belanja
- Checkout (alamat, metode kirim, pembayaran transfer manual)
- Riwayat dan status pesanan
- Panel admin (web sederhana PHP) untuk CRUD produk, stok, pesanan

### 4.2 Out of Scope (Fase Berikutnya)
- Payment gateway (Midtrans/Xendit)
- Integrasi ongkir otomatis (RajaOngkir/Biteship)
- Push notification, wishlist, review & rating
- Voucher/promo, program loyalitas
- Aplikasi admin native

---

## 5. Kategori & Produk

| Kategori | Contoh Produk | Catatan |
|---|---|---|
| Instax Mini | Kamera Instax Mini 11/12/Evo, dll. | Varian warna |
| Instax Paper | Film Instax Mini (isi 10/20/40) | Varian motif/frame |
| Nvidia | GPU GeForce / produk Nvidia lainnya | Spesifikasi teknis & garansi |
| TEREA (IQOS) | Stik TEREA berbagai varian rasa | **Age-gated**, 21+, lihat bagian 2 |

---

## 6. Kebutuhan Fungsional

### 6.1 Autentikasi & Akun
- FR-01: Registrasi dengan nama, email, no. HP, password, tanggal lahir.
- FR-02: Login dengan email + password (token JWT).
- FR-03: Lupa password via email (fase 2 jika SMTP belum siap).
- FR-04: Edit profil dan kelola beberapa alamat pengiriman.

### 6.2 Verifikasi Usia (TEREA)
- FR-05: Kategori TEREA tersembunyi/terkunci bagi pengguna yang belum lolos verifikasi usia.
- FR-06: Pengguna mengunggah foto KTP; admin menyetujui/menolak.
- FR-07: Status verifikasi: `belum`, `menunggu`, `disetujui`, `ditolak`.
- FR-08: Admin dapat menonaktifkan seluruh kategori TEREA melalui feature flag.

### 6.3 Katalog
- FR-09: Beranda berisi banner, kategori, dan produk unggulan.
- FR-10: Daftar produk dengan pencarian, filter (kategori, harga), dan urutan (terbaru, harga).
- FR-11: Detail produk: galeri foto, deskripsi, harga, stok, varian.

### 6.4 Keranjang & Checkout
- FR-12: Tambah, ubah jumlah, hapus item di keranjang (validasi stok).
- FR-13: Checkout: pilih alamat, kurir/ongkir flat, ringkasan biaya.
- FR-14: Pembayaran transfer manual: tampilkan rekening perusahaan, unggah bukti bayar.
- FR-15: Pesanan dibuat dengan nomor unik (mis. `IPT-20261112-0001`).
- FR-16: Stok berkurang saat pesanan dibuat dan dikembalikan jika dibatalkan/kedaluwarsa.

### 6.5 Pesanan
- FR-17: Riwayat pesanan dan detail pesanan.
- FR-18: Status: `menunggu_pembayaran` → `menunggu_verifikasi` → `diproses` → `dikirim` → `selesai` / `dibatalkan`.
- FR-19: Pelanggan dapat membatalkan pesanan selama belum diproses.
- FR-20: Input nomor resi oleh admin, tampil di detail pesanan.

### 6.6 Panel Admin (Web)
- FR-21: Login admin dengan role-based access (`admin`, `staff`).
- FR-22: CRUD kategori, produk, varian, foto produk.
- FR-23: Kelola stok dan lihat peringatan stok menipis.
- FR-24: Verifikasi pembayaran, ubah status pesanan, input resi.
- FR-25: Verifikasi usia pengguna.
- FR-26: Laporan penjualan sederhana (harian/bulanan, ekspor CSV).

---

## 7. Kebutuhan Non-Fungsional

| Aspek | Target |
|---|---|
| Performa | Respons API < 500 ms (lokal), list produk dengan paginasi |
| Keamanan | Password di-hash (bcrypt), JWT, prepared statements (anti SQL injection), validasi input, HTTPS saat produksi |
| Privasi | Foto KTP disimpan terbatas akses, sesuai UU PDP |
| Kompatibilitas | Android 8+, iOS 14+ |
| Skalabilitas | API stateless sehingga mudah dipindah ke hosting |
| Bahasa | Bahasa Indonesia, mata uang Rupiah (IDR) |

---

## 8. Arsitektur Teknis

```
[Expo Go / React Native]  --HTTP(JSON)-->  [PHP REST API (Laragon/Apache)]  -->  [MySQL (phpMyAdmin)]
                                                      |
                                                      +--> storage/uploads (gambar produk, bukti bayar, KTP)
```

### 8.1 Tech Stack
- **Mobile:** React Native + Expo (SDK terbaru), React Navigation, Axios, Context API/Zustand, `expo-secure-store` (token), `expo-image-picker`.
- **Backend:** PHP 8.x. Disarankan **Laravel** (Sanctum/JWT, Eloquent, validation) agar lebih cepat dan aman; alternatif PHP native dengan struktur MVC sederhana.
- **Database:** MySQL via Laragon, dikelola dengan phpMyAdmin.
- **Admin Panel:** Blade (Laravel) atau PHP + Bootstrap.

### 8.2 Struktur Folder (Usulan)

```
ipt-store/
├── mobile/            # Proyek Expo
│   ├── src/
│   │   ├── api/       # axios client & service
│   │   ├── screens/
│   │   ├── components/
│   │   ├── navigation/
│   │   ├── context/
│   │   └── utils/
│   └── app.json
└── backend/           # Proyek PHP (di C:\laragon\www\ipt-api)
    ├── app/ routes/ database/ public/
    └── storage/
```

### 8.3 Setup Lokal
1. Jalankan Laragon (Apache + MySQL), buat database `ipt_store` lewat phpMyAdmin.
2. Cari IP LAN laptop (`ipconfig`), set di `.env` mobile: `API_URL=http://<IP-LAN>/ipt-api/api`.
3. Izinkan Apache lewat Windows Firewall.
4. Jalankan `npx expo start`, scan QR dengan Expo Go (HP satu jaringan Wi-Fi).

---

## 9. Rancangan Database (MySQL)

| Tabel | Kolom Utama |
|---|---|
| `users` | id, name, email, phone, password, birth_date, role (`customer`/`admin`/`staff`), age_status, ktp_image, created_at |
| `addresses` | id, user_id, label, recipient, phone, address, city, province, postal_code, is_default |
| `categories` | id, name, slug, is_age_restricted, is_active |
| `products` | id, category_id, name, slug, description, price, stock, weight_gram, is_active, created_at |
| `product_variants` | id, product_id, name (warna/rasa/isi), sku, price_override, stock |
| `product_images` | id, product_id, image_url, sort_order |
| `carts` | id, user_id, updated_at |
| `cart_items` | id, cart_id, product_id, variant_id, qty |
| `orders` | id, order_number, user_id, address_snapshot, subtotal, shipping_cost, total, status, payment_proof, courier, tracking_number, created_at |
| `order_items` | id, order_id, product_id, variant_id, name_snapshot, price, qty |
| `payments` | id, order_id, method, amount, proof_image, status, verified_by, verified_at |
| `banners` | id, image_url, link, is_active |
| `settings` | key, value (mis. `terea_enabled`, rekening bank) |

Relasi utama: `categories 1—N products`, `products 1—N variants/images`, `users 1—N orders`, `orders 1—N order_items`.

---

## 10. Rancangan API (Ringkas)

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/auth/register` | Registrasi |
| POST | `/auth/login` | Login, return token |
| GET | `/me` | Profil pengguna |
| POST | `/me/age-verification` | Unggah KTP |
| GET/POST/PUT/DELETE | `/addresses` | Kelola alamat |
| GET | `/categories` | Daftar kategori |
| GET | `/products?category=&q=&sort=&page=` | Daftar produk |
| GET | `/products/{id}` | Detail produk |
| GET/POST/PUT/DELETE | `/cart` | Keranjang |
| POST | `/orders` | Checkout |
| GET | `/orders` / `/orders/{id}` | Riwayat & detail |
| POST | `/orders/{id}/payment-proof` | Unggah bukti bayar |
| POST | `/orders/{id}/cancel` | Batalkan pesanan |
| — | `/admin/*` | Endpoint admin (CRUD, verifikasi, laporan) |

Format respons: `{ "success": true, "data": {...}, "message": "..." }`.

---

## 11. Daftar Layar (Mobile)

1. Splash & Onboarding
2. Login / Registrasi
3. Beranda (banner, kategori, unggulan)
4. Daftar Produk (filter & pencarian)
5. Detail Produk
6. Gerbang Verifikasi Usia (untuk TEREA)
7. Keranjang
8. Checkout (alamat, pengiriman, ringkasan)
9. Instruksi Pembayaran & Unggah Bukti
10. Riwayat Pesanan & Detail Pesanan
11. Profil, Alamat, Pengaturan

**Alur utama:** Beranda → Produk → Detail → Keranjang → Checkout → Bayar → Pantau Pesanan.

---

## 12. Panduan UI/UX

- Desain bersih, minimalis, berfokus pada gambar produk.
- Warna dasar mengikuti identitas Indonesia Pacific Trade (logo/warna perlu disediakan).
- Tombol aksi utama selalu jelas ("Tambah ke Keranjang", "Checkout").
- Tampilkan state loading, kosong, dan error di setiap layar.
- Halaman TEREA menampilkan peringatan kesehatan dan penanda 21+.

---

## 13. Rencana Rilis (Milestone)

| Fase | Cakupan | Estimasi |
|---|---|---|
| 0 | Finalisasi PRD, desain wireframe, setup Laragon & Expo | 1 minggu |
| 1 | Database, API auth, produk & kategori | 1–2 minggu |
| 2 | UI mobile: auth, beranda, katalog, detail | 2 minggu |
| 3 | Keranjang, checkout, pesanan, upload bukti bayar | 2 minggu |
| 4 | Panel admin + verifikasi usia | 2 minggu |
| 5 | Testing, perbaikan bug, review kepatuhan hukum | 1 minggu |
| 6 | Deploy ke hosting/VPS, build APK/IPA (EAS) | 1 minggu |

---

## 14. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Regulasi produk tembakau & penjualan online | Tinggi | Konsultasi hukum, feature flag TEREA, verifikasi usia |
| Kebijakan App Store/Play Store terhadap produk tembakau | Tinggi | Cek kebijakan, siapkan distribusi tanpa kategori TEREA atau via APK langsung |
| HP tidak bisa konek ke API lokal | Sedang | Gunakan IP LAN, satu Wi-Fi, buka firewall |
| Stok tidak sinkron (race condition) | Sedang | Transaksi database + penguncian saat checkout |
| Kebocoran data KTP | Tinggi | Akses terbatas, enkripsi penyimpanan, kebijakan retensi |

---

## 15. Pertanyaan Terbuka

1. Apakah "Nvidia" yang dimaksud GPU/hardware, atau produk lain?
2. Apakah TEREA akan benar-benar dijual lewat aplikasi setelah tinjauan hukum?
3. Backend: Laravel atau PHP native?
4. Apakah perlu dukungan pembayaran digital (QRIS, e-wallet, VA) sejak awal?
5. Apakah ada logo, warna brand, dan aset foto produk dari perusahaan?
6. Pengiriman: ongkir flat, per wilayah, atau integrasi kurir?
7. Apakah aplikasi akan dipublikasikan ke Play Store/App Store atau hanya internal?

---

## 16. Lampiran — Langkah Selanjutnya

1. Review dan setujui PRD ini.
2. Buat wireframe layar utama.
3. Buat skema SQL (`ipt_store.sql`) untuk diimpor ke phpMyAdmin.
4. Scaffold proyek backend dan Expo.
