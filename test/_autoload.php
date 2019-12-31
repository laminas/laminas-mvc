<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

if (! class_exists(PHPUnit_Framework_Assert::class)
    && class_exists(PHPUnit\Framework\Assert::class)
) {
    class_alias(PHPUnit\Framework\Assert::class, PHPUnit_Framework_Assert::class);
}
