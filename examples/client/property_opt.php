<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

// $curl = new Curl();
// var_dump($curl->base_url);

#err
// $curl = new Curl('www.renren.com');


$curl = new Curl('http://www.baidu.com/');
// echo $curl->base_url,'<br />';

$curl->setBaseUrl('http://localhost/git/curl/demo.php');
// echo $curl->base_url,'<br />';

$curl->setUrl('https://localhost',['user_name'=>'zhangsan']);
echo $curl->url,'<br />';

$curl->setUrl('http://localhost/git/curl/demo.php');

$curl->setUrl('http://localhost/git/curl/demo.php#abc',['user_name'=>'zhangsan']);
echo $curl->url,'<br />';
var_dump($curl->getOption('URl'));

$curl->setPort(123);
$curl->setUserAgent('222');
$curl->setTimeout(3);
$curl->setHeaderOut(true);
$curl->setSsl(true);
$curl->setCA('/apiclient_cert.pem','/apiclient_key.pem');
$curl->setProxy(33)->setProxyLogin('a','b')->setConnectTimeout(4)->setReferrer(5);
$curl->setCookieFile(1)->setCookieJar(2);
$curl->setCookies($_COOKIE)->setAuth('a','b')->setMaxFilesize(5);
$curl->setHeaders(["Content-type:"=>"application/json"])->makeHeaders();

var_dump($curl->getOptions());
?>