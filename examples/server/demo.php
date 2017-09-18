<?php
$data['method'] = $_SERVER['REQUEST_METHOD'];
$data['get'] = $_GET;
$data['post'] = $_POST;
parse_str(file_get_contents('php://input'),$data['input']);
$data['files'] = $_FILES;
switch($data['method']) {
    case 'GET':
        $data['data'] = '取列表或详情';
        break;
    case 'POST':
        $data['data'] = '添加';
        break;
    case 'PUT':
        $data['data'] = '更新全部信息';
        break;
    case 'PATCH':
        $data['data'] = '更新部分信息';
        break;
    case 'DELETE':
        $data['data'] = '删除';
        break;
    case 'OPTIONS': 
        $data['data'] = '哪些属性可以编辑';
        break; 
    default:  
}
$str =  json_encode($data, JSON_UNESCAPED_UNICODE);
echo $str;


?>