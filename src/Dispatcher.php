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
use Swoole\Coroutine as co;

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
    private $controllerPoolInfo = [];
    private $waitList = null;

    function __construct($controllerNameSpace,$maxDepth = 5,$maxPoolNum = 20)
    {
        $this->controllerNameSpacePrefix = trim($controllerNameSpace,'\\');
        $this->maxDepth = $maxDepth;
        $this->maxPoolNum = $maxPoolNum;
        $this->waitList = new \SplQueue();
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
            $c = $this->getController($finalClass);
            if($c instanceof Controller){
                try{
                    $c->__hook($actionName,$request,$response);
                }catch (\Throwable $throwable){
                    if(is_callable($this->exceptionHandler)){
                        call_user_func($this->exceptionHandler,$throwable,$request,$response);
                    }else{
                        Trigger::throwable($throwable);
                    }
                }finally {
                    $this->recycleController($finalClass,$c);
                }
            }else{
                //直接抛给上层调用
                throw new ControllerPoolEmpty('controller pool empty for '.$finalClass);
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

    protected function getController(string $class)
    {
        if(!isset($this->controllerPool[$class])){
            $this->controllerPool[$class] = new \SplQueue();
        }
        $pool = $this->controllerPool[$class];
        //懒惰创建模式
        if($pool->isEmpty()){
            if(!isset($this->controllerPoolInfo[$class])){
                $this->controllerPoolInfo[$class] = 0;
            }
            $createNum = $this->controllerPoolInfo[$class];
            if($createNum < $this->maxPoolNum){
                $this->controllerPoolInfo[$class] = $createNum+1;
                try{
                    //防止用户在控制器结构函数做了什么东西导致异常
                    return new $class();
                }catch (\Throwable $exception){
                    $this->controllerPoolInfo[$class] = $createNum;
                    throw new ControllerError();
                }
            }
            $cid = co::getUid();
            $this->waitList->enqueue($cid);
            co::suspend();
            return $pool->dequeue();
        }
        return $pool->dequeue();
    }

    protected function recycleController(string $class,Controller $obj)
    {
        $obj->objectRestore();
        ($this->controllerPool[$class])->enqueue($obj);
        if(!$this->waitList->isEmpty()){
            co::resume($this->waitList->dequeue());
        }
    }
}