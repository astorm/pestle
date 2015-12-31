<?php
namespace Pulsestorm\Magento2\Cli\Path_From_Class;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');

function getPathFromClass($class)
{
    $class = trim($class, '\\');
    return getBaseMagentoDir() . '/app/code/' . implode('/', explode('\\', $class)) . '.php';
}

/**
* Short Description
* Long
* Description
* @command path_from_class
*/
function pestle_cli($argv)
{
    $class = input('Enter Class: ', 'Pulsestorm\Helloworld\Model\ConfigSourceProductIdentifierMode');
    output(getPathFromClass($class));
}
