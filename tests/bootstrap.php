<?php

error_reporting( E_ALL ^ E_NOTICE );

$root = realpath(dirname(__FILE__) . '/../');
define('APPLICATION_PATH', $root . '/application');
define('APPLICATION_ENV',  'unitest');
$library     = $root . '/library';
$tests       = $root . '/tests';
$models      = $root . '/application/models';
$controllers = $root . '/application/controllers';


$path = array(
    $library,
    $tests,
    $tests.'/library',
    get_include_path()
);
require_once $tests . '/testConfiguration.php';
set_include_path(implode(PATH_SEPARATOR, $path));
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();
Zend_Loader_Autoloader::getInstance()->registerNamespace('My_');
Zend_Registry::set('testRoot', $root);
Zend_Registry::set('testBootstrap', $root . '/application/bootstrap.php');

/*
 * Unset global variables that are no longer needed.
 */
unset($root, $library, $tests, $path);