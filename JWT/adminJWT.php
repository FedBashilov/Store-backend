<?php
error_reporting(E_ALL); // Показывать сообщения об ошибках

date_default_timezone_set('Europe/Moscow'); // Установить часовой пояс по умолчанию

// Переменные, используемые для JWT
$key = "key_for_admin_jwt";
$iss = "http://localhost";
$aud = "http://localhost";
$iat = 1356999524;
$nbf = 1357000000;
?>
