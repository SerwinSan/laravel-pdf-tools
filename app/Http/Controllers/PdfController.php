<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    // Halaman Utama
    public function index()
    {
        return view('welcome');
    }

// --- MERGE DENGAN FITUR ORIENTASI MANUAL ---
    public function merge(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'mimes:pdf|max:51200',
            // Kita terima array orientasi (bisa 'auto', 'P', atau 'L')
            'orientations' => 'array', 
        ]);

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);

        $files = $request->file('files');
        $orientations = $request->input('orientations', []); // Ambil input orientasi
        $tempPaths = [];

        // Loop menggunakan index $k untuk mencocokkan file dengan orientasinya
        foreach ($files as $k => $file) {
            $relativePath = $file->store('temp'); 
            $absolutePath = Storage::path($relativePath);
            $tempPaths[] = $relativePath;

            // Ambil settingan orientasi user untuk file ini (default 'auto')
            $userOrientation = $orientations[$k] ?? 'auto';

            try {
                $pageCount = $pdf->setSourceFile($absolutePath);
                
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplId);
                    
                    $w = $size['width'];
                    $h = $size['height'];

                    // Logika Penentuan Orientasi
                    if ($userOrientation === 'auto') {
                        // Ikuti aslinya
                        $finalOrientation = ($w > $h) ? 'L' : 'P';
                        $finalW = $w;
                        $finalH = $h;
                    } 
                    elseif ($userOrientation === 'P') {
                        // Paksa Portrait (Sisi pendek jadi lebar)
                        $finalOrientation = 'P';
                        $finalW = min($w, $h);
                        $finalH = max($w, $h);
                    } 
                    else { // 'L' aka Landscape
                        // Paksa Landscape (Sisi panjang jadi lebar)
                        $finalOrientation = 'L';
                        $finalW = max($w, $h);
                        $finalH = min($w, $h);
                    }

                    // Tambah Halaman
                    $pdf->AddPage($finalOrientation, [$finalW, $finalH]);
                    
                    // Tempel Template (Otomatis menyesuaikan kotak yang sudah kita buat)
                    $pdf->useTemplate($tplId, 0, 0, $finalW, $finalH);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Output & Download
        $fileName = 'merged_' . time() . '.pdf';
        $outputRelativePath = 'public/pdfs/' . $fileName;
        $outputAbsolutePath = Storage::path($outputRelativePath);

        $directory = dirname($outputAbsolutePath);
        if (!file_exists($directory)) mkdir($directory, 0755, true);

        $pdf->Output($outputAbsolutePath, 'F');

        foreach ($tempPaths as $path) {
            if(Storage::exists($path)) Storage::delete($path);
        }

        return response()->download($outputAbsolutePath)->deleteFileAfterSend(true);
    }
        
    // 2. IMAGE TO PDF (Original Size)
    public function imageToPdf(Request $request)
    {
        $request->validate([
            'images' => 'required',
            'images.*' => 'mimes:jpeg,png,jpg,gif|max:10240'
        ]);

        $pdf = new Fpdi();
        $files = $request->file('images');
        $tempPaths = [];

        foreach ($files as $file) {
            $relativePath = $file->store('temp');
            $absolutePath = Storage::path($relativePath);
            $tempPaths[] = $relativePath;

            // FITUR BARU: Hitung ukuran pixel gambar asli
            $imgSize = getimagesize($absolutePath); // [0]=>width, [1]=>height
            
            // Konversi Pixel ke Milimeter (FPDF default pakai mm)
            // 1 pixel = 0.264583 mm (Asumsi 96 DPI)
            $widthMM = $imgSize[0] * 0.264583;
            $heightMM = $imgSize[1] * 0.264583;

            // Buat halaman PDF sesuai ukuran gambar
            $orientation = ($widthMM > $heightMM) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$widthMM, $heightMM]);

            // Tempel gambar memenuhi halaman (0,0)
            $pdf->Image($absolutePath, 0, 0, $widthMM, $heightMM);
        }

        // Output
        $fileName = 'images_merged_' . time() . '.pdf';
        $outputRelativePath = 'public/pdfs/' . $fileName;
        $outputAbsolutePath = Storage::path($outputRelativePath);
        
        $directory = dirname($outputAbsolutePath);
        if (!file_exists($directory)) mkdir($directory, 0755, true);

        $pdf->Output($outputAbsolutePath, 'F');

        // Cleanup
        foreach ($tempPaths as $path) {
            if(Storage::exists($path)) Storage::delete($path);
        }

        return response()->download($outputAbsolutePath)->deleteFileAfterSend(true);
    }

// 3. SPLIT PDF (FIXED KEYS: width & height)
    public function split(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:20480',
            'selected_pages' => 'required|string', // Contoh: "1,3,5"
        ]);

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false); // Matikan auto page break

        $file = $request->file('file');
        $relativePath = $file->store('temp');
        $absolutePath = Storage::path($relativePath);

        try {
            $pageCount = $pdf->setSourceFile($absolutePath);
            
            // Ubah string "1,3,5" menjadi array [1, 3, 5]
            $pagesToKeep = explode(',', $request->selected_pages);

            foreach ($pagesToKeep as $pageNo) {
                $pageNo = (int)$pageNo;
                
                // Pastikan halaman valid (tidak nol dan tidak melebihi total halaman)
                if ($pageNo <= $pageCount && $pageNo > 0) {
                    $tplId = $pdf->importPage($pageNo);
                    
                    // Ambil ukuran asli
                    $size = $pdf->getTemplateSize($tplId);
                    
                    // PERBAIKAN DISINI: Gunakan 'width' dan 'height'
                    $width  = $size['width'];
                    $height = $size['height'];
                    
                    $orientation = ($width > $height) ? 'L' : 'P';
                    
                    $pdf->AddPage($orientation, [$width, $height]);
                    $pdf->useTemplate($tplId); // Tempel otomatis
                }
            }

            // Output & Download
            $fileName = 'split_' . time() . '.pdf';
            $outputRelativePath = 'public/pdfs/' . $fileName;
            $outputAbsolutePath = Storage::path($outputRelativePath);
            
            $directory = dirname($outputAbsolutePath);
            if (!file_exists($directory)) mkdir($directory, 0755, true);

            $pdf->Output($outputAbsolutePath, 'F');

            // Hapus file temp upload user
            if(Storage::exists($relativePath)) Storage::delete($relativePath);

            return response()->download($outputAbsolutePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Jika error, hapus temp file
            if(Storage::exists($relativePath)) Storage::delete($relativePath);
            
            // Kembalikan ke halaman awal dengan pesan error (bisa ditampilkan di blade jika mau)
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
}