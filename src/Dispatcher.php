<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午2:56
 */

namespace EasySwoole\Http;


use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Exceptions\ControllerError;
use EasySwoole\Http\Exceptions\ControllerPoolEmpty;
use EasySwoole\Http\Message\Status;
use EasySwoole\Trigger\Trigger;
use Swoole\Coroutine as Co;
use FastRoute\Dispatcher\GroupCountBased;

class Dispatcher
{
    private $router = null;
    private $routerRegister = null;
    private $controllerNameSpacePrefix;
    private $maxDepth;
    private $maxPoolNum;
    private $exceptionHandler = null;
    /*
     * 这部分的进程对象池，单独实现
     */
    private $controllerPool = [];
    private $controllerCreateNum = [];
    private $waitList = null;

    function __construct($controllerNameSpace,$maxDepth = 5,$maxPoolNum = 20)
    {
        $this->controllerNameSpacePrefix = trim($controllerNameSpace,'\\');
        $this->maxDepth = $maxDepth;
        $this->maxPoolNum = $maxPoolNum;
        $this->waitList = [];
        $class = $this->controllerNameSpacePrefix.'\\Router';
        try{
            if(class_exists($class)){
                $ref = new \ReflectionClass($class);
                if($ref->isSubclassOf(AbstractRouter::class)){
                    $this->routerRegister =  $ref->newInstance();
                    $this->router = new GroupCountBased($this->routerRegister->getRouteCollector()->getData());
                }else{
                    Trigger::error("class : {$class} not AbstractRouter class");
                }
            }
        }catch (\Throwable $throwable){
            Trigger::throwable($throwable);
        }
    }

    function setExceptionHandler(callable $handler):void
    {
        $this->exceptionHandler = $handler;
    }

    public function dispatch(Request $request,Response $response):void
    {
        $path = UrlParser::pathInfo($request->getUri()->getPath());
        if($this->router instanceof GroupCountBased){
            $handler = null;
            $routeInfo = $this->router->dispatch($request->getMethod(),$path);
            if($routeInfo !== false){
                switch ($routeInfo[0]) {
                    case \FastRoute\Dispatcher::NOT_FOUND:{
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                    }
                    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:{
                        $handler = $this->routerRegister->getMethodNotAllowCallBack();
                        break;
                    }
                    case \FastRoute\Dispatcher::FOUND:{
                        $func = $routeInfo[1];
                        $vars = $routeInfo[2];
                        if(is_callable($func)){
                            try{
                                call_user_func_array($func,array_merge([$request,$response],array_values($vars)));
                            }catch (\Throwable $throwable){
                                Trigger::throwable($throwable);
                            }
                        }else if(is_string($func)){
                            $data = $request->getQueryParams();
                            $request->withQueryParams($vars+$data);
                            $pathInfo = UrlParser::pathInfo($func);
                            $request->getUri()->withPath($pathInfo);
                        }
                        break;
                    }
                    default:{
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                    }
                }
            }
            /*
             * 全局模式的时候，都拦截。非全局模式，否则继续往下
             */
            if($this->routerRegister->isGlobalMode()){
                if(is_callable($handler)){
                    try{
                        call_user_func($handler,$request,$response);
                    }catch (\Throwable $throwable){
                        Trigger::throwable($throwable);
                    }
                }
                return;
            }
        }
        if(!$response->isEndResponse()){
            $this->controllerHandler($request,$response,$path);
        };
    }

