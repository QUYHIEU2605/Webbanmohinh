const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('anh');
        const preview = document.getElementById('preview');
        let fileList = []; // Danh sách các file đã chọn
        const tenhangSelect = document.getElementById('tenhang');
        const popup = document.getElementById('popup');
        const popupOverlay = document.getElementById('popup-overlay');
        const closePopupBtn = document.getElementById('close-popup');

        // Xử lý khi người dùng nhấn vào dropArea
        dropArea.addEventListener('click', () => fileInput.click());

        // Xử lý kéo thả file vào dropArea
        dropArea.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropArea.classList.add('dragover');
        });

        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));

        dropArea.addEventListener('drop', (event) => {
            event.preventDefault();
            dropArea.classList.remove('dragover');

            const files = Array.from(event.dataTransfer.files);
            handleFiles(files);
        });

        // Xử lý khi chọn file qua input
        fileInput.addEventListener('change', () => {
            const files = Array.from(fileInput.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            files.forEach(file => {
                if (!fileList.some(existingFile => existingFile.name === file.name)) { // Kiểm tra trùng lặp
                    fileList.push(file);
                    displayPreview(file);
                }
            });
            updateFileInput();
        }

        function displayPreview(file) {
            const reader = new FileReader();
            reader.onload = () => {
                const imgContainer = document.createElement('div');
                imgContainer.style.position = 'relative';
                imgContainer.style.display = 'inline-block';
                imgContainer.style.margin = '5px';

                const img = document.createElement('img');
                img.src = reader.result;
                img.className = 'preview-img';
                img.style.maxWidth = '100px';
                img.style.maxHeight = '100px';
                img.style.border = '1px solid #ccc';

                const removeBtn = document.createElement('button');
                removeBtn.textContent = 'X';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '0';
                removeBtn.style.right = '0';
                removeBtn.style.backgroundColor = 'red';
                removeBtn.style.color = 'white';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.width = '20px';
                removeBtn.style.height = '20px';

                removeBtn.addEventListener('click', () => {
                    const index = fileList.indexOf(file);
                    if (index > -1) {
                        fileList.splice(index, 1); // Loại bỏ file khỏi danh sách
                        imgContainer.remove(); // Loại bỏ phần preview
                        updateFileInput();
                    }
                });

                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                preview.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            fileList.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files; // Cập nhật lại input file với danh sách file hiện tại
        }
        tenhangSelect.addEventListener('change', () => {
            if (tenhangSelect.value === 'add_new') {
                popup.classList.add('active');
                popupOverlay.classList.add('active');
            }
        });

        closePopupBtn.addEventListener('click', () => {
            popup.classList.remove('active');
            popupOverlay.classList.remove('active');
            tenhangSelect.value = ''; // Reset dropdown
        });

        popupOverlay.addEventListener('click', () => {
            popup.classList.remove('active');
            popupOverlay.classList.remove('active');
            tenhangSelect.value = ''; // Reset dropdown
        });
        