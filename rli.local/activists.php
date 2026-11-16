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

// Группируем активистов по ветвям власти
$groupedActivists = [];
foreach ($activists as $act) {
    $branchKey = $act['branch'];
    $groupedActivists[$branchKey][] = $act;
}
?>

<div class="container" style="padding: 40px 0;">
    <h1 style="font-size:24px; font-weight:900; margin-bottom:30px;">Органы лицейского самоуправления</h1>
    
    <?php if (empty($activists)): ?>
        <p style="color:var(--text-muted); font-size:13px;">Активные роли пока не назначены директором.</p>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:30px;">
            <?php foreach ($branches as $branchCode => $branchName): 
                if (empty($groupedActivists[$branchCode])) continue;
            ?>
                <div style="background:#ffffff; border:2px solid var(--card-border); border-radius:4px; padding:24px; box-shadow:var(--shadow);">
                    <h2 style="font-size:18px; color:var(--primary); margin-bottom:16px; border-bottom:2px solid var(--border-color); padding-bottom:8px;">
                        <?= htmlspecialchars($branchName) ?>
                    </h2>
                    
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                        <?php foreach ($groupedActivists[$branchCode] as $act): ?>
                            <div style="background:#f4f7fb; padding:16px; border-radius:4px; border:1px solid var(--border-color);">
                                <h3 style="font-size:15px; font-weight:900; margin-bottom:4px; color:#000;"><?= htmlspecialchars($act['full_name']) ?></h3>
                                <p style="font-size:13px; color:var(--primary); font-weight:700;">Должность: <?= htmlspecialchars($act['position_title']) ?></p>
                                <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Профиль: <?= htmlspecialchars($act['profile']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>