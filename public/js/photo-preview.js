document.addEventListener('DOMContentLoaded', function () {
    const photoInput = document.getElementById('photo-input');
    const photoPreview = document.getElementById('photo-preview');
    const previewContainer = document.getElementById('preview-container');

    if (photoInput && photoPreview && previewContainer) {
        photoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];

            if (file) {
                // 1. Cek Ukuran File (2MB = 2 * 1024 * 1024 Byte = 2.097.152 Byte)
                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('Ukuran berkas terlalu besar! Maksimal ukuran berkas adalah 2MB.');
                    photoInput.value = ''; // Reset pilihan berkas
                    previewContainer.classList.add('d-none');
                    return;
                }

                // 2. Cek Format/Tipe File
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format berkas tidak didukung! Gunakan format JPG, PNG, atau WEBP.');
                    alert('Format berkas tidak didukung! Gunakan format JPG, JPEG, atau PNG.');
                    photoInput.value = ''; // Reset pilihan berkas
                    previewContainer.classList.add('d-none');
                    return;
                }

                // 3. Jika Lolos Validasi, Tampilkan Pratinjau
                const reader = new FileReader();
                reader.onload = function (event) {
                    photoPreview.src = event.target.result;
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('d-none');
                photoPreview.src = '#';
            }
        });
    }
});