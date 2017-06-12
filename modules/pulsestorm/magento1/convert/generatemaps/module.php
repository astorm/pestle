<?php
namespace Pulsestorm\Magento1\Convert\Generatemaps;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function runCommand($cmd)
{
    output("Running");
    output("    $cmd");

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
* ALPHA: Wrapper for Magento's code-migration tools
*
* @command magento1:convert:generate-maps
* @argument path_cmd Path to bin/utils.php [bin/utils.php]
* @argument path_cmd_migrate Path to bin/migrate.php [bin/migrate.php]
* @argument path_m1 Path to Magento 1 [m1]
* @argument path_m2 Path to Magento 2 [m2]
* @argument enterprise Include Enterprise? (Y/N) [N]
*/
function pestle_cli($argv)
{
    $pathCmd        = $argv['path_cmd'];//'bin/utils.php';
    $pathCmdMigrate = $argv['path_cmd_migrate'];//'bin/migrate.php';
    $pathM1  = $argv['path_m1'];//'m1';
    $pathM2  = $argv['path_m2'];//'m2';
    $includeEE = strToLower($argv['enterprise'])[0] === 'y' ? true : false;
    
    $cmds = [
        sprintf('php %s generateClassDependency %s', $pathCmd, $pathM1),       // - Regenerate mapping/class_dependency.json and mapping/class_dependency_aggregated.json

        sprintf('php %s generateClassMapping %s %s', $pathCmd, $pathM1, $pathM2),     // - Regenerate mapping/class_mapping.json and mapping/unmapped_classes.json

        sprintf('php %s generateModuleMapping %s %s', $pathCmd, $pathM1, $pathM2),    // - Regenerate mapping/module_mapping.json

        sprintf('php %s generateTableNamesMapping %s', $pathCmd, $pathM1),     // - Regenerate mapping/table_names_mapping.json

        sprintf('php %s generateViewMapping %s %s', $pathCmd, $pathM1, $pathM2),      // - Regenerate mapping/view_mapping_adminhtml.json and mapping/view_mapping_frontend.json, mapping/references.xml

        sprintf('php %s generateAliasMapping %s', $pathCmdMigrate, $pathM1),   // - Regenerate mapping/aliases.json
    ];
    $cmdsEE = [
        sprintf('php %s generateAliasMappingEE %s', $pathCmdMigrate, $pathM1),   // - Regenerate mapping/aliases_ee.json    
    ];
    
    array_map(function($cmd){
        runCommand($cmd);
    }, $cmds);
    
    if($includeEE)
    {
        array_map(function($cmd){
            runCommand($cmd);
        }, $cmdsEE);    
    }
}
