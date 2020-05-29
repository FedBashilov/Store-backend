<?php
//Подключение библиотечных скриптов для JWT токена
include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT; //Подключение пространства имен

function route($method, $urlData, $formData) { //Главная функция

    require 'database.php'; //Подключаем скрипт с БД


    if($method === 'GET'){
      include_once 'JWT/adminJWT.php'; //Подключаем скрипт с JWT

      if (count($urlData) === 0) {  //Запрос GET ../API/orders
        $jwt = getallheaders()["JWT"]; //Получение JWT из заголовков запроса

        if($jwt) {
          try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
          }
          catch (Exception $e){
            http_response_code(401);
            echo json_encode("Ошибка доступа!");
            return;
          }

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          //Если id админа из JWT токена существует в таблице БД
          if(mysqli_query($con, $sqlCheckAdmin) ){
            $sqlOrders = "SELECT * FROM `client_order`";
            //Если получены все заказы
            if($result = mysqli_query($con,$sqlOrders)){
              //Записываем все заказы в массив
              for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
                $allOrders[$i]['id'] = $row['id'];
                $allOrders[$i]['address'] = $row['address'];
                $allOrders[$i]['viewed'] = $row['viewed'] == 1;
                $allOrders[$i]['bought'] = $row['bought'] == 1;
                $allOrders[$i]['created'] = $row['created'];

                $sqlClient = "SELECT first_name, phone, email FROM `client` WHERE id=".$row['client_id'];
                //Если получаем данные о пользователе, оформившем заказ
                if( $client = mysqli_fetch_assoc(mysqli_query($con,$sqlClient)) ){
                  //Записываем данные в массив
                  $allOrders[$i]['client_first_name'] = $client['first_name'];
                  $allOrders[$i]['phone'] = $client['phone'];
                  $allOrders[$i]['email'] = $client['email'];
                }

                $sqlOrderProducts = "SELECT * FROM `order_product` WHERE order_id=".$row['id'];
                //Если получаем список товаров заказа
                if( $resOrderProducts = mysqli_query($con,$sqlOrderProducts) ){
                  $allOrders[$i]['total_price'] = 0;  // Параметр для подсчета общей суммы заказа
                  //Для каждого товара
                  for($j = 0; $resOrderProduct = mysqli_fetch_assoc($resOrderProducts); $j++) {
                    $sqlProduct = "SELECT * FROM `product` WHERE id=".$resOrderProduct['product_id'];
                    //Если получена информация о товаре
                    if( $resProduct = mysqli_fetch_assoc(mysqli_query($con,$sqlProduct)) ){
                      //Записываем инфомацию о товаре
                      $allOrders[$i]['products'][$j]['id'] = $resProduct['id'];
                      $allOrders[$i]['products'][$j]['name'] = $resProduct['name'];
                      $allOrders[$i]['products'][$j]['amount'] = $resOrderProduct['amount'];
                      $allOrders[$i]['total_price'] += $resProduct['price'] * $resOrderProduct['amount'];
                    }
                  }
                }
              }
              http_response_code(200);
              echo json_encode($allOrders); //Возвращаем все заказы
            }
            else{
              http_response_code(404);
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


      if (count($urlData) === 1) { //Запрос GET ../API/orders/{id}
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

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          //Если id админа из JWT токена существует в таблице БД
          if( mysqli_query($con, $sqlCheckAdmin) ){
            $sqlOrder = "SELECT * FROM `client_order` WHERE id=".$urlData[0];
            //Если получена информация о заказе
            if($result = mysqli_query($con,$sqlOrder)){
              //Записываем заказ
              $row = mysqli_fetch_assoc($result);
              $order['id'] = $row['id'];
              $order['address'] = $row['address'];
              $order['viewed'] = $row['viewed'] == 1;
              $order['bought'] = $row['bought'] == 1;
              $order['created'] = $row['created'];

              $sqlClient = "SELECT first_name, phone, email FROM `client` WHERE id=".$row['client_id'];
              //Если получена информация о клиенте, оформившем заказ
              if( $client = mysqli_fetch_assoc(mysqli_query($con,$sqlClient)) ){
                //Записываем клиента
                $order['client_first_name'] = $client['first_name'];
                $order['phone'] = $client['phone'];
                $order['email'] = $client['email'];
                }

                $sqlOrderProducts = "SELECT * FROM `order_product` WHERE order_id=".$row['id'];
                //Если получена информация о товарах заказа
                if( $resOrderProducts = mysqli_query($con,$sqlOrderProducts) ){
                  $order['total_price'] = 0;  // Параметр для подсчета общей суммы заказа
                  //Для каждого товара
                  for($i = 0; $resOrderProduct = mysqli_fetch_assoc($resOrderProducts); $i++) {
                    $sqlProduct = "SELECT * FROM `product` WHERE id=".$resOrderProduct['product_id'];
                    //Если получена информация о товаре
                    if( $resProduct = mysqli_fetch_assoc(mysqli_query($con,$sqlProduct)) ){
                      //Записываем инфомацию о товаре
                      $order['products'][$i]['id'] = $resProduct['id'];
                      $order['products'][$i]['name'] = $resProduct['name'];
                      $order['products'][$i]['amount'] = $resOrderProduct['amount'];
                      $order['total_price'] += $resProduct['price'] * $resOrderProduct['amount'];
                    }
                  }
                }
              http_response_code(200);
              echo json_encode($order); //Возвращаем заказ
            }
            else{
              http_response_code(404);
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

    }


    if ($method === 'POST') { //Запрос POST ../API/orders
      include_once 'JWT/clientJWT.php'; //Подключаем скрипт с JWT
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
        //Если id клиента из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckClient)){
          $newOrder = json_decode($formData);  //Получение объекта из тела запроса

          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $address = mysqli_real_escape_string($con, trim( $newOrder->data->address));

          $sqlOrder = "INSERT INTO `client_order`(`id`,`client_id`, `address`, `viewed`, `bought`) VALUES (null,'{$client_id}','{$address}', false, false)";
          //Если удалось создать новый заказ
          if(mysqli_query($con,$sqlOrder)){
            $newOrderId = mysqli_insert_id($con); //Получения id вставленной записи

            //Для каждого товара заказа
            foreach($newOrder->data->products as $order_product){
              $sqlOrderProduct= "INSERT INTO `order_product`(`order_id`,`product_id`,`amount`) VALUES ('{$newOrderId}','{$order_product->id}', '{$order_product->amount}')";

              //Если не удалось добавить товары заказа в БД
              if(!mysqli_query($con, $sqlOrderProduct)){
                http_response_code(422);
                echo json_encode("Ошибка создания заказа!");
                return;
              }
            }

            http_response_code(201);
            echo json_encode($newOrderId);  //Возвращаем ID нового заказа
          }
          else{
            http_response_code(422);
            echo "Ошибка создания заказа!";
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


    if ($method === 'PUT') {   //Запрос  PUT ../API/orders
        include_once 'JWT/adminJWT.php';
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

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          //Если id админа из JWT токена существует в таблице БД
          if( mysqli_query($con, $sqlCheckAdmin) ){
            $newOrder = json_decode($formData);

            $id = mysqli_real_escape_string($con, trim( $newOrder->data->id));
            $address = mysqli_real_escape_string($con, trim( $newOrder->data->address));
            $viewed = mysqli_real_escape_string($con, trim( $newOrder->data->viewed)) ? 1 : 0;
            $bought = mysqli_real_escape_string($con, trim( $newOrder->data->bought)) ? 1 : 0;

            $sqlOrder = "UPDATE `client_order` SET address='{$address}', viewed='{$viewed}', bought='{$bought}' WHERE id='{$id}'";
            //Если заказ успешно обновлен
            if(mysqli_query($con,$sqlOrder)){
                //Удалить и вставить новый список товаров заказа в БД
                $sqlDelete = "DELETE FROM `order_product` WHERE order_id='{$id}'";
                if(mysqli_query($con, $sqlDelete)){

                  foreach($newOrder->data->products as $order_product){
                    $sqlOrderProduct= "REPLACE INTO `order_product`(`order_id`, `product_id`, `amount`) VALUES ('{$id}', '{$order_product->id}', '{$order_product->amount}')";
                    if(!mysqli_query($con, $sqlOrderProduct)){
                      http_response_code(422);
                      echo json_encode("Ошибка обновления информации о заказе!");
                      return;
                    }
                  }
                  http_response_code(200);
                  echo json_encode($id);  //Возвращаем id обновленного заказа
                }
            }
            else {
                http_response_code(422);
                echo "Ошибка обновления информации о заказе!";
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


    if ($method === 'DELETE' && count($urlData) === 1) { // Запрос DELETE ../API/orders/{id}
      include_once 'JWT/adminJWT.php';
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

        $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
        //Если id админа из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckAdmin) ){
          $id = $urlData[0];  //Получаем id заказа из URL запроса
          $sql = "DELETE FROM `client_order` WHERE id=".$id;
          //Если удаление прошло успешно
          if(mysqli_query($con, $sql)) {
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
