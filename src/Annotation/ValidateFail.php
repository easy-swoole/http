<?php


namespace EasySwoole\Http\Annotation;

use EasySwoole\Annotation\AnnotationTagInterface;

/**
 * Class ValidateFail
 * @package EasySwoole\Http\Annotation
 * @Annotation
 */
final class ValidateFail implements AnnotationTagInterface
{

    /**
     * @var callable
     */
    protected $failCall;

    public function tagName(): string
    {
        return 'ValidateFail';
    }

    public function aliasMap(): array
    {
        return [static::class];
    }

    public function assetValue(?string $raw)
    {

    }
}