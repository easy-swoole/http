<?php

namespace EasySwoole\Http;

use EasySwoole\Component\Singleton;
use EasySwoole\Http\Exception\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Coroutine;

class Context
{
    use Singleton;

    const KEY_HTTP_REQUEST = 'KEY_HTTP_REQUEST';

    const KEY_HTTP_RESPONSE = 'KEY_HTTP_RESPONSE';

    protected array $context = [];

    function set(string $key, $value):void
    {
        if($key == self::KEY_HTTP_REQUEST)
        {
            if(!$value instanceof ServerRequestInterface){
                throw new Exception('KEY_HTTP_REQUEST value must be ServerRequestInterface');
            }
        }
        if($key == self::KEY_HTTP_RESPONSE){
            if(!$value instanceof ResponseInterface){
                throw new Exception('KEY_HTTP_RESPONSE value must be ResponseInterface');
            }
        }


        $cId = Coroutine::getCid();
        if(!isset($this->context[$cId])){
            Coroutine::defer(function () use ($cId){
                unset($this->context[$cId]);
            });
        }
        $this->context[$cId][$key] = $value;
    }

    function get(string $key):mixed
    {
        $cId = Coroutine::getCid();
        if(isset($this->context[$cId][$key])){
            return $this->context[$cId][$key];
        }
        return null;
    }

    function __destroy():void
    {
        $cId = Coroutine::getCid();
        unset($this->context[$cId]);
    }
}