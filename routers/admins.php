<?php
//Подключение библиотечных скриптов для JWT токена
include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;  //Подключение пространства имен

function route($method, $urlData, $formData) {  //Главная функция

    require 'database.php';  //Подключаем скрипт с БД
    include_once 'JWT/adminJWT.php';  //Подключаем скрипт с JWT

    if ($method === 'POST' && count($urlData) === 1) { // Запрос POST ../API/admins/login
        $admin = json_decode($formData);  //Получение объекта из тела запроса

        /*Экранируем специальные символы в строках для использования в SQL выражении,
        используя текущий набор символов соединения*/
        $login = mysqli_real_escape_string($con, trim( $admin->data->login));
        $password = mysqli_real_escape_string($con, trim( $admin->data->password));

        $sqlAdmin = "SELECT * FROM `admin` WHERE login='$login'"; //SQL запрос

        //Если SQL запрос вернул результат и если пароль верный
        if( $row = mysqli_fetch_assoc( mysqli_query($con,$sqlAdmin) ) &&
          && password_verify( $password, $row['password'] ) ){

          //Создание токена
          $token = array(
             "iss" => $iss,
             "aud" => $aud,
             "iat" => $iat,
             "nbf" => $nbf,
             "data" => array(
               "id" => $row['id'],
               "login" => $row['login']
             )
          );

          http_response_code(200);  //Код ответа

          // Создание и отправка jwt
          $jwt = JWT::encode($token, $key);
          echo json_encode("jwt" => $jwt);
        }
        else {
          http_response_code(422); //Код ответа
          echo json_encode("Неверный логин или пароль!"); //Отправка ошибки
        }
        return;
    }


    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
        'error' => 'Bad Request'
    ));
}

?>
