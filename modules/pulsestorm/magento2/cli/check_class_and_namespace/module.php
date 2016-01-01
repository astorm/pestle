<?php
namespace Pulsestorm\Magento2\Cli\Check_Class_And_Namespace;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\glob_recursive');

function parseNamespace($contents)
{
    preg_match('%namespace(.+?);%', $contents, $matches);
    
    if(count($matches) < 1)
    {
        return false;
    }
    return trim($matches[1]);
}

function parseClass($contents)
{
    preg_match('%class(.+?){%s', $contents, $matches);
    if(count($matches) < 1)
    {
        return false;
    }    
    $line = trim($matches[1]);
    $parts = preg_split('%\s{1,100}%',$line);
    return array_shift($parts);
}

/**
* 
* @command check_class_and_namespace
*/
function pestle_cli($argv)
{    
    $path = inputOrIndex('Which folder?','/path/to/magento/app/code/Pulsestorm',
    $argv, 0);
    $files = glob_recursive($path . '/*');
    
    foreach($files as $file)
    {
        $file = realpath($file);
        if(strpos($file, '.php') === false)
        {
            output("NOT .php: Skipping $file");
            continue;
        }

        $contents  = file_get_contents($file);
        $namespace = parseNamespace($contents);
        if(!$namespace)
        {
            output("No Namspace: Skipping $file");
            continue;            
        }
        $class     = parseClass($contents);
        if(!$class)
        {
            output("No Class: Skipping $class");
            continue;            
        }        
        $full_class = $namespace . '\\' . $class;
        $path       = str_replace('\\','/', $full_class) . '.php';
    
        if(strpos($file, $path) === false)
        {
            output("ERROR: Path `$path` not in");
            output($file);
        }
        else
        {
            output('.');
        }
    }
}
