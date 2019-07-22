<?php


namespace EasySwoole\Http\Annotation;


use EasySwoole\Annotation\AbstractAnnotationTag;

/**
 * Class Method
 * @package EasySwoole\Http\Annotation
 * @Annotation
 */
final class Method extends AbstractAnnotationTag
{
    /**
     * @var array
     */
    public $allow = [];

    public function tagName(): string
    {
        return 'Method';
    }

    public function assetValue(?string $raw)
    {
        parse_str($raw,$str);
        if(isset($str['allow'])){
            $str = trim($str['allow'],"{}");
            $list = explode(",",$str);
            foreach ($list as $item){
                $this->allow[] = trim($item);
            }
        }
    }
}