<?php

namespace EasySwoole\Http;

use EasySwoole\Component\Singleton;


class ReflectionCache
{
    use Singleton;
    protected array $classReflection = [];
    protected array $allowMethodReflections = [];

    private const  forbidMethodList = [
            '__hook', '__destruct',
            '__clone', '__construct', '__call',
            '__callStatic', '__get', '__set',
            '__isset', '__unset', '__sleep',
            '__wakeup', '__toString', '__invoke',
            '__set_state', '__clone', '__debugInfo',
            'onRequest'
    ];

    function getClassReflection(string $class):\ReflectionClass
    {
        $key = md5($class);
        if(isset( $this->classReflection[$key])){
            return $this->classReflection[$key];
        }
        $ref = new \ReflectionClass($class);
        $this->classReflection[$key] = $ref;
        return $ref;
    }

    function allowMethodReflections(\ReflectionClass $reflectionClass):array
    {
        $key = md5($reflectionClass->name);
        if(isset($this->allowMethodReflections[$key])){
            return $this->allowMethodReflections[$key];
        }
        $list = [];
        $public = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($public as $item) {
            if((!in_array($item->getName(),self::forbidMethodList)) && (!$item->isStatic())){
                $list[$item->getName()] = $item;
            }
        }
        $this->allowMethodReflections[$key] = $list;
        return $list;
    }

}