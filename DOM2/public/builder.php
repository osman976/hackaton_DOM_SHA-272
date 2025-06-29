<?php 
require 'config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'builder') {
    header("Location: login.php");
    exit;
}

$msgComplex = '';
$msgApartment = '';

// Добавление нового комплекса — ТОЛЬКО имя и описание
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_complex'])) {
    $compName = trim($_POST['comp_name'] ?? '');
    $compDesc = trim($_POST['comp_desc'] ?? '');
    if ($compName === '') {
        $msgComplex = 'Пожалуйста, заполните название ЖК.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO complexes (name, description) VALUES (?, ?)");
        try {
            $stmt->execute([$compName, $compDesc]);
            $msgComplex = 'Жилой комплекс успешно добавлен.';
        } catch (PDOException $e) {
            $msgComplex = 'Ошибка сохранения комплекса: ' . $e->getMessage();
        }
    }
}

// Добавление квартиры — добавить поле адрес!
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_apartment'])) {
    $aptName = trim($_POST['apt_name'] ?? '');
    $aptAddress = trim($_POST['apt_address'] ?? '');
    $aptDesc = trim($_POST['apt_desc'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $rooms = trim($_POST['rooms'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $complexId = $_POST['complex_id'] ?? '';
    if ($aptName === '' || $complexId === '' || $aptAddress === '') {
        $msgApartment = 'Заполните название, адрес квартиры и выберите ЖК.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO apartments 
            (complex_id, name, address, description, price, rooms, area, floor) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $complexId, $aptName, $aptAddress, $aptDesc, 
                $price ?: null, $rooms ?: null, $area ?: null, $floor ?: null
            ]);
            $msgApartment = 'Квартира успешно добавлена.';
        } catch (PDOException $e) {
            $msgApartment = 'Ошибка добавления квартиры: ' . $e->getMessage();
        }
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --accent: #377dff;
            --accent-dark: #2353a6;
            --bg: #f3f6fb;
            --glass: rgba(255,255,255,0.85);
            --border: #c3d1ed;
            --shadow: 0 8px 32px 0 rgba(60,90,170,0.11), 0 1.5px 6px #377dff33;
        }
        body, html { margin: 0; padding: 0; background: var(--bg); font-family: 'Segoe UI', 'Inter', Arial, sans-serif;}
        header { background: linear-gradient(90deg, #edf3ff 0%, #e3eaff 100%); padding: 36px 0 16px 0; text-align: center; box-shadow: 0 2px 24px #dae9fc30; border-radius: 0 0 36px 36px; margin-bottom: 16px;}
        header h1 { font-size: 2rem; font-weight: 800; margin: 0 0 7px 0; letter-spacing: -1px; color: var(--accent-dark); text-shadow: 0 2px 8px #bddcff24;}
        nav { background: none; margin-bottom: 4px; font-size: 1.05rem; text-align: center;}
        nav a, nav strong { color: var(--accent-dark); text-decoration: none; margin: 0 12px; font-weight: 600;}
        nav a { border-bottom: 2px solid transparent; transition: color .15s, border .15s;}
        nav a:hover { color: var(--accent); border-bottom: 2px solid var(--accent);}
        main { max-width: 960px; margin: 0 auto; padding: 0 18px; flex-wrap: wrap; gap: 34px; align-items: flex-start;}
        .block { background: var(--glass); border-radius: 22px; box-shadow: var(--shadow); border: 1.5px solid var(--border); padding: 28px 34px 26px 34px; margin-bottom: 24px; min-width: 320px; flex: 1 1 330px;}
        .block h2, .block h3 { margin-top: 0; color: var(--accent-dark); font-weight: 700; margin-bottom: 12px; font-size: 1.12rem;}
        .block ul { padding-left: 18px; margin: 0 0 10px 0;}
        .block li {margin-bottom: 4px; color: #282c36;}
        .block form p, .block form label, .block form input, .block form textarea, .block form select { font-size: 1rem;}
        .block form input, .block form textarea, .block form select {
            border-radius: 10px; border: 1.5px solid var(--border); padding: 7px 12px; background: #f5f8fd; margin-bottom: 13px; margin-top: 3px;
            width: 100%; box-sizing: border-box; transition: border .13s;
        }
        .block form input:focus, .block form textarea:focus, .block form select:focus { border-color: var(--accent);}
        .block button { margin-top: 6px; padding: 9px 30px; border: none; border-radius: 9px; background: linear-gradient(90deg, var(--accent), #7dd3fc); color: #fff; font-weight: 700; cursor: pointer; font-size: 1.08rem; box-shadow: 0 2px 12px #73affc26; transition: background .19s, color .2s, transform .17s; letter-spacing: .01em;}
        .block button:hover { background: var(--accent-dark); color: #fff; transform: translateY(-1.5px) scale(1.03);}
        .msg-success { color: #009688; margin-bottom: 8px; font-weight: 600;}
        .msg-error { color: #ff1744; margin-bottom: 8px; font-weight: 600;}
        .apartment-list { display: grid; gap: 16px; margin-top: 10px;}
        .apt-card {
            background: #f6faff; border-radius: 13px; box-shadow: 0 2px 16px #7cbaff22;
            border: 1px solid #e3eefc; padding: 19px 17px 12px 19px;
            display: grid; grid-template-columns: 1fr 1.2fr 1.3fr 1fr 1fr 1fr 1fr 1fr;
            align-items: center; font-size: 1rem;
        }
        .apt-card-header { background: none; font-weight: bold; color: var(--accent-dark);}
        .apt-card .apt-id {color: #7fa0ca;}
        .apt-card .apt-name {font-weight: 600;}
        .apt-card .apt-price {color: var(--accent);}
        @media (max-width:900px) {
            main { flex-direction: column; gap: 16px;}
            .block { min-width: unset;}
            .apt-card { grid-template-columns: 1fr 1.2fr 1.2fr 1fr 1fr 1fr 1fr;}
        }
        @media (max-width:600px) {
            .block { padding: 12px 6vw;}
            .apt-card { font-size: .92rem; grid-template-columns: 1fr 1fr 1fr; gap: 2px;}
        }
    </style>
</head>
<body>
<header>
    <nav>
        Вы вошли как <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?> (застройщик)</strong>
        <a href="index.php">На сайт</a> 
        <a href="logout.php">Выйти</a>
    </nav>
    <h1>Кабинет застройщика</h1>
</header>
<main>
    <div class="block" style="flex:1.1">
        <h2>Ваши жилые комплексы</h2>
        <?php if ($complexes): ?>
            <ul>
                <?php foreach ($complexes as $comp): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($comp['name']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color:#b1b1b7;">Вы еще не добавили ни одного ЖК.</p>
        <?php endif; ?>

        <h3>Добавить новый жилой комплекс</h3>
        <?php if ($msgComplex): ?>
            <div class="<?php echo strpos($msgComplex, 'успешн')!==false ? 'msg-success' : 'msg-error'; ?>">
                <?php echo htmlspecialchars($msgComplex); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="builder.php">
            <input type="hidden" name="add_complex" value="1">
            <p><label>Название ЖК:<br>
                <input type="text" name="comp_name" required></label></p>
            <p><label>Описание ЖК:<br>
                <textarea name="comp_desc" cols="30" rows="2"></textarea></label></p>
            <button type="submit">Добавить комплекс</button>
        </form>
    </div>
    <div class="block" style="flex:2">
        <h2>Ваши квартиры</h2>
        <?php if ($apartments): ?>
            <div class="apartment-list">
                <div class="apt-card apt-card-header">
                    <span>ID</span>
                    <span>Название</span>
                    <span>Адрес</span>
                    <span>ЖК</span>
                    <span>Цена</span>
                    <span>Комнат</span>
                    <span>Площадь</span>
                    <span>Этаж</span>
                </div>
                <?php foreach ($apartments as $apt): ?>
                    <div class="apt-card">
                        <span class="apt-id"><?php echo $apt['id']; ?></span>
                        <span class="apt-name"><?php echo htmlspecialchars($apt['name']); ?></span>
                        <span><?php echo htmlspecialchars($apt['address']); ?></span>
                        <span><?php echo htmlspecialchars($apt['complex_name']); ?></span>
                        <span class="apt-price"><?php echo $apt['price'] ? number_format($apt['price'],0,'',' ') . ' ₽' : ''; ?></span>
                        <span><?php echo $apt['rooms'] ?: ''; ?></span>
                        <span><?php echo $apt['area'] ? $apt['area'] . ' м²' : ''; ?></span>
                        <span><?php echo $apt['floor'] ?: ''; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#b1b1b7;">Пока нет ни одной квартиры.</p>
        <?php endif; ?>

        <h3>Добавить новую квартиру</h3>
        <?php if ($msgApartment): ?>
            <div class="<?php echo strpos($msgApartment, 'успешн')!==false ? 'msg-success' : 'msg-error'; ?>">
                <?php echo htmlspecialchars($msgApartment); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="builder.php">
            <input type="hidden" name="add_apartment" value="1">
            <p><label>Название/№ квартиры:<br>
                <input type="text" name="apt_name" required></label></p>
            <p><label>Жилой комплекс:<br>
                <select name="complex_id" required>
                    <option value="">-- выберите ЖК --</option>
                    <?php foreach ($complexes as $comp): ?>
                    <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                    <?php endforeach; ?>
                </select></label></p>
            <p><label>Адрес квартиры:<br>
                <input type="text" name="apt_address" required></label></p>
            <p><label>Описание квартиры:<br>
                <textarea name="apt_desc" cols="30" rows="2"></textarea></label></p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <p style="flex:1;min-width:120px"><label>Цена (₽):<br><input type="number" name="price" min="0"></label></p>
                <p style="flex:1;min-width:120px"><label>Комнат:<br><input type="number" name="rooms" min="1"></label></p>
                <p style="flex:1;min-width:120px"><label>Площадь (м²):<br><input type="number" name="area" step="0.1" min="0"></label></p>
                <p style="flex:1;min-width:120px"><label>Этаж:<br><input type="number" name="floor" min="1"></label></p>
            </div>
            <button type="submit">Добавить квартиру</button>
        </form>
    </div>
</main>
<footer style="text-align:center; font-size:1rem; color:#a3abbc; padding:24px 0 30px 0;">
    &copy; 2025 – Панель управления застройщика
</footer>
</body>
</html>
