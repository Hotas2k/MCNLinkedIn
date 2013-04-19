<?php
// change directory to project root
chdir(dirname(__DIR__));

/* @var $loader \Composer\Autoload\ClassLoader */

function findParentPath($path)
{
    $dir = __DIR__;
    $previousDir = '.';
    while (!is_dir($dir . '/' . $path)) {
        $dir = dirname($dir);
        if ($previousDir === $dir) return false;
        $previousDir = $dir;
    }
    return $dir . '/' . $path;
}

$path = findParentPath('vendor');

if (! is_readable($path . '/autoload.php')) {

    die('Could not find the vendor autoload, run composer.phar install in the root directory of the module');
}

$loader = include $path . '/autoload.php';

$loader->add('MCNLinkedInTest\\', __DIR__);

$config = @include __DIR__ . '/TestConfig.php';

if (! $config) {

    die('Could not load the application config');
}

MCNStdlib\TestUtil\ServiceManagerFactory::setConfig($config);
