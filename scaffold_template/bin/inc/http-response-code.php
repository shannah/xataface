<?php
if (!@$argv) {
	die("CLI only");
}
function fetch(string $method, string $url, string $body, array $headers = []) {
    $context = stream_context_create([
        "http" => [
            // http://docs.php.net/manual/en/context.http.php
            "method"        => $method,
            "header"        => implode("\r\n", $headers),
            "content"       => $body,
            "ignore_errors" => true,
        ],
    ]);

    $response = file_get_contents($url, false, $context);

    /**
     * @var array $http_response_header materializes out of thin air
     */

    $status_line = $http_response_header[0];

    preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);

    $status = $match[1];

    return $status;
}
echo @fetch('GET', $argv[1], '');