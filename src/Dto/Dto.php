<?php

namespace Bigyohann\DtoBundle\Dto;

use Bigyohann\DtoBundle\Attributes\ConvertProperty;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Dto implements DtoInterface
{
    public function transformToObject(object $object): void
    {
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $reflectionProperty = new ReflectionProperty(static::class, $property->getName());
            if (!$reflectionProperty->isInitialized($this)) {
                continue;
            }
            /** @var ConvertProperty $convertedPropertyAttributes */
            $attributes = $reflectionProperty->getAttributes(ConvertProperty::class);

            if ($this->checkIfPropertyNeedAutoConvert($attributes)) {
                continue;
            }

            $setFunctionName = 'set' . ucfirst($property->name);
            $getFunctionName = 'get' . ucfirst($property->name);
            if (method_exists($object::class, $setFunctionName) !== true) {
                throw new HttpException(500, 'exception');
            }
            if ($this->$getFunctionName($this->{$getFunctionName}())) {
                $object->$setFunctionName($this->{$getFunctionName}());
            }
        }
    }

    /**
     * @param ReflectionAttribute[] $attributes
     * @return bool
     */
    private function checkIfPropertyNeedAutoConvert(array $attributes): bool
    {
        if (!$attributes) {
            return false;
        }
        foreach ($attributes as $attribute) {
            if (!$attribute->getArguments()) {
                return false;
            } else {
                return !$attribute->getArguments()['shouldConvertAutomatically'];
            }
        }
        return false;
    }
}