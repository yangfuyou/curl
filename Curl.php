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
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
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

    public function buildUrl($url, Array $data) {
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
    public function head($url, $params = []) {
        // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
        $this->setHeaders([["Content-type:"=>"application/x-www-form-urlencoded"]]);
        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_NOBODY, true);
        return $this->_exec($this->buildUrl($url, $params));
    }


}


$obj = new Curl();
echo '<pre>';
// $ret = $obj->get('http://www.baidu.com/');
// print_r($ret);

$ret = $obj->head('http://www.baidu.com/');
print_r($ret);




