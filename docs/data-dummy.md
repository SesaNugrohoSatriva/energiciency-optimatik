# Data Dummy Energiciency

Dokumen ini merangkum data contoh yang tersedia di proyek Energiciency. Aplikasi
memakai `buildMockData()` dari `src/data/mockDatabase.js` sebagai sumber data
awal, kecuali jika data sebelumnya sudah tersimpan di `localStorage` dengan key
`energiciency-data`.

> **Penting:** seluruh angka, order, biaya, performa mesin, dan log di bawah
> adalah data simulasi untuk demo. Nama produk terinspirasi dari konsep
> yang ditampilkan secara publik, tetapi catatan produk, routing, biaya,
> dan parameter produksi bukan spesifikasi atau data resmi perusahaan.

## Ringkasan data aplikasi

Objek dari `buildMockData()` menyediakan:

| Properti | Isi |
| --- | --- |
| `machines` | 6 mesin/workstation simulasi |
| `processes` | 6 proses produksi dan estimasi energi |
| `products` | 5 produk dengan routing, target OEE, dan estimasi biaya |
| `orders` / `productionOrders` | Gabungan 8 order generator dan 6 order statis |
| `inventory` | 9 item persediaan hasil generator |
| `costing` | Data costing bulanan selama 6 bulan |
| `energyData` | Data energi harian selama 14 hari |
| `energyProcessData` | Ringkasan energi untuk 4 proses |
| `productionData` | Data produksi bulanan selama 6 bulan |
| `dashboardKpis` | 4 KPI dashboard statis |
| `reportsTrend` | 6 titik data laporan statis |
| `machinePerformance` | Ringkasan dan tren OEE yang dihitung dari data OEE |
| `processPerformance` | Ringkasan performa proses yang dihitung dari data OEE |
| `productPerformance` | Ringkasan performa produk yang dihitung dari order statis |
| `maintenanceLogs` | 3 log pemeliharaan |

## Katalog mesin

| ID | Kode | Mesin | Kategori | Proses | Daya nominal | Target OEE |
| --- | --- | --- | --- | --- | ---: | ---: |
| M-101 | MECH-01 | Mechanical Assembly Workstation | Mechanical | PROC-01, PROC-02 | 10,5 kW | 82% |
| M-102 | ELEC-01 | Electrical Assembly Workstation | Electrical | PROC-03 | 9,2 kW | 80% |
| M-103 | CTRL-01 | Control Integration Workstation | Control | PROC-04 | 11,8 kW | 85% |
| M-104 | CAL-01 | Calibration Workstation | Calibration | PROC-05 | 12,1 kW | 78% |
| M-105 | TEST-01 | Functional Test Workstation | Test | PROC-06 | 8,7 kW | 88% |
| M-106 | QC-01 | Final Inspection Workstation | Quality | PROC-06 | 6,8 kW | 90% |

Seluruh mesin ditandai `isSimulated: true`. Status awalnya Normal kecuali M-104
yang berstatus Monitor. Katalog juga menyimpan biaya operasi per jam dan daftar
produk yang didukung.

## Katalog proses

| ID | Proses | Mesin | Energi contoh |
| --- | --- | --- | ---: |
| PROC-01 | Component preparation | M-101 | 36 kWh |
| PROC-02 | Mechanical assembly | M-101 | 42 kWh |
| PROC-03 | Electrical assembly | M-102 | 28 kWh |
| PROC-04 | Control-system integration | M-103 | 31 kWh |
| PROC-05 | Calibration and configuration | M-104 | 24 kWh |
| PROC-06 | Functional testing and inspection | M-105, M-106 | 18 kWh |

`energyProcessData` adalah data generator terpisah untuk visualisasi energi:

| Proses | Energi | Target | Utilisasi |
| --- | ---: | ---: | ---: |
| Welding | 42 | 36 | 92% |
| Assembly | 36 | 33 | 87% |
| Cutting | 27 | 24 | 79% |
| Finishing | 19 | 18 | 72% |

