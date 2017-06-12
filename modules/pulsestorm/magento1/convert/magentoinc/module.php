<?php
namespace Pulsestorm\Magento1\Convert\Magentoinc;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Menu\inputFromArray');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function getSteps()
{
    return [
        'migrateModuleStructure',
        'convertLayout',
        'convertConfig',
        'convertPhpCode'
    ];
}

function getWhichStep($args, $currentIndex)
{
    if(array_key_exists($currentIndex, $args))
    {
        return $args[$currentIndex];
    }
    $steps = getSteps();
    return inputFromArray("Which Step", $steps);
}

function buildCmdForMigrateModuleStructure($argv)
{
    // var_dump($argv);
    return 'php '              . 
    $argv['bin_migrate'] . ' ' . 
    'migrateModuleStructure '  . 
    $argv['to_convert'] . ' '  .
    $argv['destination'];
    
    // return 'Hello World';
}

function buildCmdForConvertLayout($argv)
{
    return 'php '              . 
    $argv['bin_migrate'] . ' ' . 
    'convertLayout '  . 
    $argv['destination'];    
}

function buildCmdForConvertConfig($argv)
{
    return 'php '              . 
    $argv['bin_migrate'] . ' ' . 
    'convertConfig '  . 
    $argv['destination'];    
}

function buildCmdForConvertPhpCode($argv)
{
    return 'php '              . 
    $argv['bin_migrate'] . ' ' . 
    'convertPhpCode '  . 
    $argv['destination'] . ' ' .
    $argv['magento1'] . ' ' .
    $argv['magento2'] . ' ';;    
}

function runCommand($cmd)
{
    // $proc = popen('ls', 'r');
    $proc = popen($cmd, 'r');
    while (!feof($proc))
    {
        echo fread($proc, 4096);
        @ flush();
    }
    pclose($proc);
}

/**
* ALPHA: Wrapper for Magento Inc.'s code-migration tool
*
* @command magento1:convert:magentoinc
* @argument bin_migrate Path to bin/migrate.php [bin/migrate.php]
* @argument to_convert Folder with Modules to Convert [m1-to-convert]
* @argument destination Destination Folder [m2-converted]
* @argument magento1 Magento 1 Folder [m1]
* @argument magento2 Magento 2 Folder [m2]
* @argument step @callback getWhichStep
*/
function pestle_cli($argv)
{
    $steps = getSteps();
    if(!in_array($argv['step'], $steps))
    {
        exitWithErrorMessage("Unknown step {$argv['step']}");
    }

    $cmd = call_user_func(__NAMESPACE__ . '\buildCmdFor' . ucwords($argv['step']), $argv);
    runCommand($cmd);
    
    // output("@TODO: Generate registration.php");
    // output("@TODO: Clean up/Comment invalid node left in config.xml");
    // output("@TODO: Element 'route': Missing child element(s). Expected is ( module ). in routes.xml files");
    // output("@TODO: controller converted to: 'class Index extends ABC\Contacts\Controller\Index;");
    // output("@TODO: controller has empty contructor, so DI doesn't get called");    
    // output("@TODO: controller doesn't replace loadLayout/renderLayout calls with page layout object");    
    // output("@TODO: Added OBSOLETE to my layout handle XML file.");        
    // output("@TODO: Didn't Covert layout handle XML file completly");        
    // output("    @TODO: Didn't add javascript file");        
    // output("    @TODO: Didn't add a layout='' attribute");            
    // output("    @TODO: Didn't produce content block");  
    // output("    @TODO: Handle based on frontName, not route name (abc_contacts_index_index)");      
    // output("    @TODO: setTitle in wrong spot");      
    
}
