<?php
namespace Pulsestorm\Magento2\Cli\Generate\Registration;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\templateRegistrationPhp');
pestle_import('Pulsestorm\Magento2\Cli\Library\inputModuleName');

/**
* Short Description
* Long
* Description
* @command generate_registration
*/
function pestle_cli($argv)
{
    if(count($argv) === 0)
    {
        $argv[] = inputModuleName();
    }
    $module_name = $argv[0];
    
    output(templateRegistrationPhp($module_name));
}
