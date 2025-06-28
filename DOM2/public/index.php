<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Поиск квартир</title>
    <!-- Подключение стилей Leaflet для карты -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-uNj+bQJ+re3ovvfEUBXrqGiLvih3XgUq3aroZQ0pBzo=" crossorigin=""/>
    <style>
        /* Небольшие стили для оформления */
        body { font-family: Arial, sans-serif; margin: 20px; }
        header, nav, footer { margin-bottom: 20px; }
        .apartment-card { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .apartment-card h3 { margin: 0 0 5px; }
        .apartment-card p { margin: 5px 0; }
        #map { height: 400px; margin-top: 20px; }
    </style>
</head>
<body>
<?php
// Получаем список комплексов для заполнения фильтра (выпадающий список)
$complexList = $pdo->query("SELECT id, name FROM complexes")->fetchAll(PDO::FETCH_ASSOC);

// Инициализация фильтров по умолчанию (из GET-параметров, если есть)
$priceMin = isset($_GET['price_min']) ? (int)$_GET['price_min'] : '';
$priceMax = isset($_GET['price_max']) ? (int)$_GET['price_max'] : '';
$rooms = isset($_GET['rooms']) ? (int)$_GET['rooms'] : '';
$filterComplex = isset($_GET['complex_id']) ? (int)$_GET['complex_id'] : '';

// Формируем SQL-запрос для выборки квартир с учетом фильтров
$sql = "SELECT a.*, c.name AS complex_name, c.lat AS clat, c.lon AS clon 
        FROM apartments a 
        JOIN complexes c ON a.complex_id = c.id";
$conditions = [];
$params = [];

// Добавляем условия фильтра на основе заполненных полей
if ($priceMin !== '' && $priceMin >= 0) {
    $conditions[] = "a.price >= ?";
    $params[] = $priceMin;
}
if ($priceMax !== '' && $priceMax >= 0) {
    $conditions[] = "a.price <= ?";
    $params[] = $priceMax;
}
if ($rooms !== '' && $rooms > 0) {
    $conditions[] = "a.rooms = ?";
    $params[] = $rooms;
}
if ($filterComplex !== '' && $filterComplex > 0) {
    $conditions[] = "a.complex_id = ?";
    $params[] = $filterComplex;
}
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
// Выполняем запрос
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<header>
    <!-- Навигационное меню (верхняя часть страницы) -->
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            Здравствуйте, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! 
            <?php if ($_SESSION['user_role'] === 'builder'): ?>
                <a href="builder.php">[Кабинет застройщика]</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a> | <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
    <h1>Каталог квартир</h1>
</header>

<!-- Блок фильтрации квартир -->
<section id="filters">
    <h2>Фильтр поиска</h2>
    <form method="get" action="index.php">
        <label>Цена от: 
            <input type="number" name="price_min" value="<?php echo htmlspecialchars($priceMin); ?>" />
        </label>
        <label>до: 
            <input type="number" name="price_max" value="<?php echo htmlspecialchars($priceMax); ?>" />
        </label>
        <label>Комнат: 
            <select name="rooms">
                <option value="">любое</option>
                <option value="1" <?php if ($rooms===1) echo 'selected'; ?>>1</option>
                <option value="2" <?php if ($rooms===2) echo 'selected'; ?>>2</option>
                <option value="3" <?php if ($rooms===3) echo 'selected'; ?>>3</option>
                <option value="4" <?php if ($rooms===4) echo 'selected'; ?>>4</option>
                <option value="5" <?php if ($rooms===5) echo 'selected'; ?>>5</option>
            </select>
        </label>
        <label>Жилой комплекс: 
            <select name="complex_id">
                <option value="">все</option>
                <?php foreach ($complexList as $comp): ?>
                    <option value="<?php echo $comp['id']; ?>" 
                        <?php if ($filterComplex !== '' && $filterComplex == $comp['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($comp['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Применить фильтр</button>
        <a href="index.php">Сбросить</a>
    </form>
</section>

<!-- Список квартир по результатам фильтра -->
<section id="apartments">
    <h2>Найденные квартиры</h2>
    <?php if ($apartments): ?>
        <?php foreach ($apartments as $apt): ?>
            <div class="apartment-card">
                <h3><?php echo htmlspecialchars($apt['name']); ?> (<?php echo htmlspecialchars($apt['complex_name']); ?>)</h3>
                <p>Цена: <?php echo $apt['price'] ? $apt['price'] . ' $' : 'не указана'; ?>, 
                   Комнат: <?php echo $apt['rooms'] ?: 'не указано'; ?>,
                   Площадь: <?php echo $apt['area'] ? $apt['area'] . ' м²' : 'не указана'; ?>,
                   Этаж: <?php echo $apt['floor'] ?: 'не указан'; ?></p>
                <p><a href="apartment.php?id=<?php echo $apt['id']; ?>">Подробнее &#10148;</a></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Квартир по заданным критериям не найдено.</p>
    <?php endif; ?>
</section>

<!-- Карта с расположением квартир -->
<section id="map-section">
    <h2>Карта</h2>
    <div id="map"></div>
</section>

<!-- Подключение скрипта Leaflet и отображение маркеров на карте -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-VGH3myOf5ZzQNJ3tvKm0V3r5Lmj7JIOcR50e+pLePQo=" crossorigin=""></script>
<script>
    // Инициализация карты: задаем центр и масштаб по умолчанию
    <?php 
      // Центрируем карту либо на все маркеры, либо на фиксированные координаты
      if ($apartments && count($apartments) > 0) {
          // Берем координаты первой квартиры для центра карты
          $latCenter = $apartments[0]['clat'];
          $lonCenter = $apartments[0]['clon'];
      } else {
          // Если квартир нет, центр по умолчанию (например, координаты первого комплекса или города)
          $latCenter = 56.95;
          $lonCenter = 24.1;
      }
    ?>
    var map = L.map('map').setView([<?php echo $latCenter; ?>, <?php echo $lonCenter; ?>], 12);
    // Добавляем слой карты (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Массив объектов квартир для вывода маркеров (передаем данные из PHP в JS)
    var apartmentsJS = <?php echo json_encode($apartments); ?>;
    apartmentsJS.forEach(function(apt) {
        if (apt.clat && apt.clon) {
            var marker = L.marker([apt.clat, apt.clon]).addTo(map);
            // Настраиваем всплывающую подсказку при клике на маркер
            var popupText = apt.name + " (" + apt.complex_name + ")<br>" +
                            (apt.price ? apt.price + " $" : "Цена не указана");
            marker.bindPopup(popupText);
        }
    });
</script>

<footer>
    <p>&copy; 2025 – Сервис по поиску квартир</p>
</footer>
</body>
</html>
