<?php

namespace EasySwoole\Http\Tests\Fixtures\Dispatch;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    public function initialize(RouteCollector $routeCollector): void
    {
        $this->parseParams(self::PARSE_PARAMS_NONE);

        $routeCollector->get('/route', function (Request $request, Response $response): bool {
            $response->write('route');
            return false;
        });

        $routeCollector->get('/forward', '/index/hello');
    }
}
