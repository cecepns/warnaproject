<?php
/**
 * Admin Login Page
 * Kamala Weddings Admin Panel
 */

require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submittedToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = :username LIMIT 1");
                $stmt->execute([':username' => $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Set session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $admin['id'];
                    $_SESSION['admin_username']  = $admin['username'];
                    $_SESSION['admin_name']      = $admin['name'];
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                $error = 'Database connection error: ' . $e->getMessage();
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
    <title>Admin Login — Kamala Weddings</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/all.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at center, #181c25 0%, #0a0b0e 100%);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #fff;
            margin-bottom: 6px;
        }
        .brand-title span {
            color: var(--accent-gold);
        }
        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .login-hint {
            background: rgba(197, 168, 128, 0.08);
            border: 1px dashed rgba(197, 168, 128, 0.3);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 24px;
            font-size: 12px;
            color: var(--accent-gold-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .login-hint code {
            background: rgba(0,0,0,0.4);
            padding: 2px 6px;
            border-radius: 4px;
            color: #fff;
            font-weight: 600;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            letter-spacing: 1px;
            justify-content: center;
            margin-top: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
        }
        .back-link a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }
        .back-link a:hover {
            color: var(--accent-gold);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h1 class="brand-title">K A M A L A <span>ADMIN</span></h1>
            <p class="brand-subtitle">Content & News Management</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <span><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="login-hint">
            <i class="fas fa-key" style="color:var(--accent-gold); font-size:16px;"></i>
            <div>
                Default Login: User <code>admin</code> & Password <code>admin123</code>
            </div>
        </div>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div style="position:relative;">
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required autofocus placeholder="Enter your username">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
            </button>
        </form>

        <div class="back-link">
            <a href="../news.php"><i class="fas fa-arrow-left" style="margin-right:6px;"></i> Back to Kamala Weddings Site</a>
        </div>
    </div>

</body>
</html>
