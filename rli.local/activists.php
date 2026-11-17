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

$groupedActivists = [];
foreach ($activists as $act) {
    $groupedActivists[$act['branch']][] = $act;
}
?>

<div class="container" style="padding: 40px 0;">
    <h1 style="font-size:24px; font-weight:900; margin-bottom:16px;">Активисты школы</h1>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Органы лицейского самоуправления, отсортированные по ветвям власти и активам участников.</p>

    <!-- ФИЛЬТР ПО ВЕТВЯМ ВЛАСТИ -->
    <?php if (!empty($activists)): ?>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px;">
            <button class="btn-hero filter-btn active" data-filter="all" onclick="filterBranch('all', this)">Все ветви</button>
            <?php foreach ($branches as $branchCode => $branchName): 
                if (empty($groupedActivists[$branchCode])) continue;
            ?>
                <button class="btn-hero filter-btn" data-filter="<?= htmlspecialchars($branchCode) ?>" onclick="filterBranch('<?= htmlspecialchars($branchCode) ?>', this)"><?= htmlspecialchars($branchName) ?></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($activists)): ?>
        <p style="color:var(--text-muted); font-size:13px;">Активные роли пока не назначены администрацией.</p>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:30px;" id="activistsList">
            <?php foreach ($branches as $branchCode => $branchName): 
                if (empty($groupedActivists[$branchCode])) continue;
            ?>
                <div class="branch-block" data-branch="<?= htmlspecialchars($branchCode) ?>" style="background:#ffffff; border:2px solid var(--card-border); border-radius:4px; padding:24px; box-shadow:var(--shadow);">
                    <h2 style="font-size:18px; color:var(--primary); margin-bottom:16px; border-bottom:2px solid var(--border-color); padding-bottom:8px;">
                        <?= htmlspecialchars($branchName) ?> <span style="font-size:12px; color:var(--text-muted);">(<?= count($groupedActivists[$branchCode]) ?> чел.)</span>
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

<script>
function filterBranch(branch, btn) {
    document.querySelectorAll('.filter-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
    });
    btn.classList.add('active');
    btn.style.background = 'var(--primary)';
    btn.style.color = '#ffffff';

    document.querySelectorAll('.branch-block').forEach(function(block) {
        block.style.display = (branch === 'all' || block.getAttribute('data-branch') === branch) ? 'block' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
