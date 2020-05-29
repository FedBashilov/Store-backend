<?php
//Подключение библиотечных скриптов для JWT токена
include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT; //Подключение пространства имен

function route($method, $urlData, $formData) { //Главная функция

    require 'database.php'; //Подключаем скрипт с БД
    include_once 'JWT/clientJWT.php'; //Подключаем скрипт с JWT


    if ($method === 'GET') { // Запрос GET ../API/clients
      $jwt = getallheaders()["JWT"];  //Получение JWT из заголовков запроса

      if($jwt) {
        // Если декодирование выполнено успешно, показать данные пользователя
        try {
            $decoded = JWT::decode($jwt, $key, array('HS256')); // Декодирование jwt
            http_response_code(200);
            echo json_encode($decoded->data);
        }
        // Если декодирование не удалось, это означает, что JWT является недействительным
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Ошибка доступа!");
        }
      }
      else { // показать сообщение об ошибке, если jwt пуст
        http_response_code(401);
        echo json_encode("Ошибка доступа!");
      }
      return;
    }

    if ($method === 'POST') {
      if (count($urlData) === 0) { //Запрос  POST ../API/clients
          $client = json_decode($formData);  //Получение объекта из тела запроса

          /*Экранируем специальные символы в строках для использования в SQL выражении,
          используя текущий набор символов соединения*/
          $first_name = mysqli_real_escape_string($con, trim( $client->data->first_name));
          $last_name = mysqli_real_escape_string($con, trim( $client->data->last_name));
          $email = mysqli_real_escape_string($con, trim( $client->data->email));
          $phone = mysqli_real_escape_string($con, trim( $client->data->phone));
          $password = mysqli_real_escape_string($con, trim( $client->data->password));

          $password = password_hash($password, PASSWORD_DEFAULT); //хешируем пароль
          if(!$password){
            echo json_encode("Ошибка! Невозможно создать пароль");
            return;
          }

          $sqlCheckEmail = "SELECT * FROM client WHERE email='{$email}'";

          //Если такой Email еще не зарегистрирован
          if( mysqli_query($con,$sqlCheckEmail)->num_rows == 0 ){
            $sqlСlient = "INSERT INTO `client` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`)
            VALUES (null,'{$first_name}','{$last_name}', '{$email}', '{$phone}', '{$password}')";
            //Если вставка успешна
            if(mysqli_query($con,$sqlСlient)){
              $newСlientId = mysqli_insert_id($con); //Получения id вставленной записи
              $token = array(
                 "iss" => $iss,
                 "aud" => $aud,
                 "iat" => $iat,
                 "nbf" => $nbf,
                 "data" => array(
                     "id" => $newclientId,
                     "first_name" => $first_name,
                     "last_name" => $last_name,
                     "email" => $email,
                     "phone" => $phone
                 )
              );

              http_response_code(201);

              // Создание и отправка jwt
              $jwt = JWT::encode($token, $key);
              echo json_encode(array("jwt" => $jwt));

            } else {
              http_response_code(422);
              echo json_encode("Ошибка регистрации!");
            }
          } else {
            http_response_code(200);
            echo json_encode("Email уже зарегистрирован!");
          }
          return;
      }
      else {
        // Запрос POST ../API/clients/login
        $client = json_decode($formData);

        $email = mysqli_real_escape_string($con, trim( $client->data->email));
        $password = mysqli_real_escape_string($con, trim( $client->data->password));

        $sqlClient = "SELECT * FROM `client` WHERE email='$email'";

        //Если клиент с таким email зарегистррован
        if( $row = mysqli_fetch_assoc( mysqli_query($con,$sqlClient) ) ){
          //Если верный пароль
          if( password_verify( $password, $row['password'] ) ){
            $token = array(
               "iss" => $iss,
               "aud" => $aud,
               "iat" => $iat,
               "nbf" => $nbf,
               "data" => array(
                   "id" => $row['id'],
                   "first_name" => $row['first_name'],
                   "last_name" => $row['last_name'],
                   "email" => $row['email'],
                   "phone" => $row['phone']
               )
            );

            http_response_code(200);

            // Создание и отправка jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode(array("jwt" => $jwt));
          }
          else{
            http_response_code(200);
            echo json_encode("Неверный пароль!");
          }

        }
        else {
          http_response_code(200);
          echo json_encode("Неверный email!");
        }
        return;
      }

    }

    if($method === 'PUT'){ // Запрос PUT ../API/clients
      $jwt = getallheaders()["JWT"]; //Получение JWT из заголовков запроса

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo "Ошибка доступа!";
          return;
        }
        $client = json_decode($formData);

        $id = mysqli_real_escape_string($con, trim($client->data->id));
        $first_name = mysqli_real_escape_string($con, trim($client->data->first_name));
        $last_name = mysqli_real_escape_string($con, trim($client->data->last_name));
        $email = mysqli_real_escape_string($con, trim($client->data->email));
        $phone = mysqli_real_escape_string($con, trim($client->data->phone));

        if($id == $decoded->data->id){
          $sqlClient = "UPDATE `client` SET first_name='{$first_name}', last_name='{$last_name}', email='{$email}', phone='{$phone}' WHERE id='{$id}'";

          //Если обновление записи удалось
          if(mysqli_query($con,$sqlClient)){
            $token = array(
              "iss" => $iss,
              "aud" => $aud,
              "iat" => $iat,
              "nbf" => $nbf,
              "data" => array(
                "id" => $id,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "email" => $email,
                "phone" => $phone
              )
            );

            http_response_code(200); //Код ответа

            // Создание и отправка jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode("jwt" => $jwt);
          }
          else {
            http_response_code(422);
            echo json_encode("Ошибка обновления информации о пользователе!");
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Ошибка доступа!");
        }
      }
      else {
        http_response_code(401);
        echo json_encode("Ошибка доступа!");
      }
      return;

    }


    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
        'error' => 'Bad Request'
    ));
}

?>
