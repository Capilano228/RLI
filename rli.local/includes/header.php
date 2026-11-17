<?php
require_once __DIR__ . '/../config/db.php';


try {
    $activeBallCount = (int)$pdo->query("SELECT COUNT(*) FROM special_announcements WHERE is_ball = 1 AND expires_at > NOW()")->fetchColumn();
    $anyBallCount = (int)$pdo->query("SELECT COUNT(*) FROM special_announcements WHERE is_ball = 1")->fetchColumn();
    if ($anyBallCount > 0 && $activeBallCount === 0) {
        $pdo->exec("UPDATE ball_tickets SET status = 'used', used_at = COALESCE(used_at, NOW()) WHERE status = 'active'");
    }
} catch (Throwable $e) {

}

$themeClass = 'theme-default';
$profileLabels = [
    'fizmat'       => 'Физико-математический',
    'engineering'  => 'Инженерный',
    'polytech'     => 'Политехнический',
    'rosneft'      => 'Технический (Роснефть)',
    'biohim'       => 'Биолого-химический',
    'humanitarian' => 'Гуманитарный'
];

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'teacher') {
        $themeClass = 'theme-teacher';
    } elseif ($_SESSION['role'] === 'student' && !empty($_SESSION['profile'])) {
        $prof = $_SESSION['profile'];
        $themeClass = 'theme-' . htmlspecialchars($prof);
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
$homeClass = ($currentPage === 'index.php' || $currentPage === '') ? 'is-home' : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Республиканский лицей-интернат' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body class="<?= $themeClass ?> <?= $homeClass ?>">

<header class="main-header profile-header-theme">
    <div class="container header-wrap">
        <a href="index.php" class="brand">
            <div class="brand-text">
                <strong>РЛИ</strong>
                <span>Республика Саха (Якутия)</span>
            </div>
        </a>

        <button class="burger-btn" id="burgerBtn" aria-label="Меню" onclick="toggleMobileMenu()">
            <span></span><span></span><span></span>
        </button>

        <nav class="top-nav" id="topNav">
            <a href="index.php">Главная</a>
            <a href="constitution.php">Конституция</a>
            <a href="activists.php">Активисты</a>
            <a href="menu.php">Меню</a>
            <a href="schedule.php">Расписание</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-username"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a href="dashboard.php" class="nav-accent">Кабинет</a>
                <a href="logout.php" onclick="event.preventDefault(); rliConfirmLink('Выход из профиля', 'Вы действительно хотите выйти из учётной записи?', 'logout.php', 'Выйти');">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="nav-accent">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
function toggleMobileMenu() {
    document.getElementById('topNav').classList.toggle('open');
    document.getElementById('burgerBtn').classList.toggle('active');
}
</script>
<main>
