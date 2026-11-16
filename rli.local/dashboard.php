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
$isTeacher = ($userRole === 'teacher');

// 1. ДЕЙСТВИЯ ДИРЕКТОРА
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

// 3. ДОБАВЛЕНИЕ И УДАЛЕНИЕ ЗАКОНОВ (Председатель Парламента)
if ($isSpeaker && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_law'])) {
        $lawTitle = trim($_POST['law_title']);
        $lawContent = trim($_POST['law_content']);
        if ($lawTitle && $lawContent) {
            $stmt = $pdo->prepare("INSERT INTO laws (title, content) VALUES (?, ?)");
            $stmt->execute([$lawTitle, $lawContent]);
            $msg = "Закон успешно добавлен в Конституцию.";
        }
    } elseif (isset($_POST['delete_law_id'])) {
        $delLawId = (int)$_POST['delete_law_id'];
        $pdo->prepare("DELETE FROM laws WHERE id = ?")->execute([$delLawId]);
        $msg = "Закон удален.";
    } elseif (isset($_POST['cash_out_student'])) {
        $targetStudentId = (int)$_POST['target_student_id'];
        $amountToCash = (int)$_POST['amount_to_cash'];
        if ($amountToCash > 0) {
            $stmt = $pdo->prepare("INSERT INTO cash_outs (student_id, speaker_id, amount) VALUES (?, ?, ?)");
            $stmt->execute([$targetStudentId, $userId, $amountToCash]);
            $msg = "Лиры успешно списаны с аккаунта ученика.";
        }
    } elseif (isset($_POST['update_rates'])) {
        $newPhys = (float)$_POST['rate_physical'];
        $newMent = (float)$_POST['rate_mental'];
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_physical'")->execute([$newPhys]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_mental'")->execute([$newMent]);
        $msg = "Ставки зарплат обновлены.";
        $ratePhysical = $newPhys; $rateMental = $newMent;
    }
}

