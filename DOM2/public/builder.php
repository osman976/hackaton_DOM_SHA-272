<?php 
require 'config.php'; 

// Проверяем, авторизован ли пользователь как застройщик
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'builder') {
    // Если нет доступа, перенаправляем на страницу входа
    header("Location: login.php");
    exit;
}

// Инициализируем сообщения об успехе/ошибках
$msgComplex = '';
$msgApartment = '';

// Обработка добавления жилого комплекса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_complex'])) {
    $compName = trim($_POST['comp_name'] ?? '');
    $compDesc = trim($_POST['comp_desc'] ?? '');
    $compLat = trim($_POST['comp_lat'] ?? '');
    $compLon = trim($_POST['comp_lon'] ?? '');
    if ($compName === '' || $compLat === '' || $compLon === '') {
        $msgComplex = 'Пожалуйста, заполните поля Название, Широта и Долгота.';
    } else {
        // Вставляем новый комплекс в БД
        $stmt = $pdo->prepare("INSERT INTO complexes (name, description, lat, lon) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$compName, $compDesc, $compLat, $compLon]);
            $msgComplex = 'Жилой комплекс успешно добавлен.';
        } catch (PDOException $e) {
            $msgComplex = 'Ошибка сохранения комплекса: ' . $e->getMessage();
        }
    }
}

// Обработка добавления квартиры
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_apartment'])) {
    $aptName = trim($_POST['apt_name'] ?? '');
    $aptDesc = trim($_POST['apt_desc'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $rooms = trim($_POST['rooms'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $complexId = $_POST['complex_id'] ?? '';
    if ($aptName === '' || $complexId === '') {
        $msgApartment = 'Введите как минимум название квартиры и выберите ЖК.';
    } else {
        // Берем координаты комплекса, чтобы установить для квартиры такие же (если требуется, можно вводить вручную)
        $stmtComp = $pdo->prepare("SELECT lat, lon FROM complexes WHERE id = ?");
        $stmtComp->execute([$complexId]);
        $comp = $stmtComp->fetch(PDO::FETCH_ASSOC);
        $lat = $comp['lat'] ?? null;
        $lon = $comp['lon'] ?? null;
        // Вставляем квартиру
        $stmt = $pdo->prepare("INSERT INTO apartments (complex_id, name, description, price, rooms, area, floor, lat, lon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$complexId, $aptName, $aptDesc, $price ?: null, $rooms ?: null, $area ?: null, $floor ?: null, $lat, $lon]);
            $msgApartment = 'Квартира успешно добавлена.';
        } catch (PDOException $e) {
            $msgApartment = 'Ошибка добавления квартиры: ' . $e->getMessage();
        }
    }
}

// Получаем обновленные списки комплексов и квартир для отображения
$complexes = $pdo->query("SELECT * FROM complexes")->fetchAll(PDO::FETCH_ASSOC);
$apartments = $pdo->query(
    "SELECT a.*, c.name as complex_name 
     FROM apartments a 
     JOIN complexes c ON a.complex_id = c.id
     ORDER BY a.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Кабинет застройщика</title>
</head>
<body>
<!-- Навигация: имя застройщика и ссылка для выхода -->
<nav>
    Вы вошли как <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?> (застройщик)</strong> | 
    <a href="index.php">На сайт</a> | 
    <a href="logout.php">Выйти</a>
</nav>

<h1>Кабинет застройщика</h1>

<!-- Список существующих комплексов -->
<h2>Мои жилые комплексы</h2>
<?php if ($complexes): ?>
    <ul>
    <?php foreach ($complexes as $comp): ?>
        <li>
            <strong><?php echo htmlspecialchars($comp['name']); ?></strong>
            (ID: <?php echo $comp['id']; ?>) –
            Координаты: [<?php echo $comp['lat'] . ', ' . $comp['lon']; ?>]
        </li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Вы еще не добавили ни одного ЖК.</p>
<?php endif; ?>

<!-- Форма добавления нового ЖК -->
<h3>Добавить новый жилой комплекс</h3>
<?php if ($msgComplex): ?>
    <p style="color:blue;"><?php echo htmlspecialchars($msgComplex); ?></p>
<?php endif; ?>
<form method="post" action="builder.php">
    <input type="hidden" name="add_complex" value="1">
    <p><label>Название ЖК:<br>
        <input type="text" name="comp_name"></label></p>
    <p><label>Описание ЖК:<br>
        <textarea name="comp_desc" cols="30" rows="3"></textarea></label></p>
    <p><label>Широта (lat):<br>
        <input type="text" name="comp_lat"></label></p>
    <p><label>Долгота (lon):<br>
        <input type="text" name="comp_lon"></label></p>
    <button type="submit">Добавить комплекс</button>
</form>

<hr>

<!-- Список квартир -->
<h2>Мои квартиры</h2>
<?php if ($apartments): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>ID</th><th>Название квартиры</th><th>ЖК</th><th>Цена</th><th>Комнат</th><th>Площадь</th><th>Этаж</th></tr>
        <?php foreach ($apartments as $apt): ?>
        <tr>
            <td><?php echo $apt['id']; ?></td>
            <td><?php echo htmlspecialchars($apt['name']); ?></td>
            <td><?php echo htmlspecialchars($apt['complex_name']); ?></td>
            <td><?php echo $apt['price'] ? $apt['price'] . ' $' : ''; ?></td>
            <td><?php echo $apt['rooms'] ?: ''; ?></td>
            <td><?php echo $apt['area'] ? $apt['area'] . ' м<sup>2</sup>' : ''; ?></td>
            <td><?php echo $apt['floor'] ?: ''; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Пока нет ни одной квартиры.</p>
<?php endif; ?>

<!-- Форма добавления новой квартиры -->
<h3>Добавить новую квартиру</h3>
<?php if ($msgApartment): ?>
    <p style="color:blue;"><?php echo htmlspecialchars($msgApartment); ?></p>
<?php endif; ?>
<form method="post" action="builder.php">
    <input type="hidden" name="add_apartment" value="1">
    <p><label>Название/№ квартиры:<br>
        <input type="text" name="apt_name"></label></p>
    <p><label>Жилой комплекс:<br>
        <select name="complex_id">
            <option value="">-- выберите ЖК --</option>
            <?php foreach ($complexes as $comp): ?>
            <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
            <?php endforeach; ?>
        </select></label></p>
    <p><label>Описание квартиры:<br>
        <textarea name="apt_desc" cols="30" rows="2"></textarea></label></p>
    <p>
        <label>Цена ($):<br><input type="number" name="price"></label><br>
        <label>Комнат:<br><input type="number" name="rooms"></label><br>
        <label>Площадь (кв.м):<br><input type="number" name="area" step="0.1"></label><br>
        <label>Этаж:<br><input type="number" name="floor"></label>
    </p>
    <button type="submit">Добавить квартиру</button>
</form>
</body>
</html>
