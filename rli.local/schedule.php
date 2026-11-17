<?php
$pageTitle = 'Расписание | РЛИ';
include 'includes/header.php';

$msg = '';
$isLoggedIn = isset($_SESSION['user_id']);
$canEdit = false;
$userDefaultClass = '10_fizmat';


$twelveClasses = [
    '10_fizmat'       => '10 Физико-математический',
    '10_engineering'  => '10 Инженерный',
    '10_polytech'     => '10 Политехнический',
    '10_rosneft'      => '10 Технический (Роснефть)',
    '10_biohim'       => '10 Биолого-химический',
    '10_humanitarian' => '10 Гуманитарный',
    '11_fizmat'       => '11 Физико-математический',
    '11_engineering'  => '11 Инженерный',
    '11_polytech'     => '11 Политехнический',
    '11_rosneft'      => '11 Технический (Роснефть)',
    '11_biohim'       => '11 Биолого-химический',
    '11_humanitarian' => '11 Гуманитарный'
];

$days = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота'
];

if ($isLoggedIn) {
    $u = $pdo->prepare("SELECT role, position_code, position_title, login, school_class, profile FROM users WHERE id = ?");
    $u->execute([$_SESSION['user_id']]);
    $ud = $u->fetch();
    $canEdit = in_array($ud['position_code'], ['head_teacher', 'director'], true)
        || $ud['login'] === 'director'
        || mb_stripos($ud['position_title'] ?? '', 'Завуч') !== false
        || mb_stripos($ud['position_title'] ?? '', 'Директор') !== false;

    if (!empty($ud['school_class']) && !empty($ud['profile'])) {
        $candidate = $ud['school_class'] . '_' . $ud['profile'];
        if (isset($twelveClasses[$candidate])) {
            $userDefaultClass = $candidate;
        }
    }
}

$selectedClass = $_GET['class_code'] ?? ($_POST['class_code'] ?? $userDefaultClass);
if (!isset($twelveClasses[$selectedClass])) {
    $selectedClass = '10_fizmat';
}


if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lesson'])) {
    $cCode = $_POST['class_code'] ?? '';
    $dayNum = (int)($_POST['day_of_week'] ?? 1);
    $lessonNum = (int)($_POST['lesson_number'] ?? 1);
    $subject = trim($_POST['subject'] ?? '');
    $teacherName = trim($_POST['teacher_name'] ?? '');
    $room = trim($_POST['room'] ?? '');

    if (isset($twelveClasses[$cCode]) && $dayNum >= 1 && $dayNum <= 6 && $lessonNum >= 1 && $lessonNum <= 8) {
        $cParallel = explode('_', $cCode)[0];
        if ($subject === '') {
            $pdo->prepare("DELETE FROM schedule WHERE class_code = ? AND day_of_week = ? AND lesson_number = ?")
                ->execute([$cCode, $dayNum, $lessonNum]);
            $msg = "Урок №{$lessonNum} очищен.";
        } else {
            $pdo->prepare("
                INSERT INTO schedule (class_code, class_name, day_of_week, lesson_number, subject, teacher_name, room)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    subject = VALUES(subject),
                    teacher_name = VALUES(teacher_name),
                    room = VALUES(room)
            ")->execute([$cCode, $cParallel, $dayNum, $lessonNum, $subject, $teacherName, $room]);
            $msg = "Урок №{$lessonNum} ({$twelveClasses[$cCode]}, {$days[$dayNum]}) сохранён.";
        }
        $selectedClass = $cCode;
    }
}

$st = $pdo->prepare("SELECT * FROM schedule WHERE class_code = ? ORDER BY day_of_week, lesson_number");
$st->execute([$selectedClass]);
$rows = $st->fetchAll();

$schedGrid = [];
foreach ($rows as $r) {
    $schedGrid[(int)$r['day_of_week']][(int)$r['lesson_number']] = $r;
}
?>

