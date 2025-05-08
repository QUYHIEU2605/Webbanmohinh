const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('duongdan');
const preview = document.getElementById('preview');
const removeImagesInput = document.getElementById('remove_images');
  const newImagesInput = document.getElementById('new_images');
   let newImages = [];

  dropArea.addEventListener('click', () => {
     fileInput.click();
 });
dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
     dropArea.classList.add('dragover');
 });

dropArea.addEventListener('dragleave', () => {
    dropArea.classList.remove('dragover');
 });

dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
     dropArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
     handleFiles(files);
 });

 fileInput.addEventListener('change', () => {
    const files = fileInput.files;
    handleFiles(files);
 });
function handleFiles(files) {
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
             reader.onload = function(e) {
                const imagePath = e.target.result;
                addImagePreview(imagePath, file.name);
                 newImages.push(file);
                   updateNewImagesInput();
             }
            reader.readAsDataURL(file);
        }
     }
 }
   function addImagePreview(imagePath, filename) {
      const previewImageContainer = document.createElement('div');
       previewImageContainer.classList.add('preview-image-container');

        const img = document.createElement('img');
         img.src = imagePath;
         img.width = 50;
         img.height = 50;
         img.style.marginRight = '5px';

        const removeImage = document.createElement('span');
        removeImage.classList.add('remove-image');
        removeImage.dataset.src = imagePath; // Lưu đường dẫn đầy đủ
         removeImage.innerText = '×';

        previewImageContainer.appendChild(img);
        previewImageContainer.appendChild(removeImage);

        preview.appendChild(previewImageContainer);
     }
   preview.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-image')) {
            const container = e.target.closest('.preview-image-container');
            const imageSrc = e.target.dataset.src;
              let filename = imageSrc.substring(imageSrc.lastIndexOf('/') + 1);
             let removedImages = removeImagesInput.value ? removeImagesInput.value.split(',') : [];
             removedImages.push(filename);
              removeImagesInput.value = removedImages.join(',');
               container.remove();
        }
   });

   function updateNewImagesInput() {
      const dataTransfer = new DataTransfer();
         newImages.forEach(file => dataTransfer.items.add(file));
       newImagesInput.files = dataTransfer.files;
 }