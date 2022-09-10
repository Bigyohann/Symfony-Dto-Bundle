<?php

namespace Bigyohann\DtoBundle\Attributes;

use Attribute;

#[Attribute]
class ConvertProperty
{
    public function __construct(public bool $shouldConvertAutomatically = true)
    {
    }
}