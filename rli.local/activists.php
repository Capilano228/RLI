<?php
$pageTitle = 'Активисты школы | РЛИ';
include 'includes/header.php';

$activists = $pdo->query("
    SELECT * FROM users 
    WHERE branch != 'none' 
    ORDER BY branch, full_name
")->fetchAll();

$branches = [
    'Government' => 'Правительство РЛИ',
    'Parliament' => 'Парламент РЛИ',
    'SBRL'       => 'СБРЛ (Служба безопасности)',
    'Directorate'=> 'Дирекция и Администрация'
];
?>

<div class="container" style="padding: 40px 0;">
    <h1 style="font-size:22px; font-weight:600; margin-bottom:20px;">Органы лицейского самоуправления</h1>
    
    <?php if (empty($activists)): ?>
        <p style="color:var(--text-muted); font-size:13px;">Активные роли пока не назначены директором.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
            <?php foreach ($activists as $act): ?>
                <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius); border:1px solid var(--border-color); box-shadow:var(--shadow);">
                    <span style="font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;">
                        <?= htmlspecialchars($branches[$act['branch']] ?? $act['branch']) ?>
                    </span>
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:4px;"><?= htmlspecialchars($act['full_name']) ?></h3>
                    <p style="font-size:13px; color:var(--text-main); font-weight:500;">Должность: <?= htmlspecialchars($act['position_title']) ?></p>
                    <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Профиль: <?= htmlspecialchars($act['profile']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>