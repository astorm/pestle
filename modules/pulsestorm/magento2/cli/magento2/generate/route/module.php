<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Route;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Route\exported_pestle_cli');

/**
* Creates a Route XML
* generate_route module area id 
* @command magento2:generate:route
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument area Which Area (frontend, adminhtml)? [frontend]
* @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