// 4. НАЧИСЛЕНИЕ ЧАСОВ (Учителя)
if ($isTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_check'])) {
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

// 5. ВЫПИСКА ШТРАФОВ (СБРЛ)
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
                    <span style="font-size:11px; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;">
                        <?= htmlspecialchars($userData['branch'] !== 'none' ? $userData['branch'] . ' / ' . $userData['position_title'] : 'Ученик лицея') ?>
                    </span>
                    <h2 style="font-size:20px;"><?= htmlspecialchars($userData['full_name']) ?></h2>
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
                <h3 style="font-size:15px; margin-bottom:12px;">Заработок (Чеки)</h3>
                <?php if (empty($checks)): ?><p style="color:var(--text-muted); font-size:13px;">Начислений нет.</p>
                <?php else: foreach ($checks as $check): ?>
                    <div class="receipt-card">
                        <div class="receipt-top"><span>Чек №<?= $check['id'] ?></span><span>+<?= $check['hours'] ?> ч.</span></div>
                        <div>Основание: <?= htmlspecialchars($check['reason']) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <div>
                <h3 style="font-size:15px; margin-bottom:12px; color:#991b1b;">Штрафы и Чеки обналичивания</h3>
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

    <!-- ПАНЕЛЬ ДИРЕКТОРА -->
    <?php if ($isDirector): ?>
        <?php $allUsersList = $pdo->query("SELECT id, full_name, role, branch, position_title FROM users ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Панель Директора: Управление должностями</h3>
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
                    <label>Назначить или отобрать должность</label>
                    <select name="position_code" class="form-control" required>
                        <?php foreach ($officialPositions as $code => $info): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($info['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Применить</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ УЧИТЕЛЯ -->
    <?php if ($isTeacher): ?>
        <?php $studentsForCheck = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Выписать рабочие часы ученику</h3>
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

    <!-- ВЕДОМОСТЬ СТОЛОВОЙ ДЛЯ ДИРЕКТОРА И УЧИТЕЛЕЙ -->
    <?php if ($isDirector || $isTeacher): ?>
        <?php 
        $todayDate = date('Y-m-d');
        $stmtCanteen = $pdo->prepare("
            SELECT u.id, u.full_name, u.profile, cv.vote 
            FROM users u 
            LEFT JOIN canteen_votes cv ON u.id = cv.student_id AND cv.vote_date = ?
            WHERE u.role = 'student'
            ORDER BY u.profile ASC, u.full_name ASC
        ");
        $stmtCanteen->execute([$todayDate]);
        $allStudentsCanteen = $stmtCanteen->fetchAll();

        $groupedByProfile = [];
        foreach ($allStudentsCanteen as $st) {
            $profKey = $st['profile'] ?: 'none';
            $groupedByProfile[$profKey][] = $st;
        }
        ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0;">Ведомость столовой на сегодня (<?= date('d.m.Y') ?>)</h3>
                <button type="button" class="btn-primary" onclick="const el = document.getElementById('canteenFullList'); el.style.display = (el.style.display === 'none') ? 'block' : 'none';">
                    Показать / Скрыть список
                </button>
            </div>

            <div id="canteenFullList" style="display:block;">
                <?php if (empty($groupedByProfile)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">Зарегистрированных учеников пока нет.</p>
                <?php else: ?>
                    <?php foreach ($groupedByProfile as $profKey => $studentsInProfile): 
                        $profTitle = $profileLabels[$profKey] ?? 'Без профиля';
                    ?>
                        <div style="margin-bottom:24px;">
                            <h4 style="font-size:14px; background:#f0f4f8; padding:8px 14px; border:1.5px solid var(--border-color); border-bottom:none;">
                                Профиль: <?= htmlspecialchars($profTitle) ?> (Всего: <?= count($studentsInProfile) ?>)
                            </h4>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">№</th>
                                        <th>ФИО Ученика</th>
                                        <th style="width:200px;">Статус питания</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    foreach ($studentsInProfile as $stRow): 
                                        $voteStatus = $stRow['vote'];
                                        if ($voteStatus === 'yes') {
                                            $statusText = 'Обедает';
                                            $badgeStyle = 'background:#e6fffa; color:#117a65; border:1px solid #99f6e4;';
                                        } elseif ($voteStatus === 'no') {
                                            $statusText = 'Не обедает';
                                            $badgeStyle = 'background:#ffe3e3; color:#a61b1b; border:1px solid #fecaca;';
                                        } else {
                                            $statusText = 'Не дал ответ';
                                            $badgeStyle = 'background:#f3f4f6; color:#4b5563; border:1px solid #d1d5db;';
                                        }
                                    ?>
                                        <tr>
                                            <td><?= $counter++ ?></td>
                                            <td><?= htmlspecialchars($stRow['full_name']) ?></td>
                                            <td>
                                                <span style="padding:4px 10px; border-radius:4px; font-size:12px; display:inline-block; <?= $badgeStyle ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ПРЕДСЕДАТЕЛЯ ПАРЛАМЕНТА (Учет лиров + Добавление и удаление законов) -->
    <?php if ($isSpeaker): ?>
        <?php 
        $allStudentsForSpeaker = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll();
        $allLaws = $pdo->query("SELECT * FROM laws ORDER BY created_at DESC")->fetchAll();
        ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Управление законами РЛИ (Председатель Парламента)</h3>
            <form method="POST" style="margin-bottom:24px; padding-bottom:20px; border-bottom:1.5px solid var(--border-color);">
                <input type="hidden" name="add_law" value="1">
                <div class="form-group"><label>Название закона</label><input type="text" name="law_title" class="form-control" required></div>
                <div class="form-group"><label>Содержание закона</label><textarea name="law_content" class="form-control" rows="3" required></textarea></div>
                <button type="submit" class="btn-primary">Добавить закон в Конституцию</button>
            </form>

            <h4 style="font-size:14px; margin-bottom:10px;">Существующие законы</h4>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($allLaws as $law): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f4f7fb; padding:10px 14px; border-radius:4px; font-size:13px;">
                        <div><b><?= htmlspecialchars($law['title']) ?></b> <span style="color:var(--text-muted); font-size:11px;">(<?= $law['created_at'] ?>)</span></div>
                        <form method="POST" onsubmit="return confirm('Удалить этот закон?');">
                            <input type="hidden" name="delete_law_id" value="<?= $law['id'] ?>">
                            <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:11px; border-color:#a61b1b; color:#a61b1b;">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Учет лиров и мификов (Председатель Парламента)</h3>
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
                            <td><?= htmlspecialchars($st['full_name']) ?></td>
                            <td><?= htmlspecialchars($st['profile']) ?></td>
                            <td><?= number_format($earnedSt, 0) ?></td>
                            <td style="color:#991b1b;">-<?= $finesSt ?></td>
                            <td style="color:#b45309;">-<?= $coSt ?></td>
                            <td><span style="color:#117a65;"><?= number_format($balanceSt, 0) ?> лы.</span></td>
                            <td>
                                <?php if ($balanceSt > 0): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Обналичить все лиры с аккаунта ученика?');">
                                        <input type="hidden" name="cash_out_student" value="1">
                                        <input type="hidden" name="target_student_id" value="<?= $stId ?>">
                                        <input type="hidden" name="amount_to_cash" value="<?= $balanceSt ?>">
                                        <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:11px;">Обналичить</button>
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
            <h3>Управление ставками зарплат</h3>
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
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Панель Премьер-министра: Управление новостями</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-bottom:24px; padding-bottom:20px; border-bottom:1.5px solid var(--border-color);">
                <input type="hidden" name="publish_news" value="1">
                <div class="form-group"><label>Заголовок</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label>Фото</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                <div class="form-group"><label>Содержание</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                <button type="submit" class="btn-primary">Опубликовать</button>
            </form>

            <h4 style="font-size:14px; margin-bottom:10px;">Существующие объявления</h4>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($allNews as $item): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f4f7fb; padding:10px 14px; border-radius:4px; font-size:13px;">
                        <div><span><?= htmlspecialchars($item['title']) ?></span> <span style="color:var(--text-muted); font-size:11px;">(<?= $item['created_at'] ?>)</span></div>
                        <form method="POST" onsubmit="return confirm('Удалить это объявление?');">
                            <input type="hidden" name="delete_news_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:11px; border-color:#a61b1b; color:#a61b1b;">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ГЛАВЫ СБРЛ -->
    <?php if ($isSbrlHead): ?>
        <?php $studentsListForFine = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3>Панель Главы СБРЛ: Выписать штраф ученику</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="issue_fine" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Нарушитель (Ученик)</label>
                    <select name="student_id" class="form-control" required>
                        <?php foreach ($studentsListForFine as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['profile']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Причина нарушения</label>
                    <select name="reason_type" class="form-control">
                        <option value="1">1. Нарушение очереди (-200 лир)</option>
                        <option value="2">2. Фейковое имя (-300 лир)</option>
                        <option value="3">3. Оскорбление сотрудника (-450 лир)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Штрафовать</button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>