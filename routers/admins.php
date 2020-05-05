<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'adminJWT.php';

    // POST /admin
    if ($method === 'POST') {
        $admin = json_decode($formData);

        // Sanitize.
        $login = mysqli_real_escape_string($con, trim( $admin->data->login));
        $password = mysqli_real_escape_string($con, trim( $admin->data->password));

        $password = password_hash($password, PASSWORD_DEFAULT);
        if(!$password){
          echo json_encode("Error! Cannot make password");
          return;
        }

          $sqlAdmin = "INSERT INTO `admin` (`id`, `login`, `password`) VALUES (null,'{$login}', '{$password}')";

          if(mysqli_query($con,$sqlAdmin)){

            $newAdminId = mysqli_insert_id($con);

            $token = array(
               "iss" => $iss,
               "aud" => $aud,
               "iat" => $iat,
               "nbf" => $nbf,
               "data" => array(
                   "id" => $newAdminId,
                   "login" => $login,
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

      return;
    }


    // return error
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
        'error' => 'Bad Request'
    ));
}

?>
