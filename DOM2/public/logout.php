<?php
require 'config.php';
// Очистка всех данных сессии и ее завершение
$_SESSION = [];
session_destroy();
// Перенаправление на главную страницу
header("Location: index.php");
exit;
