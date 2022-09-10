<?php

namespace Bigyohann\DtoBundle\Dto;

interface DtoInterface
{
    public function transformToObject(object $object): void;
}