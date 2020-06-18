<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php'; //Подключаем скрипт с БД
    include_once 'JWT/clientJWT.php'; //Подключаем скрипт с JWT


    if ($method === 'GET' && count($urlData) === 2) { //Запрос GET ../API/reviews/of-product/{product_id}
      $product_id = $urlData[1]; //Получаем id товара из URL запроса

      $sql = "SELECT * FROM `review` WHERE product_id=".$product_id;
      //Если отзывы получены
      if($result = mysqli_query($con,$sql)) {
        $reviews = [];  //Переменная для массива отзывов
        //Для каждого отзыва
        for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
          $sqlClient = "SELECT first_name FROM `client` WHERE id=".$row['client_id'];
          //Если получили информацию о клиенте, оставившим отзыв
          if($resClient = mysqli_query($con,$sqlClient)){
            $client = mysqli_fetch_assoc($resClient);
            //Записываем информацию о клиенте
            $reviews[$i]['client']['id'] = $row['client_id'];
            $reviews[$i]['client']['first_name'] = $client['first_name'];
            //Записываем информацию об отзыве
            $reviews[$i]['product_id'] = $row['product_id'];
            $reviews[$i]['text'] = $row['text'];
            $reviews[$i]['rating'] = $row['rating'];
            $reviews[$i]['modified'] = $row['modified'];
          }
        }
        http_response_code(200);  //Код ответа
        echo json_encode($reviews); //Возвращаем отзывы
      }
      else {
        http_response_code(404);
      }
      return;
    }


    if ($method === 'POST') { //Запрос POST ../API/reviews
      $jwt = getallheaders()["JWT"];

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Ошибка доступа!");
          return;
        }

        $sqlCheckClient = "SELECT * FROM `client` WHERE id=".$decoded->data->id;
        //Если id пользователя из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckClient) ){
          $newReview = json_decode($formData);  //Получаем объект из тела запроса

          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $product_id = mysqli_real_escape_string($con, trim( $newReview->data->product_id));
          $text = mysqli_real_escape_string($con, trim( $newReview->data->text));
          $rating = mysqli_real_escape_string($con, trim( $newReview->data->rating));

          $sqlBoughtCheck = "SELECT * FROM `order_product` WHERE product_id='{$product_id}' AND order_id IN (
            SELECT id FROM `client_order` WHERE client_id='{$decoded->data->id}' AND bought=1 )";
          //Если пользователь уже купил этот товар
          if( mysqli_fetch_assoc( mysqli_query($con,$sqlBoughtCheck) ) ){
            $sqlReview = "INSERT INTO `review`(`product_id`,`client_id`, `text`, `rating`)
            VALUES ('{$product_id}','{$client_id}','{$text}', '{$rating}')";
            //Если вставка отзыва в БД прошла успешно
            if(mysqli_query($con,$sqlReview)){
              http_response_code(201);  //Код ответа
            }
            else{
              http_response_code(422);
              echo json_encode("Ошибка создания отзыва!");
            }
          }
          else{
            http_response_code(401);
            echo json_encode("Ошибка доступа!");
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


    if($method === 'PUT') { //Запрос PUT ../API/reviews
      $jwt = getallheaders()["JWT"];

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Ошибка доступа!");
          return;
        }

        $sqlCheckClient = "SELECT * FROM `client` WHERE id=".$decoded->data->id;
        //Если id пользователя из JWT токена существует в таблице БДы
        if( mysqli_query($con, $sqlCheckClient) ){
          $newReview = json_decode($formData);  //Получаем объект из тела запроса

          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $product_id = mysqli_real_escape_string($con, trim( $newReview->data->product_id));
          $text = mysqli_real_escape_string($con, trim( $newReview->data->text));
          $rating = mysqli_real_escape_string($con, trim( $newReview->data->rating));

          $sqlReview = "UPDATE `review` SET text='{$text}', rating='{$rating}' WHERE product_id='{$product_id}' AND client_id='{$client_id}'";
          //Если отзыв успешно обновлен
          if(mysqli_query($con,$sqlReview)){
            http_response_code(200);  //Код ответа
          }
          else{
            http_response_code(422);
            echo json_encode("Ошибка обновления отзыва!");
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


    if($method === 'DELETE' && count($urlData) === 1){ //Запрос DELETE ../API/reviews/{product_id}
      $jwt = getallheaders()["JWT"];

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Ошибка доступа!");
          return;
        }
        $sqlCheckClient = "SELECT * FROM `client` WHERE id=".$decoded->data->id;
        //Если id пользователя из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckClient) ){
          $product_id = $urlData[0]; //Получаем id отзыва из URL запроса
          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));

          $sqlReview = "DELETE FROM `review` WHERE product_id='{$product_id}' AND client_id='{$client_id}'";
          //Если удалили товар из БД
          if(mysqli_query($con,$sqlReview)){
            http_response_code(204);  //Код ответа
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
