<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$url = "http://www.baidu.com/";
$curl = new Curl($url);

$ret = $curl->head();
print_r($ret);
