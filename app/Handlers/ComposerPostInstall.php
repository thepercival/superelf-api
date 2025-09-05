<?php

declare(strict_types=1);

namespace App\Handlers;

use Composer\Script\Event;

final class ComposerPostInstall
{
    /** @psalm-suppress UndefinedClass */
    public static function execute(Event $event): int
    {
//        if ($event->isDevMode()) {
//            echo "devMode is enabled, no post-install-executed" . PHP_EOL;
//            return -1;
//        }

        $pathPrefix = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
        if( $pathPrefix === false ) {
            throw new \Exception('invalid path prefix');
        }
        $pathPrefix .= DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

        self::resetRouterCache($pathPrefix);
        self::resetSerializerCache($pathPrefix);

//        $doctrineProxies = $pathPrefix . 'proxies/';
//        if (is_dir($doctrineProxies)) {
//            static::rrmdir($doctrineProxies);
//        }
//        mkdir($doctrineProxies);
//        chmod($doctrineProxies, 0775);
//        chgrp($doctrineProxies, 'www-data');


        return 0;
    }

    private static function resetRouterCache(string $pathPrefix): void
    {
        $routerCache = $pathPrefix . 'router';
        if (file_exists($routerCache)) {
            echo "router cached emptied" . PHP_EOL;
            unlink($routerCache);
        } else {
            echo "no router cache found" . PHP_EOL;
        }
    }

    private static function resetSerializerCache(string $pathPrefix): void
    {
        $serializer = $pathPrefix . 'serializer';

        $serializerMetadata = $serializer . '/metadata';
        if (is_dir($serializerMetadata)) {
            static::rrmdir($serializerMetadata);
        }

        $serializerAnnotations = $serializer . '/annotations';
        if (is_dir($serializerAnnotations)) {
            static::rrmdir($serializerAnnotations);
        }

        if (is_dir($serializer)) {
            static::rrmdir($serializer);
        }

        mkdir($serializer);
        chmod($serializer, 0775);
        chgrp($serializer, 'www-data');

        mkdir($serializerMetadata);
        chmod($serializerMetadata, 0775);
        chgrp($serializerMetadata, 'www-data');

        mkdir($serializerAnnotations);
        chmod($serializerAnnotations, 0775);
        chgrp($serializerAnnotations, 'www-data');
    }

    private static function rrmdir(string $src): void
    {
        $dir = opendir($src);
        if ($dir === false) {
            echo "could not open dir : " . $src . PHP_EOL;
            return;
        }
        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    static::rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
