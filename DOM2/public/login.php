<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Вход на сайт</title>
</head>
<body>
<?php
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($email === '' || $pass === '') {
        $message = 'Введите email и пароль.';
    } else {
        // Ищем пользователя с таким email
        $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($pass, $user['password'])) {
            // Успешный вход: сохраняем данные в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            // Перенаправляем: если застройщик, можно сразу в его кабинет, иначе на главную
            if ($user['role'] === 'builder') {
                header("Location: builder.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $message = 'Неверный email или пароль.';
        }
    }
}
?>

<nav>
    <a href="index.php">Главная</a> |
    <a href="register.php">Регистрация</a>
</nav>

<h2>Вход</h2>
<?php if ($message): ?>
    <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<form method="post" action="login.php">
    <p>
        <label>Email:<br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? '') ?>">
        </label>
    </p>
    <p>
        <label>Пароль:<br>
            <input type="password" name="password">
        </label>
    </p>
    <button type="submit">Войти</button>
</form>
</body>
</html>
