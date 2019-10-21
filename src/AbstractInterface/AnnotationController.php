<?php


namespace EasySwoole\Http\AbstractInterface;



use EasySwoole\Annotation\Annotation;
use EasySwoole\Component\Context\ContextManager;
use EasySwoole\Http\Annotation\Context;
use EasySwoole\Http\Annotation\Di;
use EasySwoole\Http\Annotation\Method;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Exception\AnnotationMethodNotAllow;
use EasySwoole\Http\Exception\ParamAnnotationError;
use EasySwoole\Http\Exception\ParamAnnotationValidateError;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Validate\Validate;
use EasySwoole\Component\Di as IOC;

abstract class AnnotationController extends Controller
{
    private $methodAnnotations = [];
    private $propertyAnnotations = [];
    private $annotation;

    public function __construct(?Annotation $annotation = null)
    {
        parent::__construct();
        if($annotation == null){
            $this->annotation = new Annotation();
        }else{
            $this->annotation = $annotation;
        }
        /*
         * 注册解析命令
         */
        $this->annotation->addParserTag(new Method());
        $this->annotation->addParserTag(new Param());
        $this->annotation->addParserTag(new Context());
        $this->annotation->addParserTag(new Di());
        foreach ($this->getAllowMethodReflections() as $name => $reflection){
            $ret = $this->annotation->getClassMethodAnnotation($reflection);
            if(!empty($ret)){
                $this->methodAnnotations[$name] = $ret;
            }
        }
        foreach ($this->getPropertyReflections() as $name => $reflection){
            $ret = $this->annotation->getPropertyAnnotation($reflection);
            if(!empty($ret)){
                $this->propertyAnnotations[$name] = $ret;
            }
        }
    }

    protected function getMethodAnnotations():array
    {
        return $this->methodAnnotations;
    }

    protected function getAnnotation():Annotation
    {
        return $this->annotation;
    }

    function __hook(?string $actionName, Request $request, Response $response, callable $actionHook = null)
    {
        /*
         * 扫码全部public属性的注解
         */

        foreach ($this->propertyAnnotations as $name => $propertyAnnotation){
            /*
             * 判断上下文注解
             */
            if(!empty($propertyAnnotation['Context'])){
                $context = $propertyAnnotation['Context'][0]->key;
                if(!empty($context)){
                    $this->{$name} = ContextManager::getInstance()->get($context);
                }
            }
            /*
             * 判断Di注入
             */
            if(!empty($propertyAnnotation['DI'])){
                $key = $propertyAnnotation['DI'][0]->key;
                if(!empty($key)){
                    $this->{$name} = IOC::getInstance()->get($key);
                }
            }
        }

        $actionHook = function ()use($actionName){
            if(isset($this->methodAnnotations[$actionName])){
                $annotations = $this->methodAnnotations[$actionName];
                /*
                     * 处理请求方法
                */
                if(!empty($annotations['Method'])){
                    $method = $annotations['Method'][0]->allow;
                    if(!in_array($this->request()->getMethod(),$method)){
                        throw new AnnotationMethodNotAllow("request method {$this->request()->getMethod()} is not allow for action {$actionName} in class ".(static::class) );
                    }
                }
                /*
                 * 参数构造与validate验证
                 */
                $actionArgs = [];
                $validate = new Validate();
                if(!empty($annotations['Param'])){
                    $params = $annotations['Param'];
                    foreach ($params as $param){
                        $paramName = $param->name;
                        if(empty($paramName)){
                            throw new ParamAnnotationError("param annotation error for action {$actionName} in class ".(static::class));
                        }
                        if(!empty($param->from)){
                            $value = null;
                            /*
                             * 按照允许的列表顺序进行取值
                             */
                            foreach ($param->from as $from){
                                switch ($from){
                                    case "POST":{
                                        $value = $this->request()->getParsedBody($paramName);
                                        break;
                                    }
                                    case "GET":{
                                        $value = $this->request()->getQueryParam($paramName);
                                        break;
                                    }
                                    case "COOKIE":{
                                        $value = $this->request()->getCookieParams($paramName);
                                        break;
                                    }
                                }
                                if($value !== null){
                                    break;
                                }
                            }
                        }else{
                            $value = $this->request()->getRequestParam($paramName);
                        }
                        /*
                         * 注意，这边可能得到null数据，若要求某个数据不能为null,请用验证器柜子
                         */
                        $actionArgs[$paramName] = $value;
                        if(!empty($param->validateRuleList)){
                            foreach ($param->validateRuleList as $rule => $none){
                                $validateArgs = $param->{$rule};
                                if(!is_array($validateArgs)){
                                    $validateArgs = [$validateArgs];
                                }
                                $validate->addColumn($param->name,$param->alias)->{$rule}(...$validateArgs);
                            }
                        }
                    }
                }
                $data = $actionArgs +  $this->request()->getRequestParam();
                if(!$validate->validate($data)){
                    $ex = new ParamAnnotationValidateError("validate fail for column {$validate->getError()->getField()}");
                    $ex->setValidate($validate);
                    throw $ex;
                }
                return $this->$actionName(...array_values($actionArgs));
            }else{
                return $this->$actionName();
            }
        };
        return parent::__hook($actionName, $request, $response, $actionHook);
    }
}