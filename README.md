# Task Management API (Backend Only)

Dokumen ini menyediakan spesifikasi API untuk aplikasi backend Task Management, yang dibangun dengan CodeIgniter 4. Aplikasi ini menangani otentikasi pengguna (pendaftaran, login, logout) dan operasi CRUD (Create, Read, Update, Delete) untuk tugas, dengan otorisasi berbasis peran (User dan Admin).

---

## ⚙️ Prerequisites 

Pastikan Anda memiliki hal-hal berikut untuk menjalankan aplikasi:

- PHP versi 7.4 atau lebih baru.
- Composer terinstal.
- MySQL/MariaDB atau database lain yang didukung oleh CodeIgniter 4.
- Pastikan ekstensi PHP yang diperlukan (seperti `mbstring`, `intl`, dan `json`) sudah aktif.
---

## 🛠️ Installation

Ikuti langkah-langkah di bawah ini untuk menginstal dan menjalankan proyek secara lokal:

### 1. Clone repositori ini:

```bash
    git clone https://github.com/khalidmahfudh/task-manager-api.git
    cd task-manager-api
```

### 2. Instal dependensi Composer:

```bash
    composer install
```

### 3. Salin file `.env`:

```bash
    cp .env.example .env
```

### 4. Konfigurasi file `.env`:

- Buka file `.env` dan atur properti `app.baseURL`.
- Konfigurasi detail database Anda (`database.default.hostname`, `database.default.database`, `database.default.username`, `database.default.password`).
- Atur kunci JWT untuk otentikasi token di bagian `JWT` (contoh: `jwt.secretKey = 'your_super_secret_key'`).

### 5. Jalankan migrasi database:

```bash
    php spark migrate
```

### 6. Jalankan server pengembangan:

```bash
    php spark serve
```

Aplikasi sekarang dapat diakses di http://localhost:8080 (atau port lain yang ditentukan).

---

## 📚 API Endpoints

### 0. Common Error Responses
Respon-respon ini digunakan di beberapa endpoint untuk penanganan kesalahan yang konsisten.

- `400 Bad Request`: Validasi gagal.
- `401 Unauthorized`: Token akses hilang atau tidak valid.
- `403 Forbidden`: Akses ditolak karena tidak ada izin.
- `404 Not Found`: Sumber daya tidak ditemukan.
- `409 Conflict`: Terjadi konflik (misalnya, sumber daya sudah ada).

### 1. Authentication Endpoints

| Endpoint | Method | Deskripsi |
|------|------|------|
| `/api/register` | `POST` | Mendaftarkan pengguna baru. |
| `/api/login` | `POST` | Otentikasi pengguna dan berikan token akses. |
| `/api/logout` | `POST` | Mencabut token akses pengguna. |

### 2. Task Management Endpoints (User Role)
Endpoint ini memerlukan otentikasi. Pengguna hanya dapat mengelola tugas mereka sendiri.

| Endpoint | Method | Deskripsi |
|------|------|------|
| `/api/tasks` | `GET` | Mengambil semua tugas milik pengguna yang terotentikasi. |
| `/api/tasks` | `POST` | Membuat tugas baru untuk pengguna yang terotentikasi. |
| `/api/tasks/{id}` | `GET` | Mengambil detail tugas berdasarkan ID. |
| `/api/tasks/{id}` | `PUT` | Memperbarui tugas berdasarkan ID. |
| `/api/tasks/{id}` | `DELETE` | Menghapus tugas berdasarkan ID. |

### 3. Admin Task Management Endpoints (Admin Role)
Endpoint ini memerlukan otentikasi dan peran `admin`.

| Endpoint | Method | Deskripsi |
|------|------|------|
| `/api/admin/tasks` | `GET` | Mengambil semua tugas dari semua pengguna (hanya admin). |
| `/api/admin/tasks/{id}` | `PUT` | Memperbarui tugas apa pun di sistem (hanya admin). |
| `/api/admin/tasks/{id}` | `DELETE` | Menghapus tugas apa pun di sistem (hanya admin). |

---

## 🧪 Testing

Proyek ini dilengkapi dengan file `*.rest` yang dapat Anda gunakan untuk menguji semua endpoint API dengan mudah. Pastikan Anda memiliki ekstensi REST Client (atau ekstensi sejenis) yang terinstal di editor kode Anda (misalnya, VS Code).

### 🚀 Cara Menggunakan
#### 1. Buka salah satu dari file berikut:
- `auth.rest`: Untuk menguji endpoint pendaftaran dan login.
- `tasks.rest`: Untuk menguji endpoint manajemen tugas pengguna.
- `admin.rest`: Untuk menguji endpoint manajemen tugas admin.

#### 2. Ikuti instruksi di dalam setiap file untuk mengirim permintaan ke API.
#### 3. Pastikan Anda telah melakukan login dan menyalin token akses JWT yang dihasilkan ke variabel `access_token` di dalam file `tasks.rest` dan `admin.rest` untuk permintaan yang membutuhkan otorisasi.

### Contoh Alur Pengujian
#### 1. Buka `auth.rest` dan jalankan permintaan Register untuk membuat pengguna baru.
#### 2. Jalankan permintaan Login untuk mendapatkan `access_token`. Salin token tersebut.
#### 3. Buka `tasks.rest`, ganti `YOUR_ACCESS_TOKEN_HERE` dengan token yang Anda salin, dan jalankan permintaan `GET` untuk melihat tugas-tugas Anda.
#### 4. Lanjutkan dengan permintaan `POST`, `PUT`, dan `DELETE` untuk mengelola tugas.
#### 5. Untuk menguji endpoint admin, ikuti langkah yang sama dengan file `admin.rest`, setelah Anda memiliki token dari akun yang memiliki peran admin.

