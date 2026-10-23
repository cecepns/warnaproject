<?php
/**
 * Admin News Delete Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    $_SESSION['flash_error'] = 'Invalid security token. Please try again.';
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_error'] = 'Invalid article ID.';
    header('Location: index.php');
    exit;
}

try {
    $pdo = getDB();
    
    // Check if article exists
    $stmt = $pdo->prepare("SELECT `id`, `title`, `image` FROM `news` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if ($news) {
        // Delete physical featured image if it was uploaded
        if (!empty($news['image']) && strpos($news['image'], 'uploads/news/') === 0) {
            $imagePath = __DIR__ . '/../' . $news['image'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        // Delete from database
        $delStmt = $pdo->prepare("DELETE FROM `news` WHERE `id` = :id");
        $delStmt->execute([':id' => $id]);

        $_SESSION['flash_success'] = 'Article "' . htmlspecialchars($news['title']) . '" has been successfully deleted.';
    } else {
        $_SESSION['flash_error'] = 'Article not found.';
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: index.php');
exit;
