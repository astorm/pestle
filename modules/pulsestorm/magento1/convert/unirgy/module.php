<?php
namespace Pulsestorm\Magento1\Convert\Unirgy;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

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
* ALPHA: Wrapper for Unirgy Magento Module Conversion
*
* @command magento1:convert:unirgy
* @argument unirgy_path Path to convert.php.php? [./convert.php]
* @argument m1_path Path to Magento 1 system? [./m1]
* @argument module_path Path to Modules to Convert? [./m1-to-convert]
* @argument desination_path Destination path? [./m2-converted]
*/
function pestle_cli($argv)
{
//     output("@TODO: Check format in m1-to-convert (top level module folder)");
//     output("@TODO: Check that all folders are what they say they are");
    
    $cmd = "php {$argv['unirgy_path']} s={$argv['module_path']} o={$argv['desination_path']} m={$argv['m1_path']}";
    runCommand($cmd);
}
