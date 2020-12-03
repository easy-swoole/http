<?php


namespace EasySwoole\Http\Tests;


use EasySwoole\Http\Dispatcher;
use EasySwoole\Http\Message\Uri;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use PHPUnit\Framework\TestCase;

class DispatchTest extends TestCase
{
    private $dispatcherWithRouter;
    private $dispatcher;

    function setUp(): void
    {
        $this->dispatcher = new Dispatcher('EasySwoole\Http\Tests\Controller');
        $this->dispatcherWithRouter = new Dispatcher('EasySwoole\Http\Tests\ControllerWithRouter');
    }


    function testIndex()
    {
        $response = new Response();
        $this->dispatcher->dispatch($this->getRequest('/'),$response);
        $this->assertEquals(200,$response->getStatusCode());
        $this->assertEquals('index',$response->getBody()->__toString());
    }



    private function getRequest($url,array $postData = null)
    {
        $request = new Request();
        $request->withUri(new Uri($url));
        return $request;
    }
}