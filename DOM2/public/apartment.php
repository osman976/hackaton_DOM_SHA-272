<?php
require 'config.php';

$aptId = $_GET['id'] ?? null;
$apt = null;
$message = '';

if ($aptId || isset($_POST['apt_id'])) {
    $id = $aptId ? (int)$aptId : (int)$_POST['apt_id'];
    $stmt = $pdo->prepare("SELECT a.*, c.name AS complex_name, c.description AS complex_desc
                           FROM apartments a 
                           JOIN complexes c ON a.complex_id = c.id 
                           WHERE a.id = ?");
    $stmt->execute([$id]);
    $apt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$apt) {
        echo "<p>Квартира с ID $id не найдена.</p>";
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        if ($name && $phone) {
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
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Информация о квартире</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=d6c63484-44a7-4d7c-9af8-7e7fe64e27f3" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.149.0/build/three.min.js"></script>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            background: #f4f7fb;
            color: #23263b;
        }
        header {
            background: #fff;
            border-bottom: 1px solid #e3e8ee;
            margin-bottom: 32px;
            padding: 16px 0;
            box-shadow: 0 2px 10px #eef4ff20;
        }
        nav {
            max-width: 680px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        nav a {
            color: #3172fa;
            text-decoration: none;
            margin-left: 18px;
            font-weight: 500;
        }
        h1 {
            font-size: 2rem;
            margin: 0 0 12px 0;
            text-align: center;
        }
        .main-wrap {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 32px #d3e2fa25;
            padding: 32px 24px 32px 24px;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-block p {
            margin: 5px 0 0 0;
        }
        .section-title {
            font-size: 1.2rem;
            margin: 32px 0 10px 0;
            color: #3776e0;
            font-weight: 600;
        }
        #three-container {
            max-width: 420px;
            width: 100%;
            height: 270px;
            margin: 0 auto 28px auto;
            border-radius: 16px;
            background: linear-gradient(105deg, #e2ecff 40%, #f3fafd 100%);
            box-shadow: 0 3px 18px #7cbaff17;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        #infra-map {
            max-width: 420px;
            width: 100%;
            height: 240px;
            margin: 12px auto 30px auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 18px #8ab1d817;
        }
        #bookingForm {
            max-width: 340px;
            margin: 16px auto;
            padding: 18px 20px;
            background: #f8faff;
            border-radius: 18px;
            box-shadow: 0 2px 8px #b0d0fa17;
            border: 1px solid #d2e3fa;
            display: none;
        }
        #bookingForm label { font-weight: 500; }
        #bookingForm input {
            width: 100%;
            margin-top: 3px;
            margin-bottom: 13px;
            border: 1px solid #dde6f3;
            border-radius: 7px;
            padding: 7px;
            font-size: 1rem;
        }
        button, .main-btn {
            background: linear-gradient(97deg, #459aff 0%, #82d2ff 100%);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 11px 23px;
            font-size: 1.04rem;
            font-weight: 600;
            cursor: pointer;
            margin: 10px auto;
            transition: background 0.2s;
            display: block;
            box-shadow: 0 2px 6px #7cc0ff28;
        }
        button:hover, .main-btn:hover {
            background: linear-gradient(97deg, #3776e0 0%, #5cd4fe 100%);
        }
        .success-msg { color: #36ab6a; font-weight: 600; text-align: center; }
        .error-msg { color: #ff4747; font-weight: 500; text-align: center; }
        @media (max-width: 760px) {
            .main-wrap, header nav { padding: 0 8px; }
            #three-container, #infra-map { max-width: 100%; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <div>
            <a href="index.php">&#8592; К списку квартир</a>
        </div>
        <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="color:#23263b;">|</span>
            <a href="logout.php">Выйти</a>
        <?php endif; ?>
        </div>
    </nav>
</header>
<div class="main-wrap">
    <h1><?php echo htmlspecialchars($apt['name']); ?> <span style="font-weight:400;color:#adc7e6;">– <?php echo htmlspecialchars($apt['complex_name']); ?></span></h1>
    <div class="info-block">
        <p><b>Адрес:</b> <?php echo htmlspecialchars($apt['address']); ?></p>
        <p><strong>Цена:</strong> <?php echo $apt['price'] ? $apt['price'] . ' $' : 'не указана'; ?></p>
        <p>
            <strong>Комнат:</strong> <?php echo $apt['rooms'] ?: 'не указано'; ?>; 
            <strong>Площадь:</strong> <?php echo $apt['area'] ? $apt['area'] . ' м²' : 'не указана'; ?>; 
            <strong>Этаж:</strong> <?php echo $apt['floor'] ?: 'не указан'; ?>
        </p>
    </div>
    <div class="section-title">Описание ЖК "<?php echo htmlspecialchars($apt['complex_name']); ?>"</div>
    <p><?php echo nl2br(htmlspecialchars($apt['complex_desc'])); ?></p>

    <div class="section-title">3D-обзор комплекса</div>
    <div id="three-container"><em>Загрузка 3D...</em></div>

    <div class="section-title">Инфраструктура рядом</div>
    <p style="margin-bottom:8px;">На карте отмечено расположение комплекса и объектов рядом с ним.</p>
    <div id="infra-map"></div>

    <div class="section-title">Бронирование квартиры</div>
    <?php if ($message): ?>
        <p class="success-msg"><?php echo $message; ?></p>
    <?php endif; ?>
    <button class="main-btn" id="bookBtn">Забронировать эту квартиру</button>
    <div id="bookingForm">
        <h3 style="margin:0 0 10px 0;">Заявка на бронирование</h3>
        <form method="post" action="apartment.php?id=<?php echo $apt['id']; ?>">
            <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
            <label>Ваше имя: <input type="text" name="name"></label>
            <label>Телефон: <input type="text" name="phone"></label>
            <label>Email: <input type="email" name="email"></label>
            <button type="submit" name="reserve" value="1">Отправить заявку</button>
        </form>
    </div>
</div>

<script>
// THREE.JS BLOCK (3D-cube in adaptive box)
const threeContainer = document.getElementById('three-container');
threeContainer.innerHTML = '';
const width = threeContainer.offsetWidth;
const height = threeContainer.offsetHeight;
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
renderer.setSize(width, height);
threeContainer.appendChild(renderer.domElement);
// Simple 3D box as placeholder for "building"
const geometry = new THREE.BoxGeometry(1, 1, 1);
const material = new THREE.MeshPhongMaterial({ color: 0x3f8bfd });
const cube = new THREE.Mesh(geometry, material);
scene.add(cube);
const light = new THREE.DirectionalLight(0xffffff, 1);
light.position.set(2, 2, 5);
scene.add(light);
camera.position.z = 4;
function animate() {
    requestAnimationFrame(animate);
    cube.rotation.x += 0.012;
    cube.rotation.y += 0.014;
    renderer.render(scene, camera);
}
animate();
// Resize 3D view adaptively
window.addEventListener('resize', function() {
    const w = threeContainer.offsetWidth;
    const h = threeContainer.offsetHeight;
    renderer.setSize(w, h);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
});
</script>
<script>
// YANDEX MAP: Geocode address & mark apartment
var address = <?php echo json_encode($apt['address']); ?>;
ymaps.ready(function () {
    var map = new ymaps.Map('infra-map', {
        center: [55.751244, 37.618423],
        zoom: 14
    });
    ymaps.geocode(address, { results: 1 }).then(function (res) {
        var obj = res.geoObjects.get(0);
        if (obj) {
            var coords = obj.geometry.getCoordinates();
            var placemark = new ymaps.Placemark(coords, {
                balloonContentHeader: <?php echo json_encode($apt['name']); ?>,
                balloonContentBody: address
            });
            map.geoObjects.add(placemark);
            map.setCenter(coords, 15);
        }
    });
});
</script>
<script>
document.getElementById('bookBtn').onclick = function() {
    document.getElementById('bookingForm').style.display = 'block';
    this.style.display = 'none';
}
</script>
</body>
</html>
