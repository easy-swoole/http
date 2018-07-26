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
use EasySwoole\Trigger\Trigger;

class Session
{
    private $handler = null;
    private $request;
    private $response;
    private $isStart = false;
    private $sid = null;
    private $sessionName = 'EasySwoole';
    private $savePath;
    private $data = [];
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

    function savePath(string $path = null)
    {
        if($path){
            if(!$this->isStart){
                $this->savePath = rtrim($path,'/');
                return true;
            }else{
                return false;
            }
        }else{
            return $this->savePath;
        }
    }

    function sid(string $sid = null)
    {
        if($sid){
            if(!$this->sid){
                $this->sid = $sid;
                return true;
            }else{
                return false;
            }
        }else{
            return $this->sid;
        }
    }

    function name(string $sessionName = null)
    {
        if($sessionName){
            if(!$this->isStart){
                $this->sessionName = $sessionName;
                return true;
            }else{
                return false;
            }
        }else{
            return $this->sessionName;
        }
    }

    /*
     * 注意，这里并不是同步写入。write close的时候，才真实写入（与php一致）。
     */
    function set($key,$val):bool
    {
        if($this->isStart){
            $this->data[$key] = $val;
            return true;
        }else{
            return false;
        }
    }

    function exist($key)
    {
        if($this->isStart){
            return isset($this->data[$key]);
        }else{
            return false;
        }
    }

    function get($key)
    {
        if(isset($this->data[$key])){
            return $this->data[$key];
        }else{
            return null;
        }
    }

    function destroy()
    {
        if($this->isStart){
            $this->handler->destroy($this->sid);
            $this->close();
            return true;
        }else{
            return false;
        }
    }

    function close()
    {
        if($this->isStart){
            $this->isStart = false;
            if(!$this->handler->write($this->sid,\swoole_serialize::pack($this->data,0))){
                Trigger::error("save session {$this->sessionName}@{$this->sid} fail");
            }
            $this->handler->close();
            $this->sid = null;
            $this->sessionName = 'easySwoole';
            $this->savePath = null;
            $this->data = [];
        }
    }


    function start():bool
    {
        if(!$this->isStart){
            $this->isStart = $this->handler->open($this->savePath,$this->sessionName);
            if(!$this->isStart){
                Trigger::error("session open {$this->savePath}@{$this->sessionName} fail");
                return false;
            }else{
                //开启成功，则准备sid;
                $this->sid = $this->generateSid();
                //载入数据
                $data = $this->handler->read($this->sid);
                if(!empty($data)){
                    $data = \swoole_serialize::unpack($data);
                    if(is_array($data)){
                        $this->data = $data;
                    }
                }
                return true;
            }
        }
        return true;
    }

    private function generateSid():string
    {
        $sid = $this->request->getCookieParams($this->sessionName);
        if(!empty($sid)){
            return $sid;
        }else{
            $sid = md5(microtime(true).$this->request->getSwooleRequest()->fd);
            $this->request->withCookieParams(
                [
                    $this->sessionName => $sid
                ]
                +
                $this->request->getCookieParams()
            );
            $this->response->setCookie($this->sessionName,$sid);
            return $sid;
        }
    }

    function __destruct()
    {
        $this->close();
    }
}