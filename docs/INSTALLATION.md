# Panduan Instalasi

Panduan ini menjelaskan cara menginstal dan menyiapkan aplikasi Sistem Manajemen Toko Online.

## Prasyarat

- XAMPP/WAMP/LAMP server dengan PHP 7.4+ dan MySQL 5.7+
- Akses ke shell untuk menjalankan perintah
- Editor teks untuk mengedit file konfigurasi

## Langkah-langkah Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/username/repo.git
cd repo
```

### 2. Setup Web Server

- Copy project ke folder htdocs (XAMPP) atau www (WAMP)
- Contoh: `D:\Programs\Xampp\htdocs\Bagas`

### 3. Konfigurasi Database

**a. Buat Database Baru**
```sql
CREATE DATABASE bagas_db;
```

**b. Import Database Schema**
```bash
mysql -u root -p bagas_db < create_cart_table.sql
```

**c. Konfigurasi Koneksi Database**
Edit file `config/database.php`:
```php
$host = 'localhost';
$dbname = 'bagas_db';
$username = 'root';
$password = 'your-password';
```

### 4. Pengaturan PHP

**Pastikan ekstensi PHP berikut diaktifkan:**
- `pdo_mysql`
- `mysqli`
- `session`
- `json`

**Untuk XAMPP, edit `php/php.ini`:**
```ini
extension=pdo_mysql
extension=mysqli
```

### 5. Pengaturan Permission

```bash
# Linux/Mac
chmod -R 755 /path/to/project
chown -R www-data:www-data /path/to/project

# Windows (tidak diperlukan untuk XAMPP)
```

### 6. Test Instalasi

1. Start Apache dan MySQL service
2. Buka browser: `http://localhost/Bagas`
3. Buat akun admin pertama melalui halaman register

## Konfigurasi Tambahan

### Pengaturan Email (Opsional)

Untuk fitur reset password, edit `config/email.php`:
```php
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'your-email@gmail.com';
$smtp_password = 'your-app-password';
```

### Pengaturan Upload (Opsional)

Untuk upload gambar produk, pastikan folder upload dapat ditulis:
```bash
mkdir uploads
chmod 777 uploads
```

## Verifikasi Instalasi

### Cek Database Connection

Buat file test `test_db.php`:
```php
<?php
require_once 'config/database.php';
echo $db_connected ? 'Database Connected!' : 'Connection Failed!';
?>
```

### Cek PHP Extensions

```php
<?php
echo 'PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'OK' : 'MISSING') . "\n";
echo 'MySQLi: ' . (extension_loaded('mysqli') ? 'OK' : 'MISSING') . "\n";
?>
```

## Akun Default

**Setelah instalasi, buat akun admin pertama:**
- Username: `admin`
- Email: `admin@example.com`
- Password: `admin123` (segera ganti setelah login)
- Role: `admin`

## Troubleshooting

### Error "could not find driver"
- Pastikan ekstensi `pdo_mysql` diaktifkan di `php.ini`
- Restart Apache setelah mengubah `php.ini`

### Error "Access denied for user"
- Periksa username/password database di `config/database.php`
- Pastikan user memiliki akses ke database

### Error "Table doesn't exist"
- Jalankan ulang script SQL untuk membuat tabel
- Periksa nama database di konfigurasi

### Halaman tidak dapat diakses
- Pastikan Apache berjalan di port 80
- Periksa konfigurasi virtual host
- Periksa file .htaccess jika ada

## Support

Untuk bantuan lebih lanjut:
- Baca [FAQ](FAQ.md)
- Lihat [Troubleshooting Guide](TROUBLESHOOTING.md)
- Email: support@example.com

# Panduan Instalasi Aplikasi

## Persyaratan Sistem

1. Web Server (Apache/Nginx)
2. PHP 7.4 atau lebih tinggi
3. MySQL 5.7 atau lebih tinggi
4. Web Browser modern (Chrome, Firefox, Safari, Edge)

## Langkah-langkah Instalasi

### 1. Persiapan Database

1. Buat database baru di MySQL
2. Import file SQL yang tersedia di folder `database/schema.sql`

### 2. Konfigurasi Aplikasi

1. Salin file `config/database.example.php` menjadi `config/database.php`
2. Sesuaikan konfigurasi database pada file `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nama_database');
   define('DB_USER', 'username_database');
   define('DB_PASS', 'password_database');
   ```

### 3. Pengaturan Web Server

#### Apache
1. Pastikan mod_rewrite diaktifkan
2. Atur DocumentRoot ke folder aplikasi
3. Berikan izin write pada folder yang diperlukan:
   - uploads/
   - logs/

#### Nginx
1. Gunakan konfigurasi berikut sebagai contoh:
   ```nginx
   server {
       listen 80;
       server_name domain.com;
       root /path/to/app;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include fastcgi_params;
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       }
   }
   ```

### 4. Akun Administrator Default

Setelah instalasi, Anda dapat login menggunakan akun administrator default:
- Username: admin
- Password: admin123

**PENTING:** Segera ubah password administrator setelah login pertama kali!

### 5. Pengujian Instalasi

1. Buka aplikasi melalui browser
2. Login menggunakan akun administrator
3. Periksa semua fitur berfungsi dengan baik:
   - Manajemen pengguna
   - Manajemen produk
   - Manajemen kategori
   - Transaksi
   - Laporan

## Troubleshooting

### Masalah Umum

1. **Error Database Connection**
   - Periksa konfigurasi database
   - Pastikan service MySQL berjalan
   - Periksa firewall

2. **Error Permission Denied**
   - Periksa izin folder dan file
   - Sesuaikan owner dan group

3. **Error 500 Internal Server Error**
   - Periksa error log PHP
   - Periksa error log web server
   - Aktifkan display_errors untuk debugging

### Bantuan

Jika mengalami masalah dalam instalasi, silakan:
1. Periksa file log di folder `logs/`
2. Hubungi administrator sistem
3. Buka issue di repository GitHub

## Keamanan

1. Ubah password default administrator
2. Atur permission file dan folder dengan benar
3. Aktifkan HTTPS
4. Update PHP dan MySQL secara berkala
5. Backup database secara rutin