## Katalog produk

Semua produk berstatus Active dan memiliki `simulationStatus` yang menyatakan
bahwa datanya simulasi ilustratif, bukan data resmi. Setiap produk mempunyai
routing proses dengan waktu siklus, waktu setup, output, energi, dan kebutuhan
tenaga kerja.

| ID | SKU | Produk | Kategori | Ukuran batch | Target OEE |
| --- | --- | --- | --- | ---: | ---: | --- |
| PRD-1001 | SIM-VENT-001 | ICU Ventilator | Medical Device | 8 | 82% |
| PRD-1002 | SIM-MPPS-001 | Mini-Plan Production System | Automation System | 10 | 84% |
| PRD-1003 | SIM-DCS-001 | Digital Control System | Control System | 12 | 86% |
| PRD-1004 | SIM-FLOAMS-001 | FloAms | Process Automation | 7 | 83% |
| PRD-1005 | SIM-APE-001 | Alat Peraga Pendidikan | Education Product | 15 | 89% |

Setiap produk memiliki routing proses dengan waktu siklus, waktu setup, output,
energi, dan kebutuhan tenaga kerja. Biaya material, tenaga kerja, dan overhead
merupakan estimasi master produk yang diinput terpisah.

## Order produksi

Enam order berikut ditetapkan langsung di `mockDatabase.js`:

| ID | Produk | Status | Progress | Rencana | Aktual | Baik | Tanggal |
| --- | --- | --- | ---: | ---: | ---: | ---: | --- |
| PO-3001 | ICU Ventilator (PRD-1001) | In Progress | 68% | 24 | 18 | 17 | 2025-02-03 |
| PO-3002 | Mini-Plan Production System (PRD-1002) | Completed | 100% | 18 | 18 | 18 | 2025-02-10 |
| PO-3003 | Digital Control System (PRD-1003) | Planned | 25% | 15 | 4 | 3 | 2025-02-15 |
| PO-3004 | FloAms (PRD-1004) | Delayed | 42% | 20 | 11 | 10 | 2025-02-20 |
| PO-3005 | ICU Ventilator (PRD-1001) | Completed | 100% | 24 | 24 | 24 | 2025-02-24 |
| PO-3006 | Mini-Plan Production System (PRD-1002) | In Progress | 74% | 18 | 15 | 14 | 2025-02-27 |

Generator menambahkan 8 order dengan ID PO-2600 sampai PO-2607. Produk yang
digunakan adalah Robot Edukasi R-01, Lengan Robot R-06, Robot Inspeksi V-02,
Robot Pick & Place P-09, Robot Welding W-05, Robot Packaging K-12, Clamp
Assembly C-03, dan Vision Servo S-08. Status berulang dalam urutan Completed,
In Progress, Planned, Delayed. Tanggal awalnya 2025-01-14 dengan jarak 4 hari
antarorder. Quantity, progress, energi, biaya, dan prioritas dibentuk oleh
rumus generator.

Order hasil generator diberi `productId` dan `productName` secara berulang
dari katalog produk. Enam order statis dilengkapi nilai produk, prioritas,
energi, dan biaya agar memiliki bentuk yang sama untuk tampilan aplikasi.

## Data OEE dan pemeliharaan

`oeeTrend` berisi 15 catatan input (3 tanggal per mesin untuk M-101 sampai
M-105; M-106 tidak memiliki catatan OEE). Setiap catatan memuat tanggal,
planned production time (480 menit), operating time, ideal cycle time, total
output, serta good output. Contoh:

| Mesin | Tanggal | Waktu operasi | Siklus ideal | Total output | Output baik |
| --- | --- | ---: | ---: | ---: | ---: |
| M-101 | 2025-01-05 | 430 menit | 0,42 | 980 | 936 |
| M-101 | 2025-01-12 | 440 menit | 0,42 | 1.010 | 962 |
| M-101 | 2025-01-19 | 415 menit | 0,42 | 975 | 923 |

