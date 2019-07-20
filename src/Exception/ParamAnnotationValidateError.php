<?php


namespace EasySwoole\Http\Exception;


use EasySwoole\Validate\Validate;

class ParamAnnotationValidateError extends AnnotationError
{
    /**
     * @var Validate
     */
    private $validate;

    /**
     * @return Validate
     */
    public function getValidate(): ?Validate
    {
        return $this->validate;
    }

    /**
     * @param Validate $validate
     */
    public function setValidate(Validate $validate): void
    {
        $this->validate = $validate;
    }

}