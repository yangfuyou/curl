<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$curl = new Curl();

$url = "http://localhost/git/curl/examples/server/demo1.php";

// $ret = $curl->send($url, '../resource/me.jpg',['type'=>'jpg']);
$ret = $curl->setMaxFilesize(1*1024*1024)->send($url, '../../a.rar',['type'=>'rar']);
var_dump($ret);
?>

