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


if ($isDirector && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $targetUser = (int)$_POST['target_user_id'];
    $posCode = trim($_POST['position_code']);
    
    if (isset($officialPositions[$posCode])) {
        $posData = $officialPositions[$posCode];
        $upd = $pdo->prepare("UPDATE users SET branch = ?, position_title = ?, position_code = ? WHERE id = ?");
        $upd->execute([$posData['branch'], $posData['title'], $posCode, $targetUser]);
        $msg = "Должность успешно назначена.";
        
        $currentUser->execute([$userId]);
        $userData = $currentUser->fetch();
        $userPosCode = $userData['position_code'];
        $userPosTitle = $userData['position_title'];
    }
}

// 2. ПУБЛИКАЦИЯ НОВОСТЕЙ (Премьер-министр)
if ($isPremier && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $photoPath = '';

    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoPath = 'assets/img/news_' . time() . '.' . $ext;
        if (!is_dir(__DIR__ . '/assets/img/')) {
            mkdir(__DIR__ . '/assets/img/', 0777, true);
        }
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/' . $photoPath);
    }

    $stmt = $pdo->prepare("INSERT INTO news (title, content, photo, author_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $content, $photoPath, $userId]);
    $msg = "Объявление успешно опубликовано в ленту.";
}


if ($isSpeaker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rates'])) {
    $newPhys = (float)$_POST['rate_physical'];
    $newMent = (float)$_POST['rate_mental'];
    
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_physical'")->execute([$newPhys]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_mental'")->execute([$newMent]);
    $msg = "Ставки зарплат успешно обновлены.";
    $ratePhysical = $newPhys;
    $rateMental = $newMent;
}

