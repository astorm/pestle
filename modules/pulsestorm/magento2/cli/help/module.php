<?php
namespace Pulsestorm\Magento2\Cli\Help;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\List_Commands\pestle_cli');
/**
* Alias for list
* @command help
*/
function pestle_cli($argv)
{
    require_once __DIR__ . '/../list_commands/module.php';
    return \Pulsestorm\Magento2\Cli\List_Commands\pestle_cli($argv);
}
