<?php
$pageTitle = 'Вход в систему | РЛИ';
include 'includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login']     = $user['login']; 
        $_SESSION['role']      = $user['role'];
        $_SESSION['profile']   = $user['profile'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}
?>

<div class="container">
    <div class="auth-box">
        <h2>Авторизация</h2>
        <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; margin-top:6px;">Войти</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>