<?php
//Заголовок ответа определяет метод или методы , разрешенные при обращении к ресурсу
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$method = $_SERVER['REQUEST_METHOD']; //Метод поступающего запроса

$formData = getFormData($method); //Тело поступающего запроса


function getFormData($method) { //Взависимости от запроса возвращаем тело запроса

    if ($method === 'GET' || $method === 'DELETE')
      return $_GET;
    if ($method === 'POST' || $method === 'PUT'){
      return file_get_contents("php://input");
    }
    //Если использован неизвестный запрос возвращаем ошибку
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
      'error' => 'Bad Request'
    ));
}

// Разбираем URL запроса
$url = explode('?', $_SERVER['REQUEST_URI'], 2);
$url = trim($url[0], '/');
$urls = explode('/', $url);

$router = $urls[1]; //Роутер = Сущность API

$urlData = array_slice($urls, 2); //Все, что указано после сущности



include_once 'routers/' . $router . '.php'; // Подключаение роутера сущности
route($method, $urlData, $formData); //Старт основной функции

?>
