<?php
/**
 *  @file       Curl.php
 *  @author     laipiyang <462166282@qq.com>
 *  @since      2017-09-06 16:53:51
 *  @update
 *  @description    
 */

class Curl
{
    private $_ch;
    private $_options;
    private $_config = [
        CURLOPT_HEADER         => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_ENCODING       => '',
        CURLOPT_IPRESOLVE      => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0'
    ];

    private $_error_code;
    private $_error_string;
    private $_info;
    private $_queue;

    public function __construct() {
        try{
            $this->_ch = curl_init(); 
            $options = is_array($this->_options) ? ($this->_options + $this->_config) : $this->_config;
            $this->setOptions($options); 
        }catch(Exception $e){
            throw new Exception('Curl not installed');
        }
    }

    public function setOption($option, $value) {
        curl_setopt($this->_ch, $option, $value);
        return $this;
    }

    public function setOptions(Array $options) {
        curl_setopt_array( $this->_ch, $options);
        return $this;
    }

    public function setTimeOut($time) {
        $this->setOption(CURLOPT_TIMEOUT, intval($time));
        return $this;
    }

    public function setProxy($url, $port = 80) {
        $this->setOption(CURLOPT_HTTPPROXYTUNNEL, TRUE);
        $this->setOption(CURLOPT_PROXY, $url . ':' . $port);
        return $this;
    }

    public function setProxyLogin($username = '', $password = '') {
        $this->setOption(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }

    public function setAuth($username = '', $password = '', $method = CURLAUTH_BASIC) {
        $this->setOption(CURLOPT_HTTPAUTH, $method != CURLAUTH_BASIC ? $method : CURLAUTH_BASIC);
        $this->setOption(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    public function setReferrer($referrer)
    {
        $this->setOption(CURLOPT_REFERER, $referrer);
        return $this;
    }

    private function setCookies($cookies = [])
    {
        $cookies = count($cookies) ? ($cookies + $_COOKIE): $_COOKIE;
        $this->setOption(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
            return urlencode(strval($k)) . '=' . urlencode($v);
        }, array_keys($cookies), array_values($cookies))));
        return $this;
    }

    public function setHeaders(Array $header) {
        if(array_keys($header) !== range(0, count($header) - 1)){
            $out = [];
            foreach($header as $k => $v){
                $out[] = $k .': '.$v;
            }
            $header = $out;
        }
        $this->setOption(CURLOPT_HTTPHEADER, $header);
        return $this;
    }

