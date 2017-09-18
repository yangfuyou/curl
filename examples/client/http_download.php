<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$curl = new Curl();
// $ret = $curl->download('http://localhost/git/curl/examples/resource/me.jpg', 'b.jpg');
$ret = $curl->setMaxFilesize(10*1024*1024)->download('http://localhost/git/curl/a.rar', 'b.rar');
var_dump($ret);   

