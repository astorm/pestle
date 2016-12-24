<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Config_Helper;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Config_Helper\exported_pestle_cli');

/**
* Generates a help class for reading Magento's configuration
*
* This command will generate the necessary files and configuration 
* needed for reading Magento 2's configuration values.
* 
* @command magento2:generate:config_helper
* @todo needs to be implemented
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
