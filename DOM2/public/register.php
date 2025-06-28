<?php require 'config.php'; ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Регистрация</title>
</head>
<body>
<?php
// Если форма отправлена (метод POST), выполняем регистрацию
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и валидируем поля формы
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || $pass === '') {
        $message = 'Пожалуйста, заполните все поля.';
    } else {
        // Проверяем, не существует ли уже пользователь с таким email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = 'Этот email уже зарегистрирован.';
        } else {
            // Хэшируем пароль перед сохранением
            $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
            // Сохраняем нового пользователя (роль по умолчанию 'user')
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $passwordHash]);
            // Берём ID добавленной записи
            $newUserId = $pdo->lastInsertId();
            // Устанавливаем сессию (авторизуем пользователя сразу)
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            // Перенаправляем на главную страницу после успешной регистрации
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!-- Навигационная панель (упрощенная) -->
<nav>
    <a href="index.php">Главная</a> |
    <a href="login.php">Вход</a>
</nav>

<h2>Регистрация</h2>
<?php if ($message): ?>
    <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<form method="post" action="register.php">
    <p>
        <label>Имя:<br>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? '') ?>">
        </label>
    </p>
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
    <button type="submit">Зарегистрироваться</button>
</form>
</body>
</html>
