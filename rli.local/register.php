<?php
$pageTitle = 'Регистрация | РЛИ';
include 'includes/header.php';

$error = '';
$success = '';

$profileLabels = [
    'fizmat'       => 'Физико-математический',
    'engineering'  => 'Инженерный',
    'polytech'     => 'Политехнический',
    'rosneft'      => 'Технический (Роснефть)',
    'biohim'       => 'Биолого-химический',
    'humanitarian' => 'Гуманитарный'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['full_name'] ?? '');
    $login         = trim($_POST['login'] ?? '');
    $password      = $_POST['password'] ?? '';
    $password2     = $_POST['password2'] ?? '';
    $profile       = trim($_POST['profile'] ?? '');
    $schoolClass   = trim($_POST['school_class'] ?? '');
    $residenceType = trim($_POST['residence_type'] ?? '');

    if ($fullName === '' || $login === '' || $password === '') {
        $error = 'Заполните все обязательные поля.';
    } elseif ($password !== $password2) {
        $error = 'Пароли не совпадают.';
    } elseif (mb_strlen($password) < 6) {
        $error = 'Пароль должен быть не короче 6 символов.';
    } elseif (!isset($profileLabels[$profile])) {
        $error = 'Выберите корректный профиль.';
    } elseif (!in_array($schoolClass, ['10', '11'], true)) {
        $error = 'Выберите параллель (10 или 11 класс).';
    } elseif (!in_array($residenceType, ['city', 'boarding'], true)) {
        $error = 'Выберите тип проживания.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $error = 'Такой логин уже занят.';
        } else {
            $pdo->prepare("INSERT INTO users (full_name, login, password, role, profile, school_class, residence_type, branch, position_title, position_code) VALUES (?, ?, ?, 'student', ?, ?, ?, 'none', '', 'student')")
                ->execute([$fullName, $login, password_hash($password, PASSWORD_DEFAULT), $profile, $schoolClass, $residenceType]);
            $success = 'Регистрация прошла успешно! Теперь войдите в систему.';
        }
    }
}
?>

<div class="container">
    <div class="auth-box" style="max-width:520px;">
        <h2>Регистрация ученика</h2>
        <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="login.php" class="btn-primary" style="width:100%; text-align:center;">Войти</a>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label>ФИО (полностью)</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Пароль (мин. 6 символов)</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Повторите пароль</label>
                <input type="password" name="password2" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Профиль обучения</label>
                <select name="profile" class="form-control" required>
                    <option value="">— Выберите профиль —</option>
                    <?php foreach ($profileLabels as $key => $label): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Параллель</label>
                <select name="school_class" class="form-control" required>
                    <option value="">— Выберите класс —</option>
                    <option value="10">10 класс</option>
                    <option value="11">11 класс</option>
                </select>
            </div>
            <div class="form-group">
                <label>Тип проживания</label>
                <select name="residence_type" class="form-control" required>
                    <option value="">— Выберите тип проживания —</option>
                    <option value="city">Городской (голосует только за обед)</option>
                    <option value="boarding">Интернатский (голосует за завтрак, обед и ужин)</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; margin-top:6px;">Зарегистрироваться</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
