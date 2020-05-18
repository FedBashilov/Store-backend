<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'clientJWT.php';

    // GET /client
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

    // POST /client
    if ($method === 'POST') {
        $client = json_decode($formData);

        // Sanitize.
        $first_name = mysqli_real_escape_string($con, trim( $client->data->first_name));
        $last_name = mysqli_real_escape_string($con, trim( $client->data->last_name));
        $email = mysqli_real_escape_string($con, trim( $client->data->email));
        $phone = mysqli_real_escape_string($con, trim( $client->data->phone));
        $password = mysqli_real_escape_string($con, trim( $client->data->password));

        $password = password_hash($password, PASSWORD_DEFAULT);
        if(!$password){
          echo json_encode("Error! Cannot make password");
          return;
        }
        //store order
        $sqlCheckEmail = "SELECT * FROM client WHERE email='{$email}'";

        if( mysqli_query($con,$sqlCheckEmail)->num_rows == 0 ){
          $sqlclient = "INSERT INTO `client` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`) VALUES (null,'{$first_name}','{$last_name}', '{$email}', '{$phone}', '{$password}')";

          if(mysqli_query($con,$sqlclient)){

            $newclientId = mysqli_insert_id($con);

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

            // создание jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode(array("jwt" => $jwt));

          } else {
            echo json_encode("Error. Registration failed!");
          }
      } else {
          echo json_encode("Email is already registered!");
      }
      return;
    }


    if($method === 'PUT'){

      $jwt = getallheaders()["JWT"];

      if($jwt) {
          try {
              $decoded = JWT::decode($jwt, $key, array('HS256'));
          }
          catch (Exception $e){
            http_response_code(401);

            echo json_encode(array(
                "message" => "Access denied!",
                "error" => $e->getMessage()
            ));
            return;
          }
              $client = json_decode($formData);

              // Sanitize.
              $id = mysqli_real_escape_string($con, trim($client->data->id));
              $first_name = mysqli_real_escape_string($con, trim($client->data->first_name));
              $last_name = mysqli_real_escape_string($con, trim($client->data->last_name));
              $email = mysqli_real_escape_string($con, trim($client->data->email));
              $phone = mysqli_real_escape_string($con, trim($client->data->phone));

              if($id == $decoded->data->id){
               $sqlClient = "UPDATE `client` SET first_name='{$first_name}', last_name='{$last_name}', email='{$email}', phone='{$phone}' WHERE id='{$id}'";

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
                    // новый токен jwt
                    $jwt = JWT::encode($token, $key);
                    echo json_encode(array("jwt" => $jwt));
                }
                else
                {
                    http_response_code(201);
                    echo "SQL error";
                }
              }
              else
              {
                  http_response_code(401);
                  echo "Access denied!";
              }
        }
        else
        { // показать сообщение об ошибке, если jwt пуст

          // код ответа
          http_response_code(401);

          // сообщить пользователю что доступ запрещен
          echo json_encode(array("message" => "Access denied!"));
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
