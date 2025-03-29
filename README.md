# Teresa Pro - Laravel 12 API

![Tests](https://github.com/xBigDaddyx/teresa-pro-backend/actions/workflows/teresa-test.yml/badge.svg)

## 🚀 Tentang Teresa Pro
**Teresa Pro** adalah API berbasis **Laravel 12** yang dirancang untuk memberikan solusi manajemen data yang cepat, aman, dan scalable. Dengan arsitektur **DDD (Domain-Driven Design)**, proyek ini mengadopsi pendekatan **multi-tenancy** dan berbasis event-driven menggunakan **Laravel Reverb** serta **Redis** untuk kinerja optimal.

## ✨ Fitur Utama
- 🔥 **Laravel 12 & Sanctum** - API yang aman dengan token-based authentication.
- 🏢 **Multi-Tenancy** - Database per tenant menggunakan PostgreSQL.
- 📦 **Repository Pattern + Redis Caching** - Performa tinggi dengan caching yang efisien.
- 📡 **Real-Time Notifications** - Menggunakan Laravel Reverb & Horizon.
- 📊 **Filament Admin Panel** - Dashboard modern untuk administrasi.
- 📜 **Approval System (Teresa-Gatekeeper)** - Multi-level approval dengan logging.

## 🏗️ Arsitektur
Teresa Pro menerapkan **Domain-Driven Design (DDD)** untuk menjaga modularitas dan skalabilitas kode:
- **Bounded Contexts** untuk pemisahan domain bisnis.
- **Event-Driven Architecture** dengan Laravel Reverb.
- **Repository Pattern** untuk abstraksi data.

## 🛠️ Teknologi yang Digunakan
- **Laravel 12** (Backend API)
- **PostgreSQL** (Database Multi-Tenancy)
- **Redis** (Caching & Queue Management)
- **Filament v3** (Admin Panel)
- **Laravel Reverb & Horizon** (Real-Time Processing)
- **Docker** (Deployment & Pengembangan)

## 🚀 Instalasi & Penggunaan
### 1️⃣ Clone Repository
```bash
git clone https://github.com/username/teresa-pro.git
cd teresa-pro
```

### 2️⃣ Install Dependencies
```bash
composer install
npm install
```

### 3️⃣ Konfigurasi Lingkungan
Buat file `.env` dari template:
```bash
cp .env.example .env
```
Lalu atur database dan konfigurasi lainnya.

### 4️⃣ Generate Key & Migrasi Database
```bash
php artisan key:generate
php artisan migrate --seed
```

### 5️⃣ Jalankan Server
```bash
php artisan serve
```
API kini dapat diakses di `http://127.0.0.1:8000/api` 🚀

## 🛡️ Lisensi
Proyek ini berlisensi di bawah **MIT License**. Silakan baca [LICENSE](LICENSE) untuk detail lebih lanjut.


