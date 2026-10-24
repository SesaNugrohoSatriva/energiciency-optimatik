# Energiciency — Laravel 12

Aplikasi manajemen produksi, OEE, persediaan, energi, costing, maintenance, laporan, dan fondasi integrasi sensor IoT. Seluruh seed data ditandai simulasi dan bukan data resmi.

## Kebutuhan Windows
- PHP 8.2+ dengan ekstensi `pdo_sqlite` untuk development lokal; aktifkan `pdo_mysql` hanya untuk deployment MySQL, serta `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `curl`, `zip`
- Composer 2.x
- MySQL 8.x atau MariaDB kompatibel
- Node.js LTS dan npm untuk membangun Tailwind/Vite

## Instalasi
1. Ekstrak ZIP ke folder kerja, misalnya `C:\laragon\www\energiciency` atau `C:\xampp\htdocs\energiciency`.
2. Buka terminal di folder project lalu jalankan:
   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```
   Jika `Copy-Item` tidak tersedia, salin `.env.example` menjadi `.env` melalui File Explorer.
3. Untuk development lokal, gunakan SQLite pada `.env` (`DB_CONNECTION=sqlite`) dan pastikan file database tersedia. Untuk deployment MySQL, buat database `energiciency` (charset `utf8mb4`) dan sesuaikan `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD`.
4. Autentikasi Blade sudah disediakan untuk menjalankan aplikasi awal. Dependensi Laravel Breeze tersedia di `composer.json` sebagai basis pengembangan; jika kamu ingin mengganti autentikasi dengan scaffold resmi Breeze, lakukan di branch terpisah karena scaffold tersebut dapat mengganti layout dan route aplikasi.
5. Instal asset dan jalankan migrasi + seeder:
   ```powershell
   npm install
   npm run build
   php artisan migrate --seed
   php artisan serve
   ```
6. Buka `http://127.0.0.1:8000`.

