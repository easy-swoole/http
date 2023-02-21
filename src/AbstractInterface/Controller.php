<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:16
 */

namespace EasySwoole\Http\AbstractInterface;

use EasySwoole\Http\Message\Status;
use EasySwoole\Http\ReflectionCache;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

abstract class Controller
{
    private $request;
    private $response;
    private $actionName;

    private $json = null;
    private $xml = null;

    function __construct(Request $request, Response $response,?string $actionName)
    {
        $this->request = $request;
        $this->response = $response;
        if(empty($actionName)){
            $actionName = "actionNotFound";
        }
        $this->actionName = $actionName;
    }

    protected function actionNotFound(?string $action)
    {
        $class = static::class;
        $this->writeJson(Status::CODE_NOT_FOUND,null,"{$class} has not action for {$action}");
    }

    protected function afterAction(?string $actionName): void
    {
    }

    protected function onException(\Throwable $throwable): void
    {
        throw $throwable;
    }

    protected function onRequest(?string $action): ?bool
    {
        return true;
    }

    protected function getActionName(): ?string
    {
        return $this->actionName;
    }

    protected function request(): Request
    {
        return $this->request;
    }

    protected function response(): Response
    {
        return $this->response;
    }

    protected function writeJson($statusCode = 200, $result = null, $msg = null)
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $statusCode,
                "result" => $result,
                "msg" => $msg
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }

    protected function json(): array
    {
        if($this->json !== null){
            return $this->json;
        }
        $json = json_decode($this->request()->getBody()->__toString(), true);
        if(is_array($json)){
            $this->json = $json;
        }else{
            $this->json = [];
        }
        return $this->json;
    }

    protected function xml($options = LIBXML_NOERROR | LIBXML_NOCDATA, string $className = 'SimpleXMLElement')
    {
        if($this->xml !== null){
            return $this->xml;
        }
        if (\PHP_VERSION_ID < 80000 || \LIBXML_VERSION < 20900){
            libxml_disable_entity_loader(true);
        }
        $this->xml = simplexml_load_string($this->request()->getBody()->__toString(), $className, $options);
        return $this->xml;
    }

    //该方法用于保留对外调用
    public function __hook(?array $actionArg = [],?array $onRequestArg = null)
    {
        $actionName = $this->actionName;
        $forwardPath = null;
        $ref = ReflectionCache::getInstance()->getClassReflection(static::class);
        $allowMethodReflections = ReflectionCache::getInstance()->allowMethodReflections($ref);
        try {
            $ret = call_user_func([$this,"onRequest"],$actionName,$onRequestArg);
            if ($ret !== false) {
                if (isset($allowMethodReflections[$actionName])) {
                    $forwardPath = call_user_func([$this,$actionName],...$actionArg);
                } else {
                    $forwardPath = $this->actionNotFound($actionName);
                }
            }
        } catch (\Throwable $throwable) {
            //若没有重构onException，直接抛出给上层
            $this->onException($throwable);
        } finally {
            try {
                $this->afterAction($actionName);
            } catch (\Throwable $throwable) {
                $this->onException($throwable);
            }
        }
        return $forwardPath;
    }
}
