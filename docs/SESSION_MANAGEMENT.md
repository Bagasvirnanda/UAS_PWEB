# Session Management

Dokumentasi ini menjelaskan sistem manajemen session yang digunakan dalam aplikasi.

## Overview

Aplikasi menggunakan session PHP dengan konfigurasi khusus untuk keamanan dan performa yang optimal.

## Konfigurasi Session

### File: includes/session_manager.php

```php
// Konfigurasi session yang diterapkan:
ini_set('session.cookie_httponly', 1);  // Cookie hanya HTTP, tidak JavaScript
ini_set('session.use_only_cookies', 1); // Hanya gunakan cookie untuk session ID
ini_set('session.cookie_secure', 0);    // Set 1 untuk HTTPS
ini_set('session.gc_maxlifetime', 1440); // Session timeout (24 menit)
```

## Fitur Session Manager

### 1. Inisialisasi Session
- Memulai session dengan konfigurasi keamanan
- Melakukan regenerasi session ID untuk keamanan
- Menyimpan timestamp login

### 2. Validasi Session
- Memeriksa apakah user sudah login
- Memvalidasi role pengguna (admin/user)
- Mengarahkan ke halaman login jika session tidak valid

### 3. Session Security
- Regenerasi session ID setiap login
- HTTP-only cookies untuk mencegah XSS
- Session timeout otomatis

### 4. Logout Management
- Pembersihan semua data session
- Penghapusan session cookie
- Pengalihan ke halaman login

## Implementasi dalam Aplikasi

### Login Process
```php
// File: auth/auth.php
if (login_user($username, $password)) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
}
```

### Session Validation
```php
// Setiap halaman yang memerlukan login
require_once '../includes/session_manager.php';
if (!is_logged_in()) {
    redirect_to_login();
}
```

### Role-based Access
```php
// Halaman admin
if (!is_admin()) {
    die('Access denied');
}
```

## Session Variables

### User Session Data
- `$_SESSION['user_id']` - ID pengguna dalam database
- `$_SESSION['username']` - Username pengguna
- `$_SESSION['role']` - Role pengguna (admin/user)
- `$_SESSION['login_time']` - Timestamp login

### Temporary Data
- `$_SESSION['flash_message']` - Pesan sementara (success/error)
- `$_SESSION['redirect_after_login']` - URL redirect setelah login

## Security Features

### 1. Session Fixation Protection
- Regenerasi session ID saat login
- Invalidasi session lama

### 2. Session Hijacking Protection
- HTTP-only cookies
- Secure cookies (untuk HTTPS)
- User agent validation (opsional)

### 3. Session Timeout
- Timeout otomatis setelah 24 menit tidak aktif
- Logout paksa saat timeout

## Best Practices

### 1. Untuk Developer
- Selalu gunakan `session_manager.php` untuk validasi
- Jangan simpan data sensitif dalam session
- Lakukan logout proper saat user keluar

### 2. Untuk Security
- Aktifkan secure cookies untuk production (HTTPS)
- Set session timeout sesuai kebutuhan
- Monitor session untuk aktivitas mencurigakan

### 3. Untuk Performance
- Bersihkan session data yang tidak diperlukan
- Gunakan session storage yang tepat untuk aplikasi besar

## Troubleshooting

### Session Not Working
1. Pastikan `session_start()` dipanggil sebelum output
2. Periksa permission folder session
3. Periksa konfigurasi PHP session

### Session Timeout Too Fast
1. Periksa `session.gc_maxlifetime`
2. Periksa `session.cookie_lifetime`
3. Sesuaikan timeout di `session_manager.php`

### Security Issues
1. Aktifkan `session.cookie_secure` untuk HTTPS
2. Set `session.cookie_samesite` untuk CSRF protection
3. Implementasi additional validation jika diperlukan

## Migration Notes

Jika melakukan upgrade dari sistem session lama:

1. Backup session data existing
2. Update kode untuk menggunakan `session_manager.php`
3. Test semua fitur login/logout
4. Update dokumentasi user jika ada perubahan behavior
