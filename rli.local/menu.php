<?php
$pageTitle = 'Меню столовой | РЛИ';
include 'includes/header.php';

$msg = '';
$isLoggedIn = isset($_SESSION['user_id']);
$isHeadTeacher = false;
$userRole = $_SESSION['role'] ?? 'guest';
$residenceType = 'city';

if ($isLoggedIn) {
    $u = $pdo->prepare("SELECT residence_type, position_code, role, login FROM users WHERE id = ?");
    $u->execute([$_SESSION['user_id']]);
    $ud = $u->fetch();
    $residenceType = $ud['residence_type'] ?? 'city';
    $isHeadTeacher = ($ud['role'] === 'teacher') || in_array($ud['position_code'], ['head_teacher', 'director'], true) || $ud['login'] === 'director';
}

$selectedDate = $_GET['date'] ?? ($_POST['choice_date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}


if ($isHeadTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dish'])) {
    $pdo->prepare("INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (?, ?, ?, ?)")
        ->execute([(int)$_POST['day_of_week'], $_POST['meal_type'], trim($_POST['dish_name']), $_SESSION['user_id']]);
    $msg = 'Блюдо добавлено в постоянное меню.';
}


if ($isHeadTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dish_id'])) {
    $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([(int)$_POST['delete_dish_id']]);
    $msg = 'Блюдо удалено.';
}


if ($isLoggedIn && $userRole === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_meal_choice'])) {
    $mealType = $_POST['meal_type'] ?? '';
    $willEat = ((int)($_POST['will_eat'] ?? 0) === 1) ? 1 : 0;
    $allowed = ($residenceType === 'boarding') ? ['breakfast', 'lunch', 'dinner'] : ['lunch'];

    if (in_array($mealType, $allowed, true)) {
        $pdo->prepare("
            INSERT INTO meal_choices (user_id, choice_date, meal_type, will_eat)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE will_eat = VALUES(will_eat)
        ")->execute([$_SESSION['user_id'], $selectedDate, $mealType, $willEat]);
        $msg = $willEat ? 'Отмечено: вы будете есть.' : 'Отмечено: вы не будете есть.';
    } else {
        $msg = 'Для вашего типа проживания этот приём пищи недоступен.';
    }
}


if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_dish_id'])) {
    $dishId = (int)$_POST['vote_dish_id'];
    $dish = $pdo->prepare("SELECT meal_type FROM menu_items WHERE id = ?");
    $dish->execute([$dishId]);
    $dishData = $dish->fetch();

    $allowed = ($isHeadTeacher || $residenceType === 'boarding') ? ['breakfast', 'lunch', 'dinner'] : ['lunch'];

    if ($dishData && in_array($dishData['meal_type'], $allowed, true)) {
        try {
            $pdo->prepare("INSERT INTO menu_votes (menu_item_id, user_id, vote_date) VALUES (?, ?, CURDATE())")
                ->execute([$dishId, $_SESSION['user_id']]);
            $msg = 'Ваш голос за блюдо учтён!';
        } catch (PDOException $e) {
            $msg = 'Вы уже голосовали за это блюдо сегодня.';
        }
    }
}

$days = [1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота'];
$meals = [
    'breakfast' => 'Завтрак',
    'lunch'     => 'Обед',
    'dinner'    => 'Ужин'
];


if ($isLoggedIn && $userRole === 'student' && !$isHeadTeacher) {
    $visibleMeals = ($residenceType === 'boarding')
        ? ['breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин']
        : ['lunch' => 'Обед'];
} else {
    $visibleMeals = $meals;
}

$allItems = $pdo->query("
    SELECT m.*, 
           (SELECT COUNT(*) FROM menu_votes v WHERE v.menu_item_id = m.id AND v.vote_date = CURDATE()) AS votes_today,
           (SELECT COUNT(*) FROM menu_votes v WHERE v.menu_item_id = m.id AND v.user_id = " . ($isLoggedIn ? (int)$_SESSION['user_id'] : 0) . " AND v.vote_date = CURDATE()) AS my_vote
    FROM menu_items m
    ORDER BY m.day_of_week, FIELD(m.meal_type, 'breakfast', 'lunch', 'dinner'), m.id
")->fetchAll();

$byDayMeal = [];
foreach ($allItems as $item) {
    $byDayMeal[$item['day_of_week']][$item['meal_type']][] = $item;
}

$myChoicesMap = [];
if ($isLoggedIn && $userRole === 'student') {
    $mcSt = $pdo->prepare("SELECT meal_type, will_eat FROM meal_choices WHERE user_id = ? AND choice_date = ?");
    $mcSt->execute([$_SESSION['user_id'], $selectedDate]);
    foreach ($mcSt->fetchAll() as $row) {
        $myChoicesMap[$row['meal_type']] = (int)$row['will_eat'];
    }
}
?>

<div class="container" style="padding: 36px 0;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:14px; margin-bottom:20px;">
        <div>
            <h1 style="font-size:24px; font-weight:900; margin-bottom:6px;">Постоянное меню столовой</h1>
            <p style="font-size:13px; color:var(--text-muted);">
                <?php if (!$isLoggedIn): ?>
                    Войдите в систему, чтобы отмечать посещение столовой (буду есть / не буду есть).
                <?php elseif ($isHeadTeacher): ?>
                    Режим администрации: доступны все приёмы пищи и <a href="dashboard.php#eatingListSection" style="color:var(--primary);">отсортированный список кушания</a>.
                <?php elseif ($residenceType === 'city'): ?>
                    Статус: <strong>Городской ученик</strong> — отображается меню обедов с отметкой «Буду есть / Не буду есть».
                <?php else: ?>
                    Статус: <strong>Интернатский ученик</strong> — отображается меню завтраков, обедов и ужинов с отметкой на каждый приём пищи.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isLoggedIn && $userRole === 'student'): ?>
            <form method="GET" style="display:flex; align-items:end; gap:8px; background:#fff; padding:10px 14px; border:1.5px solid var(--border-color); border-radius:var(--radius);">
                <div class="form-group" style="margin:0;">
                    <label>Дата отметки питания</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>">
                </div>
                <button type="submit" class="btn-primary">Выбрать дату</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?><div class="alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>


    <?php if ($isLoggedIn && $userRole === 'student'): ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px;">
            <h3>Запись на питание на <?= date('d.m.Y', strtotime($selectedDate)) ?></h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:14px;">
                <?php foreach ($visibleMeals as $mKey => $mTitle): 
                    $currentVal = $myChoicesMap[$mKey] ?? null;
                ?>
                    <div style="background:#f8fafc; border:1.5px solid var(--border-color); border-radius:var(--radius); padding:14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <strong style="font-size:15px;"><?= $mTitle ?></strong>
                            <?php if ($currentVal === 1): ?>
                                <span class="badge-active">Буду есть</span>
                            <?php elseif ($currentVal === 0): ?>
                                <span class="badge-burned">Не буду есть</span>
                            <?php else: ?>
                                <span style="font-size:11px; color:var(--text-muted);">Не выбрано</span>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <form method="POST" style="margin:0; flex:1;">
                                <input type="hidden" name="set_meal_choice" value="1">
                                <input type="hidden" name="choice_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                <input type="hidden" name="meal_type" value="<?= $mKey ?>">
                                <input type="hidden" name="will_eat" value="1">
                                <button type="submit" class="btn-primary" style="width:100%; padding:7px 10px; <?= $currentVal === 1 ? 'background:#117a65; border-color:#117a65; color:#fff;' : '' ?>">Буду есть</button>
                            </form>
                            <form method="POST" style="margin:0; flex:1;">
                                <input type="hidden" name="set_meal_choice" value="1">
                                <input type="hidden" name="choice_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                <input type="hidden" name="meal_type" value="<?= $mKey ?>">
                                <input type="hidden" name="will_eat" value="0">
                                <button type="submit" class="btn-hero" style="width:100%; padding:7px 10px; <?= $currentVal === 0 ? 'background:#a61b1b; border-color:#a61b1b; color:#fff;' : '' ?>">Не буду есть</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>


    <?php if ($isHeadTeacher): ?>
        <div class="auth-box" style="max-width:100%; margin:0 0 24px;">
            <h3>Управление постоянным меню (Завуч / Директор)</h3>
            <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                <input type="hidden" name="add_dish" value="1">
                <div class="form-group" style="margin:0; flex:1; min-width:140px;">
                    <label>День недели</label>
                    <select name="day_of_week" class="form-control" required>
                        <?php foreach ($days as $num => $name): ?><option value="<?= $num ?>"><?= $name ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0; flex:1; min-width:140px;">
                    <label>Приём пищи</label>
                    <select name="meal_type" class="form-control" required>
                        <?php foreach ($meals as $key => $name): ?><option value="<?= $key ?>"><?= $name ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0; flex:3; min-width:200px;">
                    <label>Состав / название блюда</label>
                    <input type="text" name="dish_name" class="form-control" required>
                </div>
                <button type="submit" class="btn-primary">Добавить</button>
            </form>
        </div>
    <?php endif; ?>


    <?php if (empty($allItems)): ?>
        <div class="auth-box" style="max-width:100%; text-align:center; color:var(--text-muted);">Постоянное меню пока не заполнено.</div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($days as $dayNum => $dayName): 
                $hasAnyForDay = false;
                foreach ($visibleMeals as $mk => $mn) {
                    if (!empty($byDayMeal[$dayNum][$mk])) { $hasAnyForDay = true; break; }
                }
                if (!$hasAnyForDay) continue;
            ?>
                <div style="background:#ffffff; border:1.5px solid var(--card-border); border-radius:var(--radius); padding:18px;">
                    <h2 style="font-size:16px; color:var(--primary); margin-bottom:12px; border-bottom:1.5px solid var(--border-color); padding-bottom:6px;"><?= $dayName ?></h2>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
                        <?php foreach ($visibleMeals as $mealKey => $mealName): if (empty($byDayMeal[$dayNum][$mealKey])) continue; ?>
                            <div style="background:#f8fafc; padding:12px; border-radius:var(--radius); border:1px solid var(--border-color);">
                                <h3 style="font-size:14px; font-weight:900; margin-bottom:8px; color:var(--text-main);"><?= $mealName ?></h3>
                                <?php foreach ($byDayMeal[$dayNum][$mealKey] as $dish): ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid var(--border-color); gap:8px;">
                                        <span style="font-size:13px;"><?= htmlspecialchars($dish['dish_name']) ?></span>
                                        <span style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                                            <strong style="font-size:12px; color:var(--primary);"><?= (int)$dish['votes_today'] ?></strong>
                                            <?php if ($isLoggedIn && !$dish['my_vote']): ?>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="vote_dish_id" value="<?= $dish['id'] ?>">
                                                    <button type="submit" class="btn-primary" style="padding:2px 8px; font-size:11px;">+1</button>
                                                </form>
                                            <?php elseif ($dish['my_vote']): ?>
                                                <span style="font-size:11px; color:#117a65; font-weight:900;">✓</span>
                                            <?php endif; ?>
                                            <?php if ($isHeadTeacher): ?>
                                                <form method="POST" style="margin:0;" onsubmit="return rliConfirmForm(this, 'Удаление блюда', 'Удалить блюдо из постоянного меню?', 'Удалить', true);">
                                                    <input type="hidden" name="delete_dish_id" value="<?= $dish['id'] ?>">
                                                    <button type="submit" class="btn-primary" style="padding:2px 7px; font-size:11px; background:#a61b1b; border-color:#a61b1b; color:#fff;">✕</button>
                                                </form>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>