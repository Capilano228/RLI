<?php
$pageTitle = 'Меню столовой | РЛИ';
include 'includes/header.php';

$msg = '';
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_canteen_choice'])) {
    $choice = $_POST['canteen_choice'];
    if (in_array($choice, ['yes', 'no'])) {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO canteen_votes (student_id, vote, vote_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote = VALUES(vote)");
        $stmt->execute([$_SESSION['user_id'], $choice, $today]);
        $_SESSION['canteen_voted_session'] = true;
        $msg = "Ваш выбор успешно обновлен на сегодня!";
    }
}

$daysOfWeek = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота',
    7 => 'Воскресенье'
];
$currentDayNum = (int)date('N');
?>

<div class="container" style="padding: 40px 0;">
    <h1 style="font-size:24px; font-weight:700; margin-bottom:20px; color:var(--text-main);">Школьная столовая • Меню на неделю</h1>
    
    <?php if ($msg): ?>
        <div class="alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
        <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius); border:1px solid var(--border-color); margin-bottom:30px; box-shadow:var(--shadow);">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:10px; color:var(--primary);">Изменить выбор питания на сегодня (<?= date('d.m.Y') ?>)</h3>
            <form method="POST" style="display:flex; gap:12px; align-items:center;">
                <input type="hidden" name="update_canteen_choice" value="1">
                <select name="canteen_choice" class="form-control" style="max-width:250px;" required>
                    <option value="yes">Буду кушать</option>
                    <option value="no">Не буду кушать</option>
                </select>
                <button type="submit" class="btn-primary">Сохранить выбор</button>
            </form>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
        <?php foreach ($daysOfWeek as $num => $dayName): 
            $isToday = ($num === $currentDayNum);
        ?>
            <div style="background:var(--bg-surface); border-radius:var(--radius); border: <?= $isToday ? '2px solid var(--accent-gold)' : '1px solid var(--border-color)' ?>; padding:20px; box-shadow:var(--shadow); position:relative;">
                <?php if ($isToday): ?>
                    <span style="position:absolute; top:12px; right:12px; background:var(--accent-gold); color:#102a43; font-size:10px; font-weight:700; padding:3px 8px; border-radius:4px; text-transform:uppercase;">Сегодня</span>
                <?php endif; ?>
                <h3 style="font-size:17px; font-weight:700; color:var(--primary); margin-bottom:12px;"><?= $dayName ?></h3>
                
                <div style="font-size:13px; color:var(--text-main); display:flex; flex-direction:column; gap:6px;">
                    <div><b>Суп:</b> Комплексный суп дня</div>
                    <div><b>Второе блюдо:</b> Основное блюдо с гарниром</div>
                    <div><b>Напиток:</b> Чай / Компот / Сок</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>