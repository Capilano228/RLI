<?php
require_once __DIR__ . '/../config/db.php';

$themeClass = 'theme-default';
$crestPath = '';
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
        $crestPath = 'assets/img/crests/' . htmlspecialchars($prof) . '.jpg';
    }
}

// Определяем, находится ли пользователь на главной странице
$currentPage = basename($_SERVER['PHP_SELF']);
$homeClass = ($currentPage === 'index.php' || $currentPage === '') ? 'is-home' : '';

// Проверка голосования в столовой
$showCanteenPopup = false;
$todayDateFormatted = date('d.m.Y');
$todayMenu = ['soup' => 'Суп дня', 'main_dish' => 'Второе блюдо', 'drink' => 'Напиток'];

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student' && empty($_SESSION['canteen_voted_session'])) {
    $today = date('Y-m-d');
    $stmtVote = $pdo->prepare("SELECT id FROM canteen_votes WHERE student_id = ? AND vote_date = ?");
    $stmtVote->execute([$_SESSION['user_id'], $today]);
    if ($stmtVote->fetch()) {
        $_SESSION['canteen_voted_session'] = true;
    } else {
        $showCanteenPopup = true;
        $stmtMenu = $pdo->prepare("SELECT * FROM canteen_menu WHERE menu_date = ?");
        $stmtMenu->execute([$today]);
        $menuData = $stmtMenu->fetch();
        if ($menuData) {
            $todayMenu = $menuData;
        }
    }
}
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

<?php if ($showCanteenPopup): ?>
<div id="canteenModalOverlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; color:#102a43; padding:24px; border-radius:10px; max-width:420px; width:92%; box-shadow:0 10px 25px rgba(0,0,0,0.2); text-align:center;">
        <h3 style="font-size:18px; color:var(--primary); margin-bottom:6px;">Школьная столовая</h3>
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">Дата: <b><?= $todayDateFormatted ?></b></p>
        
        <div style="background:#f4f7fb; padding:12px; border-radius:8px; text-align:left; margin-bottom:16px; font-size:13px;">
            <div style="margin-bottom:4px; color:var(--primary);">Меню на сегодня:</div>
            <div>• Суп: <?= htmlspecialchars($todayMenu['soup']) ?></div>
            <div>• Второе: <?= htmlspecialchars($todayMenu['main_dish']) ?></div>
            <div>• Напиток: <?= htmlspecialchars($todayMenu['drink']) ?></div>
        </div>

        <p style="font-size:14px; margin-bottom:14px;">Будете ли вы сегодня кушать в столовой?</p>
        
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="voteCanteen('yes')" style="background:#117a65; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:900; flex:1;">Да, буду</button>
            <button onclick="voteCanteen('no')" style="background:#a61b1b; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:900; flex:1;">Нет</button>
        </div>
    </div>
</div>
<script>
function voteCanteen(val) {
    fetch('vote_canteen.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'vote=' + val
    }).then(() => {
        document.getElementById('canteenModalOverlay').style.display = 'none';
    });
}
</script>
<?php endif; ?>

<header class="main-header">
    <div class="container header-wrap">
        <a href="index.php" class="brand">
            <div class="brand-text">
                <strong>РЛИ</strong>
                <span>Республика Саха (Якутия)</span>
            </div>
        </a>

        <!-- Кнопка "3 полоски" для мобильной версии -->
        <button class="burger-btn" id="burgerBtn" aria-label="Открыть меню" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="top-nav" id="topNav">
            <a href="index.php">Главная</a>
            <a href="constitution.php">Конституция</a>
            <a href="activists.php">Активисты</a>
            <a href="menu.php">Меню</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-username"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a href="dashboard.php" class="nav-accent">Кабинет</a>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="nav-accent">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const nav = document.getElementById('topNav');
    const btn = document.getElementById('burgerBtn');
    nav.classList.toggle('open');
    btn.classList.toggle('active');
}
</script>
<main>