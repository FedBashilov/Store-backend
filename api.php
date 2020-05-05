<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$method = $_SERVER['REQUEST_METHOD'];

$formData = getFormData($method);


function getFormData($method) {

    if ($method === 'GET')
      return $_GET;
    if ($method === 'POST'){
      $postdata = file_get_contents("php://input");
      return $postdata;
    }
    if($method === 'PUT') {
      $putdata = file_get_contents('php://input');
      return $putdata;
    }
    if($method === 'DELETE'){
      return;
    }
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(array(
      'error' => 'Bad Request'
    ));
}

// separate url

$url = explode('?', $_SERVER['REQUEST_URI'], 2);

$url = trim($url[0], '/');

$urls = explode('/', $url);

$router = $urls[1];

$urlData = array_slice($urls, 2);

// include router and start main fuction
include_once 'routers/' . $router . '.php';
route($method, $urlData, $formData);

?>
