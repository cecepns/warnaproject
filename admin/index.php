<?php
/**
 * Admin Dashboard - Manage News
 * Kamala Weddings
 */

require_once __DIR__ . '/auth.php';
require_admin_login();

$admin = get_current_admin();
$pdo = getDB();

// Handle Flash Messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filter parameters
$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');

// Statistics queries
$totalNews = $pdo->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
$totalPublished = $pdo->query("SELECT COUNT(*) FROM `news` WHERE `status` = 'published'")->fetchColumn();
$totalDrafts = $pdo->query("SELECT COUNT(*) FROM `news` WHERE `status` = 'draft'")->fetchColumn();
$totalViews = $pdo->query("SELECT COALESCE(SUM(`views`), 0) FROM `news`")->fetchColumn();

// Get unique categories for filter dropdown
$categories = $pdo->query("SELECT DISTINCT `category` FROM `news` WHERE `category` != '' ORDER BY `category` ASC")->fetchAll(PDO::FETCH_COLUMN);

// Build query with filters
$query = "SELECT * FROM `news` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (`title` LIKE :search OR `excerpt` LIKE :search OR `category` LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $query .= " AND `category` = :category";
    $params[':category'] = $category;
}

if (!empty($status) && in_array($status, ['published', 'draft'])) {
    $query .= " AND `status` = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY `created_at` DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Management — Kamala Weddings Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/all.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                K A M A L A <span>ADMIN</span>
            </a>
            <span class="sidebar-badge">V1.0</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="active">
                <i class="fas fa-newspaper"></i>
                <span>News Management</span>
            </a>
            <a href="news-create.php">
                <i class="fas fa-plus-circle"></i>
                <span>Create New Article</span>
            </a>

            <div class="nav-section-title" style="margin-top:20px;">Quick Links</div>
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
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left" style="display:flex; align-items:center; gap:16px;">
                <button id="mobileMenuBtn" style="display:none; background:none; border:none; color:#fff; font-size:20px; cursor:pointer;">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>News & Articles Management</h1>
            </div>
            <div class="topbar-right">
                <a href="../news.php" target="_blank" class="btn-public-site">
                    <i class="fas fa-globe"></i> View Public News
                </a>
                <a href="news-create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Article
                </a>
            </div>
        </header>

        <!-- Content Area -->
        <main class="admin-content">

            <!-- Flash Messages -->
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success">
                    <span><i class="fas fa-check-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($flashSuccess) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="alert alert-danger">
                    <span><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($flashError) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalNews) ?></h3>
                        <p>Total Articles</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalPublished) ?></h3>
                        <p>Published Articles</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalDrafts) ?></h3>
                        <p>Draft Articles</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon gold">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalViews) ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>
            </div>

            <!-- News List Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Articles (<?= count($newsList) ?>)</h2>

                    <!-- Filter Form -->
                    <form method="GET" action="index.php" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="width:200px; padding:7px 12px; font-size:13px;" placeholder="Search title...">
                        
                        <select name="category" class="form-control" style="width:160px; padding:7px 12px; font-size:13px;" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status" class="form-control" style="width:140px; padding:7px 12px; font-size:13px;" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>

                        <?php if (!empty($search) || !empty($category) || !empty($status)): ?>
                            <a href="index.php" class="btn btn-secondary btn-sm" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Cover</th>
                                <th>Article Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Date</th>
                                <th style="text-align: right; width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newsList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 48px 20px; color:var(--text-muted);">
                                        <i class="fas fa-newspaper" style="font-size:36px; margin-bottom:12px; display:block; opacity:0.3;"></i>
                                        No articles found. Click <strong>Add Article</strong> above to create your first story!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($newsList as $item): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                                $thumbSrc = !empty($item['image']) ? '../' . $item['image'] : '../img/image.png';
                                            ?>
                                            <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="Cover" class="table-thumb" onerror="this.src='../img/image.png'">
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#fff; margin-bottom:4px; max-width:320px;">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </div>
                                            <div style="font-size:12px; color:var(--text-muted); font-family:monospace;">
                                                /<?= htmlspecialchars($item['slug']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-category"><?= htmlspecialchars($item['category']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($item['status'] === 'published'): ?>
                                                <span class="badge badge-published"><i class="fas fa-circle" style="font-size:7px; margin-right:4px;"></i> Published</span>
                                            <?php else: ?>
                                                <span class="badge badge-draft"><i class="fas fa-clock" style="font-size:9px; margin-right:4px;"></i> Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color:var(--text-muted); font-size:13px;">
                                                <i class="fas fa-eye" style="margin-right:4px; color:var(--accent-gold);"></i> <?= number_format($item['views']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:var(--text-muted);">
                                                <?= date('M d, Y', strtotime($item['published_at'] ?? $item['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end;">
                                                <a href="../news-detail.php?slug=<?= urlencode($item['slug']) ?>" target="_blank" class="action-btn" title="View Article on Site">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="news-edit.php?id=<?= $item['id'] ?>" class="action-btn" title="Edit Article">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" title="Delete Article" onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['title'])) ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; max-width:440px; width:90%; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,0.6);">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="width:54px; height:54px; background:rgba(239, 68, 68, 0.15); color:var(--danger); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:12px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="color:#fff; font-size:18px; margin-bottom:8px;">Delete Article?</h3>
            <p style="color:var(--text-muted); font-size:13px; line-height:1.5;">Are you sure you want to permanently delete <strong id="deleteArticleTitle" style="color:#fff;"></strong>? This action cannot be undone.</p>
        </div>

        <form method="POST" action="news-delete.php" id="deleteForm" style="display:flex; gap:12px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="id" id="deleteArticleId" value="">
            <button type="button" class="btn btn-secondary" style="flex:1; justify-content:center;" onclick="closeDeleteModal()">Cancel</button>
            <button type="submit" class="btn btn-danger" style="flex:1; justify-content:center;"><i class="fas fa-trash-alt"></i> Delete</button>
        </form>
    </div>
</div>

<script>
    function confirmDelete(id, title) {
        document.getElementById('deleteArticleId').value = id;
        document.getElementById('deleteArticleTitle').textContent = '"' + title + '"';
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    window.onclick = function(e) {
        const modal = document.getElementById('deleteModal');
        if (e.target === modal) {
            closeDeleteModal();
        }
    }
</script>

</body>
</html>
