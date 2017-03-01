<?php
namespace Pulsestorm\Pestle\Clear_Cache;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Pestle_Clear_Cache\pestle_cli_exported');

/**
* BETA: Clears the pestle cache
*
* @command pestle:clear-cache
*/
function pestle_cli($argv, $options)
{
    return pestle_cli_exported($argv, $options);
}
