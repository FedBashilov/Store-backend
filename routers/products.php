<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';


    if ($method === 'GET') {

    // GET /products
        if (count($urlData) === 0) {
            $allProducts = [];
            $sql = "SELECT * FROM product";

            if($result = mysqli_query($con,$sql)) {
                for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
                  $allProducts[$i]['id'] = $row['id'];
                  $allProducts[$i]['name'] = $row['name'];
                  $allProducts[$i]['description'] = $row['description'];
                  $allProducts[$i]['price'] = $row['price'];
                  $allProducts[$i]['photo'] = $row['photo'];
                }
                echo json_encode($allProducts);
            }
            else {
                http_response_code(404);
            }
            return;
        }

        // GET /products/...
        if (count($urlData) === 1) {

          //GET /products/boughtByClient
          if($urlData[0] === 'boughtByClient'){
            include_once 'clientJWT.php';

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

              $sqlCheckClient = "SELECT * FROM `client` WHERE id=".$decoded->data->id;
              $client = mysqli_query($con, $sqlCheckClient);

              if( $client ){

                $sqlProductsIds = "SELECT product_id FROM `order_product` WHERE order_id IN (
                  SELECT id FROM `client_order` WHERE client_id='{$decoded->data->id}' AND bought=1 )";

                if($resProductsIds = mysqli_query($con, $sqlProductsIds) ){
                  for($i = 0; $row = mysqli_fetch_assoc($resProductsIds); $i++) {
                    $productsIds[$i] = $row['product_id'];
                  }
                  http_response_code(200);
                  echo json_encode($productsIds);
                }
                else{
                  http_response_code(422);
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

          }
          //GET /products/{id}
          else{
            $id = $urlData[0];
            $sql = "SELECT id, name, description, price, photo FROM product WHERE id = '$id'";
            $result = mysqli_query($con,$sql);
            if($row = mysqli_fetch_assoc($result)) {
                $product['id'] = $row['id'];
                $product['name'] = $row['name'];
                $product['description'] = $row['description'];
                $product['price'] = $row['price'];
                $product['photo'] = $row['photo'];
                var_dump($product);
                echo json_encode($product);
            }
            else {
                http_response_code(404);
            }
          }

        }

        return;
    }

    if ($method === 'POST') {
      $newProduct = json_decode($formData);
      //check adminJWT
    }

    if($method === 'DELETE' && count($urlData) === 1) {
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
          $productId = $urlData[0];
          $sqlProduct = "DELETE FROM `product` WHERE id=".$productId;
          if( mysqli_query($con, $sqlProduct) ){
            http_response_code(200);
            echo json_encode("Success");
          }
          else{
            http_response_code(422);
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
