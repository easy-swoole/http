<?php


namespace EasySwoole\Http\Annotation;


use EasySwoole\Annotation\AnnotationTagInterface;
/**
 * Class Param
 * @package EasySwoole\Http\Annotation
 * @Annotation
 */
class Param implements AnnotationTagInterface
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string GET|POST
     */
    protected $method;

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

    }
}