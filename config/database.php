<?php
/**
 * Database Configuration & Auto-Migration (MySQL Native PDO)
 * Designed for Kamala Weddings / warnaproject
 */

// MySQL Database Credentials (editable or override via environment)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'warnaproject_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // First connect to MySQL server (without specifying DB in case it needs to be created)
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Ensure database exists
        $pdo->exec(sprintf(
            "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci;",
            DB_NAME,
            DB_CHARSET,
            DB_CHARSET
        ));

        // Switch to the database
        $pdo->exec(sprintf("USE `%s`;", DB_NAME));

        // Check if tables exist, auto-create if needed
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'news'")->fetch();
        if (!$tableCheck) {
            initMySQLDatabase($pdo);
        }
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:8px;max-width:600px;margin:40px auto;line-height:1.6;'>
            <h3 style='margin-top:0;'>Database Connection Error</h3>
            <p>Could not connect to MySQL server at <code>" . htmlspecialchars(DB_HOST . ":" . DB_PORT) . "</code>.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p style='font-size:13px;color:#666;'>Please ensure your MySQL service (e.g. XAMPP, MAMP, Homebrew MySQL) is running and the credentials in <code>config/database.php</code> are correct.</p>
        </div>");
    }

    return $pdo;
}

function initMySQLDatabase(PDO $pdo): void {
    // 1. Create admins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Create news table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `news` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `category` VARCHAR(100) NOT NULL,
            `author` VARCHAR(100) DEFAULT 'Kamala Weddings',
            `image` VARCHAR(255) DEFAULT '',
            `excerpt` TEXT,
            `content` MEDIUMTEXT,
            `status` ENUM('published', 'draft') DEFAULT 'published',
            `views` INT DEFAULT 0,
            `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`status`),
            INDEX (`category`),
            INDEX (`published_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Seed default admin if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($count == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`, `name`) VALUES (:username, :password, :name)");
        $stmt->execute([
            ':username' => 'admin',
            ':password' => $defaultPassword,
            ':name'     => 'Kamala Admin'
        ]);
    }

    // 4. Seed sample news articles if table is empty
    $newsCount = $pdo->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
    if ($newsCount == 0) {
        seedSampleMySQLNews($pdo);
    }
}

function seedSampleMySQLNews(PDO $pdo): void {
    $articles = [
        [
            'title' => 'Top 7 Tips for Flawless Golden Hour Wedding Photos in Bali',
            'slug' => 'top-7-tips-golden-hour-wedding-photos-bali',
            'category' => 'Wedding Tips',
            'author' => 'Kamala Weddings',
            'image' => 'img/wedding/A1.webp',
            'excerpt' => 'Golden hour in Bali is magical. Discover our top professional tips to make the most of that dreamy, warm sunset glow for your wedding and pre-wedding portraits.',
            'content' => '<h2>The Magic of Bali\'s Golden Hour</h2><p>There is something undeniably enchanting about the way the tropical sun dips below the Indian Ocean horizon in Bali. As wedding photographers with over eight years of experience capturing love stories across the island, we consider golden hour—the final hour before sunset—the absolute pinnacle of outdoor portraiture.</p><blockquote><p>"Light is the language of photography. In Bali, that language speaks in hues of molten amber, soft rose, and luminous gold."</p></blockquote><h3>1. Timing is Everything: Know Your Sunset Window</h3><p>Unlike temperate climates where sunset twilight lingers for hours, tropical sunsets happen rapidly. In Bali, true golden hour typically peaks between <strong>5:15 PM and 6:15 PM</strong> depending on the time of year. Always schedule your couple portraits with at least 45 minutes of buffer time before the sun touches the horizon.</p><h3>2. Choose Locations with Clear Western Horizons</h3><p>Whether you choose the dramatic limestone cliffs of Uluwatu, the pristine black sands of Canggu, or the sweeping clifftops overlooking Nusa Dua, ensure your chosen venue or beach offers an unobstructed western view.</p><h3>3. Coordinate Wardrobe for Warm Lighting</h3><p>Flowing fabrics like silk organza, chiffon, and soft tulle catch backlight exquisitely, creating an ethereal rim light around the bride and groom. Earthy tones, ivory, champagne, and blush complement the natural golden tones of the sunset landscape.</p><h3>4. Embrace Spontaneous, Candid Movement</h3><p>Rather than stiff, formal posing, golden hour calls for genuine interaction. Walk hand in hand along the shoreline, whisper something that makes your partner laugh, and allow the ocean breeze to add dynamic flow to your hair and dress.</p><p>Ready to plan your dream wedding or pre-wedding session in Bali? Contact our team at Kamala Weddings to discuss our exclusive photography packages.</p>',
            'status' => 'published',
            'views' => 142,
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'title' => 'How to Feel Completely Natural & Relaxed in Front of the Camera',
            'slug' => 'how-to-feel-natural-relaxed-in-front-of-camera',
            'category' => 'Pre-Wedding',
            'author' => 'Melda - Kamala Weddings',
            'image' => 'img/couples/A1.webp',
            'excerpt' => 'Not a natural model? Don\'t worry! Here is our proven guide to shedding camera anxiety and creating authentic, emotion-filled pre-wedding photographs.',
            'content' => '<h2>Why Almost Everyone Feels Camera-Shy At First</h2><p>If you or your fiancé tense up the moment a lens points in your direction, please know: <em>you are completely normal</em>. Over 90% of the couples we photograph tell us during our first meeting, "We are really awkward in photos!"</p><p>Yet, when you look through our portfolio, all you see are warm, joyful, effortless smiles. How do we get there? It is not about knowing how to pose like a supermodel—it is about feeling comfortable and being yourselves.</p><blockquote><p>"The best wedding photos don\'t look like a photoshoot. They look like two people completely immersed in their love."</p></blockquote><h3>1. Treat It Like a Romantic Date</h3><p>Think of your pre-wedding shoot not as an audition, but as an intimate date where someone just happens to have a camera nearby. Hold hands, tease each other, reminisce about your first date, and focus on each other rather than the photographer.</p><h3>2. Focus on Action Rather Than Poses</h3><p>Static poses can feel awkward and stiff. We guide our couples with simple, active prompts: "Whisper your favorite secret joke in her ear," or "Gently twirl her towards the waves." Motion brings life, natural smiles, and real emotion to every frame.</p><h3>3. Trust Your Photographers as Friends</h3><p>At Kamala Weddings, our style is friendly, calm, and patient. We don\'t rush you, and we give clear, gentle direction so you never have to wonder what to do with your hands. By minute twenty, you will forget the camera is even there!</p>',
            'status' => 'published',
            'views' => 98,
            'published_at' => date('Y-m-d H:i:s', strtotime('-12 days'))
        ],
        [
            'title' => 'Editorial Wedding Trends: Intimate Ceremonies & Timeless Aesthetics',
            'slug' => 'editorial-wedding-trends-intimate-ceremonies-timeless-aesthetics',
            'category' => 'Inspiration',
            'author' => 'Kamala Weddings',
            'image' => 'img/wedding/A13.webp',
            'excerpt' => 'From micro-weddings in cliffside villas to nostalgic film-inspired color grades, discover the top aesthetic directions defining modern wedding storytelling.',
            'content' => '<h2>The Shift Towards Intentional, Timeless Celebrations</h2><p>The wedding landscape has undergone a wonderful transformation over recent seasons. Couples are increasingly trading giant, exhausting banquets for meaningful, boutique ceremonies surrounded only by their closest family and lifelong friends.</p><h3>1. Nostalgic, Editorial Color Palettes</h3><p>Say goodbye to overly saturated, unnatural filters. Today\'s brides and grooms crave true-to-life tones with rich contrast, warm highlights, and subtle film emulation. This timeless aesthetic ensures your wedding album looks as stylish thirty years from now as it does today.</p><h3>2. Flash & Direct-Light Afterparty Photography</h3><p>Inspired by vintage 90s fashion editorials and celebrity afterparties, direct on-camera flash has become the defining look for reception dance floors. It creates high-energy, documentary-style snapshots full of laughter, movement, and unfiltered joy.</p><h3>3. Story-Driven Photojournalism</h3><p>While elegant bridal portraits will always remain essential, couples now place equal value on the quiet, unscripted in-between moments: a tear falling from a parent\'s cheek, a shared glance before walking down the aisle, or the bride kicking off her heels under the dinner table.</p><p>Interested in capturing your wedding day with an artistic, editorial touch? Explore our portfolio or send us an inquiry today.</p>',
            'status' => 'published',
            'views' => 215,
            'published_at' => date('Y-m-d H:i:s', strtotime('-20 days'))
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `news` (`title`, `slug`, `category`, `author`, `image`, `excerpt`, `content`, `status`, `views`, `published_at`)
        VALUES (:title, :slug, :category, :author, :image, :excerpt, :content, :status, :views, :published_at)
    ");

    foreach ($articles as $art) {
        $stmt->execute([
            ':title'        => $art['title'],
            ':slug'         => $art['slug'],
            ':category'     => $art['category'],
            ':author'       => $art['author'],
            ':image'        => $art['image'],
            ':excerpt'      => $art['excerpt'],
            ':content'      => $art['content'],
            ':status'       => $art['status'],
            ':views'        => $art['views'],
            ':published_at' => $art['published_at']
        ]);
    }
}

/**
 * Utility Functions
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a-' . time();
    }

    return $text;
}

function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

function get_reading_time(string $content): string {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes) . ' min read';
}
