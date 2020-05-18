<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';


    if($method === 'GET'){
      include_once 'adminJWT.php';

      //GET /orders
      if (count($urlData) === 0) {
        $jwt = getallheaders()["JWT"];

        if($jwt) {
          try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
          }
          catch (Exception $e){
            http_response_code(401);
            echo json_encode("Access denied!");
            return;
          }

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          $admin = mysqli_query($con, $sqlCheckAdmin);
          if( $admin ){
            $sqlOrders = "SELECT * FROM `client_order`";
            if($result = mysqli_query($con,$sqlOrders)){
              for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
                $allOrders[$i]['id'] = $row['id'];
                $allOrders[$i]['address'] = $row['address'];
                $allOrders[$i]['viewed'] = $row['viewed'] == 1;
                $allOrders[$i]['bought'] = $row['bought'] == 1;
                $allOrders[$i]['created'] = $row['created'];

                $sqlClient = "SELECT first_name, phone, email FROM `client` WHERE id=".$row['client_id'];

                if( $client = mysqli_fetch_assoc(mysqli_query($con,$sqlClient)) ){
                  $allOrders[$i]['client_first_name'] = $client['first_name'];
                  $allOrders[$i]['phone'] = $client['phone'];
                  $allOrders[$i]['email'] = $client['email'];
                }

                $sqlOrderProducts = "SELECT * FROM `order_product` WHERE order_id=".$row['id'];
                if( $resOrderProducts = mysqli_query($con,$sqlOrderProducts) ){
                  $allOrders[$i]['total_price'] = 0;
                  for($j = 0; $resOrderProduct = mysqli_fetch_assoc($resOrderProducts); $j++) {
                    $sqlProduct = "SELECT * FROM `product` WHERE id=".$resOrderProduct['product_id'];
                    if( $resProduct = mysqli_fetch_assoc(mysqli_query($con,$sqlProduct)) ){
                      $allOrders[$i]['products'][$j]['id'] = $resProduct['id'];
                      $allOrders[$i]['products'][$j]['name'] = $resProduct['name'];
                      $allOrders[$i]['products'][$j]['amount'] = $resOrderProduct['amount'];
                      $allOrders[$i]['total_price'] += $resProduct['price'] * $resOrderProduct['amount'];
                    }
                  }
                }
              }
              http_response_code(200);
              echo json_encode($allOrders);
            }
            else{
              http_response_code(404);
            }
          }
          else {
            http_response_code(401);
            echo json_encode("Invalid token! Access denied!");
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Access denied!");
        }
        return;
      }

      //GET /orders/{id}
      if (count($urlData) === 1) {
        $jwt = getallheaders()["JWT"];

        if($jwt) {
          try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
          }
          catch (Exception $e){
            http_response_code(401);
            echo json_encode("Access denied!");
            return;
          }

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          $admin = mysqli_query($con, $sqlCheckAdmin);
          if( $admin ){
            $sqlOrder = "SELECT * FROM `client_order` WHERE id=".$urlData[0];
            if($result = mysqli_query($con,$sqlOrder)){
              $row = mysqli_fetch_assoc($result);
              $order['id'] = $row['id'];
              $order['address'] = $row['address'];
              $order['viewed'] = $row['viewed'] == 1;
              $order['bought'] = $row['bought'] == 1;
              $order['created'] = $row['created'];

              $sqlClient = "SELECT first_name, phone, email FROM `client` WHERE id=".$row['client_id'];

              if( $client = mysqli_fetch_assoc(mysqli_query($con,$sqlClient)) ){
                $order['client_first_name'] = $client['first_name'];
                $order['phone'] = $client['phone'];
                $order['email'] = $client['email'];
                }

                $sqlOrderProducts = "SELECT * FROM `order_product` WHERE order_id=".$row['id'];
                if( $resOrderProducts = mysqli_query($con,$sqlOrderProducts) ){
                  $order['total_price'] = 0;
                  for($i = 0; $resOrderProduct = mysqli_fetch_assoc($resOrderProducts); $i++) {
                    $sqlProduct = "SELECT * FROM `product` WHERE id=".$resOrderProduct['product_id'];
                    if( $resProduct = mysqli_fetch_assoc(mysqli_query($con,$sqlProduct)) ){
                      $order['products'][$i]['id'] = $resProduct['id'];
                      $order['products'][$i]['name'] = $resProduct['name'];
                      $order['products'][$i]['amount'] = $resOrderProduct['amount'];
                      $order['total_price'] += $resProduct['price'] * $resOrderProduct['amount'];
                    }
                  }
                }
              http_response_code(200);
              echo json_encode($order);
            }
            else{
              http_response_code(404);
            }
          }
          else {
            http_response_code(401);
            echo json_encode("Invalid token! Access denied!");
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Access denied!");
        }
        return;
      }

    }


    // POST /orders
    if ($method === 'POST') {
      include_once 'clientJWT.php';

      $jwt = getallheaders()["JWT"];

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Access denidded!");
          return;
        }

        $sqlCheckClient = "SELECT * FROM `client` WHERE id=".$decoded->data->id;
        $client = mysqli_query($con, $sqlCheckClient);
        if( $client ){
          $newOrder = json_decode($formData);
          // Sanitize.
          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $address = mysqli_real_escape_string($con, trim( $newOrder->data->address));
          //store order
          $sqlOrder = "INSERT INTO `client_order`(`id`,`client_id`, `address`, `viewed`, `bought`) VALUES (null,'{$client_id}','{$address}', false, false)";

          if(mysqli_query($con,$sqlOrder)){
            $newOrderId = mysqli_insert_id($con);
            //store order_products
            foreach($newOrder->data->products as $order_product){
              $sqlOrderProduct= "INSERT INTO `order_product`(`order_id`,`product_id`,`amount`) VALUES ('{$newOrderId}','{$order_product->id}', '{$order_product->amount}')";

              if(!mysqli_query($con, $sqlOrderProduct)){
                http_response_code(422);
                echo "error";
                return;
              }
            }

            http_response_code(201);
            echo json_encode($newOrderId);
          }
          else{
            http_response_code(422);
            echo "error";
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Invalid token! Access denied!");
        }
      }
      else {
        http_response_code(401);
        echo json_encode("Access denied!");
      }

      return;
    }

    // PUT /orders
    if ($method === 'PUT') {
        include_once 'adminJWT.php';

        $jwt = getallheaders()["JWT"];

        if($jwt) {
          try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
          }
          catch (Exception $e){
            http_response_code(401);
            echo json_encode("Access denied!");
            return;
          }

          $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
          $admin = mysqli_query($con, $sqlCheckAdmin);
          if( $admin ){
            $newOrder = json_decode($formData);
            // Sanitize.
            $id = mysqli_real_escape_string($con, trim( $newOrder->data->id));
            $address = mysqli_real_escape_string($con, trim( $newOrder->data->address));
            $viewed = mysqli_real_escape_string($con, trim( $newOrder->data->viewed)) ? 1 : 0;
            $bought = mysqli_real_escape_string($con, trim( $newOrder->data->bought)) ? 1 : 0;
            //store order
            $sqlOrder = "UPDATE `client_order` SET address='{$address}', viewed='{$viewed}', bought='{$bought}' WHERE id='{$id}'";

            if(mysqli_query($con,$sqlOrder)){

                $sqlDelete = "DELETE FROM `order_product` WHERE order_id='{$id}'";
                if(mysqli_query($con, $sqlDelete)){

                  foreach($newOrder->data->products as $order_product){

                    $sqlOrderProduct= "REPLACE INTO `order_product`(`order_id`, `product_id`, `amount`) VALUES ('{$id}', '{$order_product->id}', '{$order_product->amount}')";
                    if(!mysqli_query($con, $sqlOrderProduct)){
                      http_response_code(422);
                      echo "error";
                      return;
                    }
                  }
                  http_response_code(200);
                  echo json_encode($id);
                }
            }
            else
            {
                http_response_code(422);
                echo "error";
            }
          }
          else {
            http_response_code(401);
            echo json_encode("Invalid token! Access denied!");
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Access denied!");
        }
      return;
    }

    //DELETE /orders/{id}
    if ($method === 'DELETE' && count($urlData) === 1) {
      include_once 'adminJWT.php';

      $jwt = getallheaders()["JWT"];

      if($jwt) {
        try {
          $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (Exception $e){
          http_response_code(401);
          echo json_encode("Access denied!");
          return;
        }

        $sqlCheckAdmin = "SELECT * FROM `admin` WHERE id=".$decoded->data->id;
        $admin = mysqli_query($con, $sqlCheckAdmin);
        if( $admin ){
          $id = $urlData[0];
          $sql = "DELETE FROM `client_order` WHERE id=".$id;
          if(mysqli_query($con, $sql)){
            http_response_code(200);
            echo json_encode("Deleted success");
          }
          else{
            http_response_code(201);
            echo json_encode("SQL error");
          }
        }
        else {
          http_response_code(401);
          echo json_encode("Invalid token! Access denied!");
        }
      }
      else {
        http_response_code(401);
        echo json_encode("Access denied!");
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
