<?php

declare(strict_types=1);

if (
    ! class_exists(phpunit_framework_assert::class)
    && class_exists(PHPUnit\Framework\Assert::class)
) {
    class_alias(PHPUnit\Framework\Assert::class, phpunit_framework_assert::class);
}
