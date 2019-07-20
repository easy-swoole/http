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
    protected $name;

    /**
     * @var array
     */
    protected $method = ['GET','POST'];
    /**
     * @var array
     */
    protected $required = [];
    /**
     * @var array
     */
    protected $maxLen = [];

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
        var_dump($raw);
    }
}