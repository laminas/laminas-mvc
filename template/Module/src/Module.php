<?php

declare(strict_types=1);

namespace %name%;

use DirectoryIterator;

class Module
{
    public function getConfig() : array
    {
        $config = [];

        foreach (new DirectoryIterator(__DIR__ . '/../config') as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $key = $fileInfo->getBasename('.php');
            $config[$key] = include $fileInfo->getPathname();
        }

        return $config;
    }
}
