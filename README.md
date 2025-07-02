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
## Recent Updates (July 2025)

### 🎉 Major Database and CRUD Improvements

#### Fixed Issues:
- ✅ **CASCADE DELETE Implementation**: Resolved foreign key constraint issues that prevented deletion of products, users, and categories
- ✅ **Database Connection**: Fixed database connection issues and implemented proper MySQL user authentication
- ✅ **Dashboard Data**: Fixed admin dashboard not showing transaction and report data
- ✅ **Query Corrections**: Fixed `transaction_date` column references throughout the application

#### Database Schema Enhancements:
- **Foreign Key Constraints**: Properly implemented CASCADE DELETE and SET NULL constraints
- **New Tables**: Added `user_activity_logs` table for better user tracking
- **Data Integrity**: Enhanced relationships between tables for better data consistency

#### CRUD Operations:
- **Products**: Can now be deleted even if used in transactions (CASCADE DELETE)
- **Users**: Deletion automatically removes all related transactions, cart items, and activity logs
- **Categories**: Deletion sets product category_id to NULL (preserves products)

#### Security Improvements:
- **Database User**: Changed from root to dedicated `bagas_user` for better security
- **Credential Management**: Improved database credential handling

### Installation & Setup

#### Prerequisites:
- PHP 8.4+
- MySQL 8.0+
- Nginx/Apache
- SSL Certificate (recommended)

#### Quick Setup:
```bash
# 1. Clone repository
git clone https://github.com/VernSG/UAS_PWEB.git
cd UAS_PWEB

# 2. Setup database
mysql -u root -p < database/setup_database.sql

# 3. Create database user
mysql -u root -p -e "
CREATE USER 'bagas_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON bagas_db.* TO 'bagas_user'@'localhost';
FLUSH PRIVILEGES;"

# 4. Update database configuration
# Edit config/database.php with your credentials

# 5. Set permissions
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

#### Default Login:
- **Username**: `admin`
- **Password**: `admin123`
- **URL**: `https://yourdomain.com`

### Testing CASCADE DELETE:
The application now supports proper CASCADE DELETE operations:

1. **Delete Product**: Automatically removes related transaction_items and cart entries
2. **Delete User**: Automatically removes user's transactions, cart, and activity logs
3. **Delete Category**: Sets products' category_id to NULL (products remain)

### Live Demo:
- **URL**: https://bagas.mangaverse.my.id
- **Admin Panel**: Login with admin/admin123

---

For technical support or questions about the recent updates, please create an issue on GitHub.
