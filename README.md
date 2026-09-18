# 🐱 Kinbot - Sistem Laporan Kinerja Harian

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/Database-SQLite3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![UI Theme](https://img.shields.io/badge/UI-Dark%20%26%20Light%20Mode-38BDF8?style=for-the-badge)

**Kinbot** adalah aplikasi web manajemen dan pencatatan laporan kinerja harian lokal berbasis PHP & SQLite. Dirancang dengan antarmuka yang bersih (*clean*), responsif, modern, serta dilengkapi maskot **Kino** untuk pengalaman pengguna yang intuitif.

Aplikasi ini ditujukan untuk mempermudah pengawasan dan rekapitulasi tugas harian pada unit tertentu (saat ini diaplikasi masih dua unit)

---

## ✨ Fitur Utama

- 📊 **Dashboard Ringkasan & Stat Cards**: Menampilkan total pekerjaan, status selesai, dalam proses, dan belum dikerjakan secara *real-time*.
- 📈 **Progress Capaian Visual**: Bilah persentase otomatis berdasarkan penyelesaian tugas bulanan.
- 🤖 **Evaluasi Kinerja Otomatis oleh Kino**: Kesimpulan kinerja akhir bulan yang digenerate otomatis berdasarkan persentase capaian.
- 🗓️ **Filter Periode Bulan**: Memudahkan peninjauan laporan kinerja pada bulan-bulan sebelumnya.
- ✏️ **Manajemen Tugas Lengkap (CRUD)**:
  - Tambah tugas baru per divisi.
  - *Quick status update* langsung dari tabel.
  - Ubah & Hapus data via Modal Dialog.
- 🖨️ **Print Preview & Export**:
  - Export ke **Excel** (`.xls`).
  - Print / Export ke **PDF** (elemen UI seperti form dan tombol aksi otomatis disembunyikan agar hasil cetak rapi).
- 🌙 **Dual Theme Mode**: Dukungan mode gelap (*Dark Mode*) dan terang (*Light Mode*) yang tersimpan secara lokal (*persistent*).
- 💾 **Database Standalone**: Menggunakan SQLite3 berbasis file yang ringan dan mudah dipindahkan.

---

## 🛠️ Teknologi yang Digunakan

* **Backend**: PHP (PDO SQLite)
* **Database**: SQLite3 (`schema.sql` / `db_kinerja.sqlite`)
* **Frontend**: HTML5, CSS3 (CSS Variables & Flexbox/Grid Layout), Native JavaScript
* **Font**: Poppins (Google Fonts)
* **Graphics**: Integrated Inline SVG (Maskot Kinbot)

---

## 🚀 Cara Menjalankan di Lokal (Ubuntu / Linux)

### 1. Prasyarat
Pastikan PHP dan modul SQLite3 sudah terinstall di sistem Anda:

```bash
sudo apt update
sudo apt install php php-sqlite3 sqlite3 -y
