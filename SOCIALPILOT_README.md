# SocialPilot AI — Setup & Panduan

## Tentang
SocialPilot AI adalah platform SaaS multi-tenant untuk mengelola banyak akun media sosial dalam satu dashboard.
Dibangun dengan Laravel 12 + Mantis Bootstrap 5 Admin Template.

## Stack Teknologi
- **Backend**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL (atau MySQL)
- **UI**: Mantis Bootstrap 5 Admin Template
- **AI**: OpenAI GPT-4o / GPT-4o-mini
- **Social Media API**: Zernio API
- **Payment**: Midtrans
- **Calendar**: FullCalendar 6
- **Charts**: ApexCharts 3

## Struktur Database (16 tabel)
| Tabel | Deskripsi |
|-------|-----------|
| packages | Paket berlangganan (Basic/Pro/Agency) |
| tenants | Data bisnis/customer SaaS |
| users | User login per tenant |
| subscriptions | Riwayat langganan tenant |
| social_accounts | Akun sosmed terhubung ke Zernio |
| posts | Post yang sudah dipublish |
| scheduled_posts | Post terjadwal |
| comments | Komentar masuk dari Zernio webhook |
| comment_replies | Riwayat balasan komentar (AI/manual) |
| ai_settings | Pengaturan AI per tenant |
| knowledge_bases | Knowledge base AI tenant |
| inbox_messages | DM dan inbox |
| webhook_logs | Audit log webhook Zernio |
| analytics | Cache analytics harian |
| payments | Riwayat pembayaran Midtrans |
| activity_logs | Audit seluruh aktivitas user |

## Paket Berlangganan
| Paket | Harga | Akun Sosmed | AI Reply | Analytics | Multi User |
|-------|-------|-------------|----------|-----------|------------|
| Basic | Rp199.000/bln | 1 | ❌ | ❌ | ❌ |
| Pro | Rp399.000/bln | 5 | ✅ (500/bln) | ❌ | ❌ |
| Agency | Rp999.000/bln | 10 | ✅ (2000/bln) | ✅ | ✅ |

## Fitur Implemented
- [x] Multi-tenant registration & login (Laravel Breeze)
- [x] Email verification
- [x] Forgot password / reset password
- [x] Dashboard dengan stats & ringkasan
- [x] Koneksi akun sosial media (Instagram, Facebook, LinkedIn, TikTok, Threads, X, YouTube)
- [x] Buat post & publish sekarang / jadwalkan
- [x] Content Calendar (FullCalendar dengan drag & drop)
- [x] Inbox terpusat (komentar + DM)
- [x] AI Auto Reply (OpenAI + Knowledge Base)
- [x] Analytics dengan chart (ApexCharts)
- [x] Paket berlangganan dengan checkout
- [x] Webhook handler Zernio
- [x] Activity logs
- [x] Feature gating berdasarkan paket

## Cara Setup

### 1. Copy .env
```bash
cp .env.example .env
php artisan key:generate
```

### 2. Konfigurasi Database di .env
```
DB_CONNECTION=pgsql  # atau mysql
DB_HOST=127.0.0.1
DB_DATABASE=socialpilot
DB_USERNAME=postgres
DB_PASSWORD=yourpassword
```

### 3. Konfigurasi API Keys di .env
```
OPENAI_API_KEY=sk-your-openai-api-key
ZERNIO_API_KEY=your-zernio-api-key
MIDTRANS_SERVER_KEY=your-midtrans-server-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key
```

### 4. Jalankan Migration & Seeder
```bash
php artisan migrate --seed
```

### 5. Jalankan Development Server
```bash
php artisan serve
```

## URL Penting
- **Landing Page**: `/`
- **Register**: `/register`
- **Login**: `/login`
- **Dashboard**: `/dashboard`
- **Buat Post**: `/posts/create`
- **Calendar**: `/calendar`
- **Inbox**: `/inbox`
- **AI Settings**: `/ai/settings`
- **Analytics**: `/analytics`
- **Akun Sosmed**: `/social-accounts`
- **Langganan**: `/subscription`
- **Webhook Zernio**: `POST /webhook/zernio`

## Integrasi Zernio API
Konfigurasikan webhook URL di dashboard Zernio:
- URL: `https://yourdomain.com/webhook/zernio`
- Events: `comment.new`, `message.new`

## Integrasi Midtrans
Untuk payment production, ganti simulasi di `SubscriptionController::checkout()`
dengan Midtrans Snap API call.
