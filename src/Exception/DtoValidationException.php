<?php

namespace Bigyohann\DtoBundle\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DtoValidationException extends Exception
{
    private array|ConstraintViolationListInterface $errors;

    /**
     * @param ConstraintViolationListInterface|ConstraintViolation[] $errors
     */
    public function __construct(string $message, $errors)
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): ConstraintViolationListInterface|array
    {
        return $this->errors;
    }
}
