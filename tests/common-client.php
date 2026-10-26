<?php

error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__);
require ($argv[1] ?? $root . '/vendor/autoload.php');
spl_autoload_register(static function ($class) use ($root) {
    if (str_starts_with($class, 'TencentCloud\\Common\\')) {
        $file = $root . '/src/' . str_replace('\\', '/', $class) . '.php';
        if (is_file($file)) require $file;
    }
}, true, true);

$profile = new TencentCloud\Common\Profile\ClientProfile();
$profile->getHttpProfile()->setReqTimeout(10);
$profile->getHttpProfile()->setKeepAlive(true);
$client = new TencentCloud\Common\CommonClient('sts', '2018-08-13',
    new TencentCloud\Common\Credential('fixture-id', 'fixture-key'), 'ap-shanghai', $profile);
$history = [];
$stack = GuzzleHttp\HandlerStack::create(new GuzzleHttp\Handler\MockHandler([
    new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"Response":{"RequestId":"fixture-request"}}'),
]));
$stack->push(GuzzleHttp\Middleware::history($history));
$connection = (new ReflectionProperty(TencentCloud\Common\AbstractClient::class, 'httpConn'))->getValue($client);
(new ReflectionProperty(TencentCloud\Common\Http\HttpConnection::class, 'client'))->setValue($connection,
    new GuzzleHttp\Client(['handler' => $stack, 'base_uri' => 'https://sts.tencentcloudapi.com']));
$result = $client->callJson('GetFederationToken', ['Name' => 'fixture', 'DurationSeconds' => 3600, 'Policy' => '{}']);
$count = 0;
$check = static function ($actual, $expected, $name) use (&$count) {
    ++$count;
    if ($actual !== $expected) throw new RuntimeException($name);
};
$request = $history[0]['request'];
$body = json_decode((string)$request->getBody(), true, 512, JSON_THROW_ON_ERROR);
$check($result['RequestId'], 'fixture-request', 'common-client response');
$check($request->getHeaderLine('Content-Type'), 'application/json', 'JSON content type');
$check($request->getHeaderLine('X-TC-Action'), 'GetFederationToken', 'action');
$check($request->getHeaderLine('X-TC-Version'), '2018-08-13', 'version');
$check($request->getHeaderLine('X-TC-Region'), 'ap-shanghai', 'region');
$check(ctype_digit($request->getHeaderLine('X-TC-Timestamp')), true, 'timestamp string');
$check(abs(time() - (int)$request->getHeaderLine('X-TC-Timestamp')) < 5, true, 'timestamp unchanged');
$check(str_starts_with($request->getHeaderLine('Authorization'), 'TC3-HMAC-SHA256 Credential=fixture-id/'), true, 'TC3 signing');
$check($body['DurationSeconds'], 3600, 'JSON object sent exactly once');
$check($body['Policy'], '{}', 'policy string preserved');
$check($history[0]['options']['timeout'], 10, 'request timeout');
$check(error_reporting(), E_ALL, 'E_ALL');
echo json_encode(['passed' => true, 'assertions' => $count, 'php' => PHP_VERSION]), PHP_EOL;
