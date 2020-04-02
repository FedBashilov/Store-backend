<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'core.php';

    // GET /user
    if ($method === 'GET') {
      $jwt = getallheaders()["JWT"];

      if($jwt) {
        // если декодирование выполнено успешно, показать данные пользователя
        try {
            // декодирование jwt
            $decoded = JWT::decode($jwt, $key, array('HS256'));

            // код ответа
            http_response_code(200);

            // показать детали
            echo json_encode($decoded->data);

        }
        // если декодирование не удалось, это означает, что JWT является недействительным
        catch (Exception $e){

            // код ответа
            http_response_code(401);

            // сообщить пользователю отказано в доступе и показать сообщение об ошибке
            echo json_encode(array(
                "message" => "Access denied!",
                "error" => $e->getMessage()
            ));
        }
      } else{ // показать сообщение об ошибке, если jwt пуст

        // код ответа
        http_response_code(401);

        // сообщить пользователю что доступ запрещен
        echo json_encode(array("message" => "Access denied!"));
      }
      return;
    }

    // POST /user
    if ($method === 'POST') {
        $user = json_decode($formData);


    // Sanitize.
        $first_name = mysqli_real_escape_string($con, trim( $user->data->first_name));
        $last_name = mysqli_real_escape_string($con, trim( $user->data->last_name));
        $email = mysqli_real_escape_string($con, trim( $user->data->email));
        $password = mysqli_real_escape_string($con, trim( $user->data->password));

        $password = password_hash($password, PASSWORD_DEFAULT);
        if(!$password){
          echo json_encode("Error! Cannot make password");
        }
    //store order
        $sqlCheckEmail = "SELECT * FROM `user` WHERE email=`${email}`";
        if(!mysqli_query($con,$sqlCheckEmail)){
          $sqlUser = "INSERT INTO `user` (`id`, `first_name`, `last_name`, `email`, `password`) VALUES (null,'{$first_name}','{$last_name}', '{$email}', '{$password}')";

          if(mysqli_query($con,$sqlUser)){

            $newUserId = mysqli_insert_id($con);

            $token = array(
               "iss" => $iss,
               "aud" => $aud,
               "iat" => $iat,
               "nbf" => $nbf,
               "data" => array(
                   "id" => $newUserId,
                   "first_name" => $first_name,
                   "last_name" => $last_name,
                   "email" => $email
               )
            );

            http_response_code(201);

            // создание jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode($jwt);

          } else {
            http_response_code(422);
            echo json_encode("Error. Registration failed!");
          }
      } else {
          http_response_code(422);
          echo json_encode("Email is already registered!");
      }
      return;
    }

    // return error
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
        'error' => 'Bad Request'
    ));
}

?>
