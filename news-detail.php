<?php
/**
 * News Article Detail Page
 * Kamala Weddings
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDB();

$slug = trim($_GET['slug'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$article = null;

if (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM `news` WHERE `slug` = :slug AND `status` = 'published' LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch();
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM `news` WHERE `id` = :id AND `status` = 'published' LIMIT 1");
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch();
}

// 404 Check
if (!$article) {
    http_response_code(404);
} else {
    // Increment views count (using session flag to avoid spamming on simple refreshes)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $viewKey = 'viewed_news_' . $article['id'];
    if (empty($_SESSION[$viewKey])) {
        $_SESSION[$viewKey] = true;
        $viewStmt = $pdo->prepare("UPDATE `news` SET `views` = `views` + 1 WHERE `id` = :id");
        $viewStmt->execute([':id' => $article['id']]);
        $article['views'] += 1;
    }

    // Previous & Next Articles
    $prevStmt = $pdo->prepare("SELECT `title`, `slug` FROM `news` WHERE `status` = 'published' AND `id` < :id ORDER BY `id` DESC LIMIT 1");
    $prevStmt->execute([':id' => $article['id']]);
    $prevArticle = $prevStmt->fetch();

    $nextStmt = $pdo->prepare("SELECT `title`, `slug` FROM `news` WHERE `status` = 'published' AND `id` > :id ORDER BY `id` ASC LIMIT 1");
    $nextStmt->execute([':id' => $article['id']]);
    $nextArticle = $nextStmt->fetch();

    // Related Articles (3 from same category or newest)
    $relStmt = $pdo->prepare("
        SELECT * FROM `news` 
        WHERE `status` = 'published' AND `id` != :id 
        ORDER BY (`category` = :cat) DESC, `published_at` DESC 
        LIMIT 3
    ");
    $relStmt->execute([':id' => $article['id'], ':cat' => $article['category']]);
    $relatedArticles = $relStmt->fetchAll();
}

// Full current URL for social share
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/news-detail.php';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$currentUrl = ($isHttps ? "https" : "http") . "://" . $httpHost . $requestUri;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <?php if ($article): ?>
        <title><?= htmlspecialchars($article['title']) ?> — Kamala Weddings</title>
        <meta name="description" content="<?= htmlspecialchars($article['excerpt']) ?>">
        <meta name="author" content="<?= htmlspecialchars($article['author']) ?>">

        <!-- Open Graph Meta Tags -->
        <meta property="og:title" content="<?= htmlspecialchars($article['title']) ?>">
        <meta property="og:description" content="<?= htmlspecialchars($article['excerpt']) ?>">
        <meta property="og:type" content="article">
        <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
        <?php if (!empty($article['image'])): ?>
            <meta property="og:image" content="<?= htmlspecialchars($currentUrl . '/../' . $article['image']) ?>">
        <?php endif; ?>

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= htmlspecialchars($article['title']) ?>">
        <meta name="twitter:description" content="<?= htmlspecialchars($article['excerpt']) ?>">
    <?php else: ?>
        <title>Article Not Found — Kamala Weddings</title>
    <?php endif; ?>

    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon.png">
    <link rel="shortcut icon" href="img/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400&display=swap" rel="stylesheet">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        .article_hero {
            padding: 130px 0 40px;
            background: linear-gradient(180deg, #0a0b0e 0%, #12141a 100%);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .breadcrumb_wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #8b92a0;
            margin-bottom: 20px;
        }
        .breadcrumb_wrap a {
            color: #8b92a0;
            text-decoration: none;
            transition: color 0.2s;
        }
        .breadcrumb_wrap a:hover {
            color: #c5a880;
        }
        .article_category_tag {
            display: inline-block;
            background: rgba(197, 168, 128, 0.15);
            color: #c5a880;
            border: 1px solid rgba(197, 168, 128, 0.3);
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .article_title {
            font-size: 34px;
            font-weight: 700;
            color: #fff;
            line-height: 1.35;
            margin-bottom: 20px;
        }
        @media (min-width: 768px) {
            .article_title {
                font-size: 42px;
            }
        }
        .article_meta_bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 13px;
            color: #9ca3af;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .article_meta_bar span i {
            color: #c5a880;
            margin-right: 6px;
        }

        /* Detail Section */
        .article_main_section {
            background-color: #0c0e12;
            padding: 50px 0 90px;
        }
        .featured_img_wrapper {
            margin-bottom: 40px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #272c38;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            background: #171a21;
        }
        .featured_img_wrapper img {
            width: 100%;
            max-height: 540px;
            object-fit: cover;
            display: block;
        }

        /* Rich Article Content Typography */
        .article_body_content {
            font-size: 16px;
            line-height: 1.85;
            color: #d1d5db;
        }
        .article_body_content p {
            margin-bottom: 24px;
            color: #d1d5db;
            font-size: 16px;
            line-height: 1.85;
        }
        .article_body_content h2 {
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            margin-top: 40px;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .article_body_content h3 {
            font-size: 21px;
            font-weight: 600;
            color: #f3f4f6;
            margin-top: 32px;
            margin-bottom: 14px;
        }
        .article_body_content blockquote {
            margin: 32px 0;
            padding: 24px 28px;
            background: #171a21;
            border-left: 4px solid #c5a880;
            border-radius: 0 10px 10px 0;
            font-style: italic;
            font-size: 18px;
            color: #EBE0D8;
            line-height: 1.6;
        }
        .article_body_content blockquote p {
            margin-bottom: 0;
            color: #EBE0D8;
        }
        .article_body_content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 28px 0;
            border: 1px solid #272c38;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .article_body_content ul, 
        .article_body_content ol {
            margin-bottom: 24px;
            padding-left: 24px;
        }
        .article_body_content li {
            margin-bottom: 8px;
        }
        .article_body_content a {
            color: #c5a880;
            text-decoration: underline;
        }
        .article_body_content a:hover {
            color: #EBE0D8;
        }

        /* Share Bar */
        .share_bar {
            background: #171a21;
            border: 1px solid #272c38;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 40px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .share_title {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .share_buttons {
            display: flex;
            gap: 10px;
        }
        .share_btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .share_btn.wa { background: #25d366; }
        .share_btn.fb { background: #1877f2; }
        .share_btn.tw { background: #1da1f2; }
        .share_btn.copy { background: #374151; }
        .share_btn:hover {
            filter: brightness(1.15);
            color: #fff;
        }

        /* Post Navigation (Prev/Next) */
        .post_nav {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #272c38;
        }
        .post_nav_box {
            background: #171a21;
            border: 1px solid #272c38;
            border-radius: 10px;
            padding: 16px 20px;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .post_nav_box:hover {
            border-color: #c5a880;
            background: #1f232c;
        }
        .post_nav_label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8b92a0;
            margin-bottom: 6px;
        }
        .post_nav_title {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            line-height: 1.4;
        }

        /* Related Stories */
        .related_stories_section {
            background: #0f1115;
            padding: 70px 0;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .related_card {
            background: #171a21;
            border: 1px solid #272c38;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.25s ease;
            height: 100%;
        }
        .related_card:hover {
            transform: translateY(-4px);
            border-color: rgba(197, 168, 128, 0.4);
        }
        .related_thumb {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .related_body {
            padding: 18px;
        }
        .related_title {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
            color: #fff;
            margin-bottom: 8px;
        }
        .related_title a {
            color: inherit;
            text-decoration: none;
        }
        .related_title a:hover {
            color: #c5a880;
        }
    </style>
</head>

<body>
    <!--::header part start::-->
    <header class="main_menu home_menu">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <a class="navbar-brand d-lg-none" href="index.html">
                            K A M A L A
                        </a>

                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="menu_icon"><i class="fas fa-bars"></i></span>
                        </button>

                        <div class="collapse navbar-collapse main-menu-item" id="navbarSupportedContent">
                            <div class="nav-grid-container">
                                <div class="nav-group nav-left">
                                    <ul class="navbar-nav">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.html">Home</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="portfolio.html">Portfolio</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="reviews.html">Reviews</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="nav-group nav-center d-none d-lg-flex">
                                    <a class="navbar-brand" href="index.html">
                                        K A M A L A
                                    </a>
                                </div>

                                <div class="nav-group nav-right">
                                    <ul class="navbar-nav">
                                        <li class="nav-item active">
                                            <a class="nav-link" href="news.php" style="color:#c5a880;">News</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="faq.html">FAQ</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="inquiry.html">Inquiry</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- Header part end-->

    <?php if (!$article): ?>
        <!-- 404 Not Found State -->
        <section class="article_hero" style="padding:180px 0 100px; text-align:center;">
            <div class="container">
                <i class="fas fa-exclamation-circle" style="font-size:54px; color:#c5a880; margin-bottom:20px; display:inline-block;"></i>
                <h1 style="color:#fff; font-size:32px; margin-bottom:12px;">Article Not Found</h1>
                <p style="color:#9ca3af; font-size:15px; max-width:500px; margin:0 auto 28px;">The story you are looking for may have been moved or unpublished.</p>
                <a href="news.php" class="btn btn-primary" style="background:#c5a880; color:#000; font-weight:600; padding:10px 24px; border-radius:50px; text-decoration:none;">
                    <i class="fas fa-arrow-left"></i> Back to News & Stories
                </a>
            </div>
        </section>
    <?php else: ?>

        <!-- Article Hero Header -->
        <header class="article_hero">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="breadcrumb_wrap">
                            <a href="index.html">Home</a>
                            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                            <a href="news.php">News & Journal</a>
                            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                            <span><?= htmlspecialchars($article['category']) ?></span>
                        </div>

                        <span class="article_category_tag"><?= htmlspecialchars($article['category']) ?></span>

                        <h1 class="article_title"><?= htmlspecialchars($article['title']) ?></h1>

                        <div class="article_meta_bar">
                            <span><i class="fas fa-user-edit"></i> <?= htmlspecialchars($article['author']) ?></span>
                            <span><i class="far fa-calendar-alt"></i> <?= date('F d, Y', strtotime($article['published_at'])) ?></span>
                            <span><i class="far fa-clock"></i> <?= get_reading_time($article['content']) ?></span>
                            <span><i class="far fa-eye"></i> <?= number_format($article['views']) ?> views</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Article Main Content -->
        <section class="article_main_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        
                        <!-- Featured Cover Image -->
                        <?php if (!empty($article['image'])): ?>
                            <div class="featured_img_wrapper">
                                <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>

                        <!-- Quill HTML Content -->
                        <div class="article_body_content">
                            <?= $article['content'] ?>
                        </div>

                        <!-- Social Share Bar -->
                        <div class="share_bar">
                            <div class="share_title">
                                <i class="fas fa-share-alt" style="color:#c5a880;"></i> Share this story:
                            </div>
                            <div class="share_buttons">
                                <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['title'] . ' ' . $currentUrl) ?>" target="_blank" class="share_btn wa" title="Share on WhatsApp">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" class="share_btn fb" title="Share on Facebook">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode($currentUrl) ?>" target="_blank" class="share_btn tw" title="Share on X / Twitter">
                                    <i class="fab fa-twitter"></i> X
                                </a>
                                <button type="button" class="share_btn copy" onclick="copyCurrentLink()">
                                    <i class="fas fa-link"></i> <span id="copyBtnText">Copy Link</span>
                                </button>
                            </div>
                        </div>

                        <!-- Previous / Next Navigation -->
                        <?php if ($prevArticle || $nextArticle): ?>
                            <div class="post_nav">
                                <?php if ($prevArticle): ?>
                                    <a href="news-detail.php?slug=<?= urlencode($prevArticle['slug']) ?>" class="post_nav_box">
                                        <div class="post_nav_label"><i class="fas fa-arrow-left"></i> Previous Article</div>
                                        <div class="post_nav_title"><?= htmlspecialchars($prevArticle['title']) ?></div>
                                    </a>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <?php if ($nextArticle): ?>
                                    <a href="news-detail.php?slug=<?= urlencode($nextArticle['slug']) ?>" class="post_nav_box" style="text-align:right;">
                                        <div class="post_nav_label">Next Article <i class="fas fa-arrow-right"></i></div>
                                        <div class="post_nav_title"><?= htmlspecialchars($nextArticle['title']) ?></div>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </section>

        <!-- Related Articles Section -->
        <?php if (!empty($relatedArticles)): ?>
            <section class="related_stories_section">
                <div class="container">
                    <div class="row justify-content-center mb-4">
                        <div class="col-lg-10 text-center">
                            <span style="color:#c5a880; font-size:12px; letter-spacing:2px; text-transform:uppercase; font-weight:600;">More Stories</span>
                            <h3 style="color:#fff; font-size:26px; font-weight:700; margin-top:6px;">You Might Also Enjoy</h3>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="row">
                                <?php foreach ($relatedArticles as $rel): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="related_card">
                                            <?php 
                                                $relImg = !empty($rel['image']) ? htmlspecialchars($rel['image']) : 'img/wedding/A1.webp';
                                            ?>
                                            <a href="news-detail.php?slug=<?= urlencode($rel['slug']) ?>">
                                                <img src="<?= $relImg ?>" alt="<?= htmlspecialchars($rel['title']) ?>" class="related_thumb" onerror="this.src='img/wedding/A1.webp'">
                                            </a>
                                            <div class="related_body">
                                                <span style="color:#c5a880; font-size:11px; text-transform:uppercase; font-weight:600;"><?= htmlspecialchars($rel['category']) ?></span>
                                                <h4 class="related_title" style="margin-top:6px;">
                                                    <a href="news-detail.php?slug=<?= urlencode($rel['slug']) ?>">
                                                        <?= htmlspecialchars($rel['title']) ?>
                                                    </a>
                                                </h4>
                                                <span style="color:#71717a; font-size:12px;">
                                                    <?= date('M d, Y', strtotime($rel['published_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

    <!--::footer_part start::-->
    <footer class="footer-area">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-sm-6 col-md-6 col-lg-3">
                    <div class="single-footer-widget">
                        <h4>Our Service</h4>
                        <ul>
                            <li><a href="portfolio.html">Wedding Photography</a></li>
                            <li><a href="portfolio.html">Pre-Wedding & Couples</a></li>
                            <li><a href="news.php">Stories & Journal</a></li>
                            <li><a href="admin/login.php" style="color:#6b7280; font-size:12px;"><i class="fas fa-lock"></i> Admin Portal</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-3">
                    <div class="single-footer-widget footer_icon">
                        <h4>Contact Info</h4>
                        <p>Jl. Bypass Ngurah, Denpasar, Bali 80221</p>
                        <ul>
                            <li><a href="https://wa.me/62818725255" target="_blank"><i class="ti-mobile"></i>+628-187-25-255</a></li>
                            <li><a href="mailto:kamalaweddings.booking@gmail.com"><i class="ti-email"></i>kamalaweddings.booking@gmail.com</a></li>
                            <li><a href="#"><i class="ti-world"></i> kamalaweddings.com</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-3">
                    <div class="single-footer-widget footer_2">
                        <h4>Follow Us</h4>
                        <p>Stay updated with our latest wedding captures & behind the scenes.</p>
                        <div class="social_icon">
                            <a href="https://www.instagram.com/kamalaweddings/" target="_blank"><i class="ti-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="copyright_part_text text-center">
                        <p class="footer-text m-0">
                            Copyright &copy; 2025 Kamala Weddings. All Rights Reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!--::footer_part end::-->

    <script src="js/jquery-1.12.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
    <script>
        function copyCurrentLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                const btnText = document.getElementById('copyBtnText');
                const oldText = btnText.textContent;
                btnText.textContent = 'Link Copied!';
                setTimeout(() => {
                    btnText.textContent = oldText;
                }, 2000);
            });
        }
    </script>
</body>
</html>
