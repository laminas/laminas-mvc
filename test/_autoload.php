<?php

if (! class_exists(PHPUnit_Framework_Assert::class)
    && class_exists(PHPUnit\Framework\Assert::class)
) {
    class_alias(PHPUnit\Framework\Assert::class, PHPUnit_Framework_Assert::class);
}
