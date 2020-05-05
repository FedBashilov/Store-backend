<?php

include_once 'libs/php-jwt/src/BeforeValidException.php';
include_once 'libs/php-jwt/src/ExpiredException.php';
include_once 'libs/php-jwt/src/SignatureInvalidException.php';
include_once 'libs/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

function route($method, $urlData, $formData) {

    require 'database.php';
    include_once 'clientJWT.php';


    // GET /reviews/of-product/{product_id}
    if ($method === 'GET' && count($urlData) === 2) {

      $product_id = $urlData[1];

      $sql = "SELECT * FROM `review` WHERE product_id=".$product_id;
      if($result = mysqli_query($con,$sql)) {
        for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {

          $sqlClient = "SELECT first_name FROM `client` WHERE id=".$row['client_id'];
          if($resClient = mysqli_query($con,$sqlClient)){
            $client = mysqli_fetch_assoc($resClient);
            $reviews[$i]['client']['id'] = $row['client_id'];
            $reviews[$i]['client']['first_name'] = $client['first_name'];

            $reviews[$i]['product_id'] = $row['product_id'];
            $reviews[$i]['text'] = $row['text'];
            $reviews[$i]['rating'] = $row['rating'];
            $reviews[$i]['modified'] = $row['modified'];
          }
        }
        http_response_code(200);
        echo json_encode($reviews);
      }
      else {
        http_response_code(404);
        echo json_encode("Not found");
      }
      return;
    }

    //POST /reviews
    if ($method === 'POST') {
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

          $newReview = json_decode($formData);
          // Sanitize.
          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $product_id = mysqli_real_escape_string($con, trim( $newReview->data->product_id));
          $text = mysqli_real_escape_string($con, trim( $newReview->data->text));
          $rating = mysqli_real_escape_string($con, trim( $newReview->data->rating));
          //store order
          $sqlReview = "INSERT INTO `review`(`product_id`,`client_id`, `text`, `rating`) VALUES ('{$product_id}','{$client_id}','{$text}', '{$rating}')";

          if(mysqli_query($con,$sqlReview)){

            http_response_code(201);
            echo json_encode("Success");
          }
          else{
            http_response_code(422);
            echo "SQL error";
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

    //PUT /reviews
    if($method === 'PUT') {
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

          $newReview = json_decode($formData);
          // Sanitize.
          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));
          $product_id = mysqli_real_escape_string($con, trim( $newReview->data->product_id));
          $text = mysqli_real_escape_string($con, trim( $newReview->data->text));
          $rating = mysqli_real_escape_string($con, trim( $newReview->data->rating));
          //store order
          $sqlReview = "UPDATE `review` SET text='{$text}', rating='{$rating}' WHERE product_id='{$product_id}' AND client_id='{$client_id}'";

          if(mysqli_query($con,$sqlReview)){

            http_response_code(201);
            echo json_encode("Success");
          }
          else{
            http_response_code(422);
            echo "SQL error";
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

    //DELETE /reviews/{product_id}
    if($method === 'DELETE' && count($urlData) === 1){
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
          $product_id = $urlData[0];
          // Sanitize.
          $client_id = mysqli_real_escape_string($con, trim( $decoded->data->id));

          //store order
          $sqlReview = "DELETE FROM `review` WHERE product_id='{$product_id}' AND client_id='{$client_id}'";

          if(mysqli_query($con,$sqlReview)){

            http_response_code(201);
            echo json_encode("Success");
          }
          else {
            http_response_code(422);
            echo "SQL error";
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
