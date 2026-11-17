<?php
$pageTitle = 'Личный кабинет | РЛИ';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$msgType = 'success';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$userLogin = $_SESSION['login'] ?? '';

$officialPositions = [
    'student' => ['branch' => 'none', 'title' => 'Ученик', 'name' => 'Ученик (без должности)'],
    'president' => ['branch' => 'Government', 'title' => 'Президент', 'name' => 'Президент РЛИ'],
    'vice_president' => ['branch' => 'Government', 'title' => 'Вице-президент', 'name' => 'Вице-президент РЛИ'],
    'head_teacher' => ['branch' => 'Directorate', 'title' => 'Завуч', 'name' => 'Завуч школы'],
    'prime_minister' => ['branch' => 'Government', 'title' => 'Премьер-министр', 'name' => 'Премьер-министр Правительства'],
    'government_minister' => ['branch' => 'Government', 'title' => 'Министр', 'name' => 'Министр Правительства'],
    'parliament_speaker' => ['branch' => 'Parliament', 'title' => 'Председатель Парламента', 'name' => 'Председатель Парламента'],
    'parliament_deputy' => ['branch' => 'Parliament', 'title' => 'Депутат', 'name' => 'Депутат Парламента'],
    'sbrl_head' => ['branch' => 'SBRL', 'title' => 'Глава СБРЛ', 'name' => 'Глава СБРЛ (Служба безопасности)'],
    'sbrl_deputy' => ['branch' => 'SBRL', 'title' => 'Заместитель главы СБРЛ', 'name' => 'Заместитель главы СБРЛ'],
    'sbrl_member' => ['branch' => 'SBRL', 'title' => 'Участник СБРЛ', 'name' => 'Участник СБРЛ']
];

$profileNames = [
    'fizmat' => 'Физико-математический',
    'engineering' => 'Инженерный',
    'polytech' => 'Политехнический',
    'rosneft' => 'Технический (Роснефть)',
    'biohim' => 'Биолого-химический',
    'humanitarian' => 'Гуманитарный'
];
$residenceNames = ['city' => 'Городской', 'boarding' => 'Интернатский'];
$mealNames = ['breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин'];

$currentUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$currentUser->execute([$userId]);
$userData = $currentUser->fetch();

$userPosCode = $userData['position_code'] ?? 'student';
$userPosTitle = $userData['position_title'] ?? '';
$userBranch = $userData['branch'] ?? 'none';

$isDirector = ($userLogin === 'director' || $userPosCode === 'director' || mb_stripos($userPosTitle, 'Директор') !== false);
$isHeadTeacher = ($isDirector || $userPosCode === 'head_teacher' || mb_stripos($userPosTitle, 'Завуч') !== false);
$isPresident = ($isDirector || $userPosCode === 'president' || $userPosCode === 'vice_president');
$isPremier = ($isDirector || $userPosCode === 'prime_minister');
$isSpeaker = ($isDirector || $userPosCode === 'parliament_speaker');

$isSbrlStaff = ($isDirector || $userBranch === 'SBRL' || in_array($userPosCode, ['sbrl_head', 'sbrl_deputy', 'sbrl_member'], true));
$isTeacher = ($userRole === 'teacher' || $isHeadTeacher);

$ratesQuery = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$ratePhys = (float)($ratesQuery['rate_physical'] ?? 300);
$rateMent = (float)($ratesQuery['rate_mental'] ?? 350);
$ballPrice = (int)($ratesQuery['ball_price'] ?? 2000);


$activeBallEvent = null;
try {
    $ballStmt = $pdo->query("SELECT * FROM special_announcements WHERE is_ball = 1 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $activeBallEvent = $ballStmt ? $ballStmt->fetch() : null;
} catch (Throwable $e) {
    $activeBallEvent = null;
}


if ($isHeadTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $targetUser = (int)$_POST['target_user_id'];
    $posCode = trim($_POST['position_code']);
    if (isset($officialPositions[$posCode])) {
        $posData = $officialPositions[$posCode];
        $pdo->prepare("UPDATE users SET branch = ?, position_title = ?, position_code = ? WHERE id = ?")
            ->execute([$posData['branch'], $posData['title'], $posCode, $targetUser]);
        $msg = "Должность успешно обновлена.";
    }
}


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
        $pdo->prepare("INSERT INTO news (title, content, photo, author_id) VALUES (?, ?, ?, ?)")->execute([$title, $content, $photoPath, $userId]);
        $msg = "Новость опубликована.";
    } elseif (isset($_POST['delete_news_id'])) {
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([(int)$_POST['delete_news_id']]);
        $msg = "Новость удалена.";
    }
}


if ($isSpeaker && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cash_out_student'])) {
        $targetId = (int)$_POST['target_student_id'];
        $amount = (int)$_POST['amount_to_cash'];
        if ($amount > 0) {
            $pdo->prepare("INSERT INTO cash_outs (student_id, speaker_id, amount) VALUES (?, ?, ?)")->execute([$targetId, $userId, $amount]);
            $msg = "Лиры успешно списаны с аккаунта.";
        }
    } elseif (isset($_POST['update_rates'])) {
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_physical'")->execute([(float)$_POST['rate_physical']]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'rate_mental'")->execute([(float)$_POST['rate_mental']]);
        $msg = "Ставки зарплат обновлены.";
    } elseif (isset($_POST['add_law'])) {
        $pdo->prepare("INSERT INTO laws (title, content, author_id) VALUES (?, ?, ?)")
            ->execute([trim($_POST['law_title']), trim($_POST['law_content']), $userId]);
        $msg = "Закон Парламента опубликован.";
    } elseif (isset($_POST['delete_law_id'])) {
        $pdo->prepare("DELETE FROM laws WHERE id = ?")->execute([(int)$_POST['delete_law_id']]);
        $msg = "Закон удалён.";
    }
}


