<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## CorpLogistics — Catatan Keamanan & Operasional

Aplikasi internal MVPWarehouse (Laravel 12 + SQLite) untuk workflow permintaan barang (gudang → HR → belanja). Catatan berikut penting untuk deployment yang aman.

### Konfigurasi produksi
- Pastikan `APP_DEBUG=false` di lingkungan produksi. `APP_DEBUG=true` (default lokal) akan menampilkan stack trace dan detail sensitif bila terjadi error.
- Aktifkan HTTPS dan set `SESSION_SECURE_COOKIE=true` agar cookie session hanya dikirim melalui koneksi aman.
- `DB_CONNECTION` di aplikasi ini memakai **SQLite**; modul backup/restore (`storage/app/backups`) berasumsi database berbasis file SQLite.

### Attachment (lampiran permintaan)
- Untuk V1, lampiran disimpan di **disk `public`** (`storage/app/public/attachments`) sehingga dapat diakses langsung melalui URL `/storage/attachments/...` **tanpa autentikasi** oleh siapa pun yang mengetahui URL-nya.
- Nama file dibuat acak oleh Laravel, sehingga URL tidak mudah ditebak — tetapi ini **bukan** proteksi akses yang sesungguhnya.
- Keterbatasan ini diterima untuk V1. Untuk V2, pindahkan lampiran ke disk privat (`local`) dan sajikan lewat route terkontrol (hanya pemohon dan HR/admin yang boleh mengakses).

### Developer Mode
- Fitur `dev_mode` (toggle di dashboard admin) membuka seluruh pembatasan peran untuk keperluan **testing lokal**.
- Sejak versi review-fix, dev-mode **tidak berfungsi di lingkungan produksi** (`app()->isProduction()`), sehingga tidak dapat dipakai untuk melewati otorisasi role di produksi.

### Password sementara
- Saat admin membuat akun tanpa password atau me-reset password, sistem membuat **password acak** yang hanya ditampilkan **sekali** melalui flash message, lalu `must_change_password` diaktifkan sehingga pengguna wajib mengganti password saat login berikutnya.
- Password tidak pernah ditulis ke audit log.

### Keamanan dasar lain
- Semua aksi state-mengubah dilindungi CSRF; login dibatasi `throttle:5,1`.
- Header keamanan (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`) diterapkan global. CSP tidak digunakan karena template Blade memakai inline script/style.
- Otorisasi role (`gudang`/`hr`/`admin`) ditegakkan di sisi server via middleware.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
