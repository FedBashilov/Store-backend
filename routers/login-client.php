<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'clientJWT.php';

    // POST /login-client
    if ($method === 'POST') {
        $client = json_decode($formData);
    // Sanitize.
        $email = mysqli_real_escape_string($con, trim( $client->data->email));
        $password = mysqli_real_escape_string($con, trim( $client->data->password));

        $sqlclient = "SELECT * FROM `client` WHERE email='$email'";

        if( $row = mysqli_fetch_assoc( mysqli_query($con,$sqlclient) ) ){

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

            http_response_code(201);

            // создание jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode(array("jwt" => $jwt));
          }
          else{
            echo json_encode("Неверный пароль!");
          }

        }
        else {
            echo json_encode("Неверный email!");
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