## Deployment InfinityFree
- Workflow GitHub Actions mengunggah project ke `htdocs`; root `index.php` meneruskan request ke `public/index.php`, sedangkan `.htaccess` mengarahkan asset dan route ke folder `public`.
- Workflow memasang dependensi Composer dan mengunggah `vendor`; jangan mengecualikan folder tersebut dari deploy.
- Buat `.env` secara manual di direktori project pada server (sejajar dengan `artisan`), karena file `.env` sengaja tidak diunggah. Isi minimal `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, dan kredensial database hosting.
- Pastikan `storage` dan `bootstrap/cache` dapat ditulisi oleh PHP. Setelah deploy, jalankan migrasi melalui cara yang tersedia di hosting sebelum membuka aplikasi.
- Sebelum migrasi penghapusan BOM, buat backup database. Migrasi ini menghapus tabel BOM, snapshot BOM, kebutuhan material batch, serta transaksi inventory yang terkait dengan batch; saldo master inventory dan transaksi pembukaan/penyesuaian stok dipertahankan.

## Login demo
- Email: `admin@energiciency.local`
- Password: `password`

Ganti password demo dan token IoT sebelum dipakai di jaringan selain localhost. Data seed adalah simulasi.

## Modul yang tersedia
- Dashboard KPI energi, biaya energi, output baik, jumlah order, dan OEE agregat.
- Detail mesin dengan histori energi, ringkasan biaya, OEE berbobot dari input valid, batch yang benar-benar tertaut, dan maintenance.
- Energy Monitoring dengan filter rentang waktu, mesin, sumber, order/batch, device, pencarian, tren jam/hari/minggu/bulan, dan detail payload tersamarkan.
- CRUD mesin, proses, produk, order produksi, persediaan, catatan energi, serta log pemeliharaan.
- Laporan energi per bulan.
- Migrasi MySQL dengan foreign key, indeks waktu telemetri, dan unique event ID untuk mencegah event IoT terduplikasi.
- Seeder 6 mesin, 6 proses, 5 produk, 6 order statis + 8 order generator, 9 item persediaan, 3 log maintenance, data OEE dan 14 hari data energi. Generator order mengikuti pola status/tanggal dan nilai ilustratif; nama robot dipertahankan sebagai label generator sementara katalog produk mengacu pada lima produk utama.

## API penerimaan data IoT
Endpoint: `POST /api/iot/telemetry`
Header: `X-IOT-TOKEN: <nilai IOT_INGEST_TOKEN di .env>`
Content-Type: `application/json`

Contoh payload:
```json
{
  "device_id": "EM-01",
  "external_event_id": "EM-01-2026-10-15T10:30:00Z-001",
  "machine_code": "MECH-01",
  "recorded_at": "2026-10-15T17:30:00+07:00",
  "kwh": 1.284,
  "payload": {"voltage": 220.4, "current": 4.1, "power_factor": 0.91}
}
```

Respons sukses memberikan `201` saat event baru dibuat dan `200` bila event ID yang sama dikirim ulang. Event ID dipakai untuk deduplikasi, timestamp ber-offset dinormalisasi ke zona waktu aplikasi, dan biaya dihitung dari `ENERGY_TARIFF_PER_KWH` lalu disimpan bersama event. `external_event_id` harus unik. Atur token rahasia panjang di `.env`; untuk deployment nyata gunakan HTTPS, secret manager, autentikasi perangkat individual, rate limit sesuai perangkat, monitoring, dan rotasi token. Jangan membuka endpoint ke internet sebelum pengamanan deployment ditinjau.

### Batas perhitungan analitik energi
- Grafik menggunakan SVG yang dibangun langsung dari agregasi query database (tanpa library chart tambahan); hasil grafik dibatasi maksimal 180 bucket terbaru. Ringkasan dan tabel tetap mengikuti rentang/filter terpilih.
- Total kWh dan biaya adalah jumlah nilai serta biaya yang tersimpan per event. Biaya historis tidak dihitung ulang memakai tarif saat ini.
- Skema belum menyimpan jenis meter (`interval` atau `cumulative`) maupun interval sampling. Karena itu total bukan hasil rekonstruksi delta cumulative meter, dan grafik tidak membuat titik untuk bucket kosong.
- Average/peak power, reset meter, deteksi gap/anomali, status online IoT, idle/downtime, serta freshness terhadap heartbeat tidak ditampilkan sebagai angka/status karena metadata pendukung belum tersedia.
- OEE periode dihitung dari input lengkap: availability = total operating minutes / total planned minutes; performance = Σ(ideal cycle minutes × total output) / total operating minutes; quality = total good output / total output; OEE = ketiganya dikalikan. Intensitas energi per jam menggunakan total energi periode dibagi operating hours dari catatan OEE pada rentang tanggal sama; ini bukan pengukuran daya per interval.
- Energi per unit hanya ditampilkan untuk konsumsi yang eksplisit tertaut ke batch. Output pembagi menggunakan good quantity untuk unit baik dan actual quantity untuk total output.

### Scaling IoT yang disarankan
- Satu perangkat mengirim event dengan `device_id`, timestamp UTC/offset-aware, kWh, dan ID event unik.
- Tambahkan tabel `iot_devices` untuk status provisioning, lokasi, kredensial ter-hash, dan metadata perangkat saat deployment multi-device.
- Untuk perangkat yang mengirim daya sesaat (W/kW), integrasikan pembacaan kumulatif meter atau hitung energi dari sampel dengan interval yang diketahui; jangan menyamakan kW dengan kWh.
- Untuk volume besar, kirim melalui MQTT broker/gateway, masukkan ke queue (Redis/database), lalu worker memvalidasi dan menulis batch. Pisahkan telemetri mentah dari agregat per menit/jam/hari; pertimbangkan partisi/retensi data saat tabel membesar.
- Kaitkan telemetri dengan `machine_id`, `process_id`, dan `production_order_id` bila mapping tersedia. Gunakan waktu kejadian, bukan waktu diterima server, untuk agregasi.

## Catatan batasan versi awal
Paket ini adalah fondasi aplikasi yang bisa dikembangkan, bukan sistem MES/SCADA tersertifikasi. CRUD memakai formulir umum; relasi FK untuk beberapa field dimasukkan sebagai ID numerik. Login Blade yang disertakan adalah implementasi minimal yang kompatibel dengan pola autentikasi Laravel, bukan hasil generate langsung dari perintah Breeze. Costing per produk memiliki service kalkulasi, tetapi belum menjadi workflow penutupan order otomatis. Seeder mereproduksi data inti yang disebutkan di `data.md`, tidak seluruh rumus generator front-end. Uji di Windows dan verifikasi rumus OEE dengan definisi siklus ideal yang digunakan di fasilitas sebelum dipakai untuk keputusan operasional.
