<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:16
 */

namespace EasySwoole\Http\AbstractInterface;


use EasySwoole\Component\Pool\AbstractObject;
use EasySwoole\Http\Message\Status;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

abstract class Controller extends AbstractObject
{
    private $request;
    private $response;
    private $actionName;

    protected $forbidActions = ['__construct','__hook','objectRestore'];

    abstract function index();

    protected function gc()
    {
        // TODO: Implement gc() method.
    }

    function objectRestore()
    {
        // TODO: Implement objectRestore() method.
        $this->actionName = null;
        $this->request = null;
        $this->response = null;
    }


    protected function actionNotFound($action):void
    {
        $this->response()->withStatus(Status::CODE_NOT_FOUND);
    }

    protected function afterAction($actionName):void
    {

    }

    protected function onException(\Throwable $throwable):void
    {
        throw $throwable ;
    }

    protected function onRequest($action):?bool
    {
        return true;
    }

    protected function getActionName():string
    {
        return $this->actionName;
    }

    public function __hook(?string $actionName,Request $request,Response $response):void
    {
        if(in_array($actionName,$this->forbidActions)){
            $this->response()->withStatus(Status::CODE_BAD_REQUEST);
            return ;
        }
        $this->request = $request;
        $this->response = $response;
        $this->actionName = $actionName;
        if($this->onRequest($actionName) !== false){
            //支持在子类控制器中以private，protected来修饰某个方法不可见
            try{
                $ref = new \ReflectionClass(static::class);
                if($ref->hasMethod($actionName) && $ref->getMethod( $actionName)->isPublic()){
                    $this->$actionName();
                }else{
                    $this->actionNotFound($actionName);
                }
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
            //afterAction 始终都会被执行
            try{
                $this->afterAction($actionName);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        }
    }

    protected function request():Request
    {
        return $this->request;
    }

    protected function response():Response
    {
        return $this->response;
    }

    protected function writeJson($statusCode = 200,$result = null,$msg = null){
        if(!$this->response()->isEndResponse()){
            $data = Array(
                "code"=>$statusCode,
                "result"=>$result,
                "msg"=>$msg
            );
            $this->response()->write(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type','application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        }else{
            return false;
        }
    }
}