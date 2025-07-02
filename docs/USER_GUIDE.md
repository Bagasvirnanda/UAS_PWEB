# Panduan Penggunaan Aplikasi

Panduan lengkap untuk menggunakan Sistem Manajemen Toko Online.

## Daftar Isi

1. [Login dan Registrasi](#login-dan-registrasi)
2. [Dashboard](#dashboard)
3. [Untuk User Biasa](#untuk-user-biasa)
4. [Untuk Administrator](#untuk-administrator)
5. [Tips dan Trik](#tips-dan-trik)

## Login dan Registrasi

### Registrasi Akun Baru

1. **Akses Halaman Registrasi**
   - Buka browser dan akses `http://localhost/Bagas/register.php`
   - Atau klik link "Register" di halaman login

2. **Isi Form Registrasi**
   - **Username**: Nama pengguna yang unik (minimal 3 karakter)
   - **Email**: Alamat email yang valid
   - **Password**: Password yang kuat (minimal 6 karakter)
   - **Konfirmasi Password**: Ulangi password

3. **Submit Form**
   - Klik tombol "Register"
   - Jika berhasil, akan diarahkan ke halaman login

### Login ke Sistem

1. **Akses Halaman Login**
   - Buka `http://localhost/Bagas/login.php`

2. **Masukkan Kredensial**
   - **Username/Email**: Gunakan username atau email yang sudah terdaftar
   - **Password**: Masukkan password Anda

3. **Login**
   - Klik tombol "Login"
   - Jika berhasil, akan diarahkan ke dashboard

### Logout

- Klik nama pengguna di pojok kanan atas
- Pilih "Logout" dari dropdown menu

## Dashboard

Setelah login, Anda akan melihat dashboard yang berbeda tergantung role:

### Dashboard User
- **Katalog Produk**: Link ke halaman belanja
- **Keranjang**: Akses keranjang belanja
- **Riwayat Transaksi**: Lihat pesanan yang sudah dibuat

### Dashboard Admin
- **Manajemen Pengguna**: Kelola akun user
- **Kategori**: Kelola kategori produk
- **Produk**: Kelola produk
- **Transaksi**: Kelola transaksi dan update status
- **Laporan**: Lihat laporan penjualan

## Untuk User Biasa

### Berbelanja

#### 1. Browsing Katalog Produk

1. **Akses Katalog**
   - Klik "Katalog Produk" di sidebar
   - Atau akses `http://localhost/Bagas/shop/catalog.php`

2. **Fitur Pencarian dan Filter**
   - **Search**: Ketik nama produk di kotak pencarian
   - **Kategori**: Pilih kategori tertentu dari dropdown
   - **Sorting**: Urutkan berdasarkan nama, harga, atau tanggal
   - **Order**: Pilih urutan A-Z atau Z-A

3. **Melihat Detail Produk**
   - Setiap produk menampilkan:
     - Nama produk
     - Kategori
     - Deskripsi singkat
     - Harga
     - Status stok

#### 2. Menambah ke Keranjang

1. **Pilih Jumlah**
   - Tentukan jumlah produk yang ingin dibeli
   - Pastikan tidak melebihi stok yang tersedia

2. **Tambah ke Keranjang**
   - Klik tombol "Tambah" dengan ikon keranjang
   - Produk akan ditambahkan ke keranjang

3. **Notifikasi**
   - Akan muncul notifikasi sukses jika berhasil
   - Atau notifikasi error jika ada masalah (stok habis, dll)

#### 3. Mengelola Keranjang

1. **Akses Keranjang**
   - Klik "Keranjang Belanja" di sidebar
   - Atau akses `http://localhost/Bagas/shop/cart.php`

2. **Fitur Keranjang**
   - **Lihat Item**: Semua produk yang sudah ditambahkan
   - **Update Jumlah**: Ubah quantity produk
   - **Hapus Item**: Hapus produk dari keranjang
   - **Lihat Total**: Total harga semua item

3. **Update Keranjang**
   - Ubah jumlah produk di kolom quantity
   - Klik "Perbarui Keranjang" untuk menyimpan perubahan

#### 4. Proses Checkout

1. **Akses Checkout**
   - Dari halaman keranjang, klik "Lanjut ke Checkout"
   - Atau akses `http://localhost/Bagas/shop/checkout.php`

2. **Review Pesanan**
   - **Review Item**: Pastikan semua produk benar
   - **Cek Total**: Verifikasi total harga
   - **Info Pembeli**: Lihat informasi Anda

3. **Konfirmasi Pesanan**
   - Baca kebijakan dan ketentuan
   - Klik "Proses Pesanan" untuk melanjutkan
   - Konfirmasi dengan mengklik "OK" pada dialog

4. **Pesanan Berhasil**
   - Akan diarahkan ke halaman konfirmasi
   - Simpan ID transaksi untuk referensi
   - Pesanan berstatus "Pending" menunggu konfirmasi admin

#### 5. Melihat Riwayat Transaksi

1. **Akses Riwayat**
   - Klik "Riwayat Transaksi" di sidebar
   - Atau akses `http://localhost/Bagas/shop/history.php`

2. **Informasi Transaksi**
   - **ID Transaksi**: Nomor referensi unik
   - **Tanggal**: Kapan transaksi dibuat
   - **Total Item**: Jumlah produk yang dibeli
   - **Total Harga**: Nilai transaksi
   - **Status**: Pending/Completed/Cancelled

3. **Detail Transaksi**
   - Klik tombol "Detail" untuk melihat item yang dibeli
   - Modal akan menampilkan breakdown per produk

## Untuk Administrator

### Manajemen Pengguna

#### 1. Akses Manajemen User
- Klik "Manajemen Pengguna" di sidebar admin
- Akses `http://localhost/Bagas/admin/users.php`

#### 2. Fitur yang Tersedia
- **Lihat Daftar User**: Semua pengguna terdaftar
- **Tambah User Baru**: Buat akun untuk user lain
- **Edit User**: Ubah informasi pengguna
- **Hapus User**: Hapus akun pengguna (kecuali admin)
- **Ubah Role**: Upgrade user menjadi admin

#### 3. Tambah User Baru
1. Klik tombol "Tambah Pengguna"
2. Isi form:
   - Username (unik)
   - Email (valid)
   - Password
   - Role (User/Admin)
3. Klik "Simpan"

### Manajemen Kategori

#### 1. Akses Kategori
- Klik "Kategori" di sidebar admin
- Akses `http://localhost/Bagas/admin/categories.php`

#### 2. Kelola Kategori
- **Tambah Kategori**: Buat kategori produk baru
- **Edit Kategori**: Ubah nama dan deskripsi
- **Hapus Kategori**: Hapus kategori (jika tidak memiliki produk)

#### 3. Tips Kategori
- Gunakan nama yang jelas dan mudah dipahami
- Berikan deskripsi yang membantu user memahami kategori
- Tidak bisa menghapus kategori yang masih memiliki produk

### Manajemen Produk

#### 1. Akses Produk
- Klik "Produk" di sidebar admin
- Akses `http://localhost/Bagas/admin/products.php`

#### 2. Fitur Produk
- **Filter dan Pencarian**: Cari produk berdasarkan nama atau kategori
- **Tambah Produk**: Buat produk baru
- **Edit Produk**: Ubah informasi produk
- **Hapus Produk**: Hapus produk (jika tidak ada dalam transaksi)

#### 3. Tambah/Edit Produk
1. Isi informasi:
   - **Nama Produk**: Nama yang jelas
   - **Kategori**: Pilih dari kategori yang ada
   - **Deskripsi**: Penjelasan detail produk
   - **Harga**: Dalam rupiah
   - **Stok**: Jumlah tersedia
2. Klik "Simpan"

#### 4. Manajemen Stok
- Stok akan berkurang otomatis saat ada pembelian
- Edit stok manual jika ada perubahan fisik
- Produk dengan stok 0 tidak akan muncul di katalog user

### Manajemen Transaksi

#### 1. Akses Transaksi
- Klik "Transaksi" di sidebar admin
- Akses `http://localhost/Bagas/admin/transactions.php`

#### 2. Fitur Transaksi
- **Filter**: Berdasarkan status, tanggal, atau user
- **Lihat Detail**: Detail item dalam transaksi
- **Update Status**: Ubah status transaksi

#### 3. Status Transaksi
- **Pending**: Menunggu konfirmasi admin
- **Completed**: Transaksi selesai/disetujui
- **Cancelled**: Transaksi dibatalkan

#### 4. Proses Transaksi
1. **Review Transaksi Pending**
   - Periksa detail pesanan
   - Pastikan stok tersedia
   - Verifikasi informasi pembeli

2. **Update Status**
   - Untuk transaksi pending, klik tombol status
   - Pilih "Completed" untuk menyetujui
   - Pilih "Cancelled" untuk membatalkan

### Laporan dan Analitik

#### 1. Akses Laporan
- Klik "Laporan" di sidebar admin
- Akses `http://localhost/Bagas/admin/reports.php`

#### 2. Jenis Laporan
- **Laporan Penjualan**: Total penjualan per periode
- **Produk Terlaris**: Produk dengan penjualan terbanyak
- **Analisis User**: Aktivitas pengguna
- **Laporan Stok**: Status stok produk

## Tips dan Trik

### Untuk User

1. **Optimasi Pencarian**
   - Gunakan kata kunci spesifik
   - Manfaatkan filter kategori
   - Coba berbagai urutan sorting

2. **Mengelola Keranjang**
   - Cek keranjang secara berkala
   - Item di keranjang akan tersimpan selama session
   - Pastikan stok masih tersedia sebelum checkout

3. **Tracking Pesanan**
   - Catat ID transaksi untuk referensi
   - Cek status pesanan di riwayat transaksi
   - Hubungi admin jika ada pertanyaan

### Untuk Admin

1. **Manajemen Stok**
   - Pantau stok secara berkala
   - Update stok saat ada perubahan fisik
   - Set notifikasi untuk stok menipis

2. **Proses Transaksi**
   - Review transaksi pending setiap hari
   - Konfirmasi atau batalkan dalam waktu wajar
   - Komunikasi dengan user jika ada masalah

3. **Analisis Data**
   - Gunakan laporan untuk insight bisnis
   - Identifikasi produk terlaris
   - Analisis tren penjualan

### Keamanan

1. **Password**
   - Gunakan password yang kuat
   - Ganti password secara berkala
   - Jangan bagikan kredensial

2. **Session**
   - Logout setelah selesai menggunakan
   - Jangan tinggalkan sistem terbuka
   - Gunakan browser yang aman

3. **Data**
   - Backup data secara berkala
   - Hati-hati saat menghapus data
   - Verifikasi sebelum melakukan perubahan besar

## FAQ

### Q: Bagaimana cara reset password?
A: Saat ini belum ada fitur reset password otomatis. Hubungi administrator untuk reset password.

### Q: Kenapa produk tidak muncul di katalog?
A: Produk tidak akan muncul jika stoknya 0 atau sudah dihapus oleh admin.

### Q: Berapa lama transaksi diproses?
A: Transaksi biasanya diproses dalam 1-2 hari kerja oleh admin.

### Q: Bisakah membatalkan pesanan?
A: Pesanan dengan status "Pending" masih bisa dibatalkan. Hubungi admin.

### Q: Bagaimana cara mengubah informasi profil?
A: Klik nama pengguna di pojok kanan atas, lalu pilih "Profil".

## Kontak Support

Jika mengalami masalah atau membutuhkan bantuan:
- Email: support@example.com
- Website: https://example.com/support
- Dokumentasi: [docs/](./)
