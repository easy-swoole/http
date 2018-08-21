<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:16
 */

namespace EasySwoole\Http\AbstractInterface;

use EasySwoole\Http\Message\Status;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Http\Session\Session;
use EasySwoole\Validate\Validate;

abstract class Controller
{
    private $request;
    private $response;
    private $actionName;
    private $session;

    private $allowMethods = [];

    function __construct()
    {
        //支持在子类控制器中以private，protected来修饰某个方法不可见
        $list = [];
        $ref = new \ReflectionClass(static::class);
        $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($public as $item){
            array_push($list,strtolower($item->getName()));
        }
        $this->allowMethods = array_diff($list,
            array_map('strtolower', [
                'gc','__hook','__destruct',
                '__clone','__construct','__call',
                '__callStatic','__get','__set',
                '__isset','__unset','__sleep',
                '__wakeup','__toString','__invoke',
                '__set_state','__clone','__debugInfo'
            ])
        );
    }

    abstract function index();

    public function gc()
    {
        // TODO: Implement gc() method.
        $this->actionName = null;
        $this->request = null;
        $this->response = null;
        if($this->session instanceof Session){
            $this->session->writeClose();
            $this->session = null;
        }
    }

    protected function actionNotFound(?string $action):void
    {
        $this->response()->withStatus(Status::CODE_NOT_FOUND);
    }

    protected function afterAction(?string $actionName):void
    {

    }

    protected function onException(\Throwable $throwable):void
    {
        throw $throwable ;
    }

    protected function onRequest(?string $action):?bool
    {
        return true;
    }

    protected function getActionName():?string
    {
        return $this->actionName;
    }

    public function __hook(?string $actionName,Request $request,Response $response):void
    {
        $this->request = $request;
        $this->response = $response;
        $this->actionName = $actionName;
        try{
            if($this->onRequest($actionName) !== false){
                if(in_array(strtolower($actionName), $this->allowMethods)){
                    $this->$actionName();
                }else{
                    $this->actionNotFound($actionName);
                }
            }
        }catch (\Throwable $throwable){
            //若没有重构onException，直接抛出给上层
            $this->onException($throwable);
        }finally{
            $this->afterAction($actionName);
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

    protected function json():?array
    {
        return json_decode($this->request()->getBody()->__toString(),true);
    }

    protected function xml($options = LIBXML_NOERROR,string $className = 'SimpleXMLElement')
    {
        return simplexml_load_string($this->request()->getBody()->__toString(), $className,$options);
    }

    protected function validate(Validate $validate)
    {
        return $validate->validate($this->request()->getRequestParam());
    }

    protected function session(\SessionHandlerInterface $sessionHandler = null):Session
    {
        if($this->session == null){
            $this->session = new Session($this->request,$this->response,$sessionHandler);
        }
        return $this->session;
    }

    /*
     * 对象回收时，注意释放Session
     */
    function __destruct()
    {
        // TODO: Implement __destruct() method.
        if($this->session instanceof Session){
            $this->session->writeClose();
            $this->session = null;
        }
    }
}
