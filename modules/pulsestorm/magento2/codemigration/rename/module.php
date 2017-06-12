<?php
namespace Pulsestorm\Magento2\Codemigration\Rename;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function runCommand($cmd)
{
    output("Running Command");
    output($cmd);
    output(`$cmd`);
    output("Command Done");
    output("--------------------");
}


/**
* ALPHA: Rename .converted files
*
* @command magento2:code-migration:rename
* @argument path Path to module? [app/code/Package/Module]
*/
function pestle_cli($argv)
{
    

    $path = $argv['path'];
    
    $olds = `find $path/ -name '*.php.old'`;
    $olds = explode("\n", $olds);
    $olds = array_filter($olds);
    if(count($olds) > 0)
    {
        exitWithErrorMessage("BAILING: Found *.php.old files -- looks like you already ran this command.");
    }

//     var_dump($olds);
//     exit(__FUNCTION__ . "\n");
    // $path = 'm2-converted';
    // $path = 'app/code/LCG/Ambassador';
    $oldFiles       = `find $path/ -name '*.php'`;
    $convertedFiles = `find $path/ -name '*.php.converted'`;
    
    

    $oldFiles = explode("\n", $oldFiles);    
    $oldFiles = array_filter($oldFiles);
    foreach($oldFiles as $file)
    {    
        $cmd = sprintf('mv %s %s', $file, $file . '.old');
        runCommand($cmd);
    }
    
    $convertedFiles = explode("\n", $convertedFiles);
    $convertedFiles = array_filter($convertedFiles);
    foreach($convertedFiles as $file)
    {
        $newPhpFile = preg_replace('%.php.converted$%', '.php', $file); 
        $cmd = sprintf('mv %s %s', $file, $newPhpFile);
        runCommand($cmd);
    }

}
