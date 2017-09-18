<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$url = "http://localhost/git/curl/examples/server/demo.php";
$curl = new Curl($url);

$ret = $curl->trace();
print_r($ret);
