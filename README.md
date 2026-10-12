# 🎓 NexusCampus Portal — Panduan Instalasi & Menjalankan

Portal Akademik Kampus Fiktif bergaya **Cyberpunk Academic Dark Mode** berbasis **PHP Native (PDO) & MySQL**.

---

## ⚡ Fitur Utama

- **Role Mahasiswa**:
  - Dashboard statistik & IPK
  - Transkrip nilai interaktif & visualisasi bar
  - Unggah tugas kuliah (Drag & Drop, multi-format, deadline countdown)
  - Forum diskusi interaktif & tanya jawab
  - Notifikasi real-time & badge unread
  - Pengaturan profil & ganti password

- **Role Dosen**:
  - Input nilai otomatis hitung bobot (Tugas 30%, UTS 30%, UAS 40%) & live Grade Preview
  - Buat penugasan baru dengan batas deadline & skor maksimal
  - Review pengumpulan tugas mahasiswa & unduh file serta beri nilai + feedback
  - Kelola kelas & interaksi forum

- **Role Admin**:
  - Manajemen akun pengguna (Mahasiswa, Dosen, Admin)
  - Buat akun baru, aktifkan/nonaktifkan status akun, hapus akun
  - Kelola kurikulum mata kuliah & plot dosen pengampu
  - Laporan & statistik akademik kampus
  - Broadcast notifikasi ke seluruh kampus atau role tertentu

---

## 🚀 Cara Menjalankan (XAMPP / Laragon / PHP Server)

### 1. Database MySQL
1. Buka **phpMyAdmin** atau MySQL CLI.
2. Buat database baru bernama `nexus_campus` (atau import langsung file `database/schema.sql`).
3. Import file:
   ```
   database/schema.sql
   ```
   *Seluruh struktur tabel dan data sintetis (akun demo, matkul, nilai, tugas, forum) otomatis terisi.*

### 2. Konfigurasi Database (Jika Diperlukan)
Buka file `includes/config.php` dan sesuaikan username/password database MySQL Anda:
```php
define('DB_HOST',   'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');          // isi password database jika ada
define('DB_NAME',   'nexus_campus');
```

### 3. Jalankan Web Server
- **Jika menggunakan XAMPP**: Letakkan folder `kampus-portal` di dalam folder `htdocs`, lalu buka browser:
  `http://localhost/kampus-portal`
- **Jika menggunakan PHP Built-in Server**:
  Masuk ke direktori project melalui terminal lalu jalankan:
  ```bash
  php -S localhost:8000
  ```
  Kemudian buka browser di `http://localhost:8000` (sesuaikan `APP_URL` di `includes/config.php` ke `http://localhost:8000`).

---

## 🔑 Akun Demo Sintetis (Semua Password: `password`)

| Role | Username | Password | Deskripsi |
|---|---|---|---|
| **Admin** | `admin` | `password` | Super Administrator Portal |
| **Dosen** | `dr.andi` | `password` | Dr. Andi Pratama, M.Kom |
| **Dosen** | `prof.sari` | `password` | Prof. Sari Dewi, Ph.D |
| **Mahasiswa** | `budi2022` | `password` | Budi Santoso (NIM: 22TIF001) |
| **Mahasiswa** | `citra2022` | `password` | Citra Lestari (NIM: 22TIF002) |
| **Mahasiswa** | `dimas2023` | `password` | Dimas Rahardjo (NIM: 23TIF001) |

> 💡 *Pada halaman login tersedia kotak kartu akun demo yang bisa diklik untuk auto-fill username & password secara instan!*
