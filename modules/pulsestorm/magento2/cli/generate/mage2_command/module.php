<?php
namespace Pulsestorm\Magento2\Cli\Generate\Mage2_Command;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');

function createPathFromNamespace($namespace)
{
    $parts = explode('\\', $namespace);
    $path_dir  = strToLower('modules/' . implode('/', $parts));
    $path_full = $path_dir . '/module.php';
    return $path_full;
}

function createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
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
    //$namespace = 'Pulsestorm\Magento2\Cli\\' . $namespace_portion;
    $namespace_module = trim($namespace_module, '\\');
    $namespace = $namespace_module . '\\' . $namespace_portion;
    return $namespace;
}

/**
* Generates pestle command boiler plate
* This command creates the necessary files 
* for a pestle command
*
*     pestle.phar generate_pestle_command command_name
*
* @command generate_pestle_command
* @argument command_name New Command Name? [foo_bar]
* @argument namespace_module Create in PHP Namespace? [Pulsestorm\Magento2\Cli]
*/
function pestle_cli($argv)
{
    $command_name = $argv['command_name'];
    $namespace = createNamespaceFromNamespaceAndCommandName($argv['namespace_module'], $command_name);
            
    $command = '<' . '?php' . "\n" .
        'namespace ' . $namespace . ';'  . "\n" .
        'use function Pulsestorm\Pestle\Importer\pestle_import;'       . "\n" .
        'pestle_import(\'Pulsestorm\Pestle\Library\output\');' . "\n\n" .
        '/**' . "\n" .
        '* One Line Description' . "\n" .
        '*' . "\n" .
        '* @command '.$command_name.'' . "\n" .
        '*/' . "\n" .
        'function pestle_cli($argv)' . "\n" .
        '{' . "\n" .        
        '    output("Hello Sailor");' . "\n" .
        '}' . "\n";
        
    output("Creating the following module");        
    output($command);
    
    $path_full = createPathFromNamespace($namespace);

    if(file_exists($path_full))
    {
        output("$path_full already exists, bailing");
        exit;
    }

    writeStringToFile($path_full, $command);
    output("bbedit $path_full");
    output("sublime $path_full");
    output("vi $path_full");    
}