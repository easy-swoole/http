<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午2:56
 */

namespace EasySwoole\Http;


use EasySwoole\Component\Context\ContextManager;
use EasySwoole\Component\Context\Exception\ModifyError;
use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Exception\Exception;
use EasySwoole\Http\Exception\RouterError;
use EasySwoole\Http\Message\Status;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\Dispatcher as RouterDispatcher;
use FastRoute\RouteCollector;

class Dispatcher
{
    private bool|null|GroupCountBased $router = null;
    /**
     * @var AbstractRouter|null
     */
    private ?AbstractRouter $routerRegister = null;
    //以下为外部配置项目
    private string $namespacePrefix;
    private int $maxDepth;
    /** @var null|callable */
    private $httpExceptionHandler = null;
    /** @var callable */
    private $onRouterCreate;

    private bool $enableFakeRouter = false;

    function __construct(string $namespacePrefix = null,int $maxDepth = 5)
    {
        if($namespacePrefix !== null){
            $this->namespacePrefix = trim($namespacePrefix,'\\');
        }
        $this->maxDepth = $maxDepth;
    }

    function enableFakeRouter():Dispatcher
    {
        $this->enableFakeRouter = true;
        return $this;
    }

    function setNamespacePrefix(string $space):Dispatcher
    {
        $this->namespacePrefix = trim($space,'\\');
        return $this;
    }


    function setOnRouterCreate(callable $call):Dispatcher
    {
        $this->onRouterCreate = $call;
        return $this;
    }

    function setHttpExceptionHandler(callable $handler):Dispatcher
    {
        $this->httpExceptionHandler = $handler;
        return $this;
    }

    function setMaxDepth($depth):Dispatcher
    {
        $this->maxDepth = $depth;
        return $this;
    }

    /**
     * @throws RouterError|ModifyError
     */
    public function dispatch(Request $request, Response $response):void
    {
        // 进行一次初始化判定
        if($this->router === null){
            $this->initRouter($this->enableFakeRouter);
        }

        $path = UrlParser::pathInfo($request->getUri()->getPath());
        if($this->router instanceof GroupCountBased){
            if($this->routerRegister->isPathInfoMode()){
                $routerPath = $path;
            }else{
                $routerPath = $request->getUri()->getPath();
            }
            $handler = null;
            $routeInfo = $this->router->dispatch($request->getMethod(),$routerPath);
            if($routeInfo !== false){
                switch ($routeInfo[0]) {
                    case RouterDispatcher::FOUND:{
                        $handler = $routeInfo[1];
                        $inject = $this->routerRegister->parseParams();
                        //合并解析出来的数据
                        switch ($inject){
                            case AbstractRouter::PARSE_PARAMS_IN_GET:{
                                $vars = $routeInfo[2];
                                $data = $request->getQueryParams();
                                $request->withQueryParams($vars+$data);
                                break;
                            }
                            case AbstractRouter::PARSE_PARAMS_IN_POST:{
                                $vars = $routeInfo[2];
                                $data = $request->getParsedBody();
                                $request->withParsedBody($vars + $data);
                                break;
                            }
                            case AbstractRouter::PARSE_PARAMS_IN_CONTEXT:{
                                $vars = $routeInfo[2];
                                ContextManager::getInstance()->set(AbstractRouter::PARSE_PARAMS_CONTEXT_KEY,$vars);
                                break;
                            }
                            case AbstractRouter::PARSE_PARAMS_NONE:
                            default:{
                                break;
                            }
                        }
                        break;
                    }
                    case RouterDispatcher::METHOD_NOT_ALLOWED:{
                        $handler = $this->routerRegister->getMethodNotAllowCallBack();
                        break;
                    }
                    case RouterDispatcher::NOT_FOUND:
                    default:{
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                    }
                }
                //如果handler不为null，那么说明，非为 \FastRoute\Dispatcher::FOUND ，因此执行
                if(is_callable($handler)){
                    try{
                        $ret = call_user_func($handler,$request,$response);
                        if(is_string($ret)){
                            $path = UrlParser::pathInfo($ret);
                            $request->getUri()->withPath($path);
                            goto execController;
                        }else if($ret === false){
                            return;
                        }
                    }catch (\Throwable $throwable){
                        $this->onException($throwable,$request,$response);
                        //出现异常的时候，不在往下dispatch
                        return;
                    }
                }else if(is_string($handler)){
                    $path = UrlParser::pathInfo($handler);
                    $request->getUri()->withPath($path);
                    goto execController;
                }
            }
            //全局模式的时候，都拦截。非全局模式，否则继续往下
            if($this->routerRegister->isGlobalMode()){
                return;
            }
        }
        execController:{
            $this->controllerExecutor($request,$response,$path);
        }

    }

