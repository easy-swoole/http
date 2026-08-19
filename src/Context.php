<?php

namespace EasySwoole\Http;

use EasySwoole\Component\Singleton;
use Swoole\Coroutine;

class Context
{
    use Singleton;

    protected array $context = [];

    function set(string $key, $value):void
    {
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