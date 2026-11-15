<?php
$pageTitle = 'Личный кабинет | РЛИ';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$userLogin = $_SESSION['login'] ?? '';

$officialPositions = [
    'student' => ['branch' => 'none', 'title' => 'Ученик', 'name' => 'Ученик (без должности)'],
    'prime_minister' => ['branch' => 'Government', 'title' => 'Премьер-министр', 'name' => 'Премьер-министр Правительства'],
    'government_minister' => ['branch' => 'Government', 'title' => 'Министр', 'name' => 'Министр Правительства'],
    'parliament_speaker' => ['branch' => 'Parliament', 'title' => 'Председатель Парламента', 'name' => 'Председатель Парламента'],
    'parliament_deputy' => ['branch' => 'Parliament', 'title' => 'Депутат', 'name' => 'Депутат Парламента'],
    'sbrl_head' => ['branch' => 'SBRL', 'title' => 'Глава СБРЛ', 'name' => 'Глава СБРЛ (Служба безопасности)'],
    'sbrl_deputy' => ['branch' => 'SBRL', 'title' => 'Заместитель главы СБРЛ', 'name' => 'Заместитель главы СБРЛ'],
    'sbrl_member' => ['branch' => 'SBRL', 'title' => 'Участник СБРЛ', 'name' => 'Участник СБРЛ']
];

$ratesQuery = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$ratePhysical = (float)($ratesQuery['rate_physical'] ?? 300);
$rateMental = (float)($ratesQuery['rate_mental'] ?? 350);

$currentUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$currentUser->execute([$userId]);
$userData = $currentUser->fetch();

if (!$userData) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$userProfileKey = $userData['profile'] ?? 'none';
$userPosCode = $userData['position_code'] ?? 'student';
$userPosTitle = $userData['position_title'] ?? '';

$isDirector = ($userLogin === 'director' || $userPosCode === 'director' || mb_stripos($userPosTitle, 'Директор') !== false);
$isPremier = ($userPosCode === 'prime_minister' || mb_stripos($userPosTitle, 'Премьер-министр') !== false);
$isSpeaker = ($userPosCode === 'parliament_speaker' || mb_stripos($userPosTitle, 'Председатель Парламента') !== false);
$isSbrlHead = ($userPosCode === 'sbrl_head' || mb_stripos($userPosTitle, 'Глава СБРЛ') !== false);
$isZavuch = ($isDirector || mb_stripos($userData['full_name'], 'завуч') !== false);

// 1. ДЕЙСТВИЯ ДИРЕКТОРА (Выдача и отбор должностей)
if ($isDirector && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $targetUser = (int)$_POST['target_user_id'];
    $posCode = trim($_POST['position_code']);
    
    if (isset($officialPositions[$posCode])) {
        $posData = $officialPositions[$posCode];
        $upd = $pdo->prepare("UPDATE users SET branch = ?, position_title = ?, position_code = ? WHERE id = ?");
        $upd->execute([$posData['branch'], $posData['title'], $posCode, $targetUser]);
        $msg = "Должность ученика успешно изменена.";
    }
}

// 2. ПУБЛИКАЦИЯ И УДАЛЕНИЕ НОВОСТЕЙ (Премьер-министр)
if ($isPremier && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['publish_news'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $photoPath = '';
        if (!empty($_FILES['photo']['name'])) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoPath = 'assets/img/news_' . time() . '.' . $ext;
            if (!is_dir(__DIR__ . '/assets/img/')) { mkdir(__DIR__ . '/assets/img/', 0777, true); }
            move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/' . $photoPath);
        }
        $stmt = $pdo->prepare("INSERT INTO news (title, content, photo, author_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $photoPath, $userId]);
        $msg = "Объявление успешно опубликовано.";
    } elseif (isset($_POST['delete_news_id'])) {
        $delId = (int)$_POST['delete_news_id'];
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$delId]);
        $msg = "Объявление успешно удалено.";
    }
}

