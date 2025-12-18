/**
 * Filezone.mn - Main JavaScript
 * * Агуулга:
 * 1. Mobile Menu (Бүх хуудас)
 * 2. FAQ Toggle (help.php)
 * 3. Password Visibility (login.php, register.php)
 * 4. File Upload Preview (upload.php, add_service.php)
 * 5. Tab Switcher (profile.php)
 * 6. Image Gallery (service-details.php)
 */

/* 1. Mobile Menu Toggle */
function toggleMenu() {
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-menu-overlay');
    if (!menu || !overlay) return;

    if (menu.classList.contains('-translate-x-full')) {
        menu.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        menu.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Esc дарахад цэс хаах
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") toggleMenu();
});


/* 2. FAQ Accordion */
function toggleFaq(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    if (!content || !icon) return;

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}


/* 3. Password Visibility Toggle */
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}


/* 4. File Upload & Drag-Drop Logic */
// Энгийн input дээр файл сонгох үед
function handleFileSelect(input, previewId = 'preview-img', placeholderId = 'preview-placeholder') {
    if (input.files && input.files[0]) {
        showPreview(input.files[0], previewId, placeholderId);
    }
}

// Preview харуулах туслах функц
function showPreview(file, previewId, placeholderId) {
    const img = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    const fileNameDisplay = document.getElementById('file-name-display'); // Хэрэв файлын нэр харуулах бол

    if (file.type.startsWith('image/')) {
        // Зураг бол preview харуулах
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img) {
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            if (placeholder) placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    } else {
        // Зураг биш бол (PDF, DOCX г.м) зөвхөн нэрийг нь харуулах эсвэл icon солих
        if (fileNameDisplay) {
            fileNameDisplay.textContent = file.name;
            fileNameDisplay.classList.remove('hidden');
        }
    }
}

// Drag & Drop Init (Хуудас ачаалахад идэвхжүүлнэ)
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-upload');

    if (dropZone && fileInput) {
        // Drag over
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-active');
        });

        // Drag leave
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-active');
        });

        // Drop
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-active');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect(fileInput);
            }
        });
    }
});


/* 5. Tab Switcher (Profile хуудсанд) */
function openTab(evt, tabName) {
    // 1. Бүх агуулгыг (tab-content) нуух
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // 2. Бүх товчны (tab-btn) идэвхтэй загварыг арилгах (Reset)
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        // Active загварыг хасах (Цэнхэр дэвсгэр)
        tablinks[i].classList.remove("bg-brand-50", "text-brand-600");
        
        // Inactive загварыг нэмэх (Саарал текст, hover эффект)
        tablinks[i].classList.add("text-gray-600", "hover:bg-gray-100", "hover:text-gray-900");
    }

    // 3. Сонгосон агуулгыг харуулах
    var selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = "block";
    }

    // 4. Дарсан товчийг идэвхжүүлэх (Active болгох)
    if (evt) {
        // Inactive загварыг хасах
        evt.currentTarget.classList.remove("text-gray-600", "hover:bg-gray-100", "hover:text-gray-900");
        
        // Active загварыг нэмэх (Цэнхэр дэвсгэр, тод цэнхэр текст)
        evt.currentTarget.classList.add("bg-brand-50", "text-brand-600");
    }
}


/* 6. Image Gallery (Service Details хуудсанд) */
function changeMainImage(src) {
    const mainImage = document.getElementById('main-image');
    if (mainImage) {
        // Fade effect
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = src;
            mainImage.style.opacity = '1';
        }, 200);
    }
}