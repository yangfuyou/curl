<?php
function file_get_contents_chunked($filename, $chunk_size, $callback) {
    $handle = fopen($filename, 'r');
    while (!feof($handle)) {
        call_user_func_array($callback, array(fread($handle, $chunk_size)));
    }
    fclose($handle);
}

// $tmpnam = tempnam('.', 'chunk.');
$tmpnam = time().'.'.$_GET['type'];
$file = fopen($tmpnam, 'ab+');

file_get_contents_chunked('php://input', 1024, function ($chunk) use (&$file) {
    fwrite($file, $chunk);
});    

$data['method'] = $_SERVER['REQUEST_METHOD'];
$data['get'] = $_GET;
$data['post'] = $_POST;
parse_str(file_get_contents('php://input'),$data['input']);
$data['files'] = $tmpnam;

$str =  json_encode($data, JSON_UNESCAPED_UNICODE);
echo $str;


?>