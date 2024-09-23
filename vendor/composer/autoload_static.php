<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb25e5720c60de774708355e8bccac8da
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Romki4\\ResponsiveImages\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Romki4\\ResponsiveImages\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb25e5720c60de774708355e8bccac8da::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb25e5720c60de774708355e8bccac8da::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb25e5720c60de774708355e8bccac8da::$classMap;

        }, null, ClassLoader::class);
    }
}
