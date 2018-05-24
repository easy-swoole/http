# EasySwoole HTTP 服务组件

一个轻量级的HTTP Dispatch组件

```
$http = new swoole_http_server("0.0.0.0", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$service = new \EasySwoole\Http\WebService();

$http->on("request", function ($request, $response)use($service) {
   $service->onRequest($request,$response);
});

$http->start();
```