Nilai availability, performance, quality, dan OEE dihitung dari catatan input
menggunakan `calculateOEE()`. Ringkasan mesin/proses dan status tindak lanjut
dibentuk dari hasil tersebut.

| Mesin | Isu simulasi | Tanggal | Prioritas | Status |
| --- | --- | --- | --- | --- |
| M-104 | Calibration drift detected | 2025-02-06 | High | Open |
| M-102 | Minor stoppage pattern | 2025-02-12 | Medium | Monitor |
| M-103 | Sensor alignment check | 2025-02-18 | Low | Planned |

## Energi, produksi, costing, dan laporan

Generator yang dipanggil `buildMockData()` menghasilkan:

- **Energi:** 14 catatan tanggal 2025-01-01 sampai 2025-01-14. Setiap record
  memiliki `kwh`, `target`, `peak`, `cost`, dan nama mesin simulasi
  (Robot Welding, Stamping, atau Assembly). Nilai dibentuk dari rumus
  deterministik berbasis indeks hari; biaya memakai tarif contoh Rp1.640/kWh.
- **Produksi:** 6 catatan bulanan, dimulai dari Januari 2024. Setiap record
  memiliki bulan, label bulan/tahun, jumlah produksi, energi, dan efisiensi.
- **Costing:** 6 catatan bulanan, dimulai dari Juli 2024. Tiap record
  memecah biaya menjadi material, labor, energy, overhead, dan total.
- **KPI dashboard statis:** Total Produksi 486 unit; Konsumsi Energi 1.284 kWh;
  Biaya Energi Rp1,92 jt (Rp3.950/unit); Efisiensi Produksi 87,6%.
- **Laporan statis (`reportsTrend`):**

  | Bulan | Output | Energi | Efisiensi |
  | --- | ---: | ---: | ---: |
  | Jan | 320 | 520 | 86% |
  | Feb | 340 | 545 | 87% |
  | Mar | 360 | 560 | 88% |
  | Apr | 410 | 610 | 89% |
  | Mei | 450 | 640 | 90% |
  | Jun | 486 | 680 | 91% |

## Persediaan

Generator membuat 9 item persediaan dengan SKU INV-100 sampai INV-108. Nama
item diambil berurutan dari Servo Motor, Welding Rod, Safety Glove, Control
Panel, Gearbox, Hydraulic Pump, Electrical Relay, Lubricant Oil, dan Air
Filter. Record mencakup kategori, stock, threshold, satuan, lokasi rak, unit
cost, dan status stok. Angka stok, ambang, harga, kategori, satuan, dan lokasi
dibentuk deterministik dari indeks item.

## Data contoh lain di repository

- `src/data/dashboard.js` memuat data dashboard versi lama: KPI, tren produksi
  dan energi, 3 order, 4 tahapan proses, serta rincian biaya. Data ini berbeda
  dari `buildMockData()` dan tidak digunakan sebagai sumber awal oleh
  `src/App.jsx`.
- `src/data/generators/qualityGenerator.js` menyediakan generator 10 catatan
  pemeriksaan kualitas default (status Passed, Failed, atau Needs Review,
  defect, defect rate, inspector, dan tanggal). Generator ini belum dipanggil
  oleh `buildMockData()`.
- Data yang dibuat aplikasi dapat berubah jika pengguna mengedit data lalu
  menyimpannya: `src/App.jsx` membaca dan menulis state ke `localStorage`.
  Karena itu, data di browser tidak selalu sama dengan nilai awal generator.

## Sumber implementasi

- `src/data/mockDatabase.js` — katalog, order statis, data OEE, log
  pemeliharaan, KPI, laporan, dan perakitan objek data.
- `src/data/generators/` — generator produksi, order, energi, inventory,
  costing, dan kualitas.
- `src/utils/oeeCalculator.js` dan `src/utils/costingCalculator.js` —
  kalkulasi OEE dan biaya produk.