if ($userRole === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_check'])) {
    $studentId = (int)$_POST['student_id'];
    $hours = (float)$_POST['hours'];
    $workType = $_POST['work_type'];
    $reason = trim($_POST['reason'] ?? 'Труд');

    if ($studentId > 0 && $hours > 0 && in_array($workType, ['physical', 'mental'])) {
        $stmt = $pdo->prepare("INSERT INTO work_checks (student_id, teacher_id, hours, work_type, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $userId, $hours, $workType, $reason]);
        $msg = "Часы работы успешно начислены.";
    }
}


if ($isSbrlHead && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_fine'])) {
    $studentId = (int)$_POST['student_id'];
    $reasonType = (int)$_POST['reason_type'];
    
    $amounts = [1 => 200, 2 => 300, 3 => 450];
    $fineAmount = $amounts[$reasonType] ?? 200;

    $stmt = $pdo->prepare("INSERT INTO fines (student_id, officer_id, reason_type, amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$studentId, $userId, $reasonType, $fineAmount]);
    $msg = "Штраф успешно выписан ученику.";
}
?>

<div class="container dash-wrap">

    <?php if ($userRole === 'student'): ?>

        <?php
        $stmt = $pdo->prepare("
            SELECT wc.*, u.full_name AS teacher_name 
            FROM work_checks wc 
            JOIN users u ON wc.teacher_id = u.id 
            WHERE wc.student_id = ? 
            ORDER BY wc.created_at DESC
        ");
        $stmt->execute([$userId]);
        $checks = $stmt->fetchAll();

        $stmtFines = $pdo->prepare("
            SELECT f.*, u.full_name AS officer_name 
            FROM fines f 
            JOIN users u ON f.officer_id = u.id 
            WHERE f.student_id = ? 
            ORDER BY f.created_at DESC
        ");
        $stmtFines->execute([$userId]);
        $fines = $stmtFines->fetchAll();

        $totalEarned = 0;
        foreach ($checks as $c) {
            $h = (float)$c['hours'];
            $rate = ($c['work_type'] === 'mental') ? $rateMental : $ratePhysical;
            $totalEarned += ($h * $rate);
        }

        $totalFines = 0;
        foreach ($fines as $f) {
            $totalFines += (int)$f['amount'];
        }

        $finalBalance = $totalEarned - $totalFines;
        $crestFile = ($userProfileKey !== 'none' && file_exists(__DIR__ . '/assets/img/crests/' . $userProfileKey . '.jpg')) ? 'assets/img/crests/' . $userProfileKey . '.jpg' : '';
        $profileNameFormatted = $profileLabels[$userProfileKey] ?? 'Общий профиль';

        $fineReasonsText = [
            1 => 'Нарушение очереди (-200 лир)',
            2 => 'Фейковое имя сотруднику СБРЛ (-300 лир)',
            3 => 'Оскорбление сотрудника СБРЛ (-450 лир)'
        ];
        ?>

        <div class="dash-header-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <?php if ($crestFile): ?>
                    <img src="<?= $crestFile ?>" alt="Герб" class="dash-crest-large">
                <?php endif; ?>
                <div>
                    <span style="font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;">
                        <?= htmlspecialchars($userData['branch'] !== 'none' ? $userData['branch'] . ' / ' . $userData['position_title'] : 'Ученик лицея') ?>
                    </span>
                    <h2 style="font-size:20px; font-weight:600;"><?= htmlspecialchars($userData['full_name']) ?></h2>
                    <p style="font-size:13px; color:var(--text-muted); margin-top:2px;">Класс: <?= htmlspecialchars($profileNameFormatted) ?></p>
                </div>
            </div>

            <div style="display:flex; gap:12px;">
                <div class="hours-counter">
                    <span>Заработано</span>
                    <strong><?= number_format($totalEarned, 0, '.', ' ') ?> лы.</strong>
                </div>
                <div class="hours-counter" style="border-color:#fecaca;">
                    <span>Штрафы</span>
                    <strong style="color:#991b1b;">-<?= number_format($totalFines, 0, '.', ' ') ?> лы.</strong>
                </div>
                <div class="hours-counter" style="border-color:#bbf7d0;">
                    <span>Итого баланс</span>
                    <strong style="color:#117a65;"><?= number_format($finalBalance, 0, '.', ' ') ?> лы.</strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?><div class="alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if ($isPremier): ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; border-color:var(--primary);">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px; color:var(--primary);">Панель Премьер-министра: Дать объявление в ленту</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="publish_news" value="1">
                <div class="form-group">
                    <label>Заголовок объявления</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Фотография (опционально)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Содержание</label>
                    <textarea name="content" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Опубликовать в ленту</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($isSbrlHead): ?>
        <?php $studentsListForFine = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0; border-color:#f59e0b;">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px; color:#b45309;"> Панель Главы СБРЛ: Выписать штраф ученику</h3>
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
                <button type="submit" class="btn-primary" style="background:#b45309; border-color:#b45309;">Штрафовать</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($isDirector): ?>
        <?php $allUsersList = $pdo->query("SELECT id, full_name, role, branch, position_title FROM users ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;"> Панель директора: Назначение должности ученику</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="update_role" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Пользователь</label>
                    <select name="target_user_id" class="form-control" required>
                        <?php foreach ($allUsersList as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= $u['role'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Выберите должность</label>
                    <select name="position_code" class="form-control" required>
                        <?php foreach ($officialPositions as $code => $info): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($info['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Назначить роль</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ПРЕДСЕДАТЕЛЯ ПАРЛАМЕНТА (Изменение ставок) -->
    <?php if ($isSpeaker): ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">⚖️ Управление ставками зарплат (Председатель Парламента)</h3>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="update_rates" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Физическая (лир/ч)</label>
                    <input type="number" step="10" name="rate_physical" class="form-control" value="<?= $ratePhysical ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Умственная (лир/ч)</label>
                    <input type="number" step="10" name="rate_mental" class="form-control" value="<?= $rateMental ?>" required>
                </div>
                <button type="submit" class="btn-primary">Сохранить ставки</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ЕСЛИ ПОЛЬЗОВАТЕЛЬ — УЧИТЕЛЬ (Выписка часов труда) -->
    <?php if ($userRole === 'teacher'): ?>
        <?php $studentsForCheck = $pdo->query("SELECT id, full_name, profile, branch, position_title FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        
        <div id="checkModal" class="auth-box" style="max-width:100%; margin:0 0 24px 0;">
            <h3 id="modalStudentTitle" style="font-size:16px; font-weight:600; margin-bottom:12px;">Выписать рабочие часы ученику</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 1fr 1.2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="issue_check" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Ученик</label>
                    <select name="student_id" class="form-control" required>
                        <?php foreach ($studentsForCheck as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['profile']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Часы</label>
                    <input type="number" step="0.5" min="0.5" name="hours" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Тип труда</label>
                    <select name="work_type" class="form-control">
                        <option value="physical">Физическая</option>
                        <option value="mental">Умственная</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Основание</label>
                    <input type="text" name="reason" class="form-control" value="Общественно-полезный труд" required>
                </div>
                <button type="submit" class="btn-primary">Начислить</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($userRole === 'student'): ?>
        <!-- СПИСКИ ЧЕКОВ И ШТРАФОВ В КАБИНЕТЕ УЧЕНИКА -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start; margin-top:30px;">
            <div>
                <h3 style="font-size:15px; font-weight:600; margin-bottom:12px;">Заработок (Чеки)</h3>
                <?php if (empty($checks)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">Начислений пока нет.</p>
                <?php else: ?>
                    <?php foreach ($checks as $check): ?>
                        <div class="receipt-card">
                            <div class="receipt-top">
                                <span>Чек №<?= $check['id'] ?></span>
                                <span>+<?= $check['hours'] ?> ч. (<?= $check['work_type'] === 'mental' ? 'Умственная' : 'Физическая' ?>)</span>
                            </div>
                            <div>Основание: <?= htmlspecialchars($check['reason']) ?></div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><?= date('d.m.Y H:i', strtotime($check['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <h3 style="font-size:15px; font-weight:600; margin-bottom:12px; color:#991b1b;">Штрафы СБРЛ</h3>
                <?php if (empty($fines)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">Штрафов нет.</p>
                <?php else: ?>
                    <?php foreach ($fines as $fine): ?>
                        <div class="receipt-card" style="border-color:#fecaca; background:#fffcfc;">
                            <div class="receipt-top" style="color:#991b1b; border-color:#fee2e2;">
                                <span>Штраф №<?= $fine['id'] ?></span>
                                <span>-<?= $fine['amount'] ?> лир</span>
                            </div>
                            <div>Причина: <?= $fineReasonsText[$fine['reason_type']] ?? 'Нарушение' ?></div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><?= date('d.m.Y H:i', strtotime($fine['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>