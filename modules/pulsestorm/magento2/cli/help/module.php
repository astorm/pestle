<?php
namespace Pulsestorm\Magento2\Cli\Help;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Runner\applyCommandNameAlias');
/**
* Alias for list
* @option is-machine-readable pipable/processable output?
* @command help
*/
function pestle_cli($argv, $options)
{
    require_once __DIR__ . '/../list_commands/module.php';
    if(isset($argv[0]))
    {
        $argv[0] = applyCommandNameAlias($argv[0]);
    }
    return \Pulsestorm\Magento2\Cli\List_Commands\pestle_cli($argv, $options);
}
