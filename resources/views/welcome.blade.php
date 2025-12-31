<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Tools Pro - Laravel</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <style>
        /* Style untuk List Antrian (Sortable) */
        .queue-list { 
            list-style: none; padding: 0; margin-bottom: 20px; 
            border: 1px solid #ddd; min-height: 50px; 
            border-radius: 8px; background: #fff; 
        }
        .queue-item { 
            padding: 12px; border-bottom: 1px solid #eee; 
            display: flex; justify-content: space-between; align-items: center; 
            background: white; transition: background 0.2s;
        }
        .queue-item:last-child { border-bottom: none; }
        
        /* Efek saat item di-drag */
        .sortable-ghost { background: #f0f4f8; opacity: 0.6; border: 1px dashed #3490dc; }
        
        /* Icon Drag Handle */
        .drag-handle { cursor: move; color: #adb5bd; margin-right: 12px; font-size: 1.2rem; }
        .drag-handle:hover { color: #495057; }
        
        /* Container Tombol Atas */
        .action-buttons { margin-bottom: 15px; display: flex; gap: 10px; align-items: center; }

        /* Tombol Tambah File */
        .add-file-btn { 
            cursor: pointer; background: #6c757d; color: white; 
            padding: 8px 16px; border-radius: 6px; display: inline-block; 
            font-weight: 500; transition: 0.2s; margin: 0; font-size: 0.9rem;
        }
        .add-file-btn:hover { background: #5a6268; opacity: 0.9; }

        /* Tombol Clear All */
        .clear-all-btn {
            background: none; border: 1px solid #e3342f; color: #e3342f;
            padding: 7px 12px; border-radius: 6px; cursor: pointer;
            font-size: 0.85rem; transition: 0.2s;
        }
        .clear-all-btn:hover { background: #e3342f; color: white; }

        /* Dropdown Orientasi */
        .orientation-select {
            padding: 6px; border-radius: 4px; border: 1px solid #ced4da;
            margin-right: 10px; font-size: 0.85rem; color: #495057;
        }

        /* --- DROP ZONE STYLE (Untuk Splitter) --- */
        .drop-zone {
            border: 2px dashed #cbd5e0; border-radius: 8px;
            padding: 30px; text-align: center; background: #f8fafc;
            transition: all 0.2s; cursor: pointer; position: relative;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #3490dc; background: #ebf8ff;
        }
        .drop-zone p { margin: 0; color: #606f7b; pointer-events: none; }

        /* Preview Container Splitter */
        #preview-container {
            display: flex; flex-wrap: wrap; gap: 15px; 
            margin-top: 20px; justify-content: center;
            padding: 10px; background: #f8fafc; border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>PDF Tools Pro</h1>
@if(session('error'))
        <div style="background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <strong>Terjadi Kesalahan:</strong><br>
            {{ session('error') }}
        </div>
    @endif
    
    <div class="card">
        <h2>Gabungkan PDF (Merge)</h2>
        <p>Tarik garis tiga (☰) untuk mengatur urutan file. Gunakan tombol "Hapus Semua" untuk reset.</p>
        
        <form id="mergeForm" action="{{ route('pdf.merge') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="action-buttons">
                <label for="mergeInputSource" class="add-file-btn" style="background: #3490dc;">
                    + Tambah PDF
                </label>
                <button type="button" class="clear-all-btn" onclick="clearAll('merge', 'mergeList', 'mergeEmpty', 'btnMerge')">
                    Hapus Semua
                </button>
            </div>

            <input type="file" id="mergeInputSource" multiple accept=".pdf" style="display: none;">

            <ul id="mergeList" class="queue-list">
                <li style="padding: 20px; text-align: center; color: #aaa;" id="mergeEmpty">
                    Belum ada file.
                </li>
            </ul>

            <input type="file" name="files[]" id="mergeFinalInput" multiple style="display: none;">
            <div id="hiddenOrientations"></div>

            <button type="submit" id="btnMerge" disabled>Gabungkan & Download</button>
        </form>
    </div>

    <div class="card">
        <h2>Ubah Gambar ke PDF</h2>
        <p>Urutkan gambar sesuai keinginan. Ukuran PDF mengikuti gambar asli.</p>
        
        <form id="imgForm" action="{{ route('pdf.image') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="action-buttons">
                <label for="imgInputSource" class="add-file-btn" style="background: #38c172;">
                    + Tambah Gambar
                </label>
                <button type="button" class="clear-all-btn" onclick="clearAll('img', 'imgList', 'imgEmpty', 'btnImg')">
                    Hapus Semua
                </button>
            </div>

            <input type="file" id="imgInputSource" multiple accept="image/*" style="display: none;">
            
            <ul id="imgList" class="queue-list">
                <li style="padding: 20px; text-align: center; color: #aaa;" id="imgEmpty">
                    Belum ada gambar.
                </li>
            </ul>

            <input type="file" name="images[]" id="imgFinalInput" multiple style="display: none;">
            <button type="submit" id="btnImg" style="background-color: #38c172;" disabled>Konversi ke PDF</button>
        </form>
    </div>

    <div class="card">
        <h2>Pisahkan Halaman PDF (Splitter)</h2>
        <p>Drop file PDF di kotak bawah, lalu <b>klik halaman</b> yang ingin diambil.</p>
        
        <form action="{{ route('pdf.split') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div id="splitDropZone" class="drop-zone">
                <p>📂 Klik atau Tarik File PDF ke Sini</p>
                <input type="file" name="file" id="pdf-upload" accept=".pdf" 
                       style="opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer;" required>
            </div>

            <input type="hidden" name="selected_pages" id="selected-pages-input">
            
            <div id="preview-container"></div>
            
            <br>
            <button type="submit" id="btn-split" style="background-color: #e3342f; opacity: 0.5; cursor: not-allowed;" disabled>
                Pisahkan Halaman Terpilih
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    </script>

    <script>
        // ============================================
        // BAGIAN 1 & 2: QUEUE SYSTEM (Merge & Image)
        // ============================================
        // Array Global
        let mergeQueue = []; 
        let imgQueue = [];

        // Init SortableJS
        function initSortable(listId) {
            new Sortable(document.getElementById(listId), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.drag-handle',
            });
        }
        initSortable('mergeList');
        initSortable('imgList');

        // Event Listeners Input
        document.getElementById('mergeInputSource').addEventListener('change', function(e) {
            addFilesToQueue(Array.from(e.target.files), mergeQueue, 'mergeList', 'mergeEmpty', 'btnMerge', true);
            this.value = ''; 
        });
        document.getElementById('imgInputSource').addEventListener('change', function(e) {
            addFilesToQueue(Array.from(e.target.files), imgQueue, 'imgList', 'imgEmpty', 'btnImg', false);
            this.value = '';
        });

        // Helper: Tambah ke HTML
        function addFilesToQueue(newFiles, queueArray, listId, emptyId, btnId, showOrientation) {
            const listEl = document.getElementById(listId);
            const emptyEl = document.getElementById(emptyId);
            const btnEl = document.getElementById(btnId);

            if (emptyEl) emptyEl.style.display = 'none';
            btnEl.disabled = false;
            btnEl.style.opacity = '1';

            newFiles.forEach(file => {
                const uniqueId = Date.now() + Math.random().toString(36).substr(2, 9);
                queueArray.push({ id: uniqueId, file: file });

                const li = document.createElement('li');
                li.className = 'queue-item';
                li.setAttribute('data-id', uniqueId);

                let orientationHtml = '';
                if(showOrientation) {
                    orientationHtml = `
                        <select class="orientation-select" title="Orientasi Halaman">
                            <option value="auto">Auto</option>
                            <option value="P">Portrait</option>
                            <option value="L">Landscape</option>
                        </select>
                    `;
                }

                li.innerHTML = `
                    <div style="display:flex; align-items:center; overflow:hidden;">
                        <span class="drag-handle">☰</span>
                        <div style="display:flex; flex-direction:column;">
                            <span style="font-weight:500; font-size:0.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 250px;">${file.name}</span>
                            <span style="font-size:0.75rem; color:#888;">${(file.size/1024).toFixed(1)} KB</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center;">
                        ${orientationHtml}
                        <button type="button" onclick="removeFile(this, '${listId}', '${emptyId}', '${btnId}')" style="background:none; border:none; color:#e3342f; cursor:pointer; font-size:1.2rem; margin-left:5px;">&times;</button>
                    </div>
                `;
                listEl.appendChild(li);
            });
        }

        // Helper: Hapus Satu File
        window.removeFile = function(btn, listId, emptyId, btnId) {
            const li = btn.closest('li');
            const id = li.getAttribute('data-id');
            
            // Hapus dari array global
            let foundInMerge = mergeQueue.findIndex(q => q.id === id);
            if(foundInMerge !== -1) mergeQueue.splice(foundInMerge, 1);
            
            let foundInImg = imgQueue.findIndex(q => q.id === id);
            if(foundInImg !== -1) imgQueue.splice(foundInImg, 1);

            li.remove();
            checkEmpty(listId, emptyId, btnId);
        };

        // Helper: Clear All (BARU)
        window.clearAll = function(type, listId, emptyId, btnId) {
            if(!confirm('Yakin ingin menghapus semua file dari daftar?')) return;

            // 1. Kosongkan Array
            if(type === 'merge') mergeQueue = [];
            if(type === 'img') imgQueue = [];

            // 2. Kosongkan HTML List (Hapus semua li class queue-item)
            const listEl = document.getElementById(listId);
            const items = listEl.querySelectorAll('.queue-item');
            items.forEach(item => item.remove());

            // 3. Reset State
            checkEmpty(listId, emptyId, btnId);
        }

        // Cek apakah kosong
        function checkEmpty(listId, emptyId, btnId) {
            const listEl = document.getElementById(listId);
            // Cek apakah ada item tersisa
            if (listEl.querySelectorAll('.queue-item').length === 0) {
                document.getElementById(emptyId).style.display = 'block';
                const btnEl = document.getElementById(btnId);
                btnEl.disabled = true;
                btnEl.style.opacity = '0.5';
            }
        }


        // Submit Logic
        function processQueueOnSubmit(listId, queueArray, inputId, orientationContainerId) {
            const listItems = document.querySelectorAll(`#${listId} .queue-item`);
            const dataTransfer = new DataTransfer();
            let orientationHtml = '';
            
            listItems.forEach(li => {
                const id = li.getAttribute('data-id');
                const fileObj = queueArray.find(q => q.id === id);
                if (fileObj) {
                    dataTransfer.items.add(fileObj.file);
                    if (orientationContainerId) {
                        const select = li.querySelector('.orientation-select');
                        const value = select ? select.value : 'auto';
                        orientationHtml += `<input type="hidden" name="orientations[]" value="${value}">`;
                    }
                }
            });

            document.getElementById(inputId).files = dataTransfer.files;
            if (orientationContainerId) {
                document.getElementById(orientationContainerId).innerHTML = orientationHtml;
            }
        }

        document.getElementById('mergeForm').addEventListener('submit', () => processQueueOnSubmit('mergeList', mergeQueue, 'mergeFinalInput', 'hiddenOrientations'));
        document.getElementById('imgForm').addEventListener('submit', () => processQueueOnSubmit('imgList', imgQueue, 'imgFinalInput', null));

        
        // ============================================
        // BAGIAN 3: SPLITTER
        // ============================================
        const splitFileInput = document.getElementById('pdf-upload');
        const splitZone = document.getElementById('splitDropZone');
        const splitContainer = document.getElementById('preview-container');
        const splitHiddenInput = document.getElementById('selected-pages-input');
        const splitBtn = document.getElementById('btn-split');
        let splitSelected = [];

        splitZone.addEventListener('dragover', (e) => { e.preventDefault(); splitZone.classList.add('dragover'); });
        splitZone.addEventListener('dragleave', () => splitZone.classList.remove('dragover'));
        splitZone.addEventListener('drop', (e) => {
            e.preventDefault(); splitZone.classList.remove('dragover');
            if(e.dataTransfer.files.length > 0) {
                splitFileInput.files = e.dataTransfer.files;
                splitFileInput.dispatchEvent(new Event('change'));
            }
        });

        splitFileInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            splitZone.querySelector('p').innerText = "📄 " + file.name;
            splitContainer.innerHTML = '<p style="width:100%; text-align:center;">Memuat halaman...</p>';
            splitSelected = [];
            updateSplitInput();

            const uri = URL.createObjectURL(file);
            const pdf = await pdfjsLib.getDocument(uri).promise;
            splitContainer.innerHTML = ''; 

            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 0.2 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.className = 'page-thumbnail';
                await page.render({ canvasContext: context, viewport: viewport }).promise;

                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                const pageNum = document.createElement('span');
                pageNum.className = 'page-number';
                pageNum.innerText = i;

                canvas.onclick = function() {
                    if (splitSelected.includes(i)) {
                        splitSelected = splitSelected.filter(p => p !== i);
                        canvas.classList.remove('selected');
                    } else {
                        splitSelected.push(i);
                        canvas.classList.add('selected');
                    }
                    splitSelected.sort((a, b) => a - b);
                    updateSplitInput();
                };

                wrapper.appendChild(canvas);
                wrapper.appendChild(pageNum);
                splitContainer.appendChild(wrapper);
            }
        });

        function updateSplitInput() {
            splitHiddenInput.value = splitSelected.join(',');
            if (splitSelected.length > 0) {
                splitBtn.disabled = false;
                splitBtn.style.opacity = '1';
                splitBtn.style.cursor = 'pointer';
                splitBtn.innerText = `Pisahkan (${splitSelected.length} Halaman)`;
            } else {
                splitBtn.disabled = true;
                splitBtn.style.opacity = '0.5';
                splitBtn.style.cursor = 'not-allowed';
                splitBtn.innerText = 'Pisahkan Halaman Terpilih';
            }
        }

        // Drag & Drop visual effect
        bgZone.addEventListener('dragover', (e) => { e.preventDefault(); bgZone.classList.add('dragover'); });
        bgZone.addEventListener('dragleave', () => bgZone.classList.remove('dragover'));
        bgZone.addEventListener('drop', (e) => {
            e.preventDefault(); bgZone.classList.remove('dragover');
            if(e.dataTransfer.files.length > 0) {
                bgInput.files = e.dataTransfer.files;
                bgInput.dispatchEvent(new Event('change'));
            }
        });

        // Preview Logic
        bgInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                bgZone.querySelector('p').innerText = "✅ Foto Terpilih";
                bgName.innerText = file.name;
                bgImg.src = URL.createObjectURL(file);
                bgPreviewCont.style.display = 'block';
                bgBtn.disabled = false;
                bgBtn.style.opacity = '1';
                bgBtn.style.cursor = 'pointer';
            }
        });
    </script>
</body>
</html>