$scannedTicket = null;
if ($isSbrlStaff && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['issue_fine'])) {
        $studentId = (int)$_POST['student_id'];
        $reasonType = (int)$_POST['reason_type'];
        $amounts = [1 => 200, 2 => 300, 3 => 450];
        $pdo->prepare("INSERT INTO fines (student_id, officer_id, reason_type, amount) VALUES (?, ?, ?, ?)")
            ->execute([$studentId, $userId, $reasonType, $amounts[$reasonType] ?? 200]);
        $msg = "Штраф выписан.";
    } elseif (isset($_POST['check_ticket_code'])) {
        $code = strtoupper(trim($_POST['ticket_code'] ?? ''));
        $stT = $pdo->prepare("SELECT bt.*, u.full_name AS buyer_name, u.school_class, u.profile FROM ball_tickets bt JOIN users u ON bt.buyer_id = u.id WHERE bt.ticket_code = ?");
        $stT->execute([$code]);
        $scannedTicket = $stT->fetch();
        if (!$scannedTicket) {
            $msg = "Билет с кодом «{$code}» не найден в системе проданных билетов.";
            $msgType = 'error';
        }
    } elseif (isset($_POST['burn_ticket_id'])) {
        $tId = (int)$_POST['burn_ticket_id'];
        $stCheck = $pdo->prepare("SELECT * FROM ball_tickets WHERE id = ?");
        $stCheck->execute([$tId]);
        $tk = $stCheck->fetch();
        if (!$tk) {
            $msg = "Билет не найден.";
            $msgType = 'error';
        } elseif ($tk['status'] === 'used') {
            $msg = "Билет уже просканирован.";
            $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE ball_tickets SET status = 'used', used_at = NOW(), checked_by = ? WHERE id = ?")->execute([$userId, $tId]);
            $msg = "Билет {$tk['ticket_code']} одобрен на входе и сожжён.";
        }
    }
}


