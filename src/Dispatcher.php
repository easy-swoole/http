<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午2:56
 */

namespace EasySwoole\Http;


use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Exceptions\ControllerError;
use EasySwoole\Http\Exceptions\ControllerPoolEmpty;
use EasySwoole\Http\Message\Status;
use EasySwoole\Trigger\Trigger;
use Swoole\Coroutine as Co;

class Dispatcher
{
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
    }

    function setExceptionHandler(callable $handler):void
    {
        $this->exceptionHandler = $handler;
    }

    public function dispatch(Request $request,Response $response):void
    {
        $this->controllerHandler($request,$response);
    }

    private function controllerHandler(Request $request,Response $response)
    {
        $pathInfo = ltrim(UrlParser::pathInfo($request->getUri()->getPath()),"/");
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
            $obj->objectRestore();
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