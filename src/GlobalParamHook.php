<?php


namespace EasySwoole\Http;


use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplContextArray;

class GlobalParamHook
{
    use Singleton;

    private $handler_list = [];

    function hook(string $name,callable $onRequest,?callable $afterRequest = null)
    {
        $this->handler_list[$name] = [
            $onRequest,
            $afterRequest
        ];
    }

    function onRequest(Request $request,Response $response)
    {
        foreach ($this->handler_list as $name => $value){
            if(!$$name instanceof SplContextArray){
                $$name = new SplContextArray();
            }
            $res = call_user_func($value[0],$$name,$request,$response);
            if(is_array($res)){
                $$name->loadArray($res);
            }
        }
    }

    function afterRequest(Request $request,Response $response)
    {
        foreach ($this->handler_list as $name => $value){
            if(!$$name instanceof SplContextArray){
                $$name = new SplContextArray();
            }
            call_user_func($value[1],$$name,$request,$response);
        }
    }

    function hookDefault()
    {
        $this->hookCookie();
        $this->hookGet();
        $this->hookPost();
    }

    public function hookCookie()
    {
        $this->hook('_COOKIE',function (SplContextArray $array,Request $request){
            return $request->getCookieParams();
        });
    }

    public function hookGet()
    {
        $this->hook('_GET',function (SplContextArray $array,Request $request){
            return $request->getQueryParams();
        });
    }

    public function hookPost()
    {
        $this->hook('_POST',function (SplContextArray $array,Request $request){
            return $request->getParsedBody();
        });
    }
}