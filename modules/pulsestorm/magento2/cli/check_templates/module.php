<?php
namespace Pulsestorm\Magento2\Cli\Check_Templates;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Library\askForModuleAndReturnFolder');

/**
* Checks for incorrectly named template folder
* Long
* Description
* @command magento2:check_templates
*/
function pestle_cli($argv)
{
    $base = askForModuleAndReturnFolder($argv) . '/view';
    
    $view_areas = glob($base . '/*');
    foreach($view_areas as $area)
    {
        output("Checking $area");
        if(is_dir($area . '/template'))
        {
            output("    `template` should be `templates`");
            continue;
        }
        output("    OK");
    }
    output("Done");
}