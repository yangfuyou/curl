<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$curl = new Curl();

$ret = $curl->download('https://www.amazingjokes.com/img/2014/530c9613d29bd_CountvonCount.jpg','bb.jpg');
// var_dump($ret);   

// $ret = $curl->setMaxFilesize(11024)->download('https://www.amazingjokes.com/img/2014/530c9613d29bd_CountvonCount.jpg','b.jpg');
// var_dump($ret); 