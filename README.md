# EasySwoole HTTP 服务组件

一个轻量级的HTTP Dispatch组件

```
require 'vendor/autoload.php';

$http = new swoole_http_server("0.0.0.0", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

//默认注册的控制器搜索路径是 App\HttpController\
$service = new \EasySwoole\Http\WebService();

$http->on("request", function ($request, $response)use($service) {
    $service->onRequest(new \EasySwoole\Http\Request($request),new \EasySwoole\Http\Response($response));
});

$http->start();
```