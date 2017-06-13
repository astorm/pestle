<?php
namespace Pulsestorm\Magento2\Cli\Path_From_Class;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');

function getPathFromClass($class)
{
    $class = trim($class, '\\');
    return getBaseMagentoDir() . '/app/code/' . implode('/', explode('\\', $class)) . '.php';
}

/**
* Turns a PHP class into a Magento 2 path
* Long
* Description
* @command magento2:path-from-class
*/
function pestle_cli($argv)
{
    $class = input('Enter Class: ', 'Pulsestorm\Helloworld\Model\ConfigSourceProductIdentifierMode');
    output(getPathFromClass($class));
}