<?php


namespace EasySwoole\Http\AbstractInterface;



use EasySwoole\Annotation\Annotation;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

abstract class AnnotationController extends Controller
{
    private $methodAnnotations = [];

    public function __construct()
    {
        parent::__construct();
        $annot = new Annotation();
        foreach ($this->getAllowMethodReflections() as $name => $reflection){
            $ret = $annot->getClassMethodAnnotation($reflection);
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