    private function controllerHandler(Request $request,Response $response,string $path)
    {
        $pathInfo = ltrim($path,"/");
        $list = explode("/",$pathInfo);
        $actionName = null;
        $finalClass = null;
        $controlMaxDepth = $this->maxDepth;
        $currentDepth = count($list);
        $maxDepth = $currentDepth < $controlMaxDepth ? $currentDepth : $controlMaxDepth;
        while ($maxDepth >= 0){
            $className = '';
            for ($i=0 ;$i<$maxDepth;$i++){
                $className = $className."\\".ucfirst($list[$i] ?: 'Index');//为一级控制器Index服务
            }
            if(class_exists($this->controllerNameSpacePrefix.$className)){
                //尝试获取该class后的actionName
                $actionName = empty($list[$i]) ? 'index' : $list[$i];
                $finalClass = $this->controllerNameSpacePrefix.$className;
                break;
            }else{
                //尝试搜搜index控制器
                $temp = $className."\\Index";
                if(class_exists($this->controllerNameSpacePrefix.$temp)){
                    $finalClass = $this->controllerNameSpacePrefix.$temp;
                    //尝试获取该class后的actionName
                    $actionName = empty($list[$i]) ? 'index' : $list[$i];
                    break;
                }
            }
            $maxDepth--;
        }
        if(!empty($finalClass)){
            try{
                $c = $this->getController($finalClass);
            }catch (\Throwable $throwable){
                $this->hookThrowable($throwable,$request,$response);
                return;
            }
            if($c instanceof Controller){
                try{
                    $c->__hook($actionName,$request,$response);
                }catch (\Throwable $throwable){
                    $this->hookThrowable($throwable,$request,$response);
                }finally {
                    $this->recycleController($finalClass,$c,$request,$response);
                }
            }else{
                $throwable = new ControllerPoolEmpty('controller pool empty for '.$finalClass);
                $this->hookThrowable($throwable,$request,$response);
            }
        }else{
            if(in_array($request->getUri()->getPath(),['/','/index.html'])){
                $content = file_get_contents(__DIR__.'/Static/welcome.html');
            }else{
                $response->withStatus(Status::CODE_NOT_FOUND);
                $content = file_get_contents(__DIR__.'/Static/404.html');
            }
            $response->write($content);
        }
    }

    /**
     * @param string $class
     * @return mixed
     * @throws ControllerError
     */
    protected function getController(string $class)
    {
        $classKey = $this->generateClassKey($class);
        if(!isset($this->controllerPool[$classKey])){
            $this->controllerPool[$classKey] = new \SplQueue();
            $this->controllerCreateNum[$classKey] = 0;
            $this->waitList[$classKey] = [];
        }
        $pool = $this->controllerPool[$classKey];
        //懒惰创建模式
        /** @var \SplQueue $pool */
        if($pool->isEmpty()){
            $createNum = $this->controllerCreateNum[$classKey];
            if($createNum < $this->maxPoolNum){
                $this->controllerCreateNum[$classKey] = $createNum+1;
                try{
                    //防止用户在控制器结构函数做了什么东西导致异常
                    return new $class();
                }catch (\Throwable $exception){
                    $this->controllerCreateNum[$classKey] = $createNum;
                    //直接抛给上层
                    throw new ControllerError($exception->getMessage());
                }
            }
            $cid = Co::getUid();
            array_push($this->waitList[$classKey],$cid);
            Co::suspend($cid);//挂起携程。等待恢复
            /*
             * 携程恢复后，需要再次判断。因为recycleController用户可能抛出异常
             */
            if(!$pool->isEmpty()){
                return $pool->dequeue();
            }else{
                return null;
            }
        }
        return $pool->dequeue();
    }

    protected function recycleController(string $class,Controller $obj,Request $request,Response $response)
    {
        $classKey = $this->generateClassKey($class);
        try{
            $obj->gc();
            ($this->controllerPool[$classKey])->enqueue($obj);
        }catch(\Throwable $throwable)
        {
            $this->hookThrowable($throwable,$request,$response);
        }finally{
            //无论如何，恢复一个就近的协程等待，防止全部用户卡死。
            if(!empty($this->waitList[$classKey])){
                Co::resume(array_shift($this->waitList[$classKey]));
            }
        }
    }

    protected function hookThrowable(\Throwable $throwable,Request $request,Response $response)
    {
        if(is_callable($this->exceptionHandler)){
            //预防用户自己错误处理出错
            try{
                call_user_func($this->exceptionHandler,$throwable,$request,$response);
            }catch (\Throwable $throwable){
                Trigger::throwable($throwable);
            }
        }else{
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);
            $response->write(nl2br($throwable->getMessage()."\n".$throwable->getTraceAsString()));
            Trigger::throwable($throwable);
        }
    }

    protected function generateClassKey(string $class):string
    {
        return substr(md5($class), 8, 16);
    }
}