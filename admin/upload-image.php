<?php
/**
 * Quill.js Image Upload Handler
 * Receives file from Quill editor, saves to disk, returns clean URL.
 * Prevents base64 image bloat in news content.
 */

session_start();

header('Content-Type: application/json');

// 1. Authentication check
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login first.'
    ]);
    exit;
}

// 2. Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMsg = 'No file uploaded or upload error occurred.';
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'Image file exceeds maximum upload size.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'Image file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'No image file was received.';
                break;
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

$file = $_FILES['image'];

// 3. Size validation (max 8MB)
$maxSize = 8 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Image size exceeds maximum limit of 8MB.'
    ]);
    exit;
}

// 4. MIME type validation using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

if (!array_key_exists($mimeType, $allowedMimes)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF are allowed.'
    ]);
    exit;
}

$ext = $allowedMimes[$mimeType];

// 5. Target directory
$targetDir = __DIR__ . '/../uploads/news/editor/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// 6. Generate unique filename
$filename = 'news_editor_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $targetDir . $filename;

// 7. Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Relative URL for front-end consumption
    $publicUrl = 'uploads/news/editor/' . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $publicUrl,
        'filename' => $filename,
        'message' => 'Image uploaded successfully.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move uploaded file to destination directory.'
    ]);
}
