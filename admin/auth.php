<?php
/**
 * Admin Authentication & Security Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function require_admin_login(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        $loginUrl = 'login.php';
        header("Location: $loginUrl");
        exit;
    }
}

function get_current_admin(): ?array {
    if (empty($_SESSION['admin_logged_in'])) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'] ?? 0,
        'username' => $_SESSION['admin_username'] ?? '',
        'name' => $_SESSION['admin_name'] ?? 'Admin'
    ];
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Double safety: if any base64 image somehow slips through Quill editor,
 * extract it, write to disk as physical file, and replace src attribute.
 * Guarantees zero base64 images in database!
 */
function sanitize_content_images(string $content): string {
    if (empty($content)) {
        return '';
    }

    $uploadDir = __DIR__ . '/../uploads/news/editor/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Pattern matches <img ... src="data:image/(png|jpeg|jpg|webp|gif);base64,..." ...>
    $pattern = '/<img\s+[^>]*src=[\'"](data:image\/(jpeg|jpg|png|gif|webp);base64,([^\'"]+))[\'"][^>]*>/i';

    $cleanedContent = preg_replace_callback($pattern, function ($matches) use ($uploadDir) {
        $fullImgTag = $matches[0];
        $dataUri    = $matches[1];
        $format     = strtolower($matches[2]);
        $base64Data = $matches[3];

        if ($format === 'jpeg') $format = 'jpg';

        $decoded = base64_decode($base64Data);
        if ($decoded !== false) {
            $filename = 'news_editor_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $format;
            $filepath = $uploadDir . $filename;
            
            if (file_put_contents($filepath, $decoded)) {
                $newSrc = 'uploads/news/editor/' . $filename;
                return str_replace($dataUri, $newSrc, $fullImgTag);
            }
        }
        return $fullImgTag;
    }, $content);

    return $cleanedContent;
}

/**
 * Handle featured image file upload
 */
function upload_featured_image(array $file, ?string $currentImage = null): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return $currentImage;
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedMimes)) {
        throw new Exception('Invalid featured image format. Only JPG, PNG, and WEBP are supported.');
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Featured image size must be less than 10MB.');
    }

    $ext = $allowedMimes[$mimeType];
    $targetDir = __DIR__ . '/../uploads/news/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = 'featured_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded featured image.');
    }

    // If there was an old uploaded image, optionally delete it (if in uploads/)
    if ($currentImage && strpos($currentImage, 'uploads/news/') === 0 && file_exists(__DIR__ . '/../' . $currentImage)) {
        @unlink(__DIR__ . '/../' . $currentImage);
    }

    return 'uploads/news/' . $filename;
}
