<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

if (! class_exists(PHPUnit_Framework_Assert::class)
    && class_exists(PHPUnit\Framework\Assert::class)
) {
    class_alias(PHPUnit\Framework\Assert::class, PHPUnit_Framework_Assert::class);
}
