<?php
// get the autoloader
require '../vendor/autoload.php';

// register the namespaces
$classLoader = new SplClassLoader('McFrazier\PhpBinaryCql', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'src');
$classLoader->register();