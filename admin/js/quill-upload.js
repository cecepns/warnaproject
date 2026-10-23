/**
 * Quill.js Image Upload Handler
 * Uploads images directly to the server via AJAX instead of converting to Base64.
 * Handles Toolbar Image click, Paste, and Drag-and-Drop.
 */

document.addEventListener('DOMContentLoaded', function () {
    const editorContainer = document.getElementById('quillEditor');
    const hiddenInput = document.getElementById('hiddenContent');
    const newsForm = document.getElementById('newsForm');

    if (!editorContainer || !hiddenInput) {
        return;
    }

    // Configure Quill Toolbar Options
    const toolbarOptions = [
        [{ 'header': [1, 2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['blockquote', 'code-block'],
        ['link', 'image', 'video'],
        ['clean']
    ];

    // Initialize Quill
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Write your story, news, or wedding advice here...',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // Replace Quill's default image handler
    const toolbar = quill.getModule('toolbar');
    toolbar.addHandler('image', function () {
        selectAndUploadImage(quill);
    });

    // Handle Image Paste (prevent Base64 paste)
    quill.root.addEventListener('paste', function (e) {
        const clipboardData = e.clipboardData || window.clipboardData;
        if (!clipboardData || !clipboardData.items) return;

        const items = clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const file = items[i].getAsFile();
                if (file) {
                    e.preventDefault();
                    uploadImageToServer(file, quill);
                    break;
                }
            }
        }
    });

    // Handle Image Drag & Drop (prevent Base64 drop)
    quill.root.addEventListener('drop', function (e) {
        const dataTransfer = e.dataTransfer;
        if (!dataTransfer || !dataTransfer.files || dataTransfer.files.length === 0) return;

        const file = dataTransfer.files[0];
        if (file && file.type.indexOf('image') !== -1) {
            e.preventDefault();
            uploadImageToServer(file, quill);
        }
    });

    // Form submission: sync Quill HTML to hidden input
    if (newsForm) {
        newsForm.addEventListener('submit', function (e) {
            let htmlContent = quill.root.innerHTML;

            // Check if empty editor (just <p><br></p>)
            if (quill.getText().trim().length === 0 && !htmlContent.includes('<img')) {
                htmlContent = '';
            }

            hiddenInput.value = htmlContent;
        });
    }

    /**
     * File input selector for Toolbar image click
     */
    function selectAndUploadImage(quillInstance) {
        const fileInput = document.createElement('input');
        fileInput.setAttribute('type', 'file');
        fileInput.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        fileInput.click();

        fileInput.onchange = function () {
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                uploadImageToServer(file, quillInstance);
            }
            document.body.removeChild(fileInput);
        };
    }

    /**
     * Upload Image file to server via AJAX
     */
    function uploadImageToServer(file, quillInstance) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showToast('Invalid file format. Please upload JPG, PNG, WEBP, or GIF.', 'error');
            return;
        }

        // Validate size (8MB)
        if (file.size > 8 * 1024 * 1024) {
            showToast('Image size exceeds 8MB limit.', 'error');
            return;
        }

        // Get cursor position or default to end
        const range = quillInstance.getSelection(true) || { index: quillInstance.getLength(), length: 0 };

        // Insert temporary upload indicator
        const loadingId = 'uploading-' + Date.now();
        showToast('Uploading image to server...', 'info');

        const formData = new FormData();
        formData.append('image', file);

        fetch('upload-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.url) {
                // Insert the server URL into Quill (clean URL, NO base64!)
                quillInstance.insertEmbed(range.index, 'image', data.url);
                quillInstance.setSelection(range.index + 1);
                showToast('Image uploaded and inserted successfully!', 'success');
            } else {
                throw new Error(data.message || 'Image upload failed');
            }
        })
        .catch(error => {
            console.error('Quill Image Upload Error:', error);
            showToast(error.message || 'Failed to upload image. Please try again.', 'error');
        });
    }

    /**
     * Toast notification helper
     */
    function showToast(message, type = 'info') {
        let toastContainer = document.getElementById('quillToastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'quillToastContainer';
            toastContainer.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.style.cssText = `
            pointer-events:auto;
            padding:12px 20px;
            border-radius:8px;
            font-size:14px;
            font-family:sans-serif;
            font-weight:500;
            color:#fff;
            box-shadow:0 10px 25px rgba(0,0,0,0.3);
            animation:slideUp 0.3s ease;
            display:flex;
            align-items:center;
            gap:10px;
            background:${type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6')};
        `;

        const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
        toast.innerHTML = `<span style="font-weight:bold">${icon}</span> <span>${message}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.4s ease';
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    }
});
