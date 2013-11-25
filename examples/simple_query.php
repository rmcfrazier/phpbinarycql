<?php
// get the autoloader
require '../vendor/autoload.php';

// register the namespaces
$classLoader = new SplClassLoader('McFrazier\PhpBinaryCql', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'src');
$classLoader->register();

$pbc = new \McFrazier\PhpBinaryCql\CqlClient('192.168.2.240', '9042'); // host and port for cassandra
$pbc->addStartupOption('CQL_VERSION', '3.0.4');

$queryText = 'select * from system.schema_keyspaces';
$response = $pbc->query($queryText, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);

// view the entire response object
var_dump($response);

// view just the data payload
var_dump($response->getData());