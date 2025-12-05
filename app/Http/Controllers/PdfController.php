<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use App\Models\FileHistory;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    // 1. Tampilkan Halaman Utama
    public function index()
    {
        // Ambil 5 history terakhir
        $histories = FileHistory::latest()->take(5)->get();
        return view('welcome', compact('histories'));
    }

    // 2. Proses Penggabungan (Merge) - SUDAH DIPERBAIKI
    public function merge(Request $request)
    {
        // Validasi input
        $request->validate([
            'files' => 'required',
            'files.*' => 'mimes:pdf|max:20480' // Maks 20MB per file
        ]);

        $pdf = new Fpdi();
        $files = $request->file('files');
        $tempPaths = []; // Array untuk menampung path file sementara

        foreach ($files as $file) {
            // A. Simpan file sementara di folder 'storage/app/temp'
            $relativePath = $file->store('temp'); 
            
            // B. SOLUSI ERROR: Ambil path absolut (C:\...) menggunakan Storage::path
            $absolutePath = Storage::path($relativePath);
            
            // C. Masukkan path ini ke antrian penghapusan nanti
            $tempPaths[] = $relativePath;

            // D. Set Source File menggunakan path absolut
            try {
                $pageCount = $pdf->setSourceFile($absolutePath);
            } catch (\Exception $e) {
                // Jika file gagal dibaca, skip atau return error
                return back()->with('error', 'Gagal membaca file: ' . $file->getClientOriginalName());
            }

            // E. Import setiap halaman
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $pdf->importPage($i);
                $pdf->AddPage();
                $pdf->useTemplate($tplId);
            }
        }

        // F. Siapkan penyimpanan file Hasil (Merged)
        // Kita simpan di folder 'storage/app/public/pdfs' agar rapi
        $fileName = 'merged_' . time() . '.pdf';
        $outputRelativePath = 'public/pdfs/' . $fileName;
        
        // Ambil path absolut untuk tujuan penyimpanan
        $outputAbsolutePath = Storage::path($outputRelativePath);

        // Pastikan folder tujuan ada (buat jika belum ada)
        $directory = dirname($outputAbsolutePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // G. Output file ke server
        $pdf->Output($outputAbsolutePath, 'F'); // 'F' = File

        // H. Hapus file-file sementara (Cleanup)
        foreach ($tempPaths as $path) {
            if(Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        // I. Simpan data ke Database (PostgreSQL)
        FileHistory::create([
            'original_name' => count($files) . ' Files Merged',
            'action_type'   => 'MERGE',
            'result_path'   => 'pdfs/' . $fileName
        ]);

        // J. Download file hasilnya ke browser user
        return response()->download($outputAbsolutePath);
    }

    // 3. Proses Image to PDF (Baru)
    public function imageToPdf(Request $request)
    {
        // Validasi: Harus gambar (jpeg, png, jpg, gif)
        $request->validate([
            'images' => 'required',
            'images.*' => 'mimes:jpeg,png,jpg,gif|max:10240' // Maks 10MB per gambar
        ]);

        $pdf = new Fpdi();
        $files = $request->file('images');
        $tempPaths = [];

        foreach ($files as $file) {
            // A. Simpan gambar sementara
            $relativePath = $file->store('temp');
            $absolutePath = Storage::path($relativePath);
            $tempPaths[] = $relativePath;

            // B. Tambah Halaman Baru (A4)
            $pdf->AddPage();

            // C. Masukkan Gambar ke Halaman
            // Syntax: Image(file, x, y, width, height)
            // Kita set x=10, y=10, width=190 (A4 width 210mm - margin kanan kiri 20mm)
            // Height dibiarkan kosong agar proporsional otomatis
            try {
                $pdf->Image($absolutePath, 10, 10, 190);
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses gambar: ' . $file->getClientOriginalName());
            }
        }

        // D. Simpan Hasil
        $fileName = 'images_merged_' . time() . '.pdf';
        $outputRelativePath = 'public/pdfs/' . $fileName;
        $outputAbsolutePath = Storage::path($outputRelativePath);

        // Pastikan folder ada
        $directory = dirname($outputAbsolutePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->Output($outputAbsolutePath, 'F');

        // E. Bersihkan file sementara
        foreach ($tempPaths as $path) {
            if(Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        // F. Simpan ke History
        FileHistory::create([
            'original_name' => count($files) . ' Images Converted',
            'action_type'   => 'IMAGE_TO_PDF',
            'result_path'   => 'pdfs/' . $fileName
        ]);

        return response()->download($outputAbsolutePath);
    }

    // 4. Proses Split PDF (Baru)
    public function split(Request $request)
    {
        // Validasi
        $request->validate([
            'file' => 'required|mimes:pdf|max:20480',
            'selected_pages' => 'required|string', // Format: "1,3,5"
        ]);

        $pdf = new Fpdi();
        $file = $request->file('file');
        
        // Simpan sementara & Ambil Path Absolut (Penting untuk Windows)
        $relativePath = $file->store('temp');
        $absolutePath = Storage::path($relativePath);

        try {
            $pageCount = $pdf->setSourceFile($absolutePath);
            
            // Ubah string "1,3,5" menjadi array [1, 3, 5]
            $pagesToKeep = explode(',', $request->selected_pages);

            foreach ($pagesToKeep as $pageNo) {
                // Pastikan halaman yang diminta valid (tidak lebih dari total halaman)
                if ($pageNo <= $pageCount && $pageNo > 0) {
                    $tplId = $pdf->importPage((int)$pageNo);
                    $pdf->AddPage();
                    $pdf->useTemplate($tplId);
                }
            }

            // Simpan Hasil
            $fileName = 'split_' . time() . '.pdf';
            $outputAbsolutePath = Storage::path('public/pdfs/' . $fileName);
            
            // Cek folder
            $directory = dirname($outputAbsolutePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $pdf->Output($outputAbsolutePath, 'F');

            // Cleanup
            if(Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }

            // History
            FileHistory::create([
                'original_name' => $file->getClientOriginalName(),
                'action_type'   => 'SPLIT',
                'result_path'   => 'pdfs/' . $fileName
            ]);

            return response()->download($outputAbsolutePath);

        } catch (\Exception $e) {
            // Cleanup jika error
            if(Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }
            return back()->with('error', 'Terjadi kesalahan saat memproses file.');
        }
    }
}