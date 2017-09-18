<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

require __DIR__ . '/../../autoload.php';

use \Curl\Curl;

if($_FILES) {
    // if (class_exists('\CURLFile', false)) {
//     $this->setOption(CURLOPT_SAFE_UPLOAD, true);
    // $data = ['pic' => new \CURLFile(realpath('../resource/me.jpg'), 'image/jpeg', 'm.jpg')];
// } else {
    // $data['pic']='@'.realpath('../resource/me.jpg').";type=image/jpeg;filename=m.jpg";
// }


    $url = "http://localhost/git/curl/examples/server/demo.php";

    $tmpname = $_FILES['fname']['name'];
    $tmpfile = $_FILES['fname']['tmp_name'];
    $tmpType = $_FILES['fname']['type'];
    $tmp_file = __DIR__.'/'.$tmpname;
    move_uploaded_file($tmpfile, $tmp_file);
// echo $tmp_file;
    $obj = new Curl();
    $ret = $obj->upload($url, $tmp_file, $tmpType, $tmpname);
    unlink($tmp_file);
    print_r($ret);
    exit;    
}

?>
<form method="POST" action="" enctype="multipart/form-data">
<p>Select a file to upload : </p>
<input type="file" name="fname">
<input type="submit" name="check_submit"/>
</form>
