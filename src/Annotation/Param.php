<?php


namespace EasySwoole\Http\Annotation;

use EasySwoole\Annotation\AnnotationTagInterface;

/**
 * Class Param
 * @package EasySwoole\Http\Annotation
 * @Annotation
 */
final class Param implements AnnotationTagInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $method = [];

    /**
     * @var string
     */
    public $alias = null;

    /**
     * 以下为校验规则
     */

    public $validateRuleList = [];

    /**
     * @var string
     */
    public $required;

    /**
     * @var string
     */
    public $lengthMax;

    public function tagName(): string
    {
        return 'Param';
    }

    public function aliasMap(): array
    {
        return [static::class];
    }

    public function assetValue(?string $raw)
    {
        $list = explode(',',$raw);
        foreach ($list as $item){
            parse_str($item,$args);
            if(isset($args['name'])){
                $this->name = trim($args['name']," \t\n\r\0\x0B\"'");
            }else if(isset($args['method'])){
                $str = trim($args['method'],"{}");
                $temp = explode(",",$str);
                foreach ($temp as $method){
                    $this->method[] = trim($method," \t\n\r\0\x0B\"'");
                }
            }else if(isset($args['alias'])){
                $this->alias = trim($args['alias']," \t\n\r\0\x0B\"'");
            }else{
                $key = array_keys($args)[0];
                if(property_exists($this,$key)){
                    $value = trim($args[$key]," \t\n\r\0\x0B\"'");
                    $temp = explode("|",$value);
                    $list = [];
                    foreach ($temp as $sub){
                        $list[] = trim($sub," \t\n\r\0\x0B\"'");
                    }
                    $this->{$key} = $list;
                    $this->validateRuleList[$key] = true;
                }
            }
        }
    }
}