<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'core.php';

    // POST /login
    if ($method === 'POST') {
        $user = json_decode($formData);
    // Sanitize.
        $email = mysqli_real_escape_string($con, trim( $user->data->email));
        $password = mysqli_real_escape_string($con, trim( $user->data->password));

        $sqlUser = "SELECT * FROM `user` WHERE email='$email'";

        if( $row = mysqli_fetch_assoc( mysqli_query($con,$sqlUser) ) ){

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
                   "email" => $row['email']
               )
            );

            http_response_code(201);

            // создание jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode(
                array(
                    "message" => "Успешный вход в систему.",
                    "jwt" => $jwt
                )
            );
          }
          else{
            echo json_encode("Wrong password!");
          }

        }
        else {
            echo json_encode("Wrong email!");
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
