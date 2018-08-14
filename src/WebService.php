<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:10
 */

namespace EasySwoole\Http;


use EasySwoole\Trace\Trigger;

class WebService
{
    private $dispatcher;
    final function __construct($controllerNameSpace = 'App\\HttpController\\',Trigger $trigger,$depth = 5,$maxPoolNum = 100)
    {
        $this->dispatcher = new Dispatcher($controllerNameSpace,$trigger,$depth,$maxPoolNum);
    }

    function setExceptionHandler(callable $handler)
    {
        $this->dispatcher->setHttpExceptionHandler($handler);
    }

    function onRequest(Request $request_psr,Response $response_psr):void
    {
        $this->dispatcher->dispatch($request_psr,$response_psr);
        $response_psr->response();
    }
}