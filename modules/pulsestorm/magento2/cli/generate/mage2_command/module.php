<?php
namespace Pulsestorm\Magento2\Cli\Generate\Mage2_Command;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Code_Generation\createPathFromNamespace');
pestle_import('Pulsestorm\Cli\Code_Generation\createNamespaceFromNamespaceAndCommandName');

/**
* Generates pestle command boiler plate
* This command creates the necessary files 
* for a pestle command
*
*     pestle.phar generate_pestle_command command_name
*
* @command generate-pestle-command
* @argument command_name New Command Name? [foo_bar]
* @argument namespace_module Create in PHP Namespace? [Pulsestorm]
*/
function pestle_cli($argv)
{
    $command_name = $argv['command_name'];
    $namespace = createNamespaceFromNamespaceAndCommandName($argv['namespace_module'], $command_name);
            
    $command = '<' . '?php' . "\n" .
        'namespace ' . $namespace . ';'  . "\n" .
        'use function Pulsestorm\Pestle\Importer\pestle_import;'       . "\n" .
        'pestle_import(\'Pulsestorm\Pestle\Library\output\');' . "\n\n" .
        'pestle_import(\'Pulsestorm\Pestle\Library\exitWithErrorMessage\');' . "\n\n" .
        

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

function pestle_cli_exported($argv, $options=[])
{
    return pestle_cli($argv, $options);
}    