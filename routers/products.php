<?php
//Подключение библиотечных скриптов для JWT токена
include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php'; //Подключение пространства имен
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) { //Главная функция

    require 'database.php'; //Подключаем скрипт с БД


    if ($method === 'GET') {


      if (count($urlData) === 0) {   //Запрос GET ../API/products
        $allProducts = [];  //Переменная для массива товаров
        $sql = "SELECT * FROM product";
        //Если товары получены из БД
        if($result = mysqli_query($con,$sql)) {
          //Записываем все товары в массив
          for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
            $allProducts[$i]['id'] = $row['id'];
            $allProducts[$i]['name'] = $row['name'];
            $allProducts[$i]['description'] = $row['description'];
            $allProducts[$i]['price'] = $row['price'];
            $allProducts[$i]['photo'] = $row['photo'];
          }
          http_response_code(200); //Код ответа
          echo json_encode($allProducts); //Возвращаем товары
        }
        else {
          http_response_code(404);
        }
        return;
      }

      if (count($urlData) === 1) {

        if($urlData[0] === 'boughtByClient'){ //Запрос GET ../API/products/boughtByClient
          include_once 'JWT/clientJWT.php'; //Подключаем скрипт с JWT
          $jwt = getallheaders()["JWT"];  //Получение JWT из заголовков запроса

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
              $sqlProductsIds = "SELECT product_id FROM `order_product` WHERE order_id IN (
                SELECT id FROM `client_order` WHERE client_id='{$decoded->data->id}' AND bought=1 )";
              //Если получены id товаров
              if($resProductsIds = mysqli_query($con, $sqlProductsIds) ){
                $productsIds = [];  //Переменная для массива id товаров
                //Записываем массив id
                for($i = 0; $row = mysqli_fetch_assoc($resProductsIds); $i++) {
                  $productsIds[$i] = $row['product_id'];
                }
                http_response_code(200);
                echo json_encode($productsIds); //Возвращаем массив id товаров
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

        }
        else{ //Запрос GET ../API/products/{id}
          $id = $urlData[0]; //Получаем id товара из URL запроса
          $sql = "SELECT id, name, description, price, photo FROM product WHERE id = '$id'";
          $result = mysqli_query($con,$sql);
          //Если найден такой товар в БД
          if($row = mysqli_fetch_assoc($result)) {
            //Записываем его в переменную
            $product['id'] = $row['id'];
            $product['name'] = $row['name'];
            $product['description'] = $row['description'];
            $product['price'] = $row['price'];
            $product['photo'] = $row['photo'];
            http_response_code(200);
            echo json_encode($product); //Возвращаем товар
          }
          else {
            http_response_code(404);
          }
        }
      }

      return;
    }

    if ($method === 'POST') {
      include_once 'JWT/adminJWT.php'; //Подключаем скрипт с JWT
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
        if( mysqli_query($con, $sqlCheckAdmin) ){
          $newProduct = json_decode($formData);  //Получение объекта из тела запроса

          $name = mysqli_real_escape_string($con, trim( $newProduct->data->name));
          $price = mysqli_real_escape_string($con, trim( $newProduct->data->price));
          $description = mysqli_real_escape_string($con, trim( $newProduct->data->description));
          $photo = mysqli_real_escape_string($con, trim( $newProduct->data->photo));

          $sqlProduct = "INSERT INTO `product`(`id`,`name`, `description`, `price`) VALUES (null,'{$name}','{$description}', '{$price}')";
          //Если вставка товара удалась
          if(mysqli_query($con,$sqlProduct)){
            $id = mysqli_insert_id($con); //Получения id вставленной записи

            //Если товар добавлен вместе с фото
            if($photo) {
              if(preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                $photo = substr($photo, strpos($photo, ',') + 1);
                $type = strtolower($type[1]); // Формат картинки
                //Проверка фотмата
                if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                  throw new \Exception('invalid image type');
                }
                $photo = base64_decode($photo); //Декодируем base64 фото
                if ($photo === false) {
                  throw new \Exception('base64_decode failed');
                }
              }
              else {
                throw new \Exception('did not match data URI with image data');
              }

              //Сохраняем картинку на сервере
              file_put_contents("backend/product-photos/{$id}.{$type}", $photo);

              $sqlProduct = "UPDATE `product` SET name='{$name}', price='{$price}', description='{$description}', photo='{$id}.{$type}' WHERE id='{$id}'";
              //Если получилосб обновить путь картинки для данного товара в БД
              if(mysqli_query($con,$sqlProduct)){
                http_response_code(200);
                echo json_encode($id);  //Возвращаем id нового товара
              }
            }
          }
          else{
            http_response_code(422);
            echo json_encode("Ошибка добавления товара!");
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

    if($method === 'PUT') {
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
        //Если id клиента из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckAdmin) ){
          $newProduct = json_decode($formData);

          $id = mysqli_real_escape_string($con, trim( $newProduct->data->id));
          $name = mysqli_real_escape_string($con, trim( $newProduct->data->name));
          $price = mysqli_real_escape_string($con, trim( $newProduct->data->price));
          $description = mysqli_real_escape_string($con, trim( $newProduct->data->description));
          $photo = mysqli_real_escape_string($con, trim( $newProduct->data->photo));

          $sqlProduct = "UPDATE `product` SET name='{$name}', price='{$price}', description='{$description}' WHERE id='{$id}'";
          //Если товар обновлен
          if(mysqli_query($con,$sqlProduct)){
            //Если нужно обновить фото
            if($photo) {
              if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                $photo = substr($photo, strpos($photo, ',') + 1);
                $type = strtolower($type[1]); // Фотмат

                if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                  throw new \Exception('invalid image type');
                }

                $photo = base64_decode($photo); //Декодирование base64
                if ($photo === false) {
                  throw new \Exception('base64_decode failed');
                }
              }
              else {
                throw new \Exception('did not match data URI with image data');
              }

              $sqlOldPhoto = "SELECT photo FROM `product` WHERE id='{$id}'";
              //Если получили старый путь к фото товара
              if($result = mysqli_query($con,$sqlOldPhoto)){
                $row = mysqli_fetch_assoc($result);
                //Удаление старого фото с сервера
                unlink("backend/product-photos/".$row['photo']);
                //Добавление нового фото на сервер
                file_put_contents("backend/product-photos/{$id}.{$type}", $photo);

                $sqlProduct = "UPDATE `product` SET name='{$name}', price='{$price}', description='{$description}', photo='{$id}.{$type}' WHERE id='{$id}'";
                //Если успешно обновили путь к фото товара
                if(mysqli_query($con,$sqlProduct)){
                  http_response_code(200);
                  echo json_encode($id);  //Возвращаем id обновленного товара
                }
              }
            }
            http_response_code(200);
            echo json_encode($id); //Возвращаем id обновленного товара
          }
          else{
            http_response_code(422);
            echo json_encode("Ошибка обновления информации о товаре!");
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


    if($method === 'DELETE' && count($urlData) === 1) { //Запрос DELETE ../API/products/{id}
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
        //Если id клиента из JWT токена существует в таблице БД
        if( mysqli_query($con, $sqlCheckAdmin) ){
          $productId = $urlData[0]; //Получаем id товара из URL запроса
          $sqlProductPhoto = "SELECT photo FROM `product` WHERE id=".$productId;
          //Если получили путь к фото товара
          if( $result = mysqli_query($con, $sqlProductPhoto) ){
            $row = mysqli_fetch_assoc($result); // Путь к фото
            $sqlDelete = "DELETE FROM `product` WHERE id=".$productId;
            //Если удалили товар из БД
            if( mysqli_query($con, $sqlDelete) ){
              //Удаляем фото товара
              if( file_exists('backend/product-photos/'.$row['photo']) ) {
                unlink('backend/product-photos/'.$row['photo']);
              }
              http_response_code(204);  //Код ответа
            }
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
