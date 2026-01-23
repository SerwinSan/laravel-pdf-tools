# 📄 Laravel PDF Tools (Stateless)

Aplikasi manipulasi PDF berbasis web yang ringan, aman, dan tanpa database (Stateless). Dibangun menggunakan **Laravel**, aplikasi ini memproses file secara sementara dan langsung menghapusnya setelah diunduh, menjamin privasi pengguna dan hemat penyimpanan server.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/laravel-v10.x-red.svg)
![PHP](https://img.shields.io/badge/php-^8.1-777BB4.svg)

## ✨ Fitur Utama

Aplikasi ini memiliki 3 fitur inti yang didukung antarmuka modern:

### 1. 🔗 Merge PDF (Gabungkan PDF)
* **Drag & Drop Sorting:** Atur urutan file PDF dengan mudah menggunakan fitur seret-dan-lepas (didukung oleh *SortableJS*).
* **Orientasi Halaman:** Pilihan otomatis mendeteksi orientasi, atau paksa ke Portrait/Landscape per file.
* **Flexible Size:** Mendukung penggabungan file dengan ukuran kertas berbeda tanpa terpotong.

### 2. 🖼️ Image to PDF (Gambar ke PDF)
* Ubah banyak gambar (JPG, PNG) menjadi satu file PDF.
* **Original Scaling:** Ukuran halaman PDF otomatis mengikuti resolusi/dimensi asli gambar.
* Fitur Drag & Drop urutan gambar.

### 3. ✂️ Split PDF (Pisahkan Halaman)
* **Visual Preview:** Upload PDF dan lihat *thumbnail* setiap halaman.
* **Select to Split:** Klik halaman-halaman tertentu yang ingin diambil/dipisahkan.
* Antarmuka *Drag & Drop Upload* yang interaktif.

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** Laravel (PHP)
* **PDF Library:** `setasign/fpdi` & `fpdf`
* **Frontend Logic:** JavaScript (Vanilla)
* **UI Libraries:** * [SortableJS](https://sortablejs.github.io/Sortable/) (Untuk Drag & Drop list)
    * [PDF.js](https://mozilla.github.io/pdf.js/) (Untuk visual preview split)
* **Database:** Tidak ada (Stateless).

---

## 🚀 Instalasi Lokal (Cara Menjalankan)

Ikuti langkah ini untuk menjalankan proyek di komputer Anda (Windows/Linux/Mac):

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/username-anda/laravel-pdf-tools.git](https://github.com/username-anda/laravel-pdf-tools.git)
    cd laravel-pdf-tools
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Setup Environment**
    Duplikat file `.env.example` menjadi `.env`:
    ```bash
    cp .env.example .env
    ```

4.  **Konfigurasi Database (PENTING)**
    Karena aplikasi ini *stateless*, kita tidak butuh database berat. Cukup gunakan SQLite atau biarkan kosong. Edit file `.env`:
    ```env
    DB_CONNECTION=sqlite
    # Hapus atau beri komentar (#) pada baris DB_HOST, DB_PORT, dll.
    ```
    *Tips: Buat file kosong `database/database.sqlite` jika Laravel memintanya.*

5.  **Generate Key**
    ```bash
    php artisan key:generate
    ```

6.  **Jalankan Aplikasi**
    ```bash
    php artisan serve
    ```
    Buka browser dan akses: `http://localhost:8000`
