<?php
$data['method'] = $_SERVER['REQUEST_METHOD'];
$data['get'] = $_GET;
$data['post'] = $_POST;
$data['input'] = file_get_contents('php://input');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>