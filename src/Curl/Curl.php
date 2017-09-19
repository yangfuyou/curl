<?php
/**
 *  @file       Curl.php
 *  @author     laipiyang <462166282@qq.com>
 *  @since      2017-09-06 16:53:51
 *  @update
 *  @description    
 */

namespace Curl;

class Curl
{
    private $_params = [
        'base_url'=>'',
        'url'=>'',
        'headers'=>'',
        'cookies'=>[]
    ];

    private $_options = [];

    private $_config = [
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HEADER         => false,
        CURLINFO_HEADER_OUT    => true,#notice order
        CURLOPT_RETURNTRANSFER => true,#CURLOPT_FILE depends on CURLOPT_RETURNTRANSFER, "true" return the output as a string, Without this the output would be directly echoed to the screen or STDOUT.
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_IPRESOLVE      => 1 
    ];

    private $_curl;

    private $_error;

    private $_err = [];

    private $_chunked_from = 0;
    private $_chunked_size = 4096;

    private $_request = [];

    private $_response = [];

    private $_header_callback_data = [];

    private $_writer_callback_data = '';

    private $_reader_callback_data = '';

    private $_raw;

    private $_json_pattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';
    
    public function __construct($url = '') {
        $url && $this->setBaseUrl($url);
    }

    #################################   set   #################################

    public function setBaseUrl($url) {
        $this->_params['base_url'] = $this->buildUrl($url, []);
        return $this;
    }
    
    public function setUrl($url, Array $data = []) {
        $this->setBaseUrl($url);
        !empty($data) && $this->_params['url'] = $this->buildUrl($url, $data);
        return $this;
    }

