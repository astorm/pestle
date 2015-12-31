<?php
namespace Pulsestorm\Magento2\Cli\Generate\Mage2_Command;
use function Pulsestorm\Pestle\Runner\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');

function createPathFromNamespace($namespace)
{
    $parts = explode('\\', $namespace);
    $path_dir  = strToLower('modules/' . implode('/', $parts));
    $path_full = $path_dir . '/module.php';
    return $path_full;
}

function createNamespaceFromCommandName($command_name)
{
    if(strpos($command_name,'generate_') !== false)
    {
        $parts = explode('_', $command_name);
        array_shift($parts);
        
        $post_fix = implode(' ', $parts);
        $post_fix = ucwords($post_fix);
        $post_fix = str_replace(' ', '\\', $post_fix);
        $command_name = 'generate\\' . $post_fix;
    }
    $namespace_portion = str_replace(' ','_',
        ucwords(str_replace('_',' ',$command_name)));
    $namespace = 'Pulsestorm\Magento2\Cli\\' . $namespace_portion;
    return $namespace;
}

/**
* One Line Description
*
* @command generate_mage2_command
*/
function pestle_cli($argv)
{
    $command_name = inputOrIndex('Command Name?', 'foo_bar', $argv, 0);
    
    $namespace = createNamespaceFromCommandName($command_name);
            
    $command = '<' . '?php' . "\n" .
        'namespace ' . $namespace . ';'  . "\n" .
        'use function Pulsestorm\Pestle\Runner\pestle_import;'       . "\n" .
        'pestle_import(\'Pulsestorm\Magento2\Cli\Library\output\');' . "\n\n" .
        '/**' . "\n" .
        '* One Line Description' . "\n" .
        '*' . "\n" .
        '* @command '.$command_name.'' . "\n" .
        '*/' . "\n" .
        'function pestle_cli($argv)' . "\n" .
        '{' . "\n" .        
        '    output("Hello Sailor");' . "\n" .
        '}' . "\n";
        
    output("Creating the following class");        
    output($command);
    
    $path_full = createPathFromNamespace($namespace);

    if(file_exists($path_full))
    {
        output("$path_full already exists, bailing");
        exit;
    }

    writeStringToFile($path_full, $command);
    output("bbedit $path_full");
}