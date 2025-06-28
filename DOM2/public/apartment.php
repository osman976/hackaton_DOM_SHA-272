<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Информация о квартире</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        header nav { margin-bottom: 20px; }
        #three-container { width: 100%; height: 400px; background: #f0f0f0; margin-bottom: 20px; }
        #infra-map { width: 100%; height: 300px; margin: 10px 0; }
        #bookingForm { display: none; border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
<?php
// Получаем ID квартиры из параметра URL
$aptId = $_GET['id'] ?? null;
$apt = null;
$message = '';

// Если ID передан или если была отправлена форма бронирования
if ($aptId || isset($_POST['apt_id'])) {
    $id = $aptId ? (int)$aptId : (int)$_POST['apt_id'];
    // Получаем данные квартиры и комплекса
    $stmt = $pdo->prepare("SELECT a.*, c.name AS complex_name, c.description AS complex_desc, c.lat, c.lon 
                           FROM apartments a 
                           JOIN complexes c ON a.complex_id = c.id 
                           WHERE a.id = ?");
    $stmt->execute([$id]);
    $apt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$apt) {
        echo "<p>Квартира с ID $id не найдена.</p>";
        exit;
    }
    // Обработка отправки формы бронирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        if ($name && $phone) {
            // Здесь можно было бы сохранить заявку в базу или отправить email
            $message = "Спасибо, $name! Ваша заявка на бронирование отправлена.";
        } else {
            $message = "Пожалуйста, укажите имя и телефон для бронирования.";
        }
    }
} else {
    echo "<p>Не указан ID квартиры.</p>";
    exit;
}
?>

<header>
    <nav>
        <a href="index.php">&#8592; К списку квартир</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            | <a href="logout.php">Выйти</a>
        <?php endif; ?>
    </nav>
</header>

<h1><?php echo htmlspecialchars($apt['name']); ?> – <?php echo htmlspecialchars($apt['complex_name']); ?></h1>
<p><strong>Цена:</strong> <?php echo $apt['price'] ? $apt['price'] . ' $' : 'не указана'; ?></p>
<p><strong>Комнат:</strong> <?php echo $apt['rooms'] ?: 'не указано'; ?>; 
   <strong>Площадь:</strong> <?php echo $apt['area'] ? $apt['area'] . ' м²' : 'не указана'; ?>; 
   <strong>Этаж:</strong> <?php echo $apt['floor'] ?: 'не указан'; ?></p>

<h2>Описание ЖК "<?php echo htmlspecialchars($apt['complex_name']); ?>"</h2>
<p><?php echo nl2br(htmlspecialchars($apt['complex_desc'])); ?></p>

<h2>3D-обзор комплекса</h2>
<div id="three-container"><em>Загрузка 3D...</em></div>

<h2>Инфраструктура рядом</h2>
<p>На карте отмечено местоположение комплекса и объекты инфраструктуры рядом с ним.</p>
<div id="infra-map"></div>

<!-- Форма бронирования/покупки -->
<h2>Бронирование квартиры</h2>
<?php if ($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>
<button id="bookBtn">Забронировать эту квартиру</button>
<div id="bookingForm">
    <h3>Заявка на бронирование</h3>
    <form method="post" action="apartment.php?id=<?php echo $apt['id']; ?>">
        <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
        <p><label>Ваше имя: <br><input type="text" name="name"></label></p>
        <p><label>Телефон: <br><input type="text" name="phone"></label></p>
        <p><label>Email: <br><input type="email" name="email"></label></p>
        <button type="submit" name="reserve" value="1">Отправить заявку</button>
    </form>
</div>

<!-- Подключение библиотек Three.js и Leaflet -->
<script src="https://cdn.jsdelivr.net/npm/three@0.149.0/build/three.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// === Three.js 3D Scene Initialization ===
// Создаем сцену
const scene = new THREE.Scene();
// Камера перспективы (поле зрения 75°, соотношение сторон по размеру контейнера)
const camera = new THREE.PerspectiveCamera(75, window.innerWidth / 400, 0.1, 1000);
camera.position.z = 5;  // отодвигаем камеру назад

// Рендерер и добавление его canvas на страницу
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, 400);
document.getElementById('three-container').appendChild(renderer.domElement);

// Добавляем простой куб как схематичное здание ЖК
const geometry = new THREE.BoxGeometry(1, 1, 1);
const material = new THREE.MeshBasicMaterial({ color: 0x44aa88 });
const cube = new THREE.Mesh(geometry, material);
scene.add(cube);

// Источник света (для базового освещения куба)
const light = new THREE.DirectionalLight(0xffffff, 1);
light.position.set(2, 2, 5);
scene.add(light);

// Анимация вращения куба
function animate() {
    requestAnimationFrame(animate);
    cube.rotation.x += 0.01;
    cube.rotation.y += 0.01;
    renderer.render(scene, camera);
}
animate();
</script>
<script>
// === Leaflet Map for Infrastructure ===
// Инициализируем карту с центром на координатах комплекса
var infraMap = L.map('infra-map').setView([<?php echo $apt['lat']; ?>, <?php echo $apt['lon']; ?>], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(infraMap);

// Маркер позиции комплекса
var complexMarker = L.marker([<?php echo $apt['lat']; ?>, <?php echo $apt['lon']; ?>]).addTo(infraMap);
complexMarker.bindPopup("ЖК <?php echo htmlspecialchars($apt['complex_name']); ?>").openPopup();

// Пример: добавим два маркера инфраструктуры (фиктивные данные)
var schoolMarker = L.marker([<?php echo $apt['lat'] + 0.005; ?>, <?php echo $apt['lon']; ?>]).addTo(infraMap);
schoolMarker.bindPopup("Школа");

var shopMarker = L.marker([<?php echo $apt['lat']; ?>, <?php echo $apt['lon'] + 0.005; ?>]).addTo(infraMap);
shopMarker.bindPopup("Супермаркет");
</script>
<script>
// Показать форму бронирования по нажатию кнопки
document.getElementById('bookBtn').onclick = function() {
    document.getElementById('bookingForm').style.display = 'block';
    this.style.display = 'none';
}
</script>
</body>
</html>
