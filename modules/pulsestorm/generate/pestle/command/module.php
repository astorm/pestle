<?php
namespace Pulsestorm\Generate\Pestle\Command;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Mage2_Command\pestle_cli_exported');

/**
* Generates pestle command boiler plate
* This command creates the necessary files 
* for a pestle command
*
*     pestle.phar pestle:generate_command command_name
*
* @command pestle:generate_command
* @argument command_name New Command Name? [foo_bar]
* @argument namespace_module Create in PHP Namespace? [Pulsestorm\Magento2\Cli]
*/
function pestle_cli($argv, $options)
{
    return pestle_cli_exported($argv, $options);
}