    public function buildUrl($url, Array $data) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('It is not a url. Input was : '.$url);  
        }
        $return = null;
        if (empty($data)) {
            $return = $url;
        } else {
            $parsed = parse_url($url);
            isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = [] ;
            $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
            $parsed['query'] = ($params) ? '?'.http_build_query($params) : '';
            $return = $parsed['scheme'].'://'.$parsed['host'].(isset($parsed['port']) ? ':'.$parsed['port'] : '');
            $return .= isset($parsed['path']) ? $parsed['path'] : '/';
            $return .= $parsed['query'].(isset($parsed['fragment']) ? "#".$parsed['fragment'] : '');        
        }
        return $return;
    }

    public function buildPostData($data) {
        if ($this->shouldBeJson()) {
            if ($data == null) {
                return '{}';
            } elseif (is_string($data)) {
                if(json_decode($data, true)===false) {
                    return '{}';
                }
                return $data;
            } elseif (is_array($data) || is_object($data)) {
                return json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        } else {
            if (is_array($data) || is_object($data)) {
                $data = http_build_query($data);
            }
        }
        return $data;
    }
    #10002
    public function makeUrl() {
        $this->setOption(CURLOPT_URL, $this->_params['url'] | $this->_params['base_url']);
        return $this;
    }

    public function setOption($option, $value) {
        $this->_options[$option] = $value;
        return $this;
    }

    public function unsetOption($option) {
        unset($this->_options[$option]);
        return $this;
    }

    public function setOptions(Array $options) {
        $this->_options = $options+$this->_options;
        return $this;
    }
    #3
    public function setPort($port) {
        $this->setOption(CURLOPT_PORT, intval($port));
        return $this;
    }
    #10018
    public function setUserAgent($user_agent) {
        $this->setOption(CURLOPT_USERAGENT, $user_agent);
        return $this;
    }
    #13
    public function setTimeout($time) {
        $this->setOption(CURLOPT_TIMEOUT, intval($time));
        return $this;
    }
    #2
    public function setHeaderOut($flag = false) {
        $this->setOption(CURLINFO_HEADER_OUT, $flag);
        return $this;   
    }
    #64,81
    public function setSsl($enabled = false) {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $enabled)->setOption(CURLOPT_SSL_VERIFYHOST, $enabled);
        return $this;
    }
    #10086,10088,10025,10087
    public function setCA($cert_path, $key_path, $cert_type = 'PEM', $key_type = 'PEM') {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, true)->setOption(CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        $this->setOption(CURLOPT_SSLCERTTYPE, $cert_type)->setOption(CURLOPT_SSLKEYTYPE, $key_type);
        $this->setOption(CURLOPT_SSLCERT, $cert_path)->setOption(CURLOPT_SSLKEY, $key_path);
        return $this;
    }
    #61,10004,59
    public function setProxy($url, $port = 80) {
        $this->setOption(CURLOPT_HTTPPROXYTUNNEL, TRUE);
        $this->setOption(CURLOPT_PROXY, $url)->setOption(CURLOPT_PROXYPORT, $port);
        return $this;
    }
    #10006
    public function setProxyLogin($username = '', $password = '') {
        $this->setOption(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }
    #78
    public function setConnectTimeout($seconds) {
        $this->setOption(CURLOPT_CONNECTTIMEOUT, intval($seconds));
        return $this;
    }
    #10016
    public function setReferrer($referrer) {
        $this->setOption(CURLOPT_REFERER, $referrer);
        return $this;
    }

    #10031 from which the cookie data should be read to send in the next request
    public function setCookieFile($cookie_file){
        $this->setOption(CURLOPT_COOKIEFILE, $cookie_file);
        return $this;
    }
    #10082 specifies the file where the cookie data should be saved. same as CURLOPT_COOKIEFILE
    public function setCookieJar($cookie_jar) {
        $this->setOption(CURLOPT_COOKIEJAR, $cookie_jar);
        return $this;
    }
    #10022
    public function setCookies($cookies = []) {
        count($cookies) && $this->_params['cookies'] = ($cookies + $this->_params['cookies']);
        $this->setOption(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
            return urlencode(strval($k)) . '=' . urlencode($v);
        }, array_keys($this->_params['cookies']), array_values($this->_params['cookies']))));
        return $this;
    }
    #107,10005
    public function setAuth($username = '', $password = '', $method = CURLAUTH_BASIC) {
        $this->setOption(CURLOPT_HTTPAUTH, $method != CURLAUTH_BASIC ? $method : CURLAUTH_BASIC)->setOption(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }
    #20056,43
    public function setMaxFilesize($bytes) {
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $callback = function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($bytes) {
                return $downloaded > $bytes ? 1 : 0;
            };
        } else {
            $callback = function ($download_size, $downloaded, $upload_size, $uploaded) use ($bytes) {
                return $downloaded > $bytes ? 1 : 0;
            };
        }
        $this->setOption(CURLOPT_PROGRESSFUNCTION, $callback)->setOption(CURLOPT_NOPROGRESS, false);
        return $this;
    }

    public function setHeader($key, $value) {
        $this->_params['headers'][$key] = $value;
        return $this;
    }

    public function unsetHeader($key) {
        unset($this->_params['headers'][$key]);
        return $this;
    }

    public function removeHeader($key) {
        if($key && isset($this->_params['headers'][$key])) {
            $this->setHeader($key, '');  
        }
        return $this;
    }

    public function setJson() {
        $this->setHeader("Content-type","application/json");
        return $this;
    }

    public function setXml() {
        $this->setHeader("Content-type","text/xml");
        return $this;
    }
    
    public function setHeaders(Array $headers) {
        foreach($headers AS $key => $value) {
            $this->_params['headers'][$key] = $value;
        }       
        return $this;
    }
    #10023
    public function makeHeaders() {
        if(!isset($this->_params['headers']) || !is_array($this->_params['headers'])) {
            return $this;
        }
        $headers = [];
        foreach ($this->_params['headers'] as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOption(CURLOPT_HTTPHEADER, $headers);
        return $this; 
    }

    public function setChunkFrom($from = 0) {
        $this->_chunked_from = intval($from);
        return $this;
    }

    public function setChunkSize($size = 4096) {
        $this->_chunked_size = intval($size);
        return $this;
    }

    private function setOpts() {
        $k = array_merge(array_keys($this->_config),array_keys($this->_options));
        $v = array_merge(array_values($this->_config),array_values($this->_options));
        $this->_options = array_combine($k, $v);
        curl_setopt_array( $this->_curl, $this->_options);
        return $this;
    }

    #20079 CURLOPT_HEADERFUNCTION is for handling header lines received *in the response*
    private function setHeaderCallback() {
        $data = new \stdClass();
        $data->headers = '';
        $data->cookies = [];
        $this->_header_callback_data = $data;
        $this->setOption(CURLOPT_HEADERFUNCTION, $this->createHeaderCallback($data)); 
        return $this;       
    }

    private function createHeaderCallback($data) {
        return function ($ch, $header) use ($data) {
            if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
                $data->cookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
            }
            $data->headers .= $header;
            return strlen($header);
        };
    }
    #20011 CURLOPT_WRITEFUNCTION is for handling data received *from the response*
    private function setWriterCallback() {
        $data = new \stdClass();
        $data->content = '';
        $this->_writer_callback_data = $data;
        $this->setOption(CURLOPT_WRITEFUNCTION, $this->createWriterCallback($data)); 
        return $this;         
    }
    #todo 
    private function createWriterCallback($data) {
        return function ($ch, $str) use ($data) {
            $data->content .= $str;  
            return strlen($str);
        };
    }
    #20012 CURLOPT_READFUNCTION is for handling data passed along *in the request*.
    private function setReaderCallback() {
        $this->setOption(CURLOPT_READFUNCTION, $this->createReaderCallback()); 
        return $this;        
    }
    #todo
    private function createReaderCallback() {
        return function ($ch, $fd, $length) {
            return fread($fd, $length);
        };
    }

    private function setMethod($method = 'GET') {
        $method = strtoupper($method);
        switch($method) {
            case 'POST':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method)->setOption(CURLOPT_POST, true);
                break;
            case 'PUT':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
                break;
            case 'TRACE':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method)->setHeaderOut(true)->setOption(CURLOPT_VERBOSE, true);
                break;
            case 'DELETE':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
                break;
            case 'OPTIONS':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method)->removeHeader('Content-Length');
                break;
            case 'HEAD':
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method)->setOption(CURLOPT_NOBODY, true)->setHeader("Content-type","application/x-www-form-urlencoded");
                break;
            case 'SEND':
                $this->setOption(CURLOPT_UPLOAD, true)->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                break; 
            case 'DOWNLOAD':
            case 'GET':
            default:
                #10036,80
                $this->setOption(CURLOPT_CUSTOMREQUEST, 'GET')->setOption(CURLOPT_HTTPGET, true);
        }
        return $this;
    }

    public function setHttp($v=1.1) {
        $this->setOption(CURLOPT_HTTP_VERSION, $v==1.1 ? CURL_HTTP_VERSION_1_1  : CURL_HTTP_VERSION_1_0);
        return $this;
    }

    #################################   parse   #################################

    private function handle() {
        if ($this->getOption(CURLINFO_HEADER_OUT)) {
            $this->_request['headers'] = $this->parseRequestHeaders(curl_getinfo($this->_curl, CURLINFO_HEADER_OUT));
        }

        $this->_response['cookies'] = $this->_header_callback_data->cookies;
        $this->_header_callback_data->cookies = null;
        $this->_response['headers'] = $this->parseResponseHeaders($this->_header_callback_data->headers);
        $this->_header_callback_data->headers = null;

        if($this->_writer_callback_data) {
            $this->_response['body'] = $this->parseResponse($this->_response['headers'], $this->_writer_callback_data->content);
            $this->_writer_callback_data->content = null;
        } else {
            $this->_response['body'] = '';
        }

        $this->_err['curl']['code'] = curl_errno($this->_curl);
        $this->_err['curl']['msg'] = curl_error($this->_curl);
        $this->_err['curl']['ext'] = curl_strerror($this->_err['curl']['code']);

        $this->_err['http']['code'] = curl_getinfo($this->_curl,CURLINFO_HTTP_CODE);

        $this->_error = (!($this->_err['curl']['code'] === 0)) || in_array(floor($this->_err['http']['code'] / 100), array(4, 5));

        if (isset($this->_response['headers']['Status-Line'])) {
            $this->_err['http']['msg'] = $this->_response['headers']['Status-Line'];
        }

        return $this;
    }

    private function parseResponseHeaders($raw_response_headers) {
        $response_header = '';
        $response_header_array = explode("\r\n\r\n", $raw_response_headers);
        foreach ($response_header_array as $key => $value) {
            if (stripos($value, 'HTTP/') === 0) {
                $response_header = $value;
                break;
            }
        }
        $response_headers = [];
        list($first_line, $headers) = $this->parseHeaders($response_header);
        $response_headers['Status-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $response_headers[$key] = $value;
        }
        return $response_headers;
    }

    private function parseHeaders($raw_headers) {
        $raw_headers = preg_split('/\r\n/', $raw_headers, null, PREG_SPLIT_NO_EMPTY);
        if($raw_headers===false || empty($raw_headers)) {
            return ['',[]];
        }
        $http_headers = [];
        foreach ($raw_headers as $k=>$val) {
            if($k===0) {
                continue;
            }
            list($key, $value) = explode(':', $val, 2);
            $key = trim($key);
            $value = trim($value);
            if (isset($http_headers[$key])) {
                $http_headers[$key] .= ',' . $value;
            } else {
                $http_headers[$key] = $value;
            }
        }
        return array($raw_headers['0'], $http_headers);
    }

    private function parseResponse($response_headers, $response) {
        if (isset($response_headers['Content-Type'])) {
            if (preg_match($this->_json_pattern, $response_headers['Content-Type'])) {
                $response = json_decode($response, true);
            } elseif (preg_match('#^(?:text/|application/(?:atom\+|rss\+)?)xml#i', $response_headers['Content-Type'])) {
                $response = @simplexml_load_string($response);
            }
        }
        return $response;
    }

    private function parseRequestHeaders($raw_headers) {
        $request_headers = [];
        list($first_line, $headers) = $this->parseHeaders($raw_headers);
        $request_headers['Request-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $request_headers[$key] = $value;
        }
        return $request_headers;
    }

    #################################   get   #################################

    public function __get($name) {
        $return = null;
        $funcStr = preg_replace_callback('/_([a-z])/', function($matched){
            return strtoupper($matched[1]);
        }, ucfirst($name));
        if (is_callable(array($this, $getter = '__get' . $funcStr))) {
            $return = $this->$getter();
        } else {
            if (isset($this->_params[$name])) {
                $return = $this->_params[$name];
            }

        }
        return $return;
    }

    public function getOption($option) {
        if (is_string($option) && !is_int($option)) {
            $option = constant('CURLOPT_'.strtoupper($option));
        }
        return isset($this->_options[$option]) ? $this->_options[$option] : null ;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function response() {
        switch (strtoupper($this->getOption(CURLOPT_CUSTOMREQUEST))) {
            case 'TRACE':
                return [$this->_request['headers'],$this->_response['headers']];
                break;
            case 'HEAD':
                return $this->_response['headers'];
                break;
            case 'GET':
                return $this->_response['body']; 
                break;
            default:
                return $this->_response['body'];
        }
    }

    public function getUrl() {
        $url = curl_getinfo($this->_curl, CURLINFO_EFFECTIVE_URL);
        return curl_unescape($this->_curl, $url);
    }

    public function getTotalTime()
    {
        return curl_getinfo($this->_curl, CURLINFO_TOTAL_TIME);
    }

    public function getError() {
        return $this->_err;
    }

    public function getCookie($key) {
        return isset($this->_response['cookies'][$key]) ? $this->_response['cookies'][$key] : null;
    }

    public function getHeader($key) {
        return isset($this->_response['headers'][$key]) ? $this->_response['headers'][$key] : null;
    }

    public function shouldBeJson() {
        if (isset($this->_params['headers']['Content-Type'])) {
            if(preg_match($this->_json_pattern, $this->_params['headers']['Content-Type'])) {
                return true;
            }
        }
        return false;
    }

    #################################   opr   #################################

    private function exec() {
        try{
            $this->_curl = curl_init();        
            $this->makeUrl()->makeHeaders()->setHeaderCallback()->setOpts();

            $content = curl_exec($this->_curl);
            curl_reset($this->_curl);
            if ($content) {
                $this->_raw = $content;
            }

            return $this;
        }catch(\Exception $e){
            throw new \Exception('Curl not installed');
        }
    }

    // RFC7231
    public function get($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        return $this->setUrl($url, $params)->setMethod('get')->setWriterCallback()->exec()->handle()->response();
    }

    // RFC7231
    public function head($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        return $this->setUrl($url, $params)->setMethod('head')->exec()->handle()->response();
    }

    // RFC7231
    public function options($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        return $this->setUrl($url, $params)->setMethod('options')->setWriterCallback()->exec()->handle()->response();
    }

    // RFC7231
    public function trace($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        return $this->setUrl($url, $params)->setMethod('trace')->setWriterCallback()->exec()->handle()->response();
    }

    // RFC7231
    public function post($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        $this->setUrl($url, [])->setMethod('post')->setOption(CURLOPT_POSTFIELDS, $this->buildPostData($params));
        return $this->setWriterCallback()->exec()->handle()->response();
    }

    // RFC7231
    public function put($url = '', Array $params = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }
        $this->setUrl($url, [])->setMethod('put')->setOption(CURLOPT_POSTFIELDS, $put_data = $this->buildPostData($params));
        return $this->setHeader('Content-Length', strlen($put_data))->setWriterCallback()->exec()->handle()->response();
    }

    // RFC7231
    public function delete($url = '', Array $params = [], Array $data = []) {
        if ( func_num_args() == 0 ) {
            $url = $this->_params['base_url'];
        } else {
            if (is_array($url)) {
                $data = $params;
                $params = $url;
                $url = $this->_params['base_url'];
            }
        }

        $this->setUrl($url, $params)->setMethod('delete')->setOption(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->setWriterCallback()->exec()->handle()->response();
    }

    //upload
    public function upload($url, $file, $type, $name) {
        if (class_exists('\CURLFile', false)) {
            $this->setOption(CURLOPT_SAFE_UPLOAD, true);
            $data = ['pic' => new \CURLFile(realpath($file), $type, $name)];
        } else {
            $data['pic']='@'.realpath($file).";type=".$type.";filename=".$name;
        }
        $this->setUrl($url, [])->setMethod('post')->setOption(CURLOPT_POSTFIELDS, $data)->setWriterCallback()->exec();
        return $this->handle()->response();
    }

    //send chunked fseek
    public function send($url, $file, Array $params = []) {
        $this->setUrl($url, $params)->setHeader('Transfer-Encoding', 'chunked')->setMethod('send');
        $this->setOption(CURLOPT_INFILE, $this->readFile($file))->setOption(CURLOPT_INFILESIZE, filesize($file));
        $this->setReaderCallback()->setWriterCallback()->exec()->handle();
        return !$this->_error;
    }

    private function readFile($file) {
        $fp = fopen(realpath($file), 'rb');
        fseek($fp, $this->_chunked_from);
        return $fp;
    }

    //download
    public function download($url, $file) {
        $this->setUrl($url, [])->setOption(CURLOPT_FILE, fopen($file, "a"))->setMethod('download')->exec()->handle();
        return !$this->_error;
    }

    //downloading
    public function downloading($url, $file) {
        if (file_exists($file)) {
            $this->setChunkFrom(filesize($file))->setOption(CURLOPT_RANGE, $this->_chunked_from.'-'.($this->_chunked_from+$this->_chunked_size));
        } else {
            $this->setOption(CURLOPT_RANGE, '0-'.$this->_chunked_size);
        }
        $this->download($url, $file);
        $size = $this->getHeader('Content-Range');
        if($size) {
            $size = preg_split('#[-/]#',$size);
            return $size[2] <= $size[1]+1;
        }
        return !$this->_err['curl']['code'];
    }
 
}

?>