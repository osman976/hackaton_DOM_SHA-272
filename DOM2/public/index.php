<?php
require 'config.php';

$complexList = $pdo->query("SELECT id, name FROM complexes")->fetchAll(PDO::FETCH_ASSOC);

$priceMin = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$priceMax = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$rooms = isset($_GET['rooms']) ? (int)$_GET['rooms'] : '';
$filterComplex = isset($_GET['complex_id']) ? (int)$_GET['complex_id'] : '';

$sql = "SELECT a.*, c.name AS complex_name FROM apartments a JOIN complexes c ON a.complex_id = c.id";
$conditions = [];
$params = [];

if ($priceMin !== '' && is_numeric($priceMin)) {
    $conditions[] = "a.price >= ?";
    $params[] = (int)$priceMin;
}
if ($priceMax !== '' && is_numeric($priceMax)) {
    $conditions[] = "a.price <= ?";
    $params[] = (int)$priceMax;
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
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>DOMinate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ТВОЙ_API_КЛЮЧ" type="text/javascript"></script>
    <style>
        :root {
            --accent: #377dff;
            --accent-dark: #2353a6;
            --bg: #f3f6fb;
            --glass: rgba(255,255,255,0.82);
            --border: #c3d1ed;
            --shadow: 0 8px 32px 0 rgba(60,90,170,0.11), 0 1.5px 6px #377dff33;
        }
        body, html {
            margin: 0; padding: 0;
            background: var(--bg);
            font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
        }
        header {
            background: linear-gradient(90deg, #edf3ff 0%, #e3eaff 100%);
            padding: 36px 0 20px 0; text-align: center; box-shadow: 0 2px 24px #dae9fc30;
            border-radius: 0 0 36px 36px;
        }
        header h1 {
            font-size: 2.4rem;
            font-weight: 800;
            margin: 0 0 7px 0;
            letter-spacing: -1px;
            color: var(--accent-dark);
            text-shadow: 0 2px 8px #bddcff24;
        }
        nav {
            margin-bottom: 6px; font-size: 1.05rem;
        }
        nav a {
            color: var(--accent-dark);
            text-decoration: none;
            margin: 0 12px; font-weight: 600;
            transition: color .17s;
            padding: 2px 0;
            border-bottom: 2px solid transparent;
        }
        nav a:hover {
            color: var(--accent); border-bottom: 2px solid var(--accent);
        }
        #filters {
            margin: 36px auto 28px auto;
            max-width: 720px;
            background: var(--glass);
            padding: 26px 36px 16px 36px;
            border-radius: 22px;
            box-shadow: var(--shadow);
            border: 1.5px solid var(--border);
        }
        #filters h2 {
            margin-top: 0;
            font-size: 1.2rem;
            color: var(--accent-dark);
            letter-spacing: 0.01em;
            font-weight: 600;
        }
        #filters label {
            margin-right: 22px;
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        #filters input, #filters select {
            font-size: 1rem; padding: 7px 12px;
            border: 1.5px solid var(--border);
            border-radius: 9px; margin-left: 7px;
            margin-bottom: 9px;
            background: #f5f8fd;
            outline: none; transition: border-color .17s;
            box-shadow: 0 1.5px 8px #c8d6f522;
        }
        #filters input:focus, #filters select:focus { border-color: var(--accent); }
        #filters button, #filters a {
            margin-left: 8px; padding: 8px 28px; border: none; border-radius: 9px;
            background: linear-gradient(90deg, var(--accent), #7dd3fc);
            color: #fff; font-weight: 700; cursor: pointer;
            font-size: 1.08rem; box-shadow: 0 2px 12px #73affc26;
            text-decoration: none; transition: background .21s, color .2s, transform .17s;
            letter-spacing: .01em;
        }
        #filters button:hover, #filters a:hover { background: var(--accent-dark); color: #fff; transform: translateY(-1.5px) scale(1.03);}
        #apartments {
            max-width: 850px; margin: 0 auto 32px auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 28px;
        }
        #apartments h2 {
            grid-column: 1/-1;
            margin: 0 0 8px 0;
            font-size: 1.15rem; color: var(--accent-dark);
            font-weight: 600;
        }
        .apartment-card {
            background: var(--glass);
            border-radius: 21px;
            box-shadow: var(--shadow);
            border: 1.5px solid var(--border);
            padding: 28px 22px 20px 22px;
            display: flex; flex-direction: column; gap: 10px;
            transition: box-shadow .19s, transform .13s;
            min-height: 155px;
            position: relative;
        }
        .apartment-card:hover {
            box-shadow: 0 6px 36px #90c3fc44, 0 1.5px 6px #377dff33;
            border-color: var(--accent);
            transform: translateY(-2.5px) scale(1.015);
        }
        .apartment-card h3 {
            margin: 0 0 3px 0; font-size: 1.18rem; font-weight: 700; color: var(--accent);
        }
        .apartment-card p {
            margin: 6px 0; font-size: 1.03rem; color: #222b34;
            line-height: 1.55;
        }
        .apartment-card a {
            align-self: flex-end;
            margin-top: 10px;
            color: #fff; background: var(--accent-dark);
            padding: 8px 22px; border-radius: 8px; text-decoration: none;
            font-weight: 700; letter-spacing: .03em;
            box-shadow: 0 2px 8px #377dff18;
            transition: background .19s, transform .15s;
        }
        .apartment-card a:hover { background: var(--accent); transform: scale(1.04);}
        #map-section {
            max-width: 950px; margin: 0 auto 36px auto; border-radius: 30px;
            background: var(--glass); box-shadow: var(--shadow); padding: 18px;
            border: 1.5px solid var(--border);
        }
        #map-section h2 { font-size: 1.13rem; color: var(--accent-dark); font-weight: 600; margin: 0 0 8px 0;}
        #map { height: 440px; width: 100%; border-radius: 18px; border: 1.5px solid var(--border);}
        footer {
            text-align: center; font-size: 1rem; color: #a3abbc; padding: 26px 0 38px 0;
            letter-spacing: .02em;
        }
        @media (max-width: 1050px) {
            #apartments { grid-template-columns: 1fr; }
            #filters, #apartments, #map-section { max-width: 98vw; }
        }
        @media (max-width: 700px) {
            #filters, #apartments, #map-section { padding: 8px; }
            .apartment-card { padding: 17px 8px 13px 10px; }
            #map { height: 260px; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            Здравствуйте, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
            <?php if ($_SESSION['user_role'] === 'builder'): ?>
                <a href="builder.php">Кабинет застройщика</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a> | <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
    <h1>DOMinate</h1>
</header>

<section id="filters">
    <h2>Фильтр поиска</h2>
    <form method="get" action="index.php">
        <label>Цена от:
            <input type="number" name="price_min" value="<?php echo htmlspecialchars($priceMin); ?>" min="0" />
        </label>
        <label>до:
            <input type="number" name="price_max" value="<?php echo htmlspecialchars($priceMax); ?>" min="0" />
        </label> <br>
        <label>Комнат:
            <select name="rooms">
                <option value="">любое</option>
                <option value="1" <?php if ($rooms === 1) echo 'selected'; ?>>1</option>
                <option value="2" <?php if ($rooms === 2) echo 'selected'; ?>>2</option>
                <option value="3" <?php if ($rooms === 3) echo 'selected'; ?>>3</option>
                <option value="4" <?php if ($rooms === 4) echo 'selected'; ?>>4</option>
                <option value="5" <?php if ($rooms === 5) echo 'selected'; ?>>5</option>
            </select>
        </label> <br>
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
        </label> <br>
        <button type="submit">Применить</button>
        <a href="index.php">Сбросить</a>
    </form>
</section>

<section id="apartments">
    <h2>Найденные квартиры</h2>
    <?php if ($apartments): ?>
        <?php foreach ($apartments as $apt): ?>
            <div class="apartment-card">
                <h3><?php echo htmlspecialchars($apt['name']); ?> (<?php echo htmlspecialchars($apt['complex_name']); ?>)</h3>
                <p><b>Адрес:</b> <?php echo htmlspecialchars($apt['address']); ?></p>
                <p>Цена: <?php echo $apt['price'] ? number_format($apt['price'], 0, '', ' ') . ' ₽' : 'не указана'; ?>,
                   Комнат: <?php echo $apt['rooms'] ?: 'не указано'; ?>,
                   Площадь: <?php echo $apt['area'] ? $apt['area'] . ' м²' : 'не указана'; ?>,
                   Этаж: <?php echo $apt['floor'] ?: 'не указан'; ?></p>
                <a href="apartment.php?id=<?php echo $apt['id']; ?>">Подробнее &#10148;</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#b1b1b7;">Квартир по заданным критериям не найдено.</p>
    <?php endif; ?>
</section>
<section id="map-section">
    <h2>Карта</h2>
    <div id="map"></div>
</section>

<script>
var apartmentsJS = <?php echo json_encode($apartments); ?>;
ymaps.ready(function () {
    var myMap = new ymaps.Map("map", {
        center: [55.751244, 37.618423],
        zoom: 10
    });
    apartmentsJS.forEach(function (apt, idx) {
        if (apt.address && apt.address.trim().length > 0) {
            ymaps.geocode(apt.address, { results: 1 }).then(function (res) {
                var obj = res.geoObjects.get(0);
                if (obj) {
                    var coords = obj.geometry.getCoordinates();
                    var placemark = new ymaps.Placemark(coords, {
                        balloonContentHeader: apt.name + ' (' + apt.complex_name + ')',
                        balloonContentBody: '<b>' + apt.address + '</b><br>' +
                            (apt.price ? apt.price + " ₽" : "Цена не указана")
                    });
                    myMap.geoObjects.add(placemark);
                    if (idx === 0) myMap.setCenter(coords, 14);
                }
            });
        }
    });
});
</script>
<footer>
    <p>&copy; 2025 – Сервис по поиску квартир</p>
</footer>
</body>
</html>
