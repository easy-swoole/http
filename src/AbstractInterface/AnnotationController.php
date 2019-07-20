<?php


namespace EasySwoole\Http\AbstractInterface;



use EasySwoole\Annotation\Annotation;
use EasySwoole\Http\Annotation\Method;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Exception\AnnotationMethodNotAllow;
use EasySwoole\Http\Exception\ParamAnnotationError;
use EasySwoole\Http\Exception\ParamAnnotationValidateError;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Validate\Validate;

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
        $annotation->addParserTag(new Param());
        foreach ($this->getAllowMethodReflections() as $name => $reflection){
            $ret = $annotation->getClassMethodAnnotation($reflection);
            if(!empty($ret)){
                $this->methodAnnotations[$name] = $ret;
            }
        }
    }

    function __hook(?string $actionName, Request $request, Response $response, callable $actionHook = null)
    {
        $actionHook = function ()use($actionName){
            if(isset($this->methodAnnotations[$actionName])){
                $annotations = $this->methodAnnotations[$actionName];
                /*
                     * 处理请求方法
                */
                if(!empty($annotations['Method'])){
                    $method = $annotations['Method'][0]->allow;
                    if(!in_array($this->request()->getMethod(),$method)){
                        throw new AnnotationMethodNotAllow("request method{$this->request()->getMethod()} is not allow for action {$actionName} in class ".(static::class) );
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
                        if(!empty($param->method)){
                            if(in_array('POST',$param->method)){
                                $value = $this->request()->getParsedBody($paramName);
                            }else if(in_array('GET',$param->method)){
                                $value = $this->request()->getQueryParam($paramName);
                            }else{
                                $value = $this->request()->getRequestParam($paramName);
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