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
<body class="<?= $themeClass ?>">

<?php if ($showCanteenPopup): ?>
<div id="canteenModalOverlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;">
    <div style="background:#fff; color:#102a43; padding:30px; border-radius:10px; max-width:440px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2); text-align:center;">
        <h3 style="font-size:20px; font-weight:700; color:var(--primary); margin-bottom:8px;">Школьная столовая</h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Дата: <b><?= $todayDateFormatted ?></b></p>
        
        <div style="background:#f4f7fb; padding:14px; border-radius:6px; text-align:left; margin-bottom:20px; font-size:13px;">
            <div style="font-weight:600; margin-bottom:4px; color:var(--primary);">Меню на сегодня:</div>
            <div>• Суп: <?= htmlspecialchars($todayMenu['soup']) ?></div>
            <div>• Второе: <?= htmlspecialchars($todayMenu['main_dish']) ?></div>
            <div>• Напиток: <?= htmlspecialchars($todayMenu['drink']) ?></div>
        </div>

        <p style="font-size:14px; font-weight:600; margin-bottom:16px;">Будете ли вы сегодня кушать в столовой?</p>
        
        <div style="display:flex; gap:12px; justify-content:center;">
            <button onclick="voteCanteen('yes')" style="background:#117a65; color:#fff; border:none; padding:10px 24px; border-radius:6px; cursor:pointer; font-weight:600; flex:1;">Да, буду</button>
            <button onclick="voteCanteen('no')" style="background:#a61b1b; color:#fff; border:none; padding:10px 24px; border-radius:6px; cursor:pointer; font-weight:600; flex:1;">Нет</button>
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

        <nav class="top-nav">
            <a href="index.php">Главная</a>
            <a href="constitution.php">Конституция</a>
            <a href="activists.php">Активисты</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="font-size: 13px; font-weight: 500; color: var(--text-main);">
                    <?= htmlspecialchars($_SESSION['full_name']) ?>
                </span>
                <a href="dashboard.php" class="nav-accent">Кабинет</a>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="nav-accent">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>