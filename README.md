# EasySwoole HTTP 服务组件

一个轻量级的HTTP Dispatch组件
## Server Script
```
require 'vendor/autoload.php';
$trigger = new \EasySwoole\Trace\Trigger();

$http = new swoole_http_server("0.0.0.0", 9501);
$http->set([
    'worker_num'=>1
]);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

//默认注册的控制器搜索路径是 App\HttpController\
$service = new \EasySwoole\Http\WebService($controllerNameSpace = 'App\\HttpController\\',$trigger,$depth = 5);
$service->setExceptionHandler(function (\Throwable $throwable,\EasySwoole\Http\Request $request,\EasySwoole\Http\Response $response){
    $response->write('error');
});

$http->on("request", function ($request, $response)use($service) {
    $req = new \EasySwoole\Http\Request($request);
    $service->onRequest($req,new \EasySwoole\Http\Response($response));
});
//
$http->start();
```

## Controller Folder
```
namespace App\HttpController;


use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{

    function index()
    {
        // TODO: Implement index() method
        $this->response()->write('hello world');
    }

    function actionNotFound($action): void
    {
        $this->response()->write("{$action} not found");
    }

    function testSession()
    {
        $this->session()->start();
        $this->session()->set('a',1);
        $this->session()->writeClose();
    }

    function testSession2()
    {
        $this->session()->start();
        $this->response()->write($this->session()->get('a'));
    }

    function testException()
    {
        new NoneClass();
    }

    protected function onException(\Throwable $throwable): void
    {
        $this->response()->write($throwable->getMessage());
    }

    public function gc()
    {
        var_dump('class :'.static::class.' is recycle to pool');
    }
}
```

