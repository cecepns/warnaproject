<?php
/**
 * Edit Article
 * Kamala Weddings Admin
 */

require_once __DIR__ . '/auth.php';
require_admin_login();

$admin = get_current_admin();
$pdo = getDB();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    $_SESSION['flash_error'] = 'Invalid article ID.';
    header('Location: index.php');
    exit;
}

// Fetch article
$stmt = $pdo->prepare("SELECT * FROM `news` WHERE `id` = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();

if (!$article) {
    $_SESSION['flash_error'] = 'Article not found.';
    header('Location: index.php');
    exit;
}

$error = '';
$title    = $article['title'];
$slug     = $article['slug'];
$category = $article['category'];
$author   = $article['author'];
$status   = $article['status'];
$excerpt  = $article['excerpt'];
$content  = $article['content'];
$image    = $article['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submittedToken)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title    = trim($_POST['title'] ?? '');
        $slug     = trim($_POST['slug'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $customCategory = trim($_POST['custom_category'] ?? '');
        if (!empty($customCategory)) {
            $category = $customCategory;
        }
        $author   = trim($_POST['author'] ?? 'Kamala Weddings');
        $status   = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';
        $excerpt  = trim($_POST['excerpt'] ?? '');
        $content  = trim($_POST['content'] ?? '');

        if (empty($title)) {
            $error = 'Article title is required.';
        } elseif (empty($content)) {
            $error = 'Article content cannot be empty.';
        } else {
            if (empty($slug)) {
                $slug = slugify($title);
            } else {
                $slug = slugify($slug);
            }

            // Ensure unique slug (excluding this article)
            $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `slug` = :slug AND `id` != :id");
            $slugCheck->execute([':slug' => $slug, ':id' => $id]);
            if ($slugCheck->fetchColumn() > 0) {
                $slug .= '-' . time();
            }

            // Handle featured image upload
            $newImage = $image;
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $newImage = upload_featured_image($_FILES['featured_image'], $image);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                if (!empty($image) && strpos($image, 'uploads/news/') === 0 && file_exists(__DIR__ . '/../' . $image)) {
                    @unlink(__DIR__ . '/../' . $image);
                }
                $newImage = '';
            }

            if (empty($error)) {
                // Double safety: eliminate any base64 images and save them as physical files
                $cleanedContent = sanitize_content_images($content);

                if (empty($excerpt)) {
                    $plainText = strip_tags($cleanedContent);
                    $excerpt = mb_substr($plainText, 0, 160) . '...';
                }

                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE `news` SET
                            `title` = :title,
                            `slug` = :slug,
                            `category` = :category,
                            `author` = :author,
                            `image` = :image,
                            `excerpt` = :excerpt,
                            `content` = :content,
                            `status` = :status,
                            `updated_at` = NOW()
                        WHERE `id` = :id
                    ");
                    $updateStmt->execute([
                        ':title'    => $title,
                        ':slug'     => $slug,
                        ':category' => $category,
                        ':author'   => $author,
                        ':image'    => $newImage,
                        ':excerpt'  => $excerpt,
                        ':content'  => $cleanedContent,
                        ':status'   => $status,
                        ':id'       => $id
                    ]);

                    $_SESSION['flash_success'] = 'Article "' . htmlspecialchars($title) . '" has been updated successfully!';
                    header('Location: index.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article: <?= htmlspecialchars($article['title']) ?> — Kamala Weddings Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/all.css">
    <!-- Quill.js Snow Theme CSS -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                K A M A L A <span>ADMIN</span>
            </a>
            <span class="sidebar-badge">V1.0</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php">
                <i class="fas fa-newspaper"></i>
                <span>News Management</span>
            </a>
            <a href="news-create.php">
                <i class="fas fa-plus-circle"></i>
                <span>Create New Article</span>
            </a>

            <div class="nav-section-title" style="margin-top:20px;">Quick Links</div>
            <a href="../news-detail.php?slug=<?= urlencode($article['slug']) ?>" target="_blank">
                <i class="fas fa-eye"></i>
                <span>Preview Current Article</span>
            </a>
            <a href="../news.php" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>Public News Page</span>
            </a>
            <a href="../index.html" target="_blank">
                <i class="fas fa-home"></i>
                <span>Main Website</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h5><?= htmlspecialchars($admin['name']) ?></h5>
                    <span>@<?= htmlspecialchars($admin['username']) ?></span>
                </div>
            </div>
            <a href="logout.php" title="Sign Out" style="color:var(--danger); font-size:16px;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1>Edit Article #<?= $id ?></h1>
            </div>
            <div class="topbar-right">
                <a href="../news-detail.php?slug=<?= urlencode($article['slug']) ?>" target="_blank" class="btn-public-site">
                    <i class="fas fa-eye"></i> View on Site
                </a>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to News
                </a>
            </div>
        </header>

        <main class="admin-content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="news-edit.php?id=<?= $id ?>" enctype="multipart/form-data" id="newsForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="content" id="hiddenContent">
                <input type="hidden" name="remove_image" id="removeImageInput" value="0">

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px;">
                    
                    <!-- Left Column: Title & Editor -->
                    <div>
                        <!-- Title Field -->
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label" for="title">Article Title <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required style="font-size:16px; font-weight:600;">
                                </div>

                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" for="slug">URL Slug</label>
                                    <div style="display:flex; align-items:center;">
                                        <span style="background:var(--bg-card-hover); border:1px solid var(--border-color); border-right:none; border-top-left-radius:8px; border-bottom-left-radius:8px; padding:10px 12px; color:var(--text-muted); font-size:13px;">
                                            news/
                                        </span>
                                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($slug) ?>" style="border-top-left-radius:0; border-bottom-left-radius:0; font-family:monospace; font-size:13px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quill.js Rich Text Editor -->
                        <div class="card">
                            <div class="card-header" style="justify-content:space-between;">
                                <h3 class="card-title"><i class="fas fa-edit" style="color:var(--accent-gold); margin-right:8px;"></i> Article Content <span class="req">*</span></h3>
                                <span style="font-size:12px; color:var(--accent-gold); background:rgba(197,168,128,0.1); padding:4px 10px; border-radius:4px;">
                                    <i class="fas fa-cloud-upload-alt"></i> Images upload directly to server (No Base64)
                                </span>
                            </div>
                            <div class="card-body" style="padding:16px;">
                                <div id="quillEditor"><?= $content ?></div>
                            </div>
                        </div>

                        <!-- Excerpt Field -->
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group" style="margin-bottom:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <label class="form-label" for="excerpt" style="margin-bottom:0;">Short Excerpt / Meta Summary</label>
                                        <span id="excerptCount" style="font-size:11px; color:var(--text-muted);"><?= mb_strlen($excerpt) ?> / 200</span>
                                    </div>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($excerpt) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings, Featured Image & Status -->
                    <div>
                        <!-- Publishing Status & Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Publish Settings</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <div style="display:flex; gap:16px;">
                                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                            <input type="radio" name="status" value="published" <?= $status === 'published' ? 'checked' : '' ?>>
                                            <span style="font-size:14px; color:#34d399; font-weight:600;"><i class="fas fa-check-circle"></i> Published</span>
                                        </label>
                                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                            <input type="radio" name="status" value="draft" <?= $status === 'draft' ? 'checked' : '' ?>>
                                            <span style="font-size:14px; color:#9ca3af; font-weight:500;"><i class="fas fa-clock"></i> Draft</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="author">Author Name</label>
                                    <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($author) ?>">
                                </div>

                                <div class="form-group" style="font-size:12px; color:var(--text-muted); margin-bottom:15px; border-top:1px solid var(--border-color); padding-top:12px;">
                                    <div><strong>Created:</strong> <?= date('d M Y, H:i', strtotime($article['created_at'])) ?></div>
                                    <div><strong>Views:</strong> <?= number_format($article['views']) ?> views</div>
                                </div>

                                <div style="display:flex; gap:10px; margin-top:20px;">
                                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                                        <i class="fas fa-save"></i> Update Article
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </div>

                        <!-- Category Selection -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Category</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label" for="categorySelect">Current Category</label>
                                    <select class="form-control" id="categorySelect" name="category">
                                        <option value="Wedding Tips" <?= $category === 'Wedding Tips' ? 'selected' : '' ?>>Wedding Tips</option>
                                        <option value="Pre-Wedding" <?= $category === 'Pre-Wedding' ? 'selected' : '' ?>>Pre-Wedding</option>
                                        <option value="Inspiration" <?= $category === 'Inspiration' ? 'selected' : '' ?>>Inspiration</option>
                                        <option value="Venues & Guides" <?= $category === 'Venues & Guides' ? 'selected' : '' ?>>Venues & Guides</option>
                                        <option value="Photography Story" <?= $category === 'Photography Story' ? 'selected' : '' ?>>Photography Story</option>
                                        <option value="News & Updates" <?= $category === 'News & Updates' ? 'selected' : '' ?>>News & Updates</option>
                                        <?php if (!in_array($category, ['Wedding Tips', 'Pre-Wedding', 'Inspiration', 'Venues & Guides', 'Photography Story', 'News & Updates'])): ?>
                                            <option value="<?= htmlspecialchars($category) ?>" selected><?= htmlspecialchars($category) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" for="customCategory">Or Change to Custom</label>
                                    <input type="text" class="form-control" id="customCategory" name="custom_category" placeholder="Type new category...">
                                </div>
                            </div>
                        </div>

                        <!-- Featured Image Upload -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Featured Image (Cover)</h3>
                            </div>
                            <div class="card-body">
                                <?php $hasImage = !empty($image); ?>
                                <div class="image-upload-wrapper" id="uploadWrapper" style="<?= $hasImage ? 'display:none;' : '' ?>" onclick="document.getElementById('featuredImageInput').click()">
                                    <i class="fas fa-image" style="font-size:36px; color:var(--accent-gold); margin-bottom:12px; display:block;"></i>
                                    <p style="font-size:13px; color:#fff; font-weight:600; margin-bottom:4px;">Click or Drag to Upload Cover</p>
                                    <span style="font-size:11px; color:var(--text-muted);">JPG, PNG, WEBP (Max 10MB)</span>
                                    <input type="file" id="featuredImageInput" name="featured_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                </div>

                                <div class="image-preview-container <?= $hasImage ? 'has-image' : '' ?>" id="previewContainer">
                                    <img id="previewImg" src="<?= $hasImage ? '../' . htmlspecialchars($image) : '' ?>" alt="Cover Preview" onerror="this.src='../img/image.png'">
                                    <div style="margin-top:12px; display:flex; gap:8px;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('featuredImageInput').click()">
                                            <i class="fas fa-sync-alt"></i> Change Image
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFeaturedImage()">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Quill.js CDN -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<!-- Custom Quill Upload Handler (Prevents Base64) -->
<script src="js/quill-upload.js"></script>

<script>
    // Excerpt Character Counter
    const excerptInput = document.getElementById('excerpt');
    const excerptCount = document.getElementById('excerptCount');
    excerptInput.addEventListener('input', function() {
        excerptCount.textContent = this.value.length + ' / 200';
    });

    // Featured Image Preview & Controls
    const featuredInput = document.getElementById('featuredImageInput');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    const uploadWrapper = document.getElementById('uploadWrapper');
    const removeImageInput = document.getElementById('removeImageInput');

    featuredInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.add('has-image');
                uploadWrapper.style.display = 'none';
                removeImageInput.value = '0';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    function removeFeaturedImage() {
        featuredInput.value = '';
        previewImg.src = '';
        previewContainer.classList.remove('has-image');
        uploadWrapper.style.display = 'block';
        removeImageInput.value = '1';
    }
</script>

</body>
</html>
