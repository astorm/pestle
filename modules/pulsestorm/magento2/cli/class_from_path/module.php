<?php
namespace Pulsestorm\Magento2\Cli\Class_From_Path;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* Turns a Magento file path into a PHP class
* Long
* Description
* @command magento2:class-from-path
*/
function pestle_cli($argv)
{
    $path = input('Enter Path: ');
    $parts = explode('/',$path);
    $class = [];
    foreach($parts as $part)
    {
        if($part === 'code' || count($class) > 0)
        {
            $class[] = $part;
        }
    }
    array_shift($class);

    $class_name = array_pop($class);
    $body = '<' . '?' . 'php' . "\n" . 
    'namespace ' . implode('\\', $class) . ";\n" .
    'class ' . str_replace('.php','',$class_name) . "\n" . '{}';
    
    output($body);
}