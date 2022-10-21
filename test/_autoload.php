<?php

use PHPUnit\Framework\Assert;

if (! class_exists(PHPUnit_Framework_Assert::class)
    && class_exists(Assert::class)
) {
    class_alias(Assert::class, PHPUnit_Framework_Assert::class);
}
