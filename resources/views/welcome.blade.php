<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Tools - Laravel</title>
    
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <h1>PDF Tools Sederhana</h1>

    <div class="card">
        <h2>Gabungkan PDF (Merge)</h2>
        <form action="{{ route('pdf.merge') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <p>Pilih beberapa file PDF sekaligus:</p>
                <input type="file" name="files[]" multiple accept=".pdf" required>
            </div>
            <button type="submit">Gabungkan & Download</button>
        </form>
    </div>
</div> <div class="card">
        <h2>Ubah Gambar ke PDF</h2>
        <form action="{{ route('pdf.image') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <p>Pilih file gambar (JPG, PNG):</p>
                <input type="file" name="images[]" multiple accept="image/*" required>
            </div>
            <button type="submit" style="background-color: #38c172;">Konversi ke PDF</button>
        </form>
    </div>

    <div class="card">
        <h2>Pisahkan Halaman PDF (Splitter)</h2>
        <p>Upload PDF, lalu <b>klik halaman</b> yang ingin diambil.</p>
        
        <form action="{{ route('pdf.split') }}" method="POST" enctype="multipart/form-data" id="splitForm">
            @csrf
            
            <div class="form-group">
                <input type="file" name="file" id="pdf-upload" accept=".pdf" required>
            </div>

            <input type="hidden" name="selected_pages" id="selected-pages-input">

            <div id="preview-container"></div>
            
            <br>
            <button type="submit" id="btn-split" style="background-color: #e3342f; opacity: 0.5; cursor: not-allowed;" disabled>
                Pisahkan Halaman Terpilih
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    <script>
        // Set Worker untuk PDF.js (Wajib agar script jalan)
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        const fileInput = document.getElementById('pdf-upload');
        const container = document.getElementById('preview-container');
        const hiddenInput = document.getElementById('selected-pages-input');
        const submitBtn = document.getElementById('btn-split');
        let selectedPages = []; // Array untuk menampung halaman yg dipilih

        fileInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Reset tampilan jika upload file baru
            container.innerHTML = '<p>Sedang memuat halaman...</p>';
            selectedPages = [];
            updateInput();

            // Baca file PDF
            const uri = URL.createObjectURL(file);
            const pdf = await pdfjsLib.getDocument(uri).promise;
            
            container.innerHTML = ''; // Hapus tulisan loading

            // Loop semua halaman
            for (let i = 1; i <= pdf.numPages; i++) {
                // Render halaman
                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 0.2 }); // Scale 0.2 agar jadi thumbnail kecil
                
                // Buat elemen Canvas (Gambar)
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.className = 'page-thumbnail';
                
                // Tempel konten PDF ke Canvas
                await page.render({ canvasContext: context, viewport: viewport }).promise;

                // Tambah label nomor halaman
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                
                const pageNum = document.createElement('span');
                pageNum.className = 'page-number';
                pageNum.innerText = 'Hal ' + i;

                // Event Klik: Pilih/Hapus Pilihan
                canvas.onclick = function() {
                    if (selectedPages.includes(i)) {
                        // Kalau sudah ada, hapus dari array (Unselect)
                        selectedPages = selectedPages.filter(p => p !== i);
                        canvas.classList.remove('selected');
                    } else {
                        // Kalau belum ada, masukkan ke array (Select)
                        selectedPages.push(i);
                        canvas.classList.add('selected');
                    }
                    
                    // Urutkan halaman biar rapi (1, 2, 5...)
                    selectedPages.sort((a, b) => a - b);
                    
                    updateInput();
                };

                wrapper.appendChild(canvas);
                wrapper.appendChild(pageNum);
                container.appendChild(wrapper);
            }
        });

        function updateInput() {
            // Update nilai input hidden
            hiddenInput.value = selectedPages.join(',');
            
            // Aktifkan/Matikan tombol submit
            if (selectedPages.length > 0) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                submitBtn.innerText = `Pisahkan (${selectedPages.length} Halaman)`;
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
                submitBtn.innerText = 'Pisahkan Halaman Terpilih';
            }
        }
    </script>
    <div class="card">
        <h2>Riwayat Pengerjaan</h2>
        
@if($histories->isEmpty())
        <p style="text-align: center; color: #888;">Belum ada riwayat.</p>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Aksi</th>
                        <th>Keterangan</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $h)
                    <tr>
                        <td>
                            <span style="background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">
                                {{ $h->action_type }}
                            </span>
                        </td>
                        <td>{{ $h->original_name }}</td>
                        <td>{{ $h->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div> @endif
</body>
</html>