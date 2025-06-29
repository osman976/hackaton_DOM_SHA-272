<?php
require 'config.php';

// Если форма отправлена (метод POST), выполняем регистрацию
$message = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || $pass === '') {
        $message = 'Пожалуйста, заполните все поля.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = 'Этот email уже зарегистрирован.';
        } else {
            $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $passwordHash]);
            $newUserId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            color: #23263b;
        }
        header {
            background: #fff;
            border-bottom: 1px solid #e3e8ee;
            box-shadow: 0 2px 10px #eef4ff20;
        }
        nav {
            max-width: 420px;
            margin: 0 auto;
            padding: 20px 0 0 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        nav a {
            color: #3172fa;
            text-decoration: none;
            font-weight: 500;
            margin-left: 24px;
            font-size: 1.02rem;
        }
        .register-card {
            max-width: 420px;
            margin: 42px auto 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 32px #d3e2fa25;
            padding: 32px 32px 22px 32px;
        }
        h2 {
            margin: 0 0 18px 0;
            text-align: center;
            color: #3172fa;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 22px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 7px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 11px;
            border-radius: 9px;
            border: 1px solid #d5e1f6;
            font-size: 1.04rem;
            background: #f7fafd;
            transition: border .18s;
            margin-bottom: 3px;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border: 1.5px solid #3172fa;
            background: #f2faff;
        }
        .error-msg {
            color: #ff4747;
            text-align: center;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .register-btn {
            background: linear-gradient(97deg, #459aff 0%, #82d2ff 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px 0;
            width: 100%;
            font-size: 1.07rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 5px;
            box-shadow: 0 2px 8px #7cc0ff18;
            transition: background .18s;
        }
        .register-btn:hover {
            background: linear-gradient(97deg, #3172fa 0%, #59cdfa 100%);
        }
        @media (max-width: 540px) {
            .register-card, nav { padding-left: 10px; padding-right: 10px; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <a href="index.php">Главная</a>
        <a href="login.php">Вход</a>
    </nav>
</header>
<div class="register-card">
    <h2>Регистрация</h2>
    <?php if ($message): ?>
        <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post" action="register.php" autocomplete="off">
        <div class="form-group">
            <label for="name">Имя</label>
            <input type="text" id="name" name="name" maxlength="80" value="<?php echo htmlspecialchars($name ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Логин (email)</label>
            <input type="email" id="email" name="email" maxlength="80" value="<?php echo htmlspecialchars($email ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" maxlength="64" required>
        </div>
        <button type="submit" class="register-btn">Зарегистрироваться</button>
    </form>
</div>
</body>
</html>