    protected function controllerExecutor(Request $request, Response $response, string $path)
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
                //处理例如  /api//user/ 等中间多了 路径分隔符的问题
                if(!empty($list[$i])){
                    $className = $className."\\".ucfirst($list[$i] ?: 'Index');//为一级控制器Index服务
                }
            }
            if(class_exists($this->namespacePrefix.$className)){
                //尝试获取该class后的actionName
                $actionName = empty($list[$i]) ? 'index' : $list[$i];
                $finalClass = $this->namespacePrefix.$className;
                break;
            }else{
                //尝试搜搜index控制器
                $temp = $className."\\Index";
                if(class_exists($this->namespacePrefix.$temp)){
                    $finalClass = $this->namespacePrefix.$temp;
                    //尝试获取该class后的actionName
                    $actionName = empty($list[$i]) ? 'index' : $list[$i];
                    break;
                }
            }
            $maxDepth--;
        }

        if(!empty($finalClass)){
            try{
                $controllerObject = new $finalClass($request,$response,$actionName);
            }catch (\Throwable $throwable){
                $this->onException($throwable,$request,$response);
                return;
            }
            if($controllerObject instanceof Controller){
                try{
                    $forward = $controllerObject->__hook();
                    if(is_string($forward) && (strlen($forward) > 0) && ($forward != $path)){
                        $forward = UrlParser::pathInfo($forward);
                        $request->getUri()->withPath($forward);
                        $this->dispatch($request,$response);
                    }
                }catch (\Throwable $throwable){
                    $this->onException($throwable,$request,$response);
                }
            }else{
                $throwable = new Exception('no controller object '.$finalClass);
                $this->onException($throwable,$request,$response);
            }
        }else{
            $response->withStatus(404);
            $response->write("not controller class match");
        }
    }


    protected function onException(\Throwable $throwable, Request $request, Response $response)
    {
        if(is_callable($this->httpExceptionHandler)){
            call_user_func($this->httpExceptionHandler,$throwable,$request,$response);
        }else{
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);
            $response->write(nl2br($throwable->getMessage()."\n".$throwable->getTraceAsString()));
        }
    }

    function initRouter(bool $autoCreate = false):void
    {
        $r = null;
        $class = $this->namespacePrefix.'\\Router';
        if(class_exists($class)){
            $ref = new \ReflectionClass($class);
            if($ref->isSubclassOf(AbstractRouter::class)){
                $r = $ref->newInstance();
            }else{
                throw new RouterError("class : {$class} not AbstractRouter class");
            }
        }else{
            if($autoCreate){
                $r = new class extends AbstractRouter{
                    function initialize(RouteCollector $routeCollector)
                    {

                    }
                };
            }
        }

        if($r instanceof AbstractRouter){
            $this->routerRegister = $r;
            if (is_callable($this->onRouterCreate)) {
                call_user_func($this->onRouterCreate,$r);
            }
            $data = $r->getRouteCollector()->getData();
            if(!empty($data)){
                $this->router = new GroupCountBased($data);
            }else{
                $this->router = false;
            }
        }else{
            $this->router = false;
        }

    }
}