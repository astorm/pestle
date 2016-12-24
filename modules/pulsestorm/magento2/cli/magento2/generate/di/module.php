<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Di;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Di\exported_pestle_cli');
/**
* Injects a dependency into a class constructor
* This command modifies a preexisting class, adding the provided 
* dependency to that class's property list, `__construct` parameters 
* list, and assignment list.
*
*    pestle.phar magento2:generate:di app/code/Pulsestorm/Generate/Command/Example.php 'Magento\Catalog\Model\ProductFactory' 
*
* @command magento2:generate:di
* @argument file Which PHP class file are we injecting into?
* @argument class Which class to inject? [Magento\Catalog\Model\ProductFactory]
*
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
