<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Acl;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Acl\exported_pestle_cli');

/**
* Generates a Magento 2 acl.xml file. 
*
* @command magento2:generate:acl
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument rule_ids Rule IDs? [<$module_name$>::top,<$module_name$>::config,]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
