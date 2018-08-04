<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:10
 */

namespace EasySwoole\Http;


use EasySwoole\Component\Invoker;
use EasySwoole\Http\Message\Status;

class WebService
{
    private $dispatcher;
    final function __construct($controllerNameSpace = 'App\\HttpController\\',$depth = 5,$maxPoolNum = 100)
    {
        $this->dispatcher = new Dispatcher($controllerNameSpace,$depth,$maxPoolNum);
    }

    function setExceptionHandler(callable $handler)
    {
        $this->dispatcher->setExceptionHandler($handler);
    }

    function onRequest(Request $request_psr,Response $response_psr):void
    {
        $this->dispatcher->dispatch($request_psr,$response_psr);
        $response_psr->response();
    }
}