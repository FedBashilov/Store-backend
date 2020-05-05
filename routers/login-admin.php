<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'adminJWT.php';

    // POST /login-admin
    if ($method === 'POST') {
        $admin = json_decode($formData);
    // Sanitize.
        $login = mysqli_real_escape_string($con, trim( $admin->data->login));
        $password = mysqli_real_escape_string($con, trim( $admin->data->password));

        $sqlAdmin = "SELECT * FROM `admin` WHERE login='$login'";

        if( $row = mysqli_fetch_assoc( mysqli_query($con,$sqlAdmin) ) ){

          if( password_verify( $password, $row['password'] ) ){
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

            http_response_code(201);

            // создание jwt
            $jwt = JWT::encode($token, $key);
            echo json_encode($jwt);
          }
          else{
            echo json_encode("Wrong password!");
          }

        }
        else {
            echo json_encode("Wrong login!");
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
