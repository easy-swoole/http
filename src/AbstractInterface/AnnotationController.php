<?php


namespace EasySwoole\Http\AbstractInterface;



use EasySwoole\Annotation\Annotation;
use EasySwoole\Http\Annotation\Method;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Validate\Annotations\LengthMax;
use EasySwoole\Validate\Annotations\Required;

abstract class AnnotationController extends Controller
{
    private $methodAnnotations = [];

    public function __construct()
    {
        parent::__construct();
        $annotation = new Annotation();
        /*
         * 注册解析命令
         */
        $annotation->addParserTag(new Method());

        //注册Validate命令
        $annotation->addParserTag(new Required());
        $annotation->addParserTag(new LengthMax());

        foreach ($this->getAllowMethodReflections() as $name => $reflection){
            $ret = $annotation->getClassMethodAnnotation($reflection);
            if(!empty($ret)){
                $this->methodAnnotations[$name] = $ret;
            }
        }

        var_dump($this->methodAnnotations);
    }

    function __hook(?string $actionName, Request $request, Response $response, callable $actionHook = null)
    {
        $actionHook = function (){
            /*
             * 处理请求方法
             */
            /*
             * validate验证
             */

            /*
             * 参数构造
             */
            $action = $this->getActionName();
        };
        return parent::__hook($actionName, $request, $response, $actionHook);
    }
}