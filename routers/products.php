<?php


function route($method, $urlData, $formData) {
    
    require 'database.php';

    if ($method === 'GET') {

    // GET /products
        if (count($urlData) === 0) {
            $allProductsId = [];
            $sql = "SELECT id FROM product";

            if($result = mysqli_query($con,$sql)) {
                for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
                    $allProductsId[$i] = $row['id'];
                }
                echo json_encode($allProductsId);
            }
            else {
                http_response_code(404);
            }
            return;
        }
        
    // GET /products/{id}
        if (count($urlData) === 1) {
            $id = $urlData[0];
            $sql = "SELECT id, name, description, price, photo FROM product WHERE id = '$id'";

            if($result = mysqli_query($con,$sql)) {
                
                $row = mysqli_fetch_assoc($result);
                
                $product['id'] = $row['id'];
                $product['name'] = $row['name'];
                $product['description'] = $row['description'];
                $product['price'] = $row['price'];
                $product['photo'] = $row['photo'];
                
                echo json_encode($product);
            }
            else {
                http_response_code(404);
            }
            return;
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