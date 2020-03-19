<?php


namespace EasySwoole\Http;


use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplContextArray;

class GlobalParamHook
{
    use Singleton;

    private $onRequest = [];
    private $afterRequest = [];

    function addOnRequest(callable $call)
    {
        $this->onRequest[] = $call;
    }

    function addAfterRequest(callable $call)
    {
        $this->afterRequest[] = $call;
    }

    function onRequest(Request $request,Response $response)
    {
        foreach ($this->onRequest as $call){
            call_user_func($call,$request,$response);
        }
    }

    function afterRequest(Request $request,Response $response)
    {
        foreach ($this->afterRequest as $call){
            call_user_func($call,$request,$response);
        }
    }

    function hookDefault()
    {
        $this->addOnRequest(function (Request $request){
            global $_GET;
            if(!$_GET instanceof SplContextArray){
                $_GET = new SplContextArray();
            }
            $_GET->loadArray($request->getQueryParams());


            global $_COOKIE;
            if(!$_COOKIE instanceof SplContextArray){
                $_COOKIE = new SplContextArray();
            }
            $_COOKIE->loadArray($request->getCookieParams());

            global $_POST;
            if(!$_POST instanceof SplContextArray){
                $_POST = new SplContextArray();
            }
            $_POST->loadArray($request->getParsedBody());

        });
    }
}