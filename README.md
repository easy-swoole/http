# EasySwoole HTTP 服务组件

一个轻量级的HTTP Dispatch组件
## Server Script
```php
namespace App\HttpController;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Dispatcher;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use Swoole\Http\Server;

require_once 'vendor/autoload.php';


class Index extends Controller
{

    function index()
    {
        // TODO: Implement index() method
        $this->response()->write('hello world');
        $this->response()->setCookie('a','a',time()+3600);
    }
}


$dispatcher = new Dispatcher();
$dispatcher->setNamespacePrefix('App\HttpController');
$http = new Server("127.0.0.1", 9501);

$http->on("request", function ($request, $response) use($dispatcher){
    $request_psr = new Request($request);
    $response_psr = new Response($response);
    $dispatcher->dispatch($request_psr, $response_psr);
    $response_psr->__response();
});

$http->start();
```

## 动态路由匹配规则

![Dispatcher](./resource/router.jpg)