<?php
/**
 * Public News & Stories Listing Page
 * Kamala Weddings - Professional Wedding Photography Jakarta & Bali
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDB();

// Filters & Pagination
$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$page     = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage  = 6;
$offset   = ($page - 1) * $perPage;

// Base query for published articles
$where = "WHERE `status` = 'published'";
$params = [];

if (!empty($search)) {
    $where .= " AND (`title` LIKE :search OR `excerpt` LIKE :search OR `content` LIKE :search OR `category` LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $where .= " AND `category` = :category";
    $params[':category'] = $category;
}

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM `news` $where");
$countStmt->execute($params);
$totalArticles = $countStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

// Fetch articles with pagination
$query = "SELECT * FROM `news` $where ORDER BY `published_at` DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Categories with counts
$catStmt = $pdo->query("
    SELECT `category`, COUNT(*) as count 
    FROM `news` 
    WHERE `status` = 'published' AND `category` != '' 
    GROUP BY `category` 
    ORDER BY count DESC
");
$categoriesList = $catStmt->fetchAll();

// Recent 4 posts for sidebar
$recentPosts = $pdo->query("
    SELECT `title`, `slug`, `image`, `published_at` 
    FROM `news` 
    WHERE `status` = 'published' 
    ORDER BY `published_at` DESC 
    LIMIT 4
")->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- SEO Meta Tags -->
    <title>News & Stories — Kamala Weddings | Wedding Tips, Photography Inspiration</title>
    <meta name="description" content="Discover inspiring wedding stories, photography advice, destination guides in Bali & Jakarta, and pre-wedding tips curated by the Kamala Weddings team.">
    <meta name="keywords" content="wedding photography tips, bali wedding guide, pre-wedding ideas, wedding stories, kamala weddings news, wedding photo inspiration">
    <meta name="author" content="Kamala Weddings">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="News & Stories — Kamala Weddings">
    <meta property="og:description" content="Discover inspiring wedding stories, photography advice, and destination guides in Bali & Jakarta.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://kamalaweddings.com/news.php">
    <meta property="og:image" content="https://kamalaweddings.com/img/wedding/A1.webp">

    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon.png">
    <link rel="shortcut icon" href="img/favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400&display=swap" rel="stylesheet">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Custom News Page Aesthetics matching Kamala Weddings */
        .news_banner {
            padding: 140px 0 70px;
            background: linear-gradient(180deg, #0a0b0e 0%, #12141a 100%);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            text-align: center;
            position: relative;
        }
        .news_banner h1 {
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #fff;
            margin-bottom: 12px;
        }
        .news_banner p {
            color: #EBE0D8;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            letter-spacing: 0.5px;
        }
        .news_section {
            background-color: #0c0e12;
            padding: 70px 0 100px;
        }
        .category_pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 40px;
            justify-content: center;
        }
        .category_pill {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            color: #bbb;
            background: #171a21;
            border: 1px solid #272c38;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .category_pill:hover,
        .category_pill.active {
            color: #000;
            background: #c5a880;
            border-color: #c5a880;
            font-weight: 600;
        }
        /* News Card */
        .news_card {
            background: #171a21;
            border: 1px solid #272c38;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: calc(100% - 30px);
        }
        .news_card:hover {
            transform: translateY(-6px);
            border-color: rgba(197, 168, 128, 0.4);
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
        }
        .news_card_img_wrap {
            position: relative;
            height: 240px;
            overflow: hidden;
            background: #12141a;
        }
        .news_card_img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .news_card:hover .news_card_img {
            transform: scale(1.06);
        }
        .news_card_badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(15, 17, 21, 0.85);
            backdrop-filter: blur(4px);
            color: #c5a880;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid rgba(197, 168, 128, 0.3);
        }
        .news_card_body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .news_card_meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 12px;
            color: #8b92a0;
            margin-bottom: 12px;
        }
        .news_card_meta span i {
            margin-right: 5px;
            color: #c5a880;
        }
        .news_card_title {
            font-size: 19px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .news_card_title a {
            color: #fff;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .news_card_title a:hover {
            color: #c5a880;
        }
        .news_card_excerpt {
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }
        .news_card_footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #232731;
            padding-top: 16px;
            font-size: 13px;
        }
        .read_more_link {
            color: #c5a880;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s ease;
        }
        .read_more_link:hover {
            gap: 10px;
            color: #EBE0D8;
        }

        /* Sidebar Styling */
        .news_sidebar_widget {
            background: #171a21;
            border: 1px solid #272c38;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .widget_title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding-bottom: 14px;
            margin-bottom: 20px;
            border-bottom: 2px solid #272c38;
            position: relative;
        }
        .widget_title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 40px;
            height: 2px;
            background: #c5a880;
        }
        .search_form {
            position: relative;
        }
        .search_input {
            width: 100%;
            padding: 12px 40px 12px 14px;
            background: #12141a;
            border: 1px solid #272c38;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        .search_input:focus {
            outline: none;
            border-color: #c5a880;
        }
        .search_btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #c5a880;
            cursor: pointer;
        }
        .category_list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .category_list li {
            border-bottom: 1px solid #222631;
        }
        .category_list li:last-child {
            border-bottom: none;
        }
        .category_list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .category_list a:hover {
            color: #c5a880;
            padding-left: 5px;
        }
        .category_list a span.count {
            background: #12141a;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: #6b7280;
        }
        .recent_post_item {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }
        .recent_post_thumb {
            width: 70px;
            height: 56px;
            border-radius: 6px;
            object-fit: cover;
            background: #12141a;
            flex-shrink: 0;
        }
        .recent_post_title {
            font-size: 13px;
            font-weight: 500;
            line-height: 1.4;
            color: #e5e7eb;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .recent_post_title:hover {
            color: #c5a880;
        }
        .recent_post_date {
            font-size: 11px;
            color: #71717a;
            margin-top: 4px;
        }

        /* Consultation CTA Box */
        .cta_widget {
            background: linear-gradient(145deg, #222834 0%, #171a21 100%);
            border: 1px solid rgba(197, 168, 128, 0.3);
            text-align: center;
            padding: 28px 20px;
            border-radius: 12px;
        }
        .cta_widget h4 {
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .cta_widget p {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        /* Pagination */
        .pagination_wrap {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
        }
        .page_link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border-radius: 8px;
            background: #171a21;
            border: 1px solid #272c38;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .page_link:hover,
        .page_link.active {
            background: #c5a880;
            border-color: #c5a880;
            color: #000;
            font-weight: 600;
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
                        <!-- Mobile logo -->
                        <a class="navbar-brand d-lg-none" href="index.html">
                            K A M A L A
                        </a>

                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="menu_icon"><i class="fas fa-bars"></i></span>
                        </button>

                        <div class="collapse navbar-collapse main-menu-item" id="navbarSupportedContent">
                            <!-- Custom 3-grid navigation layout -->
                            <div class="nav-grid-container">
                                <!-- Left navigation group -->
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

                                <!-- Center logo (hidden on mobile) -->
                                <div class="nav-group nav-center d-none d-lg-flex">
                                    <a class="navbar-brand" href="index.html">
                                        K A M A L A
                                    </a>
                                </div>

                                <!-- Right navigation group -->
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

    <!-- News Hero Banner -->
    <section class="news_banner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <span style="color:#c5a880; text-transform:uppercase; font-size:12px; letter-spacing:3px; font-weight:600; display:block; margin-bottom:10px;">
                        Stories & Insights
                    </span>
                    <h1>KAMALA JOURNAL</h1>
                    <p>Inspiration, wedding guides, and authentic love stories captured through our lens.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- News Content Section -->
    <section class="news_section">
        <div class="container">

            <!-- Category Pills Bar -->
            <div class="category_pills">
                <a href="news.php" class="category_pill <?= empty($category) ? 'active' : '' ?>">All Stories</a>
                <?php foreach ($categoriesList as $cat): ?>
                    <a href="news.php?category=<?= urlencode($cat['category']) ?>" class="category_pill <?= $category === $cat['category'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['category']) ?> (<?= $cat['count'] ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="row">
                <!-- Articles List (Left/Main Column) -->
                <div class="col-lg-8">
                    <?php if (!empty($search)): ?>
                        <div style="margin-bottom:24px; color:#9ca3af; font-size:14px;">
                            Search results for "<strong style="color:#fff;"><?= htmlspecialchars($search) ?></strong>" 
                            (<?= $totalArticles ?> articles found) 
                            <a href="news.php" style="color:#c5a880; margin-left:10px;"><i class="fas fa-times"></i> Clear</a>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($articles)): ?>
                        <div class="news_sidebar_widget text-center" style="padding:60px 20px;">
                            <i class="fas fa-newspaper" style="font-size:44px; color:#4b5563; margin-bottom:16px;"></i>
                            <h3 style="color:#fff; font-size:20px; margin-bottom:8px;">No Stories Found</h3>
                            <p style="color:#9ca3af; font-size:14px; max-width:400px; margin:0 auto 20px;">
                                <?= !empty($search) ? 'No articles match your search criteria. Try a different search term.' : 'There are currently no published articles in this category.' ?>
                            </p>
                            <a href="news.php" class="btn btn-primary" style="padding:10px 24px; border-radius:50px; background:#c5a880; color:#000; font-weight:600; text-decoration:none;">
                                View All Stories
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($articles as $index => $item): ?>
                                <div class="col-md-6">
                                    <article class="news_card">
                                        <div class="news_card_img_wrap">
                                            <?php 
                                                $imgSrc = !empty($item['image']) ? htmlspecialchars($item['image']) : 'img/wedding/A1.webp';
                                            ?>
                                            <a href="news-detail.php?slug=<?= urlencode($item['slug']) ?>">
                                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="news_card_img" onerror="this.src='img/wedding/A1.webp'">
                                            </a>
                                            <span class="news_card_badge"><?= htmlspecialchars($item['category']) ?></span>
                                        </div>

                                        <div class="news_card_body">
                                            <div class="news_card_meta">
                                                <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($item['published_at'])) ?></span>
                                                <span><i class="far fa-clock"></i> <?= get_reading_time($item['content']) ?></span>
                                            </div>

                                            <h2 class="news_card_title">
                                                <a href="news-detail.php?slug=<?= urlencode($item['slug']) ?>">
                                                    <?= htmlspecialchars($item['title']) ?>
                                                </a>
                                            </h2>

                                            <p class="news_card_excerpt">
                                                <?= htmlspecialchars(mb_substr($item['excerpt'], 0, 130)) ?>...
                                            </p>

                                            <div class="news_card_footer">
                                                <span style="color:#71717a; font-size:12px;">
                                                    <i class="fas fa-eye" style="margin-right:4px;"></i> <?= number_format($item['views']) ?>
                                                </span>
                                                <a href="news-detail.php?slug=<?= urlencode($item['slug']) ?>" class="read_more_link">
                                                    Read Story <i class="fas fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination_wrap">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="page_link" title="Previous Page">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="page_link <?= $p === $page ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="page_link" title="Next Page">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <!-- Sidebar (Right Column) -->
                <div class="col-lg-4">
                    <!-- Search Widget -->
                    <div class="news_sidebar_widget">
                        <h4 class="widget_title">Search Stories</h4>
                        <form method="GET" action="news.php" class="search_form">
                            <?php if (!empty($category)): ?>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                            <?php endif; ?>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="search_input" placeholder="Search keywords...">
                            <button type="submit" class="search_btn" aria-label="Search"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <!-- Categories Widget -->
                    <div class="news_sidebar_widget">
                        <h4 class="widget_title">Categories</h4>
                        <ul class="category_list">
                            <li>
                                <a href="news.php">
                                    <span>All Categories</span>
                                    <span class="count"><?= $totalArticles ?></span>
                                </a>
                            </li>
                            <?php foreach ($categoriesList as $cat): ?>
                                <li>
                                    <a href="news.php?category=<?= urlencode($cat['category']) ?>">
                                        <span><?= htmlspecialchars($cat['category']) ?></span>
                                        <span class="count"><?= $cat['count'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Recent Stories Widget -->
                    <div class="news_sidebar_widget">
                        <h4 class="widget_title">Latest Stories</h4>
                        <?php foreach ($recentPosts as $post): ?>
                            <div class="recent_post_item">
                                <?php 
                                    $recentImg = !empty($post['image']) ? htmlspecialchars($post['image']) : 'img/wedding/A1.webp';
                                ?>
                                <a href="news-detail.php?slug=<?= urlencode($post['slug']) ?>">
                                    <img src="<?= $recentImg ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="recent_post_thumb" onerror="this.src='img/wedding/A1.webp'">
                                </a>
                                <div>
                                    <a href="news-detail.php?slug=<?= urlencode($post['slug']) ?>" class="recent_post_title">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                    <div class="recent_post_date">
                                        <?= date('M d, Y', strtotime($post['published_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Consultation CTA Box -->
                    <div class="cta_widget">
                        <i class="ti-camera" style="font-size:32px; color:#c5a880; margin-bottom:12px; display:inline-block;"></i>
                        <h4>Let's Tell Your Story</h4>
                        <p>Planning your wedding or pre-wedding in Jakarta or Bali? We’d love to be part of your journey.</p>
                        <a href="inquiry.html" class="btn" style="background:#c5a880; color:#000; font-weight:600; padding:10px 24px; border-radius:50px; text-decoration:none; display:inline-block;">
                            Get in Touch
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

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

    <!-- Scripts -->
    <script src="js/jquery-1.12.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
