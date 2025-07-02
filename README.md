# Sistem Manajemen Toko Online

Aplikasi web full-stack untuk toko online dengan interface admin yang lengkap dan sistem belanja yang user-friendly. Menyediakan manajemen produk, transaksi, laporan, serta pengalaman berbelanja yang terintegrasi.

## Fitur Utama

### Sistem Autentikasi
- Login dengan username/email dan password
- Proteksi halaman berdasarkan role pengguna
- Fitur reset password
- Logout otomatis untuk keamanan

### Manajemen Pengguna
- Pendaftaran pengguna baru
- Manajemen akun (admin/user)
- Edit profil dan password
- Log aktivitas pengguna

### Manajemen Produk
- Kategori produk
- Detail produk (nama, deskripsi, harga, stok)
- Pencarian dan filter produk
- Manajemen stok otomatis

### Sistem Transaksi
- Keranjang belanja
- Proses checkout
- Status transaksi (pending, completed, cancelled)
- Riwayat transaksi

### Laporan dan Analitik
- Laporan penjualan
- Statistik transaksi
- Produk terlaris
- Grafik pendapatan

### Notifikasi
- Notifikasi stok menipis
- Notifikasi transaksi baru
- Notifikasi status transaksi

### Pengaturan Sistem
- Konfigurasi umum
- Pengaturan notifikasi
- Manajemen backup
- Log audit sistem

## Teknologi

### Backend
- PHP 7.4+
- MySQL 5.7+
- PDO untuk database connection
- Session based authentication

### Frontend
- HTML5
- CSS3 (Bootstrap 5)
- JavaScript
- Chart.js untuk visualisasi data

## Instalasi

Lihat [Panduan Instalasi](docs/INSTALLATION.md) untuk petunjuk detail tentang cara menginstal dan mengkonfigurasi aplikasi.

## Struktur Database

### Tabel Utama
- users: Manajemen pengguna
- categories: Kategori produk
- products: Data produk
- transactions: Transaksi
- transaction_items: Detail item transaksi

### Tabel Pendukung
- user_activity_logs: Log aktivitas pengguna
- system_settings: Pengaturan sistem
- reports: Data laporan
- notifications: Notifikasi sistem
- password_resets: Token reset password
- audit_logs: Log audit sistem

## Keamanan

- Password hashing
- Validasi input
- Proteksi SQL injection
- CSRF protection
- XSS prevention
- Session security

## Kontribusi

1. Fork repository
2. Buat branch fitur (`git checkout -b fitur-baru`)
3. Commit perubahan (`git commit -am 'Menambah fitur baru'`)
4. Push ke branch (`git push origin fitur-baru`)
5. Buat Pull Request

## Lisensi

Dirillis di bawah [Lisensi MIT](LICENSE)

## Kontak

Untuk pertanyaan dan dukungan, silakan hubungi:
- Email: admin@example.com
- Website: https://example.com
- GitHub: https://github.com/username/repo