<?php


namespace EasySwoole\Http\AbstractInterface;



use EasySwoole\Annotation\Annotation;
use EasySwoole\Http\Annotation\Method;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

abstract class AnnotationController extends Controller
{
    private $methodAnnotations = [];

    public function __construct()
    {
        parent::__construct();
        $annotation = new Annotation();
        $annotation->addParserTag(new Method());
        foreach ($this->getAllowMethodReflections() as $name => $reflection){
            $ret = $annotation->getClassMethodAnnotation($reflection);
            if(!empty($ret)){
                $this->methodAnnotations[$name] = $ret;
            }
        }

        var_dump($this->methodAnnotations);
    }

    function __hook(?string $actionName, Request $request, Response $response)
    {
        /*
         * 重写hook
         */
    }
}