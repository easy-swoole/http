<?php


namespace EasySwoole\Http\Tests\ControllerWithRouter;


use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    function index()
    {
        $this->response()->write('index');
    }
}