<?php

function route($method, $urlData, $formData) {

    require 'database.php';

    // POST /orders
    if ($method === 'POST') {
        $newOrder = json_decode($formData);
    // Sanitize.
        $client_name = mysqli_real_escape_string($con, trim( $newOrder->data->client_name));
        $client_phone = mysqli_real_escape_string($con, trim( $newOrder->data->client_phone));
        $client_address = mysqli_real_escape_string($con, trim( $newOrder->data->client_address));
    //store order
        $sqlOrder = "INSERT INTO `client_order`(`id`,`client_name`,`client_phone`,  `client_address`) VALUES (null,'{$client_name}','{$client_phone}', '{$client_address}')";

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
        else
        {
            http_response_code(422);
            echo "error";
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
