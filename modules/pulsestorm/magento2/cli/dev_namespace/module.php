<?php
namespace Pulsestorm\Magento2\Cli\Dev_Namespace;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
/**
* BETA: Used to move old pestle files to module.php -- still needed?
* @command dev_namespace
*/
function pestle_cli($argv)
{
    $file = inputOrIndex(
        "File?", '', $argv, 0);
        
    $contents = file_get_contents($file);        
    preg_match('%namespace (.+?);%',$contents,$matches);
    $namespace = $matches[1];
    
    $namespace = strToLower($namespace);        
    $path      = 'modules/' . str_replace('\\','/', $namespace);    
    $full_name = $path . '/module.php';   
    if(!is_dir($path))
    { 
        mkdir($path, 0755, true);
    }
    copy($file, $full_name);
    rename($file, $file . '.moved');
}