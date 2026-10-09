<?php

namespace EasySwoole\Http\Tests\Fixtures\Dispatch;

use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    public function index(): void
    {
        $this->response()->write('index');
    }

    public function hello(): void
    {
        $this->response()->write('hello');
    }

    public function forward(): string
    {
        return '/index/hello';
    }

    public function exception(): void
    {
        throw new \RuntimeException('controller error');
    }
}
