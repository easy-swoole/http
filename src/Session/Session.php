<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/26
 * Time: 下午1:36
 */

namespace EasySwoole\Http\Session;


use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Session
{
    private $handler = null;
    private $request;
    private $response;
    function __construct(Request $request,Response $response,\SessionHandlerInterface $sessionHandler = null)
    {
        $this->request = $request;
        $this->response = $response;
        if($sessionHandler){
            $this->handler = $sessionHandler;
        }else{
            $this->handler = new SessionHandler();
        }
    }

    function sid()
    {

    }

    function name()
    {

    }

    function set($key,$val)
    {

    }

    function get($key)
    {

    }

    function close()
    {

    }

    function start()
    {

    }
}