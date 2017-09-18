<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

$url = "http://localhost/git/curl/examples/server/demo.php";
$curl = new Curl($url);

$data = ['hello'=>'world','like'=>['a','b']];
// if (class_exists('\CURLFile', false)) {
//     $this->setOption(CURLOPT_SAFE_UPLOAD, true);
//     $data = ['pic' => new \CURLFile(realpath($file), $type, $name)];
// } else {
    // $data['pic']='@'.realpath('../resource/me.jpg').";type=image/jpeg;filename=m.jpg";
// }

$ret = $curl->delete($data,['id'=>1]);
print_r($ret);
