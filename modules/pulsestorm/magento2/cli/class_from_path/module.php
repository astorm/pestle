<?php
namespace Pulsestorm\Magento2\Cli\Class_From_Path;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');

/**
* Short Description
* Long
* Description
* @command class_from_path
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