<?php


namespace EasySwoole\Http;


use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplContextArray;

class GlobalParamHook
{
    use Singleton;

    private $onRequest = [];
    private $afterRequest = [];
    private $cookieExpire = 0;
    private $cookiePath = '/';
    private $cookieDomain = '';
    private $cookieSecure = false;
    private $cookieHttponly = false;
    private $cookieSamesite = '';
    
    function setCookieExpire(int $expire){
        $this->cookieExpire = $expire;
    }
    
    function setCookiePath(string $path){
        $this->cookiePath = $path;
    }
    
    function setCookieDomain(string $domain){
        $this->cookieDomain = $domain;
    }
    
    function setCookieSecure(bool $secure){
        $this->cookieSecure = $secure;
    }
    
    function setCookieHttponly(bool $httponly){
        $this->cookieHttponly = $httponly;
    }
    
    function setCookieSamesite(string $cookieSamesite){
        $this->cookieSamesite = $cookieSamesite;
    }
    
    function setOnRequest(callable $call)
    {
        $this->onRequest[] = $call;
    }

    function setAfterRequest(callable $call)
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

    function hook()
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
        global $_FILES;
        if(!$_FILES instanceof SplContextArray){
            $_FILES = new SplContextArray();
        }
        global $_SERVER;
        if(!$_SERVER instanceof SplContextArray){
            $_SERVER = new SplContextArray();
        }
        $this->setOnRequest(function (Request $request){
            global $_GET;
            /** @var $_GET SplContextArray */
            $_GET->loadArray($request->getQueryParams());
            global $_COOKIE;
            /** @var $_COOKIE SplContextArray */
            $_COOKIE->loadArray($request->getCookieParams());
            global $_POST;
            /** @var $_POST SplContextArray */
            $_POST->loadArray($request->getParsedBody());
            global $_FILES;
            $files = [];
            if(!empty($request->getSwooleRequest()->files)){
                $files = $request->getSwooleRequest()->files;
            }
            /** @var $_FILES SplContextArray */
            $_FILES->loadArray($files);
            global $_SERVER;
            $server = [];
            foreach ($request->getSwooleRequest()->header as $key => $value) {
                $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
            }
            foreach ($request->getSwooleRequest()->server as $key => $value) {
                $server[strtoupper(str_replace('-', '_', $key))] = $value;
            }
            /** @var $_SERVER SplContextArray */
            $_SERVER->loadArray($server);
        });
        return $this;
    }
}