<div class="container" style="padding: 36px 0;">
    <h1 style="font-size:24px; font-weight:900; margin-bottom:8px;">Расписание занятий (по 8 уроков в день)</h1>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">
        Выберите один из 12 профильных классов лицея.
        <?php if ($canEdit): ?>
            <strong>Режим Завуча и Директора:</strong> нажмите на класс, чтобы просмотреть или заменить любой урок (с 1 по 8).
        <?php endif; ?>
    </p>

    <?php if ($msg): ?><div class="alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

   
    <div style="background:#ffffff; border:1.5px solid var(--border-color); border-radius:var(--radius); padding:16px; margin-bottom:24px;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; margin-bottom:10px;">Все 12 классов лицея:</div>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:8px;">
            <?php foreach ($twelveClasses as $cCode => $cTitle): 
                $isActive = ($cCode === $selectedClass);
            ?>
                <a href="schedule.php?class_code=<?= urlencode($cCode) ?>"
                   class="<?= $isActive ? 'btn-primary' : 'btn-hero' ?>"
                   style="text-align:left; padding:9px 12px; <?= $isActive ? 'background:var(--primary); color:#fff;' : '' ?>">
                    <?= htmlspecialchars($cTitle) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px;">
        <h2 style="font-size:19px; color:var(--primary);"><?= htmlspecialchars($twelveClasses[$selectedClass]) ?> класс</h2>
        <?php if ($canEdit): ?>
            <span class="badge-active">Доступно редактирование уроков</span>
        <?php endif; ?>
    </div>

  
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap:16px;">
        <?php foreach ($days as $dayNum => $dayName): ?>
            <div style="background:#ffffff; border:1.5px solid var(--card-border); border-radius:var(--radius); padding:16px;">
                <h3 style="font-size:15px; font-weight:900; color:var(--text-main); margin-bottom:10px; border-bottom:1.5px solid var(--border-color); padding-bottom:6px;">
                    <?= $dayName ?>
                </h3>
                <table class="data-table" style="border:1px solid var(--border-color);">
                    <thead>
                        <tr>
                            <th style="width:42px; padding:7px 8px;">№</th>
                            <th style="padding:7px 8px;">Предмет / Учитель / Каб.</th>
                            <?php if ($canEdit): ?><th style="width:44px; padding:7px 6px;"></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($lessonNum = 1; $lessonNum <= 8; $lessonNum++): 
                            $item = $schedGrid[$dayNum][$lessonNum] ?? null;
                            $subj = $item['subject'] ?? '';
                            $teach = $item['teacher_name'] ?? '';
                            $room = $item['room'] ?? '';
                        ?>
                            <tr>
                                <td style="padding:7px 8px;"><strong><?= $lessonNum ?></strong></td>
                                <td style="padding:7px 8px;">
                                    <?php if ($subj !== ''): ?>
                                        <div><?= htmlspecialchars($subj) ?></div>
                                        <?php if ($teach !== '' || $room !== ''): ?>
                                            <div style="font-size:11px; color:var(--text-muted);">
                                                <?= htmlspecialchars($teach) ?><?= ($teach !== '' && $room !== '') ? ' • ' : '' ?><?= $room !== '' ? 'каб. ' . htmlspecialchars($room) : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">— нет урока —</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canEdit): ?>
                                    <td style="padding:6px; text-align:right;">
                                        <button type="button"
                                                class="btn-hero"
                                                style="padding:3px 8px; font-size:11px;"
                                                onclick='openLessonEditModal(<?= json_encode([
                                                    "class_code" => $selectedClass,
                                                    "class_title" => $twelveClasses[$selectedClass],
                                                    "day_of_week" => $dayNum,
                                                    "day_name" => $dayName,
                                                    "lesson_number" => $lessonNum,
                                                    "subject" => $subj,
                                                    "teacher_name" => $teach,
                                                    "room" => $room
                                                ], JSON_UNESCAPED_UNICODE) ?>)'>
                                            Изм.
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($canEdit): ?>

<div id="lessonEditOverlay" class="rli-modal-overlay" style="display:none;" onclick="if(event.target===this) closeLessonEditModal();">
    <div class="rli-modal-box" style="max-width:460px;">
        <div class="rli-modal-head">
            <h3 id="lessonModalHeader">Редактирование урока</h3>
            <button type="button" class="rli-modal-close" onclick="closeLessonEditModal()">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="save_lesson" value="1">
            <input type="hidden" name="class_code" id="editClassCode">
            <input type="hidden" name="day_of_week" id="editDayOfWeek">
            <input type="hidden" name="lesson_number" id="editLessonNumber">
            <div class="rli-modal-body">
                <p id="lessonModalSub" style="font-size:12px; color:var(--text-muted); margin-bottom:12px;"></p>
                <div class="form-group">
                    <label>Предмет (оставьте пустым, чтобы убрать урок)</label>
                    <input type="text" name="subject" id="editSubject" class="form-control" placeholder="Например: Алгебра">
                </div>
                <div class="form-group">
                    <label>Преподаватель</label>
                    <input type="text" name="teacher_name" id="editTeacher" class="form-control" placeholder="Петрова М. С.">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Кабинет</label>
                    <input type="text" name="room" id="editRoom" class="form-control" placeholder="305">
                </div>
            </div>
            <div class="rli-modal-actions">
                <button type="button" class="btn-hero" onclick="closeLessonEditModal()">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить урок</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLessonEditModal(data) {
    document.getElementById('lessonModalHeader').innerText = 'Замена / редактирование урока №' + data.lesson_number;
    document.getElementById('lessonModalSub').innerText = data.class_title + ' • ' + data.day_name;
    document.getElementById('editClassCode').value = data.class_code;
    document.getElementById('editDayOfWeek').value = data.day_of_week;
    document.getElementById('editLessonNumber').value = data.lesson_number;
    document.getElementById('editSubject').value = data.subject;
    document.getElementById('editTeacher').value = data.teacher_name;
    document.getElementById('editRoom').value = data.room;
    document.getElementById('lessonEditOverlay').style.display = 'flex';
}
function closeLessonEditModal() {
    document.getElementById('lessonEditOverlay').style.display = 'none';
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>