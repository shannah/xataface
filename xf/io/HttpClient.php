<?php
namespace xf\io;

/**
 * Class providing useful http client methods
 */
class HttpClient {
    
    private static function status_line($response_headers) {
        $status_line = null;
        foreach ($response_headers as $line) {
            if (preg_match('{HTTP\/\S*\s(\d{3})}', $line)) {
                $status_line = $line;
            }
        }
        return $status_line;
    }
    
    /**
     * Performs an HTTP GET request
     * @param string $url The URL to request
     * @param array $headers The request headers
     * @return \StdObject with properties status, code, data, and headers
     */
    public static function get($url, $headers=array()) {
        if (strpos($url, 'http://') !== 0 and strpos($url, 'https://') !== 0) {
            throw new \Exception("Only http:// and https:// URLs are supported but found ".$url);
        }
        // use key 'http' even if you send the request to https://...
        $headerStr = '';
        
        foreach ($headers as $k=>$v) {
            $headerStr .= $k.': '.$v."\r\n";
        }
        $options = array(
            'http' => array(
                'header'  => $headerStr,
                'method'  => 'GET',
                'ignore_errors' => true,
                'follow_location' => true
            )
        );
        
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if (!@$http_response_header) {
            throw new \Exception("There was a problem with the request.  No response header received");
        }
        $status_line = self::status_line($http_response_header);
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $out = new \StdClass;
        $out->status = intval($match[1]);
        $out->code = $out->status;
        $out->data = $result;
        $out->headers = $http_response_header;
        
        foreach ($http_response_header as $h) {
            if (preg_match('/^Etag:(.*)$/i', $h, $match)) {
                $out->etag = trim($match[0]);
            }
        }
        return $out;
    }
    
    public static function post($url, $headers=[], $data=[]) {
        if (strpos($url, 'http://') !== 0 and strpos($url, 'https://') !== 0) {
            throw new \Exception("Only http:// and https:// URLs are supported but found ".$url);
        }
        // use key 'http' even if you send the request to https://...
        $headerStr = '';
        if (!array_key_exists('content-type', array_change_key_case($headers))) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
            
        foreach ($headers as $k=>$v) {
            $headerStr .= $k.': '.$v."\r\n";
        }
        $options = array(
            'http' => array(
                'header'  => $headerStr,
                'method'  => 'GET',
                'ignore_errors' => true,
                'follow_location' => true,
                'content' => http_build_query($data)
            )
        );
        
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if (!@$http_response_header) {
            throw new \Exception("There was a problem with the request.  No response header received");
        }
        $status_line = self::status_line($http_response_header);
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $out = new \StdClass;
        $out->status = intval($match[1]);
        $out->code = $out->status;
        $out->data = $result;
        $out->headers = $http_response_header;
        
        foreach ($http_response_header as $h) {
            if (preg_match('/^Etag:(.*)$/i', $h, $match)) {
                $out->etag = trim($match[0]);
            }
        }
        return $out;
    }
}

function df_http_parse_headers($headers) {
    $head = array();
    foreach( $headers as $k=>$v ) {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) ) {
            $head[ trim($t[0]) ] = trim( $t[1] );
        } else {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) ) {
                $head['response_code'] = intval($out[1]);
            }
        }
    }
    //print_r($head);
    return $head;
}

$df_http_last_response_headers = null;
$df_http_last_response_code;

function df_http_response_code() {
    global $df_http_last_response_headers, $df_http_last_response_code;
    if (is_int($df_http_last_response_code)) {
        return $df_http_last_response_code;
    } else {
        if (isset($df_http_last_response_headers)) {
            $parsed = df_http_parse_headers($df_http_last_response_headers);
            $df_http_last_response_code = $parsed['response_code'];
            return $df_http_last_response_code;
        }
        return 0;
    }
}

function df_http_response_headers() {
    global $df_http_last_response_headers;
    return $df_http_last_response_headers;
}

function df_http_post($url, $data=array(), $json=true) {
    global $df_http_last_response_headers, $dt_http_last_response_code;
    $df_http_last_response_headers = null;
    $df_http_last_response_code = null;
    if (isset($data['HTTP_HEADERS'])) {
        $headers = $data['HTTP_HEADERS'];
        unset($data['HTTP_HEADERS']);
    } else {
        $headers = '';
    }
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'ignore_errors' => true,
            'header'  => $headers."Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        throw new Exception("HTTP request failed");
    }
    $df_http_last_response_headers = $http_response_header;
    
    //print_r($http_response_header);
    //print_r($result);
    if ($json) {
        return json_decode($result, true);
    }
    return $result;
}

function df_http_get($url, $headers = null, $json = true) {
    global $df_http_last_response_headers, $dt_http_last_response_code;
    $df_http_last_response_headers = null;
    $df_http_last_response_code = null;
    if (is_array($headers)) {
        $headers = implode("\r\n", $headers) . "\r\n";
    }
    $options = array(
        'http' => array(
            'ignore_errors' => true,
            'header' => $headers,
            'method' => 'GET',
        )
    );
    if (isset($headers)) {
        $options['http']['header'] = $headers;
    }
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        throw new Exception("HTTP request failed");
    }
    $df_http_last_response_headers = $http_response_header;
    
    if ($json) {
        return json_decode($result, true);
    }
    return $result;
}