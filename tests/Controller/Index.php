<?php


namespace EasySwoole\Http\Tests\Controller;


use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    function index()
    {
        $this->response()->write('index');
    }
}