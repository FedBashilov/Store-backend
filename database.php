<?php
//Указываем данные для подключения к БД
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'market');

//Попытка подключаение к БД
function connect() {
  $connect = mysqli_connect(DB_HOST ,DB_USER ,DB_PASS ,DB_NAME);
  if (mysqli_connect_errno($connect)) {
    die("Failed to connect:" . mysqli_connect_error());
  }

  mysqli_set_charset($connect, "utf8"); //Устанавливаем кодировку
  return $connect;
}

$con = connect(); // True, если подключились и false, если нет
?>
