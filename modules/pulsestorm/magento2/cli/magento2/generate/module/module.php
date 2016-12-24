<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Module\exported_pestle_cli');

/**
* Generates new module XML, adds to file system
* This command generates the necessary files and configuration
* to add a new module to a Magento 2 system.
*
*    pestle.phar Pulsestorm TestingCreator 0.0.1
*
* @argument namespace Vendor Namespace? [Pulsestorm]
* @argument name Module Name? [Testbed]
* @argument version Version? [0.0.1]
* @command magento2:generate:module
*/
function pestle_cli($argv)
{
    exported_pestle_cli($argv);
}

function test()
{
    output("Hello There. " . __FILE__);
}