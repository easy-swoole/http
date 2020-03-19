<?php


namespace EasySwoole\Http;


use EasySwoole\Component\Singleton;
use EasySwoole\Session\Session;
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
        global $_GET;
        if(!$_GET instanceof SplContextArray){
            $_GET = new SplContextArray();
        }
        global $_COOKIE;
        if(!$_COOKIE instanceof SplContextArray){
            $_COOKIE = new SplContextArray();
        }
        global $_POST;
        if(!$_POST instanceof SplContextArray){
            $_POST = new SplContextArray();
        }
        $this->addOnRequest(function (Request $request){
            global $_GET;
            $_GET->loadArray($request->getQueryParams());
            global $_COOKIE;
            $_COOKIE->loadArray($request->getCookieParams());
            global $_POST;
            $_POST->loadArray($request->getParsedBody());

        });
        return $this;
    }

    function hookSession(\SessionHandlerInterface $handler,$sessionName = 'easy_swoole_sess',string $savePath = '/')
    {
        global $_SESSION;
        $_SESSION = null;
        $_SESSION = Session::getInstance($handler,$sessionName,$savePath)->getContextArray();
        $_SESSION->setOnContextCreate(function (SplContextArray $contextArray){
            $contextArray->loadArray(Session::getInstance()->all());
        });
        Session::getInstance()->setOnStart(function (){
            global $_SESSION;
            $_SESSION->loadArray(Session::getInstance()->all());
        });
        $this->addOnRequest(function (Request $request,Response $response)use($sessionName){
            $cookie = $request->getCookieParams($sessionName);
            if(empty($cookie)){
                $sid = Session::getInstance()->sessionId();
                $response->setCookie($sessionName,$sid);
            }else{
                Session::getInstance()->sessionId($cookie);
            }
        });
    }
}