    public function setHttps($enabled = false) {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $enabled)->setOption(CURLOPT_SSL_VERIFYHOST, $enabled);
        return $this;
    }

    private function buildUrl($url, Array $data) {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = [] ;
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?'.http_build_query($params) : '';
        if(isset($parsed['port'])){
            $parsed['host'] .= ':'.$parsed['port'];
        }
        if(!isset($parsed['path'])) {
            $parsed['path']='/';
        }
        return $parsed['scheme'].'://'.$parsed['host'].$parsed['path'].$parsed['query'];
    }

    private function _exec($url) {    
        $this->setOption(CURLOPT_URL, $url);
        $s_time = microtime(true);
        $ret = curl_exec($this->_ch);
        $e_time = microtime(true);
        $use_time = $e_time-$s_time;
        //记录请求超长的请求
        if($use_time>5){
            $this->_queue[] = array(
                'URL'=>$url,
                'S_TIME'=>$s_time,
                'E_TIME'=>$e_time,
                'USE_TIME'=>$use_time
            );
        }

        if($ret === false){
            $this->_error_code = curl_errno($this->_ch);
            $this->_error_string = curl_error($this->_ch);
        }else{
            $this->_info = curl_getinfo($this->_ch);
            return $ret;
        }
        return false;   
    }

    public function getErrno()
    {
        return $this->_error_code;
    }

    public function getError()
    {
        return $this->_error_string;
    }

    public function getInfo()
    {
        return $this->_info;
    }

    public function getQueue() {
        return $this->_queue;
    }

    public function closeCurl() {
        curl_close($this->_ch);
    }    

    // RFC7231
    public function get($url, $params = []) {
        $this->setOption(CURLOPT_HTTPGET, true);
        return $this->_exec($this->buildUrl($url, $params));
    }

    // RFC7231
    public function head($url) {
        // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
        $this->setHeaders([["Content-type:"=>"application/x-www-form-urlencoded"]]);
        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_NOBODY, true);
        return $this->_exec($this->buildUrl($url, []));
    }

    // RFC7231
    public function post($url, $params = []) {
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_HEADER, false);
        // If value is an array, the Content-Type header will be set to multipart/form-data. 
        if(is_array($params)){
            $paramsString = http_build_query($params);
        }else if(is_string($params)){
            $paramsString = $params;
        }
        $this->setOption(CURLOPT_POSTFIELDS, $paramsString);
        return $this->_exec($url);
    }

    // RFC7231
    public function put($url, $data, $params = []) {
        // write to memory/temp
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);
        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_INFILE, $f);
        $this->setOption(CURLOPT_INFILESIZE, strlen($data));
        return $this->_exec($this->buildUrl($url, $params));
    }

    // RFC7231
    public function delete($url, $params = []) {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->_exec($this->buildUrl($url, $params));
    }

    // RFC7231
    public function trace($url, $params = []) {
        $f = fopen('php://temp', 'w+');
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'TRACE');
        $this->setOption(CURLOPT_VERBOSE, true);
        $this->setOption(CURLOPT_STDERR, $f); 
        $this->_exec($this->buildUrl($url, $params));
        rewind($f);
        return stream_get_contents($f);
    }

    // RFC7231
    public function options($url, $params = []) {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        return $this->_exec($this->buildUrl($url, $params));
    }

    // RFC5789
    public function patch($url, $params = []) {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
        return $this->_exec($this->buildUrl($url, $params));
    }

    //upload
    public function upload($url, $file, $type, $name) {
        #$file must be the full path
        if (class_exists('CURLFile', false)) {
            $this->setOption(CURLOPT_SAFE_UPLOAD, true);
            $data = ['pic' => new CURLFile(realpath($file), $type, $name)];
        } else {
            $data['pic']='@'.realpath($file).";type=".$type.";filename=".$name;
        }
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        return $this->_exec($url);
    }

}
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf8');

// $obj = new Curl();
// echo '<pre>';
// $ret = $obj->get('http://localhost/git/curl/demo.php',['username'=>'abc']);
// print_r($ret);

// $ret = $obj->head('http://localhost/git/curl/demo.php');
// print_r($ret);

// $ret = $obj->post('http://localhost/git/curl/demo.php',['username'=>'abc']);
// if (substr($ret,0,1)!='{') {//utf8-bom
//     $ret = substr($ret,3);
// }
// print_r(json_decode($ret,true));

// $ret = $obj->put('http://localhost/git/curl/demo.php?id=3',file_get_contents('https://www.baidu.com'),['username'=>'abc']);
// if (substr($ret,0,1)!='{') {//utf8-bom
//     $ret = substr($ret,3);
// }
// print_r(json_decode($ret,true));

// $ret = $obj->delete('http://localhost/git/curl/demo.php',['username'=>'abc']);
// print_r($ret);

// $ret = $obj->trace('http://localhost/git/curl/demo.php',['username'=>'abc']);
// print_r($ret);

if($_FILES) {
    $tmpname = $_FILES['fname']['name'];
    $tmpfile = $_FILES['fname']['tmp_name'];
    $tmpType = $_FILES['fname']['type'];
    $tmp_file = __DIR__.'/'.$tmpname;
    move_uploaded_file($tmpfile, $tmp_file);

    $obj = new Curl();
    $ret = $obj->upload('http://localhost/git/curl/demo.php', $tmp_file, $tmpType, $tmpname);
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