// 3. ОБНАЛИЧИВАНИЕ ЛИР (Председатель Парламента)
if ($isSpeaker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_out_student'])) {
    $targetStudentId = (int)$_POST['target_student_id'];
    $amountToCash = (int)$_POST['amount_to_cash'];
    if ($amountToCash > 0) {
        $stmt = $pdo->prepare("INSERT INTO cash_outs (student_id, speaker_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$targetStudentId, $userId, $amountToCash]);
        $msg = "Лиры успешно списаны с аккаунта ученика.";
    }
}

// 4. ИЗМЕНЕНИЕ СТАВОК (Председатель Парламента)
if ($isSpeaker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rates'])) {
    $newPhys = (float)$_POST['rate_physical'];
    $newMent = (float)$_POST['rate_mental'];
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_physical'")->execute([$newPhys]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_mental'")->execute([$newMent]);
    $msg = "Ставки зарплат обновлены.";
    $ratePhysical = $newPhys; $rateMental = $newMent;
}

// 5. НАЧИСЛЕНИЕ ЧАСОВ (Учителя)
if ($userRole === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_check'])) {
    $studentId = (int)$_POST['student_id'];
    $hours = (float)$_POST['hours'];
    $workType = $_POST['work_type'];
    $reason = trim($_POST['reason'] ?? 'Труд');
    if ($studentId > 0 && $hours > 0) {
        $stmt = $pdo->prepare("INSERT INTO work_checks (student_id, teacher_id, hours, work_type, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $userId, $hours, $workType, $reason]);
        $msg = "Часы начислены.";
    }
}

// 6. ВЫПИСКА ШТРАФОВ (СБРЛ)
if ($isSbrlHead && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_fine'])) {
    $studentId = (int)$_POST['student_id'];
    $reasonType = (int)$_POST['reason_type'];
    $amounts = [1 => 200, 2 => 300, 3 => 450];
    $fineAmount = $amounts[$reasonType] ?? 200;
    $stmt = $pdo->prepare("INSERT INTO fines (student_id, officer_id, reason_type, amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$studentId, $userId, $reasonType, $fineAmount]);
    $msg = "Штраф выписан.";
}
?>

<div class="container dash-wrap">

    <?php if ($userRole === 'student'): ?>
        <!-- КАБИНЕТ УЧЕНИКА -->
        <?php
        $stmt = $pdo->prepare("SELECT wc.*, u.full_name AS teacher_name FROM work_checks wc JOIN users u ON wc.teacher_id = u.id WHERE wc.student_id = ? ORDER BY wc.created_at DESC");
        $stmt->execute([$userId]);
        $checks = $stmt->fetchAll();

        $stmtFines = $pdo->prepare("SELECT f.*, u.full_name AS officer_name FROM fines f JOIN users u ON f.officer_id = u.id WHERE f.student_id = ? ORDER BY f.created_at DESC");
        $stmtFines->execute([$userId]);
        $fines = $stmtFines->fetchAll();

        $stmtCash = $pdo->prepare("SELECT * FROM cash_outs WHERE student_id = ? ORDER BY created_at DESC");
        $stmtCash->execute([$userId]);
        $cashOuts = $stmtCash->fetchAll();

        $totalEarned = 0;
        foreach ($checks as $c) {
            $h = (float)$c['hours'];
            $rate = ($c['work_type'] === 'mental') ? $rateMental : $ratePhysical;
            $totalEarned += ($h * $rate);
        }
        $totalFines = 0;
        foreach ($fines as $f) { $totalFines += (int)$f['amount']; }
        $totalCashed = 0;
        foreach ($cashOuts as $co) { $totalCashed += (int)$co['amount']; }

        $finalBalance = $totalEarned - $totalFines - $totalCashed;
        $crestFile = ($userProfileKey !== 'none' && file_exists(__DIR__ . '/assets/img/crests/' . $userProfileKey . '.jpg')) ? 'assets/img/crests/' . $userProfileKey . '.jpg' : '';
        $profileNameFormatted = $profileLabels[$userProfileKey] ?? 'Общий профиль';
        ?>

        <div class="dash-header-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <?php if ($crestFile): ?><img src="<?= $crestFile ?>" alt="Герб" class="dash-crest-large"><?php endif; ?>
                <div>
                    <span style="font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;">
                        <?= htmlspecialchars($userData['branch'] !== 'none' ? $userData['branch'] . ' / ' . $userData['position_title'] : 'Ученик лицея') ?>
                    </span>
                    <h2 style="font-size:20px; font-weight:600;"><?= htmlspecialchars($userData['full_name']) ?></h2>
                    <p style="font-size:13px; color:var(--text-muted); margin-top:2px;">Класс: <?= htmlspecialchars($profileNameFormatted) ?></p>
                </div>
            </div>

            <div style="display:flex; gap:12px;">
                <div class="hours-counter"><span>Заработано</span><strong><?= number_format($totalEarned, 0, '.', ' ') ?> лы.</strong></div>
                <div class="hours-counter" style="border-color:#fecaca;"><span>Штрафы</span><strong style="color:#991b1b;">-<?= number_format($totalFines, 0, '.', ' ') ?> лы.</strong></div>
                <div class="hours-counter" style="border-color:#fef08a;"><span>Обналичено</span><strong style="color:#b45309;">-<?= number_format($totalCashed, 0, '.', ' ') ?> лы.</strong></div>
                <div class="hours-counter" style="border-color:#bbf7d0;"><span>Итого баланс</span><strong style="color:#117a65;"><?= number_format($finalBalance, 0, '.', ' ') ?> лы.</strong></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
            <div>
                <h3 style="font-size:15px; font-weight:600; margin-bottom:12px;">Заработок (Чеки)</h3>
                <?php if (empty($checks)): ?><p style="color:var(--text-muted); font-size:13px;">Начислений нет.</p>
                <?php else: foreach ($checks as $check): ?>
                    <div class="receipt-card">
                        <div class="receipt-top"><span>Чек №<?= $check['id'] ?></span><span>+<?= $check['hours'] ?> ч.</span></div>
                        <div>Основание: <?= htmlspecialchars($check['reason']) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <div>
                <h3 style="font-size:15px; font-weight:600; margin-bottom:12px; color:#991b1b;">Штрафы и Чеки обналичивания</h3>
                <?php foreach ($cashOuts as $co): ?>
                    <div class="receipt-card" style="border-color:#fef08a; background:#fefce8;">
                        <div class="receipt-top" style="color:#b45309;"><span>Чек обналичивания</span><span>-<?= $co['amount'] ?> лир</span></div>
                        <div style="font-size:11px; color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($co['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($fines as $fine): ?>
                    <div class="receipt-card" style="border-color:#fecaca; background:#fffcfc;">
                        <div class="receipt-top" style="color:#991b1b;"><span>Штраф №<?= $fine['id'] ?></span><span>-<?= $fine['amount'] ?> лир</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?><div class="alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <!-- ПАНЕЛЬ ДИРЕКТОРА (Выдача и отбор должностей + Просмотр столовой) -->
    <?php if ($isDirector): ?>
        <?php 
        $allUsersList = $pdo->query("SELECT id, full_name, role, branch, position_title FROM users ORDER BY full_name")->fetchAll();
        $todayDate = date('Y-m-d');
        $canteenList = $pdo->prepare("SELECT u.full_name, u.profile, cv.vote FROM canteen_votes cv JOIN users u ON cv.student_id = u.id WHERE cv.vote_date = ?");
        $canteenList->execute([$todayDate]);
        $canteenRows = $canteenList->fetchAll();
        ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; border-color:var(--primary);">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px; color:var(--primary);">👑 Панель Директора: Управление должностями</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="update_role" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Пользователь</label>
                    <select name="target_user_id" class="form-control" required>
                        <?php foreach ($allUsersList as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['position_title']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Назначить/отобрать должность</label>
                    <select name="position_code" class="form-control" required>
                        <?php foreach ($officialPositions as $code => $info): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($info['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Применить</button>
            </form>
        </div>

        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; background:var(--bg-surface);">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px; color:var(--primary);">📋 Список столовой на сегодня (<?= date('d.m.Y') ?>)</h3>
            <table class="data-table">
                <thead>
                    <tr><th>ФИО Ученика</th><th>Класс</th><th>Статус питания</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($canteenRows)): ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">Никто еще не проголосовал на сегодня.</td></tr>
                    <?php else: foreach ($canteenRows as $cr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cr['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($cr['profile']) ?></td>
                            <td>
                                <span style="padding:3px 8px; border-radius:4px; font-size:11px; font-weight:600; background:<?= $cr['vote'] === 'yes' ? '#e6fffa; color:#117a65;' : '#ffe3e3; color:#a61b1b;' ?>">
                                    <?= $cr['vote'] === 'yes' ? 'Будет кушать' : 'Не будет' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ПРЕДСЕДАТЕЛЯ ПАРЛАМЕНТА (Учет лиров и мификов) -->
    <?php if ($isSpeaker): ?>
        <?php 
        $allStudentsForSpeaker = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll();
        ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; border-color:var(--primary);">
            <h3 style="font-size:17px; font-weight:600; margin-bottom:12px; color:var(--primary);">📊 Учет лиров и мификов (Председатель Парламента)</h3>
            <table class="data-table" style="margin-bottom:20px;">
                <thead>
                    <tr>
                        <th>ФИО Ученика</th>
                        <th>Класс</th>
                        <th>Заработано (л.)</th>
                        <th>Штрафы (л.)</th>
                        <th>Обналичено</th>
                        <th>Итого баланс</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allStudentsForSpeaker as $st): 
                        $stId = $st['id'];
                        $ch = $pdo->prepare("SELECT hours, work_type FROM work_checks WHERE student_id = ?"); $ch->execute([$stId]);
                        $earnedSt = 0; foreach ($ch->fetchAll() as $row) { $earnedSt += (float)$row['hours'] * (($row['work_type'] === 'mental') ? $rateMental : $ratePhysical); }
                        
                        $fn = $pdo->prepare("SELECT amount FROM fines WHERE student_id = ?"); $fn->execute([$stId]);
                        $finesSt = 0; foreach ($fn->fetchAll() as $row) { $finesSt += (int)$row['amount']; }

                        $co = $pdo->prepare("SELECT amount FROM cash_outs WHERE student_id = ?"); $co->execute([$stId]);
                        $coSt = 0; foreach ($co->fetchAll() as $row) { $coSt += (int)$row['amount']; }

                        $balanceSt = $earnedSt - $finesSt - $coSt;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($st['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($st['profile']) ?></td>
                            <td><?= number_format($earnedSt, 0) ?></td>
                            <td style="color:#991b1b;">-<?= $finesSt ?></td>
                            <td style="color:#b45309;">-<?= $coSt ?></td>
                            <td><strong style="color:#117a65;"><?= number_format($balanceSt, 0) ?> лы.</strong></td>
                            <td>
                                <?php if ($balanceSt > 0): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Обналичить/списать все лиры с аккаунта ученика?');">
                                        <input type="hidden" name="cash_out_student" value="1">
                                        <input type="hidden" name="target_student_id" value="<?= $stId ?>">
                                        <input type="hidden" name="amount_to_cash" value="<?= $balanceSt ?>">
                                        <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:11px; background:#b45309; border-color:#b45309;">Обналичить</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--text-muted);">Нет средств</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3 style="font-size:15px; font-weight:600; margin-bottom:12px;">Управление ставками зарплат</h3>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="update_rates" value="1">
                <div class="form-group" style="margin:0;"><label>Физическая (лир/ч)</label><input type="number" step="10" name="rate_physical" class="form-control" value="<?= $ratePhysical ?>" required></div>
                <div class="form-group" style="margin:0;"><label>Умственная (лир/ч)</label><input type="number" step="10" name="rate_mental" class="form-control" value="<?= $rateMental ?>" required></div>
                <button type="submit" class="btn-primary">Сохранить</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ПРЕМЬЕР-МИНИСТРА -->
    <?php if ($isPremier): ?>
        <?php $allNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.author_id = u.id ORDER BY n.created_at DESC")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; border-color:var(--primary);">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px; color:var(--primary);">📢 Панель Премьер-министра: Управление новостями</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid var(--border-color);">
                <input type="hidden" name="publish_news" value="1">
                <div class="form-group"><label>Заголовок</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label>Фото</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                <div class="form-group"><label>Содержание</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                <button type="submit" class="btn-primary">Опубликовать</button>
            </form>

            <h4 style="font-size:14px; font-weight:600; margin-bottom:10px;">Существующие объявления</h4>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($allNews as $item): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f4f7fb; padding:10px 14px; border-radius:6px; font-size:13px;">
                        <div><b><?= htmlspecialchars($item['title']) ?></b> <span style="color:var(--text-muted); font-size:11px;">(<?= $item['created_at'] ?>)</span></div>
                        <form method="POST" onsubmit="return confirm('Удалить это объявление?');">
                            <input type="hidden" name="delete_news_id" value="<?= $item['id'] ?>">
                            <button type="submit" style="background:#a61b1b; color:#fff; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-size:11px;">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ УЧИТЕЛЯ -->
    <?php if ($userRole === 'teacher'): ?>
        <?php $studentsForCheck = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Выписать рабочие часы ученику</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 1fr 1.2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="issue_check" value="1">
                <div class="form-group" style="margin:0;"><label>Ученик</label><select name="student_id" class="form-control" required><?php foreach ($studentsForCheck as $st): ?><option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label>Часы</label><input type="number" step="0.5" min="0.5" name="hours" class="form-control" required></div>
                <div class="form-group" style="margin:0;"><label>Тип труда</label><select name="work_type" class="form-control"><option value="physical">Физическая</option><option value="mental">Умственная</option></select></div>
                <div class="form-group" style="margin:0;"><label>Основание</label><input type="text" name="reason" class="form-control" value="Труд" required></div>
                <button type="submit" class="btn-primary">Начислить</button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>