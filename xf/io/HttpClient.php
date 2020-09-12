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
}