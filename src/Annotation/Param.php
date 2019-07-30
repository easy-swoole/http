<?php


namespace EasySwoole\Http\Annotation;

use EasySwoole\Annotation\AbstractAnnotationTag;
use EasySwoole\Annotation\ValueParser;

/**
 * Class Param
 * @package EasySwoole\Http\Annotation
 * @Annotation
 */
final class Param extends AbstractAnnotationTag
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $from = [];

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
        'timestampBefore', 'timestampAfter', 'url','optional'
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

    /**
     * @var string
     */
    public $optional;

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
        $allParams = ValueParser::parser($raw);
        foreach ($allParams as $key => $param){
            switch ($key){
                case 'name':{
                    $this->name = (string)$param;
                    break;
                }
                case 'from':{
                    $this->from = (array)$param;
                    break;
                }
                case 'alias':{
                    $this->alias = (string)$param;
                }
                default :{
                    if(in_array($key,$this->allowValidateRule))
                    {
                        /*
                         * 对inarray 做特殊处理
                         */
                        if(in_array($key,['inArray','notInArray'])){
                            if(!is_array($param[0])){
                                $param = [$param];
                            }
                        }
                        $this->$key = $param;
                        $this->validateRuleList[$key] = true;
                    }
                    break;
                }
            }
        }
    }
}