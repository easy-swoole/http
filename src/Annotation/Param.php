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

    private $allowValidateRule = [
        'activeUrl', 'alpha', 'alphaNum', 'alphaDash', 'between', 'bool',
        'decimal', 'dateBefore', 'dateAfter', 'equal', 'different', 'equalWithColumn',
        'differentWithColumn', 'float', 'func', 'inArray', 'integer', 'isIp',
        'notEmpty', 'numeric', 'notInArray', 'length', 'lengthMax', 'lengthMin',
        'betweenLen', 'money', 'max', 'min', 'regex', 'allDigital',
        'required', 'timestamp', 'timestampBeforeDate', 'timestampAfterDate',
        'timestampBefore', 'timestampAfter', 'url'
    ];

    /**
     * @var string
     */
    public $activeUrl;
    /**
     * @var string
     */
    public $alpha;
    /**
     * @var string
     */
    public $alphaNum;
    /**
     * @var string
     */
    public $alphaDash;
    /**
     * @var string
     */
    public $between;
    /**
     * @var string
     */
    public $bool;
    /**
     * @var string
     */
    public $decimal;
    /**
     * @var string
     */
    public $dateBefore;
    /**
     * @var string
     */
    public $dateAfter;
    /**
     * @var string
     */
    public $equal;
    /**
     * @var string
     */
    public $different;
    /**
     * @var string
     */
    public $equalWithColumn;
    /**
     * @var string
     */
    public $differentWithColumn;
    /**
     * @var string
     */
    public $float;
    /**
     * @var string
     */
    public $func;
    /**
     * @var string
     */
    public $inArray;
    /**
     * @var string
     */
    public $integer;
    /**
     * @var string
     */
    public $isIp;
    /**
     * @var string
     */
    public $notEmpty;
    /**
     * @var string
     */
    public $numeric;
    /**
     * @var string
     */
    public $notInArray;
    /**
     * @var string
     */
    public $length;
    /**
     * @var string
     */
    public $lengthMax;
    /**
     * @var string
     */
    public $lengthMin;
    /**
     * @var string
     */
    public $betweenLen;
    /**
     * @var string
     */
    public $money;
    /**
     * @var string
     */
    public $max;
    /**
     * @var string
     */
    public $min;
    /**
     * @var string
     */
    public $regex;
    /**
     * @var string
     */
    public $allDigital;
    /**
     * @var string
     */
    public $required;
    /**
     * @var string
     */
    public $timestamp;
    /**
     * @var string
     */
    public $timestampBeforeDate;
    /**
     * @var string
     */
    public $timestampAfterDate;
    /**
     * @var string
     */
    public $timestampBefore;
    /**
     * @var string
     */
    public $timestampAfter;
    /**
     * @var string
     */
    public $url;

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
        $allParams = [];
        $hasQuotation = false;
        $temp = '';
        for($i = 0;$i < strlen($raw);$i++){
            if($raw[$i] == ',' && (!$hasQuotation)){
                $allParams[] = $temp;
                $temp = '';
            }else{
                $temp = $temp.$raw[$i];
            }
            if($raw[$i] == "\""){
                if($hasQuotation){
                    $hasQuotation = false;
                }else{
                    $hasQuotation = true;
                }
            }
        }
        if(!empty($temp)){
            $allParams[] = $temp;
        }

        foreach ($allParams as $item){
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
                if(in_array($key,$this->allowValidateRule)){
                    $value = trim($args[$key]," \t\n\r\0\x0B\"'");
                    $temp = explode("|",$value);
                    $list = [];
                    foreach ($temp as $subArg){
                        /*
                         * [] 数组支持
                         */
                        if(substr($subArg,0,1) == '[' && substr($subArg,-1,1) == ']'){
                            $subArg = trim($subArg,"[]");
                            $inArray = explode(',',$subArg);
                            $index = count($list);
                            if($index <= 0){
                                $index = 1;
                            }
                            foreach ($inArray as $subItem){
                                /*
                                 * bool null支持
                                 */
                                $value = trim($subItem," \t\n\r\0\x0B\"'");
                                if($value == 'true'){
                                    $value = true;
                                }else if($value == 'false'){
                                    $value = false;
                                }else if($value == 'null'){
                                    $value = null;
                                }
                                $list[$index - 1][] = $value;
                            }
                        }else{
                            /*
                                * bool null支持
                             */
                            $value = trim($subArg," \t\n\r\0\x0B\"'");
                            if($value == 'true'){
                                $value = true;
                            }else if($value == 'false'){
                                $value = false;
                            }else if($value == 'null'){
                                $value = null;
                            }
                            $list[] = $value;
                        }
                    }
                    $this->{$key} = $list;
                    $this->validateRuleList[$key] = true;
                }
            }
        }
    }
}