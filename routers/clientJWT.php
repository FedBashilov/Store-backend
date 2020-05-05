<?php
// показывать сообщения об ошибках
error_reporting(E_ALL);

// установить часовой пояс по умолчанию
date_default_timezone_set('Europe/Moscow');

// переменные, используемые для JWT
$key = "key_for_client_jwt";
$iss = "http://localhost";
$aud = "http://localhost";
$iat = 1356999524;
$nbf = 1357000000;
?>
