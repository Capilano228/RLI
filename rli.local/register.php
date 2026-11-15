<?php
$pageTitle = 'Регистрация | РЛИ';
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'student';
    $profile  = ($role === 'student') ? ($_POST['profile'] ?? '') : 'none';

    $allowedProfiles = ['fizmat', 'engineering', 'polytech', 'rosneft', 'biohim', 'humanitarian'];

    if (!$fullName || !$login || !$password) {
        $error = 'Ты не закончил';
    } elseif ($role === 'student' && !in_array($profile, $allowedProfiles)) {
        $error = 'Профиль';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $error = 'Такой уже есть';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (full_name, login, password, role, profile) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$fullName, $login, $hash, $role, $profile]);
            $success = 'Ты зарегался';
        }
    }
}
?>

<div class="container">
    <div class="auth-box">
        <h2>Регистрация нового пользователя</h2>

        <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-success"><?= htmlspecialchars($success) ?> <a href="login.php" style="color:#166534; font-weight:600;">Войти</a></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Тип аккаунта</label>
                <select name="role" id="roleSelect" class="form-control" onchange="toggleProfile()">
                    <option value="student">Ученик лицея</option>
                    <option value="teacher">Преподаватель / Сотрудник</option>
                </select>
            </div>

            <div class="form-group">
                <label>ФИО полностью</label>
                <input type="text" name="full_name" class="form-control" placeholder="Иванов Алексей Петрович" required>
            </div>

            <div class="form-group" id="profileGroup">
                <label>Профильный класс</label>
                <select name="profile" class="form-control">
                    <option value="">-- Выберите профиль --</option>
                    <option value="fizmat">Физико-математический</option>
                    <option value="engineering">Инженерный</option>
                    <option value="polytech">Политехнический (PTCH)</option>
                    <option value="rosneft">Технический (Роснефть)</option>
                    <option value="biohim">Биолого-химический</option>
                    <option value="humanitarian">Гуманитарный (Гумнар)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; margin-top:6px;">Зарегистрироваться</button>
        </form>
    </div>
</div>

<script>
function toggleProfile() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('profileGroup').style.display = (role === 'student') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>