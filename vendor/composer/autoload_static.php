<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita97dd59a52a31b8d199eda8343b99fde
{
    public static $files = array (
        '3b48c7d670fd1def4262ee0da383f2e4' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v4p12.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInita97dd59a52a31b8d199eda8343b99fde::$classMap;

        }, null, ClassLoader::class);
    }
}