if ($isPresident && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_special_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $duration = (int)$_POST['duration_hours'];
        $isBall = isset($_POST['is_ball']) ? 1 : 0;
        $expiresAt = date('Y-m-d H:i:s', time() + ($duration * 3600));
        $pdo->prepare("INSERT INTO special_announcements (title, content, is_ball, author_id, expires_at) VALUES (?, ?, ?, ?, ?)")->execute([$title, $content, $isBall, $userId, $expiresAt]);
        $msg = "Специальное объявление опубликовано.";

        // Обновляем статус активного бала после создания
        $ballStmt = $pdo->query("SELECT * FROM special_announcements WHERE is_ball = 1 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $activeBallEvent = $ballStmt ? $ballStmt->fetch() : null;
    } elseif (isset($_POST['delete_special_id'])) {
        $saId = (int)$_POST['delete_special_id'];
        $saRow = $pdo->prepare("SELECT is_ball FROM special_announcements WHERE id = ?");
        $saRow->execute([$saId]);
        $saInfo = $saRow->fetch();
        $pdo->prepare("DELETE FROM special_announcements WHERE id = ?")->execute([$saId]);
        if ($saInfo && (int)$saInfo['is_ball'] === 1) {
            $remBalls = (int)$pdo->query("SELECT COUNT(*) FROM special_announcements WHERE is_ball = 1 AND expires_at > NOW()")->fetchColumn();
            if ($remBalls === 0) {
                $pdo->exec("UPDATE ball_tickets SET status = 'used', used_at = COALESCE(used_at, NOW()) WHERE status = 'active'");
            }
            $msg = "Специальное объявление о бале завершено досрочно. Неиспользованные билеты на бал сожжены.";
        } else {
            $msg = "Специальное объявление удалено досрочно.";
        }

        $ballStmt = $pdo->query("SELECT * FROM special_announcements WHERE is_ball = 1 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $activeBallEvent = $ballStmt ? $ballStmt->fetch() : null;
    }
}


if ($isTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_check'])) {
    $pdo->prepare("INSERT INTO work_checks (student_id, teacher_id, hours, work_type, reason) VALUES (?, ?, ?, ?, ?)")
        ->execute([(int)$_POST['student_id'], $userId, (float)$_POST['hours'], $_POST['work_type'], trim($_POST['reason'])]);
    $msg = "Часы начислены.";
}


if ($userRole === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_ball_ticket'])) {
    if (!$activeBallEvent) {
        $msg = "Покупка билетов недоступна: сейчас нет активного специального события (Бала).";
        $msgType = 'error';
    } else {
        $currentBallId = (int)$activeBallEvent['id'];
        
        $burnedCheck = $pdo->prepare("
            SELECT COUNT(*) FROM ball_tickets 
            WHERE status = 'used' 
              AND (buyer_id = ? OR partner_id = ?) 
              AND (announcement_id = ? OR (announcement_id IS NULL AND created_at >= ?))
        ");
        $burnedCheck->execute([$userId, $userId, $currentBallId, $activeBallEvent['created_at']]);
        if ((int)$burnedCheck->fetchColumn() > 0) {
            $msg = "Билет уже просканирован.";
            $msgType = 'error';
        }
    }

    $isDouble = isset($_POST['is_double']) ? 1 : 0;
    $partnerId = $isDouble ? (int)($_POST['partner_id'] ?? 0) : null;
    $partnerName = '';

    if ($msgType !== 'error' && $isDouble) {
        if ($partnerId <= 0 || $partnerId === (int)$userId) {
            $msg = "Выберите существующего ученика лицея в качестве пары (нельзя выбрать самого себя).";
            $msgType = 'error';
        } else {
            $pStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'student'");
            $pStmt->execute([$partnerId]);
            $pRow = $pStmt->fetch();
            if (!$pRow) {
                $msg = "Выбранный пользователь не найден в системе.";
                $msgType = 'error';
            } else {
                $partnerName = $pRow['full_name'];
            }
        }
    }

    if ($msgType !== 'error') {
        $isActivist = (($userData['branch'] ?? 'none') !== 'none');
        $pricePerTicket = $isActivist ? (int)round($ballPrice * 0.5) : $ballPrice;
        $totalPaid = $isDouble ? $pricePerTicket * 2 : $pricePerTicket;

        $ch = $pdo->prepare("SELECT hours, work_type FROM work_checks WHERE student_id = ?");
        $ch->execute([$userId]);
        $earned = 0;
        foreach ($ch->fetchAll() as $r) { $earned += (float)$r['hours'] * (($r['work_type'] === 'mental') ? $rateMent : $ratePhys); }
        $fn = $pdo->prepare("SELECT SUM(amount) FROM fines WHERE student_id = ?"); $fn->execute([$userId]);
        $finesAmt = (int)$fn->fetchColumn();
        $co = $pdo->prepare("SELECT SUM(amount) FROM cash_outs WHERE student_id = ?"); $co->execute([$userId]);
        $cashedAmt = (int)$co->fetchColumn();
        $balance = $earned - $finesAmt - $cashedAmt;

        if ($balance < $totalPaid) {
            $msg = "Недостаточно лир на балансе (нужно $totalPaid, есть $balance).";
            $msgType = 'error';
        } else {
            $ticketCode = 'RLI-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $currentBallId = (int)$activeBallEvent['id'];
            try {
                $pdo->prepare("INSERT INTO ball_tickets (announcement_id, buyer_id, is_double, partner_id, partner_name, total_paid, ticket_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')")
                    ->execute([$currentBallId, $userId, $isDouble, $partnerId, $partnerName, $totalPaid, $ticketCode]);
            } catch (Throwable $e) {
                // Фоллбэк, если столбец announcement_id ещё не добавлен в старой БД
                $pdo->prepare("INSERT INTO ball_tickets (buyer_id, is_double, partner_id, partner_name, total_paid, ticket_code, status) VALUES (?, ?, ?, ?, ?, ?, 'active')")
                    ->execute([$userId, $isDouble, $partnerId, $partnerName, $totalPaid, $ticketCode]);
            }
            $pdo->prepare("INSERT INTO cash_outs (student_id, speaker_id, amount) VALUES (?, ?, ?)")->execute([$userId, $userId, $totalPaid]);
            $msg = "Билет куплен! Код билета: $ticketCode";
        }
    }
}

$allStudentsForSearch = $pdo->query("SELECT id, full_name, school_class, profile, residence_type FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll();
?>

<script src="assets/js/qrcode.min.js"></script>

<div class="container dash-wrap">
    <?php if ($userRole === 'student'): ?>
        <?php
        $checks = $pdo->prepare("SELECT wc.*, u.full_name AS teacher_name FROM work_checks wc JOIN users u ON wc.teacher_id = u.id WHERE wc.student_id = ? ORDER BY wc.created_at DESC");
        $checks->execute([$userId]);
        $chList = $checks->fetchAll();

        $finesSt = $pdo->prepare("SELECT f.*, u.full_name AS officer_name FROM fines f JOIN users u ON f.officer_id = u.id WHERE f.student_id = ? ORDER BY f.created_at DESC");
        $finesSt->execute([$userId]);
        $fnList = $finesSt->fetchAll();

        $totEarned = 0; foreach ($chList as $c) { $totEarned += (float)$c['hours'] * (($c['work_type'] === 'mental') ? $rateMent : $ratePhys); }
        $totFines = 0; foreach ($fnList as $f) { $totFines += (int)$f['amount']; }
        $coSt = $pdo->prepare("SELECT SUM(amount) FROM cash_outs WHERE student_id = ?"); $coSt->execute([$userId]); $totCashed = (int)$coSt->fetchColumn();
        $finalBal = $totEarned - $totFines - $totCashed;

        $isActivist = (($userData['branch'] ?? 'none') !== 'none');
        $myPrice = $isActivist ? (int)round($ballPrice * 0.5) : $ballPrice;

        $myTickets = $pdo->prepare("
            SELECT bt.*, ub.full_name AS buyer_name 
            FROM ball_tickets bt 
            JOIN users ub ON bt.buyer_id = ub.id 
            WHERE bt.buyer_id = ? OR bt.partner_id = ? 
            ORDER BY bt.created_at DESC
        ");
        $myTickets->execute([$userId, $userId]);
        $myTicketsList = $myTickets->fetchAll();

   
        $hasScannedTicketForCurrentBall = false;
        if ($activeBallEvent) {
            foreach ($myTicketsList as $tkItem) {
                $sameEvent = (!empty($tkItem['announcement_id']) && (int)$tkItem['announcement_id'] === (int)$activeBallEvent['id'])
                    || (empty($tkItem['announcement_id']) && strtotime($tkItem['created_at']) >= strtotime($activeBallEvent['created_at']));
                if ($sameEvent && $tkItem['status'] === 'used') {
                    $hasScannedTicketForCurrentBall = true;
                    break;
                }
            }
        }
        ?>

        <div class="dash-header-card">
            <div>
                <span style="font-size:11px; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;"><?= htmlspecialchars(($userData['branch'] ?? 'none') !== 'none' ? $userData['branch'] . ' / ' . $userData['position_title'] : 'Ученик лицея') ?></span>
                <h2 style="font-size:20px;"><?= htmlspecialchars($userData['full_name']) ?></h2>
                <p style="font-size:13px; color:var(--text-muted);"><?= htmlspecialchars($userData['school_class']) ?> класс | Профиль: <?= htmlspecialchars($profileNames[$userData['profile']] ?? $userData['profile']) ?> | Проживание: <?= htmlspecialchars($residenceNames[$userData['residence_type']] ?? $userData['residence_type']) ?></p>
            </div>
            <div class="hours-counter"><span>Баланс</span><strong style="color:#14532d;"><?= number_format($finalBal, 0, '.', ' ') ?> л.</strong></div>
        </div>
    <?php else: ?>
        <div class="dash-header-card">
            <div>
                <span style="font-size:11px; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:4px;"><?= htmlspecialchars($userData['branch'] !== 'none' ? $userData['branch'] : 'Педагогический состав') ?></span>
                <h2 style="font-size:20px;"><?= htmlspecialchars($userData['full_name']) ?></h2>
                <p style="font-size:13px; color:var(--text-muted);">Должность: <?= htmlspecialchars($userData['position_title'] ?: 'Учитель') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="<?= $msgType === 'error' ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>


    <?php if ($userRole === 'student'): ?>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:24px;">
            <div class="auth-box" style="max-width:100%; margin:0;">
                <h3>Мои начисления (трудовые часы)</h3>
                <?php if (empty($chList)): ?>
                    <p style="font-size:13px; color:var(--text-muted);">Начислений пока не было.</p>
                <?php else: ?>
                    <?php foreach ($chList as $c): 
                        $sum = (float)$c['hours'] * (($c['work_type'] === 'mental') ? $rateMent : $ratePhys); ?>
                        <div class="receipt-card">
                            <div class="receipt-top"><span>+<?= number_format($sum, 0, '.', ' ') ?> лир</span><span><?= date('d.m.Y', strtotime($c['created_at'])) ?></span></div>
                            <span style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($c['reason']) ?> (<?= $c['work_type'] === 'mental' ? 'умственный' : 'физический' ?> труд, <?= htmlspecialchars($c['teacher_name']) ?>)</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div class="auth-box" style="max-width:100%; margin:0 0 20px;">
                    <h3>Билет на бал (Осенний / Весенний)</h3>
                    <?php if (!$activeBallEvent): ?>
                        <div style="padding:14px; background:#e2e8f0; border:1.5px solid #64748b; color:#1e293b; font-size:13px;">
                            Покупка билетов сейчас закрыта. Оформить билет можно только во время специального события (Бала), объявленного Президентом.
                        </div>
                    <?php elseif ($hasScannedTicketForCurrentBall): ?>
                        <div style="padding:14px; background:#fee2e2; border:1.5px solid #7f1d1d; color:#7f1d1d; font-size:14px; font-weight:900;">
                            Билет уже просканирован
                        </div>
                    <?php else: ?>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">
                            Активное событие: <strong><?= htmlspecialchars($activeBallEvent['title']) ?></strong> (до <?= date('d.m.Y H:i', strtotime($activeBallEvent['expires_at'])) ?>)
                        </p>
                        <p style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">
                            Цена билета: <strong><?= $ballPrice ?> лир</strong>.
                            <?php if ($isActivist): ?>
                                <span style="color:#14532d; font-weight:900;">Ваша активистская скидка 50% — билет за <?= $myPrice ?> лир!</span>
                            <?php endif; ?>
                        </p>
                        <form method="POST" onsubmit="return rliConfirmForm(this, 'Покупка билета на бал', 'Подтвердить покупку билета? С вашего баланса будут списаны лиры.', 'Купить билет', false);">
                            <input type="hidden" name="buy_ball_ticket" value="1">
                            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="is_double" value="1" id="dbl" onchange="document.getElementById('partnerWrap').style.display = this.checked ? 'block' : 'none';">
                                <label for="dbl" style="margin:0;">Билет на двоих (позвать пару из лицея)</label>
                            </div>
                            <div class="form-group" id="partnerWrap" style="display:none;">
                                <label>Поиск пары среди зарегистрированных учеников</label>
                                <input type="text" id="partnerSearchInput" class="form-control" placeholder="Введите ФИО ученика..." oninput="filterPartnerOptions()" style="margin-bottom:8px;">
                                <select name="partner_id" id="partnerSelect" class="form-control" size="4" style="height:auto;">
                                    <option value="">— Выберите пару из списка —</option>
                                    <?php foreach ($allStudentsForSearch as $stOpt): if ((int)$stOpt['id'] === (int)$userId) continue; ?>
                                        <option value="<?= $stOpt['id'] ?>" data-search="<?= htmlspecialchars(mb_strtolower($stOpt['full_name'] . ' ' . $stOpt['school_class'] . ' ' . ($profileNames[$stOpt['profile']] ?? ''))) ?>">
                                            <?= htmlspecialchars($stOpt['full_name']) ?> (<?= htmlspecialchars($stOpt['school_class']) ?> кл., <?= htmlspecialchars($profileNames[$stOpt['profile']] ?? $stOpt['profile']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary">Купить билет</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="auth-box" style="max-width:100%; margin:0;">
                    <h3>Мои билеты</h3>
                    <?php if (empty($myTicketsList)): ?>
                        <p style="font-size:13px; color:var(--text-muted);">Билетов пока нет.</p>
                    <?php else: ?>
                        <?php foreach ($myTicketsList as $t): 
                            $isBurned = ($t['status'] === 'used');
                        ?>
                            <div class="receipt-card" style="text-align:center; <?= $isBurned ? 'background:#e2e8f0; border-color:#64748b;' : '' ?>">
                                <div class="receipt-top">
                                    <span><?= $t['is_double'] ? 'На двоих' : 'Одиночный' ?></span>
                                    <?php if ($isBurned): ?>
                                        <span class="badge-burned">БИЛЕТ УЖЕ ПРОСКАНИРОВАН</span>
                                    <?php else: ?>
                                        <span class="badge-active">АКТИВЕН • <?= $t['total_paid'] ?> лир</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($t['is_double']): ?>
                                    <p style="font-size:12px; margin-bottom:6px;">
                                        Владелец: <?= htmlspecialchars($t['buyer_name']) ?> • Пара: <?= htmlspecialchars($t['partner_name']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($isBurned): ?>
                                    <div style="padding:14px; border:1.5px solid #7f1d1d; background:#fee2e2; margin:8px 0; color:#7f1d1d; font-size:13px; font-weight:900;">
                                        Билет уже просканирован
                                    </div>
                                <?php else: ?>
                                    <div class="qr-box" data-code="<?= htmlspecialchars($t['ticket_code']) ?>" style="display:flex; justify-content:center; margin:8px 0;"></div>
                                <?php endif; ?>
                                <strong style="font-size:14px; color:<?= $isBurned ? '#475569; text-decoration:line-through;' : 'var(--primary);' ?>"><?= htmlspecialchars($t['ticket_code']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Мои штрафы (СБРЛ)</h3>
            <?php if (empty($fnList)): ?>
                <p style="font-size:13px; color:var(--text-muted);">Штрафов нет. Так держать!</p>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Сумма</th><th>Дата</th><th>Сотрудник</th></tr></thead>
                    <tbody>
                        <?php foreach ($fnList as $f): ?>
                            <tr><td><strong style="color:#7f1d1d;">-<?= (int)$f['amount'] ?> лир</strong></td><td><?= date('d.m.Y', strtotime($f['created_at'])) ?></td><td><?= htmlspecialchars($f['officer_name']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

  
    <?php if ($isHeadTeacher): ?>
        <?php 
        $allUsersList = $pdo->query("SELECT id, full_name, position_title FROM users ORDER BY full_name")->fetchAll();
        $mealFilterDate = $_GET['meal_date'] ?? date('Y-m-d');
        $mealFilterType = $_GET['meal_type'] ?? 'all';
        $mealFilterRes = $_GET['meal_res'] ?? 'all';
        $mealFilterClass = $_GET['meal_class'] ?? 'all';
        $mealFilterStatus = $_GET['meal_status'] ?? 'all';

        $mealSql = "
            SELECT mc.choice_date, mc.meal_type, mc.will_eat, u.full_name, u.school_class, u.profile, u.residence_type
            FROM meal_choices mc
            JOIN users u ON mc.user_id = u.id
            WHERE mc.choice_date = ?
        ";
        $mealParams = [$mealFilterDate];
        if (in_array($mealFilterType, ['breakfast', 'lunch', 'dinner'], true)) {
            $mealSql .= " AND mc.meal_type = ?";
            $mealParams[] = $mealFilterType;
        }
        if (in_array($mealFilterRes, ['city', 'boarding'], true)) {
            $mealSql .= " AND u.residence_type = ?";
            $mealParams[] = $mealFilterRes;
        }
        if (in_array($mealFilterClass, ['10', '11'], true)) {
            $mealSql .= " AND u.school_class = ?";
            $mealParams[] = $mealFilterClass;
        }
        if ($mealFilterStatus === '1' || $mealFilterStatus === '0') {
            $mealSql .= " AND mc.will_eat = ?";
            $mealParams[] = (int)$mealFilterStatus;
        }
        $mealSql .= " ORDER BY FIELD(mc.meal_type, 'breakfast', 'lunch', 'dinner'), mc.will_eat DESC, u.school_class ASC, u.profile ASC, u.full_name ASC";
        $mealSt = $pdo->prepare($mealSql);
        $mealSt->execute($mealParams);
        $mealRecords = $mealSt->fetchAll();

        $eatCounts = ['breakfast' => 0, 'lunch' => 0, 'dinner' => 0];
        foreach ($mealRecords as $mr) {
            if ((int)$mr['will_eat'] === 1 && isset($eatCounts[$mr['meal_type']])) {
                $eatCounts[$mr['meal_type']]++;
            }
        }
        ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Панель Завуча и Директора: Управление должностями</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="update_role" value="1">
                <div class="form-group" style="margin:0;"><label>Пользователь</label><select name="target_user_id" class="form-control" required><?php foreach ($allUsersList as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['position_title'] ?: 'без должности') ?>)</option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label>Должность</label><select name="position_code" class="form-control" required><?php foreach ($officialPositions as $code => $info): ?><option value="<?= $code ?>"><?= htmlspecialchars($info['name']) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn-primary">Применить</button>
            </form>
        </div>

        <div class="auth-box" style="max-width:100%; margin-bottom:24px;" id="eatingListSection">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                <h3 style="margin:0;">Список кушания (отсортированный учёт питания)</h3>
                <a href="schedule.php" class="btn-primary" style="padding:6px 14px;">Управление расписанием 12 классов →</a>
            </div>

            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:16px;">
                <div class="receipt-card" style="margin:0; text-align:center;">
                    <span style="font-size:11px; color:var(--text-muted);">ЗАВТРАК (БУДУТ ЕСТЬ)</span>
                    <strong style="display:block; font-size:18px; color:var(--primary);"><?= $eatCounts['breakfast'] ?> чел.</strong>
                </div>
                <div class="receipt-card" style="margin:0; text-align:center;">
                    <span style="font-size:11px; color:var(--text-muted);">ОБЕД (БУДУТ ЕСТЬ)</span>
                    <strong style="display:block; font-size:18px; color:var(--primary);"><?= $eatCounts['lunch'] ?> чел.</strong>
                </div>
                <div class="receipt-card" style="margin:0; text-align:center;">
                    <span style="font-size:11px; color:var(--text-muted);">УЖИН (БУДУТ ЕСТЬ)</span>
                    <strong style="display:block; font-size:18px; color:var(--primary);"><?= $eatCounts['dinner'] ?> чел.</strong>
                </div>
            </div>

            <form method="GET" action="dashboard.php#eatingListSection" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:10px; align-items:end; margin-bottom:16px; background:#e2e8f0; padding:12px; border:1px solid #94a3b8; border-radius:var(--radius);">
                <div class="form-group" style="margin:0;">
                    <label>Дата</label>
                    <input type="date" name="meal_date" class="form-control" value="<?= htmlspecialchars($mealFilterDate) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Приём пищи</label>
                    <select name="meal_type" class="form-control">
                        <option value="all">Все приёмы</option>
                        <option value="breakfast" <?= $mealFilterType === 'breakfast' ? 'selected' : '' ?>>Завтрак</option>
                        <option value="lunch" <?= $mealFilterType === 'lunch' ? 'selected' : '' ?>>Обед</option>
                        <option value="dinner" <?= $mealFilterType === 'dinner' ? 'selected' : '' ?>>Ужин</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Тип проживания</label>
                    <select name="meal_res" class="form-control">
                        <option value="all">Все</option>
                        <option value="city" <?= $mealFilterRes === 'city' ? 'selected' : '' ?>>Городской</option>
                        <option value="boarding" <?= $mealFilterRes === 'boarding' ? 'selected' : '' ?>>Интернатский</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Параллель</label>
                    <select name="meal_class" class="form-control">
                        <option value="all">Все классы</option>
                        <option value="10" <?= $mealFilterClass === '10' ? 'selected' : '' ?>>10 класс</option>
                        <option value="11" <?= $mealFilterClass === '11' ? 'selected' : '' ?>>11 класс</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Статус</label>
                    <select name="meal_status" class="form-control">
                        <option value="all">Все отметки</option>
                        <option value="1" <?= $mealFilterStatus === '1' ? 'selected' : '' ?>>Будет есть</option>
                        <option value="0" <?= $mealFilterStatus === '0' ? 'selected' : '' ?>>Не будет есть</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Фильтровать</button>
            </form>

            <?php if (empty($mealRecords)): ?>
                <p style="font-size:13px; color:var(--text-muted);">На выбранную дату отметок питания пока нет.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Приём пищи</th>
                            <th>ФИО ученика</th>
                            <th>Класс / Профиль</th>
                            <th>Проживание</th>
                            <th>Решение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mealRecords as $mr): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mealNames[$mr['meal_type']] ?? $mr['meal_type']) ?></strong></td>
                                <td><?= htmlspecialchars($mr['full_name']) ?></td>
                                <td><?= htmlspecialchars($mr['school_class']) ?> кл. (<?= htmlspecialchars($profileNames[$mr['profile']] ?? $mr['profile']) ?>)</td>
                                <td><?= htmlspecialchars($residenceNames[$mr['residence_type']] ?? $mr['residence_type']) ?></td>
                                <td>
                                    <?php if ((int)$mr['will_eat'] === 1): ?>
                                        <span class="badge-active">Будет есть</span>
                                    <?php else: ?>
                                        <span class="badge-burned">Не будет есть</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ПАНЕЛЬ ПРЕЗИДЕНТА -->
    <?php if ($isPresident): ?>
        <?php $activeSpecials = $pdo->query("SELECT * FROM special_announcements WHERE expires_at > NOW() ORDER BY created_at DESC")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Панель Президента: Специальные объявления</h3>
            <form method="POST" style="margin-bottom:20px; border-bottom:1px solid #94a3b8; padding-bottom:16px;">
                <input type="hidden" name="create_special_announcement" value="1">
                <div class="form-group"><label>Заголовок</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label>Текст</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                <div class="form-group"><label>Часы показа</label><input type="number" name="duration_hours" class="form-control" value="72" min="1" required></div>
                <div class="form-group" style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="is_ball" value="1" id="ib"><label for="ib" style="margin:0;">Сделать объявлением о Бале</label></div>
                <button type="submit" class="btn-primary">Опубликовать</button>
            </form>

            <h4 style="font-size:14px; margin-bottom:10px;">Активные специальные объявления (можно удалить досрочно)</h4>
            <?php if (empty($activeSpecials)): ?>
                <p style="font-size:13px; color:var(--text-muted);">Активных специальных объявлений нет.</p>
            <?php else: ?>
                <?php foreach ($activeSpecials as $sa): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 12px; background:#ffffff; border:1px solid #94a3b8; border-radius:var(--radius); margin-bottom:8px;">
                        <div>
                            <strong><?= htmlspecialchars($sa['title']) ?></strong>
                            <?= $sa['is_ball'] ? '<span class="badge-active" style="margin-left:6px;">БАЛ</span>' : '' ?>
                            <div style="font-size:12px; color:var(--text-muted);">Действует до: <?= date('d.m.Y H:i', strtotime($sa['expires_at'])) ?></div>
                        </div>
                        <form method="POST" style="margin:0;" onsubmit="return rliConfirmForm(this, 'Удаление объявления', 'Удалить специальное объявление пораньше? Если это объявление о бале, активные билеты учеников сгорят.', 'Удалить досрочно', true);">
                            <input type="hidden" name="delete_special_id" value="<?= $sa['id'] ?>">
                            <button type="submit" class="btn-primary" style="padding:5px 12px; background:#7f1d1d; border-color:#7f1d1d; color:#fff;">Удалить пораньше</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <?php if ($isPremier): ?>
        <?php $allNews = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Панель Премьер-министра: Управление новостями</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-bottom:20px; border-bottom:1px solid #94a3b8; padding-bottom:15px;">
                <input type="hidden" name="publish_news" value="1">
                <div class="form-group"><label>Заголовок</label><input type="text" name="title" class="form-control" required></div>
                <div class="form-group"><label>Фото</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                <div class="form-group"><label>Текст</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                <button type="submit" class="btn-primary">Опубликовать</button>
            </form>
            <?php foreach ($allNews as $item): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#ffffff; border:1px solid #94a3b8; margin-bottom:6px; border-radius:3px;">
                    <span><?= htmlspecialchars($item['title']) ?></span>
                    <form method="POST" onsubmit="return rliConfirmForm(this, 'Удаление новости', 'Вы уверены, что хотите удалить эту новость?', 'Удалить', true);"><input type="hidden" name="delete_news_id" value="<?= $item['id'] ?>"><button type="submit" class="btn-primary" style="padding:4px 10px; background:#7f1d1d; border-color:#7f1d1d; color:#fff;">Удалить</button></form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <?php if ($isSpeaker): ?>
        <?php 
        $allSt = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll();
        $ballTicketsList = $pdo->query("SELECT bt.*, u.full_name AS buyer_name FROM ball_tickets bt JOIN users u ON bt.buyer_id = u.id ORDER BY bt.created_at DESC")->fetchAll();
        $lawsList = $pdo->query("SELECT * FROM laws ORDER BY created_at DESC")->fetchAll();
        ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Учет лиров (Председатель Парламента)</h3>
            <table class="data-table" style="margin-bottom:24px;">
                <thead><tr><th>Ученик</th><th>Профиль</th><th>Баланс</th><th>Действие</th></tr></thead>
                <tbody>
                    <?php foreach ($allSt as $st): 
                        $ch = $pdo->prepare("SELECT hours, work_type FROM work_checks WHERE student_id = ?"); $ch->execute([$st['id']]);
                        $er = 0; foreach ($ch->fetchAll() as $r) { $er += (float)$r['hours'] * (($r['work_type'] === 'mental') ? $rateMent : $ratePhys); }
                        $fn = $pdo->prepare("SELECT SUM(amount) FROM fines WHERE student_id = ?"); $fn->execute([$st['id']]); $fnAmt = (int)$fn->fetchColumn();
                        $co = $pdo->prepare("SELECT SUM(amount) FROM cash_outs WHERE student_id = ?"); $co->execute([$st['id']]); $coAmt = (int)$co->fetchColumn();
                        $bal = $er - $fnAmt - $coAmt;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($st['full_name']) ?></td>
                            <td><?= htmlspecialchars($profileNames[$st['profile']] ?? $st['profile']) ?></td>
                            <td><strong><?= $bal ?> л.</strong></td>
                            <td>
                                <?php if ($bal > 0): ?>
                                    <form method="POST" onsubmit="return rliConfirmForm(this, 'Обналичивание лиров', 'Списать весь баланс ученика при обналичивании?', 'Обналичить', false);"><input type="hidden" name="cash_out_student" value="1"><input type="hidden" name="target_student_id" value="<?= $st['id'] ?>"><input type="hidden" name="amount_to_cash" value="<?= $bal ?>"><button type="submit" class="btn-primary" style="padding:4px 10px;">Обналичить</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 style="margin-bottom:12px;">Ставки зарплат за труд (лиры)</h3>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end; margin-bottom:24px;">
                <input type="hidden" name="update_rates" value="1">
                <div class="form-group" style="margin:0;"><label>Физический труд (за час)</label><input type="number" name="rate_physical" class="form-control" value="<?= $ratePhys ?>" step="1" required></div>
                <div class="form-group" style="margin:0;"><label>Умственный труд (за час)</label><input type="number" name="rate_mental" class="form-control" value="<?= $rateMent ?>" step="1" required></div>
                <button type="submit" class="btn-primary">Сохранить ставки</button>
            </form>

            <h3 style="margin-bottom:12px;">Законы Парламента РЛИ</h3>
            <form method="POST" style="margin-bottom:16px; border-bottom:1px solid #94a3b8; padding-bottom:15px;">
                <input type="hidden" name="add_law" value="1">
                <div class="form-group"><label>Название закона</label><input type="text" name="law_title" class="form-control" required></div>
                <div class="form-group"><label>Текст закона</label><textarea name="law_content" class="form-control" rows="3" required></textarea></div>
                <button type="submit" class="btn-primary">Принять закон</button>
            </form>
            <?php foreach ($lawsList as $law): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#ffffff; border:1px solid #94a3b8; margin-bottom:6px; border-radius:3px;">
                    <span><strong><?= htmlspecialchars($law['title']) ?></strong> <span style="color:var(--text-muted); font-size:12px;">(<?= date('d.m.Y', strtotime($law['created_at'])) ?>)</span></span>
                    <form method="POST" onsubmit="return rliConfirmForm(this, 'Удаление закона', 'Вы действительно хотите удалить этот закон Парламента?', 'Удалить', true);"><input type="hidden" name="delete_law_id" value="<?= $law['id'] ?>"><button type="submit" class="btn-primary" style="padding:4px 10px; background:#7f1d1d; border-color:#7f1d1d; color:#fff;">Удалить</button></form>
                </div>
            <?php endforeach; ?>

            <h3 style="margin:24px 0 12px;">Список купивших билеты на бал</h3>
            <table class="data-table">
                <thead><tr><th>Покупатель</th><th>Тип</th><th>Сумма</th><th>Код билета</th><th>Статус</th></tr></thead>
                <tbody>
                    <?php foreach ($ballTicketsList as $bt): ?>
                        <tr>
                            <td><?= htmlspecialchars($bt['buyer_name']) ?></td>
                            <td><?= $bt['is_double'] ? 'На двоих (' . htmlspecialchars($bt['partner_name']) . ')' : 'Одиночный' ?></td>
                            <td><?= $bt['total_paid'] ?> лир</td>
                            <td><strong><?= htmlspecialchars($bt['ticket_code']) ?></strong></td>
                            <td><?= $bt['status'] === 'used' ? '<span class="badge-burned">Сожжён</span>' : '<span class="badge-active">Активен</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>


    <?php if ($isSbrlStaff): ?>
        <?php $stList = $pdo->query("SELECT id, full_name, school_class, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Панель СБРЛ: Контроль входа на бал (Сканер QR-кода билета)</h3>
            <p style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">Отсканируйте QR-код камерой или введите код билета вручную, чтобы проверить его в базе проданных билетов и сжечь при входе.</p>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
                <button type="button" class="btn-primary" id="startQrCamBtn" onclick="startQrScanner()">Включить камеру для сканирования QR</button>
                <button type="button" class="btn-hero" id="stopQrCamBtn" style="display:none;" onclick="stopQrScanner()">Остановить камеру</button>
            </div>

            <div id="qrScannerContainer" style="display:none; margin-bottom:16px; max-width:380px; border:2px solid #0f172a; border-radius:var(--radius); overflow:hidden; background:#000;">
                <video id="qrVideo" style="width:100%; display:block;" playsinline></video>
                <div id="qrCamStatus" style="background:#0f172a; color:#fff; font-size:12px; padding:6px 10px;">Наведите камеру на QR-код ученика...</div>
            </div>

            <form method="POST" id="ticketCheckForm" style="display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="check_ticket_code" value="1">
                <div class="form-group" style="margin:0;">
                    <label>Код билета (например, RLI-XXXXXXXXXX)</label>
                    <input type="text" name="ticket_code" id="ticketCodeInput" class="form-control" value="<?= htmlspecialchars($_POST['ticket_code'] ?? '') ?>" placeholder="Введите или отсканируйте код..." required>
                </div>
                <button type="submit" class="btn-primary">Проверить билет</button>
            </form>

            <?php if ($scannedTicket): ?>
                <div style="margin-top:16px; padding:16px; border:2px solid <?= $scannedTicket['status'] === 'active' ? '#14532d' : '#7f1d1d' ?>; background:#ffffff; border-radius:var(--radius);">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
                        <strong style="font-size:16px;">Билет <?= htmlspecialchars($scannedTicket['ticket_code']) ?></strong>
                        <?php if ($scannedTicket['status'] === 'active'): ?>
                            <span class="badge-active">ДЕЙСТВИТЕЛЕН (НЕ ИСПОЛЬЗОВАН)</span>
                        <?php else: ?>
                            <span class="badge-burned">БИЛЕТ УЖЕ ПРОСКАНИРОВАН</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Владелец:</strong> <?= htmlspecialchars($scannedTicket['buyer_name']) ?> (<?= htmlspecialchars($scannedTicket['school_class']) ?> кл., <?= htmlspecialchars($profileNames[$scannedTicket['profile']] ?? $scannedTicket['profile']) ?>)</p>
                    <p style="font-size:13px; margin-bottom:4px;"><strong>Тип билета:</strong> <?= $scannedTicket['is_double'] ? 'На двоих — пара: ' . htmlspecialchars($scannedTicket['partner_name']) : 'Одиночный' ?></p>
                    <p style="font-size:13px; margin-bottom:12px;"><strong>Оплачено:</strong> <?= (int)$scannedTicket['total_paid'] ?> лир</p>

                    <?php if ($scannedTicket['status'] === 'active'): ?>
                        <form method="POST" onsubmit="return rliConfirmForm(this, 'Одобрение и сжигание билета', 'Одобрить проход и безвозвратно сжечь билет <?= htmlspecialchars($scannedTicket['ticket_code']) ?>?', 'Одобрить и сжечь билет', true);">
                            <input type="hidden" name="burn_ticket_id" value="<?= $scannedTicket['id'] ?>">
                            <button type="submit" class="btn-primary" style="background:#14532d; border-color:#14532d; color:#fff;">Одобрить вход и сжечь билет</button>
                        </form>
                    <?php else: ?>
                        <div style="padding:10px 12px; background:#fee2e2; border:1px solid #7f1d1d; color:#7f1d1d; font-size:13px; font-weight:900;">
                            Билет уже просканирован<?= !empty($scannedTicket['used_at']) ? ' (' . date('d.m.Y H:i', strtotime($scannedTicket['used_at'])) . ')' : '' ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Панель СБРЛ: Выписать штраф</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 2fr auto; gap:12px; align-items:end;" onsubmit="return rliConfirmForm(this, 'Выписка штрафа', 'Выписать выбранный штраф ученику?', 'Выписать штраф', true);">
                <input type="hidden" name="issue_fine" value="1">
                <div class="form-group" style="margin:0;"><label>Ученик</label><select name="student_id" class="form-control"><?php foreach ($stList as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['school_class']) ?> кл.)</option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label>Причина</label><select name="reason_type" class="form-control"><option value="1">Нарушение очереди (-200)</option><option value="2">Фейковое имя (-300)</option><option value="3">Оскорбление (-450)</option></select></div>
                <button type="submit" class="btn-primary">Штрафовать</button>
            </form>
        </div>
    <?php endif; ?>

  
    <?php if ($isTeacher && !$isHeadTeacher): ?>
        <?php $stListT = $pdo->query("SELECT id, full_name, profile FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(); ?>
        <div class="auth-box" style="max-width:100%; margin-bottom:24px;">
            <h3>Начислить трудовые часы ученику</h3>
            <form method="POST" style="display:grid; grid-template-columns: 2fr 1fr 1fr 2fr auto; gap:12px; align-items:end;">
                <input type="hidden" name="issue_check" value="1">
                <div class="form-group" style="margin:0;"><label>Ученик</label><select name="student_id" class="form-control"><?php foreach ($stListT as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin:0;"><label>Часы</label><input type="number" name="hours" class="form-control" step="0.5" min="0.5" value="1" required></div>
                <div class="form-group" style="margin:0;"><label>Тип труда</label><select name="work_type" class="form-control"><option value="physical">Физический</option><option value="mental">Умственный</option></select></div>
                <div class="form-group" style="margin:0;"><label>За что</label><input type="text" name="reason" class="form-control" required></div>
                <button type="submit" class="btn-primary">Начислить</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.qr-box').forEach(function(box) {
    new QRCode(box, {
        text: box.getAttribute('data-code'),
        width: 120,
        height: 120,
        colorDark: '#0f172a',
        colorLight: '#ffffff'
    });
});

function filterPartnerOptions() {
    const query = document.getElementById('partnerSearchInput').value.toLowerCase().trim();
    const select = document.getElementById('partnerSelect');
    Array.from(select.options).forEach(function(opt) {
        if (!opt.value) return;
        const hay = opt.getAttribute('data-search') || opt.text.toLowerCase();
        opt.style.display = (query === '' || hay.indexOf(query) !== -1) ? '' : 'none';
    });
}

let qrStream = null;
let qrScanTimer = null;

async function startQrScanner() {
    const container = document.getElementById('qrScannerContainer');
    const video = document.getElementById('qrVideo');
    const status = document.getElementById('qrCamStatus');
    const startBtn = document.getElementById('startQrCamBtn');
    const stopBtn = document.getElementById('stopQrCamBtn');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        openRliModal('Камера недоступна', 'Браузер не поддерживает доступ к камере. Введите код билета в поле вручную или используйте USB-сканер.', [{ label: 'Понятно', primary: true }]);
        return;
    }

    try {
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        video.srcObject = qrStream;
        await video.play();
        container.style.display = 'block';
        startBtn.style.display = 'none';
        stopBtn.style.display = 'inline-block';

        if ('BarcodeDetector' in window) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            qrScanTimer = setInterval(async function() {
                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0 && codes[0].rawValue) {
                        document.getElementById('ticketCodeInput').value = codes[0].rawValue.trim();
                        stopQrScanner();
                        document.getElementById('ticketCheckForm').submit();
                    }
                } catch (e) {}
            }, 400);
        } else {
            status.innerText = 'Камера включена. Если автозахват QR не поддерживается браузером, введите код вручную.';
        }
    } catch (err) {
        openRliModal('Ошибка камеры', 'Не удалось получить доступ к камере: ' + err.message, [{ label: 'Закрыть', primary: true }]);
    }
}

function stopQrScanner() {
    if (qrScanTimer) {
        clearInterval(qrScanTimer);
        qrScanTimer = null;
    }
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
    const container = document.getElementById('qrScannerContainer');
    if (container) container.style.display = 'none';
    const startBtn = document.getElementById('startQrCamBtn');
    const stopBtn = document.getElementById('stopQrCamBtn');
    if (startBtn) startBtn.style.display = 'inline-block';
    if (stopBtn) stopBtn.style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>