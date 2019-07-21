<?php


namespace EasySwoole\Http\AbstractInterface;


use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

abstract class AnnotationREST extends AnnotationController
{
    function __hook(?string $actionName, Request $request, Response $response, callable $actionHook = null)
    {
        $actionName = $request->getMethod().ucfirst($actionName);
        return parent::__hook($actionName, $request, $response, $actionHook);
    }
}