<?php
$data['method'] = $_SERVER['REQUEST_METHOD'];
$data['get'] = $_GET;
$data['post'] = $_POST;
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>