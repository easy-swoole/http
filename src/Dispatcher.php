<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午2:56
 */

namespace EasySwoole\Http;


use EasySwoole\Http\Message\Status;

class Dispatcher
{

    private $controllerNameSpacePrefix;
    private $maxDepth = 5;

    function __construct($controllerNameSpace,$maxDepth = 5)
    {
        $this->controllerNameSpacePrefix = trim($controllerNameSpace,'\\');
        $this->maxDepth = $maxDepth;
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
            (new $finalClass($actionName,$request,$response));
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
}