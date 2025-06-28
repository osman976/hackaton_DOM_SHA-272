<?php
// Инициализация сессии для сохранения состояния авторизации
session_start();

// Параметры соединения с базой данных
$dsn = 'mysql:host=db;dbname=realestate;charset=utf8';
$dbUser = 'root';
$dbPass = 'rootpass';

try {
    // Установка соединения с базой (PDO)
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Прерываем выполнение, если не удалось подключиться
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
